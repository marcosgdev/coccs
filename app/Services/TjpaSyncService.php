<?php

namespace GestContratos\Services;

use GestContratos\Core\Database;

/**
 * Sincroniza contratos vigentes do GRP Thema (dados abertos TJPA) com o banco local.
 *
 * Formato real da API (verificado em campo):
 *  - numero / numeroExibicao  → número oficial do contrato (exibição)
 *  - contrato                 → ID interno do sistema GRP (armazenado em processo)
 *  - valorTotalOriginal / valorTotal
 *  - dataValidadeFinal        (DD/MM/YYYY)
 *  - ajustes[]                (aditivos embutidos na resposta)
 *  - empenhos[]               (empenhos embutidos, com valor empenhado)
 */
final class TjpaSyncService
{
    private TjpaApiService $api;
    private ContractRulesService $rules;

    public function __construct()
    {
        $this->api   = new TjpaApiService();
        $this->rules = new ContractRulesService();
    }

    /** @return array{created:int,updated:int,errors:int,total:int,duration:float,messages:string[]} */
    public function sync(): array
    {
        $start    = microtime(true);
        $created  = $updated = $errors = 0;
        $skippedArps = 0;
        $messages = [];
        $empenhosColetados = []; // acumula todos os empenhos para buscar liquidações no final

        $rawContratos = $this->api->searchContratos([]);

        // Diagnóstico: situações e anos retornados pela API
        $situacoesEncontradas = array_count_values(
            array_map(fn($c) => trim($c['descricaoSituacaoContrato'] ?? '(vazio)'), $rawContratos)
        );
        arsort($situacoesEncontradas);
        $anosEncontrados = array_count_values(
            array_map(fn($c) => (int) ($c['exercicio'] ?? 0), $rawContratos)
        );
        ksort($anosEncontrados);
        $messages[] = count($rawContratos) . ' contratos únicos da API. Situações: ' .
            implode(' | ', array_map(fn($k, $v) => "\"$k\"=$v", array_keys($situacoesEncontradas), $situacoesEncontradas));
        $messages[] = 'Anos na API: ' .
            implode(', ', array_map(fn($k, $v) => "$k($v)", array_keys($anosEncontrados), $anosEncontrados));

        // Sem filtro de situação: sincroniza tudo que a API retorna (exceto ARPs identificadas abaixo).

        if (empty($rawContratos)) {
            return $this->result($created, $updated, $errors, $messages, $start);
        }

        $pdo = Database::pdo();

        foreach ($rawContratos as $c) {
            try {
                $numero    = trim((string) ($c['numero'] ?? $c['numeroExibicao'] ?? ''));
                $exercicio = (int) ($c['exercicio'] ?? 0);

                if (!$numero || !$exercicio) {
                    $errors++;
                    continue;
                }
                if ($this->detectTipo($c) !== 'CONTRATO') {
                    $skippedArps++;
                    continue;
                }

                $row  = $this->mapToLocal($c);
                $row  = $this->rules->normalize($row);
                $row['situacao'] = 'Vigente'; // API retornou Ativo → sempre vigente independente de data

                // Busca pelo ID interno GRP (processo) primeiro
                // Inclui soft-deleted: se a API retornou o contrato como Ativo,
                // deve ser restaurado mesmo que esteja marcado como excluído localmente.
                $contratoInterno = (string) ($c['contrato'] ?? '');
                $id = false;
                $wasSoftDeleted = false;
                if ($contratoInterno !== '') {
                    $s = $pdo->prepare(
                        'SELECT id, deleted_at FROM contratos WHERE processo = ? AND ano = ? LIMIT 1'
                    );
                    $s->execute([$contratoInterno, $exercicio]);
                    $found = $s->fetch(\PDO::FETCH_ASSOC);
                    if ($found) {
                        $id = $found['id'];
                        $wasSoftDeleted = $found['deleted_at'] !== null;
                    }
                }
                if (!$id) {
                    $s = $pdo->prepare(
                        'SELECT id, deleted_at FROM contratos WHERE chave = ? LIMIT 1'
                    );
                    $s->execute([$row['chave']]);
                    $found = $s->fetch(\PDO::FETCH_ASSOC);
                    if ($found) {
                        $id = $found['id'];
                        $wasSoftDeleted = $found['deleted_at'] !== null;
                    }
                }

                if ($id) {
                    if ($wasSoftDeleted) {
                        // Restaura registro soft-deleted: API confirma que está Ativo
                        $pdo->prepare('UPDATE contratos SET deleted_at = NULL WHERE id = ?')
                            ->execute([$id]);
                    }
                    $this->update($pdo, (int) $id, $row);
                    $updated++;
                } else {
                    $id = $this->insert($pdo, $row);
                    $created++;
                }

                if ($id && !empty($c['ajustes'])) {
                    $this->syncAditivos($pdo, (int) $id, $c['ajustes']);
                }
                if ($id && is_array($c['documentos'] ?? null) && count($c['documentos'])) {
                    $this->syncDocumentos($pdo, (int) $id, $c['documentos']);
                }
                if ($id) {
                    if (is_array($c['itensContratados'] ?? null)) {
                        $this->syncItens($pdo, (int) $id, $c['itensContratados']);
                    }
                    if (is_array($c['empenhos'] ?? null)) {
                        $this->syncEmpenhos($pdo, (int) $id, $c['empenhos']);
                        // Coleta empenhos para busca de liquidação em lote após o loop
                        foreach ($c['empenhos'] as $e) {
                            $emp = trim($e['empenho'] ?? '');
                            if ($emp !== '') {
                                $empenhosColetados[] = $emp;
                            }
                        }
                    }
                    if (is_array($c['eventos'] ?? null)) {
                        $this->syncEventos($pdo, (int) $id, $c['eventos']);
                    }
                }

            } catch (\Throwable $e) {
                $errors++;
                $messages[] = 'Erro em ' . ($c['numero'] ?? '?') . '/' . ($c['exercicio'] ?? '?') . ': ' . $e->getMessage();
            }
        }

        if ($skippedArps > 0) {
            $messages[] = $skippedArps . ' ARP(s) ignorada(s) na API. Atas devem ser importadas por planilha.';
        }

        // ── Busca liquidações em lote para todos os empenhos coletados ──────────
        if (!empty($empenhosColetados)) {
            $empenhosColetados = array_unique($empenhosColetados);
            try {
                $liquidacoes = $this->api->fetchLiquidacoes($empenhosColetados);

                // Busca contrato_id de cada empenho para gravar liquidacoes individuais
                $empToContrato = [];
                $stmtMap = $pdo->prepare(
                    'SELECT empenho, contrato_id FROM contrato_empenhos WHERE empenho = ?'
                );
                foreach ($empenhosColetados as $e) {
                    $stmtMap->execute([$e]);
                    $r = $stmtMap->fetch();
                    if ($r) $empToContrato[$e] = (int) $r['contrato_id'];
                }

                $upd = $pdo->prepare(
                    'UPDATE contrato_empenhos SET valor_liquidado=?, valor_pago=?, liquidado_em=NOW()
                     WHERE empenho=?'
                );
                $insLiq = $pdo->prepare(
                    'INSERT INTO contrato_liquidacoes
                        (contrato_id, empenho, liquidacao, data_liquidacao, valor_liquidacao, observacao)
                     VALUES (?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE
                        data_liquidacao=VALUES(data_liquidacao),
                        valor_liquidacao=VALUES(valor_liquidacao),
                        observacao=VALUES(observacao)'
                );

                foreach ($liquidacoes as $emp => $vals) {
                    $upd->execute([$vals['valorLiquidacao'], $vals['valorPago'], $emp]);

                    $contratoId = $empToContrato[$emp] ?? null;
                    if (!$contratoId) continue;
                    foreach ($vals['liquidacoes'] as $liq) {
                        if (empty($liq['liquidacao']) || empty($liq['dataLiquidacao'])) continue;
                        $insLiq->execute([
                            $contratoId,
                            $emp,
                            $liq['liquidacao'],
                            $this->parseDateBr($liq['dataLiquidacao']),
                            (float) ($liq['valorLiquidacao'] ?? 0),
                            $liq['observacao'] ?? null,
                        ]);
                    }
                }
                $messages[] = count($liquidacoes) . ' empenhos com liquidação atualizada.';

                // Atualiza valor_executado em contratos com a soma das liquidações reais
                $pdo->exec("
                    UPDATE contratos c
                    SET valor_executado = COALESCE((
                        SELECT SUM(COALESCE(ce.valor_liquidado, 0))
                        FROM contrato_empenhos ce
                        WHERE ce.contrato_id = c.id
                    ), 0)
                    WHERE c.deleted_at IS NULL AND c.tipo = 'CONTRATO'
                ");
            } catch (\Throwable $e) {
                $messages[] = 'Aviso: falha ao buscar liquidações — ' . $e->getMessage();
            }
        }

        return $this->result($created, $updated, $errors, $messages, $start);
    }

    // ── Mapeamento API → local ─────────────────────────────────────────────────

    private function mapToLocal(array $c): array
    {
        $numero    = trim((string) ($c['numero'] ?? $c['numeroExibicao'] ?? ''));

        // Prefere o campo exercicio do contrato; como fallback usa o ano de dataAssinatura/dataInicio
        // (nunca usa date('Y') pois jogaria contratos de anos diferentes em 2026).
        $exercicio = (int) ($c['exercicio'] ?? 0);
        if (!$exercicio) {
            $dataRef = $c['dataAssinatura'] ?? $c['dataInicio'] ?? $c['dataValidadeFinal'] ?? '';
            if ($dataRef && preg_match('/(\d{4})$/', $dataRef, $m)) {
                $exercicio = (int) $m[1];
            }
        }
        if (!$exercicio) {
            $exercicio = (int) ($c['exercicioLicitacao'] ?? 0);
        }

        $tipo      = 'CONTRATO';
        $ajustes   = $c['ajustes']  ?? [];
        $empenhos  = $c['empenhos'] ?? [];

        $valorOriginal   = (float) ($c['valorTotalOriginal'] ?? $c['valorOriginal'] ?? 0);
        $valorAtualizado = (float) ($c['valorTotal'] ?? $valorOriginal);
        $valorExecutado  = (float) array_sum(array_column($empenhos, 'valor'));

        $licitacao = trim(
            ($c['numeroLicitacao'] ?? '') .
            ($c['exercicioLicitacao'] ? '/' . $c['exercicioLicitacao'] : '')
        );

        $qtdAditivos = count(array_unique(array_filter(array_column($ajustes, 'numeroAditivo'))));

        $gestores = $this->extractGestores(is_array($c['gestores'] ?? null) ? $c['gestores'] : []);

        return [
            'tipo'                      => $tipo,
            'numero'                    => $numero,
            'ano'                       => $exercicio,
            'chave'                     => $tipo . $numero . '/' . $exercicio,
            'fornecedor_nome'           => $c['nomeContratado'] ?? null,
            'cnpj_cpf'                  => $c['documentoContratado'] ?? null,
            'objeto'                    => $c['objeto'] ?? null,
            'forma_contratacao_nome'    => $c['descricaoModalidade'] ?? null,
            'natureza_contratacao_nome' => $c['descricaoTipo'] ?? null,
            'licitacao_numero'          => $licitacao ?: null,
            'processo'                  => (string) ($c['contrato'] ?? ''),
            'setor_nome'                => $this->extractUnidade($c['descricaoLocalGestor'] ?? ''),
            'data_inicio'               => $this->parseDateBr($c['dataInicio'] ?? $c['dataAssinatura'] ?? ''),
            'data_termino'              => $this->parseDateBr($c['dataValidadeFinal'] ?? $c['dataFimVigencia'] ?? ''),
            'valor_global_inicial'      => $valorOriginal,
            'valor_global_atualizado'   => $valorAtualizado,
            'valor_executado'           => $valorExecutado,
            'valor_acumulado_executado' => 0,
            'quantidade_aditivos'       => $qtdAditivos,
            'texto_notificacao'         => '',
            ...$gestores,
        ];
    }

    /**
     * Converte o array gestores[] da API nos campos locais correspondentes.
     * Mapeamento por nomeChave (case-insensitive, sem acentos).
     */
    private function extractGestores(array $gestores): array
    {
        $map = [
            'gestor'                => ['gestor'],
            'gestor substituto'     => ['gestor_substituto'],
            'fiscal tecnico'        => ['fiscal_tecnico'],
            'fiscal técnico'        => ['fiscal_tecnico'],
            'fiscal demandante'     => ['fiscal_demandante'],
            'fiscal administrativo' => ['fiscal_administrativo'],
            'fiscal substituto'     => ['fiscal_substituto'],
        ];
        $result = [];
        foreach ($gestores as $g) {
            $chave = mb_strtolower(trim($g['nomeChave'] ?? ''));
            $nome  = trim($g['nome'] ?? '');
            if (!$nome) continue;
            foreach ($map as $key => $cols) {
                if ($chave === $key) {
                    foreach ($cols as $col) {
                        $result[$col] = ($result[$col] ?? '') !== ''
                            ? $result[$col] . '; ' . $nome
                            : $nome;
                    }
                }
            }
        }
        return $result;
    }

    private function detectTipo(array $c): string
    {
        $desc = strtolower(trim($c['descricaoTipo'] ?? ''));
        // "Ata de Registro de Preços" começa com "ata" → ARP.
        // "Contrato Resultante de Ata" contém "ata" mas é CONTRATO: não deve ser rejeitado.
        $isArp = str_starts_with($desc, 'ata') || str_starts_with($desc, 'arp');
        return $isArp ? 'ARP' : 'CONTRATO';
    }

    private function extractUnidade(string $localGestor): ?string
    {
        if (!$localGestor) return null;
        $parts = array_values(array_filter(array_map('trim', explode('\\', $localGestor))));
        $nome  = $parts[2] ?? $parts[array_key_last($parts)] ?? null;
        if ($nome === null) return null;
        return $this->aplicarMapeamentoSetor($nome);
    }

    private ?array $mapeamentosCache = null;

    private function aplicarMapeamentoSetor(string $nome): string
    {
        if ($this->mapeamentosCache === null) {
            try {
                $rows = Database::pdo()->query(
                    "SELECT nome_origem, nome_destino FROM setor_mapeamentos WHERE ativo = 1"
                )->fetchAll(\PDO::FETCH_KEY_PAIR);
                $this->mapeamentosCache = $rows ?: [];
            } catch (\Throwable) {
                $this->mapeamentosCache = [];
            }
        }
        return $this->mapeamentosCache[$nome] ?? $nome;
    }

    /** Converte DD/MM/YYYY → YYYY-MM-DD. Aceita também ISO. */
    private function parseDateBr(string $val): ?string
    {
        if (!$val) return null;
        $val = stripslashes($val);
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $val, $m)) return "$m[3]-$m[2]-$m[1]";
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/',    $val, $m)) return "$m[1]-$m[2]-$m[3]";
        return null;
    }

    // ── Sync de itens contratados ─────────────────────────────────────────────

    private function syncItens(\PDO $pdo, int $contratoId, array $itens): void
    {
        foreach ($itens as $it) {
            $item = (int) ($it['item'] ?? 0);
            if (!$item) continue;
            $pdo->prepare(
                'INSERT INTO contrato_itens (contrato_id, item, descricao, unidade, quantidade, preco_unitario, preco_total)
                 VALUES (?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE descricao=VALUES(descricao), unidade=VALUES(unidade),
                 quantidade=VALUES(quantidade), preco_unitario=VALUES(preco_unitario), preco_total=VALUES(preco_total)'
            )->execute([
                $contratoId, $item,
                $it['descricao'] ?? null,
                trim($it['unidade'] ?? ''),
                (float) ($it['quantidade'] ?? 0),
                (float) ($it['precoUnitario'] ?? 0),
                (float) ($it['precoTotal'] ?? 0),
            ]);
        }
    }

    // ── Sync de empenhos ──────────────────────────────────────────────────────

    private function syncEmpenhos(\PDO $pdo, int $contratoId, array $empenhos): void
    {
        foreach ($empenhos as $e) {
            $emp = trim($e['empenho'] ?? '');
            if (!$emp) continue;
            $pdo->prepare(
                'INSERT INTO contrato_empenhos (contrato_id, empenho, data_empenho, valor)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE data_empenho=VALUES(data_empenho), valor=VALUES(valor)'
            )->execute([
                $contratoId, $emp,
                $this->parseDateBr($e['dataEmpenho'] ?? ''),
                (float) ($e['valor'] ?? 0),
            ]);
        }
    }

    // ── Sync de eventos ───────────────────────────────────────────────────────

    private function syncEventos(\PDO $pdo, int $contratoId, array $eventos): void
    {
        foreach ($eventos as $ev) {
            $ordem = (int) ($ev['ordem'] ?? 0);
            if (!$ordem) continue;
            $pdo->prepare(
                'INSERT INTO contrato_eventos (contrato_id, ordem, descricao, data)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE descricao=VALUES(descricao), data=VALUES(data)'
            )->execute([
                $contratoId, $ordem,
                $ev['descricao'] ?? null,
                $this->parseDateBr($ev['data'] ?? ''),
            ]);
        }
    }

    // ── Sync de documentos ────────────────────────────────────────────────────

    private function syncDocumentos(\PDO $pdo, int $contratoId, array $documentos): void
    {
        foreach ($documentos as $d) {
            $numDoc = (int) ($d['numero'] ?? 0);
            if (!$numDoc) continue;
            $pdo->prepare(
                'INSERT INTO contrato_documentos (contrato_id, numero_doc, identificacao, data_documento, tipo)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE identificacao=VALUES(identificacao), data_documento=VALUES(data_documento), tipo=VALUES(tipo)'
            )->execute([
                $contratoId,
                $numDoc,
                $d['identificacao'] ?? null,
                $this->parseDateBr($d['dataDocumento'] ?? ''),
                $d['tipo'] ?? null,
            ]);
        }
    }

    // ── Sync de aditivos ───────────────────────────────────────────────────────

    private function syncAditivos(\PDO $pdo, int $contratoId, array $ajustes): void
    {
        // Garante coluna valor_prorrogacao (idempotente)
        try {
            $pdo->exec("ALTER TABLE aditivos ADD COLUMN valor_prorrogacao DECIMAL(15,2) NOT NULL DEFAULT 0");
        } catch (\PDOException) { /* já existe */ }

        // Processa cada ajuste expandindo suas alteracoes internas.
        // Cada sub-alteração com dataFinal → componente de prorrogação.
        // Demais sub-alterações → componente de ajuste financeiro (acréscimo/reajuste/etc.).
        // Agrupa por numeroAditivo mantendo um registro por aditivo.
        $grouped = [];
        foreach ($ajustes as $a) {
            $num = (string) ($a['numeroAditivo'] ?? '');
            if ($num === '') continue;

            if (!isset($grouped[$num])) {
                $grouped[$num] = [
                    'numero_aditivo'    => $num,
                    'tipo_aditivo'      => null,
                    'data_aditivo'      => null,
                    'objeto'            => null,
                    'valor_acrescido'   => 0.0,
                    'valor_suprimido'   => 0.0,
                    'valor_prorrogacao' => 0.0,
                    'nova_data_termino' => null,
                ];
            }

            if (!empty($a['dataCelebracao'])) {
                $grouped[$num]['data_aditivo'] = $this->parseDateBr($a['dataCelebracao']);
            }
            if ($a['descricao'] ?? null) {
                $grouped[$num]['objeto'] ??= $a['descricao'];
            }

            // O endpoint /contrato/ entrega ajustes[] já planos: cada entrada é uma
            // sub-alteração individual com campos próprios (descricaoTipo, valorAditivo,
            // dataFinal). Não existe nível aninhado "alteracoes" nesse contexto.
            // O endpoint /ajusteContrato/ (fetchAditivosDetalhados) usa estrutura aninhada,
            // mas não passa por aqui.
            $v         = (float) ($a['valorAditivo'] ?? 0);
            $tipo      = (string) ($a['descricaoTipo'] ?? '');
            $dataFinal = (string) ($a['dataFinal'] ?? '');

            // É prorrogação se tipo contém 'prorrog' OU se dataFinal está preenchida
            $isProrrog = stripos($tipo, 'prorrog') !== false || $dataFinal !== '';

            if ($isProrrog) {
                $grouped[$num]['valor_prorrogacao'] += max(0, $v);
                if ($dataFinal !== '') {
                    // Normaliza para YYYY-MM-DD
                    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dataFinal, $m)) {
                        $grouped[$num]['nova_data_termino'] = "$m[1]-$m[2]-$m[3]";
                    } elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $dataFinal, $m)) {
                        $grouped[$num]['nova_data_termino'] = "$m[3]-$m[2]-$m[1]";
                    }
                }
            } else {
                if ($v > 0) $grouped[$num]['valor_acrescido'] += $v;
                if ($v < 0) $grouped[$num]['valor_suprimido'] += abs($v);
            }

            if ($tipo && !$grouped[$num]['tipo_aditivo']) {
                $grouped[$num]['tipo_aditivo'] = $tipo;
            }
        }

        foreach ($grouped as $row) {
            $exists = $pdo->prepare(
                'SELECT id FROM aditivos WHERE contrato_id = ? AND numero_aditivo = ? AND deleted_at IS NULL LIMIT 1'
            );
            $exists->execute([$contratoId, $row['numero_aditivo']]);
            if ($exists->fetchColumn()) {
                $pdo->prepare(
                    'UPDATE aditivos SET tipo_aditivo=?, data_aditivo=?, objeto=?,
                     valor_acrescido=?, valor_suprimido=?, valor_prorrogacao=?, nova_data_termino=?
                     WHERE contrato_id=? AND numero_aditivo=? AND deleted_at IS NULL'
                )->execute([
                    $row['tipo_aditivo'], $row['data_aditivo'], $row['objeto'],
                    $row['valor_acrescido'], $row['valor_suprimido'], $row['valor_prorrogacao'],
                    $row['nova_data_termino'],
                    $contratoId, $row['numero_aditivo'],
                ]);
            } else {
                $pdo->prepare(
                    'INSERT INTO aditivos
                     (contrato_id, numero_aditivo, tipo_aditivo, data_aditivo, objeto,
                      valor_acrescido, valor_suprimido, valor_prorrogacao, nova_data_termino)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $contratoId, $row['numero_aditivo'], $row['tipo_aditivo'], $row['data_aditivo'],
                    $row['objeto'], $row['valor_acrescido'], $row['valor_suprimido'],
                    $row['valor_prorrogacao'], $row['nova_data_termino'],
                ]);
            }
        }
    }

    // ── Persistência ──────────────────────────────────────────────────────────

    private const SYNC_COLS = [
        'tipo', 'numero', 'ano', 'chave', 'processo',
        'fornecedor_nome', 'cnpj_cpf', 'objeto',
        'forma_contratacao_nome', 'natureza_contratacao_nome', 'licitacao_numero',
        'setor_nome', 'data_inicio', 'data_termino',
        'valor_global_inicial', 'valor_global_atualizado',
        'valor_executado', 'valor_acumulado_executado', 'quantidade_aditivos',
        'gestor', 'gestor_substituto', 'fiscal_tecnico',
        'fiscal_demandante', 'fiscal_administrativo', 'fiscal_substituto',
        'situacao', 'prazo', 'dias_contrato', 'dias_restantes',
        'trimestre_vencimento', 'prazo_prorrogacao', 'prazo_legal_classificacao',
        'status_reajuste',
    ];

    private const PROTECTED = [
        'emails_equipe', 'observacoes', 'texto_notificacao', 'contrato_estrategico',
        'data_recebimento_prorrogacao', 'data_orcamento_estimado',
    ];

    private function insert(\PDO $pdo, array $row): int
    {
        $cols = array_filter(
            array_intersect_key($row, array_flip(self::SYNC_COLS)),
            fn ($v) => $v !== null
        );
        $colList = implode(', ', array_keys($cols));
        $phList  = implode(', ', array_fill(0, count($cols), '?'));
        $pdo->prepare("INSERT INTO contratos ($colList) VALUES ($phList)")
            ->execute(array_values($cols));
        return (int) $pdo->lastInsertId();
    }

    private function update(\PDO $pdo, int $id, array $row): void
    {
        $cols = array_filter(
            array_intersect_key($row, array_flip(self::SYNC_COLS)),
            fn ($v, $k) => $v !== null && !in_array($k, self::PROTECTED, true),
            ARRAY_FILTER_USE_BOTH
        );
        $set = implode(', ', array_map(fn ($k) => "$k = ?", array_keys($cols)));
        $pdo->prepare("UPDATE contratos SET $set WHERE id = ?")
            ->execute([...array_values($cols), $id]);
    }

    private function result(int $c, int $u, int $e, array $msgs, float $start): array
    {
        return [
            'created'  => $c,
            'updated'  => $u,
            'errors'   => $e,
            'total'    => $c + $u,
            'duration' => round(microtime(true) - $start, 1),
            'messages' => $msgs,
        ];
    }
}

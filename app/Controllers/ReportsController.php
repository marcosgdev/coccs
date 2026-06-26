<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Database;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;
use GestContratos\Services\ArpValuesSpreadsheetService;
use GestContratos\Services\AuditService;
use GestContratos\Services\DocxService;
use GestContratos\Services\SetorNormalizerService;
use GestContratos\Services\UploadService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

final class ReportsController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $type = $request->query['tipo'] ?? 'contratos_vigentes';
        [$title, $rows] = $this->build($type, $request->query);

        if (($request->query['export'] ?? '') === 'csv') {
            $this->csv($title, $rows);
            return;
        }

        $this->view('reports/index', [
            'title' => 'Relatorios',
            'reportTitle' => $title,
            'type' => $type,
            'rows' => $rows,
            'filters' => $request->query,
            'arpValuesResult' => $_SESSION['arp_values_result'] ?? null,
        ]);
        unset($_SESSION['arp_values_result']);
    }

    public function uploadArpValues(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
        $this->validateCsrf($request);

        try {
            $relative = (new UploadService())->store($request->files['planilha_atas'] ?? [], 'imports', ['xlsx', 'xlsm', 'xls']);
            if (! $relative) {
                throw new \RuntimeException('Envie uma planilha .xlsx, .xlsm ou .xls.');
            }

            $result = (new ArpValuesSpreadsheetService())->updateFromSpreadsheet(base_path($relative));
            $_SESSION['arp_values_result'] = $result;

            (new AuditService())->log('atualizacao_valores_atas', 'contratos', null, [], [
                'arquivo' => $relative,
                'resultado' => $result,
            ]);

            $message = "{$result['atualizadas']} ata(s) atualizada(s).";
            if ($result['sem_correspondencia'] > 0) {
                $message .= " {$result['sem_correspondencia']} linha(s) sem correspondencia.";
            }
            flash(empty($result['erros']) ? 'success' : 'warning', $message);
        } catch (\Throwable $exception) {
            flash('danger', 'Falha ao atualizar valores das atas: ' . $exception->getMessage());
        }

        redirect('/relatorios');
    }

    public function secretariaPdf(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $pdo       = Database::pdo();
        $situacao  = $request->query['situacao'] ?? 'Vigente';
        $allowed   = ['Vigente', 'Expirado', 'todos'];
        if (!in_array($situacao, $allowed)) $situacao = 'Vigente';

        $where = $situacao === 'todos' ? '' : "AND situacao = " . $pdo->quote($situacao);

        $rows = $pdo->query("
            SELECT setor_nome, chave, fornecedor_nome, situacao, objeto,
                   data_inicio, data_termino, valor_global_atualizado, valor_executado,
                   gestor, fiscal_tecnico,
                   DATEDIFF(data_termino, CURDATE()) AS dias_restantes
            FROM contratos
            WHERE deleted_at IS NULL $where
            ORDER BY setor_nome ASC, data_termino ASC
        ")->fetchAll();

        // Agrupa por secretaria
        $normalizer  = new SetorNormalizerService($pdo);
        $secretarias = [];
        foreach ($rows as $r) {
            $s = $normalizer->normalize($r['setor_nome']);
            $secretarias[$s]['contratos'][] = $r;
            $secretarias[$s]['valor_total']     = ($secretarias[$s]['valor_total']     ?? 0) + (float)$r['valor_global_atualizado'];
            $secretarias[$s]['valor_executado'] = ($secretarias[$s]['valor_executado'] ?? 0) + (float)$r['valor_executado'];
        }
        ksort($secretarias);

        // Totais gerais
        $totalContratos   = count($rows);
        $totalValor       = array_sum(array_column($secretarias, 'valor_total'));
        $totalValorExec   = array_sum(array_column($secretarias, 'valor_executado'));

        $viewData = [
            'title'           => 'Relatório — Contratos e Atas por Secretaria',
            'secretarias'     => $secretarias,
            'totalContratos'  => $totalContratos,
            'totalValor'      => $totalValor,
            'totalValorExec'  => $totalValorExec,
            'situacao'        => $situacao,
            'geradoEm'        => date('d/m/Y \à\s H:i'),
        ];

        if (($request->query['export'] ?? '') === 'xlsx') {
            $this->exportXlsxSecretariaPdf($viewData);
        }

        $this->view('reports/secretaria_pdf', $viewData, 'layouts/print');
    }

    private function exportSecretariaDocx(array $data): never
    {
        $secretarias    = $data['secretarias'];
        $totalContratos = $data['totalContratos'];
        $totalValor     = $data['totalValor'];
        $situacao       = $data['situacao'];
        $geradoEm       = $data['geradoEm'];

        $brl = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body  { font-family: Calibri, Arial, sans-serif; font-size: 10pt; }
            h1    { font-size: 16pt; color: #1a3a5c; margin-bottom: 2pt; }
            .sub  { font-size: 9pt; color: #64748b; margin-bottom: 14pt; }
            .kpi  { margin-bottom: 14pt; font-size: 9pt; }
            .kpi strong { font-size: 11pt; }
            table { border-collapse: collapse; width: 100%; font-size: 8.5pt; margin-bottom: 18pt; }
            th    { background: #1a3a5c; color: #fff; padding: 5pt 6pt; text-align: left; font-size: 8pt; }
            td    { padding: 4pt 6pt; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
            tr:nth-child(even) td { background: #f8fafc; }
            .num  { text-align: right; white-space: nowrap; }
            h2    { font-size: 11pt; color: #1a3a5c; margin: 18pt 0 4pt; border-bottom: 1px solid #e2e8f0; padding-bottom: 3pt; }
        </style></head><body>';

        $html .= '<h1>Relatório de Contratos e Atas Vigentes</h1>';
        $html .= '<div class="sub">Instrumentos agrupados por secretaria/setor &nbsp;·&nbsp; ' . htmlspecialchars($geradoEm) . ' · GestContratos TJPA</div>';
        $html .= '<div class="kpi">';
        $html .= 'Total: <strong>' . $totalContratos . '</strong> &nbsp;&nbsp; ';
        $html .= 'Secretarias: <strong>' . count($secretarias) . '</strong> &nbsp;&nbsp; ';
        $html .= 'Valor Total: <strong>' . $brl($totalValor) . '</strong>';
        $html .= '</div>';

        foreach ($secretarias as $nome => $sec) {
            $contratos = $sec['contratos'];
            $html .= '<h2>' . htmlspecialchars($nome) . ' (' . count($contratos) . ' instrumento' . (count($contratos) > 1 ? 's' : '') . ')</h2>';
            $html .= '<table><thead><tr>
                <th>Contrato / Ata</th><th>Fornecedor</th><th>Situação</th>
                <th>Início</th><th>Término</th>
                <th style="text-align:right">Valor Atual</th><th>Gestor</th>
            </tr></thead><tbody>';

            foreach ($contratos as $c) {
                $html .= '<tr>';
                $html .= '<td><strong>' . htmlspecialchars($c['chave'] ?? '—') . '</strong></td>';
                $html .= '<td>' . htmlspecialchars(mb_substr($c['fornecedor_nome'] ?? '—', 0, 45)) . '</td>';
                $html .= '<td>' . htmlspecialchars($c['situacao'] ?? '—') . '</td>';
                $html .= '<td>' . ($c['data_inicio']  ? date('d/m/Y', strtotime($c['data_inicio']))  : '—') . '</td>';
                $html .= '<td>' . ($c['data_termino'] ? date('d/m/Y', strtotime($c['data_termino'])) : '—') . '</td>';
                $html .= '<td class="num">' . $brl((float)$c['valor_global_atualizado']) . '</td>';
                $html .= '<td>' . htmlspecialchars(mb_substr($c['gestor'] ?? '—', 0, 30)) . '</td>';
                $html .= '</tr>';
            }

            $html .= '<tr><td colspan="5" style="text-align:right;font-weight:bold">Subtotal</td>';
            $html .= '<td class="num"><strong>' . $brl($sec['valor_total']) . '</strong></td><td></td></tr>';
            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        $slug     = match($situacao) { 'Vigente' => 'vigentes', 'Expirado' => 'expirados', default => 'todos' };
        $filename = 'contratos-arps-' . $slug . '-' . date('Y-m-d') . '.doc';
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        echo $html;
        exit;
    }

    public function bienios(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $pdo = Database::pdo();

        $config = [
            '2021-2023' => [2021, 2022],
            '2023-2025' => [2023, 2024],
            '2025-2027' => [2025, 2026],
        ];

        $bienios = [];
        foreach ($config as $key => [$a1, $a2]) {
            $st = $pdo->prepare("
                SELECT
                    COUNT(*)                                                                   AS total,
                    SUM(tipo = 'ARP')                                                          AS total_arps,
                    SUM(tipo = 'CONTRATO')                                                     AS total_contratos,
                    SUM(situacao = 'Vigente')                                                  AS vigentes,
                    SUM(situacao = 'Expirado')                                                 AS expirados,
                    COALESCE(SUM(valor_global_atualizado), 0)                                  AS valor_total,
                    COALESCE(SUM(valor_global_inicial), 0)                                     AS valor_inicial_total,
                    COALESCE(AVG(valor_global_atualizado), 0)                                  AS valor_medio,
                    SUM(COALESCE(quantidade_aditivos, 0) > 0)                                  AS com_aditivos,
                    COALESCE(SUM(quantidade_aditivos), 0)                                      AS total_aditivos,
                    SUM(gestor IS NOT NULL AND gestor <> '' AND gestor <> 'sem indicação')     AS com_gestor,
                    SUM(
                        (fiscal_tecnico IS NOT NULL AND fiscal_tecnico <> '' AND fiscal_tecnico <> 'sem indicação')
                        OR (fiscal_demandante IS NOT NULL AND fiscal_demandante <> '' AND fiscal_demandante <> 'sem indicação')
                    )                                                                          AS com_fiscal,
                    COUNT(DISTINCT NULLIF(setor_nome, ''))                                     AS num_setores,
                    COUNT(DISTINCT NULLIF(fornecedor_nome, ''))                                AS num_fornecedores
                FROM contratos
                WHERE deleted_at IS NULL AND ano IN (?, ?)
            ");
            $st->execute([$a1, $a2]);
            $s = $st->fetch();

            $s['taxa_gestor']    = $s['total'] > 0 ? ($s['com_gestor']  / $s['total']) * 100 : 0;
            $s['taxa_fiscal']    = $s['total'] > 0 ? ($s['com_fiscal']  / $s['total']) * 100 : 0;
            $s['taxa_aditivos']  = $s['total'] > 0 ? ($s['com_aditivos'] / $s['total']) * 100 : 0;
            $s['taxa_vigentes']  = $s['total'] > 0 ? ($s['vigentes']    / $s['total']) * 100 : 0;
            $s['taxa_arps']      = $s['total'] > 0 ? ($s['total_arps']  / $s['total']) * 100 : 0;
            $s['reajuste_pct']   = $s['valor_inicial_total'] > 0
                ? (($s['valor_total'] - $s['valor_inicial_total']) / $s['valor_inicial_total']) * 100 : 0;
            $s['ieg']            = round(
                0.40 * $s['taxa_gestor']   +
                0.40 * $s['taxa_fiscal']   +
                0.20 * $s['taxa_aditivos'],
                1
            );

            $sn = (new SetorNormalizerService($pdo))->sqlCase('setor_nome', "'Sem setor'");
            $ss = $pdo->prepare("
                SELECT $sn AS setor_nome,
                       COUNT(*) AS qtd, COALESCE(SUM(valor_global_atualizado),0) AS valor
                FROM contratos WHERE deleted_at IS NULL AND ano IN (?,?)
                GROUP BY 1 ORDER BY qtd DESC LIMIT 5
            ");
            $ss->execute([$a1, $a2]);
            $s['top_setores'] = $ss->fetchAll();

            $sf = $pdo->prepare("
                SELECT COALESCE(NULLIF(fornecedor_nome,''),'Sem fornecedor') AS fornecedor_nome,
                       COUNT(*) AS qtd, COALESCE(SUM(valor_global_atualizado),0) AS valor
                FROM contratos WHERE deleted_at IS NULL AND ano IN (?,?)
                GROUP BY fornecedor_nome ORDER BY valor DESC LIMIT 5
            ");
            $sf->execute([$a1, $a2]);
            $s['top_fornecedores'] = $sf->fetchAll();

            $sy = $pdo->prepare("
                SELECT ano, tipo, COUNT(*) AS qtd, COALESCE(SUM(valor_global_atualizado),0) AS valor
                FROM contratos WHERE deleted_at IS NULL AND ano IN (?,?)
                GROUP BY ano, tipo ORDER BY ano, tipo
            ");
            $sy->execute([$a1, $a2]);
            $s['por_ano'] = $sy->fetchAll();

            $bienios[$key] = $s;
        }

        $this->view('reports/bienios', [
            'title'    => 'Análise Comparativa por Biênio',
            'bienios'  => $bienios,
            'geradoEm' => date('d/m/Y \à\s H:i'),
        ], 'layouts/print');
    }

    public function secretariaArps(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $pdo = Database::pdo();

        $rows = $pdo->query("
            SELECT tipo, numero, ano, chave, fornecedor_nome, objeto, setor_nome,
                   data_inicio, data_termino, valor_global_inicial, valor_global_atualizado,
                   valor_executado, gestor, fiscal_tecnico,
                   CASE
                       WHEN ano IN (2025, 2026) THEN '2025-2026'
                       WHEN ano IN (2023, 2024) THEN '2023-2024'
                       ELSE 'Demais'
                   END AS bienio
            FROM contratos
            WHERE deleted_at IS NULL AND situacao = 'Vigente'
            ORDER BY setor_nome ASC, tipo ASC, CAST(numero AS UNSIGNED) ASC, numero ASC
        ")->fetchAll();

        $normalizer   = new SetorNormalizerService($pdo);
        $secretarias  = [];
        $totalContratos = 0;
        $totalArps    = 0;
        $totalValor   = 0.0;
        $bienioStats  = [
            '2025-2026' => ['arps' => 0, 'contratos' => 0, 'valor_arp' => 0.0, 'valor_contrato' => 0.0],
            '2023-2024' => ['arps' => 0, 'contratos' => 0, 'valor_arp' => 0.0, 'valor_contrato' => 0.0],
            'Demais'    => ['arps' => 0, 'contratos' => 0, 'valor_arp' => 0.0, 'valor_contrato' => 0.0],
        ];

        foreach ($rows as $r) {
            $s      = $normalizer->normalize($r['setor_nome']);
            $bienio = $r['bienio'] ?: 'Demais';
            $valor  = (float) $r['valor_global_atualizado'];
            $isArp  = $r['tipo'] === 'ARP';

            if (! isset($secretarias[$s])) {
                $secretarias[$s] = ['itens' => [], 'qtd_arp' => 0, 'qtd_contrato' => 0, 'valor_arp' => 0.0, 'valor_contrato' => 0.0];
            }
            $secretarias[$s]['itens'][] = $r;
            if ($isArp) {
                $secretarias[$s]['qtd_arp']++;
                $secretarias[$s]['valor_arp'] += $valor;
                $totalArps++;
            } else {
                $secretarias[$s]['qtd_contrato']++;
                $secretarias[$s]['valor_contrato'] += $valor;
                $totalContratos++;
            }
            $totalValor += $valor;
            $bienioStats[$bienio][$isArp ? 'arps' : 'contratos']++;
            $bienioStats[$bienio][$isArp ? 'valor_arp' : 'valor_contrato'] += $valor;
        }
        ksort($secretarias);

        $viewDataArps = [
            'title'          => 'Relatório — Contratos e ARPs por Secretaria',
            'secretarias'    => $secretarias,
            'totalContratos' => $totalContratos,
            'totalArps'      => $totalArps,
            'totalGeral'     => $totalContratos + $totalArps,
            'totalValor'     => $totalValor,
            'bienioStats'    => $bienioStats,
            'geradoEm'       => date('d/m/Y \à\s H:i'),
        ];

        if (($request->query['export'] ?? '') === 'docx') {
            $this->exportDocxSecretariaArps($viewDataArps);
        }

        $this->view('reports/secretaria_arps', $viewDataArps, 'layouts/print');
    }

    public function secretariaContratos(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $pdo      = Database::pdo();
        $situacao = $request->query['situacao'] ?? 'Vigente';
        if (!in_array($situacao, ['Vigente', 'Expirado', 'todos'])) $situacao = 'Vigente';
        $where = $situacao === 'todos' ? '' : 'AND situacao = ' . $pdo->quote($situacao);

        $sn   = (new SetorNormalizerService($pdo))->sqlCase('setor_nome', "'Sem secretaria'");
        $rows = $pdo->query("
            SELECT
                $sn AS secretaria,
                COUNT(*)                                                   AS total,
                SUM(situacao = 'Vigente')                                  AS vigentes,
                SUM(situacao = 'Expirado')                                 AS expirados,
                SUM(DATEDIFF(data_termino, CURDATE()) BETWEEN 0 AND 30)   AS vence_30d,
                SUM(DATEDIFF(data_termino, CURDATE()) BETWEEN 0 AND 90)   AS vence_90d
            FROM contratos
            WHERE deleted_at IS NULL $where
            GROUP BY 1
            ORDER BY total DESC
        ")->fetchAll();

        $total = array_sum(array_column($rows, 'total'));
        $max   = $rows ? max(array_column($rows, 'total')) : 1;

        $this->view('reports/secretaria_contratos', [
            'title'    => 'Contratos por Secretaria',
            'rows'     => $rows,
            'total'    => $total,
            'max'      => $max,
            'situacao' => $situacao,
            'geradoEm' => date('d/m/Y \à\s H:i'),
        ], 'layouts/print');
    }

    public function additivosFinanceiros(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $pdo        = Database::pdo();
        $normalizer = new \GestContratos\Services\SetorNormalizerService($pdo);
        $sn         = $normalizer->sqlCase('c.setor_nome', "'Sem secretaria'");

        // Após sincronização correta:
        //   valor_prorrogacao (coluna dedicada) = soma das sub-alterações de prorrogação
        //   valor_acrescido                     = apenas ajustes não-prorrogação
        //   Valor Atual  = valor_global_inicial + SUM(valor_acrescido - valor_suprimido)
        //   Valor Total  = Valor Atual + SUM(valor_prorrogacao)
        //
        // Fallback (contratos não re-sincronizados, valor_prorrogacao = 0):
        //   prorrog_text = soma de valor_acrescido de aditivos com texto 'prorrog'
        //   Heurística: se valor_global_atualizado − prorrog_text >= inicial → atualizado é o Total
        //               senão → atualizado já é o Valor Atual
        $rows = $pdo->query("
            SELECT
                c.id,
                c.tipo,
                c.chave,
                c.fornecedor_nome,
                $sn                                                                AS setor_nome,
                c.data_inicio,
                c.data_termino,
                c.quantidade_aditivos,
                c.status_reajuste,
                c.valor_global_inicial,
                c.valor_global_atualizado,
                COALESCE(SUM(a.valor_prorrogacao), 0)  AS valor_prorrogacao,
                -- Reajuste / Apostilamento / Reequilíbrio
                COALESCE(SUM(CASE
                    WHEN LOWER(a.tipo_aditivo) LIKE '%reajust%'
                      OR LOWER(a.tipo_aditivo) LIKE '%reequil%'
                      OR LOWER(a.tipo_aditivo) LIKE '%apostil%'
                      OR LOWER(a.objeto)       LIKE '%reajust%'
                    THEN a.valor_acrescido - a.valor_suprimido
                    ELSE 0 END), 0)                AS valor_reajustes,
                -- Acréscimo / Supressão (exclui reajuste e prorrogação)
                COALESCE(SUM(CASE
                    WHEN LOWER(a.tipo_aditivo) NOT LIKE '%reajust%'
                     AND LOWER(a.tipo_aditivo) NOT LIKE '%reequil%'
                     AND LOWER(a.tipo_aditivo) NOT LIKE '%apostil%'
                     AND LOWER(a.tipo_aditivo) NOT LIKE '%prorrog%'
                     AND LOWER(a.objeto)       NOT LIKE '%reajust%'
                    THEN a.valor_acrescido - a.valor_suprimido
                    ELSE 0 END), 0)                AS valor_aditivos,
                MAX(a.valor_prorrogacao > 0)       AS sincronizado,
                CASE
                    WHEN c.tipo = 'ARP'
                    THEN COALESCE(c.valor_acumulado_executado, 0)
                    ELSE COALESCE((
                        SELECT SUM(ce.valor_liquidado)
                        FROM contrato_empenhos ce
                        WHERE ce.contrato_id = c.id
                    ), 0)
                END                                AS valor_executado_total
            FROM contratos c
            LEFT JOIN aditivos a ON a.contrato_id = c.id AND a.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
              AND c.situacao = 'Vigente'
              AND (
                    -- CONTRATO: exibe somente se houver aditivos com impacto financeiro
                    (c.tipo = 'CONTRATO' AND (
                        c.quantidade_aditivos > 0
                     OR c.valor_global_atualizado > c.valor_global_inicial
                     OR c.status_reajuste = 'Iniciar processo de reajuste'
                    ))
                    OR
                    -- ARP: exibe somente se o valor atual difere do valor original
                    (c.tipo = 'ARP' AND c.valor_global_atualizado <> c.valor_global_inicial)
              )
            GROUP BY c.id
            ORDER BY $sn ASC, c.data_termino ASC
        ")->fetchAll();

        foreach ($rows as &$r) {
            // Garante normalização mesmo que o SQL CASE não bata (ex: collation com acento)
            $r['setor_nome'] = $normalizer->normalize($r['setor_nome'], 'Sem secretaria');

            $vApiTotal  = (float)$r['valor_global_atualizado'];
            $vProrrog   = (float)$r['valor_prorrogacao'];
            $vReajustes = (float)$r['valor_reajustes'];
            $vOriginal  = (float)$r['valor_global_inicial'];

            if ($r['tipo'] === 'ARP') {
                // Para ARPs: Valor Total = Valor Original + Valor Atual (sem separar prorrogação)
                $r['valor_atual']    = $vApiTotal;
                $r['valor_total']    = $vOriginal + $vApiTotal;
            } else {
                // Para CONTRATOs: Valor Total = API valorTotal; Valor Atual = Total − Prorrogação
                if ($r['sincronizado']) {
                    $r['valor_total'] = $vApiTotal;
                    $r['valor_atual'] = $vApiTotal - $vProrrog;
                } else {
                    $r['valor_total'] = $vApiTotal;
                    $r['valor_atual'] = $vApiTotal;
                }
            }
            $r['valor_corrigido'] = $vOriginal + $vReajustes;
        }
        unset($r);

        // totais gerais
        $totalOriginal  = array_sum(array_column($rows, 'valor_global_inicial'));
        $totalReajustes = array_sum(array_column($rows, 'valor_reajustes'));
        $totalCorrigido = array_sum(array_column($rows, 'valor_corrigido'));
        $totalAditivos  = array_sum(array_column($rows, 'valor_aditivos'));
        $totalProrrog   = array_sum(array_column($rows, 'valor_prorrogacao'));
        $totalAtual     = array_sum(array_column($rows, 'valor_atual'));
        $totalTotal     = array_sum(array_column($rows, 'valor_total'));
        $totalExec2026  = array_sum(array_column($rows, 'valor_executado_total'));

        $viewData = [
            'title'          => 'Contratos e Atas Vigentes — Aditivos com Efeito Financeiro',
            'rows'           => $rows,
            'totalOriginal'  => $totalOriginal,
            'totalReajustes' => $totalReajustes,
            'totalCorrigido' => $totalCorrigido,
            'totalAditivos'  => $totalAditivos,
            'totalProrrog'   => $totalProrrog,
            'totalAtual'     => $totalAtual,
            'totalTotal'     => $totalTotal,
            'totalExec2026'  => $totalExec2026,
            'geradoEm'       => date('d/m/Y \à\s H:i'),
        ];

        if (($request->query['export'] ?? '') === 'docx') {
            $this->exportDocx($viewData);
        }

        $this->view('reports/aditivos_financeiros', $viewData, 'layouts/print');
    }

    private function exportDocx(array $data): never
    {
        $rows     = $data['rows'];
        $geradoEm = $data['geradoEm'];
        $brl = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');

        $grupos = [];
        foreach ($rows as $r) { $grupos[$r['setor_nome']][] = $r; }
        ksort($grupos);

        $headers = ['Contrato/Ata','Tipo','Fornecedor','Término','# Adt.','Valor Original','Reajustes','Aditivos Líq.','Prorrogação','Valor Atual','Valor Total','Executado'];
        $tableRows = [];

        foreach ($grupos as $setor => $contratos) {
            $nC = count(array_filter($contratos, fn($r) => ($r['tipo'] ?? '') === 'CONTRATO'));
            $nA = count($contratos) - $nC;
            $partes = [];
            if ($nC) $partes[] = $nC . ' contrato' . ($nC > 1 ? 's' : '');
            if ($nA) $partes[] = $nA . ' ata' . ($nA > 1 ? 's' : '');
            $tableRows[] = ['__section' => mb_strtoupper($setor) . ' (' . implode(' · ', $partes) . ')'];

            $gOrig = $gReaj = $gAdit = $gProrr = $gAtual = $gTotal = $gExec = 0.0;
            foreach ($contratos as $r) {
                $tableRows[] = [
                    $r['chave'],
                    $r['tipo'],
                    mb_substr($r['fornecedor_nome'] ?? '', 0, 40),
                    $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '—',
                    (int)$r['quantidade_aditivos'],
                    $brl((float)$r['valor_global_inicial']),
                    (float)$r['valor_reajustes']    != 0 ? $brl((float)$r['valor_reajustes'])    : '—',
                    (float)$r['valor_aditivos']     != 0 ? $brl((float)$r['valor_aditivos'])     : '—',
                    (float)$r['valor_prorrogacao']  != 0 ? $brl((float)$r['valor_prorrogacao'])  : '—',
                    $brl((float)$r['valor_atual']),
                    $brl((float)$r['valor_total']),
                    (float)$r['valor_executado_total'] > 0 ? $brl((float)$r['valor_executado_total']) : '—',
                ];
                $gOrig  += (float)$r['valor_global_inicial'];
                $gReaj  += (float)$r['valor_reajustes'];
                $gAdit  += (float)$r['valor_aditivos'];
                $gProrr += (float)$r['valor_prorrogacao'];
                $gAtual += (float)$r['valor_atual'];
                $gTotal += (float)$r['valor_total'];
                $gExec  += (float)$r['valor_executado_total'];
            }
            $tableRows[] = ['__total' => ['Subtotal ' . $setor, '', '', '', count($contratos), $brl($gOrig), $brl($gReaj), $brl($gAdit), $brl($gProrr), $brl($gAtual), $brl($gTotal), $brl($gExec)]];
        }
        $tableRows[] = ['__total' => ['TOTAL GERAL', '', '', '', count($rows), $brl($data['totalOriginal']), $brl($data['totalReajustes']), $brl($data['totalAditivos']), $brl($data['totalProrrog']), $brl($data['totalAtual']), $brl($data['totalTotal']), $brl($data['totalExec2026'])]];

        (new DocxService())
            ->titulo('Contratos e Atas Vigentes — Aditivos com Efeito Financeiro')
            ->paragrafo('Gerado em ' . $geradoEm . ' · Coordenadoria de Convênios e Contratos / SEAD · GestContratos TJPA')
            ->espacamento()
            ->tabela($headers, $tableRows)
            ->download('aditivos-financeiros-' . date('Y-m-d'));
    }

    private function exportXlsxAditivos(array $data): never
    {
        $rows    = $data['rows'];
        $geradoEm = $data['geradoEm'];
        $brl = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');

        $grupos = [];
        foreach ($rows as $r) { $grupos[$r['setor_nome']][] = $r; }
        ksort($grupos);

        $sp   = new Spreadsheet();
        $ws   = $sp->getActiveSheet();
        $ws->setTitle('Aditivos Financeiros');

        // Cabeçalho
        $headers = ['Contrato/Ata','Tipo','Fornecedor','Secretaria','Término','# Adt.','Valor Original','Reajustes','Aditivos Líq.','Prorrogação','Valor Atual','Valor Total','Valor Executado'];
        $cols    = range('A', 'M');
        foreach ($headers as $i => $h) {
            $ws->setCellValue($cols[$i] . '1', $h);
        }
        $ws->getStyle('A1:M1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($grupos as $setor => $contratos) {
            // Linha de grupo
            $ws->setCellValue("A{$row}", mb_strtoupper($setor));
            $ws->mergeCells("A{$row}:M{$row}");
            $ws->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            ]);
            $row++;

            $gOrig = $gReaj = $gAdit = $gProrr = $gAtual = $gTotal = $gExec = 0.0;
            foreach ($contratos as $r) {
                $ws->setCellValue("A{$row}", $r['chave']);
                $ws->setCellValue("B{$row}", $r['tipo']);
                $ws->setCellValue("C{$row}", $r['fornecedor_nome'] ?? '');
                $ws->setCellValue("D{$row}", $r['setor_nome']);
                $ws->setCellValue("E{$row}", $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '');
                $ws->setCellValue("F{$row}", (int)$r['quantidade_aditivos']);
                $ws->setCellValue("G{$row}", (float)$r['valor_global_inicial']);
                $ws->setCellValue("H{$row}", (float)$r['valor_reajustes']);
                $ws->setCellValue("I{$row}", (float)$r['valor_aditivos']);
                $ws->setCellValue("J{$row}", (float)$r['valor_prorrogacao']);
                $ws->setCellValue("K{$row}", (float)$r['valor_atual']);
                $ws->setCellValue("L{$row}", (float)$r['valor_total']);
                $ws->setCellValue("M{$row}", (float)$r['valor_executado_total']);
                foreach (['G','H','I','J','K','L','M'] as $c) {
                    $ws->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode('"R$"#.##0,00');
                }
                $gOrig += (float)$r['valor_global_inicial']; $gReaj += (float)$r['valor_reajustes'];
                $gAdit += (float)$r['valor_aditivos'];       $gProrr += (float)$r['valor_prorrogacao'];
                $gAtual += (float)$r['valor_atual'];          $gTotal += (float)$r['valor_total'];
                $gExec  += (float)$r['valor_executado_total'];
                $row++;
            }
            // Subtotal do grupo
            $ws->setCellValue("A{$row}", 'Subtotal — ' . $setor);
            $ws->mergeCells("A{$row}:F{$row}");
            foreach (['G'=>$gOrig,'H'=>$gReaj,'I'=>$gAdit,'J'=>$gProrr,'K'=>$gAtual,'L'=>$gTotal,'M'=>$gExec] as $c => $v) {
                $ws->setCellValue("{$c}{$row}", $v);
                $ws->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode('"R$"#.##0,00');
            }
            $ws->getStyle("A{$row}:M{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F7']],
            ]);
            $row++;
        }

        // Total geral
        $ws->setCellValue("A{$row}", 'TOTAL GERAL');
        $ws->mergeCells("A{$row}:F{$row}");
        foreach ([
            'G' => $data['totalOriginal'],  'H' => $data['totalReajustes'],
            'I' => $data['totalAditivos'],   'J' => $data['totalProrrog'],
            'K' => $data['totalAtual'],      'L' => $data['totalTotal'],
            'M' => $data['totalExec2026'],
        ] as $c => $v) {
            $ws->setCellValue("{$c}{$row}", $v);
            $ws->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode('"R$"#.##0,00');
        }
        $ws->getStyle("A{$row}:M{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
        ]);

        // Larguras automáticas
        foreach (range('A','M') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'aditivos-financeiros-' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new XlsxWriter($sp))->save('php://output');
        exit;
    }

    private function exportDocxSecretariaArps(array $data): never
    {
        $brl = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
        $headers = ['Tipo','Contrato/Ata','Fornecedor','Biênio','Início','Término','Valor Original','Valor Atualizado','Executado','Gestor'];
        $tableRows = [];

        foreach ($data['secretarias'] as $setor => $sec) {
            $nC = $sec['qtd_contrato']; $nA = $sec['qtd_arp'];
            $partes = [];
            if ($nC) $partes[] = $nC . ' contrato' . ($nC > 1 ? 's' : '');
            if ($nA) $partes[] = $nA . ' ata' . ($nA > 1 ? 's' : '');
            $tableRows[] = ['__section' => mb_strtoupper($setor) . ' (' . implode(' · ', $partes) . ')'];
            $subOrig = $subAtual = $subExec = 0.0;
            foreach ($sec['itens'] as $r) {
                $tableRows[] = [
                    $r['tipo'],
                    $r['chave'],
                    mb_substr($r['fornecedor_nome'] ?? '', 0, 38),
                    $r['bienio'],
                    $r['data_inicio']  ? date('d/m/Y', strtotime($r['data_inicio']))  : '—',
                    $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '—',
                    $brl((float)$r['valor_global_inicial']),
                    $brl((float)$r['valor_global_atualizado']),
                    (float)$r['valor_executado'] > 0 ? $brl((float)$r['valor_executado']) : '—',
                    mb_substr($r['gestor'] ?? '—', 0, 30),
                ];
                $subOrig  += (float)$r['valor_global_inicial'];
                $subAtual += (float)$r['valor_global_atualizado'];
                $subExec  += (float)$r['valor_executado'];
            }
            $tableRows[] = ['__total' => ['Subtotal', $setor, '', '', '', '', $brl($subOrig), $brl($subAtual), $brl($subExec), '']];
        }

        (new DocxService())
            ->titulo('Relatório — Contratos e ARPs por Secretaria')
            ->paragrafo('Gerado em ' . $data['geradoEm'] . ' · Coordenadoria de Convênios e Contratos / SEAD · GestContratos TJPA')
            ->espacamento()
            ->tabela($headers, $tableRows)
            ->download('contratos-arps-secretaria-' . date('Y-m-d'));
    }

    private function exportXlsxSecretariaArps(array $data): never
    {
        $secretarias = $data['secretarias'];
        $geradoEm    = $data['geradoEm'];

        $sp = new Spreadsheet();
        $ws = $sp->getActiveSheet();
        $ws->setTitle('Contratos e ARPs por Secretaria');

        $headers = ['Tipo','Contrato/Ata','Fornecedor','Biênio','Situação','Início','Término','Valor Original','Valor Atualizado','Valor Executado','Gestor','Fiscal'];
        $cols = range('A','L');
        foreach ($headers as $i => $h) {
            $ws->setCellValue($cols[$i] . '1', $h);
        }
        $ws->getStyle('A1:L1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A3A5C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($secretarias as $setor => $sec) {
            $ws->setCellValue("A{$row}", mb_strtoupper($setor));
            $ws->mergeCells("A{$row}:L{$row}");
            $ws->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            ]);
            $row++;

            $subOrig = $subAtual = $subExec = 0.0;
            foreach ($sec['itens'] as $r) {
                $ws->setCellValue("A{$row}", $r['tipo']);
                $ws->setCellValue("B{$row}", $r['chave']);
                $ws->setCellValue("C{$row}", $r['fornecedor_nome'] ?? '');
                $ws->setCellValue("D{$row}", $r['bienio']);
                $ws->setCellValue("E{$row}", $r['situacao'] ?? '');
                $ws->setCellValue("F{$row}", $r['data_inicio']   ? date('d/m/Y', strtotime($r['data_inicio']))   : '');
                $ws->setCellValue("G{$row}", $r['data_termino']  ? date('d/m/Y', strtotime($r['data_termino']))  : '');
                $ws->setCellValue("H{$row}", (float)$r['valor_global_inicial']);
                $ws->setCellValue("I{$row}", (float)$r['valor_global_atualizado']);
                $ws->setCellValue("J{$row}", (float)$r['valor_executado']);
                $ws->setCellValue("K{$row}", $r['gestor'] ?? '');
                $ws->setCellValue("L{$row}", $r['fiscal_tecnico'] ?? '');
                foreach (['H','I','J'] as $c) {
                    $ws->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode('"R$"#.##0,00');
                }
                $subOrig  += (float)$r['valor_global_inicial'];
                $subAtual += (float)$r['valor_global_atualizado'];
                $subExec  += (float)$r['valor_executado'];
                $row++;
            }

            // Subtotal
            $ws->setCellValue("A{$row}", 'Subtotal — ' . $setor);
            $ws->mergeCells("A{$row}:G{$row}");
            $ws->setCellValue("H{$row}", $subOrig);
            $ws->setCellValue("I{$row}", $subAtual);
            $ws->setCellValue("J{$row}", $subExec);
            foreach (['H','I','J'] as $c) {
                $ws->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode('"R$"#.##0,00');
            }
            $ws->getStyle("A{$row}:L{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F7']],
            ]);
            $row++;
        }

        foreach (range('A','L') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'contratos-arps-secretaria-' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new XlsxWriter($sp))->save('php://output');
        exit;
    }

    private function exportXlsxSecretariaPdf(array $data): never
    {
        $secretarias = $data['secretarias'];

        $sp = new Spreadsheet();
        $ws = $sp->getActiveSheet();
        $ws->setTitle('Contratos por Secretaria');

        $headers = ['Contrato/Ata','Fornecedor','Situação','Início','Término','Valor Atualizado','Gestor','Fiscal'];
        $cols = range('A','H');
        foreach ($headers as $i => $h) {
            $ws->setCellValue($cols[$i] . '1', $h);
        }
        $ws->getStyle('A1:H1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A3A5C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($secretarias as $setor => $sec) {
            $ws->setCellValue("A{$row}", mb_strtoupper($setor));
            $ws->mergeCells("A{$row}:H{$row}");
            $ws->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']],
            ]);
            $row++;
            $subValor = 0.0;
            foreach ($sec['contratos'] as $r) {
                $ws->setCellValue("A{$row}", $r['chave']);
                $ws->setCellValue("B{$row}", $r['fornecedor_nome'] ?? '');
                $ws->setCellValue("C{$row}", $r['situacao'] ?? '');
                $ws->setCellValue("D{$row}", $r['data_inicio']  ? date('d/m/Y', strtotime($r['data_inicio']))  : '');
                $ws->setCellValue("E{$row}", $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '');
                $ws->setCellValue("F{$row}", (float)$r['valor_global_atualizado']);
                $ws->getStyle("F{$row}")->getNumberFormat()->setFormatCode('"R$"#.##0,00');
                $ws->setCellValue("G{$row}", $r['gestor'] ?? '');
                $ws->setCellValue("H{$row}", $r['fiscal_tecnico'] ?? '');
                $subValor += (float)$r['valor_global_atualizado'];
                $row++;
            }
            $ws->setCellValue("A{$row}", 'Subtotal');
            $ws->mergeCells("A{$row}:E{$row}");
            $ws->setCellValue("F{$row}", $subValor);
            $ws->getStyle("F{$row}")->getNumberFormat()->setFormatCode('"R$"#.##0,00');
            $ws->getStyle("A{$row}:H{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F7']],
            ]);
            $row++;
        }
        foreach (range('A','H') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'contratos-secretaria-' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new XlsxWriter($sp))->save('php://output');
        exit;
    }

    public function semGestorFiscal(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $pdo = Database::pdo();

        $vazio = "(IS NULL OR = '' OR = 'sem indicação')";
        $semG = "gestor IS NULL OR gestor = '' OR gestor = 'sem indicação'";
        $semF = "(fiscal_demandante IS NULL OR fiscal_demandante = '' OR fiscal_demandante = 'sem indicação')
              AND (fiscal_tecnico   IS NULL OR fiscal_tecnico   = '' OR fiscal_tecnico   = 'sem indicação')
              AND (fiscal_substituto IS NULL OR fiscal_substituto = '' OR fiscal_substituto = 'sem indicação')";

        $filtro = $request->query['filtro'] ?? 'ambos'; // ambos | sem_gestor | sem_fiscal

        $whereExtra = match($filtro) {
            'sem_gestor' => "AND ($semG)",
            'sem_fiscal' => "AND ($semF)",
            default      => "AND (($semG) OR ($semF))",
        };

        $rows = $pdo->query("
            SELECT
                id, tipo, chave, fornecedor_nome, setor_nome, objeto,
                data_inicio, data_termino, situacao,
                valor_global_atualizado,
                gestor, gestor_substituto,
                fiscal_demandante, fiscal_tecnico, fiscal_substituto,
                DATEDIFF(data_termino, CURDATE()) AS dias_restantes,
                CASE
                    WHEN ($semG) AND ($semF) THEN 'ambos'
                    WHEN ($semG)             THEN 'sem_gestor'
                    ELSE                          'sem_fiscal'
                END AS pendencia
            FROM contratos
            WHERE deleted_at IS NULL AND situacao = 'Vigente'
            $whereExtra
            ORDER BY setor_nome ASC, data_termino ASC
        ")->fetchAll();

        // Agrupa por secretaria
        $porSetor = [];
        $totais   = ['ambos' => 0, 'sem_gestor' => 0, 'sem_fiscal' => 0, 'total' => 0, 'valor' => 0.0];
        foreach ($rows as $r) {
            $porSetor[$r['setor_nome'] ?: 'Sem secretaria'][] = $r;
            $totais[$r['pendencia']]++;
            $totais['total']++;
            $totais['valor'] += (float) $r['valor_global_atualizado'];
        }
        ksort($porSetor);

        if (($request->query['export'] ?? '') === 'docx') {
            $this->exportDocxSemGestorFiscal($rows, $totais, date('d/m/Y \à\s H:i'));
        }

        $this->view('reports/sem_gestor_fiscal', [
            'title'    => 'Contratos/ARPs sem Gestor ou Fiscal',
            'rows'     => $rows,
            'porSetor' => $porSetor,
            'totais'   => $totais,
            'filtro'   => $filtro,
            'geradoEm' => date('d/m/Y \à\s H:i'),
        ], 'layouts/print');
    }

    private function exportDocxSemGestorFiscal(array $rows, array $totais, string $geradoEm): never
    {
        $brl = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
        $headers = ['Contrato/Ata','Tipo','Fornecedor','Secretaria','Término','Valor','Pendência','Gestor','Fiscal Demandante','Fiscal Técnico'];
        $tableRows = [];

        $grupos = [];
        foreach ($rows as $r) { $grupos[$r['setor_nome'] ?: 'Sem secretaria'][] = $r; }
        ksort($grupos);

        foreach ($grupos as $setor => $contratos) {
            $tableRows[] = ['__section' => mb_strtoupper($setor) . ' (' . count($contratos) . ' instrumento' . (count($contratos) > 1 ? 's' : '') . ')'];
            foreach ($contratos as $r) {
                $pend = match($r['pendencia']) {
                    'ambos'      => 'SEM GESTOR E FISCAL',
                    'sem_gestor' => 'Sem gestor',
                    default      => 'Sem fiscal',
                };
                $tableRows[] = [
                    $r['chave'],
                    $r['tipo'],
                    mb_substr($r['fornecedor_nome'] ?? '', 0, 38),
                    mb_substr($r['setor_nome'] ?? '', 0, 30),
                    $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '—',
                    $brl((float)$r['valor_global_atualizado']),
                    $pend,
                    $r['gestor'] ?: '—',
                    $r['fiscal_demandante'] ?: '—',
                    $r['fiscal_tecnico'] ?: '—',
                ];
            }
        }
        $tableRows[] = ['__total' => ['TOTAL', '', $totais['total'] . ' instrumento(s)', '', '', $brl($totais['valor']), 'Ambos: ' . $totais['ambos'], 'S/ Gestor: ' . $totais['sem_gestor'], 'S/ Fiscal: ' . $totais['sem_fiscal'], '']];

        (new DocxService())
            ->titulo('Contratos e ARPs Vigentes — Sem Gestor ou Fiscal')
            ->paragrafo('Gerado em ' . $geradoEm . ' · Coordenadoria de Convênios e Contratos / SEAD')
            ->espacamento()
            ->tabela($headers, $tableRows)
            ->download('sem-gestor-fiscal-' . date('Y-m-d'));
    }

    public function prazoVigencia(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewReports());
        $pdo  = Database::pdo();
        $tipo = $request->query['tipo'] ?? 'todos'; // todos | CONTRATO | ARP

        $whereT = $tipo !== 'todos' ? 'AND tipo = ' . $pdo->quote($tipo) : '';

        $rows = $pdo->query("
            SELECT
                id, tipo, chave, fornecedor_nome, setor_nome, objeto,
                data_inicio, data_termino, situacao,
                gestor, fiscal_tecnico, fiscal_demandante,
                valor_global_atualizado,
                DATEDIFF(data_termino, CURDATE()) AS dias_restantes
            FROM contratos
            WHERE deleted_at IS NULL
              AND situacao = 'Vigente'
              $whereT
            ORDER BY data_termino ASC, chave ASC
        ")->fetchAll();

        // Classifica cada contrato por faixa de urgência
        $faixas = [
            'vencido'   => ['label' => 'Vencidos',          'cor' => '#dc2626', 'bg' => '#fef2f2', 'items' => []],
            'critico'   => ['label' => 'Vencem em até 30 dias',  'cor' => '#ea580c', 'bg' => '#fff7ed', 'items' => []],
            'alerta'    => ['label' => 'Vencem em 31–90 dias',   'cor' => '#d97706', 'bg' => '#fffbeb', 'items' => []],
            'atencao'   => ['label' => 'Vencem em 91–180 dias',  'cor' => '#2563eb', 'bg' => '#eff6ff', 'items' => []],
            'ok'        => ['label' => 'Vencem em mais de 180 dias', 'cor' => '#16a34a', 'bg' => '#f0fdf4', 'items' => []],
            'indefinido'=> ['label' => 'Sem data de término',    'cor' => '#64748b', 'bg' => '#f8fafc', 'items' => []],
        ];

        $totais = ['valor' => 0.0, 'count' => 0];

        foreach ($rows as &$r) {
            $d = $r['dias_restantes'];
            if ($r['data_termino'] === null || $r['data_termino'] === '') {
                $faixa = 'indefinido';
            } elseif ($d < 0) {
                $faixa = 'vencido';
            } elseif ($d <= 30) {
                $faixa = 'critico';
            } elseif ($d <= 90) {
                $faixa = 'alerta';
            } elseif ($d <= 180) {
                $faixa = 'atencao';
            } else {
                $faixa = 'ok';
            }
            $r['faixa'] = $faixa;
            $faixas[$faixa]['items'][] = $r;
            $totais['valor'] += (float) $r['valor_global_atualizado'];
            $totais['count']++;
        }
        unset($r);

        $this->view('reports/prazo_vigencia', [
            'title'    => 'Relatório de Prazo e Vigência',
            'faixas'   => $faixas,
            'totais'   => $totais,
            'tipo'     => $tipo,
            'geradoEm' => date('d/m/Y \à\s H:i'),
        ], 'layouts/print');
    }

    private function build(string $type, array $filters): array
    {
        $contracts = new Contract();
        return match ($type) {
            'contratos_expirados' => ['Contratos expirados', $contracts->search(array_merge($filters, ['situacao' => 'Expirado']))],
            'contratos_estrategicos' => ['Contratos estrategicos', Database::pdo()->query('SELECT * FROM contratos WHERE deleted_at IS NULL AND contrato_estrategico = 1 ORDER BY data_termino ASC')->fetchAll()],
            'sem_fiscal' => ['Contratos sem fiscal', Database::pdo()->query("SELECT * FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND ((fiscal_demandante IS NULL OR fiscal_demandante = '' OR fiscal_demandante = 'sem indicação') AND (fiscal_tecnico IS NULL OR fiscal_tecnico = '' OR fiscal_tecnico = 'sem indicação'))")->fetchAll()],
            'sem_gestor' => ['Contratos sem gestor', Database::pdo()->query("SELECT * FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND (gestor IS NULL OR gestor = '' OR gestor = 'sem indicação')")->fetchAll()],
            'arps_vigentes' => ['ARPs vigentes', Database::pdo()->query("SELECT * FROM arps WHERE deleted_at IS NULL AND situacao = 'Vigente' ORDER BY vigencia_final ASC")->fetchAll()],
            'execucao_ano' => ['Execucao financeira por exercicio', Database::pdo()->query('SELECT exercicio, COUNT(*) registros, SUM(valor_atualizado) valor_atualizado, SUM(valor_executado_exercicio) valor_executado_exercicio, SUM(saldo) saldo FROM execucoes_financeiras WHERE deleted_at IS NULL GROUP BY exercicio ORDER BY exercicio')->fetchAll()],
            'fornecedores_valor' => ['Ranking de fornecedores por valor', Database::pdo()->query('SELECT fornecedor_nome, COUNT(*) contratos, SUM(valor_global_atualizado) valor_global_atualizado, SUM(valor_executado) valor_executado FROM contratos WHERE deleted_at IS NULL GROUP BY fornecedor_nome ORDER BY valor_global_atualizado DESC LIMIT 100')->fetchAll()],
            'setores_valor' => ['Ranking de setores por valor', Database::pdo()->query('SELECT setor_nome, COUNT(*) contratos, SUM(valor_global_atualizado) valor_global_atualizado, SUM(valor_executado) valor_executado FROM contratos WHERE deleted_at IS NULL GROUP BY setor_nome ORDER BY valor_global_atualizado DESC LIMIT 100')->fetchAll()],
            default => ['Contratos vigentes', $this->renameValorCols(
                $contracts->search(array_merge($filters, ['situacao' => 'Vigente']))
            )],
        };
    }

    /**
     * Renomeia colunas de valor e calcula Valor Atual a partir dos aditivos
     * (mesma fórmula do relatório Aditivos Financeiros), apenas para CONTRATOs.
     *
     * Valor Atual = valor_global_inicial + SUM(valor_acrescido − valor_suprimido)
     * Para contratos ainda não sincronizados com a API (sem valor_prorrogacao),
     * cai no fallback de valor_global_atualizado.
     */
    private function renameValorCols(array $rows): array
    {
        if (empty($rows)) return $rows;

        $contratoIds = array_column(
            array_filter($rows, fn($r) => ($r['tipo'] ?? '') === 'CONTRATO'),
            'id'
        );

        $aditivoTotais = [];
        if (!empty($contratoIds)) {
            $ph   = implode(',', array_fill(0, count($contratoIds), '?'));
            $stmt = Database::pdo()->prepare("
                SELECT contrato_id,
                       COALESCE(SUM(valor_prorrogacao), 0) AS total_prorrogacao,
                       MAX(valor_prorrogacao > 0)          AS sincronizado
                FROM aditivos
                WHERE contrato_id IN ($ph) AND deleted_at IS NULL
                GROUP BY contrato_id
            ");
            $stmt->execute($contratoIds);
            foreach ($stmt->fetchAll() as $r) {
                $aditivoTotais[(int) $r['contrato_id']] = $r;
            }
        }

        return array_map(function (array $row) use ($aditivoTotais): array {
            if (($row['tipo'] ?? '') !== 'CONTRATO') {
                return $row;
            }

            $id       = (int) $row['id'];
            $vAtualiz = (float) ($row['valor_global_atualizado'] ?? 0);
            $dados    = $aditivoTotais[$id] ?? null;

            // Após sincronização com a API:
            //   valor_global_atualizado = valorTotal (Valor Total do contrato)
            //   valor_prorrogacao nos aditivos = soma das prorrogações
            //   Valor Atual = Valor Total − Σ prorrogações
            // Contratos apenas importados por planilha (sem prorrogacao nos aditivos):
            //   valor_global_atualizado já é o Valor Atual → usa direto.
            $valorAtual = ($dados && $dados['sincronizado'])
                ? $vAtualiz - (float) $dados['total_prorrogacao']
                : $vAtualiz;

            $renamed = [];
            foreach ($row as $k => $v) {
                $renamed[match($k) {
                    'valor_global_inicial'    => 'Valor Original',
                    'valor_global_atualizado' => 'Valor Atual',
                    default => $k,
                }] = $v;
            }
            $renamed['Valor Atual'] = $valorAtual;

            return $renamed;
        }, $rows);
    }

    private function csv(string $title, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . str_slug($title) . '.csv"');
        $out = fopen('php://output', 'w');
        if ($rows) {
            fputcsv($out, array_keys($rows[0]), ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
        }
        fclose($out);
    }
}

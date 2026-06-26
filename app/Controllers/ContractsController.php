<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\Additive;
use GestContratos\Models\Contract;
use GestContratos\Models\ContractTracking;
use GestContratos\Models\Notification;
use GestContratos\Services\AuditService;
use GestContratos\Services\ContractRulesService;
use GestContratos\Services\TjpaApiService;

final class ContractsController extends Controller
{
    private Contract $contracts;
    private ContractRulesService $rules;
    private AuditService $audit;

    public function __construct()
    {
        $this->contracts = new Contract();
        $this->rules = new ContractRulesService();
        $this->audit = new AuditService();
    }

    public function index(Request $request): void
    {
        $this->requireAuth();
        $filters = $request->query;
        $this->view('contracts/index', [
            'title'   => 'Contratos Vigentes',
            'contracts' => $this->contracts->search(array_merge($filters, ['tipo' => 'CONTRATO'])),
            'filters' => $filters,
            'scripts' => '<script src="' . e(asset('js/tjpa-contratos.js')) . '"></script>',
        ]);
    }

    public function create(): void
    {
        $this->requireCan(Auth::canWrite());
        $this->view('contracts/form', [
            'title' => 'Novo contrato',
            'contract' => [],
            'action' => url('/contratos'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireCan(Auth::canWrite());
        $this->validateCsrf($request);
        $data = $this->rules->normalize($this->contractData($request));
        $data['created_by'] = Auth::id();
        $id = $this->contracts->create($data);
        $this->audit->log('criacao', 'contratos', $id, [], $data);
        flash('success', 'Contrato cadastrado com sucesso.');
        redirect('/contratos/' . $id);
    }

    public function show(Request $request, string $id): void
    {
        $this->requireAuth();
        $contract = $this->contracts->find((int) $id);
        if (! $contract) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Contrato nao encontrado']);
            return;
        }
        $pdo      = \GestContratos\Core\Database::pdo();
        $cid      = (int) $id;

        $aditivos = (new Additive())->forContract($cid);

        $q = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
            $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll();
        })();

        // Calcula as 7 métricas financeiras conforme TJPA
        $finStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE
                    WHEN LOWER(tipo_aditivo) LIKE '%reajust%'
                      OR LOWER(tipo_aditivo) LIKE '%reequil%'
                      OR LOWER(tipo_aditivo) LIKE '%apostil%'
                    THEN valor_acrescido - valor_suprimido ELSE 0 END), 0) AS valor_reajustes,
                COALESCE(SUM(CASE
                    WHEN LOWER(tipo_aditivo) NOT LIKE '%reajust%'
                     AND LOWER(tipo_aditivo) NOT LIKE '%reequil%'
                     AND LOWER(tipo_aditivo) NOT LIKE '%apostil%'
                     AND LOWER(tipo_aditivo) NOT LIKE '%prorrog%'
                    THEN valor_acrescido - valor_suprimido ELSE 0 END), 0) AS valor_aditivos_liq,
                COALESCE(SUM(valor_prorrogacao), 0) AS valor_prorrogacao_total,
                MAX(valor_prorrogacao > 0)           AS sincronizado
            FROM aditivos
            WHERE contrato_id = ? AND deleted_at IS NULL
        ");
        $finStmt->execute([$cid]);
        $fin = $finStmt->fetch() ?: [];

        $vOriginal      = (float) ($contract['valor_global_inicial']    ?? 0);
        $vApiTotal      = (float) ($contract['valor_global_atualizado'] ?? 0);
        $vProrrog       = (float) ($fin['valor_prorrogacao_total']      ?? 0);
        $vReajustes     = (float) ($fin['valor_reajustes']              ?? 0);
        $vAditivosLiq   = (float) ($fin['valor_aditivos_liq']           ?? 0);
        $sincronizado   = (bool)  ($fin['sincronizado']                 ?? false);

        // Valor Total = API valorTotal (valor_global_atualizado)
        // Valor Atual = Valor Total − Prorrogação (apenas para contratos sincronizados)
        $vTotal     = $vApiTotal;
        $vAtual     = $sincronizado ? ($vApiTotal - $vProrrog) : $vApiTotal;
        $vCorrigido = $vOriginal + $vReajustes;

        $documentos        = $q('SELECT * FROM contrato_documentos WHERE contrato_id=? ORDER BY data_documento DESC, numero_doc DESC', [$cid]);
        $itens             = $q('SELECT * FROM contrato_itens WHERE contrato_id=? ORDER BY item ASC', [$cid]);
        $empenhos          = $q('SELECT * FROM contrato_empenhos WHERE contrato_id=? ORDER BY data_empenho ASC, empenho ASC', [$cid]);
        $eventos           = $q('SELECT * FROM contrato_eventos WHERE contrato_id=? ORDER BY ordem ASC', [$cid]);
        $liquidacoes       = $q('SELECT data_liquidacao, SUM(valor_liquidacao) AS valor FROM contrato_liquidacoes WHERE contrato_id=? GROUP BY data_liquidacao ORDER BY data_liquidacao', [$cid]);
        // Liquidado por empenho — fonte confiável para cálculo por vigência
        $liqPorEmpenhoRows = $q('SELECT empenho, SUM(valor_liquidacao) AS total FROM contrato_liquidacoes WHERE contrato_id=? GROUP BY empenho', [$cid]);
        $liqPorEmpenho     = array_column($liqPorEmpenhoRows, 'total', 'empenho');
        $licitacaoContratos = [];
        if (!empty($contract['licitacao_numero'])) {
            $licitacaoContratos = $q(
                'SELECT id, chave, fornecedor_nome, situacao, valor_global_atualizado FROM contratos
                 WHERE licitacao_numero=? AND id!=? AND deleted_at IS NULL ORDER BY chave ASC',
                [$contract['licitacao_numero'], $cid]
            );
        }

        $acompanhamentos = (new ContractTracking())->forContract($cid);

        $this->view('contracts/show', [
            'title'              => $contract['chave'],
            'acompanhamentos'    => $acompanhamentos,
            'contract'           => $contract,
            'aditivos'           => $aditivos,
            'documentos'         => $documentos,
            'itens'              => $itens,
            'empenhos'           => $empenhos,
            'eventos'            => $eventos,
            'liquidacoes'        => $liquidacoes,
            'liqPorEmpenho'      => $liqPorEmpenho,
            'licitacaoContratos' => $licitacaoContratos,
            // Métricas financeiras derivadas
            'vOriginal'          => $vOriginal,
            'vReajustes'         => $vReajustes,
            'vCorrigido'         => $vCorrigido,
            'vAditivosLiq'       => $vAditivosLiq,
            'vProrrog'           => $vProrrog,
            'vAtual'             => $vAtual,
            'vTotal'             => $vTotal,
            'sincronizado'       => $sincronizado,
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->requireCan(Auth::canWrite());
        $contract = $this->contracts->find((int) $id);
        if (! $contract) {
            flash('danger', 'Contrato nao encontrado.');
            redirect('/contratos');
        }
        $this->view('contracts/form', [
            'title' => 'Editar contrato',
            'contract' => $contract,
            'action' => url('/contratos/' . $id),
            'method' => 'POST',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $this->requireCan(Auth::canWrite());
        $this->validateCsrf($request);
        $before = $this->contracts->find((int) $id);
        if (! $before) {
            flash('danger', 'Contrato nao encontrado.');
            redirect('/contratos');
        }
        $data = $this->rules->normalize($this->contractData($request));
        $data['updated_by'] = Auth::id();
        $this->contracts->update((int) $id, $data);
        $this->audit->log('edicao', 'contratos', $id, $before, $data);
        flash('success', 'Contrato atualizado.');
        redirect('/contratos/' . $id);
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireCan(Auth::canDelete());
        $this->validateCsrf($request);
        $before = $this->contracts->find((int) $id);
        $this->contracts->softDelete((int) $id);
        $this->audit->log('exclusao_logica', 'contratos', $id, $before ?? [], []);
        flash('success', 'Contrato excluido logicamente.');
        redirect('/contratos');
    }

    public function duplicate(Request $request, string $id): void
    {
        $this->requireCan(Auth::canWrite());
        $contract = $this->contracts->find((int) $id);
        if (! $contract) {
            redirect('/contratos');
        }
        unset($contract['id'], $contract['created_at'], $contract['updated_at']);
        $contract['numero'] = $contract['numero'] . '-COPIA';
        $contract['chave'] = $contract['chave'] . '-COPIA';
        $contract['created_by'] = Auth::id();
        $newId = $this->contracts->create($contract);
        $this->audit->log('duplicacao', 'contratos', $newId, [], $contract);
        flash('success', 'Contrato duplicado como base para novo cadastro.');
        redirect('/contratos/' . $newId . '/editar');
    }

    public function close(Request $request, string $id): void
    {
        $this->requireCan(Auth::canDelete());
        $this->validateCsrf($request);
        $this->contracts->update((int) $id, ['situacao' => 'Expirado', 'encerrado_em' => date('Y-m-d'), 'updated_by' => Auth::id()]);
        $this->audit->log('encerramento', 'contratos', $id, [], ['encerrado_em' => date('Y-m-d')]);
        flash('success', 'Contrato marcado como encerrado.');
        redirect('/contratos/' . $id);
    }

    public function syncLiquidacoes(Request $request, string $id): void
    {
        header('Content-Type: application/json');
        set_time_limit(120);

        if (!\GestContratos\Core\Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Sessão expirada, recarregue a página.']);
            return;
        }

        if (!\GestContratos\Core\Csrf::verify((string) ($request->input('_csrf', '')))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            return;
        }

        $pdo = \GestContratos\Core\Database::pdo();
        $contract = $this->contracts->find((int) $id);
        if (!$contract) { echo json_encode(['success' => false, 'error' => 'Contrato não encontrado']); return; }

        $stmt = $pdo->prepare('SELECT empenho FROM contrato_empenhos WHERE contrato_id = ?');
        $stmt->execute([(int) $id]);
        $empenhos = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!$empenhos) { echo json_encode(['success' => true, 'total_liquidado' => 0, 'total_pago' => 0, 'atualizados' => 0]); return; }

        $liquidacoes = (new TjpaApiService())->fetchLiquidacoes($empenhos);

        $upd = $pdo->prepare(
            'UPDATE contrato_empenhos SET valor_liquidado=?, valor_pago=?, liquidado_em=NOW() WHERE contrato_id=? AND empenho=?'
        );
        $atualizados = 0;
        foreach ($liquidacoes as $emp => $vals) {
            $upd->execute([$vals['valorLiquidacao'], $vals['valorPago'], (int) $id, $emp]);
            $atualizados++;
        }

        $totais = $pdo->prepare('SELECT COALESCE(SUM(valor_liquidado),0) AS liq, COALESCE(SUM(valor_pago),0) AS pago FROM contrato_empenhos WHERE contrato_id=?');
        $totais->execute([(int) $id]);
        $row = $totais->fetch();

        echo json_encode(['success' => true, 'total_liquidado' => (float)$row['liq'], 'total_pago' => (float)$row['pago'], 'atualizados' => $atualizados]);
    }

    public function toggleStrategic(Request $request, string $id): void
    {
        $this->requireCan(Auth::canMarkStrategic());
        $this->validateCsrf($request);
        $contract = $this->contracts->find((int) $id);
        if ($contract) {
            $value = empty($contract['contrato_estrategico']) ? 1 : 0;
            $this->contracts->update((int) $id, ['contrato_estrategico' => $value, 'updated_by' => Auth::id()]);
            $this->audit->log('marcacao_estrategico', 'contratos', $id, ['contrato_estrategico' => $contract['contrato_estrategico']], ['contrato_estrategico' => $value]);
        }
        redirect('/contratos/' . $id);
    }

    public function generateNotification(Request $request, string $id): void
    {
        $this->requireCan(Auth::canNotify());
        $this->validateCsrf($request);
        $contract = $this->contracts->find((int) $id);
        if (! $contract) {
            redirect('/contratos');
        }
        redirect('/notificacoes/redigir/' . $id);
        redirect('/notificacoes');
    }

    private function contractData(Request $request): array
    {
        return $request->only([
            'tipo', 'numero', 'ano', 'chave', 'fornecedor_nome', 'cnpj_cpf', 'objeto',
            'natureza_contratacao_nome', 'forma_contratacao_nome', 'tipo_contrato_nome',
            'licitacao_numero', 'processo', 'setor_nome', 'data_inicio', 'data_termino',
            'valor_global_inicial', 'valor_global_atualizado', 'valor_executado',
            'valor_acumulado_executado', 'quantidade_aditivos', 'base_legal_nome',
            'contrato_estrategico', 'gestor', 'gestor_substituto', 'fiscal_demandante',
            'fiscal_tecnico', 'fiscal_substituto', 'fiscal_administrativo',
            'emails_equipe', 'observacoes', 'data_recebimento_prorrogacao',
            'data_orcamento_estimado', 'texto_notificacao',
        ]);
    }
}

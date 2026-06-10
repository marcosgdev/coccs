<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;
use GestContratos\Models\Notification;
use GestContratos\Services\AuditService;
use GestContratos\Services\ContractRulesService;

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
            'title' => 'Contratos Vigentes',
            'contracts' => $this->contracts->search($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission(['gestor-contratos']);
        $this->view('contracts/form', [
            'title' => 'Novo contrato',
            'contract' => [],
            'action' => url('/contratos'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): void
    {
        $this->requirePermission(['gestor-contratos']);
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
        $this->view('contracts/show', ['title' => $contract['chave'], 'contract' => $contract]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->requirePermission(['gestor-contratos']);
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
        $this->requirePermission(['gestor-contratos']);
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
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        $before = $this->contracts->find((int) $id);
        $this->contracts->softDelete((int) $id);
        $this->audit->log('exclusao_logica', 'contratos', $id, $before ?? [], []);
        flash('success', 'Contrato excluido logicamente.');
        redirect('/contratos');
    }

    public function duplicate(Request $request, string $id): void
    {
        $this->requirePermission(['gestor-contratos']);
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
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        $this->contracts->update((int) $id, ['situacao' => 'Expirado', 'encerrado_em' => date('Y-m-d'), 'updated_by' => Auth::id()]);
        $this->audit->log('encerramento', 'contratos', $id, [], ['encerrado_em' => date('Y-m-d')]);
        flash('success', 'Contrato marcado como encerrado.');
        redirect('/contratos/' . $id);
    }

    public function toggleStrategic(Request $request, string $id): void
    {
        $this->requirePermission(['gestor-contratos']);
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
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        $contract = $this->contracts->find((int) $id);
        if (! $contract) {
            redirect('/contratos');
        }
        $text = $this->rules->notificationText($contract);
        $notificationId = (new Notification())->create([
            'contrato_id' => $id,
            'tipo' => 'Prorrogacao',
            'assunto' => 'Notificacao ' . $contract['chave'],
            'texto' => $text,
            'destinatarios' => $contract['emails_equipe'] ?? '',
            'status' => 'rascunho',
            'created_by' => Auth::id(),
        ]);
        $this->contracts->update((int) $id, ['texto_notificacao' => $text]);
        $this->audit->log('geracao_notificacao', 'notificacoes', $notificationId, [], ['contrato_id' => $id]);
        flash('success', 'Texto de notificacao gerado e salvo.');
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

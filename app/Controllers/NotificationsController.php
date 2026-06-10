<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;
use GestContratos\Models\Notification;
use GestContratos\Services\AuditService;
use GestContratos\Services\ContractRulesService;

final class NotificationsController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $notifications = (new Notification())->all('id DESC', [], 1000);
        $contracts = (new Contract())->search([], 1000);
        $this->view('notifications/index', [
            'title' => 'Notificacoes',
            'notifications' => $notifications,
            'contracts' => $contracts,
        ]);
    }

    public function store(Request $request): void
    {
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        $contract = (new Contract())->find((int) $request->input('contrato_id'));
        if (! $contract) {
            flash('danger', 'Selecione um contrato valido.');
            redirect('/notificacoes');
        }
        $text = (new ContractRulesService())->notificationText($contract);
        $id = (new Notification())->create([
            'contrato_id' => $contract['id'],
            'tipo' => $request->input('tipo', 'Prorrogacao'),
            'assunto' => $request->input('assunto', 'Notificacao ' . $contract['chave']),
            'texto' => $request->input('texto') ?: $text,
            'destinatarios' => $request->input('destinatarios') ?: ($contract['emails_equipe'] ?? ''),
            'status' => 'rascunho',
            'created_by' => Auth::id(),
        ]);
        (new AuditService())->log('geracao_notificacao', 'notificacoes', $id, [], ['contrato_id' => $contract['id']]);
        flash('success', 'Notificacao criada.');
        redirect('/notificacoes');
    }

    public function markSent(Request $request, string $id): void
    {
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        (new Notification())->update((int) $id, [
            'status' => 'enviada',
            'data_envio' => date('Y-m-d H:i:s'),
            'updated_by' => Auth::id(),
        ]);
        (new AuditService())->log('envio_notificacao', 'notificacoes', $id);
        flash('success', 'Notificacao marcada como enviada.');
        redirect('/notificacoes');
    }
}

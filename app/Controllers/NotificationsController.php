<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;
use GestContratos\Models\Notification;
use GestContratos\Services\AuditService;
use GestContratos\Services\ContractRulesService;
use GestContratos\Services\MailService;

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
        $this->requireCan(Auth::canNotify());
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

    public function compose(Request $request, string $id): void
    {
        $this->requireCan(Auth::canNotify());
        $contract = (new Contract())->find((int) $id);
        if (!$contract) { flash('danger', 'Contrato não encontrado.'); redirect('/contratos'); }

        $texto = (new ContractRulesService())->fiscalizacaoNotificationText($contract);

        // Monta lista de destinatários sugeridos da equipe
        $equipe = [];
        foreach (['gestor', 'gestor_substituto', 'fiscal_demandante', 'fiscal_tecnico', 'fiscal_substituto'] as $campo) {
            if (!empty($contract[$campo])) {
                $equipe[] = ['cargo' => ucfirst(str_replace('_', ' ', $campo)), 'nome' => $contract[$campo]];
            }
        }

        $this->view('notifications/compose', [
            'title'    => 'Redigir notificação — ' . $contract['chave'],
            'contract' => $contract,
            'texto'    => $texto,
            'equipe'   => $equipe,
        ]);
    }

    public function send(Request $request, string $id): void
    {
        $this->requireCan(Auth::canNotify());
        $this->validateCsrf($request);
        $contract = (new Contract())->find((int) $id);
        if (!$contract) { flash('danger', 'Contrato não encontrado.'); redirect('/contratos'); }

        $assunto      = trim($request->input('assunto', 'Notificação de fiscalização — ' . $contract['chave']));
        $texto        = trim($request->input('texto', ''));
        $destinatarios = trim($request->input('destinatarios', ''));
        $acao         = $request->input('acao', 'rascunho'); // 'rascunho' ou 'enviar'

        $notifId = (new Notification())->create([
            'contrato_id'  => $contract['id'],
            'tipo'         => 'Fiscalizacao',
            'assunto'      => $assunto,
            'texto'        => $texto,
            'destinatarios' => $destinatarios,
            'status'       => 'rascunho',
            'created_by'   => Auth::id(),
        ]);
        (new AuditService())->log('geracao_notificacao', 'notificacoes', $notifId, [], ['contrato_id' => $id, 'tipo' => 'Fiscalizacao']);

        if ($acao === 'enviar' && $destinatarios) {
            $emails  = array_filter(array_map('trim', explode(',', $destinatarios)));
            $mailer  = new MailService();
            $ok      = $mailer->send($emails, $assunto, $texto);
            (new Notification())->update($notifId, ['status' => $ok ? 'enviada' : 'rascunho', 'data_envio' => $ok ? date('Y-m-d H:i:s') : null]);
            flash($ok ? 'success' : 'warning', $ok
                ? 'Notificação enviada com sucesso para ' . count($emails) . ' destinatário(s).'
                : 'Falha no envio. Verifique as configurações SMTP no .env (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD). A notificação foi salva como rascunho.');
        } else {
            flash('success', 'Notificação salva como rascunho. Você pode copiá-la e enviar manualmente.');
        }

        redirect('/notificacoes/redigir/' . $id . '?notif=' . $notifId);
    }

    public function markSent(Request $request, string $id): void
    {
        $this->requireCan(Auth::canNotify());
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

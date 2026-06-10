<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Gerar notificacao</h2>
    <form method="post" action="<?= e(url('/notificacoes')) ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-12 col-lg-5">
            <label class="form-label" for="contrato_id">Contrato/ARP</label>
            <select class="form-select" id="contrato_id" name="contrato_id" required>
                <option value="">Selecione</option>
                <?php foreach ($contracts as $contract): ?>
                    <option value="<?= e($contract['id']) ?>"><?= e($contract['chave'] . ' - ' . $contract['fornecedor_nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-lg-3">
            <label class="form-label" for="tipo">Tipo</label>
            <input class="form-control" id="tipo" name="tipo" value="Prorrogacao">
        </div>
        <div class="col-12 col-lg-4">
            <label class="form-label" for="assunto">Assunto</label>
            <input class="form-control" id="assunto" name="assunto" placeholder="Assunto da notificacao">
        </div>
        <div class="col-12">
            <label class="form-label" for="destinatarios">Destinatarios</label>
            <input class="form-control" id="destinatarios" name="destinatarios" placeholder="Se vazio, usa os e-mails da equipe">
        </div>
        <div class="col-12">
            <label class="form-label" for="texto">Texto opcional</label>
            <textarea class="form-control" id="texto" name="texto" rows="4" placeholder="Se vazio, o sistema gera texto pelas regras parametrizadas"></textarea>
        </div>
        <div class="col-12 text-end">
            <button class="btn btn-primary" type="submit"><i class="bi bi-magic"></i> Gerar</button>
        </div>
    </form>
</section>

<section class="gc-card p-3">
    <div class="table-responsive">
        <table class="table table-hover datatable align-middle w-100">
            <thead>
            <tr><th>ID</th><th>Tipo</th><th>Assunto</th><th>Status</th><th>Destinatarios</th><th>Criada em</th><th>Acoes</th></tr>
            </thead>
            <tbody>
            <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?= e($notification['id']) ?></td>
                    <td><?= e($notification['tipo']) ?></td>
                    <td><?= e($notification['assunto']) ?></td>
                    <td><span class="badge <?= e(badge_class($notification['status'])) ?>"><?= e($notification['status']) ?></span></td>
                    <td><?= e($notification['destinatarios']) ?></td>
                    <td><?= e(date_br($notification['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#notif<?= e($notification['id']) ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php if (($notification['status'] ?? '') !== 'enviada'): ?>
                            <form method="post" action="<?= e(url('/notificacoes/' . $notification['id'] . '/enviar')) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-send"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="collapse" id="notif<?= e($notification['id']) ?>">
                    <td colspan="7"><div class="notification-text bg-light border rounded p-3"><?= e($notification['texto']) ?></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

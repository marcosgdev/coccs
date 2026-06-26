<?php
$dias = null;
if (!empty($contract['data_termino'])) {
    $dias = (int) round((strtotime($contract['data_termino']) - time()) / 86400);
}
[$bgAlerta, $icoAlerta] = match(true) {
    $dias === null     => ['secondary', 'bi-question-circle'],
    $dias < 0          => ['danger',    'bi-x-octagon-fill'],
    $dias <= 30        => ['danger',    'bi-exclamation-octagon-fill'],
    $dias <= 90        => ['warning',   'bi-exclamation-triangle-fill'],
    $dias <= 180       => ['info',      'bi-info-circle-fill'],
    default            => ['success',   'bi-check-circle-fill'],
};
$labelDias = $dias === null ? 'Prazo não informado'
    : ($dias < 0 ? 'Vencido há ' . abs($dias) . ' dias'
    : 'Vence em ' . $dias . ' dia' . ($dias !== 1 ? 's' : ''));
?>

<!-- Cabeçalho institucional -->
<div class="gc-card p-3 mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <img src="<?= e(asset('img/brasao-tjpa-azul.png')) ?>" alt="Tribunal de Justica do Estado do Para" style="height:56px;width:auto;">
        <div class="text-center flex-grow-1">
            <div class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.06em;">Poder Judiciário – Estado do Pará</div>
            <div class="fw-bold" style="color:var(--gc-blue);">Coordenadoria de Convênios e Contratos</div>
            <div class="small text-muted">Secretaria de Administração</div>
        </div>
        <img src="<?= e(asset('img/logo-coccs.png')) ?>" alt="COCCS" style="height:52px;width:auto;">
    </div>
</div>

<!-- Cabeçalho contextual do contrato -->
<div class="gc-card p-4 mb-3 d-flex flex-wrap gap-3 align-items-center justify-content-between">
    <div>
        <div class="text-muted small mb-1"><a href="<?= e(url('/contratos/' . $contract['id'])) ?>" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Voltar ao contrato</a></div>
        <h1 class="h4 fw-bold mb-0"><?= e($contract['chave']) ?></h1>
        <div class="text-muted small"><?= e($contract['fornecedor_nome']) ?> · <?= e($contract['setor_nome']) ?></div>
    </div>
    <span class="badge text-bg-<?= $bgAlerta ?> fs-6 px-3 py-2">
        <i class="bi <?= $icoAlerta ?> me-1"></i><?= $labelDias ?>
    </span>
</div>

<!-- Equipe de fiscalização -->
<?php if (!empty($equipe)): ?>
<div class="gc-card p-4 mb-3">
    <h2 class="h6 fw-bold mb-3"><i class="bi bi-people me-2"></i>Equipe de fiscalização do contrato</h2>
    <div class="row g-2">
        <?php foreach ($equipe as $m): ?>
        <div class="col-sm-6 col-lg-4">
            <div class="border rounded px-3 py-2 d-flex align-items-center gap-2">
                <i class="bi bi-person-badge text-primary"></i>
                <div>
                    <div class="small fw-semibold"><?= e($m['nome']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= e($m['cargo']) ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-3 text-muted small"><i class="bi bi-info-circle me-1"></i>Adicione os e-mails correspondentes no campo "Destinatários" abaixo. Separe por vírgula para múltiplos endereços.</div>
</div>
<?php endif; ?>

<!-- Formulário de composição -->
<div class="gc-card p-4 mb-3">
    <h2 class="h6 fw-bold mb-3"><i class="bi bi-envelope-fill me-2"></i>Redigir notificação</h2>

    <form method="post" action="<?= e(url('/notificacoes/redigir/' . $contract['id'])) ?>" id="form-notif">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label fw-semibold small">Assunto</label>
            <?php
            $assuntoPartes = ['Notificação de Fiscalização', $contract['chave'], $contract['fornecedor_nome'] ?? ''];
            if (!empty($contract['data_termino'])) {
                $assuntoPartes[] = 'Vencimento: ' . date_br($contract['data_termino']);
            }
            ?>
            <input type="text" name="assunto" class="form-control"
                value="<?= e(implode(' — ', array_filter($assuntoPartes))) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small">Destinatários <span class="text-muted fw-normal">(e-mails separados por vírgula)</span></label>
            <input type="text" name="destinatarios" class="form-control"
                placeholder="fiscal@tjpa.jus.br, gestor@tjpa.jus.br"
                value="<?= e($contract['emails_equipe'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-semibold small mb-0">Corpo da notificação</label>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-copiar">
                    <i class="bi bi-clipboard me-1"></i>Copiar texto
                </button>
            </div>
            <textarea name="texto" id="notif-texto" class="form-control font-monospace"
                rows="20" style="font-size:13px;"><?= e($texto) ?></textarea>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" name="acao" value="rascunho" class="btn btn-outline-primary">
                <i class="bi bi-floppy me-1"></i>Salvar rascunho
            </button>
            <a id="btn-abrir-email" href="#" class="btn btn-primary" target="_blank">
                <i class="bi bi-envelope-arrow-up me-1"></i>Abrir no Outlook
            </a>
            <a href="<?= e(url('/contratos/' . $contract['id'])) ?>" class="btn btn-outline-secondary ms-auto">
                <i class="bi bi-x me-1"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<!-- Preview formatado -->
<div class="gc-card p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-eye me-2"></i>Pré-visualização</h2>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </div>
    <div id="preview-notif" class="border rounded p-4 bg-white">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3 pb-3 border-bottom flex-wrap">
            <img src="<?= e(asset('img/brasao-tjpa-azul.png')) ?>" alt="TJPA" style="height:52px;width:auto;">
            <div class="text-center flex-grow-1">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#666;">Poder Judiciário – Estado do Pará</div>
                <div style="font-weight:700;color:#002952;">Coordenadoria de Convênios e Contratos</div>
                <div style="font-size:12px;color:#666;">Secretaria de Administração</div>
            </div>
            <img src="<?= e(asset('img/logo-coccs.png')) ?>" alt="COCCS" style="height:48px;width:auto;">
        </div>
        <div id="preview-texto" style="font-family:Georgia,serif;font-size:14px;line-height:1.7;white-space:pre-line;"><?= e($texto) ?></div>
    </div>
</div>

<?php if (!empty($_GET['notif'])): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    Notificação #<?= (int)$_GET['notif'] ?> salva com sucesso.
    <a href="<?= e(url('/notificacoes')) ?>" class="alert-link">Ver todas as notificações</a>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ta   = document.getElementById('notif-texto');
    var pv   = document.getElementById('preview-texto');
    var dest = document.querySelector('input[name="destinatarios"]');
    var subj = document.querySelector('input[name="assunto"]');
    var btn  = document.getElementById('btn-abrir-email');

    function buildMailto() {
        var to      = (dest ? dest.value : '').trim();
        var subject = (subj ? subj.value : '').trim();
        var body    = (ta ? ta.value : '').trim();
        var href    = 'mailto:' + encodeURIComponent(to)
                    + '?subject=' + encodeURIComponent(subject)
                    + '&body='    + encodeURIComponent(body);
        btn.href = href;
    }

    buildMailto();
    [ta, dest, subj].forEach(function (el) {
        if (el) el.addEventListener('input', buildMailto);
    });

    // Sincroniza preview em tempo real
    if (ta && pv) {
        ta.addEventListener('input', function () { pv.textContent = ta.value; });
    }

    // Copiar para clipboard
    document.getElementById('btn-copiar').addEventListener('click', function () {
        navigator.clipboard.writeText(ta.value).then(function () {
            var b = document.getElementById('btn-copiar');
            b.innerHTML = '<i class="bi bi-check me-1"></i>Copiado!';
            setTimeout(function () {
                b.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copiar texto';
            }, 2000);
        });
    });
});
</script>

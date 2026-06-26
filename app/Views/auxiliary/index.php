<?php
$links = [
    ['/fornecedores', 'Fornecedores', 'bi-building'],
    ['/setores', 'Setores', 'bi-diagram-3'],
    ['/servidores', 'Servidores', 'bi-people'],
    ['/naturezas-contratacao', 'Naturezas de contratacao', 'bi-tags'],
    ['/formas-contratacao', 'Formas de contratacao', 'bi-list-check'],
    ['/tipos-contrato', 'Tipos de contrato', 'bi-file-earmark-text'],
    ['/bases-legais', 'Bases legais', 'bi-bank'],
    ['/unidades', 'Unidades', 'bi-houses'],
    ['/modelos-notificacao', 'Modelos de notificacao', 'bi-chat-square-text'],
    ['/usuarios', 'Usuarios', 'bi-person-gear'],
    ['/perfis', 'Perfis de acesso', 'bi-shield-lock'],
    ['/configuracoes', 'Parametros do sistema', 'bi-sliders'],
    ['/mapeamento-setores', 'Mapeamento de unidades gestoras', 'bi-signpost-split'],
];
?>
<div class="row g-3">
    <?php foreach ($links as [$href, $label, $icon]): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <a class="gc-card p-4 d-flex align-items-center gap-3 text-decoration-none h-100" href="<?= e(url($href)) ?>">
                <span class="metric-icon"><i class="bi <?= e($icon) ?>"></i></span>
                <span class="fw-bold text-body"><?= e($label) ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

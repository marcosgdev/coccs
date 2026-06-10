<?php
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$menus = [
    ['/', 'bi-speedometer2', 'Dashboard'],
    ['/contratos', 'bi-journal-text', 'Contratos Vigentes'],
    ['/arps', 'bi-folder-check', 'ARPs / Atas'],
    ['/execucao-financeira', 'bi-cash-coin', 'Execucao Financeira'],
    ['/aditivos', 'bi-plus-square', 'Aditivos'],
    ['/prazos', 'bi-clock-history', 'Prazos'],
    ['/gestao-fiscalizacao', 'bi-people', 'Gestao e Fiscalizacao'],
    ['/notificacoes', 'bi-bell', 'Notificacoes'],
    ['/relatorios', 'bi-bar-chart', 'Relatorios'],
    ['/importacao', 'bi-upload', 'Importacao'],
    ['/logs-importacao', 'bi-list-check', 'Logs de Importacao'],
    ['/auditoria', 'bi-shield-check', 'Auditoria'],
    ['/cadastros-auxiliares', 'bi-grid', 'Cadastros Auxiliares'],
    ['/configuracoes', 'bi-gear', 'Configuracoes'],
];
?>
<nav class="nav flex-column px-2 py-2">
    <?php foreach ($menus as [$href, $icon, $label]): ?>
        <?php $active = $href === '/' ? $current === '/' : str_starts_with($current, $href); ?>
        <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= e(url($href)) ?>">
            <i class="bi <?= e($icon) ?>" aria-hidden="true"></i>
            <span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

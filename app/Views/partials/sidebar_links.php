<?php
use GestContratos\Core\Auth;

$current    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$canImport  = Auth::canImport();
$canAudit   = Auth::canViewAudit();
$canReports = Auth::canViewReports();
$isAdmin    = Auth::isAdmin();

$sections = [
    'Operacional' => array_filter([
        ['/', 'bi-speedometer2', 'Dashboard'],
        ['/contratos', 'bi-journal-text', 'Contratos Vigentes'],
        ['/arps', 'bi-folder-check', 'ARPs / Atas'],
        ['/execucao-financeira', 'bi-cash-coin', 'Execucao Financeira'],
        ['/aditivos', 'bi-plus-square', 'Aditivos'],
        ['/prazos', 'bi-clock-history', 'Prazos'],
        ['/gestao-fiscalizacao', 'bi-people', 'Gestao e Fiscalizacao'],
        ['/notificacoes', 'bi-bell', 'Notificacoes'],
        $canReports ? ['/relatorios', 'bi-bar-chart', 'Relatorios'] : null,
    ]),
    'Manuais' => array_filter([
        ['/manuais/uso', 'bi-book', 'Manual de Uso'],
        ['/manuais/manutencao', 'bi-tools', 'Manual de Manutencao'],
        $isAdmin ? ['/manuais/implantacao', 'bi-server', 'Manual de Implantacao'] : null,
    ]),
    'Administracao' => array_filter([
        $canImport  ? ['/importacao', 'bi-upload', 'Importacao'] : null,
        $canImport  ? ['/logs-importacao', 'bi-list-check', 'Logs de Importacao'] : null,
        $canAudit   ? ['/auditoria', 'bi-shield-check', 'Auditoria'] : null,
        ['/cadastros-auxiliares', 'bi-grid', 'Cadastros Auxiliares'],
        $isAdmin    ? ['/configuracoes', 'bi-gear', 'Configuracoes'] : null,
        Auth::canManageUsers() ? ['/usuarios', 'bi-people-fill', 'Usuarios'] : null,
    ]),
];
?>
<nav class="nav flex-column px-2 py-2">
    <?php foreach ($sections as $section => $menus): ?>
        <?php if (empty($menus)) {
            continue;
        } ?>
        <div class="nav-section"><?= e($section) ?></div>
        <?php foreach ($menus as [$href, $icon, $label]): ?>
            <?php $active = $href === '/' ? $current === '/' : str_starts_with($current, $href); ?>
            <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= e(url($href)) ?>">
                <i class="bi <?= e($icon) ?>" aria-hidden="true"></i>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
</nav>

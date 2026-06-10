<?php $user = GestContratos\Core\Auth::user(); ?>
<!doctype html>
<html lang="pt-BR" data-theme="default">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('app.name')) ?> - <?= e(config('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php require base_path('app/Views/partials/sidebar.php'); ?>
    <div class="main">
        <?php require base_path('app/Views/partials/topbar.php'); ?>
        <main class="page-wrap" id="main-content" tabindex="-1">
            <?php require base_path('app/Views/partials/flash.php'); ?>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($title ?? 'Pagina') ?></li>
                </ol>
            </nav>
            <div class="page-header">
                <div>
                    <h1 class="h3 fw-bold mb-1"><?= e($title ?? 'GestContratos') ?></h1>
                    <p class="text-secondary mb-0">Gestao de Contratos, ARPs e Fiscalizacao.</p>
                </div>
            </div>
            <?= $content ?>
        </main>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
        <h2 class="h5" id="mobileMenuLabel">Menu</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php require base_path('app/Views/partials/sidebar_links.php'); ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap5.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.bootstrap5.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.colVis.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?= $scripts ?? '' ?>
</body>
</html>

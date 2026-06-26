<aside class="sidebar d-none d-lg-flex flex-column" aria-label="Menu principal">
    <a href="<?= e(url('/')) ?>" class="brand text-decoration-none text-white">
        <img src="<?= e(asset('img/brasao-tjpa-azul.png')) ?>" alt="TJPA" style="height:42px;width:auto;filter:brightness(0) invert(1);">
        <span class="brand-wordmark">
            <span class="brand-gest">Gest</span>
            <span class="brand-contratos">Contratos</span>
            <small class="d-block">Contratos, ARPs e Fiscalizacao</small>
        </span>
    </a>
    <div class="flex-grow-1 overflow-y-auto">
        <?php require base_path('app/Views/partials/sidebar_links.php'); ?>
    </div>
    <div class="sidebar-footer px-3 py-3" style="border-top:1px solid rgba(255,255,255,.12);">
        <img src="<?= e(asset('img/logo-coccs.png')) ?>" alt="Coordenadoria de Convenios e Contratos" style="max-width:100%;height:auto;filter:brightness(0) invert(1);opacity:.8;">
    </div>
</aside>

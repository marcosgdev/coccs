<header class="topbar d-flex align-items-center px-3 px-lg-4 gap-2">
    <a class="btn btn-outline-secondary d-lg-none me-2" data-bs-toggle="offcanvas" href="#mobileMenu" role="button" aria-label="Abrir menu">
        <i class="bi bi-list"></i>
    </a>
    <a href="#main-content" class="visually-hidden-focusable btn btn-sm btn-outline-primary">Ir para conteudo</a>
    <div class="topbar-logos d-none d-lg-flex align-items-center gap-3 me-auto">
        <img src="<?= e(asset('img/brasao-tjpa-color.png')) ?>" alt="Tribunal de Justica do Estado do Para" style="height:48px;width:auto;">
        <div class="vr" style="height:36px;opacity:.25;"></div>
        <img src="<?= e(asset('img/logo-sead.png')) ?>" alt="SEAD – Secretaria de Administracao" style="height:34px;width:auto;">
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="btn btn-light border btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i> <?= e($user['name'] ?? 'Usuario') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-secondary"><?= e($user['role_name'] ?? '') ?></span></li>
                <li><a class="dropdown-item" href="<?= e(url('/perfil')) ?>">Meu perfil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="dropdown-item" type="submit">Sair</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

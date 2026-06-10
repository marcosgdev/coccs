<header class="topbar d-flex align-items-center px-3 px-lg-4">
    <a class="btn btn-outline-secondary d-lg-none me-2" data-bs-toggle="offcanvas" href="#mobileMenu" role="button" aria-label="Abrir menu">
        <i class="bi bi-list"></i>
    </a>
    <a href="#main-content" class="visually-hidden-focusable btn btn-sm btn-outline-primary me-auto">Ir para conteudo</a>
    <div class="ms-auto d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary btn-sm" type="button" data-theme-toggle aria-label="Alternar tema">
            <i class="bi bi-circle-half"></i> Tema
        </button>
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

<section class="auth-card">
    <div class="text-center mb-4">
        <img src="<?= e(asset('img/brasao-tjpa-color.png')) ?>" alt="Tribunal de Justica do Estado do Para" style="height:130px;width:auto;">
    </div>
    <div class="d-flex align-items-center justify-content-center gap-4 mb-4 pb-4" style="border-bottom:1px solid var(--gc-border);">
        <img src="<?= e(asset('img/logo-sead.png')) ?>" alt="SEAD" style="height:90px;width:auto;">
        <div class="vr" style="height:70px;opacity:.25;"></div>
        <img src="<?= e(asset('img/logo-coccs.png')) ?>" alt="COCCS" style="height:90px;width:auto;">
    </div>
    <div class="text-center mb-4">
        <p class="text-secondary mb-0">Ambiente institucional para contratos, ARPs e fiscalizacao.</p>
    </div>

    <?php require base_path('app/Views/partials/flash.php'); ?>

    <form method="post" action="<?= e(url('/login')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">Senha</label>
            <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary w-100" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
    </form>

    <div class="demo-access small text-secondary mt-4">
        <div class="fw-bold text-uppercase text-primary mb-2">Acessos de demonstracao</div>
        <div class="mb-2">
            <strong>Administrador</strong><br>
            E-mail: <strong>admin@gestcontratos.local</strong><br>
            Senha: <strong>Admin@123</strong>
        </div>
        <div>
            <strong>Usuario comum</strong><br>
            E-mail: <strong>usuario@gestcontratos.local</strong><br>
            Senha: <strong>Usuario@123</strong>
        </div>
    </div>
</section>

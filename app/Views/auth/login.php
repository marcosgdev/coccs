<section class="auth-card">
    <div class="text-center mb-4">
        <img src="<?= e(asset('img/logo.svg')) ?>" alt="GestContratos" class="img-fluid mb-3" style="max-width: 220px;">
        <p class="text-secondary mb-0">Gestao de Contratos, ARPs e Fiscalizacao</p>
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

    <div class="small text-secondary mt-4">
        Primeiro acesso padrao do seed: <strong>admin@gestcontratos.local</strong> / <strong>Admin@123</strong>.
    </div>
</section>

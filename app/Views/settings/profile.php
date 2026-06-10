<section class="gc-card p-4">
    <h2 class="h5 fw-bold">Dados do usuario</h2>
    <dl class="detail-list row">
        <div class="col-12 col-md-6"><dt>Nome</dt><dd><?= e($user['name'] ?? '-') ?></dd></div>
        <div class="col-12 col-md-6"><dt>E-mail</dt><dd><?= e($user['email'] ?? '-') ?></dd></div>
        <div class="col-12 col-md-6"><dt>Perfil</dt><dd><?= e($user['role_name'] ?? '-') ?></dd></div>
        <div class="col-12 col-md-6"><dt>Identificador</dt><dd><?= e($user['id'] ?? '-') ?></dd></div>
    </dl>
</section>

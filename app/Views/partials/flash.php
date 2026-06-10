<?php foreach (['success', 'danger', 'warning', 'info'] as $type): ?>
    <?php if ($message = flash($type)): ?>
        <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

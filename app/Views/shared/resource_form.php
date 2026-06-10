<form method="post" action="<?= e($action) ?>" class="gc-card p-4" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach ($fields as $field): ?>
            <?php
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $value = old($name, $item[$name] ?? '');
            $col = $type === 'textarea' ? 'col-12' : 'col-12 col-md-6 col-xl-4';
            ?>
            <div class="<?= e($col) ?>">
                <?php if ($type === 'checkbox'): ?>
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="<?= e($name) ?>" name="<?= e($name) ?>" value="1" <?= ! empty($value) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= e($name) ?>"><?= e($field['label']) ?></label>
                    </div>
                <?php elseif ($type === 'textarea'): ?>
                    <label class="form-label" for="<?= e($name) ?>"><?= e($field['label']) ?></label>
                    <textarea class="form-control" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="4" <?= ! empty($field['required']) ? 'required' : '' ?>><?= e($value) ?></textarea>
                <?php else: ?>
                    <label class="form-label" for="<?= e($name) ?>"><?= e($field['label']) ?></label>
                    <input class="form-control" type="<?= e($type) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" step="<?= e($field['step'] ?? '') ?>" <?= ! empty($field['required']) ? 'required' : '' ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a class="btn btn-outline-secondary" href="javascript:history.back()">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Salvar</button>
    </div>
</form>

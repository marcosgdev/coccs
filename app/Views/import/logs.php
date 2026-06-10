<form class="filters" method="get" action="<?= e(url('/logs-importacao')) ?>">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label" for="import_batch_id">Lote</label>
            <select class="form-select" id="import_batch_id" name="import_batch_id">
                <option value="">Todos</option>
                <?php foreach ($batches ?? [] as $batch): ?>
                    <option value="<?= e($batch['id']) ?>" <?= (string) ($filters['import_batch_id'] ?? '') === (string) $batch['id'] ? 'selected' : '' ?>>
                        #<?= e($batch['id']) ?> - <?= e($batch['modo']) ?> - <?= e($batch['status']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label" for="modo">Tipo</label>
            <select class="form-select" id="modo" name="modo">
                <option value="">Todos</option>
                <?php foreach (['simulacao' => 'Simulacao', 'importacao' => 'Importacao valendo'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['modo'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label" for="status">Status</label>
            <input class="form-control" id="status" name="status" value="<?= e($filters['status'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="aba">Aba</label>
            <input class="form-control" id="aba" name="aba" value="<?= e($filters['aba'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit">Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/logs-importacao')) ?>" aria-label="Limpar filtros"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>

<section class="gc-card p-3">
    <div class="table-responsive">
        <table class="table table-hover datatable align-middle w-100">
            <thead><tr><th>ID</th><th>Lote</th><th>Tipo</th><th>Arquivo</th><th>Aba</th><th>Linha</th><th>Status</th><th>Mensagem</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['id']) ?></td>
                    <td><?= e($log['import_batch_id'] ?? '-') ?></td>
                    <td><?= e($log['modo'] ?? '-') ?></td>
                    <td><?= e($log['arquivo']) ?></td>
                    <td><?= e($log['aba']) ?></td>
                    <td><?= e($log['linha']) ?></td>
                    <td><span class="badge <?= e(badge_class($log['status'])) ?>"><?= e($log['status']) ?></span></td>
                    <td><?= e($log['mensagem']) ?></td>
                    <td><?= e($log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

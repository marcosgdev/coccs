<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= e(url($route . '/novo')) ?>">
        <i class="bi bi-plus-lg"></i> Novo
    </a>
</div>

<section class="gc-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead>
            <tr>
                <?php foreach ($columns as $label): ?>
                    <th scope="col"><?= e($label) ?></th>
                <?php endforeach; ?>
                <th scope="col" class="text-end">Acoes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <?php foreach (array_keys($columns) as $column): ?>
                        <td>
                            <?php if (str_contains($column, 'valor') || $column === 'saldo'): ?>
                                <?= e(money_br($item[$column] ?? 0)) ?>
                            <?php elseif (str_contains($column, 'data') || str_contains($column, 'vigencia')): ?>
                                <?= e(date_br($item[$column] ?? null)) ?>
                            <?php elseif ($column === 'situacao' || $column === 'ativo'): ?>
                                <?php $value = $column === 'ativo' ? ((int) ($item[$column] ?? 0) === 1 ? 'Ativo' : 'Inativo') : ($item[$column] ?? '-'); ?>
                                <span class="badge <?= e(badge_class($value)) ?>"><?= e($value) ?></span>
                            <?php else: ?>
                                <?= e($item[$column] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url($route . '/' . $item['id'] . '/editar')) ?>" aria-label="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="post" action="<?= e(url($route . '/' . $item['id'] . '/excluir')) ?>" class="d-inline" onsubmit="return confirm('Excluir logicamente este registro?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Excluir">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

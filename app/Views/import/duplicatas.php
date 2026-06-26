<?php $temDuplicatas = count($grupos) > 0; ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= e(url('/importacao')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Voltar à Importação
    </a>
    <h1 class="h5 fw-bold mb-0">Duplicatas de Contratos/ARPs</h1>
</div>

<?php if (!$temDuplicatas): ?>
<section class="gc-card p-5 text-center">
    <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
    <h2 class="h5 fw-bold mt-3">Nenhuma duplicata encontrada</h2>
    <p class="text-muted">Todos os registros ativos possuem chave única.</p>
</section>

<?php else: ?>
<section class="gc-card p-4 mb-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <h2 class="h6 fw-bold mb-1">
                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                <?= count($grupos) ?> grupo<?= count($grupos) !== 1 ? 's' : '' ?> com duplicatas
                &mdash; <?= $total_duplicatas ?> registro<?= $total_duplicatas !== 1 ? 's' : '' ?> extra<?= $total_duplicatas !== 1 ? 's' : '' ?>
            </h2>
            <p class="text-muted small mb-0">
                Para cada grupo, o registro mais antigo (menor ID) será mantido e os demais serão removidos logicamente.
                A operação é reversível — os registros ficam com <code>deleted_at</code> preenchido, não são apagados do banco.
            </p>
        </div>
        <form method="post" action="<?= e(url('/importacao/duplicatas/limpar')) ?>"
              onsubmit="return confirm('Remover <?= $total_duplicatas ?> registro(s) duplicado(s)? A operação pode ser desfeita pelo banco de dados.')">
            <?= csrf_field() ?>
            <button class="btn btn-danger" type="submit">
                <i class="bi bi-trash3-fill me-1"></i>
                Remover <?= $total_duplicatas ?> duplicata<?= $total_duplicatas !== 1 ? 's' : '' ?>
            </button>
        </form>
    </div>
</section>

<section class="gc-card p-0 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Chave canônica</th>
                <th>Tipo</th>
                <th>Chaves encontradas</th>
                <th>Fornecedor</th>
                <th class="text-center">Total</th>
                <th>ID mantido</th>
                <th>IDs removidos</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($grupos as $g):
            $todosIds   = explode(',', $g['todos_ids']);
            $manterId   = (string) $g['manter_id'];
            $removerIds = array_filter($todosIds, fn($id) => $id !== $manterId);
            $chaves     = explode(' | ', $g['todas_chaves']);
        ?>
        <tr>
            <td class="ps-3 fw-semibold"><?= e($g['chave_canonica']) ?></td>
            <td>
                <?php if ($g['tipo'] === 'ARP'): ?>
                <span class="badge" style="background:#d1fae5;color:#065f46">ARP</span>
                <?php else: ?>
                <span class="badge" style="background:#dbeafe;color:#1e40af">CONTRATO</span>
                <?php endif; ?>
            </td>
            <td class="small">
                <?php foreach ($chaves as $ch): ?>
                <code class="me-1"><?= e($ch) ?></code>
                <?php endforeach; ?>
            </td>
            <td class="small"><?= e(mb_strimwidth((string)$g['fornecedor_nome'], 0, 40, '…')) ?></td>
            <td class="text-center">
                <span class="badge bg-danger"><?= (int)$g['total'] ?></span>
            </td>
            <td>
                <span class="badge bg-success">#<?= e($manterId) ?></span>
            </td>
            <td class="small">
                <?php foreach ($removerIds as $rid): ?>
                <span class="badge bg-danger bg-opacity-10 text-danger me-1">#<?= e($rid) ?></span>
                <?php endforeach; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>

<form class="filters" method="get" action="<?= e(url('/contratos')) ?>">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-3">
            <label class="form-label" for="q">Pesquisa</label>
            <input class="form-control" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Chave, fornecedor, objeto...">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="ano">Ano</label>
            <input class="form-control" id="ano" name="ano" value="<?= e($filters['ano'] ?? '') ?>">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="situacao">Situacao</label>
            <select class="form-select" id="situacao" name="situacao">
                <option value="">Todas</option>
                <?php foreach (['Vigente', 'Expirado', 'Indeterminado'] as $option): ?>
                    <option value="<?= e($option) ?>" <?= ($filters['situacao'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-lg-3">
            <label class="form-label" for="setor_nome">Setor</label>
            <input class="form-control" id="setor_nome" name="setor_nome" value="<?= e($filters['setor_nome'] ?? '') ?>">
        </div>
        <div class="col-12 col-lg-2 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/contratos')) ?>" aria-label="Limpar filtros"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>

<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= e(url('/contratos/novo')) ?>"><i class="bi bi-plus-lg"></i> Novo contrato</a>
</div>

<section class="gc-card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead>
            <tr>
                <th>Chave</th>
                <th>Tipo</th>
                <th>Fornecedor</th>
                <th>Setor</th>
                <th>Termino</th>
                <th>Prazo</th>
                <th>Situacao</th>
                <th>Valor atualizado</th>
                <th class="text-end">Acoes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($contracts as $contract): ?>
                <tr>
                    <td><a class="fw-semibold" href="<?= e(url('/contratos/' . $contract['id'])) ?>"><?= e($contract['chave']) ?></a></td>
                    <td><?= e($contract['tipo']) ?></td>
                    <td><?= e($contract['fornecedor_nome']) ?></td>
                    <td><?= e($contract['setor_nome']) ?></td>
                    <td><?= e(date_br($contract['data_termino'])) ?></td>
                    <td><span class="badge <?= e(badge_class($contract['prazo'])) ?>"><?= e($contract['prazo']) ?></span></td>
                    <td><span class="badge <?= e(badge_class($contract['situacao'])) ?>"><?= e($contract['situacao']) ?></span></td>
                    <td><?= e(money_br($contract['valor_global_atualizado'])) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/contratos/' . $contract['id'])) ?>" aria-label="Visualizar"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/contratos/' . $contract['id'] . '/editar')) ?>" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

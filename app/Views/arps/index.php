<?php $canWrite = GestContratos\Core\Auth::canWrite(); ?>

<form class="filters mb-3" method="get" action="<?= e(url('/arps')) ?>">
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
                <option value="" <?= ($filters['situacao'] ?? '') === '' ? 'selected' : '' ?>>Todas</option>
                <?php foreach (['Vigente', 'Expirado', 'Indeterminado'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= ($filters['situacao'] ?? 'Vigente') === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-lg-3">
            <label class="form-label" for="setor_nome">Setor</label>
            <input class="form-control" id="setor_nome" name="setor_nome" value="<?= e($filters['setor_nome'] ?? '') ?>">
        </div>
        <div class="col-12 col-lg-2 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/arps')) ?>" aria-label="Limpar filtros"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>

<?php if (empty($arps)): ?>
    <div class="gc-card p-4 text-center text-muted">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
        <p class="mb-1">Nenhuma ARP encontrada.</p>
        <p class="small">As ARPs sao importadas por planilha. Contratos devem ser atualizados pelo botao <strong>Sincronizar TJPA</strong> na tela de contratos.</p>
    </div>
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted small"><?= count($arps) ?> ata(s) encontrada(s)</span>
    </div>
    <section class="gc-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable w-100">
                <thead>
                <tr>
                    <th>Chave</th>
                    <th>Fornecedor</th>
                    <th>Setor</th>
                    <th>Termino</th>
                    <th>Prazo</th>
                    <th>Situacao</th>
                    <th>Valor atualizado</th>
                    <th>Executado</th>
                    <th>Gestor</th>
                    <th class="text-end">Acoes</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($arps as $arp): ?>
                    <tr>
                        <td><a class="fw-semibold" href="<?= e(url('/contratos/' . $arp['id'])) ?>"><?= e($arp['chave']) ?></a></td>
                        <td><?= e($arp['fornecedor_nome']) ?></td>
                        <td><?= e($arp['setor_nome']) ?></td>
                        <td><?= e(date_br($arp['data_termino'])) ?></td>
                        <td><span class="badge <?= e(badge_class($arp['prazo'])) ?>"><?= e($arp['prazo']) ?></span></td>
                        <td><span class="badge <?= e(badge_class($arp['situacao'])) ?>"><?= e($arp['situacao']) ?></span></td>
                        <td><?= e(money_br($arp['valor_global_atualizado'])) ?></td>
                        <td><?= e(money_br($arp['valor_acumulado_executado'] ?: $arp['valor_executado'])) ?></td>
                        <td><?= e($arp['gestor'] ?: '—') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/contratos/' . $arp['id'])) ?>" aria-label="Visualizar"><i class="bi bi-eye"></i></a>
                            <?php if ($canWrite): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/contratos/' . $arp['id'] . '/editar')) ?>" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<!-- Importação rápida de execução de ARPs -->
<section class="gc-card p-4 mb-4" style="border-left:4px solid #7c3aed">
    <h2 class="h5 fw-bold mb-1">
        <i class="bi bi-file-earmark-bar-graph me-2 text-purple" style="color:#7c3aed"></i>
        Importar Execução de ARPs
    </h2>
    <p class="text-muted small mb-3">
        Planilha com colunas: <strong>N° Ata · Ano da Ata · Valor Atualizado (após reajuste) · Valor Executado no Exercício · Valor Executado Acumulado · Quantidade de Aditivos</strong>.
        Atualiza os valores de execução nos registros de ARP existentes.
    </p>
    <form method="post" action="<?= e(url('/importacao/arp-execucao')) ?>"
          enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold">Planilha (.xlsx / .xlsm)</label>
            <input type="file" name="planilha" accept=".xlsx,.xlsm,.xls" required class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <div class="form-check form-check-inline mt-4">
                <input class="form-check-input" type="checkbox" name="mode" value="simulate" id="arp-sim" checked>
                <label class="form-check-label small" for="arp-sim">Simular (não salvar)</label>
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm" style="background:#7c3aed;color:#fff;font-weight:600">
                <i class="bi bi-upload me-1"></i> Importar ARPs
            </button>
        </div>
    </form>
</section>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= e(url('/importacao/duplicatas')) ?>" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-copy me-1"></i>Verificar e remover duplicatas
    </a>
</div>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Upload e pre-visualizacao</h2>
    <form method="post" action="<?= e(url('/importacao/preview')) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
        <?= csrf_field() ?>
        <div class="col-12 col-lg-8">
            <label class="form-label" for="planilha">Planilha .xlsm/.xlsx</label>
            <input class="form-control" type="file" id="planilha" name="planilha" accept=".xlsm,.xlsx" required>
        </div>
        <div class="col-12 col-lg-4">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-upload"></i> Pre-visualizar</button>
        </div>
    </form>
</section>

<?php if ($preview): ?>
    <section class="gc-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Abas identificadas</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Aba</th><th>Linhas</th><th>Colunas</th><th>Formulas</th><th>Cabecalhos normalizados</th></tr></thead>
                <tbody>
                <?php foreach ($preview as $sheet): ?>
                    <tr>
                        <td><?= e($sheet['name']) ?></td>
                        <td><?= e($sheet['rows']) ?></td>
                        <td><?= e($sheet['columns']) ?></td>
                        <td><?= e($sheet['formulas']) ?></td>
                        <td class="small"><?= e(implode(', ', array_filter($sheet['headers']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post" action="<?= e(url('/importacao/executar')) ?>" class="mt-3">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Abas a importar</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    $ops = [
                        'contracts' => 'ARPs / Atas (aba Contratos Vigentes)',
                        'arps'      => 'ATA empresa valores',
                        'financial' => 'Execucao financeira',
                        'servers'   => 'Gestao e Fiscalizacao',
                        'sectors'   => 'Setores (SETOREQ)',
                        'auxiliary' => 'Dados auxiliares',
                    ];
                    foreach ($ops as $key => $label):
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="operations[]" value="<?= e($key) ?>" id="op_<?= e($key) ?>" checked>
                        <label class="form-check-label" for="op_<?= e($key) ?>"><?= e($label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm w-auto" name="duplicate_mode" aria-label="Modo de duplicidade">
                    <option value="ignore">Ignorar duplicados</option>
                    <option value="overwrite">Sobrescrever duplicados existentes</option>
                </select>
                <button class="btn btn-outline-primary btn-sm" name="simulate" value="1" type="submit">Simular</button>
                <button class="btn btn-primary btn-sm" type="submit">Importar</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if ($result): ?>
    <section class="gc-card p-4">
        <h2 class="h5 fw-bold">Resultado <?= $result['simulate'] ? 'da simulacao' : 'da importacao' ?></h2>
        <dl class="row detail-list">
            <?php foreach (['contracts' => 'ARPs da carteira', 'arps' => 'ATA empresa valores', 'financial' => 'Execucoes', 'servers' => 'Servidores', 'sectors' => 'Setores', 'auxiliary' => 'Auxiliares'] as $key => $label): ?>
                <div class="col-6 col-md-4 col-xl-2"><dt><?= e($label) ?></dt><dd><?= e($result[$key] ?? 0) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <?php if (! empty($result['errors'])): ?>
            <div class="alert alert-warning"><?= e(implode(' | ', $result['errors'])) ?></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="gc-card p-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Lotes de importacao</h2>
            <p class="text-secondary mb-0">Use desfazer para desativar somente os registros criados por uma importacao especifica.</p>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/logs-importacao')) ?>">Ver logs</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle datatable w-100">
            <thead>
            <tr>
                <th>ID</th><th>Arquivo</th><th>Modo</th><th>Status</th><th>Inicio</th><th>Fim</th><th>Resultado</th><th class="text-end">Acoes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($batches ?? [] as $batch): ?>
                <?php $result = $batch['resultado'] ? json_decode($batch['resultado'], true) : []; ?>
                <tr>
                    <td><?= e($batch['id']) ?></td>
                    <td><?= e($batch['arquivo']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= e($batch['modo']) ?></span></td>
                    <td><span class="badge <?= e(badge_class($batch['status'])) ?>"><?= e($batch['status']) ?></span></td>
                    <td><?= e($batch['started_at']) ?></td>
                    <td><?= e($batch['finished_at'] ?? '-') ?></td>
                    <td class="small">
                        ARPs carteira: <?= e($result['contracts'] ?? 0) ?>;
                        ARPs: <?= e($result['arps'] ?? 0) ?>;
                        Execucoes: <?= e($result['financial'] ?? 0) ?>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/logs-importacao?import_batch_id=' . $batch['id'])) ?>">Logs</a>
                        <?php if (($batch['modo'] ?? '') === 'importacao' && ! in_array($batch['status'], ['desfeito', 'excluido'], true)): ?>
                            <form method="post" action="<?= e(url('/importacao/lotes/' . $batch['id'] . '/desfazer')) ?>" class="d-inline" onsubmit="return confirm('Desativar todos os dados criados por este lote?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Desfazer</button>
                            </form>
                        <?php endif; ?>
                        <?php if (($canHardDeleteBatches ?? false) && ($batch['modo'] ?? '') === 'importacao' && ($batch['status'] ?? '') !== 'excluido'): ?>
                            <form method="post" action="<?= e(url('/importacao/lotes/' . $batch['id'] . '/excluir')) ?>" class="d-inline" onsubmit="return confirm('Excluir fisicamente os dados criados por este lote? Esta acao preserva os logs, mas remove os registros importados.')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-dark" type="submit">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

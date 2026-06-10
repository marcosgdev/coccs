<?php
$types = [
    'contratos_vigentes' => 'Contratos vigentes',
    'contratos_expirados' => 'Contratos expirados',
    'contratos_estrategicos' => 'Contratos estrategicos',
    'sem_fiscal' => 'Contratos sem fiscal',
    'sem_gestor' => 'Contratos sem gestor',
    'arps_vigentes' => 'ARPs vigentes',
    'execucao_ano' => 'Execucao por exercicio',
    'fornecedores_valor' => 'Ranking fornecedores',
    'setores_valor' => 'Ranking setores',
];
?>
<form class="filters" method="get" action="<?= e(url('/relatorios')) ?>">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label" for="tipo">Relatorio</label>
            <select class="form-select" id="tipo" name="tipo">
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2"><label class="form-label" for="ano">Ano</label><input class="form-control" id="ano" name="ano" value="<?= e($filters['ano'] ?? '') ?>"></div>
        <div class="col-12 col-md-3"><label class="form-label" for="setor_nome">Setor</label><input class="form-control" id="setor_nome" name="setor_nome" value="<?= e($filters['setor_nome'] ?? '') ?>"></div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit">Gerar</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/relatorios?tipo=' . $type . '&export=csv')) ?>" aria-label="Exportar CSV"><i class="bi bi-filetype-csv"></i></a>
        </div>
    </div>
</form>

<section class="gc-card p-3">
    <h2 class="h5 fw-bold mb-3"><?= e($reportTitle) ?></h2>
    <p class="small text-secondary mb-3">
        Origem dos relatórios: dados importados da planilha Contratos 2024.xlsm. Contratos usam a aba Contratos Vigentes; ARPs usam ATA empresa valores; execução financeira usa M.11 Contratos execução e ARP execução; cargas usam a lógica de contagem da aba Gestão e fiscalização atual.
    </p>
    <div class="table-responsive">
        <table class="table table-hover datatable align-middle w-100">
            <thead>
            <tr>
                <?php foreach (array_keys($rows[0] ?? ['mensagem' => 'Sem registros']) as $header): ?>
                    <th><?= e($header) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $value): ?>
                        <td><?= e(is_numeric($value) && ! is_string($value) ? $value : (string) $value) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

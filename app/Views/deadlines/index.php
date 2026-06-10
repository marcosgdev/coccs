<div class="row g-3 mb-4">
    <?php foreach ($summary as $label => $value): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="metric-card" tabindex="0" data-bs-toggle="tooltip" data-bs-title="Aba Contratos Vigentes: usa TÉRMINO, RESTANTE e PRORROGAÇÃO APRESENTADA NO PRAZO. As faixas são recalculadas pelo sistema a partir da data atual.">
                <div class="metric-value"><?= e($value) ?></div>
                <div class="metric-label"><?= e($label) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form class="filters" method="get" action="<?= e(url('/prazos')) ?>">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label" for="setor_nome">Setor</label>
            <input class="form-control" id="setor_nome" name="setor_nome" value="<?= e($filters['setor_nome'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="base_legal_nome">Base legal</label>
            <input class="form-control" id="base_legal_nome" name="base_legal_nome" value="<?= e($filters['base_legal_nome'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="prazo">Prazo</label>
            <input class="form-control" id="prazo" name="prazo" value="<?= e($filters['prazo'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
    </div>
</form>

<section class="gc-card p-3">
    <p class="small text-secondary mb-3">
        Origem: aba Contratos Vigentes. Dias restantes = TÉRMINO - data atual; prazo de prorrogação = TÉRMINO menos o parâmetro de antecedência; status compara recebimento da prorrogação com esse limite.
    </p>
    <div class="table-responsive">
        <table class="table table-hover datatable align-middle w-100">
            <thead>
            <tr><th>Chave</th><th>Fornecedor</th><th>Setor</th><th>Termino</th><th>Dias</th><th>Prazo</th><th>Prorrogacao</th><th>Acoes</th></tr>
            </thead>
            <tbody>
            <?php foreach ($contracts as $contract): ?>
                <tr>
                    <td><a href="<?= e(url('/contratos/' . $contract['id'])) ?>"><?= e($contract['chave']) ?></a></td>
                    <td><?= e($contract['fornecedor_nome']) ?></td>
                    <td><?= e($contract['setor_nome']) ?></td>
                    <td><?= e(date_br($contract['data_termino'])) ?></td>
                    <td><?= e($contract['dias_restantes'] ?? '-') ?></td>
                    <td><span class="badge <?= e(badge_class($contract['prazo'])) ?>"><?= e($contract['prazo']) ?></span></td>
                    <td><span class="badge <?= e(badge_class($contract['prorrogacao_no_prazo'])) ?>"><?= e($contract['prorrogacao_no_prazo']) ?></span></td>
                    <td>
                        <form method="post" action="<?= e(url('/contratos/' . $contract['id'] . '/notificacao')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-bell"></i> Notificar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

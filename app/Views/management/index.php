<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <section class="gc-card p-3 h-100">
            <h2 class="h5 fw-bold mb-1">Carga atual por servidor</h2>
            <p class="small text-secondary mb-3">
                Origem: aba Contratos Vigentes, equivalente aos COUNTIFS da aba Gestão e fiscalização atual. Conta contratos vigentes por servidor nos papéis de gestor, fiscal demandante, fiscal técnico e substitutos.
            </p>
            <div class="table-responsive">
                <table class="table table-hover datatable align-middle w-100">
                    <thead>
                    <tr>
                        <th>Servidor</th><th>Unidade</th><th>Gestor</th><th>Fiscal demandante</th>
                        <th>Fiscal tecnico</th><th>Substituicoes</th><th>Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($loads as $load): ?>
                        <tr>
                            <td><?= e($load['servidor']) ?></td>
                            <td><?= e($load['unidade']) ?></td>
                            <td><?= e($load['gestor']) ?></td>
                            <td><?= e($load['fiscal_demandante']) ?></td>
                            <td><?= e($load['fiscal_tecnico']) ?></td>
                            <td><?= e(($load['gestor_substituto'] ?? 0) + ($load['fiscal_substituto'] ?? 0) + ($load['fiscal_administrativo'] ?? 0)) ?></td>
                            <td><span class="badge text-bg-primary"><?= e($load['total']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-4">
        <section class="gc-card p-3 mb-3">
            <h2 class="h6 fw-bold">Contratos sem gestor</h2>
            <p class="text-secondary"><?= count($withoutManager) ?> registros vigentes.</p>
            <p class="small text-secondary">Origem: Contratos Vigentes com SITUAÇÃO Vigente e Gestor vazio/sem indicação.</p>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/relatorios?tipo=sem_gestor')) ?>">Ver relatorio</a>
        </section>
        <section class="gc-card p-3">
            <h2 class="h6 fw-bold">Contratos sem fiscal</h2>
            <p class="text-secondary"><?= count($withoutFiscal) ?> registros vigentes.</p>
            <p class="small text-secondary">Origem: Contratos Vigentes com SITUAÇÃO Vigente sem Fiscal Demandante e sem Fiscal Técnico.</p>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/relatorios?tipo=sem_fiscal')) ?>">Ver relatorio</a>
        </section>
    </div>
</div>

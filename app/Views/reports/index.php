<?php
// ── Relatórios em destaque (PDF/especiais) ─────────────────────────────
?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="small fw-bold text-muted text-uppercase" style="letter-spacing:.07em;margin-bottom:10px">Relatórios especiais</div>
    </div>
    <div class="col-md-4">
        <a href="<?= e(url('/relatorios/bienios')) ?>" target="_blank" class="text-decoration-none">
            <div class="gc-card p-4 d-flex align-items-center gap-3 h-100" style="border:2px solid #e2e8f0;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#6366f1';this.style.boxShadow='0 4px 16px rgba(99,102,241,.15)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow=''">
                <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-graph-up-arrow" style="font-size:1.5rem;color:#fff"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:#1a3a5c">Análise Comparativa por Biênio</div>
                    <div class="small text-muted">KPIs de eficiência de gestão comparando os biênios 2021–2023, 2023–2025 e 2025–2027.</div>
                    <div class="mt-2"><span style="font-size:.7rem;background:#f5f3ff;color:#6d28d9;padding:2px 8px;border-radius:10px;font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir relatório</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(url('/relatorios/secretaria-contratos')) ?>" target="_blank" class="text-decoration-none">
            <div class="gc-card p-4 d-flex align-items-center gap-3 h-100" style="border:2px solid #e2e8f0;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#2563eb';this.style.boxShadow='0 4px 16px rgba(37,99,235,.12)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow=''">
                <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#0f766e,#0d9488);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-bar-chart-horizontal-fill" style="font-size:1.5rem;color:#fff"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:#1a3a5c">Visão Geral por Secretaria</div>
                    <div class="small text-muted">Ranking de secretarias com número de contratos e distribuição percentual.</div>
                    <div class="mt-2"><span style="font-size:.7rem;background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:10px;font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir relatório</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(url('/relatorios/secretaria-arps')) ?>" target="_blank" class="text-decoration-none">
            <div class="gc-card p-4 d-flex align-items-center gap-3 h-100" style="border:2px solid #e2e8f0;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#2563eb';this.style.boxShadow='0 4px 16px rgba(37,99,235,.12)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow=''">
                <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#1a3a5c,#2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-file-earmark-pdf-fill" style="font-size:1.5rem;color:#fff"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:#1a3a5c">Contratos e ARPs por Secretaria</div>
                    <div class="small text-muted">Relatório completo com contratos e atas vigentes agrupados por setor, com resumo por biênio. Exportável como PDF.</div>
                    <div class="mt-2"><span style="font-size:.7rem;background:#eff6ff;color:#2563eb;padding:2px 8px;border-radius:10px;font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir relatório</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(url('/relatorios/sem-gestor-fiscal')) ?>" target="_blank" class="text-decoration-none">
            <div class="gc-card p-4 d-flex align-items-center gap-3 h-100" style="border:2px solid #e2e8f0;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#ea580c';this.style.boxShadow='0 4px 16px rgba(234,88,12,.12)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow=''">
                <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#ea580c,#dc2626);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-person-x-fill" style="font-size:1.5rem;color:#fff"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:#1a3a5c">Sem Gestor ou Fiscal</div>
                    <div class="small text-muted">Contratos e ARPs vigentes sem gestor ou equipe de fiscalização indicada. Filtros por tipo de pendência. Exporta em .docx.</div>
                    <div class="mt-2"><span style="font-size:.7rem;background:#fff7ed;color:#ea580c;padding:2px 8px;border-radius:10px;font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir relatório</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(url('/relatorios/prazo-vigencia')) ?>" target="_blank" class="text-decoration-none">
            <div class="gc-card p-4 d-flex align-items-center gap-3 h-100" style="border:2px solid #e2e8f0;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#dc2626';this.style.boxShadow='0 4px 16px rgba(220,38,38,.12)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow=''">
                <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#dc2626,#ea580c);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-calendar-x-fill" style="font-size:1.5rem;color:#fff"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:#1a3a5c">Prazo e Vigência</div>
                    <div class="small text-muted">Contratos e ARPs agrupados por urgência de vencimento: vencidos, críticos (30d), alerta (90d), atenção (180d) e indeterminados.</div>
                    <div class="mt-2"><span style="font-size:.7rem;background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:10px;font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir relatório</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(url('/relatorios/aditivos-financeiros')) ?>" target="_blank" class="text-decoration-none">
            <div class="gc-card p-4 d-flex align-items-center gap-3 h-100" style="border:2px solid #e2e8f0;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#0284c7';this.style.boxShadow='0 4px 16px rgba(2,132,199,.12)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow=''">
                <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#0369a1,#0ea5e9);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-currency-dollar" style="font-size:1.5rem;color:#fff"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:#1a3a5c">Aditivos com Efeito Financeiro</div>
                    <div class="small text-muted">Contratos vigentes com reajuste, acréscimo ou prorrogação com impacto financeiro. Valor original vs. valor atual.</div>
                    <div class="mt-2"><span style="font-size:.7rem;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:10px;font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir relatório</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= e(url('/relatorios/secretaria-pdf')) ?>" target="_blank" class="text-decoration-none">
            <div class="gc-card p-4 d-flex align-items-center gap-3 h-100" style="border:2px solid #e2e8f0;transition:border-color .15s,box-shadow .15s" onmouseover="this.style.borderColor='#2563eb';this.style.boxShadow='0 4px 16px rgba(37,99,235,.12)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow=''">
                <div style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#475569,#64748b);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-file-earmark-text" style="font-size:1.5rem;color:#fff"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:#1a3a5c">Contratos por Secretaria <span style="font-size:.68rem;color:#94a3b8;font-weight:400">(legado)</span></div>
                    <div class="small text-muted">Versão anterior — somente contratos, sem ARPs.</div>
                    <div class="mt-2"><span style="font-size:.7rem;background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:10px;font-weight:700"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir relatório</span></div>
                </div>
            </div>
        </a>
    </div>
</div>
<hr class="mb-4">

<?php if (\GestContratos\Core\Auth::canImport()): ?>
<section class="gc-card p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-end">
        <div>
            <h2 class="h5 fw-bold mb-1">Atualizar valores das atas</h2>
            <p class="text-secondary mb-0 small">Use uma planilha com chave ou numero/ano da ata e as colunas de valor inicial e valor atualizado.</p>
        </div>
        <form method="post" action="<?= e(url('/relatorios/atas-valores')) ?>" enctype="multipart/form-data" class="d-flex flex-column flex-md-row gap-2 align-items-md-end">
            <?= csrf_field() ?>
            <div>
                <label class="form-label small" for="planilha_atas">Planilha</label>
                <input class="form-control form-control-sm" type="file" id="planilha_atas" name="planilha_atas" accept=".xlsx,.xlsm,.xls" required>
            </div>
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-upload me-1"></i>Atualizar</button>
        </form>
    </div>
    <?php if (! empty($arpValuesResult)): ?>
        <div class="alert alert-info mt-3 mb-0 small">
            Linhas lidas: <?= e($arpValuesResult['linhas_lidas'] ?? 0) ?>;
            atualizadas: <?= e($arpValuesResult['atualizadas'] ?? 0) ?>;
            sem correspondencia: <?= e($arpValuesResult['sem_correspondencia'] ?? 0) ?>;
            ignoradas: <?= e($arpValuesResult['ignoradas'] ?? 0) ?>.
            <?php if (! empty($arpValuesResult['erros'])): ?>
                Erros: <?= e(implode(' | ', array_slice($arpValuesResult['erros'], 0, 5))) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

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
        Origem dos relatórios: contratos sincronizados pela API TJPA; ARPs/atas importadas por planilha; execução financeira usa M.11 Contratos execução e ARP execução; cargas usam a lógica de contagem da aba Gestão e fiscalização atual.
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

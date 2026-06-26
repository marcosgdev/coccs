<?php
// ── helpers ──────────────────────────────────────────────────────────────────
$bkeys  = array_keys($bienios);
$blabels = ['2021-2023' => 'Biênio 2021–2023', '2023-2025' => 'Biênio 2023–2025', '2025-2027' => 'Biênio 2025–2027'];
$bcolors = ['2021-2023' => '#475569', '2023-2025' => '#2563eb', '2025-2027' => '#0f766e'];
$bglight = ['2021-2023' => '#f8fafc', '2023-2025' => '#eff6ff', '2025-2027' => '#f0fdf4'];
$bgmed   = ['2021-2023' => '#e2e8f0', '2023-2025' => '#dbeafe', '2025-2027' => '#d1fae5'];

function pct_bar(float $val, string $color = '#2563eb', float $max = 100): string {
    $w = $max > 0 ? min(100, ($val / $max) * 100) : 0;
    return '<div style="display:flex;align-items:center;gap:7px">
        <div style="flex:1;height:7px;background:#e2e8f0;border-radius:4px;overflow:hidden;min-width:60px">
            <div style="width:' . round($w) . '%;height:100%;background:' . $color . ';border-radius:4px"></div>
        </div>
        <span style="white-space:nowrap;font-weight:700;min-width:40px;text-align:right;font-size:.78rem">' . number_format($val, 1, ',', '.') . '%</span>
    </div>';
}

function kpi_color(float $val, float $warn = 50, float $ok = 75): string {
    if ($val >= $ok)   return '#16a34a';
    if ($val >= $warn) return '#d97706';
    return '#dc2626';
}

function trend_arrow(float $a, float $b): string {
    if ($b > $a + 0.5)  return '<span style="color:#16a34a;font-weight:700">▲</span>';
    if ($b < $a - 0.5)  return '<span style="color:#dc2626;font-weight:700">▼</span>';
    return '<span style="color:#94a3b8">→</span>';
}

// max values for relative bars across biênios
$maxValor  = max(array_map(fn($b) => (float)$b['valor_total'], $bienios) ?: [1]);
$maxTotal  = max(array_map(fn($b) => (int)$b['total'],         $bienios) ?: [1]);

$b1 = $bienios['2021-2023'];
$b2 = $bienios['2023-2025'];
$b3 = $bienios['2025-2027'];
?>
<style>
/* ── capa ──────────────────────────────────────────────────────────────── */
.bienio-cover-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:24px; }
.bc-card { background:rgba(255,255,255,.12);border-radius:14px;padding:18px 16px; }
.bc-badge { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.8;margin-bottom:6px; }
.bc-total { font-size:2.4rem;font-weight:800;line-height:1; }
.bc-sub { font-size:.72rem;opacity:.7;margin-top:4px; }
.bc-valor { font-size:1.1rem;font-weight:700;margin-top:10px;opacity:.9; }
.bc-ieg { display:inline-block;margin-top:10px;background:rgba(255,255,255,.2);border-radius:20px;padding:3px 12px;font-size:.75rem;font-weight:700; }

/* ── IEG gauge bar ─────────────────────────────────────────────────────── */
.ieg-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px; }
.ieg-card { background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:20px 20px 16px;text-align:center; }
.ieg-label { font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;font-weight:700;margin-bottom:4px; }
.ieg-title { font-size:.9rem;font-weight:700;color:#1e293b;margin-bottom:14px; }
.ieg-score { font-size:2.6rem;font-weight:800;line-height:1;margin-bottom:6px; }
.ieg-bar-wrap { height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden;margin:10px 0 6px; }
.ieg-bar-fill { height:100%;border-radius:5px; }
.ieg-components { font-size:.65rem;color:#64748b;display:flex;flex-direction:column;gap:3px;margin-top:10px;text-align:left; }
.ieg-comp-row { display:flex;justify-content:space-between;align-items:center; }
.ieg-comp-bar { height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;flex:1;margin:0 6px; }
.ieg-comp-fill { height:100%;border-radius:2px; }

/* ── tabela comparativa ────────────────────────────────────────────────── */
.cmp-section-title { font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;padding:10px 16px 6px;border-top:2px solid #e2e8f0;margin-top:0; }
.cmp-table { width:100%;border-collapse:collapse;font-size:.8rem; }
.cmp-table th { padding:9px 14px;text-align:center;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#fff;border-right:1px solid rgba(255,255,255,.15); }
.cmp-table th:first-child { text-align:left;background:#1a3a5c;min-width:200px; }
.cmp-table td { padding:8px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.cmp-table td:first-child { font-weight:600;color:#374151;background:#f8fafc;border-right:2px solid #e2e8f0; }
.cmp-table td:not(:first-child) { text-align:center; }
.cmp-table td.td-trend { text-align:center;width:60px; }
.cmp-table tr.group-hdr td { background:#f1f5f9;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#475569;padding:5px 14px;border-top:1px solid #e2e8f0; }
.cmp-table tr:last-child td { border-bottom:none; }
.cmp-table tr:hover td { background:#f8fafc; }
.cmp-table tr:hover td:first-child { background:#f1f5f9; }

/* ── rankings ──────────────────────────────────────────────────────────── */
.rank-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px; }
.rank-col { background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden; }
.rank-col-hdr { padding:10px 14px;font-weight:700;font-size:.82rem;color:#fff; }
.rank-item { display:flex;align-items:center;gap:10px;padding:8px 14px;border-bottom:1px solid #f1f5f9; }
.rank-item:last-child { border-bottom:none; }
.rank-num { width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;flex-shrink:0; }
.rank-info { flex:1;min-width:0; }
.rank-name { font-size:.75rem;color:#1e293b;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.rank-bar-wrap { height:4px;background:#f1f5f9;border-radius:2px;overflow:hidden;margin-top:3px; }
.rank-bar-fill { height:100%;border-radius:2px; }
.rank-val { font-size:.7rem;font-weight:700;color:#374151;white-space:nowrap; }

/* ── distribuição por ano ──────────────────────────────────────────────── */
.dist-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px; }
.dist-card { background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden; }
.dist-hdr { padding:10px 14px;font-weight:700;font-size:.82rem;color:#fff; }
.dist-body { padding:12px 14px; }
.dist-year-row { margin-bottom:10px; }
.dist-year-lbl { display:flex;justify-content:space-between;font-size:.72rem;margin-bottom:3px; }
.dist-year-bars { display:flex;height:12px;border-radius:6px;overflow:hidden; }

/* ── nota de rodapé IEG ────────────────────────────────────────────────── */
.ieg-nota { background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:.72rem;color:#92400e;margin-bottom:24px; }

/* ── PRINT ─────────────────────────────────────────────────────────────── */
@media print {
    .bienio-cover-grid { gap:10px;margin-top:16px; }
    .bc-card { padding:12px 10px; }
    .bc-total { font-size:1.8rem; }
    .ieg-grid, .rank-grid, .dist-grid { gap:10px; }
    .ieg-card, .rank-col, .dist-card { box-shadow:none;border:1px solid #e2e8f0; }
    .cmp-table { font-size:.7rem; }
    .cmp-table th, .cmp-table td { padding:5px 8px; }
    .bc-card,.ieg-bar-fill,.rank-col-hdr,.dist-hdr,.rank-bar-fill,.ieg-comp-fill {
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
}
</style>

<!-- ── toolbar ── -->
<div class="print-toolbar">
    <a href="<?= e(url('/relatorios')) ?>" class="btn-back"><i class="bi bi-arrow-left"></i> Relatórios</a>
    <div class="pt-title">Análise Comparativa por Biênio</div>
    <span class="pt-badge"><i class="bi bi-calendar3 me-1"></i><?= e($geradoEm) ?></span>
    <button class="btn-print" id="btn-print"><i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF</button>
</div>
<div style="background:#fefce8;border-bottom:1px solid #fde68a;padding:7px 24px;font-size:.75rem;color:#92400e;display:flex;align-items:center;gap:8px" class="no-print">
    <i class="bi bi-info-circle-fill" style="color:#d97706"></i>
    <span>Para melhor PDF: desmarque <strong>"Cabeçalhos e rodapés"</strong> no diálogo de impressão e escolha <strong>orientação Paisagem</strong>.</span>
</div>

<div class="report-wrap" style="max-width:1100px">

    <!-- ══════════════ CAPA ══════════════ -->
    <div class="report-cover">
        <div class="cover-orgao">Tribunal de Justiça do Estado do Pará — TJPA</div>
        <div class="cover-titulo">Análise Comparativa por Biênio</div>
        <div class="cover-subtitulo">Indicadores de eficiência na gestão de contratos e ARPs — 2021 a 2026</div>

        <div class="bienio-cover-grid">
        <?php foreach ($bienios as $bk => $b): ?>
            <div class="bc-card">
                <div class="bc-badge"><?= $blabels[$bk] ?></div>
                <div class="bc-total"><?= (int)$b['total'] ?></div>
                <div class="bc-sub">
                    <?= (int)$b['total_contratos'] ?> contratos · <?= (int)$b['total_arps'] ?> ARPs ·
                    <?= (int)$b['num_setores'] ?> setores
                </div>
                <div class="bc-valor"><?= money_br((float)$b['valor_total']) ?></div>
                <div class="bc-ieg">IEG <?= number_format($b['ieg'], 1, ',', '.') ?>%</div>
            </div>
        <?php endforeach; ?>
        </div>

        <div class="cover-meta">Gerado em <?= e($geradoEm) ?></div>
    </div>

    <!-- ══════════════ ÍNDICE DE EFICIÊNCIA DE GESTÃO ══════════════ -->
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:12px">
        <i class="bi bi-speedometer2 me-2"></i>Índice de Eficiência de Gestão (IEG)
    </div>

    <div class="ieg-nota no-print">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Como interpretar o IEG:</strong> combina cobertura de gestão (40%), cobertura de fiscalização (40%) e taxa de instrumentos com aditivos/prorrogações (20%). Considera todos os instrumentos — vigentes e encerrados — celebrados no período do biênio.
    </div>

    <div class="ieg-grid">
    <?php foreach ($bienios as $bk => $b):
        $ieg = (float)$b['ieg'];
        $iegColor = kpi_color($ieg, 50, 70);
        $iegLabel = $ieg >= 70 ? 'Eficiência Boa' : ($ieg >= 50 ? 'Eficiência Média' : 'Atenção Necessária');
    ?>
        <div class="ieg-card">
            <div class="ieg-label"><?= $blabels[$bk] ?></div>
            <div class="ieg-title">Índice de Eficiência</div>
            <div class="ieg-score" style="color:<?= $iegColor ?>"><?= number_format($ieg, 1, ',', '.') ?>%</div>
            <div style="font-size:.7rem;color:<?= $iegColor ?>;font-weight:700;margin-bottom:6px"><?= $iegLabel ?></div>
            <div class="ieg-bar-wrap">
                <div class="ieg-bar-fill" style="width:<?= round($ieg) ?>%;background:<?= $iegColor ?>"></div>
            </div>
            <!-- componentes -->
            <div class="ieg-components">
                <div class="ieg-comp-row">
                    <span style="min-width:110px">Cobertura Gestão</span>
                    <div class="ieg-comp-bar"><div class="ieg-comp-fill" style="width:<?= round($b['taxa_gestor']) ?>%;background:<?= kpi_color($b['taxa_gestor'], 50, 80) ?>"></div></div>
                    <span style="min-width:38px;text-align:right;font-weight:700"><?= number_format($b['taxa_gestor'], 1, ',', '.') ?>%</span>
                </div>
                <div class="ieg-comp-row">
                    <span style="min-width:110px">Cobertura Fiscal.</span>
                    <div class="ieg-comp-bar"><div class="ieg-comp-fill" style="width:<?= round($b['taxa_fiscal']) ?>%;background:<?= kpi_color($b['taxa_fiscal'], 50, 80) ?>"></div></div>
                    <span style="min-width:38px;text-align:right;font-weight:700"><?= number_format($b['taxa_fiscal'], 1, ',', '.') ?>%</span>
                </div>
                <div class="ieg-comp-row">
                    <span style="min-width:110px">Taxa Aditivos</span>
                    <div class="ieg-comp-bar"><div class="ieg-comp-fill" style="width:<?= round($b['taxa_aditivos']) ?>%;background:#6366f1"></div></div>
                    <span style="min-width:38px;text-align:right;font-weight:700"><?= number_format($b['taxa_aditivos'], 1, ',', '.') ?>%</span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- ══════════════ TABELA COMPARATIVA DE INDICADORES ══════════════ -->
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:12px">
        <i class="bi bi-table me-2"></i>Comparativo de Indicadores
    </div>

    <div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;margin-bottom:28px">
    <table class="cmp-table">
        <thead>
            <tr>
                <th style="background:#1a3a5c">Indicador</th>
                <?php foreach ($bienios as $bk => $b): ?>
                <th style="background:<?= $bcolors[$bk] ?>"><?= $blabels[$bk] ?></th>
                <?php endforeach; ?>
                <th style="background:#374151;width:60px">Tend.<br><span style="font-weight:400;opacity:.7;font-size:.6rem">B2→B3</span></th>
            </tr>
        </thead>
        <tbody>

            <!-- VOLUME ────────────────────────── -->
            <tr class="group-hdr"><td colspan="5">Volume de instrumentos</td></tr>
            <tr>
                <td>Total de instrumentos</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>">
                    <?= pct_bar((float)$b['total'], $bcolors[$bk], $maxTotal) ?>
                    <div style="font-size:.82rem;font-weight:800;margin-top:3px;color:<?= $bcolors[$bk] ?>"><?= (int)$b['total'] ?></div>
                </td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['total'], (float)$b3['total']) ?></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;· Contratos</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.82rem"><strong><?= (int)$b['total_contratos'] ?></strong> <span style="color:#94a3b8;font-size:.7rem">(<?= number_format((float)$b['total_contratos'] / max($b['total'],1) * 100, 0) ?>%)</span></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['total_contratos'], (float)$b3['total_contratos']) ?></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;· ARPs</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.82rem"><strong><?= (int)$b['total_arps'] ?></strong> <span style="color:#94a3b8;font-size:.7rem">(<?= number_format((float)$b['taxa_arps'], 0) ?>%)</span></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['total_arps'], (float)$b3['total_arps']) ?></td>
            </tr>
            <tr>
                <td>Setores atendidos</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.88rem;font-weight:700"><?= (int)$b['num_setores'] ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['num_setores'], (float)$b3['num_setores']) ?></td>
            </tr>
            <tr>
                <td>Fornecedores distintos</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.88rem;font-weight:700"><?= (int)$b['num_fornecedores'] ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['num_fornecedores'], (float)$b3['num_fornecedores']) ?></td>
            </tr>

            <!-- FINANCEIRO ────────────────────── -->
            <tr class="group-hdr"><td colspan="5">Dimensão financeira</td></tr>
            <tr>
                <td>Valor total contratado</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>">
                    <?= pct_bar((float)$b['valor_total'], $bcolors[$bk], $maxValor) ?>
                    <div style="font-size:.76rem;font-weight:700;margin-top:2px"><?= money_br((float)$b['valor_total']) ?></div>
                </td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['valor_total'], (float)$b3['valor_total']) ?></td>
            </tr>
            <tr>
                <td>Valor médio por instrumento</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.82rem;font-weight:700"><?= money_br((float)$b['valor_medio']) ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['valor_medio'], (float)$b3['valor_medio']) ?></td>
            </tr>
            <tr>
                <td>Reajuste médio (Δ inicial→atual)</td>
                <?php foreach ($bienios as $bk => $b):
                    $r = (float)$b['reajuste_pct'];
                    $rc = $r > 0 ? '#d97706' : ($r < 0 ? '#16a34a' : '#64748b');
                ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.88rem;font-weight:700;color:<?= $rc ?>">
                    <?= ($r >= 0 ? '+' : '') . number_format($r, 1, ',', '.') ?>%
                </td>
                <?php endforeach; ?>
                <td class="td-trend">—</td>
            </tr>

            <!-- EFICIÊNCIA ────────────────────── -->
            <tr class="group-hdr"><td colspan="5">Indicadores de eficiência</td></tr>
            <tr>
                <td>Cobertura de gestão (gestor)</td>
                <?php foreach ($bienios as $bk => $b):
                    $c = kpi_color((float)$b['taxa_gestor'], 50, 80);
                ?>
                <td style="background:<?= $bglight[$bk] ?>"><?= pct_bar((float)$b['taxa_gestor'], $c) ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['taxa_gestor'], (float)$b3['taxa_gestor']) ?></td>
            </tr>
            <tr>
                <td>Cobertura de fiscalização</td>
                <?php foreach ($bienios as $bk => $b):
                    $c = kpi_color((float)$b['taxa_fiscal'], 50, 80);
                ?>
                <td style="background:<?= $bglight[$bk] ?>"><?= pct_bar((float)$b['taxa_fiscal'], $c) ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['taxa_fiscal'], (float)$b3['taxa_fiscal']) ?></td>
            </tr>
            <tr>
                <td>Instrumentos com aditivos</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>"><?= pct_bar((float)$b['taxa_aditivos'], '#6366f1') ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['taxa_aditivos'], (float)$b3['taxa_aditivos']) ?></td>
            </tr>

            <!-- VIGÊNCIA ──────────────────────── -->
            <tr class="group-hdr"><td colspan="5">Vigência dos instrumentos</td></tr>
            <tr>
                <td>Vigentes</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>"><?= pct_bar((float)$b['taxa_vigentes'], '#16a34a') ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['taxa_vigentes'], (float)$b3['taxa_vigentes']) ?></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;· Qtd. vigentes / expirados</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.82rem">
                    <span style="color:#16a34a;font-weight:700"><?= (int)$b['vigentes'] ?></span>
                    <span style="color:#94a3b8"> / </span>
                    <span style="color:#dc2626;font-weight:700"><?= (int)$b['expirados'] ?></span>
                </td>
                <?php endforeach; ?>
                <td class="td-trend">—</td>
            </tr>
            <tr>
                <td>Total de aditivos acumulados</td>
                <?php foreach ($bienios as $bk => $b): ?>
                <td style="background:<?= $bglight[$bk] ?>;font-size:.88rem;font-weight:700"><?= number_format((int)$b['total_aditivos'], 0, ',', '.') ?></td>
                <?php endforeach; ?>
                <td class="td-trend"><?= trend_arrow((float)$b2['total_aditivos'], (float)$b3['total_aditivos']) ?></td>
            </tr>

        </tbody>
    </table>
    </div>

    <!-- ══════════════ DISTRIBUIÇÃO POR ANO ══════════════ -->
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:12px">
        <i class="bi bi-bar-chart-fill me-2"></i>Distribuição por Ano dentro do Biênio
    </div>
    <div class="dist-grid" style="margin-bottom:28px">
    <?php foreach ($bienios as $bk => $b):
        $anoData = [];
        foreach ($b['por_ano'] as $row) {
            $anoData[$row['ano']][$row['tipo']] = ['qtd' => (int)$row['qtd'], 'valor' => (float)$row['valor']];
        }
        $maxAnoQtd = max(array_map(fn($a) => array_sum(array_column($a, 'qtd')), $anoData) ?: [1]);
    ?>
        <div class="dist-card">
            <div class="dist-hdr" style="background:<?= $bcolors[$bk] ?>"><?= $blabels[$bk] ?></div>
            <div class="dist-body">
            <?php if (empty($anoData)): ?>
                <div style="font-size:.78rem;color:#94a3b8;text-align:center;padding:12px">Sem dados para este biênio</div>
            <?php else: foreach ($anoData as $ano => $tipos):
                $qtdArp = $tipos['ARP']['qtd'] ?? 0;
                $qtdCtr = $tipos['CONTRATO']['qtd'] ?? 0;
                $qtdTot = $qtdArp + $qtdCtr;
                $valArp = $tipos['ARP']['valor'] ?? 0;
                $valCtr = $tipos['CONTRATO']['valor'] ?? 0;
                $w = $maxAnoQtd > 0 ? round($qtdTot / $maxAnoQtd * 100) : 0;
            ?>
                <div class="dist-year-row">
                    <div class="dist-year-lbl">
                        <span style="font-weight:700"><?= $ano ?></span>
                        <span style="color:#64748b"><?= $qtdTot ?> instr. · <?= money_br($valArp + $valCtr) ?></span>
                    </div>
                    <div style="display:flex;height:14px;border-radius:7px;overflow:hidden;background:#f1f5f9">
                        <?php if ($qtdArp > 0): ?>
                        <div style="width:<?= $qtdTot > 0 ? round($qtdArp/$qtdTot*$w) : 0 ?>%;background:#0f766e;transition:width .3s" title="ARPs: <?= $qtdArp ?>"></div>
                        <?php endif; ?>
                        <?php if ($qtdCtr > 0): ?>
                        <div style="width:<?= $qtdTot > 0 ? round($qtdCtr/$qtdTot*$w) : 0 ?>%;background:<?= $bcolors[$bk] ?>;opacity:.85;transition:width .3s" title="Contratos: <?= $qtdCtr ?>"></div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:10px;font-size:.65rem;color:#64748b;margin-top:3px">
                        <?php if ($qtdCtr > 0): ?><span>🔵 Contratos: <?= $qtdCtr ?></span><?php endif; ?>
                        <?php if ($qtdArp > 0): ?><span>🟢 ARPs: <?= $qtdArp ?></span><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- ══════════════ TOP SECRETARIAS ══════════════ -->
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:12px">
        <i class="bi bi-building me-2"></i>Top 5 Secretarias por Valor Contratado
    </div>
    <div class="rank-grid" style="margin-bottom:28px">
    <?php foreach ($bienios as $bk => $b):
        $maxSv = $b['top_setores'] ? max(array_column($b['top_setores'], 'valor')) : 1;
    ?>
        <div class="rank-col">
            <div class="rank-col-hdr" style="background:<?= $bcolors[$bk] ?>"><?= $blabels[$bk] ?></div>
            <?php if (empty($b['top_setores'])): ?>
                <div style="padding:16px;font-size:.78rem;color:#94a3b8;text-align:center">Sem dados</div>
            <?php else: foreach ($b['top_setores'] as $i => $row): ?>
            <div class="rank-item">
                <div class="rank-num" style="background:<?= $bgmed[$bk] ?>;color:<?= $bcolors[$bk] ?>"><?= $i+1 ?></div>
                <div class="rank-info">
                    <div class="rank-name" title="<?= e($row['setor_nome']) ?>"><?= e(mb_strimwidth($row['setor_nome'], 0, 36, '…')) ?></div>
                    <div class="rank-bar-wrap">
                        <div class="rank-bar-fill" style="width:<?= $maxSv > 0 ? round($row['valor']/$maxSv*100) : 0 ?>%;background:<?= $bcolors[$bk] ?>"></div>
                    </div>
                </div>
                <div class="rank-val" style="font-size:.68rem"><?= money_br((float)$row['valor']) ?><br><span style="color:#94a3b8;font-weight:400"><?= (int)$row['qtd'] ?> instr.</span></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- ══════════════ TOP FORNECEDORES ══════════════ -->
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:12px">
        <i class="bi bi-people-fill me-2"></i>Top 5 Fornecedores por Valor Contratado
    </div>
    <div class="rank-grid" style="margin-bottom:28px">
    <?php foreach ($bienios as $bk => $b):
        $maxFv = $b['top_fornecedores'] ? max(array_column($b['top_fornecedores'], 'valor')) : 1;
    ?>
        <div class="rank-col">
            <div class="rank-col-hdr" style="background:<?= $bcolors[$bk] ?>"><?= $blabels[$bk] ?></div>
            <?php if (empty($b['top_fornecedores'])): ?>
                <div style="padding:16px;font-size:.78rem;color:#94a3b8;text-align:center">Sem dados</div>
            <?php else: foreach ($b['top_fornecedores'] as $i => $row): ?>
            <div class="rank-item">
                <div class="rank-num" style="background:<?= $bgmed[$bk] ?>;color:<?= $bcolors[$bk] ?>"><?= $i+1 ?></div>
                <div class="rank-info">
                    <div class="rank-name" title="<?= e($row['fornecedor_nome']) ?>"><?= e(mb_strimwidth($row['fornecedor_nome'], 0, 36, '…')) ?></div>
                    <div class="rank-bar-wrap">
                        <div class="rank-bar-fill" style="width:<?= $maxFv > 0 ? round($row['valor']/$maxFv*100) : 0 ?>%;background:<?= $bcolors[$bk] ?>"></div>
                    </div>
                </div>
                <div class="rank-val" style="font-size:.68rem"><?= money_br((float)$row['valor']) ?><br><span style="color:#94a3b8;font-weight:400"><?= (int)$row['qtd'] ?> instr.</span></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <!-- ══════════════ METODOLOGIA ══════════════ -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 18px;font-size:.72rem;color:#64748b;margin-bottom:20px">
        <div style="font-weight:700;color:#374151;margin-bottom:6px">Metodologia — Índice de Eficiência de Gestão (IEG)</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
            <div><strong>Cobertura de Gestão (40%)</strong> = Instrumentos com Gestor Designado ÷ Total × 100</div>
            <div><strong>Cobertura de Fiscalização (40%)</strong> = Instrumentos com Fiscal Designado ÷ Total × 100</div>
            <div><strong>Taxa de Aditivos (20%)</strong> = Instrumentos com ≥1 Aditivo ÷ Total × 100</div>
        </div>
        <div style="margin-top:8px;color:#94a3b8">Biênio identificado pelo campo <em>ano</em> do instrumento: 2021-2023 = anos 2021-2022 · 2023-2025 = anos 2023-2024 · 2025-2027 = anos 2025-2026.</div>
    </div>

    <div class="report-footer">
        Tribunal de Justiça do Estado do Pará — Sistema de Gestão de Contratos · Gerado em <?= e($geradoEm) ?>
    </div>

</div>

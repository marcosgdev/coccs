<?php
// ── Pre-calculos ──────────────────────────────────────────────────────────
$k = $kpis;
$crit    = array_filter($alertasPrazos, fn($a) => $a['dias'] <= 30);
$atencao = array_filter($alertasPrazos, fn($a) => $a['dias'] > 30 && $a['dias'] <= 60);
$monitor = array_filter($alertasPrazos, fn($a) => $a['dias'] > 60);

$totalCrit = count($crit) ?: 0;
$maxSetor  = $cargaSetor ? max(array_column($cargaSetor, 'total')) : 1;
$maxFiscal = $cargaFiscal ? max(array_column($cargaFiscal, 'total')) : 1;

function prazoZone(array $items, int $zona, string $cor, string $label, string $icon, string $corBg): string {
    $n = count($items);
    $html  = '<div class="db-zone db-zone--' . $zona . '">';
    $html .= '<div class="db-zone-hdr" style="background:' . $cor . '">';
    $html .= '<i class="bi ' . $icon . ' me-2"></i>' . $label;
    $html .= ' <span class="db-zone-count">' . $n . '</span></div>';
    $html .= '<div class="db-zone-body">';
    if (!$items) {
        $html .= '<div class="db-zone-empty"><i class="bi bi-check-circle text-success me-1"></i>Nenhum instrumento nesta faixa</div>';
    }
    foreach ($items as $a) {
        $dias = (int)$a['dias'];
        $tipoClass = $a['tipo'] === 'ARP' ? 'tipo-arp' : 'tipo-ctr';
        $html .= '<div class="db-zone-item">';
        $html .= '<div class="db-zone-item-top">';
        $html .= '<span class="db-tipo-badge ' . $tipoClass . '">' . e($a['tipo']) . '</span> ';
        $html .= '<strong class="db-chave">' . e($a['chave']) . '</strong>';
        $html .= '<span class="db-dias" style="background:' . $cor . '">' . $dias . 'd</span>';
        $html .= '</div>';
        $html .= '<div class="db-zone-item-sub">' . e(mb_strimwidth($a['fornecedor_nome'], 0, 40, '…')) . '</div>';
        $html .= '<div class="db-zone-item-sub" style="color:#64748b">' . e($a['setor_nome']) . '</div>';
        $html .= '</div>';
    }
    $html .= '</div></div>';
    return $html;
}
?>
<style>
/* ─── KPI Groups ─────────────────────────────────────────────────────── */
.db-kpi-group {
    border-radius: 14px; padding: 20px 22px; color: #fff; height: 100%;
    position: relative; overflow: hidden;
}
.db-kpi-group::after {
    content:''; position:absolute; right:-30px; top:-30px;
    width:140px; height:140px; border-radius:50%;
    background:rgba(255,255,255,.07);
}
.db-kpi-label { font-size:.72rem; font-weight:700; text-transform:uppercase;
                letter-spacing:.09em; opacity:.8; margin-bottom:6px; }
.db-kpi-big   { font-size:3.2rem; font-weight:800; line-height:1; margin-bottom:4px; }
.db-kpi-sub   { font-size:.78rem; opacity:.75; margin-bottom:14px; }
.db-prazo-row { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
.db-prazo-pip {
    flex:1; min-width:60px; background:rgba(255,255,255,.15);
    border-radius:10px; padding:8px 10px; text-align:center;
}
.db-prazo-pip-num  { font-size:1.4rem; font-weight:800; line-height:1; }
.db-prazo-pip-lbl  { font-size:.62rem; opacity:.75; text-transform:uppercase; margin-top:2px; }
.db-kpi-footer { font-size:.72rem; opacity:.65; border-top:1px solid rgba(255,255,255,.2); padding-top:10px; margin-top:4px; }

.db-kpi-group--blue   { background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); }
.db-kpi-group--teal   { background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); }
.db-kpi-group--gov    { background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%); }

/* governance items */
.db-gov-item { display:flex; justify-content:space-between; align-items:center;
               padding:9px 0; border-bottom:1px solid rgba(255,255,255,.15); }
.db-gov-item:last-child { border-bottom:none; }
.db-gov-item-lbl  { font-size:.8rem; opacity:.85; }
.db-gov-item-val  { font-size:1.6rem; font-weight:800; }
.db-gov-alert     { background:rgba(255,255,255,.2); border-radius:50%; width:32px; height:32px;
                    display:flex; align-items:center; justify-content:center; font-size:.8rem; }

/* ─── Zonas de prazo ─────────────────────────────────────────────────── */
.db-zone { border-radius:12px; overflow:hidden; height:100%; display:flex; flex-direction:column;
           box-shadow:0 1px 4px rgba(0,0,0,.08); }
.db-zone-hdr { color:#fff; padding:12px 14px; font-size:.82rem; font-weight:700;
               display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
.db-zone-count { background:rgba(255,255,255,.25); border-radius:20px; padding:1px 10px;
                 font-size:.75rem; }
.db-zone-body { background:#fff; flex:1; overflow-y:auto; max-height:300px; }
.db-zone-empty { padding:20px 14px; font-size:.78rem; color:#64748b; text-align:center; }
.db-zone-item { padding:10px 14px; border-bottom:1px solid #f1f5f9; transition:background .1s; }
.db-zone-item:last-child { border-bottom:none; }
.db-zone-item:hover { background:#f8fafc; }
.db-zone-item-top { display:flex; align-items:center; gap:6px; margin-bottom:3px; }
.db-chave { font-size:.8rem; font-weight:700; color:#1e293b; flex:1; }
.db-dias  { font-size:.7rem; font-weight:800; color:#fff; padding:1px 7px;
            border-radius:10px; white-space:nowrap; flex-shrink:0; }
.db-zone-item-sub { font-size:.7rem; color:#64748b; white-space:nowrap; overflow:hidden;
                    text-overflow:ellipsis; }

/* ─── Tipo badges ────────────────────────────────────────────────────── */
.db-tipo-badge { font-size:.62rem; font-weight:700; padding:1px 7px; border-radius:10px;
                 white-space:nowrap; flex-shrink:0; }
.tipo-arp { background:#d1fae5; color:#065f46; }
.tipo-ctr { background:#dbeafe; color:#1e40af; }

/* ─── Tendências Biênio ──────────────────────────────────────────────── */
.db-bienio-card { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.08);
                  overflow:hidden; height:100%; }
.db-bienio-hdr { padding:12px 16px; color:#fff; font-weight:700; font-size:.85rem;
                 display:flex; justify-content:space-between; align-items:center; }
.db-bienio-body { padding:14px 16px; }
.db-bienio-row { display:flex; gap:12px; margin-bottom:10px; align-items:flex-start; }
.db-bienio-big { text-align:center; min-width:70px; }
.db-bienio-big-num { font-size:2.2rem; font-weight:800; color:#1e293b; line-height:1; }
.db-bienio-big-lbl { font-size:.62rem; color:#64748b; text-transform:uppercase; margin-top:2px; }
.db-bienio-split { flex:1; display:flex; flex-direction:column; gap:4px; }
.db-split-bar { height:24px; border-radius:6px; overflow:hidden; display:flex; }
.db-split-ctr { background:#2563eb; display:flex; align-items:center; justify-content:center;
                font-size:.65rem; color:#fff; font-weight:700; min-width:20px; }
.db-split-arp { background:#0f766e; display:flex; align-items:center; justify-content:center;
                font-size:.65rem; color:#fff; font-weight:700; min-width:20px; }
.db-bienio-kpi-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px; }
.db-bienio-kpi { background:#f8fafc; border-radius:8px; padding:8px 10px; }
.db-bienio-kpi-num { font-size:1.2rem; font-weight:800; color:#1e293b; }
.db-bienio-kpi-bar { height:4px; background:#e2e8f0; border-radius:2px; margin:4px 0; overflow:hidden; }
.db-bienio-kpi-fill { height:100%; border-radius:2px; }
.db-bienio-kpi-lbl { font-size:.6rem; color:#64748b; text-transform:uppercase; }

/* ─── Carga por Setor ────────────────────────────────────────────────── */
.db-setor-item { padding:8px 0; border-bottom:1px solid #f1f5f9; }
.db-setor-item:last-child { border-bottom:none; }
.db-setor-name { font-size:.78rem; font-weight:600; color:#1e293b; margin-bottom:5px;
                 white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
.db-setor-bar-wrap { display:flex; height:14px; border-radius:7px; overflow:hidden; background:#f1f5f9; }
.db-setor-bar-ctr { background:#2563eb; display:flex; align-items:center; justify-content:center;
                    font-size:.6rem; color:#fff; font-weight:700; min-width:0; }
.db-setor-bar-arp { background:#0f766e; display:flex; align-items:center; justify-content:center;
                    font-size:.6rem; color:#fff; font-weight:700; min-width:0; }
.db-setor-meta { display:flex; gap:10px; margin-top:4px; font-size:.65rem; }
.db-setor-meta-ctr { color:#2563eb; font-weight:600; }
.db-setor-meta-arp { color:#0f766e; font-weight:600; }
.db-setor-meta-warn { color:#dc2626; font-weight:600; }

/* ─── Carga de Fiscalização ──────────────────────────────────────────── */
.db-fiscal-item { padding:9px 0; border-bottom:1px solid #f1f5f9; }
.db-fiscal-item:last-child { border-bottom:none; }
.db-fiscal-name { font-size:.78rem; font-weight:600; color:#1e293b; margin-bottom:4px; }
.db-fiscal-bar-wrap { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin-bottom:4px; }
.db-fiscal-bar-g { background:#2563eb; height:100%; float:left; }
.db-fiscal-bar-f { background:#0d9488; height:100%; float:left; }
.db-fiscal-bar-s { background:#a78bfa; height:100%; float:left; }
.db-fiscal-pills { display:flex; gap:5px; flex-wrap:wrap; }
.db-pill { font-size:.62rem; padding:1px 7px; border-radius:10px; font-weight:600; }
.db-pill-g { background:#dbeafe; color:#1e40af; }
.db-pill-f { background:#d1fae5; color:#065f46; }
.db-pill-s { background:#ede9fe; color:#6d28d9; }
.db-pill-total { background:#f1f5f9; color:#475569; margin-left:auto; }

/* ─── Section header ─────────────────────────────────────────────────── */
.dash-section-header { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
.dash-section-title  { font-size:.8rem; font-weight:800; text-transform:uppercase;
                        letter-spacing:.07em; color:#64748b; }
.dash-section-header .badge { font-size:.65rem; }
</style>

<!-- ══════════════════════════ KPI STRIP ══════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- CONTRATOS -->
    <div class="col-12 col-lg-4">
        <div class="db-kpi-group db-kpi-group--blue h-100">
            <div class="db-kpi-label"><i class="bi bi-journal-check me-2"></i>Contratos</div>
            <div class="db-kpi-big"><?= (int)$k['c_vigentes'] ?></div>
            <div class="db-kpi-sub">vigentes · <?= (int)$k['num_setores'] ?> setores ativos</div>
            <div class="db-prazo-row">
                <div class="db-prazo-pip" style="background:rgba(220,38,38,.45)">
                    <div class="db-prazo-pip-num"><?= (int)$k['c_30d'] ?></div>
                    <div class="db-prazo-pip-lbl">≤ 30 dias</div>
                </div>
                <div class="db-prazo-pip" style="background:rgba(217,119,6,.45)">
                    <div class="db-prazo-pip-num"><?= (int)$k['c_60d'] ?></div>
                    <div class="db-prazo-pip-lbl">31–60 dias</div>
                </div>
                <div class="db-prazo-pip" style="background:rgba(202,138,4,.4)">
                    <div class="db-prazo-pip-num"><?= (int)$k['c_90d'] ?></div>
                    <div class="db-prazo-pip-lbl">61–90 dias</div>
                </div>
            </div>
            <div class="db-kpi-footer">
                <?= (int)$k['c_expirados'] ?> expirados no total
            </div>
        </div>
    </div>

    <!-- ARPs -->
    <div class="col-12 col-lg-4">
        <div class="db-kpi-group db-kpi-group--teal h-100">
            <div class="db-kpi-label"><i class="bi bi-folder-check me-2"></i>Atas de Registro de Preços</div>
            <div class="db-kpi-big"><?= (int)$k['a_vigentes'] ?></div>
            <div class="db-kpi-sub">vigentes</div>
            <div class="db-prazo-row">
                <div class="db-prazo-pip" style="background:rgba(220,38,38,.45)">
                    <div class="db-prazo-pip-num"><?= (int)$k['a_30d'] ?></div>
                    <div class="db-prazo-pip-lbl">≤ 30 dias</div>
                </div>
                <div class="db-prazo-pip" style="background:rgba(217,119,6,.45)">
                    <div class="db-prazo-pip-num"><?= (int)$k['a_60d'] ?></div>
                    <div class="db-prazo-pip-lbl">31–60 dias</div>
                </div>
                <div class="db-prazo-pip" style="background:rgba(202,138,4,.4)">
                    <div class="db-prazo-pip-num"><?= (int)$k['a_90d'] ?></div>
                    <div class="db-prazo-pip-lbl">61–90 dias</div>
                </div>
            </div>
            <div class="db-kpi-footer">
                <?= (int)$k['a_expiradas'] ?> expiradas no total
            </div>
        </div>
    </div>

    <!-- GOVERNANÇA -->
    <div class="col-12 col-lg-4">
        <div class="db-kpi-group db-kpi-group--gov h-100">
            <div class="db-kpi-label"><i class="bi bi-shield-check me-2"></i>Governança da Carteira</div>
            <div class="db-gov-item mt-2">
                <div>
                    <div class="db-gov-item-lbl">Sem gestor designado</div>
                    <div style="font-size:.68rem;opacity:.65">Instrumentos vigentes sem responsável</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="db-gov-item-val <?= (int)$k['sem_gestor'] > 0 ? '' : 'opacity-50' ?>">
                        <?= (int)$k['sem_gestor'] ?>
                    </div>
                    <?php if ((int)$k['sem_gestor'] > 0): ?>
                    <a href="<?= e(url('/contratos?situacao=Vigente&sem_gestor=1')) ?>"
                       class="db-gov-alert" title="Ver contratos sem gestor">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="db-gov-item">
                <div>
                    <div class="db-gov-item-lbl">Sem fiscal designado</div>
                    <div style="font-size:.68rem;opacity:.65">Vigentes sem fiscal técnico/demandante</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="db-gov-item-val <?= (int)$k['sem_fiscal'] > 0 ? '' : 'opacity-50' ?>">
                        <?= (int)$k['sem_fiscal'] ?>
                    </div>
                    <?php if ((int)$k['sem_fiscal'] > 0): ?>
                    <a href="<?= e(url('/relatorios?tipo=sem_fiscal')) ?>"
                       class="db-gov-alert" title="Ver relatório">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="db-gov-item">
                <div>
                    <div class="db-gov-item-lbl">Com aditivos/prorrogações</div>
                    <div style="font-size:.68rem;opacity:.65">Instrumentos vigentes com pelo menos 1 aditivo</div>
                </div>
                <div class="db-gov-item-val"><?= (int)$k['com_aditivos'] ?></div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════ ALERTA DE PRAZOS ═══════════════════════ -->
<div class="dash-section-header">
    <span class="dash-section-title"><i class="bi bi-alarm me-1"></i>Alerta de Prazos — próximos 90 dias</span>
    <?php if (count($crit) > 0): ?>
    <span class="badge bg-danger"><?= count($crit) ?> crítico<?= count($crit) !== 1 ? 's' : '' ?></span>
    <?php endif; ?>
    <?php if (count($alertasPrazos) === 0): ?>
    <span class="badge bg-success">Nenhum vencimento próximo</span>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <?= prazoZone($crit,    1, '#dc2626', 'Ação imediata (≤ 30d)', 'bi-exclamation-triangle-fill', '#fef2f2') ?>
    </div>
    <div class="col-12 col-lg-4">
        <?= prazoZone($atencao, 2, '#d97706', 'Atenção (31–60d)',       'bi-clock-fill',               '#fffbeb') ?>
    </div>
    <div class="col-12 col-lg-4">
        <?= prazoZone($monitor, 3, '#0284c7', 'Monitorar (61–90d)',     'bi-eye-fill',                 '#f0f9ff') ?>
    </div>
</div>

<!-- ══════════════════════════ TENDÊNCIAS POR BIÊNIO ═════════════════ -->
<div class="dash-section-header">
    <span class="dash-section-title"><i class="bi bi-graph-up me-1"></i>Evolução por Biênio — todos os instrumentos</span>
</div>

<?php
$bcolors = ['2021-2023' => '#475569', '2023-2025' => '#2563eb', '2025-2027' => '#0f766e'];
$blabels = ['2021-2023' => 'Biênio 2021–2023', '2023-2025' => 'Biênio 2023–2025', '2025-2027' => 'Biênio 2025–2027'];
$bsubs   = ['2021-2023' => 'anos 2021–2022', '2023-2025' => 'anos 2023–2024', '2025-2027' => 'anos 2025–2026'];
$bmax    = max(array_map(fn($b) => (int)($b['total'] ?? 0), $tendencias) ?: [1]);
?>
<div class="row g-3 mb-4">
<?php foreach ($tendencias as $bk => $b):
    $total = (int)($b['total'] ?? 0);
    $ctr   = (int)($b['contratos'] ?? 0);
    $arps  = (int)($b['arps'] ?? 0);
    $wCtr  = $total > 0 ? round($ctr / $total * 100) : 0;
    $wArp  = $total > 0 ? round($arps / $total * 100) : 0;
    $pctG  = (float)($b['pct_gestor'] ?? 0);
    $pctF  = (float)($b['pct_fiscal'] ?? 0);
    $prazo = (float)($b['prazo_medio_meses'] ?? 0);
    $ativ  = (int)($b['vigentes'] ?? 0);
?>
<div class="col-12 col-lg-4">
    <div class="db-bienio-card">
        <div class="db-bienio-hdr" style="background:<?= $bcolors[$bk] ?>">
            <span><?= $blabels[$bk] ?></span>
            <span style="font-size:.68rem;opacity:.7"><?= $bsubs[$bk] ?></span>
        </div>
        <div class="db-bienio-body">
            <div class="db-bienio-row">
                <div class="db-bienio-big">
                    <div class="db-bienio-big-num" style="color:<?= $bcolors[$bk] ?>"><?= $total ?></div>
                    <div class="db-bienio-big-lbl">Total</div>
                </div>
                <div class="db-bienio-split">
                    <div style="font-size:.65rem;color:#64748b;margin-bottom:3px">
                        <span style="color:#2563eb;font-weight:700"><?= $ctr ?> contratos</span>
                        &nbsp;+&nbsp;
                        <span style="color:#0f766e;font-weight:700"><?= $arps ?> ARPs</span>
                    </div>
                    <div class="db-split-bar" style="height:22px;border-radius:6px;overflow:hidden">
                        <?php if ($wCtr > 0): ?>
                        <div class="db-split-ctr" style="width:<?= $wCtr ?>%"><?= $ctr ?></div>
                        <?php endif; ?>
                        <?php if ($wArp > 0): ?>
                        <div class="db-split-arp" style="width:<?= $wArp ?>%"><?= $arps ?></div>
                        <?php endif; ?>
                        <?php if ($total === 0): ?>
                        <div style="width:100%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#94a3b8">sem dados</div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.65rem;color:#64748b;margin-top:4px">
                        <?= $ativ ?> vigentes · <?= $total - $ativ ?> encerrados
                    </div>
                </div>
            </div>
            <div class="db-bienio-kpi-grid">
                <div class="db-bienio-kpi">
                    <div class="db-bienio-kpi-num" style="color:<?= $pctG >= 80 ? '#16a34a' : ($pctG >= 50 ? '#d97706' : '#dc2626') ?>"><?= $pctG ?>%</div>
                    <div class="db-bienio-kpi-bar">
                        <div class="db-bienio-kpi-fill" style="width:<?= $pctG ?>%;background:<?= $pctG >= 80 ? '#16a34a' : ($pctG >= 50 ? '#d97706' : '#dc2626') ?>"></div>
                    </div>
                    <div class="db-bienio-kpi-lbl">Cobertura Gestor</div>
                </div>
                <div class="db-bienio-kpi">
                    <div class="db-bienio-kpi-num" style="color:<?= $pctF >= 80 ? '#16a34a' : ($pctF >= 50 ? '#d97706' : '#dc2626') ?>"><?= $pctF ?>%</div>
                    <div class="db-bienio-kpi-bar">
                        <div class="db-bienio-kpi-fill" style="width:<?= $pctF ?>%;background:<?= $pctF >= 80 ? '#16a34a' : ($pctF >= 50 ? '#d97706' : '#dc2626') ?>"></div>
                    </div>
                    <div class="db-bienio-kpi-lbl">Cobertura Fiscal</div>
                </div>
                <div class="db-bienio-kpi">
                    <div class="db-bienio-kpi-num" style="color:#6d28d9"><?= number_format($prazo, 1, ',', '') ?>m</div>
                    <div class="db-bienio-kpi-bar">
                        <div class="db-bienio-kpi-fill" style="width:<?= min(100, $prazo/24*100) ?>%;background:#6d28d9"></div>
                    </div>
                    <div class="db-bienio-kpi-lbl">Prazo Médio</div>
                </div>
                <div class="db-bienio-kpi">
                    <div class="db-bienio-kpi-num" style="color:#0284c7"><?= (int)($b['com_aditivos'] ?? 0) ?></div>
                    <div class="db-bienio-kpi-bar">
                        <div class="db-bienio-kpi-fill" style="width:<?= $total > 0 ? round(($b['com_aditivos']??0)/$total*100) : 0 ?>%;background:#0284c7"></div>
                    </div>
                    <div class="db-bienio-kpi-lbl">Com Aditivos</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ══════════════════════════ CARGA POR SETOR + FISCALIZAÇÃO ════════ -->
<div class="row g-3 mb-4">

    <!-- Carga por Setor -->
    <div class="col-12 col-xl-6">
        <section class="gc-card p-3 h-100">
            <h2 class="h6 fw-bold mb-1">
                <i class="bi bi-building me-1 text-primary"></i>
                Carga por Secretaria/Setor
            </h2>
            <p class="small text-muted mb-3">Instrumentos vigentes · <span style="color:#2563eb;font-weight:600">■ Contratos</span> &nbsp;<span style="color:#0f766e;font-weight:600">■ ARPs</span></p>
            <?php if (empty($cargaSetor)): ?>
                <p class="text-secondary">Sem dados.</p>
            <?php else: foreach ($cargaSetor as $row):
                $ctr  = (int)$row['contratos'];
                $arp  = (int)$row['arps'];
                $tot  = (int)$row['total'];
                $wCtr = $maxSetor > 0 ? round($ctr / $maxSetor * 100) : 0;
                $wArp = $maxSetor > 0 ? round($arp / $maxSetor * 100) : 0;
                $warn = (int)$row['vencendo_90d'];
                $sg   = (int)$row['sem_gestor'];
            ?>
            <div class="db-setor-item">
                <div class="db-setor-name" title="<?= e($row['setor_nome']) ?>">
                    <?= e(mb_strimwidth($row['setor_nome'], 0, 48, '…')) ?>
                </div>
                <div class="db-setor-bar-wrap">
                    <?php if ($wCtr > 0): ?>
                    <div class="db-setor-bar-ctr" style="width:<?= $wCtr ?>%"><?= $ctr > 2 ? $ctr : '' ?></div>
                    <?php endif; ?>
                    <?php if ($wArp > 0): ?>
                    <div class="db-setor-bar-arp" style="width:<?= $wArp ?>%"><?= $arp > 2 ? $arp : '' ?></div>
                    <?php endif; ?>
                </div>
                <div class="db-setor-meta">
                    <?php if ($ctr): ?><span class="db-setor-meta-ctr"><?= $ctr ?> ctr</span><?php endif; ?>
                    <?php if ($arp):  ?><span class="db-setor-meta-arp"><?= $arp ?> ARP</span><?php endif; ?>
                    <?php if ($warn): ?><span class="db-setor-meta-warn"><i class="bi bi-clock-fill"></i> <?= $warn ?> vencem 90d</span><?php endif; ?>
                    <?php if ($sg):   ?><span style="color:#7c3aed;font-weight:600;font-size:.65rem"><i class="bi bi-person-dash"></i> <?= $sg ?> s/ gestor</span><?php endif; ?>
                    <span style="margin-left:auto;color:#94a3b8;font-size:.65rem;font-weight:600"><?= $tot ?> total</span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </section>
    </div>

    <!-- Carga de Fiscalização -->
    <div class="col-12 col-xl-6">
        <section class="gc-card p-3 h-100">
            <h2 class="h6 fw-bold mb-1">
                <i class="bi bi-people-fill me-1 text-primary"></i>
                Carga de Fiscalização por Servidor
            </h2>
            <p class="small text-muted mb-3">
                Soma de vínculos vigentes por servidor ·
                <span class="db-pill db-pill-g">Gestor</span>
                <span class="db-pill db-pill-f">Fiscal</span>
                <span class="db-pill db-pill-s">Substituto</span>
            </p>
            <?php if (empty($cargaFiscal)): ?>
                <p class="text-secondary">Sem dados.</p>
            <?php else:
                $ALERTA_FISCAL = 10;
                foreach ($cargaFiscal as $row):
                    $tot  = (int)$row['total'];
                    $g    = (int)$row['como_gestor'];
                    $f    = (int)$row['como_fiscal'];
                    $s    = (int)$row['como_sub'];
                    $wG   = $maxFiscal > 0 ? round($g / $maxFiscal * 100) : 0;
                    $wF   = $maxFiscal > 0 ? round($f / $maxFiscal * 100) : 0;
                    $wS   = $maxFiscal > 0 ? round($s / $maxFiscal * 100) : 0;
                    $sobrecarregado = $tot >= $ALERTA_FISCAL;
            ?>
            <div class="db-fiscal-item">
                <div class="db-fiscal-name d-flex align-items-center gap-2">
                    <span><?= e($row['servidor']) ?></span>
                    <?php if ($sobrecarregado): ?>
                    <span class="badge bg-warning text-dark" style="font-size:.6rem">alta carga</span>
                    <?php endif; ?>
                </div>
                <div class="db-fiscal-bar-wrap">
                    <div class="db-fiscal-bar-g" style="width:<?= $wG ?>%"></div>
                    <div class="db-fiscal-bar-f" style="width:<?= $wF ?>%"></div>
                    <div class="db-fiscal-bar-s" style="width:<?= $wS ?>%"></div>
                </div>
                <div class="db-fiscal-pills">
                    <?php if ($g): ?><span class="db-pill db-pill-g"><?= $g ?> gestor</span><?php endif; ?>
                    <?php if ($f): ?><span class="db-pill db-pill-f"><?= $f ?> fiscal</span><?php endif; ?>
                    <?php if ($s): ?><span class="db-pill db-pill-s"><?= $s ?> sub</span><?php endif; ?>
                    <span class="db-pill db-pill-total"><?= $tot ?> total</span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </section>
    </div>

</div>

<?php $scripts = ''; ?>

<?php
// Helpers
function initials(string $name): string {
    $stop = ['DE', 'DO', 'DA', 'DOS', 'DAS', 'E', 'A', 'O', 'EM', 'NO', 'NA'];
    $words = array_filter(explode(' ', strtoupper($name)));
    $result = '';
    foreach ($words as $w) {
        if (!in_array($w, $stop, true)) $result .= mb_substr($w, 0, 1);
        if (mb_strlen($result) >= 5) break;
    }
    return $result ?: mb_substr($name, 0, 3);
}

function bienioLabel(string $key): string {
    return match($key) {
        '2025-2026' => 'Biênio 2025–2026',
        '2023-2024' => 'Biênio 2023–2024',
        default     => 'Demais Exercícios Pretéritos',
    };
}

function bienioOf(array $row): string {
    $ano = (int)($row['ano'] ?? 0);
    if ($ano === 2025 || $ano === 2026) return '2025-2026';
    if ($ano === 2023 || $ano === 2024) return '2023-2024';
    return 'Demais';
}

$valorArpTotal      = array_sum(array_column($secretarias, 'valor_arp'));
$valorContratoTotal = array_sum(array_column($secretarias, 'valor_contrato'));
$bienioOrder        = ['2025-2026', '2023-2024', 'Demais'];
?>
<style>
/* ─── tipo badges ────────────────────────────────────────── */
.tipo-arp      { display:inline-block;padding:2px 9px;border-radius:12px;font-size:.68rem;font-weight:700;background:#d1fae5;color:#065f46;white-space:nowrap; }
.tipo-contrato { display:inline-block;padding:2px 9px;border-radius:12px;font-size:.68rem;font-weight:700;background:#dbeafe;color:#1e40af;white-space:nowrap; }

/* ─── summary cards on cover ─────────────────────────────── */
.kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px; }
.kpi-card { background:rgba(255,255,255,.13);border-radius:12px;padding:16px 14px;text-align:center; }
.kpi-val  { font-size:2rem;font-weight:800;line-height:1; }
.kpi-lbl  { font-size:.68rem;opacity:.75;text-transform:uppercase;letter-spacing:.07em;margin-top:4px; }

/* ─── global summary tables ──────────────────────────────── */
.summary-grid { display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px; }
.sum-block    { background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden; }
.sum-block-hdr{ background:#1a3a5c;color:#fff;padding:11px 16px;font-weight:700;font-size:.85rem; }
.sum-tbl      { width:100%;border-collapse:collapse;font-size:.8rem; }
.sum-tbl th   { background:#f8fafc;color:#64748b;text-transform:uppercase;font-size:.65rem;letter-spacing:.05em;padding:7px 12px;border-bottom:2px solid #e2e8f0;font-weight:700;text-align:right; }
.sum-tbl th:first-child { text-align:left; }
.sum-tbl td   { padding:7px 12px;border-bottom:1px solid #e2e8f0;text-align:right; }
.sum-tbl td:first-child { text-align:left;font-weight:600; }
.sum-tbl tr:last-child td { border-bottom:none;font-weight:700;background:#f1f5f9; }

/* ─── per-secretaria card ────────────────────────────────── */
.sec-card2        { background:#fff;border-radius:12px;margin-bottom:24px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;break-inside:avoid; }
.sec-hdr2         { background:var(--pr-accent);color:#fff;padding:13px 18px;display:flex;align-items:center;gap:14px; }
.sec-init2        { width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0;letter-spacing:-.03em; }
.sec-hdr2-info    { flex:1; }
.sec-hdr2-name    { font-weight:700;font-size:.95rem;line-height:1.3; }
.sec-hdr2-sub     { font-size:.7rem;opacity:.7;margin-top:2px; }
.sec-hdr2-right   { text-align:right; }
.sec-hdr2-val     { font-size:1.05rem;font-weight:800; }
.sec-hdr2-vlbl    { font-size:.62rem;opacity:.65;text-transform:uppercase; }

/* mini summary inside card */
.mini-sum         { display:flex;gap:0;border-bottom:1px solid #e2e8f0; }
.mini-sum-cell    { flex:1;padding:9px 12px;text-align:center;border-right:1px solid #e2e8f0; }
.mini-sum-cell:last-child { border-right:none; }
.mini-sum-lbl     { font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em; }
.mini-sum-val     { font-size:.9rem;font-weight:700;color:#1e293b;margin-top:2px; }
.mini-sum-val.arp-col { color:#065f46; }
.mini-sum-val.ctr-col { color:#1e40af; }

/* detail table */
.det-tbl      { width:100%;border-collapse:collapse;font-size:.75rem; }
.det-tbl thead th { background:#f8fafc;color:#64748b;text-transform:uppercase;font-size:.6rem;letter-spacing:.04em;padding:6px 10px;border-bottom:2px solid #e2e8f0;font-weight:700;white-space:nowrap; }
.det-tbl tbody td { padding:7px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top; }
.det-tbl tbody tr:last-child td { border-bottom:none; }
.det-tbl tbody tr:hover { background:#f8fafc; }
.obj-cell     { max-width:220px;color:#374151; }
.num-cell     { font-weight:600;white-space:nowrap;color:var(--pr-accent2); }
.val-r        { text-align:right;white-space:nowrap;font-weight:600; }

/* ─── PRINT overrides ────────────────────────────────────── */
@media print {
    .kpi-grid        { grid-template-columns:repeat(4,1fr);gap:10px;margin-top:16px; }
    .kpi-card        { padding:10px 8px; }
    .kpi-val         { font-size:1.5rem; }
    .summary-grid    { gap:12px;margin-bottom:18px; }
    .sum-block       { box-shadow:none;border:1px solid #e2e8f0; }
    .sec-card2       { box-shadow:none;border:1px solid #e2e8f0;margin-bottom:14px; }
    .det-tbl         { font-size:.67rem; }
    .det-tbl thead th{ padding:4px 7px;font-size:.54rem; }
    .det-tbl tbody td{ padding:4px 7px; }
    .mini-sum-cell   { padding:6px 8px; }
    .mini-sum-val    { font-size:.82rem; }
    .tipo-arp,.tipo-contrato { font-size:.6rem;padding:1px 6px; }
    .obj-cell        { max-width:160px; }
    .kpi-card, .sum-block-hdr, .sec-hdr2 {
        -webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
}
</style>

<!-- ───────── toolbar ───────── -->
<div class="print-toolbar">
    <a href="<?= e(url('/relatorios')) ?>" class="btn-back"><i class="bi bi-arrow-left"></i> Relatórios</a>
    <div class="pt-title">Contratos e ARPs por Secretaria</div>
    <span class="pt-badge"><i class="bi bi-calendar3 me-1"></i><?= e($geradoEm) ?></span>
    <a href="<?= e(url('/relatorios/secretaria-arps?export=docx')) ?>"
       style="font-size:.75rem;padding:5px 14px;border-radius:8px;font-weight:700;
              background:rgba(255,255,255,.15);color:#fff;text-decoration:none;
              border:1px solid rgba(255,255,255,.4);display:flex;align-items:center;gap:6px">
        <i class="bi bi-file-earmark-word"></i> Exportar .docx
    </a>
    <button class="btn-print no-print" id="btn-cols"
            style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.35);border-radius:8px;
                   padding:5px 14px;color:#fff;font-size:.75rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px">
        <i class="bi bi-layout-three-columns"></i> Colunas
    </button>
    <button class="btn-print" id="btn-print"><i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF</button>
</div>

<!-- Painel seletor de colunas (oculto por padrão, não imprime) -->
<div id="col-panel" class="no-print" style="display:none;background:#1e3a5f;border-bottom:2px solid #2563eb;padding:10px 24px;gap:8px;flex-wrap:wrap;align-items:center">
    <span style="font-size:.72rem;font-weight:700;color:#93c5fd;text-transform:uppercase;letter-spacing:.06em;margin-right:8px">Colunas visíveis:</span>
    <?php
    $colunas = [
        'col-tipo'     => ['label' => 'Tipo',             'default' => true],
        'col-num'      => ['label' => 'N°/Ano',           'default' => true],
        'col-credor'   => ['label' => 'Credor',           'default' => true],
        'col-objeto'   => ['label' => 'Objeto',           'default' => true],
        'col-inicio'   => ['label' => 'Início',           'default' => true],
        'col-termino'  => ['label' => 'Término',          'default' => true],
        'col-vinicial' => ['label' => 'Valor Inicial',    'default' => true],
        'col-vatual'   => ['label' => 'Valor Atualizado', 'default' => true],
        'col-vexec'    => ['label' => 'Valor Executado',  'default' => false],
        'col-gestor'   => ['label' => 'Gestor',           'default' => false],
        'col-fiscal'   => ['label' => 'Fiscal',           'default' => false],
    ];
    foreach ($colunas as $id => $col): ?>
    <label style="display:flex;align-items:center;gap:5px;cursor:pointer;padding:4px 10px;border-radius:20px;
                  background:<?= $col['default'] ? 'rgba(255,255,255,.15)' : 'rgba(255,255,255,.06)' ?>;
                  border:1px solid rgba(255,255,255,.2);font-size:.72rem;color:#e2e8f0;font-weight:600;transition:.15s">
        <input type="checkbox" data-col="<?= $id ?>" <?= $col['default'] ? 'checked' : '' ?>
               style="accent-color:#60a5fa;width:13px;height:13px">
        <?= $col['label'] ?>
    </label>
    <?php endforeach; ?>
</div>
<div style="background:#fefce8;border-bottom:1px solid #fde68a;padding:7px 24px;font-size:.75rem;color:#92400e;display:flex;align-items:center;gap:8px" class="no-print">
    <i class="bi bi-info-circle-fill" style="color:#d97706"></i>
    <span>Para melhor resultado ao salvar como PDF: desmarque <strong>"Cabeçalhos e rodapés"</strong> no diálogo de impressão.</span>
</div>

<div class="report-wrap">

    <!-- ───────── CAPA ───────── -->
    <div class="report-cover">
        <div class="cover-orgao">Tribunal de Justiça do Estado do Pará — TJPA</div>
        <div class="cover-titulo">Relatório de Contratos e Atas Vigentes</div>
        <div class="cover-subtitulo">Instrumentos vigentes agrupados por secretaria/setor</div>
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-val"><?= $totalContratos ?></div>
                <div class="kpi-lbl">Contratos</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val"><?= $totalArps ?></div>
                <div class="kpi-lbl">ARPs</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val"><?= $totalGeral ?></div>
                <div class="kpi-lbl">Total</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val" style="font-size:1.4rem"><?= money_br($totalValor) ?></div>
                <div class="kpi-lbl">Valor Total</div>
            </div>
        </div>
        <div class="cover-meta">Gerado em <?= e($geradoEm) ?> · <?= count($secretarias) ?> secretarias/setores</div>
    </div>

    <!-- ───────── TABELAS RESUMO ───────── -->
    <div class="summary-grid">

        <!-- Situação Geral -->
        <div class="sum-block">
            <div class="sum-block-hdr"><i class="bi bi-table me-2"></i>Situação Geral</div>
            <table class="sum-tbl">
                <thead>
                    <tr><th>Tipo</th><th>Quantidade</th><th>Valor Total</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="tipo-arp">ARP</span></td>
                        <td><?= $totalArps ?></td>
                        <td><?= money_br($valorArpTotal) ?></td>
                    </tr>
                    <tr>
                        <td><span class="tipo-contrato">CONTRATO</span></td>
                        <td><?= $totalContratos ?></td>
                        <td><?= money_br($valorContratoTotal) ?></td>
                    </tr>
                    <tr>
                        <td>TOTAL GERAL</td>
                        <td><?= $totalGeral ?></td>
                        <td><?= money_br($totalValor) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Por Biênio -->
        <div class="sum-block">
            <div class="sum-block-hdr"><i class="bi bi-calendar2-range me-2"></i>Por Biênio</div>
            <table class="sum-tbl">
                <thead>
                    <tr><th>Período</th><th>ARPs</th><th>Contratos</th><th>Total</th><th>Valor Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($bienioOrder as $bk):
                        $bs   = $bienioStats[$bk];
                        $bqtd = $bs['arps'] + $bs['contratos'];
                        $bval = $bs['valor_arp'] + $bs['valor_contrato'];
                    ?>
                    <tr>
                        <td><?= bienioLabel($bk) ?></td>
                        <td><?= $bs['arps'] ?></td>
                        <td><?= $bs['contratos'] ?></td>
                        <td><?= $bqtd ?></td>
                        <td><?= money_br($bval) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>TOTAL GERAL</td>
                        <td><?= $totalArps ?></td>
                        <td><?= $totalContratos ?></td>
                        <td><?= $totalGeral ?></td>
                        <td><?= money_br($totalValor) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- ───────── POR SECRETARIA ───────── -->
    <?php foreach ($secretarias as $secNome => $sec):
        $secQtd   = $sec['qtd_arp'] + $sec['qtd_contrato'];
        $secValor = $sec['valor_arp'] + $sec['valor_contrato'];
        $init     = initials($secNome);
    ?>
    <div class="sec-card2">

        <!-- cabeçalho da secretaria -->
        <div class="sec-hdr2">
            <div class="sec-init2"><?= e($init) ?></div>
            <div class="sec-hdr2-info">
                <div class="sec-hdr2-name"><?= e($secNome) ?></div>
                <div class="sec-hdr2-sub"><?= $secQtd ?> instrumento<?= $secQtd !== 1 ? 's' : '' ?></div>
            </div>
            <div class="sec-hdr2-right">
                <div class="sec-hdr2-val"><?= money_br($secValor) ?></div>
                <div class="sec-hdr2-vlbl">Valor Total</div>
            </div>
        </div>

        <!-- mini-resumo ARP / Contratos / Total -->
        <div class="mini-sum">
            <div class="mini-sum-cell">
                <div class="mini-sum-lbl">ARPs</div>
                <div class="mini-sum-val arp-col"><?= $sec['qtd_arp'] ?></div>
            </div>
            <div class="mini-sum-cell">
                <div class="mini-sum-lbl">Contratos</div>
                <div class="mini-sum-val ctr-col"><?= $sec['qtd_contrato'] ?></div>
            </div>
            <div class="mini-sum-cell">
                <div class="mini-sum-lbl">Total</div>
                <div class="mini-sum-val"><?= $secQtd ?></div>
            </div>
            <div class="mini-sum-cell">
                <div class="mini-sum-lbl">Valor ARPs</div>
                <div class="mini-sum-val arp-col"><?= money_br($sec['valor_arp']) ?></div>
            </div>
            <div class="mini-sum-cell">
                <div class="mini-sum-lbl">Valor Contratos</div>
                <div class="mini-sum-val ctr-col"><?= money_br($sec['valor_contrato']) ?></div>
            </div>
            <div class="mini-sum-cell">
                <div class="mini-sum-lbl">Valor Total</div>
                <div class="mini-sum-val"><?= money_br($secValor) ?></div>
            </div>
        </div>

        <!-- tabela de instrumentos -->
        <table class="det-tbl">
            <thead>
                <tr>
                    <th data-col="col-tipo">Tipo</th>
                    <th data-col="col-num">N°/Ano</th>
                    <th data-col="col-credor">Credor</th>
                    <th data-col="col-objeto">Objeto</th>
                    <th data-col="col-inicio">Início</th>
                    <th data-col="col-termino">Término</th>
                    <th data-col="col-vinicial" class="val-r">Valor Inicial</th>
                    <th data-col="col-vatual"   class="val-r">Valor Atualizado</th>
                    <th data-col="col-vexec"    class="val-r" style="display:none">Valor Executado</th>
                    <th data-col="col-gestor"   style="display:none">Gestor</th>
                    <th data-col="col-fiscal"   style="display:none">Fiscal</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Group by biênio inside the secretaria
            $bienioGroups = [];
            foreach ($sec['itens'] as $item) {
                $bienioGroups[bienioOf($item)][] = $item;
            }
            foreach ($bienioOrder as $bk):
                if (empty($bienioGroups[$bk])) continue;
            ?>
                <!-- biênio separator -->
                <tr class="bienio-sep">
                    <td colspan="11" style="background:#f1f5f9;font-size:.65rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.07em;padding:5px 10px;border-top:1px solid #e2e8f0">
                        <?= bienioLabel($bk) ?>
                    </td>
                </tr>
            <?php foreach ($bienioGroups[$bk] as $item):
                $vExec = (float)($item['valor_executado'] ?? 0);
            ?>
                <tr>
                    <td data-col="col-tipo">
                        <?php if ($item['tipo'] === 'ARP'): ?>
                            <span class="tipo-arp">ARP</span>
                        <?php else: ?>
                            <span class="tipo-contrato">CONTRATO</span>
                        <?php endif; ?>
                    </td>
                    <td data-col="col-num" class="num-cell"><?= e(ltrim($item['numero'], '0') ?: $item['numero']) ?>/<?= e($item['ano']) ?></td>
                    <td data-col="col-credor" class="fornecedor-cell" style="max-width:160px"><?= e($item['fornecedor_nome']) ?></td>
                    <td data-col="col-objeto" class="obj-cell"><?= e(mb_strimwidth($item['objeto'] ?? '', 0, 80, '…')) ?></td>
                    <td data-col="col-inicio" style="white-space:nowrap"><?= e(date_br($item['data_inicio'])) ?></td>
                    <td data-col="col-termino" style="white-space:nowrap"><?= e(date_br($item['data_termino'])) ?></td>
                    <td data-col="col-vinicial" class="val-r"><?= e(money_br($item['valor_global_inicial'])) ?></td>
                    <td data-col="col-vatual"   class="val-r"><?= e(money_br($item['valor_global_atualizado'])) ?></td>
                    <td data-col="col-vexec"    class="val-r" style="display:none;color:<?= $vExec > 0 ? '#16a34a' : '#94a3b8' ?>">
                        <?= $vExec > 0 ? money_br($vExec) : '—' ?>
                    </td>
                    <td data-col="col-gestor"   style="display:none;font-size:.7rem;color:#64748b"><?= e(mb_strimwidth($item['gestor'] ?? '—', 0, 30, '…')) ?></td>
                    <td data-col="col-fiscal"   style="display:none;font-size:.7rem;color:#64748b"><?= e(mb_strimwidth($item['fiscal_tecnico'] ?? '—', 0, 30, '…')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
    <?php endforeach; ?>

    <div class="report-footer">
        Tribunal de Justiça do Estado do Pará — Sistema de Gestão de Contratos · Gerado em <?= e($geradoEm) ?>
    </div>

</div>

<script>
(function () {
    const btnCols  = document.getElementById('btn-cols');
    const panel    = document.getElementById('col-panel');
    const checks   = panel ? panel.querySelectorAll('input[type=checkbox]') : [];

    function applyCol(colId, visible) {
        document.querySelectorAll('[data-col="' + colId + '"]').forEach(function(el) {
            el.style.display = visible ? '' : 'none';
        });
        // Ajusta separador biênio para cobrir colunas visíveis
        const totalCols = document.querySelectorAll('.det-tbl thead th:not([style*="display:none"])').length;
        document.querySelectorAll('.bienio-sep td').forEach(function(td) {
            td.setAttribute('colspan', totalCols || 11);
        });
    }

    checks.forEach(function(chk) {
        chk.addEventListener('change', function() {
            applyCol(this.dataset.col, this.checked);
            // Atualiza visual do label
            this.closest('label').style.background = this.checked
                ? 'rgba(255,255,255,.15)' : 'rgba(255,255,255,.06)';
        });
    });

    if (btnCols) {
        btnCols.addEventListener('click', function() {
            panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
        });
    }
})();
</script>

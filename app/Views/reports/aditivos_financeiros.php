<?php
function brl(float $v): string {
    return 'R$&nbsp;' . number_format($v, 2, ',', '.');
}
?>
<style>
@page { size: A4 landscape; margin: 1.2cm 1.5cm; }

.rp-cover {
    background: linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);
    color: #fff; border-radius: 12px; padding: 22px 28px; margin-bottom: 20px;
}
.rp-cover h1  { font-size: 1.25rem; font-weight: 800; margin: 0 0 3px; }
.rp-cover .sub{ font-size: .75rem; opacity: .75; }
.rp-kpi-grid  { display: grid; grid-template-columns: repeat(5,1fr); gap: 10px; margin-top: 16px; }
.rp-kpi       { background: rgba(255,255,255,.12); border-radius: 8px; padding: 10px 12px; }
.rp-kpi-num   { font-size: .88rem; font-weight: 800; line-height: 1.2; }
.rp-kpi-lbl   { font-size: .56rem; opacity: .7; text-transform: uppercase; letter-spacing: .06em; margin-top: 3px; }

.rp-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.rp-table thead th {
    background: #1e3a5f; color: #fff; padding: 7px 8px;
    font-size: .6rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; white-space: nowrap;
}
.rp-table tbody tr { border-bottom: 1px solid #e8edf2; }
.rp-table tbody tr:nth-child(even) { background: #f8fafc; }
.rp-table td { padding: 6px 8px; vertical-align: middle; white-space: nowrap; }
.rp-table tfoot td { background: #1e3a5f; color: #fff; font-weight: 700; padding: 7px 8px; font-size: .7rem; }

.rp-chave { font-size: .72rem; font-weight: 800; color: #1e40af; white-space: nowrap; }
.rp-forn  { font-size: .65rem; color: #334155; }

@media print {
    .rp-cover, .rp-table thead th, .rp-table tbody tr:nth-child(even),
    .rp-table tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    tr { page-break-inside: avoid; }
}
</style>

<!-- Barra de ações -->
<div class="print-toolbar">
    <a href="<?= e(url('/relatorios')) ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i> Relatórios
    </a>
    <div class="pt-title">Contratos e Atas — Aditivos Financeiros</div>
    <a href="<?= e(url('/relatorios/aditivos-financeiros?export=docx')) ?>"
       style="font-size:.75rem;padding:5px 14px;border-radius:8px;font-weight:700;
              background:rgba(255,255,255,.15);color:#fff;text-decoration:none;
              border:1px solid rgba(255,255,255,.4);display:flex;align-items:center;gap:6px">
        <i class="bi bi-file-earmark-word"></i> Exportar .docx
    </a>
    <button id="btn-cols-af" class="no-print"
            style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.35);border-radius:8px;
                   padding:5px 14px;color:#fff;font-size:.75rem;font-weight:700;cursor:pointer;
                   display:flex;align-items:center;gap:6px">
        <i class="bi bi-layout-three-columns"></i> Colunas
    </button>
    <button class="btn-print" id="btn-print">
        <i class="bi bi-printer-fill"></i> Imprimir / PDF
    </button>
</div>

<!-- Painel seletor de colunas -->
<div id="col-panel-af" class="no-print"
     style="display:none;flex-wrap:wrap;gap:6px;align-items:center;
            background:#1a3354;border-bottom:2px solid #2563eb;padding:10px 20px">
    <span style="font-size:.68rem;font-weight:700;color:#93c5fd;text-transform:uppercase;
                 letter-spacing:.06em;margin-right:6px;white-space:nowrap">
        Colunas visíveis:
    </span>
    <?php
    $colsAf = [
        'af-chave'  => 'Contrato / Ata',
        'af-forn'   => 'Fornecedor',
        'af-term'   => 'Término',
        'af-nadit'  => '# Aditivos',
        'af-vorig'  => 'Valor Original',
        'af-var'    => 'Variações (discriminadas)',
        'af-vatual' => 'Valor Atual',
        'af-vtotal' => 'Valor Total',
        'af-exec26' => 'Valor Executado',
    ];
    foreach ($colsAf as $id => $label): ?>
    <label style="display:flex;align-items:center;gap:4px;cursor:pointer;
           padding:4px 10px;border-radius:20px;background:rgba(255,255,255,.18);
           border:1px solid rgba(255,255,255,.25);font-size:.7rem;color:#e2e8f0;font-weight:600">
        <input type="checkbox" data-col-af="<?= $id ?>" checked
               style="accent-color:#60a5fa;width:13px;height:13px">
        <?= $label ?>
    </label>
    <?php endforeach; ?>
</div>

<!-- Capa -->
<div class="rp-cover">
    <h1>Contratos e Atas Vigentes — Aditivos com Efeito Financeiro</h1>
    <div class="sub">Reajustes, acréscimos e prorrogações &nbsp;·&nbsp; Gerado em <?= e($geradoEm) ?></div>
    <div class="rp-kpi-grid">
        <div class="rp-kpi">
            <?php
                $qtdContratos = count(array_filter($rows, fn($r) => ($r['tipo'] ?? '') === 'CONTRATO'));
                $qtdArps      = count($rows) - $qtdContratos;
            ?>
            <div class="rp-kpi-num"><?= $qtdContratos ?> / <?= $qtdArps ?></div>
            <div class="rp-kpi-lbl">Contratos / Atas</div>
        </div>
        <div class="rp-kpi">
            <div class="rp-kpi-num"><?= brl($totalOriginal) ?></div>
            <div class="rp-kpi-lbl">Valor Original Total</div>
        </div>
        <div class="rp-kpi">
            <div class="rp-kpi-num"><?= brl($totalAtual) ?></div>
            <div class="rp-kpi-lbl">Valor Atual (vigência)</div>
        </div>
        <div class="rp-kpi">
            <div class="rp-kpi-num"><?= brl($totalTotal) ?></div>
            <div class="rp-kpi-lbl">Valor Total (c/ Prorrog.)</div>
        </div>
        <div class="rp-kpi">
            <?php $varPct = $totalOriginal > 0 ? ($totalAtual - $totalOriginal) / $totalOriginal * 100 : 0; ?>
            <div class="rp-kpi-num"><?= ($varPct > 0 ? '+' : '') . number_format($varPct, 1, ',', '.') ?>%</div>
            <div class="rp-kpi-lbl">Variação Original → Atual</div>
        </div>
    </div>
</div>

<!-- Tabela -->
<table class="rp-table">
    <thead>
        <tr>
            <th data-col-af="af-chave">Contrato / Ata</th>
            <th data-col-af="af-forn">Fornecedor</th>
            <th data-col-af="af-term"   style="text-align:center">Término</th>
            <th data-col-af="af-nadit"  style="text-align:center">#</th>
            <th data-col-af="af-vorig"  style="text-align:right">Valor Original</th>
            <th data-col-af="af-var"    style="text-align:right;min-width:180px">Variações</th>
            <th data-col-af="af-vatual" style="text-align:right">Valor Atual</th>
            <th data-col-af="af-vtotal" style="text-align:right">Valor Total</th>
            <th data-col-af="af-exec26" style="text-align:right;background:#064e3b">Valor Executado</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $grupos = [];
    foreach ($rows as $r) { $grupos[$r['setor_nome']][] = $r; }
    ksort($grupos);

    foreach ($grupos as $setor => $contratos):
        $gOrig  = array_sum(array_column($contratos, 'valor_global_inicial'));
        $gReaj  = array_sum(array_column($contratos, 'valor_reajustes'));
        $gCorr  = array_sum(array_column($contratos, 'valor_corrigido'));
        $gAdit  = array_sum(array_column($contratos, 'valor_aditivos'));
        $gProrr = array_sum(array_column($contratos, 'valor_prorrogacao'));
        $gAtual = array_sum(array_column($contratos, 'valor_atual'));
        $gTotal = array_sum(array_column($contratos, 'valor_total'));
        $nC = count(array_filter($contratos, fn($r) => ($r['tipo'] ?? '') === 'CONTRATO'));
        $nA = count($contratos) - $nC;
        $partes = [];
        if ($nC) $partes[] = $nC . ' contrato' . ($nC > 1 ? 's' : '');
        if ($nA) $partes[] = $nA . ' ata' . ($nA > 1 ? 's' : '');
    ?>
    <!-- Cabeçalho do grupo -->
    <tr>
        <td colspan="99" style="
            background:#1e3a5f;color:#fff;font-weight:700;font-size:.68rem;
            padding:5px 8px;letter-spacing:.04em;text-transform:uppercase;
            page-break-after:avoid;">
            <?= e($setor) ?>
            <span style="font-weight:400;opacity:.65;font-size:.6rem">
                (<?= implode(' · ', $partes) ?>)
            </span>
        </td>
    </tr>

    <?php foreach ($contratos as $r):
        $orig  = (float)$r['valor_global_inicial'];
        $reaj  = (float)$r['valor_reajustes'];
        $corr  = (float)$r['valor_corrigido'];
        $adit  = (float)$r['valor_aditivos'];
        $prorr = (float)$r['valor_prorrogacao'];
        $atual = (float)$r['valor_atual'];
        $total = (float)$r['valor_total'];
    ?>
    <tr>
        <td data-col-af="af-chave"><div class="rp-chave"><?= e($r['chave']) ?></div></td>
        <td data-col-af="af-forn"><div class="rp-forn"><?= e(mb_strimwidth($r['fornecedor_nome'] ?? '', 0, 38, '…')) ?></div></td>
        <td data-col-af="af-term"  style="text-align:center;font-size:.68rem">
            <?= $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '—' ?>
        </td>
        <td data-col-af="af-nadit" style="text-align:center">
            <?php if ((int)$r['quantidade_aditivos'] > 0): ?>
            <span style="background:#dbeafe;color:#1e40af;font-weight:700;padding:1px 7px;border-radius:10px;font-size:.62rem">
                <?= (int)$r['quantidade_aditivos'] ?>
            </span>
            <?php else: ?><span style="color:#94a3b8">—</span><?php endif; ?>
        </td>
        <td data-col-af="af-vorig" style="text-align:right"><?= brl($orig) ?></td>
        <td data-col-af="af-var"   style="text-align:right;line-height:1.6;">
            <?php if ($reaj != 0): ?>
            <div style="white-space:nowrap;">
                <span style="font-size:.58rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.04em;">Reajuste</span><br>
                <span style="color:<?= $reaj >= 0 ? '#16a34a' : '#dc2626' ?>;font-weight:600"><?= brl($reaj) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($adit != 0): ?>
            <div style="white-space:nowrap;<?= $reaj != 0 ? 'margin-top:4px;padding-top:4px;border-top:1px dashed #e2e8f0;' : '' ?>">
                <span style="font-size:.58rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.04em;">Aditivo</span><br>
                <span style="color:<?= $adit >= 0 ? '#7c3aed' : '#dc2626' ?>;font-weight:600"><?= brl($adit) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($prorr != 0): ?>
            <div style="white-space:nowrap;<?= ($reaj != 0 || $adit != 0) ? 'margin-top:4px;padding-top:4px;border-top:1px dashed #e2e8f0;' : '' ?>">
                <span style="font-size:.58rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.04em;">Prorrogação</span><br>
                <span style="color:#0369a1;font-weight:600"><?= brl($prorr) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($reaj == 0 && $adit == 0 && $prorr == 0): ?>
                <span style="color:#94a3b8">—</span>
            <?php endif; ?>
        </td>
        <td data-col-af="af-vatual" style="text-align:right;font-weight:700;color:#1e40af"><?= brl($atual) ?></td>
        <td data-col-af="af-vtotal" style="text-align:right;color:#475569"><?= brl($total) ?></td>
        <?php $exec26 = (float)$r['valor_executado_total']; ?>
        <td data-col-af="af-exec26" style="text-align:right;font-weight:700;color:<?= $exec26 > 0 ? '#065f46' : '#94a3b8' ?>">
            <?= $exec26 > 0 ? brl($exec26) : '—' ?>
        </td>
    </tr>
    <?php endforeach; ?>

    <!-- Subtotal do grupo -->
    <tr style="background:#eef2f7;border-top:2px solid #cbd5e1;font-size:.65rem;font-weight:700">
        <td data-col-af="af-chave" style="color:#1e3a5f;padding:5px 8px;white-space:nowrap">Subtotal</td>
        <td data-col-af="af-forn"   style="color:#475569;font-style:italic;font-size:.6rem"><?= e($setor) ?></td>
        <td data-col-af="af-term"></td>
        <td data-col-af="af-nadit"  style="text-align:center;color:#64748b"><?= count($contratos) ?></td>
        <td data-col-af="af-vorig" style="text-align:right"><?= brl($gOrig) ?></td>
        <td data-col-af="af-var"   style="text-align:right;line-height:1.6;font-size:.63rem;">
            <?php if ($gReaj != 0): ?><div style="white-space:nowrap;"><span style="color:#64748b;">Reajuste: </span><span style="color:#16a34a;font-weight:700"><?= brl($gReaj) ?></span></div><?php endif; ?>
            <?php if ($gAdit != 0): ?><div style="white-space:nowrap;"><span style="color:#64748b;">Aditivo: </span><span style="color:#7c3aed;font-weight:700"><?= brl($gAdit) ?></span></div><?php endif; ?>
            <?php if ($gProrr != 0): ?><div style="white-space:nowrap;"><span style="color:#64748b;">Prorrogação: </span><span style="color:#0369a1;font-weight:700"><?= brl($gProrr) ?></span></div><?php endif; ?>
        </td>
        <td data-col-af="af-vatual" style="text-align:right;color:#1e40af"><?= brl($gAtual) ?></td>
        <td data-col-af="af-vtotal" style="text-align:right"><?= brl($gTotal) ?></td>
        <?php $gExec26 = array_sum(array_column($contratos, 'valor_executado_total')); ?>
        <td data-col-af="af-exec26" style="text-align:right;color:#065f46;font-weight:700">
            <?= $gExec26 > 0 ? brl($gExec26) : '—' ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <!-- Total geral — fora do tfoot para não repetir em cada página impressa -->
    <tr style="background:#1e3a5f;color:#fff;font-weight:700;font-size:.7rem;page-break-inside:avoid;">
        <td data-col-af="af-chave" style="padding:7px 8px;white-space:nowrap">TOTAL GERAL</td>
        <td data-col-af="af-forn"  style="font-size:.62rem;opacity:.8"><?= count($rows) ?> instrumentos</td>
        <td data-col-af="af-term"></td>
        <td data-col-af="af-nadit"></td>
        <td data-col-af="af-vorig" style="text-align:right"><?= brl($totalOriginal) ?></td>
        <td data-col-af="af-var"   style="text-align:right;line-height:1.6;font-size:.63rem;">
            <?php if ($totalReajustes != 0): ?><div style="white-space:nowrap;"><span style="opacity:.7">Reajuste: </span><span style="color:#86efac;font-weight:700"><?= brl($totalReajustes) ?></span></div><?php endif; ?>
            <?php if ($totalAditivos != 0): ?><div style="white-space:nowrap;"><span style="opacity:.7">Aditivo: </span><span style="color:#c4b5fd;font-weight:700"><?= brl($totalAditivos) ?></span></div><?php endif; ?>
            <?php if ($totalProrrog != 0): ?><div style="white-space:nowrap;"><span style="opacity:.7">Prorrogação: </span><span style="color:#7dd3fc;font-weight:700"><?= brl($totalProrrog) ?></span></div><?php endif; ?>
        </td>
        <td data-col-af="af-vatual" style="text-align:right"><?= brl($totalAtual) ?></td>
        <td data-col-af="af-vtotal" style="text-align:right"><?= brl($totalTotal) ?></td>
        <td data-col-af="af-exec26" style="text-align:right;color:#86efac;font-weight:700"><?= brl($totalExec2026) ?></td>
    </tr>
    </tbody>
</table>

<?php $scripts = <<<'JS'
<script>
(function () {
    var btn   = document.getElementById('btn-cols-af');
    var panel = document.getElementById('col-panel-af');
    if (!btn || !panel) return;

    btn.addEventListener('click', function () {
        var open = panel.style.display !== 'flex';
        panel.style.display = open ? 'flex' : 'none';
        btn.style.background = open
            ? 'rgba(255,255,255,.35)'
            : 'rgba(255,255,255,.15)';
    });

    panel.querySelectorAll('input[type=checkbox]').forEach(function (chk) {
        chk.addEventListener('change', function () {
            var col = this.dataset.colAf;
            var vis = this.checked;
            document.querySelectorAll('[data-col-af="' + col + '"]').forEach(function (el) {
                el.style.display = vis ? '' : 'none';
            });
            var lbl = this.closest('label');
            if (lbl) {
                lbl.style.background = vis
                    ? 'rgba(255,255,255,.18)'
                    : 'rgba(255,255,255,.05)';
                lbl.style.opacity = vis ? '1' : '.5';
            }
        });
    });
})();
</script>
JS;
?>

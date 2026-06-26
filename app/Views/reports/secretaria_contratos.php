<?php
$situacaoLabel = match($situacao) {
    'Vigente'  => 'Contratos Vigentes',
    'Expirado' => 'Contratos Expirados',
    default    => 'Todos os Contratos',
};
$baseUrl = url('/relatorios/secretaria-contratos');
?>

<!-- Barra de ações -->
<div class="print-toolbar">
    <a href="<?= e(url('/relatorios')) ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i> Relatórios
    </a>
    <div class="pt-title">Contratos por Secretaria — Visão Geral</div>
    <span class="pt-badge"><i class="bi bi-calendar3 me-1"></i><?= e($geradoEm) ?></span>
    <button class="btn-print" id="btn-print">
        <i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF
    </button>
</div>
<div style="background:#fefce8;border-bottom:1px solid #fde68a;padding:7px 24px;font-size:.75rem;color:#92400e;display:flex;align-items:center;gap:8px" class="no-print">
    <i class="bi bi-info-circle-fill" style="color:#d97706"></i>
    <span>Para melhor resultado: desmarque <strong>"Cabeçalhos e rodapés"</strong> no diálogo de impressão.</span>
</div>

<!-- Filtros -->
<div class="print-filters">
    <span style="font-size:.82rem;font-weight:600;color:#374151">Situação:</span>
    <?php foreach (['Vigente' => 'Vigentes', 'Expirado' => 'Expirados', 'todos' => 'Todos'] as $v => $l): ?>
    <a href="<?= e($baseUrl . '?situacao=' . $v) ?>"
       style="font-size:.82rem;padding:4px 14px;border-radius:20px;text-decoration:none;font-weight:600;
              background:<?= $situacao===$v?'#1a3a5c':'#e2e8f0' ?>;
              color:<?= $situacao===$v?'#fff':'#475569' ?>">
        <?= $l ?>
    </a>
    <?php endforeach; ?>
    <span style="margin-left:auto;font-size:.75rem;color:#94a3b8">
        <?= count($rows) ?> secretarias · <?= $total ?> contratos
    </span>
</div>

<div class="report-wrap">

    <!-- Capa -->
    <div class="report-cover">
        <div class="cover-orgao">Tribunal de Justiça do Estado do Pará — TJPA</div>
        <div class="cover-titulo">Contratos por Secretaria</div>
        <div class="cover-subtitulo"><?= $situacaoLabel ?> · Visão Geral</div>
        <div class="cover-stats">
            <div class="cover-stat">
                <div class="cover-stat-num"><?= count($rows) ?></div>
                <div class="cover-stat-label">Secretarias</div>
            </div>
            <div class="cover-stat">
                <div class="cover-stat-num"><?= $total ?></div>
                <div class="cover-stat-label">Contratos</div>
            </div>
            <div class="cover-stat">
                <div class="cover-stat-num"><?= $rows[0]['secretaria'] ?? '—' ?></div>
                <div class="cover-stat-label">Maior portfólio</div>
            </div>
        </div>
        <div class="cover-meta">
            Gerado em <?= e($geradoEm) ?><br>GestContratos · TJPA
        </div>
    </div>

    <!-- Tabela + barras -->
    <div class="sec-card">
        <table class="sec-table" style="width:100%">
            <thead>
                <tr>
                    <th style="width:32px">#</th>
                    <th>Secretaria / Unidade Gestora</th>
                    <th class="text-center" style="width:90px">Contratos</th>
                    <th class="text-center" style="width:70px">% do total</th>
                    <th style="min-width:200px">Distribuição</th>
                    <?php if ($situacao === 'todos' || $situacao === 'Vigente'): ?>
                    <th class="text-center" style="width:80px">Vencem 90d</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $r):
                $pct     = $total > 0 ? round($r['total'] / $total * 100, 1) : 0;
                $barPct  = $max  > 0 ? round($r['total'] / $max  * 100)     : 0;
                $skip    = ['de','do','da','dos','das','e','a','o','em','para','por','com','um','uma'];
                $palavras = array_values(array_filter(
                    explode(' ', mb_strtolower($r['secretaria'])),
                    fn($w) => strlen($w) > 2 && !in_array($w, $skip)
                ));
                $iniciais = mb_strtoupper(($palavras[0][0] ?? '') . ($palavras[1][0] ?? ''));
                $cor = match(true) {
                    $i === 0 => '#1a3a5c',
                    $i <= 2  => '#2563eb',
                    $i <= 5  => '#3b82f6',
                    default  => '#93c5fd',
                };
            ?>
            <tr>
                <td style="color:#94a3b8;font-weight:700;font-size:.75rem"><?= $i + 1 ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:7px;background:<?= $cor ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    color:#fff;font-weight:800;font-size:.65rem;flex-shrink:0">
                            <?= e($iniciais) ?>
                        </div>
                        <span class="fw-semibold" style="font-size:.82rem"><?= e($r['secretaria']) ?></span>
                    </div>
                </td>
                <td class="text-center">
                    <span style="font-size:1.1rem;font-weight:800;color:<?= $cor ?>"><?= $r['total'] ?></span>
                </td>
                <td class="text-center">
                    <span style="font-size:.78rem;font-weight:600;color:#64748b"><?= $pct ?>%</span>
                </td>
                <td>
                    <div style="height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden">
                        <div style="height:100%;width:<?= $barPct ?>%;background:<?= $cor ?>;border-radius:5px;
                                    -webkit-print-color-adjust:exact;print-color-adjust:exact"></div>
                    </div>
                </td>
                <?php if ($situacao === 'todos' || $situacao === 'Vigente'): ?>
                <td class="text-center">
                    <?php if ($r['vence_90d'] > 0): ?>
                    <span style="background:#fef9c3;color:#854d0e;padding:2px 8px;border-radius:10px;font-size:.68rem;font-weight:700;
                                 -webkit-print-color-adjust:exact;print-color-adjust:exact">
                        <?= $r['vence_90d'] ?>
                    </span>
                    <?php else: ?>
                    <span style="color:#94a3b8;font-size:.72rem">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;border-top:2px solid #e2e8f0">
                    <td colspan="2" class="fw-bold" style="padding:10px 12px;font-size:.78rem">Total</td>
                    <td class="text-center fw-bold" style="font-size:1rem"><?= $total ?></td>
                    <td class="text-center" style="font-size:.78rem;color:#64748b">100%</td>
                    <td></td>
                    <?php if ($situacao === 'todos' || $situacao === 'Vigente'): ?>
                    <td class="text-center fw-bold" style="font-size:.82rem">
                        <?= array_sum(array_column($rows, 'vence_90d')) ?>
                    </td>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="report-footer">
        <div>Relatório gerado automaticamente pelo <strong>GestContratos · TJPA</strong> em <?= e($geradoEm) ?></div>
        <div style="margin-top:4px;font-size:.65rem">Este documento é de uso interno.</div>
    </div>

</div>

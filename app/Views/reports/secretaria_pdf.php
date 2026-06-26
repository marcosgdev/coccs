<?php
$situacaoLabel = match($situacao) {
    'Vigente'  => 'Contratos Vigentes',
    'Expirado' => 'Contratos Expirados',
    default    => 'Todos os Contratos',
};
$baseUrl = url('/relatorios/secretaria-pdf');
?>

<!-- Barra de ações -->
<div class="print-toolbar">
    <a href="<?= e(url('/relatorios')) ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i> Relatórios
    </a>
    <div class="pt-title">Contratos por Secretaria</div>
    <span class="pt-badge"><i class="bi bi-calendar3 me-1"></i><?= e($geradoEm) ?></span>
    <a href="<?= e(url('/relatorios/secretaria-pdf?export=xlsx&situacao=' . urlencode($situacao))) ?>"
       style="font-size:.75rem;padding:4px 14px;border-radius:8px;font-weight:700;
              background:rgba(255,255,255,.15);color:#fff;text-decoration:none;
              border:1px solid rgba(255,255,255,.4);display:flex;align-items:center;gap:6px">
        <i class="bi bi-file-earmark-excel"></i> Exportar .xlsx
    </a>
    <button class="btn-print" id="btn-print">
        <i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF
    </button>
</div>
<div style="background:#fefce8;border-bottom:1px solid #fde68a;padding:7px 24px;font-size:.75rem;color:#92400e;display:flex;align-items:center;gap:8px" class="no-print">
    <i class="bi bi-info-circle-fill" style="color:#d97706"></i>
    <span>Para melhor resultado ao salvar como PDF: no diálogo de impressão, desmarque <strong>"Cabeçalhos e rodapés"</strong> (Chrome) ou <strong>"Imprimir cabeçalhos e rodapés"</strong> (Edge/Firefox).</span>
</div>

<!-- Filtros rápidos -->
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
        <?= count($secretarias) ?> secretarias · <?= $totalContratos ?> contratos
    </span>
</div>

<div class="report-wrap">

    <!-- Capa -->
    <div class="report-cover">
        <div class="cover-orgao">Tribunal de Justiça do Estado do Pará — TJPA</div>
        <div class="cover-titulo">Contratos por Secretaria</div>
        <div class="cover-subtitulo"><?= $situacaoLabel ?></div>
        <div class="cover-stats">
            <div class="cover-stat">
                <div class="cover-stat-num"><?= count($secretarias) ?></div>
                <div class="cover-stat-label">Secretarias</div>
            </div>
            <div class="cover-stat">
                <div class="cover-stat-num"><?= $totalContratos ?></div>
                <div class="cover-stat-label">Contratos</div>
            </div>
        </div>
        <div class="cover-meta">
            Gerado em <?= e($geradoEm) ?><br>
            GestContratos · TJPA
        </div>
    </div>

    <!-- Uma seção por secretaria -->
    <?php foreach ($secretarias as $nome => $sec):
        $contratos  = $sec['contratos'];
        $qtd        = count($contratos);
        $valTotal   = $sec['valor_total'];
        $valExec    = $sec['valor_executado'];
        $pctExec    = $valTotal > 0 ? round($valExec / $valTotal * 100) : 0;
        $pctPortf   = $totalValor > 0 ? round($valTotal / $totalValor * 100, 1) : 0;
        $skip     = ['de','do','da','dos','das','e','a','o','em','para','por','com','um','uma'];
        $palavras = array_filter(explode(' ', mb_strtolower($nome)), fn($w) => strlen($w) > 2 && !in_array($w, $skip));
        $palavras = array_values($palavras);
        $iniciais = mb_strtoupper(($palavras[0][0] ?? '') . ($palavras[1][0] ?? ''));
    ?>
    <div class="sec-card">

        <!-- Cabeçalho da secretaria -->
        <div class="sec-header">
            <div class="sec-initial"><?= e($iniciais) ?></div>
            <div class="sec-name"><?= e($nome) ?></div>
            <div class="sec-stats">
                <div class="sec-stat">
                    <div class="sec-stat-val"><?= $qtd ?></div>
                    <div class="sec-stat-lbl">contratos</div>
                </div>
                <div class="sec-stat">
                    <div class="sec-stat-val" style="font-size:.78rem">R$&nbsp;<?= number_format($valTotal/1000000, 2, ',', '.') ?>M</div>
                    <div class="sec-stat-lbl">valor atualizado</div>
                </div>
                <div class="sec-stat">
                    <div class="sec-stat-val" style="font-size:.78rem;color:#16a34a">R$&nbsp;<?= number_format($valExec/1000000, 2, ',', '.') ?>M</div>
                    <div class="sec-stat-lbl">executado (<?= $pctExec ?>%)</div>
                </div>
            </div>
        </div>

        <!-- Tabela de contratos -->
        <table class="sec-table">
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th>Fornecedor</th>
                    <th>Situação</th>
                    <th>Início</th>
                    <th>Término</th>
                    <th>Prazo</th>
                    <th style="text-align:right">Valor Atualizado</th>
                    <th style="text-align:right">Valor Executado</th>
                    <th>Gestor</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contratos as $c):
                $dr = (int)($c['dias_restantes'] ?? 0);
                $sit = $c['situacao'] ?? '';
                $sitCls = match(true) {
                    str_contains(strtolower($sit), 'vigente')  => 'sit-vigente',
                    str_contains(strtolower($sit), 'expirado') => 'sit-expirado',
                    default => 'sit-outro',
                };
                if ($c['data_termino'] === null || $dr < 0) { $diasCls = 'dias-expired'; $diasTxt = 'Vencido'; }
                elseif ($dr < 30)  { $diasCls = 'dias-danger'; $diasTxt = $dr . 'd'; }
                elseif ($dr < 90)  { $diasCls = 'dias-warn';   $diasTxt = $dr . 'd'; }
                else               { $diasCls = 'dias-ok';     $diasTxt = $dr . 'd'; }
                $vExecC = (float)($c['valor_executado'] ?? 0);
                $vAtuC  = (float)($c['valor_global_atualizado'] ?? 0);
            ?>
            <tr>
                <td><span class="chave-link"><?= e($c['chave'] ?? '—') ?></span></td>
                <td class="fornecedor-cell"><?= e(mb_substr($c['fornecedor_nome'] ?? '—', 0, 35)) ?></td>
                <td><span class="sit-badge <?= $sitCls ?>"><?= e($sit ?: '—') ?></span></td>
                <td style="white-space:nowrap"><?= e(date_br($c['data_inicio'])) ?></td>
                <td style="white-space:nowrap"><?= e(date_br($c['data_termino'])) ?></td>
                <td><?php if ($c['data_termino']): ?><span class="dias-badge <?= $diasCls ?>"><?= $diasTxt ?></span><?php else: ?>—<?php endif; ?></td>
                <td class="valor-cell"><?= number_format($vAtuC, 2, ',', '.') ?></td>
                <td class="valor-cell" style="color:<?= $vExecC > 0 ? '#16a34a' : '#94a3b8' ?>">
                    <?= $vExecC > 0 ? number_format($vExecC, 2, ',', '.') : '—' ?>
                </td>
                <td style="font-size:.72rem;color:#64748b"><?= e(mb_substr($c['gestor'] ?? '—', 0, 25)) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9;font-weight:700;font-size:.72rem">
                    <td colspan="6" style="padding:5px 8px;color:#475569">Subtotal <?= e($nome) ?></td>
                    <td class="valor-cell"><?= number_format($valTotal, 2, ',', '.') ?></td>
                    <td class="valor-cell" style="color:#16a34a"><?= number_format($valExec, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endforeach; ?>

    <!-- Total geral -->
    <div style="background:#1e3a5f;color:#fff;border-radius:8px;padding:14px 20px;margin-bottom:20px;display:flex;gap:40px;align-items:center">
        <div style="font-weight:800;font-size:.9rem;flex:1">TOTAL GERAL — <?= $totalContratos ?> instrumentos</div>
        <div style="text-align:right">
            <div style="font-size:.62rem;opacity:.7;text-transform:uppercase;letter-spacing:.05em">Valor Atualizado</div>
            <div style="font-size:.9rem;font-weight:800">R$ <?= number_format($totalValor, 2, ',', '.') ?></div>
        </div>
        <div style="text-align:right">
            <div style="font-size:.62rem;opacity:.7;text-transform:uppercase;letter-spacing:.05em">Valor Executado</div>
            <div style="font-size:.9rem;font-weight:800;color:#86efac">R$ <?= number_format($totalValorExec, 2, ',', '.') ?></div>
        </div>
        <div style="text-align:right">
            <div style="font-size:.62rem;opacity:.7;text-transform:uppercase;letter-spacing:.05em">% Executado</div>
            <div style="font-size:.9rem;font-weight:800;color:#86efac">
                <?= $totalValor > 0 ? number_format($totalValorExec / $totalValor * 100, 1, ',', '.') : '0' ?>%
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="report-footer">
        <div>Relatório gerado automaticamente pelo <strong>GestContratos · TJPA</strong> em <?= e($geradoEm) ?></div>
        <div style="margin-top:4px;font-size:.65rem">Este documento é de uso interno. Dados extraídos do sistema de gestão de contratos.</div>
    </div>

</div>

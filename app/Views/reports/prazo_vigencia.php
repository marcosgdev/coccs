<?php
$brl = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');
$faixasOrdem = ['vencido', 'critico', 'alerta', 'atencao', 'ok', 'indefinido'];
$totalGeral  = $totais['count'];
$valorGeral  = $totais['valor'];
?>
<!-- Cabeçalho institucional -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 0 14px;border-bottom:2px solid #002952;margin-bottom:20px;" class="d-print-block">
    <img src="<?= e(asset('img/brasao-tjpa-azul.png')) ?>" alt="TJPA" style="height:56px;width:auto;">
    <div style="text-align:center;flex:1">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#64748b;">Poder Judiciário – Estado do Pará</div>
        <div style="font-weight:700;font-size:15px;color:#002952;">Relatório de Prazo e Vigência</div>
        <div style="font-size:12px;color:#64748b;">Coordenadoria de Convênios e Contratos · <?= e($geradoEm) ?></div>
    </div>
    <img src="<?= e(asset('img/logo-coccs.png')) ?>" alt="COCCS" style="height:52px;width:auto;">
</div>

<!-- Filtro de tipo (tela) -->
<div class="d-print-none mb-3 d-flex gap-2 align-items-center flex-wrap">
    <?php foreach (['todos' => 'Todos', 'CONTRATO' => 'Somente Contratos', 'ARP' => 'Somente ARPs'] as $v => $l): ?>
        <a href="<?= e(url('/relatorios/prazo-vigencia?tipo=' . $v)) ?>"
           class="btn btn-sm <?= $tipo === $v ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= e($l) ?>
        </a>
    <?php endforeach; ?>
    <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimir / PDF
    </button>
</div>

<!-- KPIs resumo -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px;">
    <?php
    $kpiDefs = [
        'vencido'    => ['Vencidos',           '#dc2626'],
        'critico'    => ['Até 30 dias',         '#ea580c'],
        'alerta'     => ['31–90 dias',           '#d97706'],
        'atencao'    => ['91–180 dias',          '#2563eb'],
        'ok'         => ['Mais de 180 dias',     '#16a34a'],
        'indefinido' => ['Sem data',             '#64748b'],
    ];
    foreach ($kpiDefs as $k => [$lbl, $cor]): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-top:4px solid <?= $cor ?>;border-radius:8px;padding:14px 16px;">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.05em;"><?= $lbl ?></div>
        <div style="font-size:1.7rem;font-weight:800;color:<?= $cor ?>;line-height:1.1;"><?= count($faixas[$k]['items']) ?></div>
        <div style="font-size:.72rem;color:#94a3b8;">instrumento(s)</div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Total geral -->
<div style="background:#002952;color:#fff;border-radius:8px;padding:14px 20px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
    <div>
        <span style="font-size:.75rem;text-transform:uppercase;opacity:.7;letter-spacing:.06em;">Total geral</span><br>
        <strong style="font-size:1.4rem;"><?= $totalGeral ?> instrumento<?= $totalGeral !== 1 ? 's' : '' ?></strong>
    </div>
    <div style="text-align:right">
        <span style="font-size:.75rem;text-transform:uppercase;opacity:.7;letter-spacing:.06em;">Valor total</span><br>
        <strong style="font-size:1.4rem;"><?= $brl($valorGeral) ?></strong>
    </div>
</div>

<!-- Tabelas por faixa -->
<?php foreach ($faixasOrdem as $fk):
    $faixa = $faixas[$fk];
    if (empty($faixa['items'])) continue;
    $cor = $faixa['cor'];
    $bg  = $faixa['bg'];
    $valorFaixa = array_sum(array_column($faixa['items'], 'valor_global_atualizado'));
?>
<div style="margin-bottom:28px;">
    <div style="background:<?= $cor ?>;color:#fff;border-radius:8px 8px 0 0;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-weight:700;font-size:.95rem;">
            <?php if ($fk === 'vencido'): ?><span style="margin-right:8px">⚠</span><?php endif; ?>
            <?= e($faixa['label']) ?>
            <span style="background:rgba(255,255,255,.22);border-radius:12px;padding:1px 10px;font-size:.78rem;margin-left:8px;"><?= count($faixa['items']) ?></span>
        </span>
        <span style="font-size:.82rem;opacity:.9;"><?= $brl($valorFaixa) ?></span>
    </div>
    <div style="overflow-x:auto;border:1px solid <?= $cor ?>33;border-top:none;border-radius:0 0 8px 8px;">
        <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
            <thead>
                <tr style="background:<?= $bg ?>;border-bottom:1px solid <?= $cor ?>33;">
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Contrato / Ata</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Fornecedor</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Secretaria</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Início</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Término</th>
                    <th style="padding:8px 10px;text-align:right;font-weight:700;color:#374151;">Dias</th>
                    <th style="padding:8px 10px;text-align:right;font-weight:700;color:#374151;">Valor</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;" class="d-print-none">Gestor</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($faixa['items'] as $i => $r):
                $d = $r['dias_restantes'];
                $diasLabel = match(true) {
                    !isset($d) || $r['data_termino'] === '' || $r['data_termino'] === null => '—',
                    $d < 0  => abs($d) . 'd atraso',
                    $d == 0 => 'Hoje',
                    default => $d . ' dias',
                };
                $diasCor = match($fk) {
                    'vencido'  => '#dc2626',
                    'critico'  => '#ea580c',
                    'alerta'   => '#d97706',
                    'atencao'  => '#2563eb',
                    'ok'       => '#16a34a',
                    default    => '#64748b',
                };
                $rowBg = $i % 2 === 0 ? '#fff' : $bg;
            ?>
                <tr style="background:<?= $rowBg ?>;border-bottom:1px solid #f1f5f9;">
                    <td style="padding:7px 10px;">
                        <?php if (strlen((string)($r['id'] ?? '')) > 0): ?>
                        <a href="<?= e(url('/contratos/' . $r['id'])) ?>" target="_blank"
                           style="font-weight:700;color:#002952;text-decoration:none;">
                            <?= e($r['chave']) ?>
                        </a>
                        <?php else: ?>
                            <strong><?= e($r['chave']) ?></strong>
                        <?php endif; ?>
                        <div style="font-size:10px;color:#94a3b8;"><?= e($r['tipo']) ?></div>
                    </td>
                    <td style="padding:7px 10px;max-width:200px;">
                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px;" title="<?= e($r['fornecedor_nome'] ?? '') ?>">
                            <?= e($r['fornecedor_nome'] ?? '—') ?>
                        </div>
                    </td>
                    <td style="padding:7px 10px;font-size:11px;color:#64748b;">
                        <?= e(mb_substr($r['setor_nome'] ?? '—', 0, 30)) ?>
                    </td>
                    <td style="padding:7px 10px;white-space:nowrap;font-size:11.5px;">
                        <?= $r['data_inicio'] ? date('d/m/Y', strtotime($r['data_inicio'])) : '—' ?>
                    </td>
                    <td style="padding:7px 10px;white-space:nowrap;font-size:11.5px;">
                        <?= $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '—' ?>
                    </td>
                    <td style="padding:7px 10px;text-align:right;white-space:nowrap;">
                        <span style="font-weight:700;color:<?= $diasCor ?>;font-size:12px;"><?= $diasLabel ?></span>
                        <?php if ($fk !== 'indefinido' && $fk !== 'vencido' && isset($d) && $d >= 0):
                            $pct = min(100, max(2, round(($d / 365) * 100)));
                            $barCor = $diasCor;
                        ?>
                        <div style="margin-top:3px;height:4px;background:#e2e8f0;border-radius:2px;">
                            <div style="width:<?= $pct ?>%;height:4px;background:<?= $barCor ?>;border-radius:2px;"></div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:7px 10px;text-align:right;white-space:nowrap;font-size:12px;">
                        <?= $brl((float)$r['valor_global_atualizado']) ?>
                    </td>
                    <td style="padding:7px 10px;font-size:11px;color:#64748b;" class="d-print-none">
                        <?= e($r['gestor'] ?? '—') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:<?= $bg ?>;border-top:2px solid <?= $cor ?>55;">
                    <td colspan="6" style="padding:8px 10px;font-weight:700;font-size:12px;color:#374151;">Subtotal</td>
                    <td style="padding:8px 10px;text-align:right;font-weight:700;color:<?= $cor ?>"><?= $brl($valorFaixa) ?></td>
                    <td class="d-print-none"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endforeach; ?>

<div style="margin-top:32px;padding-top:12px;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#94a3b8;">
    GestContratos TJPA · Gerado em <?= e($geradoEm) ?> · Coordenadoria de Convênios e Contratos / SEAD
</div>

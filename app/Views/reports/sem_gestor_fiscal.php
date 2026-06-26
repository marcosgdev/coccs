<?php $brl = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.'); ?>

<!-- Cabeçalho institucional -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 0 14px;border-bottom:2px solid #002952;margin-bottom:20px;">
    <img src="<?= e(asset('img/brasao-tjpa-azul.png')) ?>" alt="TJPA" style="height:56px;width:auto;">
    <div style="text-align:center;flex:1">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#64748b;">Poder Judiciário – Estado do Pará</div>
        <div style="font-weight:700;font-size:15px;color:#002952;">Contratos e ARPs sem Gestor ou Fiscal</div>
        <div style="font-size:12px;color:#64748b;">Coordenadoria de Convênios e Contratos · <?= e($geradoEm) ?></div>
    </div>
    <img src="<?= e(asset('img/logo-coccs.png')) ?>" alt="COCCS" style="height:52px;width:auto;">
</div>

<!-- Barra de ações -->
<div class="d-print-none mb-3 d-flex gap-2 align-items-center flex-wrap">
    <?php foreach (['ambos' => 'Sem gestor OU fiscal', 'sem_gestor' => 'Apenas sem gestor', 'sem_fiscal' => 'Apenas sem fiscal'] as $v => $l): ?>
        <a href="<?= e(url('/relatorios/sem-gestor-fiscal?filtro=' . $v)) ?>"
           class="btn btn-sm <?= $filtro === $v ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= e($l) ?>
        </a>
    <?php endforeach; ?>
    <a href="<?= e(url('/relatorios/sem-gestor-fiscal?filtro=' . $filtro . '&export=docx')) ?>"
       class="btn btn-sm btn-outline-dark ms-auto">
        <i class="bi bi-file-earmark-word me-1"></i>Exportar .docx
    </a>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimir
    </button>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px;">
    <div style="background:#fff;border:1px solid #e2e8f0;border-top:4px solid #dc2626;border-radius:8px;padding:14px 16px;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.05em;">Sem gestor E fiscal</div>
        <div style="font-size:1.8rem;font-weight:800;color:#dc2626;line-height:1.1;"><?= $totais['ambos'] ?></div>
        <div style="font-size:.7rem;color:#94a3b8;">instrumento(s)</div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-top:4px solid #ea580c;border-radius:8px;padding:14px 16px;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.05em;">Sem gestor</div>
        <div style="font-size:1.8rem;font-weight:800;color:#ea580c;line-height:1.1;"><?= $totais['sem_gestor'] ?></div>
        <div style="font-size:.7rem;color:#94a3b8;">instrumento(s)</div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-top:4px solid #d97706;border-radius:8px;padding:14px 16px;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:.05em;">Sem fiscal</div>
        <div style="font-size:1.8rem;font-weight:800;color:#d97706;line-height:1.1;"><?= $totais['sem_fiscal'] ?></div>
        <div style="font-size:.7rem;color:#94a3b8;">instrumento(s)</div>
    </div>
    <div style="background:#002952;border-radius:8px;padding:14px 16px;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:rgba(255,255,255,.65);letter-spacing:.05em;">Total listado</div>
        <div style="font-size:1.8rem;font-weight:800;color:#fff;line-height:1.1;"><?= $totais['total'] ?></div>
        <div style="font-size:.7rem;color:rgba(255,255,255,.5);"><?= $brl($totais['valor']) ?></div>
    </div>
</div>

<?php if (empty($rows)): ?>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:24px;text-align:center;color:#16a34a;">
    <i class="bi bi-check-circle-fill" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
    <strong>Nenhum instrumento encontrado para o filtro selecionado.</strong><br>
    <span style="font-size:.85rem;color:#64748b;">Todos os contratos e ARPs vigentes possuem gestor e fiscal indicados.</span>
</div>
<?php else: ?>

<!-- Tabela por secretaria -->
<?php foreach ($porSetor as $setor => $contratos): ?>
<div style="margin-bottom:24px;">
    <div style="background:#334155;color:#fff;border-radius:8px 8px 0 0;padding:9px 14px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-weight:700;font-size:.88rem;"><?= e(mb_strtoupper($setor)) ?></span>
        <span style="font-size:.75rem;background:rgba(255,255,255,.18);padding:1px 10px;border-radius:10px;"><?= count($contratos) ?> instrumento<?= count($contratos) > 1 ? 's' : '' ?></span>
    </div>
    <div style="overflow-x:auto;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
        <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;white-space:nowrap;">Contrato / Ata</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Fornecedor</th>
                    <th style="padding:8px 10px;text-align:center;font-weight:700;color:#374151;">Pendência</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Gestor</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Fiscal Demandante</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;">Fiscal Técnico</th>
                    <th style="padding:8px 10px;text-align:left;font-weight:700;color:#374151;white-space:nowrap;">Término</th>
                    <th style="padding:8px 10px;text-align:right;font-weight:700;color:#374151;">Valor</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contratos as $i => $r):
                [$badgeBg, $badgeTxt, $badgeLabel] = match($r['pendencia']) {
                    'ambos'      => ['#fef2f2', '#dc2626', 'Sem gestor e fiscal'],
                    'sem_gestor' => ['#fff7ed', '#ea580c', 'Sem gestor'],
                    default      => ['#fffbeb', '#d97706', 'Sem fiscal'],
                };
                $dias = $r['dias_restantes'];
                $diasCor = match(true) {
                    !isset($dias) || $r['data_termino'] === '' => '#94a3b8',
                    $dias < 0   => '#dc2626',
                    $dias <= 30 => '#ea580c',
                    $dias <= 90 => '#d97706',
                    default     => '#64748b',
                };
            ?>
                <tr style="background:<?= $i % 2 === 0 ? '#fff' : '#f8fafc' ?>;border-bottom:1px solid #f1f5f9;">
                    <td style="padding:7px 10px;white-space:nowrap;">
                        <a href="<?= e(url('/contratos/' . $r['id'])) ?>" target="_blank"
                           style="font-weight:700;color:#002952;text-decoration:none;"><?= e($r['chave']) ?></a>
                        <div style="font-size:10px;color:#94a3b8;"><?= e($r['tipo']) ?></div>
                    </td>
                    <td style="padding:7px 10px;max-width:200px;">
                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px;" title="<?= e($r['fornecedor_nome'] ?? '') ?>">
                            <?= e($r['fornecedor_nome'] ?? '—') ?>
                        </div>
                    </td>
                    <td style="padding:7px 10px;text-align:center;">
                        <span style="background:<?= $badgeBg ?>;color:<?= $badgeTxt ?>;font-weight:700;font-size:11px;padding:2px 8px;border-radius:10px;white-space:nowrap;">
                            <?= $badgeLabel ?>
                        </span>
                    </td>
                    <td style="padding:7px 10px;font-size:11.5px;">
                        <?php if (empty($r['gestor']) || in_array($r['gestor'], ['', 'sem indicação'])): ?>
                            <span style="color:#dc2626;font-weight:600;">⚠ Não indicado</span>
                        <?php else: ?>
                            <?= e($r['gestor']) ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding:7px 10px;font-size:11.5px;">
                        <?php if (empty($r['fiscal_demandante']) || in_array($r['fiscal_demandante'], ['', 'sem indicação'])): ?>
                            <span style="color:#d97706;">— não indicado</span>
                        <?php else: ?>
                            <?= e($r['fiscal_demandante']) ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding:7px 10px;font-size:11.5px;">
                        <?php if (empty($r['fiscal_tecnico']) || in_array($r['fiscal_tecnico'], ['', 'sem indicação'])): ?>
                            <span style="color:#d97706;">— não indicado</span>
                        <?php else: ?>
                            <?= e($r['fiscal_tecnico']) ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding:7px 10px;white-space:nowrap;font-size:11.5px;color:<?= $diasCor ?>;">
                        <?= $r['data_termino'] ? date('d/m/Y', strtotime($r['data_termino'])) : '—' ?>
                        <?php if (isset($dias)): ?>
                        <div style="font-size:10px;"><?= $dias < 0 ? abs($dias).'d vencido' : $dias.'d restantes' ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:7px 10px;text-align:right;white-space:nowrap;font-size:12px;">
                        <?= $brl((float)$r['valor_global_atualizado']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<div style="margin-top:32px;padding-top:12px;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#94a3b8;">
    GestContratos TJPA · Gerado em <?= e($geradoEm) ?> · Coordenadoria de Convênios e Contratos / SEAD
</div>

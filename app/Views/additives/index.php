<?php
$canWrite    = GestContratos\Core\Auth::canWrite();
$totCritico  = count(array_filter($contratos, fn($c) => $c['score_risco'] >= 70));
$totAtencao  = count(array_filter($contratos, fn($c) => $c['score_risco'] >= 40 && $c['score_risco'] < 70));
$totModerado = count(array_filter($contratos, fn($c) => $c['score_risco'] >= 15 && $c['score_risco'] < 40));
$totSaudavel = count(array_filter($contratos, fn($c) => $c['score_risco'] < 15));
$queueTotal  = array_sum(array_map('count', $queue));
$csrfToken   = GestContratos\Core\Csrf::token();
$statusLabels = [
    'aguardando'           => 'Aguardando',
    'iniciado'             => 'Iniciado',
    'em_revisao'           => 'Em revisão',
    'aguardando_assinatura'=> 'Aguard. assinatura',
    'concluido'            => 'Concluído',
];
$statusCls = [
    'aguardando'            => 'secondary',
    'iniciado'              => 'primary',
    'em_revisao'            => 'info',
    'aguardando_assinatura' => 'warning',
    'concluido'             => 'success',
];
?>

<ul class="nav nav-tabs mb-3" id="additiveTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-fila">
            <i class="bi bi-list-task me-1"></i>Fila de Ação
            <?php if ($queueTotal): ?><span class="tab-count"><?= $queueTotal ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-gantt">
            <i class="bi bi-bar-chart-steps me-1"></i>Linha do Tempo
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dna">
            <i class="bi bi-radar me-1"></i>Radar de Antecipação
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-controle">
            <i class="bi bi-shield-check me-1"></i>Controle
            <span class="tab-count"><?= count($contratos) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-saude">
            <i class="bi bi-heart-pulse me-1"></i>Índice de Saúde
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-calor">
            <i class="bi bi-calendar3 me-1"></i>Mapa de Calor
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-carga">
            <i class="bi bi-bar-chart-fill me-1"></i>Previsão de Carga
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scorecard">
            <i class="bi bi-person-badge me-1"></i>Scorecard
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simulador">
            <i class="bi bi-calculator me-1"></i>Simulador
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ════════════════════════════════════════════════════════════════════════
     FILA DE AÇÃO
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade show active" id="tab-fila">

    <div class="gc-card p-3 mb-3 d-flex align-items-center gap-3" style="background:var(--gc-bg-alt)">
        <div style="font-size:1.5rem">📋</div>
        <div>
            <div class="fw-bold small">Fila de Antecipação</div>
            <div class="small text-muted">Contratos ordenados por urgência de início de processo. Marque o status conforme a equipe avança.</div>
        </div>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <label class="small text-muted mb-0">Filtrar gestor:</label>
            <select class="form-select form-select-sm" id="fila-gestor-sel" style="width:auto">
                <option value="">Todos</option>
                <?php $gestoresUnicos = array_unique(array_column($contratos, 'gestor')); sort($gestoresUnicos); foreach ($gestoresUnicos as $g): if (!$g) continue; ?>
                <option><?= e($g) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php
    $grupos = [
        'urgente'   => ['label' => 'Urgente — processo deveria ter iniciado', 'cls' => 'danger',  'icon' => 'bi-exclamation-octagon-fill'],
        'semana'    => ['label' => 'Esta semana (próximos 7 dias)',           'cls' => 'warning', 'icon' => 'bi-clock-fill'],
        'mes'       => ['label' => 'Este mês (7–30 dias)',                    'cls' => 'info',    'icon' => 'bi-calendar-week'],
        'trimestre' => ['label' => 'Próximo trimestre (30–90 dias)',          'cls' => 'primary', 'icon' => 'bi-calendar-month'],
        'planejado' => ['label' => 'Planejado (> 90 dias)',                   'cls' => 'success', 'icon' => 'bi-calendar-check'],
    ];
    foreach ($grupos as $key => $grp):
        if (empty($queue[$key])) continue;
    ?>
    <div class="fila-grupo mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi <?= $grp['icon'] ?> text-<?= $grp['cls'] ?>"></i>
            <span class="fw-bold small text-<?= $grp['cls'] ?>"><?= $grp['label'] ?></span>
            <span class="badge bg-<?= $grp['cls'] ?> rounded-pill"><?= count($queue[$key]) ?></span>
        </div>
        <div class="gc-card p-0 overflow-hidden">
            <table class="table table-hover align-middle mb-0 fila-table">
                <thead class="table-light" style="font-size:.72rem">
                    <tr>
                        <th>Contrato</th>
                        <th>Fornecedor</th>
                        <th>Gestor</th>
                        <th class="text-center">Vencimento</th>
                        <th class="text-center">Iniciar processo</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($queue[$key] as $c):
                    $dias = $c['dias_para_iniciar'];
                ?>
                <tr class="fila-row" data-gestor="<?= e($c['gestor'] ?? '') ?>">
                    <td>
                        <a href="<?= e(url('/contratos/' . $c['id'])) ?>" class="fw-semibold text-decoration-none small"><?= e($c['chave']) ?></a>
                        <span class="badge bg-<?= $c['score_cls'] ?> ms-1" style="font-size:.6rem"><?= $c['score_risco'] ?></span>
                    </td>
                    <td class="small text-truncate" style="max-width:160px"><?= e($c['fornecedor_nome'] ?? '—') ?></td>
                    <td class="small text-muted text-truncate" style="max-width:120px"><?= e($c['gestor'] ?? '—') ?></td>
                    <td class="text-center small">
                        <div class="fw-semibold"><?= e(date_br($c['data_termino'])) ?></div>
                        <div class="text-muted" style="font-size:.65rem"><?= $c['dias_restantes'] !== null ? $c['dias_restantes'] . 'd restantes' : '—' ?></div>
                    </td>
                    <td class="text-center small">
                        <?php if ($dias !== null): ?>
                        <div class="fw-semibold <?= $dias < 0 ? 'text-danger' : '' ?>"><?= e(date_br($c['data_inicio_proc'])) ?></div>
                        <div class="<?= $dias < 0 ? 'text-danger' : 'text-muted' ?>" style="font-size:.65rem">
                            <?= $dias < 0 ? abs($dias) . 'd atrasado' : $dias . 'd restantes' ?>
                        </div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="fila-status-wrap" data-id="<?= $c['id'] ?>">
                            <span class="badge bg-<?= $statusCls[$c['flag_status']] ?> fila-status-badge">
                                <?= $statusLabels[$c['flag_status']] ?>
                            </span>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <?php foreach ($statusLabels as $sv => $sl): ?>
                                <li>
                                    <button class="dropdown-item small fila-status-btn <?= $c['flag_status'] === $sv ? 'active' : '' ?>"
                                            data-id="<?= $c['id'] ?>" data-status="<?= $sv ?>">
                                        <span class="badge bg-<?= $statusCls[$sv] ?> me-1" style="font-size:.6rem">·</span>
                                        <?= $sl ?>
                                    </button>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (!$queueTotal): ?>
    <div class="gc-card p-5 text-center text-muted">
        <i class="bi bi-check2-circle" style="font-size:2.5rem;color:#198754;opacity:.5"></i>
        <p class="mt-3 mb-0 fw-semibold">Nenhum contrato pendente na fila de antecipação.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     LINHA DO TEMPO (GANTT)
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-gantt">
    <div class="gc-card p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="fw-bold">Linha do Tempo — Próximos 12 meses</div>
                <div class="small text-muted">Cada barra representa o tempo restante de um contrato. Marcador <span class="gantt-marker-legend">◆</span> = data para iniciar processo.</div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm" id="gantt-gestor-sel" style="width:auto">
                    <option value="">Todos os gestores</option>
                    <?php foreach ($gestoresUnicos as $g): if (!$g) continue; ?>
                    <option><?= e($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Cabeçalho de meses -->
        <div class="gantt-wrap">
            <div class="gantt-header">
                <div class="gantt-label-col"></div>
                <div class="gantt-track-col">
                    <div class="gantt-months">
                        <?php foreach ($ganttMeses as $m): ?>
                        <div class="gantt-month"><?= $m ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Linhas de grade -->
            <div class="gantt-grid-bg">
                <?php foreach ($ganttMeses as $i => $m): ?>
                <div class="gantt-grid-col <?= $i === 0 ? 'gantt-grid-now' : '' ?>" style="left:<?= round($i/12*100,2) ?>%"></div>
                <?php endforeach; ?>
            </div>

            <!-- Contratos -->
            <div class="gantt-rows" id="gantt-rows">
            <?php foreach ($ganttContratos as $c):
                $barColor = match($c['score_cls']) {
                    'danger'  => '#dc3545',
                    'warning' => '#fd7e14',
                    'info'    => '#0dcaf0',
                    default   => '#198754',
                };
            ?>
            <div class="gantt-row" data-gestor="<?= e($c['gestor'] ?? '') ?>">
                <div class="gantt-label-col">
                    <a href="<?= e(url('/contratos/' . $c['id'])) ?>" class="text-decoration-none fw-semibold small gantt-chave"><?= e($c['chave']) ?></a>
                    <div class="gantt-fornecedor"><?= e(substr($c['fornecedor_nome'] ?? '', 0, 28)) ?></div>
                </div>
                <div class="gantt-track-col">
                    <div class="gantt-bar-wrap">
                        <div class="gantt-bar"
                             style="width:<?= $c['gantt_width'] ?>%;background:<?= $barColor ?>"
                             title="<?= e($c['chave']) ?> — Vence <?= e(date_br($c['data_termino'])) ?> (<?= $c['dias_restantes'] ?>d)">
                            <?php if ($c['gantt_proc_pct'] !== null && $c['gantt_proc_pct'] < 98): ?>
                            <div class="gantt-proc-marker" style="left:<?= $c['gantt_proc_pct'] / ($c['gantt_width'] / 100) ?>%" title="Iniciar processo: <?= e(date_br($c['data_inicio_proc'])) ?>">◆</div>
                            <?php endif; ?>
                        </div>
                        <div class="gantt-bar-label"><?= $c['dias_restantes'] !== null ? $c['dias_restantes'] . 'd' : '' ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-3 d-flex gap-4 small text-muted">
            <?php foreach (['danger' => 'Crítico','warning' => 'Atenção','info' => 'Moderado','success' => 'Saudável'] as $cls => $lbl): ?>
            <span><span class="gantt-legend-dot" style="background:<?= ['danger'=>'#dc3545','warning'=>'#fd7e14','info'=>'#0dcaf0','success'=>'#198754'][$cls] ?>"></span><?= $lbl ?></span>
            <?php endforeach; ?>
            <span><span class="gantt-marker-legend">◆</span> Iniciar processo</span>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     RADAR DE ANTECIPAÇÃO + POSIÇÃO RELATIVA
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-dna">

    <?php if (!$dnaStats): ?>
    <div class="gc-card p-5 text-center text-muted">
        <i class="bi bi-bar-chart-steps" style="font-size:2.5rem;opacity:.3"></i>
        <p class="mt-3 mb-0">Dados insuficientes. Necessário ≥ 2 prorrogações com datas registradas.</p>
    </div>
    <?php else: ?>

    <?php
    $posAdiantado = count(array_filter($dnaStats, fn($d) => !$d['fora_janela'] && ($d['posicao_relativa'] ?? 0) > 15));
    $posNoPrazo   = count(array_filter($dnaStats, fn($d) => $d['fora_janela'] || abs($d['posicao_relativa'] ?? 0) <= 15));
    $posAtrasado  = count(array_filter($dnaStats, fn($d) => !$d['fora_janela'] && ($d['posicao_relativa'] ?? 0) < -15));
    ?>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="dna-resumo-card dna-resumo-ok" data-filtro="adiantado" style="cursor:pointer">
                <div class="dna-resumo-num"><?= $posAdiantado ?></div>
                <div class="dna-resumo-titulo">Adiantados</div>
                <div class="dna-resumo-desc">Iniciaram o processo antes do seu próprio padrão histórico</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dna-resumo-card dna-resumo-neutro" data-filtro="padrao" style="cursor:pointer">
                <div class="dna-resumo-num"><?= $posNoPrazo ?></div>
                <div class="dna-resumo-titulo">No padrão</div>
                <div class="dna-resumo-desc">Dentro de ±15 dias do comportamento histórico esperado</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dna-resumo-card dna-resumo-alerta" data-filtro="atrasado" style="cursor:pointer">
                <div class="dna-resumo-num"><?= $posAtrasado ?></div>
                <div class="dna-resumo-titulo">Atrasados</div>
                <div class="dna-resumo-desc">O processo deveria ter iniciado há mais dias do que o habitual</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="gc-card p-3 mb-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="small text-muted fw-semibold">Filtrar:</span>
        <div class="btn-group btn-group-sm" id="dna-filtro-grupo">
            <button class="btn btn-outline-secondary active" data-filtro="">Todos</button>
            <button class="btn btn-outline-danger"   data-filtro="atrasado">Atrasados</button>
            <button class="btn btn-outline-secondary" data-filtro="padrao">No padrão</button>
            <button class="btn btn-outline-success"  data-filtro="adiantado">Adiantados</button>
        </div>
        <span class="ms-auto small text-muted" id="dna-count"><?= count($dnaStats) ?> contratos</span>
    </div>

    <!-- Tabela redesenhada -->
    <div class="gc-card p-0 overflow-hidden">
        <table class="table table-hover align-middle mb-0" id="dna-tabela">
            <thead class="table-light" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em">
                <tr>
                    <th style="min-width:200px">Contrato</th>
                    <th style="min-width:200px">
                        Comportamento histórico
                        <div style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.65rem;color:var(--gc-muted)">como este contrato costuma ser renovado</div>
                    </th>
                    <th class="text-center" style="min-width:110px">Vence em</th>
                    <th style="min-width:240px">
                        Você está…
                        <div style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.65rem;color:var(--gc-muted)">comparado com o seu próprio histórico</div>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dnaStats as $d):
                $pos        = $d['posicao_relativa'];
                $dr         = $d['dias_restantes'];
                $riscoCls   = $d['risco_tardio'] ? 'danger' : ($d['avg_lead'] < 60 ? 'warning' : 'success');
                $trendIcon  = $d['trend'] > 5  ? '📈' : ($d['trend'] < -5  ? '📉' : '➡️');
                $trendLabel = $d['trend'] > 5  ? 'Melhorando ciclo a ciclo' : ($d['trend'] < -5 ? 'Renovando cada vez mais tarde' : 'Comportamento estável');

                if ($pos === null) {
                    $posCls = 'secondary'; $posVerb = '—'; $posDetalhe = 'Sem dados suficientes'; $filtroAttr = '';
                } elseif ($d['fora_janela']) {
                    // Ainda muito longe do momento de agir — não é "adiantado", é "fora da janela"
                    $diasParaAgir = $d['dias_restantes'] - $d['avg_lead'];
                    $posCls = 'secondary'; $filtroAttr = 'padrao';
                    $posVerb = 'Sem ação por enquanto';
                    $posDetalhe = 'Iniciar processo em ~' . $diasParaAgir . ' dias (em ' . e(date_br($d['ideal_start'])) . ')';
                } elseif ($pos > 15) {
                    $posCls = 'success'; $posVerb = '+' . $pos . ' dias adiantado'; $filtroAttr = 'adiantado';
                    $posDetalhe = 'Processo iniciado antes do habitual';
                } elseif ($pos < -15) {
                    $posCls = 'danger'; $posVerb = abs($pos) . ' dias atrasado'; $filtroAttr = 'atrasado';
                    $posDetalhe = 'Deveria ter iniciado há ' . abs($pos) . ' dias';
                } else {
                    $posCls = 'secondary'; $posVerb = ($pos >= 0 ? '+' : '') . $pos . ' dias'; $filtroAttr = 'padrao';
                    $posDetalhe = 'Dentro do comportamento esperado';
                }

                $alertaRow = $d['alerta_dna'] || ($pos !== null && $pos < -15 && !$d['fora_janela']);
                $barEsq    = (!$d['fora_janela'] && $pos !== null && $pos < 0)  ? min(50, round(abs($pos)/90*50)) : 0;
                $barDir    = (!$d['fora_janela'] && $pos !== null && $pos >= 0) ? min(50, round($pos/90*50))      : 0;
            ?>
            <tr class="dna-row <?= $alertaRow ? 'table-warning' : '' ?>" data-filtro="<?= $filtroAttr ?>">

                <!-- Coluna 1: Contrato -->
                <td>
                    <a href="<?= e(url('/contratos/' . $d['contrato_id'])) ?>" class="fw-bold text-decoration-none" style="font-size:.85rem"><?= e($d['chave']) ?></a>
                    <div class="text-truncate text-muted" style="font-size:.72rem;max-width:190px"><?= e($d['fornecedor']) ?></div>
                    <div class="mt-1 d-flex flex-wrap gap-1">
                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:.62rem"><?= $d['qtd'] - 1 ?>× renovado</span>
                        <?php if ($d['risco_tardio']): ?>
                        <span class="badge bg-danger-subtle text-danger" style="font-size:.62rem">histórico tardio</span>
                        <?php endif; ?>
                    </div>
                </td>

                <!-- Coluna 2: Comportamento histórico -->
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <!-- Sparkline -->
                        <div>
                            <div class="dna-spark">
                                <?php
                                $absMax = max(1, max(array_map('abs', $d['lead_times'])));
                                foreach ($d['lead_times'] as $lt):
                                    $h  = max(4, (int) round(abs($lt) / $absMax * 32));
                                    $bc = $lt < 0 ? '#dc3545' : ($lt < 30 ? '#ffc107' : '#198754');
                                ?>
                                <div class="dna-bar" style="height:<?= $h ?>px;background:<?= $bc ?>" title="<?= $lt ?>d de antecedência"></div>
                                <?php endforeach; ?>
                            </div>
                            <div style="font-size:.58rem;color:var(--gc-muted);text-align:center;margin-top:2px">cada renovação</div>
                        </div>
                        <!-- Texto explicativo -->
                        <div>
                            <div class="fw-semibold text-<?= $riscoCls ?>" style="font-size:.9rem"><?= $d['avg_lead'] ?> dias</div>
                            <div style="font-size:.68rem;color:var(--gc-muted);line-height:1.3">
                                antecedência média<br>
                                <span style="font-size:.62rem"><?= $d['min_lead'] ?>d mín · <?= $d['max_lead'] ?>d máx</span>
                            </div>
                            <div style="font-size:.65rem;margin-top:4px" title="<?= $trendLabel ?>"><?= $trendIcon ?> <?= $trendLabel ?></div>
                        </div>
                    </div>
                </td>

                <!-- Coluna 3: Vencimento -->
                <td class="text-center">
                    <div class="fw-semibold" style="font-size:.82rem"><?= e(date_br($d['termino_atual'])) ?></div>
                    <div style="font-size:.7rem" class="<?= $dr === null ? 'text-muted' : ($dr < 0 ? 'text-secondary' : ($dr < 30 ? 'text-danger fw-semibold' : ($dr < 90 ? 'text-warning fw-semibold' : 'text-success'))) ?>">
                        <?php if ($dr === null): ?>—
                        <?php elseif ($dr < 0): ?>Vencido
                        <?php elseif ($dr === 0): ?>Hoje
                        <?php else: ?><?= $dr ?>d restantes<?php endif; ?>
                    </div>
                    <?php if ($d['ideal_start']): ?>
                    <div class="mt-1" style="font-size:.62rem;color:var(--gc-muted)">
                        Iniciar: <span class="fw-semibold <?= $d['alerta_dna'] ? 'text-danger' : '' ?>"><?= e(date_br($d['ideal_start'])) ?></span>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Coluna 4: Posição vs. padrão (hero) -->
                <td>
                    <?php if ($pos !== null): ?>
                    <div class="fw-bold text-<?= $posCls ?>" style="font-size:1.05rem;line-height:1.2"><?= $posVerb ?></div>
                    <div style="font-size:.7rem;color:var(--gc-muted);margin-bottom:6px"><?= $posDetalhe ?></div>
                    <!-- Barra bidirecional -->
                    <div class="dna-pos-bar-wrap">
                        <div class="dna-pos-bar-track">
                            <div class="dna-pos-bar-center"></div>
                            <?php if ($barEsq > 0): ?>
                            <div class="dna-pos-bar-fill bg-danger" style="right:50%;width:<?= $barEsq ?>%"></div>
                            <?php elseif ($barDir > 0): ?>
                            <div class="dna-pos-bar-fill bg-success" style="left:50%;width:<?= $barDir ?>%"></div>
                            <?php endif; ?>
                        </div>
                        <div class="dna-pos-bar-labels">
                            <span>← Atrasado</span>
                            <span>Adiantado →</span>
                        </div>
                    </div>
                    <?php else: ?>
                    <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>

                <!-- Ação -->
                <td>
                    <a href="<?= e(url('/contratos/' . $d['contrato_id'])) ?>" class="btn btn-sm btn-outline-primary" title="Ver contrato">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <script>
    (function(){
        // Filtro pelos cards e botões
        function aplicarFiltro(f) {
            let vis = 0;
            document.querySelectorAll('.dna-row').forEach(r => {
                const ok = !f || r.dataset.filtro === f;
                r.style.display = ok ? '' : 'none';
                if (ok) vis++;
            });
            const cnt = document.getElementById('dna-count');
            if (cnt) cnt.textContent = vis + ' contrato' + (vis !== 1 ? 's' : '');

            // Destaca card ativo
            document.querySelectorAll('.dna-resumo-card').forEach(c => c.classList.toggle('dna-resumo-ativo', c.dataset.filtro === f));

            // Destaca botão ativo
            document.querySelectorAll('#dna-filtro-grupo button').forEach(b => {
                b.classList.toggle('active', b.dataset.filtro === f);
            });
        }

        document.querySelectorAll('.dna-resumo-card').forEach(c =>
            c.addEventListener('click', () => aplicarFiltro(c.dataset.filtro))
        );
        document.querySelectorAll('#dna-filtro-grupo button').forEach(b =>
            b.addEventListener('click', () => aplicarFiltro(b.dataset.filtro))
        );
    })();
    </script>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     CONTROLE
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-controle">
    <div class="row g-3 mb-4">
        <?php foreach ([['Crítico','danger','exclamation-triangle-fill',$totCritico,'≥ 70'],['Atenção','warning','clock-fill',$totAtencao,'40–69'],['Moderado','info','info-circle-fill',$totModerado,'15–39'],['Saudável','success','check-circle-fill',$totSaudavel,'< 15']] as [$lbl,$cls,$ico,$n,$sub]): ?>
        <div class="col-6 col-md-3">
            <div class="prazo-card prazo-card-<?= $cls === 'info' ? '' : $cls ?> h-100" <?= $cls==='info' ? 'style="border-color:#0dcaf0;background:rgba(13,202,240,.06)"' : '' ?>>
                <div class="prazo-card-icon" <?= $cls==='info' ? 'style="color:#0dcaf0"' : '' ?>><i class="bi bi-<?= $ico ?>"></i></div>
                <div class="prazo-card-body">
                    <div class="prazo-card-label"><?= $lbl ?></div>
                    <div class="prazo-card-value" style="font-size:1.8rem"><?= $n ?></div>
                    <div class="prazo-card-sub">score <?= $sub ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="gc-card p-3 mb-3">
        <div class="row g-2">
            <div class="col-md-5"><input type="text" class="form-control form-control-sm" id="filtro-contrato" placeholder="Buscar contrato ou fornecedor…"></div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="filtro-risco">
                    <option value="">Todos os riscos</option>
                    <option>Crítico</option><option>Atenção</option><option>Moderado</option><option>Saudável</option>
                </select>
            </div>
            <div class="col-md-4 text-end pt-1"><span class="small text-muted" id="filtro-count"><?= count($contratos) ?> contratos</span></div>
        </div>
    </div>
    <div class="gc-card p-0 overflow-hidden">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" style="font-size:.72rem">
                <tr><th style="width:80px">Score</th><th>Contrato</th><th>Fornecedor</th><th>Vencimento</th><th>Prazo legal</th><th class="text-center">Prorrog.</th><th>Processo</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($contratos as $c):
                $dr = $c['dias_restantes'];
                $sem     = $dr===null?'secondary':($dr<0?'secondary':($dr<30?'danger':($dr<90?'warning':'success')));
                $semIcon = $dr===null?'bi-dash-circle':($dr<0?'bi-x-circle-fill':($dr<30?'bi-exclamation-triangle-fill':($dr<90?'bi-clock-fill':'bi-check-circle-fill')));
            ?>
            <tr class="prorrog-row" data-contrato="<?= e(strtolower($c['chave'].' '.$c['fornecedor_nome'])) ?>" data-risco="<?= e($c['score_label']) ?>">
                <td><div class="d-flex flex-column align-items-center gap-1"><span class="badge bg-<?= $c['score_cls'] ?>" style="font-size:.8rem;min-width:38px"><?= $c['score_risco'] ?></span><span class="text-muted" style="font-size:.6rem"><?= $c['score_label'] ?></span></div></td>
                <td><a href="<?= e(url('/contratos/'.$c['id'])) ?>" class="fw-semibold text-decoration-none small"><?= e($c['chave']) ?></a><?php if($c['situacao']):?><br><span class="badge <?= e(badge_class($c['situacao'])) ?>" style="font-size:.58rem"><?= e($c['situacao']) ?></span><?php endif;?></td>
                <td><span class="d-block text-truncate small" style="max-width:180px"><?= e($c['fornecedor_nome']??'—') ?></span><?php if($c['setor_nome']):?><span class="small text-muted"><?= e($c['setor_nome']) ?></span><?php endif;?></td>
                <td><div class="d-flex align-items-center gap-1"><i class="bi <?= $semIcon ?> text-<?= $sem ?>"></i><div><div class="fw-semibold" style="font-size:.8rem"><?= e(date_br($c['data_termino'])) ?></div><div class="text-muted" style="font-size:.65rem"><?= $dr===null?'—':($dr<0?'Vencido há '.abs($dr).'d':($dr===0?'Hoje':$dr.'d')) ?></div></div></div></td>
                <td style="min-width:120px"><?php if($c['pct_legal']!==null):?><div class="small mb-1"><?= $c['meses_totais'] ?>/<?= $limiteMax ?>m</div><div class="prazo-legal-bar"><div class="prazo-legal-fill prazo-fill-<?= $c['score_cls'] ?>" style="width:<?= $c['pct_legal'] ?>%"></div></div><div class="text-muted mt-1" style="font-size:.65rem"><?= $c['pct_legal'] ?>%</div><?php else:?>—<?php endif;?></td>
                <td class="text-center"><span class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-bold" style="font-size:.78rem"><?= $c['qtd_prorrogacoes'] ?>×</span><?php if($c['ultima_prorrogacao']):?><div class="text-muted" style="font-size:.6rem"><?= e(date_br($c['ultima_prorrogacao'])) ?></div><?php endif;?></td>
                <td><?php if($c['data_inicio_proc']):?><div class="small <?= ($c['lead_venceu']&&$dr>0)?'text-danger fw-semibold':'text-muted' ?>"><?= ($c['lead_venceu']&&$dr>0)?'⚠ Atrasado':'Até' ?></div><div class="small fw-semibold"><?= e(date_br($c['data_inicio_proc'])) ?></div><?php else:?>—<?php endif;?></td>
                <td class="text-end"><a href="<?= e(url('/contratos/'.$c['id'])) ?>#tab-timeline" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-right"></i></a></td>
            </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     ÍNDICE DE SAÚDE
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-saude">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="gc-card p-4 h-100 text-center">
                <div class="prazo-card-label mb-3" style="font-size:.72rem">ÍNDICE DE SAÚDE DO PORTFÓLIO</div>
                <div class="position-relative d-inline-block mb-3">
                    <canvas id="gaugeChart" width="220" height="220"></canvas>
                    <div class="position-absolute top-50 start-50 translate-middle text-center" style="margin-top:20px">
                        <div style="font-size:2.6rem;font-weight:800;line-height:1" class="text-<?= $healthCls ?>"><?= $portfolioHealth ?></div>
                        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase" class="text-<?= $healthCls ?>"><?= $healthLabel ?></div>
                    </div>
                </div>
                <div class="small text-muted">Score médio de risco: <strong><?= $mediaScore ?>/100</strong></div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="gc-card p-4 h-100">
                <div class="prazo-card-label mb-3" style="font-size:.72rem">DISTRIBUIÇÃO POR FAIXA</div>
                <?php foreach ([['Crítico','danger',$totCritico],['Atenção','warning',$totAtencao],['Moderado','info',$totModerado],['Saudável','success',$totSaudavel]] as [$l,$cl,$n]):
                    $pct = count($contratos) ? round($n/count($contratos)*100) : 0; ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1 small"><span class="fw-semibold"><?= $l ?></span><span class="text-muted"><?= $n ?> · <?= $pct ?>%</span></div>
                    <div class="progress" style="height:9px;border-radius:6px"><div class="progress-bar bg-<?= $cl ?>" style="width:<?= $pct ?>%"></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     MAPA DE CALOR
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-calor">

    <?php
    // Totalizadores do mapa de calor
    $heatTotalContratos = array_sum(array_column($heatData, 'total'));
    $heatTotalValor     = array_sum(array_column($heatData, 'valor'));
    $heatMesMax         = array_keys($heatData, max($heatData))[0] ?? null;
    ?>

    <!-- Resumo -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="gc-card p-3 text-center">
                <div class="prazo-card-label mb-1">Contratos vencendo</div>
                <div class="fw-bold" style="font-size:1.5rem"><?= $heatTotalContratos ?></div>
                <div class="small text-muted">próximos 24 meses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="gc-card p-3 text-center">
                <div class="prazo-card-label mb-1">Valor em risco</div>
                <div class="fw-bold text-danger" style="font-size:1.3rem"><?= 'R$ ' . number_format($heatTotalValor/1e6, 1, ',', '.') . ' mi' ?></div>
                <div class="small text-muted">soma dos contratos</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="gc-card p-3 text-center">
                <div class="prazo-card-label mb-1">Mês mais crítico</div>
                <?php if ($heatMesMax): [$hmy,$hmm] = explode('-',$heatMesMax); ?>
                <div class="fw-bold text-danger" style="font-size:1.3rem"><?= date('M', mktime(0,0,0,(int)$hmm,1)) ?>/<?= $hmy ?></div>
                <div class="small text-muted"><?= $heatData[$heatMesMax]['total'] ?> contratos</div>
                <?php else: ?><div class="fw-bold">—</div><?php endif; ?>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="gc-card p-3 text-center">
                <div class="prazo-card-label mb-1">Média mensal</div>
                <div class="fw-bold" style="font-size:1.5rem"><?= $heatTotalContratos > 0 ? round($heatTotalContratos / 24, 1) : 0 ?></div>
                <div class="small text-muted">contratos/mês</div>
            </div>
        </div>
    </div>

    <!-- Grade -->
    <div class="gc-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="fw-bold">Mapa de Calor — próximos 24 meses</div>
                <div class="small text-muted">Clique em um mês para ver os contratos em detalhe</div>
            </div>
            <div class="d-flex align-items-center gap-2 small text-muted">
                <span>Poucos</span>
                <div style="display:flex;gap:3px">
                    <?php foreach (['heat-1','heat-2','heat-3','heat-4'] as $hc): ?>
                    <div class="<?= $hc ?>" style="width:18px;height:18px;border-radius:3px"></div>
                    <?php endforeach; ?>
                </div>
                <span>Muitos</span>
            </div>
        </div>

        <div class="heat-grid" id="heat-grid">
            <?php foreach ($heatData as $mes => $data):
                $qtd  = $data['total'];
                $val  = $data['valor'];
                $pct  = $heatMax > 0 ? round($qtd / $heatMax * 100) : 0;
                $cls  = $pct===0?'heat-0':($pct<=25?'heat-1':($pct<=50?'heat-2':($pct<=75?'heat-3':'heat-4')));
                [$ano,$mm] = explode('-', $mes);
                $nomeMes = date('M', mktime(0,0,0,(int)$mm,1));
                $valFmt  = $val > 0 ? 'R$ '.number_format($val/1e6,1,',','.').'mi' : '';
            ?>
            <div class="heat-cell <?= $cls ?> <?= $qtd>0?'heat-clickable':'' ?>"
                 data-mes="<?= $mes ?>"
                 title="<?= $nomeMes ?>/<?= $ano ?>: <?= $qtd ?> contrato<?= $qtd!==1?'s':'' ?>">
                <div class="heat-cell-label"><?= $nomeMes ?><br><span><?= $ano ?></span></div>
                <div class="heat-cell-num"><?= $qtd ?: '' ?></div>
                <?php if ($valFmt): ?><div class="heat-cell-val"><?= $valFmt ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Painel de detalhe do mês selecionado -->
    <div id="heat-detail-panel" class="gc-card p-4 mt-3" style="display:none">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="fw-bold" id="heat-detail-titulo">—</div>
                <div class="small text-muted" id="heat-detail-sub">—</div>
            </div>
            <button class="btn btn-sm btn-outline-secondary" id="heat-detail-fechar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="heat-detail-table">
                <thead class="table-light">
                    <tr>
                        <th>Contrato</th>
                        <th>Fornecedor</th>
                        <th>Gestor</th>
                        <th class="text-end">Valor</th>
                        <th class="text-center">Vencimento</th>
                        <th class="text-center">Dias restantes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="heat-detail-tbody"></tbody>
            </table>
        </div>
    </div>

    <?php
    // Serializa dados de detalhe para JS
    $heatDetailJs = [];
    foreach ($heatDetail as $mes => $contratos_mes) {
        foreach ($contratos_mes as $cm) {
            $heatDetailJs[$mes][] = [
                'id'       => $cm['id'],
                'chave'    => $cm['chave'],
                'fornecedor'=> mb_substr($cm['fornecedor_nome'] ?? '—', 0, 40),
                'gestor'   => $cm['gestor'] ?? '—',
                'situacao' => $cm['situacao'] ?? '',
                'termino'  => $cm['data_termino'],
                'valor'    => (float)($cm['valor_global_atualizado'] ?? 0),
                'dias'     => (int)($cm['dias_restantes'] ?? 0),
            ];
        }
    }
    ?>
    <script>
    (function(){
        const detail = <?= json_encode($heatDetailJs, JSON_UNESCAPED_UNICODE) ?>;
        const panel  = document.getElementById('heat-detail-panel');
        const tbody  = document.getElementById('heat-detail-tbody');
        const titulo = document.getElementById('heat-detail-titulo');
        const sub    = document.getElementById('heat-detail-sub');

        function fmtBR(iso){ if(!iso)return'—'; const[y,m,d]=iso.split('-'); return d+'/'+m+'/'+y; }
        function fmtMoney(v){ return'R$ '+v.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}); }
        function diaCls(d){ return d<0?'text-secondary':d<30?'text-danger fw-semibold':d<90?'text-warning fw-semibold':'text-success'; }

        document.getElementById('heat-grid').addEventListener('click', function(e){
            const cell = e.target.closest('.heat-clickable');
            if(!cell) return;
            const mes    = cell.dataset.mes;
            const rows   = detail[mes] || [];
            const [ano,mm] = mes.split('-');
            const nomeMes = new Date(ano,mm-1,1).toLocaleString('pt-BR',{month:'long'});

            // Destaca célula selecionada
            document.querySelectorAll('.heat-cell').forEach(c=>c.classList.remove('heat-selected'));
            cell.classList.add('heat-selected');

            titulo.textContent = nomeMes.charAt(0).toUpperCase()+nomeMes.slice(1)+' de '+ano;
            const totalValor = rows.reduce((s,r)=>s+r.valor,0);
            sub.textContent  = rows.length+' contrato'+(rows.length!==1?'s':'')+' · '+fmtMoney(totalValor)+' em valor';

            tbody.innerHTML = rows.map(r=>`
                <tr>
                    <td><a href="/contratos/${r.id}" class="fw-semibold text-decoration-none">${r.chave}</a></td>
                    <td class="text-truncate" style="max-width:180px">${r.fornecedor}</td>
                    <td class="text-muted">${r.gestor}</td>
                    <td class="text-end">${fmtMoney(r.valor)}</td>
                    <td class="text-center">${fmtBR(r.termino)}</td>
                    <td class="text-center"><span class="${diaCls(r.dias)}">${r.dias<0?'Vencido':r.dias+'d'}</span></td>
                    <td><a href="/contratos/${r.id}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-right"></i></a></td>
                </tr>
            `).join('');

            panel.style.display = '';
            panel.scrollIntoView({behavior:'smooth', block:'nearest'});
        });

        document.getElementById('heat-detail-fechar').addEventListener('click', function(){
            panel.style.display = 'none';
            document.querySelectorAll('.heat-cell').forEach(c=>c.classList.remove('heat-selected'));
        });
    })();
    </script>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     PREVISÃO DE CARGA
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-carga">
    <div class="gc-card p-4">
        <div class="fw-bold mb-1">Previsão de Carga de Trabalho</div>
        <div class="small text-muted mb-4">Contratos que entrarão na janela de ação (≤ 60 dias) por mês — próximos 12 meses</div>
        <?php if ($cargaMeses !== '[]'): ?>
        <canvas id="cargaChart" height="100"></canvas>
        <div class="mt-3 small text-muted"><i class="bi bi-lightbulb me-1 text-warning"></i>Meses com pico exigem planejamento antecipado.</div>
        <?php else: ?>
        <div class="text-center text-muted py-5"><i class="bi bi-bar-chart" style="font-size:2rem"></i><p class="mt-2">Nenhum contrato na janela.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     SCORECARD
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-scorecard">
    <?php if (!$gestores): ?>
    <div class="gc-card p-4 text-center text-muted">Nenhum gestor identificado.</div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($gestores as $g): ?>
        <div class="col-md-6 col-lg-4">
            <div class="gc-card p-0 overflow-hidden h-100">
                <div class="d-flex align-items-center gap-3 p-3" style="border-bottom:1px solid var(--gc-border)">
                    <div class="sc-avatar bg-<?= $g['score_cls'] ?>-subtle text-<?= $g['score_cls'] ?>"><?= mb_strtoupper(mb_substr(trim($g['nome']),0,2)) ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="fw-semibold small text-truncate"><?= e($g['nome']) ?></div>
                        <div class="small text-muted"><?= $g['total'] ?> contratos</div>
                    </div>
                    <span class="badge bg-<?= $g['score_cls'] ?>"><?= $g['score_medio'] ?></span>
                </div>
                <div class="p-3">
                    <div class="row g-2 text-center mb-2">
                        <div class="col-4"><div class="text-danger fw-bold"><?= $g['critico'] ?></div><div style="font-size:.62rem" class="text-muted">Crítico</div></div>
                        <div class="col-4"><div class="text-warning fw-bold"><?= $g['atencao'] ?></div><div style="font-size:.62rem" class="text-muted">Atenção</div></div>
                        <div class="col-4"><div class="text-success fw-bold"><?= $g['saudavel'] ?></div><div style="font-size:.62rem" class="text-muted">Saudável</div></div>
                    </div>
                    <?php $criticos = array_slice(array_filter($g['contratos'], fn($c)=>$c['score_risco']>=40), 0, 3);
                    foreach ($criticos as $cc): ?>
                    <div class="d-flex justify-content-between py-1 small" style="border-top:1px solid var(--gc-border)">
                        <a href="<?= e(url('/contratos/'.$cc['id'])) ?>" class="text-decoration-none fw-semibold"><?= e($cc['chave']) ?></a>
                        <span class="badge bg-<?= $cc['score_cls'] ?>" style="font-size:.62rem"><?= $cc['score_risco'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     SIMULADOR
════════════════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="tab-simulador">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="gc-card p-4">
                <div class="fw-bold mb-1">Simulador de Prorrogação</div>
                <div class="small text-muted mb-4">Simule os impactos da próxima prorrogação</div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Contrato</label>
                    <select class="form-select ts-search" id="sim-contrato-sel" data-placeholder="Buscar contrato…">
                        <option value="">Selecione…</option>
                        <?php foreach ($contratos as $c): ?>
                        <option value="<?= $c['id'] ?>" data-termino="<?= $c['data_termino'] ?>" data-meses="<?= $c['meses_totais'] ?>" data-valor="<?= $c['valor_global_atualizado'] ?>" data-inicio="<?= $c['data_inicio'] ?>">
                            <?= e($c['chave']) ?> — <?= e(substr($c['fornecedor_nome']??'',0,35)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="sim-info" style="display:none">
                    <div class="gc-card p-3 mb-3" style="background:var(--gc-bg-alt)">
                        <div class="row g-2 text-center small">
                            <div class="col-4"><div class="text-muted" style="font-size:.62rem">VENCIMENTO</div><div class="fw-semibold" id="sim-info-termino">—</div></div>
                            <div class="col-4"><div class="text-muted" style="font-size:.62rem">PRAZO</div><div class="fw-semibold" id="sim-info-meses">—</div></div>
                            <div class="col-4"><div class="text-muted" style="font-size:.62rem">VALOR</div><div class="fw-semibold" id="sim-info-valor">—</div></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Período</label>
                        <select class="form-select" id="sim-periodo">
                            <option value="6">6 meses</option><option value="12" selected>12 meses</option>
                            <option value="18">18 meses</option><option value="24">24 meses</option>
                            <option value="custom">Outro…</option>
                        </select>
                    </div>
                    <div id="sim-custom-wrap" style="display:none" class="mb-3">
                        <input type="number" class="form-control" id="sim-custom-meses" min="1" max="60" value="6" placeholder="Meses">
                    </div>
                    <button class="btn btn-primary w-100" id="sim-calcular"><i class="bi bi-calculator me-1"></i>Calcular</button>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="gc-card p-4 h-100" id="sim-resultado" style="display:none">
                <div class="fw-bold mb-3">Resultado</div>
                <div class="row g-3 mb-3">
                    <div class="col-6"><div class="prazo-sim-result"><div class="sim-label">Nova data de término</div><div class="sim-value" id="sim-r-termino">—</div></div></div>
                    <div class="col-6"><div class="prazo-sim-result"><div class="sim-label">Total prazo legal</div><div class="sim-value" id="sim-r-legal">—</div></div></div>
                    <div class="col-6"><div class="prazo-sim-result"><div class="sim-label">Meses disponíveis após</div><div class="sim-value" id="sim-r-saldo">—</div></div></div>
                    <div class="col-6"><div class="prazo-sim-result"><div class="sim-label">% do limite usado</div><div class="sim-value" id="sim-r-pct">—</div></div></div>
                </div>
                <div id="sim-alerta-legal" style="display:none" class="sim-alerta alert mb-3"></div>
                <div class="fw-semibold small mb-2">Consumo do limite legal</div>
                <div class="prazo-legal-bar" style="height:12px"><div class="prazo-legal-fill" id="sim-r-barra" style="width:0%;transition:width .5s"></div></div>
                <div class="d-flex justify-content-between small text-muted mt-1"><span>0</span><span id="sim-r-barra-label">—</span><span>60m</span></div>
            </div>
            <div class="gc-card p-4 h-100 d-flex align-items-center justify-content-center text-center text-muted" id="sim-placeholder">
                <div><i class="bi bi-calculator" style="font-size:2.5rem;opacity:.3"></i><p class="mt-3 mb-0">Selecione um contrato</p></div>
            </div>
        </div>
    </div>
</div>


</div><!-- /tab-content -->

<style>
/* Fila */
.fila-table td { font-size:.82rem; }

/* Gantt */
.gantt-wrap          { position:relative; overflow-x:auto; }
.gantt-header        { display:flex; margin-bottom:4px; position:sticky; top:0; z-index:2; background:var(--gc-surface); }
.gantt-label-col     { width:200px; min-width:200px; flex-shrink:0; padding-right:12px; }
.gantt-track-col     { flex:1; min-width:0; position:relative; }
.gantt-months        { display:flex; }
.gantt-month         { flex:1; font-size:.65rem; font-weight:700; text-transform:uppercase; color:var(--gc-muted); border-left:1px solid var(--gc-border); padding-left:4px; }
.gantt-grid-bg       { position:absolute; top:36px; left:200px; right:0; bottom:0; pointer-events:none; }
.gantt-grid-col      { position:absolute; top:0; bottom:0; width:1px; background:var(--gc-border); }
.gantt-grid-now      { background:#0d6efd44; width:2px; }
.gantt-rows          { padding-top:4px; }
.gantt-row           { display:flex; align-items:center; margin-bottom:6px; }
.gantt-row:hover     { background:var(--gc-bg-alt); border-radius:6px; }
.gantt-chave         { font-size:.78rem; color:var(--gc-text); }
.gantt-fornecedor    { font-size:.65rem; color:var(--gc-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:190px; }
.gantt-bar-wrap      { position:relative; height:22px; display:flex; align-items:center; }
.gantt-bar           { height:18px; border-radius:4px; position:relative; opacity:.85; min-width:6px; }
.gantt-bar:hover     { opacity:1; }
.gantt-bar-label     { font-size:.62rem; color:var(--gc-muted); margin-left:6px; white-space:nowrap; }
.gantt-proc-marker   { position:absolute; top:50%; transform:translateY(-50%); color:#fff; font-size:.6rem; opacity:.9; pointer-events:none; }
.gantt-legend-dot    { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:4px; }
.gantt-marker-legend { color:#fff; background:#333; border-radius:2px; padding:0 3px; font-size:.65rem; }

/* Posição Relativa (legado removido, novo abaixo) */

/* DNA redesign */
.dna-resumo-card        { border-radius:12px; padding:20px; text-align:center; transition:transform .15s,box-shadow .15s; border:2px solid transparent; }
.dna-resumo-card:hover  { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.1); }
.dna-resumo-ativo       { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.12); }
.dna-resumo-ok          { background:rgba(25,135,84,.07); border-color:#198754; }
.dna-resumo-neutro      { background:rgba(108,117,125,.07); border-color:#6c757d; }
.dna-resumo-alerta      { background:rgba(220,53,69,.07); border-color:#dc3545; }
.dna-resumo-ok.dna-resumo-ativo    { background:rgba(25,135,84,.14); }
.dna-resumo-neutro.dna-resumo-ativo{ background:rgba(108,117,125,.14); }
.dna-resumo-alerta.dna-resumo-ativo{ background:rgba(220,53,69,.14); }
.dna-resumo-num   { font-size:2.4rem; font-weight:800; line-height:1; }
.dna-resumo-ok    .dna-resumo-num  { color:#198754; }
.dna-resumo-neutro .dna-resumo-num { color:#6c757d; }
.dna-resumo-alerta .dna-resumo-num { color:#dc3545; }
.dna-resumo-titulo{ font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin:6px 0 4px; }
.dna-resumo-desc  { font-size:.72rem; color:var(--gc-muted); line-height:1.4; }

.dna-pos-bar-wrap    { margin-top:2px; }
.dna-pos-bar-track   { position:relative; height:8px; background:#e9ecef; border-radius:4px; overflow:hidden; }
.dna-pos-bar-center  { position:absolute; left:50%; top:0; bottom:0; width:2px; background:#adb5bd; }
.dna-pos-bar-fill    { position:absolute; top:0; bottom:0; }
.dna-pos-bar-labels  { display:flex; justify-content:space-between; font-size:.58rem; color:var(--gc-muted); margin-top:2px; }

/* DNA sparkline */
.dna-spark { display:flex; align-items:flex-end; gap:3px; height:38px; overflow:hidden; }
.dna-bar   { width:8px; border-radius:2px 2px 0 0; min-height:4px; flex-shrink:0; }

/* Heat map */
.heat-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:8px; }
@media(max-width:767px){ .heat-grid { grid-template-columns:repeat(3,1fr); } }
.heat-cell { border-radius:8px; padding:10px 8px; text-align:center; cursor:default; transition:transform .15s; }
.heat-cell:hover { transform:scale(1.05); }
.heat-cell-label { font-size:.68rem; font-weight:700; text-transform:uppercase; line-height:1.3; }
.heat-cell-label span { font-weight:400; font-size:.62rem; }
.heat-cell-num { font-size:1.2rem; font-weight:800; margin-top:4px; }
.heat-0 { background:#f1f3f5; color:#868e96; }
.heat-1 { background:#d3f9d8; color:#2b8a3e; }
.heat-2 { background:#74c0fc; color:#1864ab; }
.heat-3 { background:#ffd43b; color:#5f3c00; }
.heat-4 { background:#ff6b6b; color:#7d1010; }
.heat-clickable  { cursor:pointer; }
.heat-clickable:hover { filter:brightness(.92); transform:scale(1.05); }
.heat-selected   { outline:3px solid #0d6efd; outline-offset:2px; }
.heat-cell-val   { font-size:.6rem; font-weight:600; margin-top:3px; opacity:.85; }

/* Scorecard */
.sc-avatar { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.8rem; flex-shrink:0; }

/* Prazo fills */
.prazo-fill-danger  { background:#dc3545; }
.prazo-fill-warning { background:#ffc107; }
.prazo-fill-info    { background:#0dcaf0; }
.prazo-fill-success { background:#198754; }
</style>

<script>
const CSRF = '<?= e($csrfToken) ?>';

// ── Fila: filtro por gestor ───────────────────────────────────────────────
(function () {
    const sel  = document.getElementById('fila-gestor-sel');
    if (!sel) return;
    sel.addEventListener('change', function () {
        const g = this.value.toLowerCase();
        document.querySelectorAll('.fila-row').forEach(r => {
            r.style.display = (!g || r.dataset.gestor.toLowerCase() === g) ? '' : 'none';
        });
        document.querySelectorAll('.fila-grupo').forEach(grp => {
            const visivel = [...grp.querySelectorAll('.fila-row')].some(r => r.style.display !== 'none');
            grp.style.display = visivel ? '' : 'none';
        });
    });
})();

// ── Fila: atualização de status ───────────────────────────────────────────
(function () {
    const cls = { aguardando:'secondary', iniciado:'primary', em_revisao:'info', aguardando_assinatura:'warning', concluido:'success' };
    const lbl = { aguardando:'Aguardando', iniciado:'Iniciado', em_revisao:'Em revisão', aguardando_assinatura:'Aguard. assinatura', concluido:'Concluído' };

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.fila-status-btn');
        if (!btn) return;
        const id     = btn.dataset.id;
        const status = btn.dataset.status;
        const fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('contrato_id', id);
        fd.append('status', status);

        fetch('<?= url('/aditivos/processo-status') ?>', { method:'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) return;
                const wrap  = document.querySelector(`.fila-status-wrap[data-id="${id}"]`);
                const badge = wrap?.querySelector('.fila-status-badge');
                if (badge) {
                    badge.className = `badge bg-${cls[status]} fila-status-badge`;
                    badge.textContent = lbl[status];
                }
                if (status === 'concluido') {
                    const row = wrap?.closest('tr');
                    if (row) { row.style.opacity = '.4'; setTimeout(() => row.remove(), 800); }
                }
            });
    });
})();

// ── Gantt: filtro por gestor ──────────────────────────────────────────────
(function () {
    const sel = document.getElementById('gantt-gestor-sel');
    if (!sel) return;
    sel.addEventListener('change', function () {
        const g = this.value.toLowerCase();
        document.querySelectorAll('#gantt-rows .gantt-row').forEach(r => {
            r.style.display = (!g || r.dataset.gestor.toLowerCase() === g) ? '' : 'none';
        });
    });
})();

// ── Controle: filtro ─────────────────────────────────────────────────────
(function () {
    const inp  = document.getElementById('filtro-contrato');
    const sel  = document.getElementById('filtro-risco');
    const cnt  = document.getElementById('filtro-count');
    const rows = document.querySelectorAll('.prorrog-row');
    function filtrar() {
        const txt  = (inp?.value||'').toLowerCase();
        const risk = (sel?.value||'').toLowerCase();
        let vis = 0;
        rows.forEach(r => {
            const ok = (!txt||r.dataset.contrato.includes(txt)) && (!risk||r.dataset.risco.toLowerCase()===risk);
            r.style.display = ok?'':'none';
            if(ok) vis++;
        });
        if(cnt) cnt.textContent = vis+' contratos';
    }
    inp?.addEventListener('input', filtrar);
    sel?.addEventListener('change', filtrar);
})();

// ── Simulador ────────────────────────────────────────────────────────────
(function () {
    const sel     = document.getElementById('sim-contrato-sel');
    const info    = document.getElementById('sim-info');
    const periodo = document.getElementById('sim-periodo');
    const custom  = document.getElementById('sim-custom-wrap');
    const btnCalc = document.getElementById('sim-calcular');
    const result  = document.getElementById('sim-resultado');
    const ph      = document.getElementById('sim-placeholder');
    if (!sel) return;
    const LIMITE = 60;

    function fmtBR(iso){if(!iso)return'—';const[y,m,d]=iso.split('-');return d+'/'+m+'/'+y;}
    function addMonths(iso,n){const d=new Date(iso+'T12:00:00');d.setMonth(d.getMonth()+n);return d.toISOString().split('T')[0];}
    function fmtMoney(v){return'R$ '+Number(v).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});}

    sel.addEventListener('change', function(){
        const opt=this.options[this.selectedIndex];
        if(!this.value){info.style.display='none';return;}
        info.style.display='';
        document.getElementById('sim-info-termino').textContent=fmtBR(opt.dataset.termino);
        document.getElementById('sim-info-meses').textContent=(opt.dataset.meses||'?')+'/'+LIMITE+'m';
        document.getElementById('sim-info-valor').textContent=fmtMoney(opt.dataset.valor||0);
        result.style.display='none'; ph.style.display='';
    });
    periodo.addEventListener('change',function(){custom.style.display=this.value==='custom'?'':'none';});
    btnCalc.addEventListener('click',function(){
        const opt=sel.options[sel.selectedIndex];
        if(!sel.value) return;
        const termino=opt.dataset.termino;
        const mesesAtual=parseFloat(opt.dataset.meses)||0;
        const mesesAdd=periodo.value==='custom'?parseInt(document.getElementById('sim-custom-meses').value)||0:parseInt(periodo.value);
        if(!mesesAdd||!termino) return;
        const novoTermino=addMonths(termino,mesesAdd);
        const novoTotal=mesesAtual+mesesAdd;
        const saldo=Math.max(0,LIMITE-novoTotal).toFixed(1);
        const pct=Math.min(100,Math.round(novoTotal/LIMITE*100));
        const barColor=pct>=95?'#dc3545':pct>=80?'#ffc107':'#198754';
        document.getElementById('sim-r-termino').textContent=fmtBR(novoTermino);
        document.getElementById('sim-r-legal').textContent=novoTotal.toFixed(1)+' meses';
        document.getElementById('sim-r-saldo').textContent=saldo+' meses';
        document.getElementById('sim-r-pct').textContent=pct+'%';
        document.getElementById('sim-r-barra').style.cssText=`width:${pct}%;background:${barColor};transition:width .5s`;
        document.getElementById('sim-r-barra-label').textContent=novoTotal.toFixed(1)+' / 60 meses';
        const alerta=document.getElementById('sim-alerta-legal');
        if(novoTotal>LIMITE){alerta.style.display='';alerta.className='sim-alerta alert alert-danger';alerta.textContent='⚠ Excede o limite legal de 60 meses.';}
        else if(novoTotal>LIMITE*.9){alerta.style.display='';alerta.className='sim-alerta alert alert-warning';alerta.textContent='Atenção: restarão apenas '+saldo+' meses — última prorrogação possível.';}
        else{alerta.style.display='none';}
        result.style.display=''; ph.style.display='none';
    });
})();

// ── Gráficos (carregam após window.load) ─────────────────────────────────
window.addEventListener('load', function(){
    if(!window.Chart) return;

    const health=<?= $portfolioHealth ?>, cls='<?= $healthCls ?>';
    const colors={success:'#198754',info:'#0dcaf0',warning:'#ffc107',danger:'#dc3545'};
    let gaugeInst=null;
    function initGauge(){
        const c=document.getElementById('gaugeChart');
        if(!c||gaugeInst) return;
        gaugeInst=new Chart(c,{type:'doughnut',data:{datasets:[{data:[health,100-health],backgroundColor:[colors[cls]||'#198754','#e9ecef'],borderWidth:0,circumference:180,rotation:-90}]},options:{responsive:false,cutout:'72%',plugins:{legend:{display:false},tooltip:{enabled:false}}}});
    }

    const meses=<?= $cargaMeses ?>, totais=<?= $cargaTotais ?>, valores=<?= $cargaValores ?>;
    let cargaInst=null;
    function initCarga(){
        const c=document.getElementById('cargaChart');
        if(!c||cargaInst||!meses.length) return;
        cargaInst=new Chart(c,{type:'bar',data:{labels:meses,datasets:[{label:'Contratos a renovar',data:totais,backgroundColor:totais.map(v=>v>=5?'#dc3545cc':v>=3?'#ffc107cc':'#198754cc'),borderRadius:4,yAxisID:'y'},{label:'Valor (R$ mi)',data:valores,type:'line',borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,.08)',borderWidth:2,pointRadius:4,fill:true,yAxisID:'y2',tension:.3}]},options:{responsive:true,plugins:{legend:{position:'top'},tooltip:{mode:'index'}},scales:{y:{title:{display:true,text:'Contratos'},beginAtZero:true,ticks:{stepSize:1}},y2:{title:{display:true,text:'R$ mi'},position:'right',beginAtZero:true,grid:{drawOnChartArea:false}}}}});
    }

    document.querySelector('[data-bs-target="#tab-saude"]')?.addEventListener('shown.bs.tab', initGauge);
    document.querySelector('[data-bs-target="#tab-carga"]')?.addEventListener('shown.bs.tab', initCarga);
});
</script>

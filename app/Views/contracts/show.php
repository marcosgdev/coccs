<?php
$canWrite        = GestContratos\Core\Auth::canWrite();
$canDelete       = GestContratos\Core\Auth::canDelete();
$canStrategic    = GestContratos\Core\Auth::canMarkStrategic();
$canNotify       = GestContratos\Core\Auth::canNotify();
$vInicial        = $vOriginal;
$vAtualizado     = $vAtual;     // compatibilidade com código legado na view
$vExecutado      = (float) ($contract['valor_executado'] ?? 0);
$pctExec         = $vAtual > 0 ? min(100, round($vExecutado / $vAtual * 100, 1)) : 0;
$validacao       = base64_encode(date('d/m/Y'));
$isProrrogacao = fn($a) =>
    stripos($a['tipo_aditivo'] ?? '', 'prorrog') !== false ||
    stripos($a['objeto']       ?? '', 'prorrog') !== false;
$qtdProrrogacoes = count(array_filter($aditivos ?? [], $isProrrogacao));

// Formata chave: "CONTRATO50/2024" → tipo="CONTRATO", num="50", ano="2024"
preg_match('/^([A-Za-z]+)(\d+)\/(\d+)$/', $contract['chave'] ?? '', $chaveParts);
$chaveLabel = !empty($chaveParts)
    ? ucfirst(strtolower($chaveParts[1])) . ' nº ' . ltrim($chaveParts[2], '0') . '/' . $chaveParts[3]
    : $contract['chave'];

// Reconstrói vigências a partir dos aditivos de prorrogação com nova_data_termino
// Reconstrói vigências a partir dos aditivos de prorrogação.
// Estratégia de split (em ordem de preferência):
//   1. data_aditivo — data de assinatura ≈ fim da vigência anterior
//   2. Fallback proporcional — divide o período total igualmente entre as vigências
$vigencias    = [];
$dataInicio   = $contract['data_inicio']  ?? null;
$dataTermino  = $contract['data_termino'] ?? null;

// Aditivos de prorrogação com valor autorizado (valor_acrescido = valor da nova vigência)
$prorrogsAditivos = array_values(array_filter($aditivos ?? [],
    fn($a) => $isProrrogacao($a) && !empty($a['nova_data_termino'])
));
$nProrrog = count($prorrogsAditivos);

if ($dataInicio && $nProrrog > 0) {
    // Pontos de corte entre vigências: data_aditivo (assinatura ≈ fim da vigência anterior)
    $prorrogsDatas = [];
    foreach ($prorrogsAditivos as $ad) {
        if (!empty($ad['data_aditivo'])) {
            $prorrogsDatas[] = $ad['data_aditivo'];
        }
    }
    sort($prorrogsDatas);
    $prorrogsDatas = array_unique($prorrogsDatas);

    // Fallback proporcional quando data_aditivo está ausente
    if (count($prorrogsDatas) < $nProrrog && $dataTermino) {
        $tI = strtotime($dataInicio); $tF = strtotime($dataTermino);
        $partes = $nProrrog + 1;
        $prorrogsDatas = [];
        for ($i = 1; $i < $partes; $i++) {
            $prorrogsDatas[] = date('Y-m-d', $tI + (int)(($tF - $tI) / $partes * $i));
        }
    }

    // Valor autorizado de cada vigência
    // Vigências de prorrogação: valor_acrescido do aditivo
    // 1ª vigência (original): valor total − soma das prorrogações
    $totalProrrogacoes = array_sum(array_map(fn($a) => (float)($a['valor_acrescido'] ?? 0), $prorrogsAditivos));
    $valorOriginal     = $vAtualizado - $totalProrrogacoes;

    $inic = $dataInicio;
    $idxP = 0;
    // 1ª vigência
    $corte = $prorrogsDatas[0] ?? $dataTermino;
    $vigencias[] = ['inicio' => $inic, 'fim' => $corte, 'autorizado' => max(0, $valorOriginal)];
    $inic = $corte;
    // Vigências de prorrogação
    foreach ($prorrogsAditivos as $i => $ad) {
        $corte = $prorrogsDatas[$i + 1] ?? $dataTermino;
        $fim   = ($i < $nProrrog - 1 && $corte > $inic) ? $corte : $dataTermino;
        if ($fim && $inic < $fim) {
            $vigencias[] = ['inicio' => $inic, 'fim' => $fim, 'autorizado' => (float)($ad['valor_acrescido'] ?? 0)];
            $inic = $fim;
        }
    }
} elseif ($dataInicio && $dataTermino) {
    $vigencias[] = ['inicio' => $dataInicio, 'fim' => $dataTermino, 'autorizado' => $vAtualizado];
}

$prorrogsDatas = $prorrogsDatas ?? [];

// Helper: em qual vigência cai uma data?
$vigenciaKey = function(?string $data) use ($vigencias): string {
    if (!$data || !$vigencias) {
        $ano = $data ? substr($data, 0, 4) : 'S/D';
        return '999|' . $ano;
    }
    $last = array_key_last($vigencias);
    foreach ($vigencias as $i => $v) {
        // Última vigência: inclui o fim. Demais: fim é exclusivo (início da próxima).
        $dentroFim = $v['fim'] === null || ($i === $last ? $data <= $v['fim'] : $data < $v['fim']);
        if ($data >= $v['inicio'] && $dentroFim) {
            $label = date_br($v['inicio']) . ' – ' . ($v['fim'] ? date_br($v['fim']) : '?');
            return str_pad($i, 3, '0', STR_PAD_LEFT) . '|' . $label;
        }
    }
    // Fora de qualquer vigência mapeada — usa o exercício
    $ano = substr($data, 0, 4);
    return '999|' . $ano;
};

// Pré-popula todas as vigências (inclusive as sem empenhos ainda)
$empAnos = [];
foreach ($vigencias as $i => $v) {
    $lbl = date_br($v['inicio']) . ' – ' . ($v['fim'] ? date_br($v['fim']) : '?');
    $key = str_pad($i, 3, '0', STR_PAD_LEFT) . '|' . $lbl;
    $empAnos[$key] = ['total' => 0, 'liquidado' => 0, 'itens' => []];
}

// Empenhos agrupados por vigência (data_empenho como base).
// Liquidado = soma das contrato_liquidacoes daquele empenho (fonte confiável e atual),
// atribuída à mesma vigência do empenho. Assim cada vigência mostra o que foi
// empenhado nela e quanto desses empenhos foi efetivamente liquidado (em qualquer data).
foreach ($empenhos ?? [] as $e) {
    $chave   = $vigenciaKey($e['data_empenho'] ?? null);
    $liqEmp  = (float) ($liqPorEmpenho[$e['empenho']] ?? $e['valor_liquidado'] ?? 0);
    $empAnos[$chave]['total']     = ($empAnos[$chave]['total']     ?? 0) + (float) $e['valor'];
    $empAnos[$chave]['liquidado'] = ($empAnos[$chave]['liquidado'] ?? 0) + $liqEmp;
    $empAnos[$chave]['itens'][]   = $e;
}
ksort($empAnos);

$maxEmpenhado = max(array_map(fn($g) => $g['total'], $empAnos) ?: [1]);

// ── Controle de Prazo ────────────────────────────────────────────────────────
$hoje            = time();
$tInicio         = $dataInicio  ? strtotime($dataInicio)  : null;
$tTermino        = $dataTermino ? strtotime($dataTermino) : null;
$diasRestantes   = $tTermino ? (int) round(($tTermino - $hoje) / 86400) : null;
$mesesConsumidos = ($tInicio && $tTermino)
    ? (int) round(($hoje - $tInicio) / (86400 * 30.4375))
    : null;
$limiteMaxMeses  = 60; // Lei 8.666 — serviços contínuos
$pctLegal        = ($tInicio && $tTermino)
    ? min(100, round((strtotime($dataTermino) - $tInicio) / (86400 * 30.4375) / $limiteMaxMeses * 100))
    : null;
$mesesTotais     = ($tInicio && $tTermino)
    ? round((strtotime($dataTermino) - $tInicio) / (86400 * 30.4375), 1)
    : null;
$leadTimeDias    = 60; // dias ideais para iniciar processo de prorrogação
$dataInicioProc  = $tTermino ? date('Y-m-d', $tTermino - $leadTimeDias * 86400) : null;
$leadTimeVenceu  = $dataInicioProc && $dataInicioProc < date('Y-m-d');

// Score de risco (0 = saudável, 100 = crítico)
$scoreRisco = 0;
if ($diasRestantes !== null) {
    if ($diasRestantes < 0)         $scoreRisco += 50;
    elseif ($diasRestantes < 30)    $scoreRisco += 40;
    elseif ($diasRestantes < 90)    $scoreRisco += 25;
    elseif ($diasRestantes < 180)   $scoreRisco += 10;
}
if ($pctLegal !== null) {
    if ($pctLegal >= 95) $scoreRisco += 40;
    elseif ($pctLegal >= 80) $scoreRisco += 25;
    elseif ($pctLegal >= 60) $scoreRisco += 10;
}
$scoreRisco = min(100, $scoreRisco);
[$scoreLabel, $scoreCls] = match(true) {
    $scoreRisco >= 70 => ['Crítico',  'danger'],
    $scoreRisco >= 40 => ['Atenção',  'warning'],
    $scoreRisco >= 15 => ['Moderado', 'info'],
    default           => ['Saudável', 'success'],
};

// Burn rate mensal de empenhos (para simulador)
$burnRateMensal = 0;
if ($tInicio && $vExecutado > 0) {
    $mesesDecorridos = max(1, round(($hoje - $tInicio) / (86400 * 30.4375)));
    $burnRateMensal  = round($vExecutado / $mesesDecorridos, 2);
}

// Meses acrescidos por cada prorrogação
$mesesPorProrrog = [];
foreach ($prorrogsAditivos as $idx => $ad) {
    $novaTerm = $ad['nova_data_termino'] ?? null;
    $prevTerm = $idx === 0 ? $dataInicio : ($prorrogsAditivos[$idx-1]['nova_data_termino'] ?? $dataInicio);
    if ($novaTerm && $prevTerm) {
        $mesesPorProrrog[$ad['numero_aditivo']] =
            round((strtotime($novaTerm) - strtotime($prevTerm)) / (86400 * 30.4375));
    }
}

// Timeline: cruza eventos com aditivos por ordem/numeroAditivo
$timeline = [];
foreach ($eventos ?? [] as $ev) {
    $ord = (int) $ev['ordem'];
    $timeline[$ord] = ['ordem' => $ord, 'data' => $ev['data'], 'evento' => $ev['descricao'], 'aditivos' => []];
}
foreach ($aditivos ?? [] as $ad) {
    $ord = (int) $ad['numero_aditivo'];
    if (!isset($timeline[$ord])) {
        $timeline[$ord] = ['ordem' => $ord, 'data' => $ad['data_aditivo'], 'evento' => null, 'aditivos' => []];
    }
    $timeline[$ord]['aditivos'][] = $ad;
    if (!$timeline[$ord]['data'] && $ad['data_aditivo']) $timeline[$ord]['data'] = $ad['data_aditivo'];
}
ksort($timeline);

$itensAtivos   = array_filter($itens ?? [], fn($i) => (float) $i['quantidade'] > 0);
$itensInativos = array_filter($itens ?? [], fn($i) => (float) $i['quantidade'] == 0);

$crescimentoValor = $vInicial > 0 ? round(($vAtualizado - $vInicial) / $vInicial * 100, 1) : 0;

// ── Resumo de aditivos ────────────────────────────────────────────────────
$_totalAcrescimos  = 0;
$_totalSupressoes  = 0;
$_totalMesesProrrog = array_sum($mesesPorProrrog);
$_tiposContagem    = [];
$_ultimoAditivo    = null;
foreach ($aditivos ?? [] as $_ad) {
    $_totalAcrescimos += (float) ($_ad['valor_acrescido']  ?? 0);
    $_totalSupressoes += (float) ($_ad['valor_suprimido']  ?? 0);
    $tipo = trim($_ad['tipo_aditivo'] ?? 'Outros');
    $_tiposContagem[$tipo] = ($_tiposContagem[$tipo] ?? 0) + 1;
    $dt = $_ad['data_aditivo'] ?? null;
    if ($dt && (!$_ultimoAditivo || $dt > $_ultimoAditivo)) $_ultimoAditivo = $dt;
}
arsort($_tiposContagem);

// Alertas legais automáticos
$_alertas = [];
$_pctAcrescimo = $vInicial > 0 ? round(($_totalAcrescimos / $vInicial) * 100, 1) : 0;
if ($_pctAcrescimo > 25) {
    $_alertas[] = ['danger', 'bi-exclamation-triangle-fill',
        "Acréscimos somam {$_pctAcrescimo}% do valor original — acima do limite legal de 25% (Art. 65, §1°, Lei 8.666)."];
} elseif ($_pctAcrescimo > 20) {
    $_alertas[] = ['warning', 'bi-exclamation-triangle',
        "Acréscimos já somam {$_pctAcrescimo}% do valor original — próximo do limite legal de 25%."];
}
if ($pctLegal !== null && $pctLegal >= 95) {
    $_alertas[] = ['danger', 'bi-calendar-x-fill',
        "O contrato usa {$pctLegal}% do prazo legal máximo de 60 meses — prorrogação adicional não é possível."];
} elseif ($pctLegal !== null && $pctLegal >= 80) {
    $_alertas[] = ['warning', 'bi-calendar-exclamation',
        "O contrato usa {$pctLegal}% do prazo legal máximo de 60 meses."];
}
$_pctTempo = ($tInicio && $tTermino)
    ? min(100, round(($hoje - $tInicio) / ($tTermino - $tInicio) * 100))
    : null;
if ($_pctTempo !== null && $_pctTempo > 50 && $pctExec < 20 && $vExecutado > 0) {
    $_alertas[] = ['warning', 'bi-graph-down-arrow',
        "Baixa execução ({$pctExec}%) apesar de {$_pctTempo}% do prazo decorrido — risco de subutilização."];
}
if (empty($_alertas) && !empty($aditivos)) {
    $_alertas[] = ['success', 'bi-check-circle-fill', 'Nenhuma anomalia detectada nos aditivos registrados.'];
}

// Projeção de esgotamento (baseada no burn rate mensal)
$_projMeses = null;
$_projData  = null;
if ($burnRateMensal > 0 && $vAtualizado > 0) {
    $_saldoRestante = max(0, $vAtualizado - $vExecutado);
    $_projMeses = $_saldoRestante > 0 ? ceil($_saldoRestante / $burnRateMensal) : 0;
    $_projData  = date('m/Y', strtotime("+{$_projMeses} months"));
}

// Waterfall de evolução do valor
$_waterfall = [];
$_vCorrente = $vInicial;
$_waterfall[] = ['label' => 'Valor original', 'valor' => $_vCorrente, 'delta' => 0, 'tipo' => 'origem', 'data' => $dataInicio];
foreach ($aditivos ?? [] as $_wad) {
    $delta = (float)($_wad['valor_acrescido'] ?? 0) - (float)($_wad['valor_suprimido'] ?? 0);
    if ($delta == 0 && !$_wad['nova_data_termino']) continue;
    $_vCorrente += $delta;
    $_waterfall[] = [
        'label' => 'Aditivo ' . ($_wad['numero_aditivo'] ?? '?') . ' — ' . ($_wad['tipo_aditivo'] ?? 'Sem tipo'),
        'valor' => $_vCorrente,
        'delta' => $delta,
        'tipo'  => $delta > 0 ? 'acrescimo' : ($delta < 0 ? 'supressao' : 'prazo'),
        'data'  => $_wad['data_aditivo'] ?? null,
    ];
}

// Posições para mini-timeline (% do contrato)
$_timelineEvents = [];
if ($tInicio && $tTermino && $tTermino > $tInicio) {
    $_duracao = $tTermino - $tInicio;
    foreach ($aditivos ?? [] as $_tad) {
        $tAd = $_tad['data_aditivo'] ? strtotime($_tad['data_aditivo']) : null;
        if (!$tAd) continue;
        $pct = min(98, max(1, round(($tAd - $tInicio) / $_duracao * 100)));
        $_timelineEvents[] = [
            'pct'   => $pct,
            'label' => 'Aditivo ' . ($_tad['numero_aditivo'] ?? '?') . ' (' . ($_tad['tipo_aditivo'] ?? 'Sem tipo') . ') — ' . date_br($_tad['data_aditivo']),
            'tipo'  => strtolower($_tad['tipo_aditivo'] ?? ''),
        ];
    }
    // posição atual (hoje)
    $_pctHoje = min(100, max(0, round(($hoje - $tInicio) / $_duracao * 100)));
}
$vSaldo           = $vAtualizado - $vExecutado;   // saldo contratual restante
$pctEmpenhado     = $vAtualizado > 0 ? round($vExecutado / $vAtualizado * 100, 1) : 0;

// Totais de liquidação (buscados sob demanda)
$stmtLiq = \GestContratos\Core\Database::pdo()->prepare(
    'SELECT COALESCE(SUM(valor_liquidado),0) AS liq,
            MAX(liquidado_em) AS atualizado_em,
            COUNT(CASE WHEN valor_liquidado IS NOT NULL THEN 1 END) AS com_liq,
            COUNT(*) AS total_emp
     FROM contrato_empenhos WHERE contrato_id=?'
);
$stmtLiq->execute([$contract['id']]);
$liqRow        = $stmtLiq->fetch();
$vLiquidado    = (float) ($liqRow['liq'] ?? 0);
$temLiquidacao = ($liqRow['com_liq'] ?? 0) > 0;
$pctLiq        = $vExecutado > 0 ? min(100, round($vLiquidado / $vExecutado * 100, 1)) : 0;
$liquidadoEm   = $liqRow['atualizado_em'] ?? null;
$vALiquidar    = $vExecutado - $vLiquidado; // empenhado ainda não liquidado
?>

<!-- ── Cabeçalho ─────────────────────────────────────────────────────────── -->
<div class="show-hero mb-4">
    <div class="show-hero-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="show-tipo-badge"><?= e(strtoupper($contract['tipo'] ?? 'CONTRATO')) ?></span>
            <?php if (!empty($contract['licitacao_numero'])): ?>
                <span class="show-licitacao-badge"><i class="bi bi-gavel me-1"></i>Pregão <?= e($contract['licitacao_numero']) ?></span>
            <?php endif; ?>
        </div>

        <h1 class="show-title"><?= e($chaveLabel) ?></h1>
        <p class="show-subtitle"><?= e($contract['fornecedor_nome']) ?></p>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="badge show-badge-status <?= e(badge_class($contract['situacao'])) ?>"><?= e($contract['situacao']) ?></span>
            <span class="badge show-badge-status <?= e(badge_class($contract['prazo'])) ?>"><?= e($contract['prazo']) ?></span>
            <?php if ($qtdProrrogacoes > 0): ?>
                <span class="badge show-badge-status bg-info text-dark"><?= $qtdProrrogacoes ?>× prorrogado</span>
            <?php endif; ?>
            <?php if ($contract['contrato_estrategico']): ?>
                <span class="badge show-badge-status text-bg-warning"><i class="bi bi-star-fill me-1"></i>Estratégico</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Métricas rápidas -->
    <div class="show-metrics">
        <div class="show-metric">
            <div class="show-metric-label">Valor Atualizado</div>
            <div class="show-metric-value"><?= e(money_br($vAtualizado)) ?></div>
            <?php if ($crescimentoValor != 0): ?>
                <div class="show-metric-sub <?= $crescimentoValor > 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $crescimentoValor > 0 ? '▲' : '▼' ?> <?= abs($crescimentoValor) ?>% do original
                </div>
            <?php endif; ?>
        </div>
        <div class="show-metric">
            <div class="show-metric-label">Execução</div>
            <div class="show-metric-value"><?= $pctExec ?>%</div>
            <div class="show-metric-sub"><?= e(money_br($vExecutado)) ?> empenhado</div>
        </div>
        <div class="show-metric">
            <div class="show-metric-label">Término</div>
            <div class="show-metric-value show-metric-value--date"><?= e(date_br($contract['data_termino'])) ?></div>
            <?php if (is_numeric($contract['dias_restantes'] ?? '')): ?>
                <div class="show-metric-sub"><?= $contract['dias_restantes'] ?> dias restantes</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canWrite || $canStrategic || $canNotify): ?>
    <div class="show-actions">
        <?php if ($canWrite): ?>
        <a class="btn btn-sm btn-outline-light" href="<?= e(url('/contratos/'.$contract['id'].'/editar')) ?>"><i class="bi bi-pencil me-1"></i>Editar</a>
        <form method="post" action="<?= e(url('/contratos/'.$contract['id'].'/duplicar')) ?>" class="d-inline"><?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-light"><i class="bi bi-files me-1"></i>Duplicar</button></form>
        <?php endif; ?>
        <?php if ($canStrategic): ?>
        <form method="post" action="<?= e(url('/contratos/'.$contract['id'].'/estrategico')) ?>" class="d-inline"><?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-warning"><i class="bi bi-star me-1"></i>Estratégico</button></form>
        <?php endif; ?>
        <?php if ($canNotify): ?>
        <form method="post" action="<?= e(url('/contratos/'.$contract['id'].'/notificacao')) ?>" class="d-inline"><?= csrf_field() ?>
            <button class="btn btn-sm btn-warning"><i class="bi bi-bell me-1"></i>Gerar notificação</button></form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ── Abas ───────────────────────────────────────────────────────────────── -->
<ul class="nav show-tabs mb-3" id="contractTabs">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-detalhes"><i class="bi bi-info-circle me-1"></i>Detalhes</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-financeiro"><i class="bi bi-cash-stack me-1"></i>Financeiro<?php if ($empenhos): ?> <span class="tab-count"><?= count($empenhos) ?></span><?php endif; ?></button></li>
    <?php if ($itensAtivos): ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-itens"><i class="bi bi-list-check me-1"></i>Itens <span class="tab-count"><?= count($itensAtivos) ?></span></button></li>
    <?php endif; ?>
    <?php if ($timeline): ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-timeline"><i class="bi bi-clock-history me-1"></i>Histórico <span class="tab-count"><?= count($timeline) ?></span></button></li>
    <?php endif; ?>
    <?php if ($documentos): ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documentos"><i class="bi bi-file-earmark me-1"></i>Documentos <span class="tab-count"><?= count($documentos) ?></span></button></li>
    <?php endif; ?>
    <?php if ($licitacaoContratos): ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-licitacao"><i class="bi bi-diagram-3 me-1"></i>Licitação <span class="tab-count"><?= count($licitacaoContratos) + 1 ?></span></button></li>
    <?php endif; ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-acompanhamento"><i class="bi bi-journal-check me-1"></i>Acompanhamento<?php if (!empty($acompanhamentos)): ?> <span class="tab-count"><?= count($acompanhamentos) ?></span><?php endif; ?></button></li>
</ul>

<div class="tab-content">

<!-- ── ABA: Detalhes ──────────────────────────────────────────────────────── -->
<div class="tab-pane fade show active" id="tab-detalhes">
    <div class="row g-3">
        <div class="col-12">
            <div class="gc-card p-4">
                <h2 class="show-section-title">Informações gerais</h2>
                <dl class="show-dl">
                    <?php
                    $details = [
                        ['Tipo',              $contract['tipo']],
                        ['Número/Ano',        $contract['numero'].'/'.$contract['ano']],
                        ['CNPJ / CPF',        $contract['cnpj_cpf']],
                        ['Setor',             $contract['setor_nome']],
                        ['Natureza',          $contract['natureza_contratacao_nome']],
                        ['Forma',             $contract['forma_contratacao_nome']],
                        ['Base legal',        $contract['base_legal_nome']],
                        ['Licitação',         $contract['licitacao_numero']],
                        ['Processo interno',  $contract['processo']],
                        ['Assinatura',        date_br($contract['data_inicio'])],
                        ['Início vigência',   date_br($contract['data_inicio'])],
                        ['Término vigência',  date_br($contract['data_termino'])],
                        ['Prorrogações',      $qtdProrrogacoes > 0 ? $qtdProrrogacoes.'×' : ($aditivos ? '0×' : '-')],
                        ['Trimestre',         $contract['trimestre_vencimento']],
                        ['Prazo prorrogação', date_br($contract['prazo_prorrogacao'])],
                        ['Prazo legal',       $contract['prazo_legal_classificacao']],
                        ['Reajuste',          $contract['status_reajuste']],
                    ];
                    foreach ($details as [$label, $value]): ?>
                    <div class="show-dl-item">
                        <dt><?= e($label) ?></dt>
                        <dd><?= e($value ?: '—') ?></dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="gc-card p-4 h-100">
                <h2 class="show-section-title">Equipe</h2>
                <div class="show-team">
                    <?php
                    $team = [
                        ['Gestor',               'bi-person-badge',      $contract['gestor']],
                        ['Gestor substituto',     'bi-person-badge-fill', $contract['gestor_substituto']],
                        ['Fiscal demandante',     'bi-person',            $contract['fiscal_demandante']],
                        ['Fiscal técnico',        'bi-person-gear',       $contract['fiscal_tecnico']],
                        ['Fiscal substituto',     'bi-person-dash',       $contract['fiscal_substituto']],
                        ['Fiscal administrativo', 'bi-person-check',      $contract['fiscal_administrativo']],
                    ];
                    foreach ($team as [$role, $icon, $name]):
                        if (!$name) continue; ?>
                    <div class="show-team-member">
                        <i class="bi <?= $icon ?> show-team-icon"></i>
                        <div>
                            <div class="show-team-name"><?= e($name) ?></div>
                            <div class="show-team-role"><?= e($role) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($contract['emails_equipe']): ?>
                    <div class="show-team-emails mt-2">
                        <i class="bi bi-envelope text-muted me-2"></i><span class="small text-muted"><?= e($contract['emails_equipe']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="gc-card p-4 h-100">
                <h2 class="show-section-title">Objeto</h2>
                <p class="show-objeto"><?= nl2br(e($contract['objeto'])) ?></p>
                <?php if ($contract['observacoes']): ?>
                    <hr class="my-3">
                    <p class="text-muted small mb-0"><?= nl2br(e($contract['observacoes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── ABA: Financeiro ────────────────────────────────────────────────────── -->
<div class="tab-pane fade" id="tab-financeiro">

    <?php if ($contract['tipo'] === 'CONTRATO' && $sincronizado): ?>
    <!-- Composição de valor em 7 etapas -->
    <div class="gc-card p-3 mb-3">
        <div class="small fw-bold text-muted text-uppercase mb-2" style="letter-spacing:.06em">Composição do Valor</div>
        <div class="row g-2">
            <div class="col-6 col-md-3 col-xl">
                <div class="show-fin-card" style="border-left:3px solid #6366f1">
                    <div class="show-fin-label">Valor Original</div>
                    <div class="show-fin-value" style="font-size:.95rem"><?= e(money_br($vOriginal)) ?></div>
                    <div class="show-fin-sub text-muted">Na assinatura</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="show-fin-card" style="border-left:3px solid #0ea5e9">
                    <div class="show-fin-label">Valor Reajustes</div>
                    <div class="show-fin-value" style="font-size:.95rem;color:<?= $vReajustes >= 0 ? '#16a34a' : '#dc2626' ?>"><?= e(money_br($vReajustes)) ?></div>
                    <div class="show-fin-sub text-muted">Reajuste / Apostilamento</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="show-fin-card" style="border-left:3px solid #0284c7">
                    <div class="show-fin-label">Valor Corrigido</div>
                    <div class="show-fin-value" style="font-size:.95rem"><?= e(money_br($vCorrigido)) ?></div>
                    <div class="show-fin-sub text-muted">Original + Reajustes</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="show-fin-card" style="border-left:3px solid #f59e0b">
                    <div class="show-fin-label">Valor Aditivos</div>
                    <div class="show-fin-value" style="font-size:.95rem;color:<?= $vAditivosLiq >= 0 ? '#16a34a' : '#dc2626' ?>"><?= e(money_br($vAditivosLiq)) ?></div>
                    <div class="show-fin-sub text-muted">Acréscimo / Supressão</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="show-fin-card" style="border-left:3px solid #8b5cf6">
                    <div class="show-fin-label">Valor Prorrogação</div>
                    <div class="show-fin-value" style="font-size:.95rem"><?= e(money_br($vProrrog)) ?></div>
                    <div class="show-fin-sub text-muted">Soma das prorrogações</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="show-fin-card show-fin-card--primary">
                    <div class="show-fin-label">Valor Atual</div>
                    <div class="show-fin-value"><?= e(money_br($vAtual)) ?></div>
                    <div class="show-fin-sub">Corrigido + Aditivos</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="show-fin-card" style="border-left:3px solid #1a3a5c;background:#f8fafc">
                    <div class="show-fin-label">Valor Total</div>
                    <div class="show-fin-value" style="font-size:.95rem"><?= e(money_br($vTotal)) ?></div>
                    <div class="show-fin-sub text-muted">Atual + Prorrogação</div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Linha 1: valores simples (ARP ou não sincronizado) -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4">
            <div class="show-fin-card">
                <div class="show-fin-label">Valor Original</div>
                <div class="show-fin-value"><?= e(money_br($vOriginal)) ?></div>
                <div class="show-fin-sub text-muted">Na assinatura</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="show-fin-card show-fin-card--primary">
                <div class="show-fin-label">Valor Atual</div>
                <div class="show-fin-value"><?= e(money_br($vAtual)) ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="show-fin-card show-fin-card--saldo">
                <div class="show-fin-label">Saldo do contrato</div>
                <?php if ($vSaldo >= 0): ?>
                    <div class="show-fin-value"><?= e(money_br($vSaldo)) ?></div>
                    <div class="show-fin-sub text-muted"><?= $pctEmpenhado ?>% empenhado</div>
                <?php else: ?>
                    <div class="show-fin-value text-danger"><?= e(money_br(abs($vSaldo))) ?></div>
                    <div class="show-fin-sub text-danger">⚠ Empenhado excede o valor</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Linha 2: execução orçamentária -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4">
            <div class="show-fin-card show-fin-card--empenho">
                <div class="show-fin-label">Empenhado</div>
                <div class="show-fin-value"><?= e(money_br($vExecutado)) ?></div>
                <div class="show-fin-sub text-muted">Comprometido orçamentariamente</div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="show-fin-card show-fin-card--success">
                <div class="show-fin-label">Liquidado</div>
                <div class="show-fin-value" id="val-liquidado">
                    <?= $temLiquidacao ? e(money_br($vLiquidado)) : '<span class="show-fin-nd">—</span>' ?>
                </div>
                <div class="show-fin-sub" id="sub-liquidado">
                    <?php if ($temLiquidacao): ?>
                        <?= $pctLiq ?>% do empenhado · serviço confirmado
                        <?php if ($liquidadoEm): ?><br><span style="font-size:.65rem;opacity:.7">atualizado <?= date('d/m H:i', strtotime($liquidadoEm)) ?></span><?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-link btn-sm p-0 small" onclick="document.getElementById('btn-liquidacoes').click()">Buscar agora</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="show-fin-card">
                <div class="show-fin-label">A liquidar</div>
                <div class="show-fin-value" id="val-a-liquidar">
                    <?= $temLiquidacao ? e(money_br($vALiquidar)) : '<span class="show-fin-nd">—</span>' ?>
                </div>
                <div class="show-fin-sub" id="sub-a-liquidar">
                    <?php if ($temLiquidacao): ?>
                        Empenhado pendente de liquidação
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Botão buscar liquidações -->
    <div class="d-flex align-items-center gap-3 mb-3">
        <button id="btn-liquidacoes" class="btn btn-sm btn-outline-primary"
                data-url="<?= e(url('/contratos/'.$contract['id'].'/liquidacoes')) ?>"
                data-csrf="<?= e(\GestContratos\Core\Csrf::token()) ?>">
            <i class="bi bi-arrow-repeat me-1"></i>
            <?= $temLiquidacao ? 'Atualizar liquidações' : 'Buscar liquidações' ?>
        </button>
        <span id="liq-status" class="small text-muted"></span>
    </div>

    <?php if ($empAnos): ?>
    <!-- Pipeline visual por vigência -->
    <div class="gc-card p-4 mb-3">
        <h2 class="show-section-title mb-1">Execução por vigência</h2>
        <div class="d-flex align-items-center gap-3 small text-muted mb-3 flex-wrap">
            <span><span class="d-inline-block rounded me-1" style="width:12px;height:12px;background:var(--gc-primary);opacity:.85"></span>Empenhado</span>
            <span><span class="d-inline-block rounded me-1" style="width:12px;height:12px;background:#198754"></span>Liquidado</span>
            <?php $prorrogSemData = count(array_filter($aditivos ?? [], fn($a) => $isProrrogacao($a) && empty($a['nova_data_termino'])));
            if ($prorrogSemData > 0): ?>
            <span class="ms-auto d-flex align-items-center gap-2 text-warning-emphasis">
                <i class="bi bi-exclamation-triangle"></i>
                Datas das prorrogações não sincronizadas — vigências unificadas.
                <button class="btn btn-sm btn-outline-warning py-0 px-2" id="btn-sync-aditivos-show">
                    <i class="bi bi-arrow-repeat me-1"></i>Sincronizar datas
                </button>
            </span>
            <?php endif; ?>
        </div>
        <?php
        $nVig    = count($empAnos);
        $iVig    = 0;
        foreach ($empAnos as $chave => $g):
            $iVig++;
            $liqV       = (float) ($g['liquidado'] ?? 0);
            $empV       = (float) $g['total'];
            // Valor autorizado para esta vigência (original ou da prorrogação)
            $vigIdx     = (int) explode('|', $chave)[0];
            $autorizado = (float) ($vigencias[$vigIdx]['autorizado'] ?? 0);
            // Barra externa: empenhado como % do autorizado da vigência
            $basePct    = $autorizado > 0 ? $autorizado : $maxEmpenhado;
            $pctEmp     = $basePct > 0 ? min(100, round($empV / $basePct * 100, 1)) : 0;
            $pctLiqV    = $empV > 0    ? min(100, round($liqV / $empV * 100, 1))    : 0;
            $aLiqV      = $empV - $liqV;
            $label      = preg_replace('/^\d+\|/', '', $chave);
            $isLast     = $iVig === $nVig;
            $dotCls     = $pctLiqV >= 90 ? 'bg-success' : ($pctLiqV >= 50 ? 'bg-warning' : ($empV > 0 ? 'bg-primary' : 'bg-secondary'));
        ?>
        <div class="vig-track <?= !$isLast ? 'mb-3' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-1 gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="vig-dot <?= $dotCls ?>"></span>
                    <span class="vig-label"><?= e($label) ?></span>
                    <?php if ($nVig > 1): ?>
                        <span class="vig-badge"><?= $iVig ?>ª vigência</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-3 text-end">
                    <?php if ($autorizado > 0): ?>
                    <span class="small text-muted text-nowrap">Aut. <?= e(money_br($autorizado)) ?></span>
                    <?php endif; ?>
                    <span class="small text-muted text-nowrap"><?= e(money_br($empV)) ?> emp. <span class="text-body-secondary">(<?= $pctEmp ?>%)</span></span>
                    <?php if ($liqV > 0): ?>
                    <span class="small text-nowrap" style="color:#198754"><?= e(money_br($liqV)) ?> liq.</span>
                    <?php endif; ?>
                    <?php if ($empV > 0 && $pctLiqV > 0): ?>
                    <span class="badge rounded-pill <?= $pctLiqV >= 90 ? 'bg-success' : ($pctLiqV >= 50 ? 'bg-warning text-dark' : 'bg-secondary') ?>" style="min-width:46px">
                        <?= $pctLiqV ?>% liq.
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Barra externa = empenhado / atualizado -->
            <div class="vig-bar-track">
                <div class="vig-bar-emp" style="width:<?= $pctEmp ?>%">
                    <!-- Barra interna = liquidado / empenhado -->
                    <?php if ($liqV > 0 && $pctLiqV > 0): ?>
                    <div class="vig-bar-liq" style="width:<?= $pctLiqV ?>%"></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($aLiqV > 0.01 && $liqV > 0): ?>
            <div class="vig-sublabel">A liquidar: <?= e(money_br($aLiqV)) ?></div>
            <?php elseif ($empV == 0): ?>
            <div class="vig-sublabel text-muted">Sem empenhos nesta vigência</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if ($nVig > 1): ?>
        <div class="vig-total mt-3 pt-3">
            <div class="d-flex justify-content-between align-items-center small">
                <span class="text-muted">Total contratado: <strong class="text-body"><?= e(money_br($vAtualizado)) ?></strong></span>
                <span class="text-muted">Total empenhado: <strong class="text-body"><?= e(money_br($vExecutado)) ?></strong>
                    <?php if ($temLiquidacao): ?> · Liquidado: <strong style="color:#198754"><?= e(money_br($vLiquidado)) ?></strong><?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="gc-card p-4">
        <h2 class="show-section-title">Empenhos por vigência</h2>
        <?php foreach ($empAnos as $chave => $group):
            $labelEmp = preg_replace('/^\d+\|/', '', $chave);
        ?>
        <div class="show-emp-ano mb-4">
            <div class="show-emp-ano-header">
                <span class="show-emp-ano-label"><?= e($labelEmp) ?></span>
                <span class="show-emp-ano-total"><?= e(money_br($group['total'])) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm show-table mb-0">
                    <thead><tr><th>Empenho</th><th>Data</th><th class="text-end">Valor</th></tr></thead>
                    <tbody>
                    <?php foreach ($group['itens'] as $e): ?>
                        <tr class="<?= (float)$e['valor'] == 0 ? 'show-row-muted' : '' ?>">
                            <td class="font-monospace small"><?= e($e['empenho']) ?></td>
                            <td class="text-nowrap small"><?= e(date_br($e['data_empenho'])) ?></td>
                            <td class="text-end text-nowrap"><?= e(money_br($e['valor'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ── ABA: Itens ─────────────────────────────────────────────────────────── -->
<?php if ($itensAtivos): ?>
<div class="tab-pane fade" id="tab-itens">
    <div class="gc-card p-4">
        <h2 class="show-section-title">Itens contratados</h2>
        <div class="table-responsive">
            <table class="table show-table align-middle">
                <thead>
                <tr>
                    <th style="width:3rem">#</th>
                    <th>Descrição</th>
                    <th>Unid.</th>
                    <th class="text-end">Qtd</th>
                    <th class="text-end">Preço unit.</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($itensAtivos as $it): ?>
                <tr>
                    <td class="text-muted small"><?= (int)$it['item'] ?></td>
                    <td><?= e($it['descricao']) ?></td>
                    <td class="text-nowrap small text-muted"><?= e(trim($it['unidade'])) ?></td>
                    <td class="text-end"><?= number_format((float)$it['quantidade'], 0, ',', '.') ?></td>
                    <td class="text-end text-nowrap small"><?= e(money_br($it['preco_unitario'])) ?></td>
                    <td class="text-end text-nowrap fw-semibold"><?= e(money_br($it['preco_total'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="show-tfoot">
                    <td colspan="5" class="text-end fw-semibold">Total</td>
                    <td class="text-end text-nowrap fw-bold"><?= e(money_br(array_sum(array_column($itensAtivos, 'preco_total')))) ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
        <?php if ($itensInativos): ?>
        <details class="mt-3">
            <summary class="text-muted small c-pointer"><?= count($itensInativos) ?> itens sem quantidade (clique para expandir)</summary>
            <ul class="mt-2 mb-0 small text-muted ps-3">
                <?php foreach ($itensInativos as $it): ?>
                    <li><?= e($it['descricao']) ?></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── ABA: Histórico ─────────────────────────────────────────────────────── -->
<?php if ($timeline): ?>
<div class="tab-pane fade" id="tab-timeline">

    <!-- ── Resumo dos Aditivos ───────────────────────────────────────────────── -->
    <?php if (!empty($aditivos)): ?>
    <div class="gc-card p-4 mb-3">
        <h2 class="show-section-title mb-3"><i class="bi bi-bar-chart-steps me-2"></i>Resumo dos aditivos</h2>
        <div class="row g-3 mb-3">

            <!-- Bloco: valor original vs atual -->
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Valor original</div>
                    <div class="fw-bold"><?= e(money_br($vInicial)) ?></div>
                    <div class="text-muted small mt-2 mb-1">Valor atual</div>
                    <div class="fw-bold"><?= e(money_br($vAtualizado)) ?></div>
                    <div class="mt-2">
                        <?php $cls = $crescimentoValor > 0 ? 'text-danger' : ($crescimentoValor < 0 ? 'text-success' : 'text-muted'); ?>
                        <span class="<?= $cls ?> fw-semibold">
                            <?= $crescimentoValor > 0 ? '+' : '' ?><?= $crescimentoValor ?>%
                        </span>
                        <span class="text-muted small"> em relação ao original</span>
                    </div>
                </div>
            </div>

            <!-- Bloco: acréscimos e supressões -->
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Acréscimos</div>
                    <div class="fw-bold text-danger">+ <?= e(money_br($_totalAcrescimos)) ?></div>
                    <div class="text-muted small mt-2 mb-1">Supressões</div>
                    <div class="fw-bold text-success">− <?= e(money_br($_totalSupressoes)) ?></div>
                    <div class="text-muted small mt-2">Saldo líquido: <strong class="<?= ($_totalAcrescimos - $_totalSupressoes) >= 0 ? 'text-danger' : 'text-success' ?>"><?= ($_totalAcrescimos - $_totalSupressoes) >= 0 ? '+' : '' ?><?= e(money_br($_totalAcrescimos - $_totalSupressoes)) ?></strong></div>
                </div>
            </div>

            <!-- Bloco: prorrogações -->
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Prorrogações</div>
                    <div class="fw-bold fs-4"><?= $qtdProrrogacoes ?>×</div>
                    <?php if ($_totalMesesProrrog > 0): ?>
                    <div class="text-muted small mt-1">
                        <?= $_totalMesesProrrog ?> mes<?= $_totalMesesProrrog > 1 ? 'es' : '' ?> acrescidos no total
                    </div>
                    <?php endif; ?>
                    <?php if ($_ultimoAditivo): ?>
                    <div class="text-muted small mt-2">Último aditivo: <strong><?= e(date_br($_ultimoAditivo)) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bloco: tipos de aditivo -->
            <div class="col-sm-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-2">Tipos de aditivo</div>
                    <?php if ($_tiposContagem): ?>
                        <?php foreach ($_tiposContagem as $tipo => $qtd): ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small"><?= e($tipo ?: 'Sem tipo') ?></span>
                            <span class="badge text-bg-secondary"><?= $qtd ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted small">—</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Mini Timeline Visual -->
        <?php if (!empty($_timelineEvents) && $tInicio && $tTermino): ?>
        <div class="mt-3">
            <div class="text-muted small mb-2 fw-semibold">Linha do tempo dos aditivos</div>
            <div class="position-relative rounded" style="height:36px;background:linear-gradient(90deg,#e9f5ff 0%,#dbeafe 100%);border:1px solid #bfdbfe;">
                <!-- Barra de progresso do tempo decorrido -->
                <div class="position-absolute top-0 start-0 h-100 rounded-start" style="width:<?= $_pctHoje ?? 0 ?>%;background:rgba(59,130,246,0.12);pointer-events:none;"></div>
                <!-- Hoje -->
                <?php if (isset($_pctHoje) && $_pctHoje > 0 && $_pctHoje < 100): ?>
                <div class="position-absolute top-0 h-100 d-flex align-items-center" style="left:<?= $_pctHoje ?>%;transform:translateX(-50%);z-index:3;" title="Hoje">
                    <div style="width:2px;height:100%;background:#3b82f6;"></div>
                </div>
                <?php endif; ?>
                <!-- Marcadores de aditivos -->
                <?php foreach ($_timelineEvents as $_ev):
                    $cor = str_contains($_ev['tipo'], 'prorrog') ? '#f59e0b'
                         : (str_contains($_ev['tipo'], 'acr') ? '#ef4444'
                         : (str_contains($_ev['tipo'], 'supr') ? '#10b981' : '#8b5cf6'));
                ?>
                <div class="position-absolute top-0 h-100 d-flex align-items-center" style="left:<?= $_ev['pct'] ?>%;transform:translateX(-50%);z-index:2;" title="<?= e($_ev['label']) ?>">
                    <div style="width:3px;height:70%;background:<?= $cor ?>;border-radius:2px;"></div>
                </div>
                <?php endforeach; ?>
                <!-- Labels início/fim -->
                <span class="position-absolute start-0 bottom-0 text-muted" style="font-size:10px;padding:0 4px;line-height:1.2;"><?= e(date_br($dataInicio)) ?></span>
                <span class="position-absolute end-0 bottom-0 text-muted" style="font-size:10px;padding:0 4px;line-height:1.2;"><?= e(date_br($dataTermino)) ?></span>
            </div>
            <div class="d-flex gap-3 mt-1" style="font-size:11px;color:#64748b;">
                <span><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:2px;vertical-align:middle;"></span> Prorrogação</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:2px;vertical-align:middle;"></span> Acréscimo</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#10b981;border-radius:2px;vertical-align:middle;"></span> Supressão</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#8b5cf6;border-radius:2px;vertical-align:middle;"></span> Outros</span>
                <span><span style="display:inline-block;width:2px;height:10px;background:#3b82f6;vertical-align:middle;"></span> Hoje</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Alertas Legais -->
        <?php if (!empty($_alertas)): ?>
        <div class="mt-3">
            <?php foreach ($_alertas as [$nivel, $ico, $msg]): ?>
            <div class="d-flex align-items-start gap-2 p-2 mb-1 rounded border-start border-3 border-<?= $nivel ?>" style="background:var(--bs-<?= $nivel ?>-bg-subtle,#f8f9fa);">
                <i class="bi <?= $ico ?> text-<?= $nivel ?> mt-1 flex-shrink-0"></i>
                <span class="small"><?= e($msg) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Projeção de Saldo -->
        <?php if ($_projMeses !== null && $burnRateMensal > 0): ?>
        <div class="mt-3 p-3 border rounded" style="background:#f0fdf4;">
            <div class="fw-semibold small mb-1"><i class="bi bi-graph-up-arrow text-success me-1"></i>Projeção de esgotamento do saldo</div>
            <div class="row g-2 small text-muted">
                <div class="col-auto">Burn rate mensal: <strong class="text-dark"><?= e(money_br($burnRateMensal)) ?>/mês</strong></div>
                <div class="col-auto">Saldo restante: <strong class="text-dark"><?= e(money_br(max(0, $vAtualizado - $vExecutado))) ?></strong></div>
                <div class="col-auto">
                    <?php if ($_projMeses <= 0): ?>
                        <strong class="text-danger">Saldo já esgotado.</strong>
                    <?php else: ?>
                        Saldo se esgota em aprox. <strong class="text-dark"><?= $_projMeses ?> mes<?= $_projMeses > 1 ? 'es' : '' ?></strong>
                        (<?= $_projData ?>)<?= ($tTermino && strtotime("+{$_projMeses} months") > $tTermino) ? ' — <span class="text-warning fw-semibold">após o término do contrato</span>' : '' ?>.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Waterfall de Evolução do Valor -->
        <?php if (count($_waterfall) > 1): ?>
        <div class="mt-3">
            <div class="text-muted small fw-semibold mb-2">Evolução do valor contratual</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:13px;">
                    <thead><tr>
                        <th class="text-muted fw-normal">Evento</th>
                        <th class="text-muted fw-normal">Data</th>
                        <th class="text-muted fw-normal text-end">Variação</th>
                        <th class="text-muted fw-normal text-end">Valor acumulado</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($_waterfall as $_wf): ?>
                    <tr>
                        <td><?= e($_wf['label']) ?></td>
                        <td class="text-muted"><?= $_wf['data'] ? e(date_br($_wf['data'])) : '—' ?></td>
                        <td class="text-end">
                            <?php if ($_wf['delta'] > 0): ?>
                                <span class="text-danger">+<?= e(money_br($_wf['delta'])) ?></span>
                            <?php elseif ($_wf['delta'] < 0): ?>
                                <span class="text-success">−<?= e(money_br(abs($_wf['delta']))) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold"><?= e(money_br($_wf['valor'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php
        // Gera frase-resumo automática
        $_frases = [];
        $totalAd = count($aditivos);
        $_frases[] = $totalAd === 1 ? '1 aditivo registrado.' : "{$totalAd} aditivos registrados.";
        if ($qtdProrrogacoes > 0) {
            $f = $qtdProrrogacoes === 1 ? '1 prorrogação' : "{$qtdProrrogacoes} prorrogações";
            $_frases[] = $f . ($_totalMesesProrrog > 0 ? ", somando {$_totalMesesProrrog} meses de vigência adicional" : '') . '.';
        }
        if ($_totalAcrescimos > 0 || $_totalSupressoes > 0) {
            $saldo = $_totalAcrescimos - $_totalSupressoes;
            $dir   = $saldo >= 0 ? 'acréscimo' : 'redução';
            $_frases[] = 'Variação financeira líquida de ' . ($saldo >= 0 ? '+' : '−') . money_br(abs($saldo)) . " ({$dir}).";
        }
        if (abs($crescimentoValor) > 0) {
            $_frases[] = "O valor contratual " . ($crescimentoValor > 0 ? 'cresceu' : 'reduziu') . ' ' . abs($crescimentoValor) . '% em relação ao original.';
        }
        ?>
        <div class="alert alert-light border-start border-3 border-primary py-2 px-3 mb-0 mt-3">
            <i class="bi bi-lightbulb text-primary me-2"></i>
            <?= implode(' ', array_map('e', $_frases)) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Painel de Controle de Prazo ─────────────────────────────────────── -->
    <?php if ($diasRestantes !== null): ?>
    <div class="gc-card p-4 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h2 class="show-section-title mb-0">Controle de prazo</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="prazo-score-badge bg-<?= $scoreCls ?>">
                    Score de risco: <strong><?= $scoreRisco ?>/100</strong> — <?= $scoreLabel ?>
                </span>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <!-- Semáforo de vencimento -->
            <div class="col-md-4">
                <div class="prazo-card prazo-card-<?= $diasRestantes < 0 ? 'expired' : ($diasRestantes < 30 ? 'danger' : ($diasRestantes < 90 ? 'warning' : 'ok')) ?>">
                    <div class="prazo-card-icon">
                        <i class="bi <?= $diasRestantes < 0 ? 'bi-x-circle-fill' : ($diasRestantes < 30 ? 'bi-exclamation-triangle-fill' : ($diasRestantes < 90 ? 'bi-clock-fill' : 'bi-check-circle-fill')) ?>"></i>
                    </div>
                    <div class="prazo-card-body">
                        <div class="prazo-card-label">Vencimento</div>
                        <div class="prazo-card-value">
                            <?php if ($diasRestantes < 0): ?>
                                Vencido há <?= abs($diasRestantes) ?> dias
                            <?php elseif ($diasRestantes === 0): ?>
                                Vence hoje
                            <?php else: ?>
                                <?= $diasRestantes ?> dias restantes
                            <?php endif; ?>
                        </div>
                        <div class="prazo-card-sub"><?= e(date_br($dataTermino)) ?></div>
                    </div>
                </div>
            </div>

            <!-- Prazo legal consumido -->
            <div class="col-md-4">
                <div class="prazo-card prazo-card-<?= $pctLegal >= 90 ? 'danger' : ($pctLegal >= 70 ? 'warning' : 'ok') ?>">
                    <div class="prazo-card-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="prazo-card-body">
                        <div class="prazo-card-label">Prazo legal (60 meses)</div>
                        <div class="prazo-card-value"><?= $mesesTotais ?> de 60 meses</div>
                        <div class="prazo-legal-bar mt-1">
                            <div class="prazo-legal-fill" style="width:<?= $pctLegal ?>%"></div>
                        </div>
                        <div class="prazo-card-sub mt-1"><?= $pctLegal ?>% consumido · <?= max(0, round(60 - $mesesTotais, 1)) ?> meses disponíveis</div>
                    </div>
                </div>
            </div>

            <!-- Lead time / processo -->
            <div class="col-md-4">
                <div class="prazo-card prazo-card-<?= $leadTimeVenceu ? 'danger' : ($diasRestantes < 90 ? 'warning' : 'ok') ?>">
                    <div class="prazo-card-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="prazo-card-body">
                        <div class="prazo-card-label">Iniciar processo de prorrogação</div>
                        <div class="prazo-card-value"><?= e(date_br($dataInicioProc)) ?></div>
                        <div class="prazo-card-sub">
                            <?= $qtdProrrogacoes ?> prorrogação(ões) realizada(s)
                            <?php if ($leadTimeVenceu && $diasRestantes > 0): ?>
                            · <span class="text-danger fw-semibold">Processo atrasado</span>
                            <?php elseif (!$leadTimeVenceu): ?>
                            · <?= max(0, round((strtotime($dataInicioProc) - $hoje) / 86400)) ?> dias para iniciar
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Simulador de prorrogação -->
        <?php if ($diasRestantes > 0 && $pctLegal < 100): ?>
        <div class="prazo-simulador-header" id="btn-simulador" role="button">
            <i class="bi bi-calculator me-2"></i>Simulador de prorrogação
            <i class="bi bi-chevron-down ms-auto" id="sim-chevron"></i>
        </div>
        <div id="prazo-simulador" style="display:none" class="prazo-simulador-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Prorrogar por</label>
                    <select class="form-select form-select-sm" id="sim-meses">
                        <option value="6">6 meses</option>
                        <option value="12" selected>12 meses</option>
                        <option value="18">18 meses</option>
                        <option value="24">24 meses</option>
                        <option value="custom">Outro...</option>
                    </select>
                    <input type="number" class="form-control form-control-sm mt-1" id="sim-meses-custom"
                           style="display:none" min="1" max="60" placeholder="Meses">
                </div>
                <div class="col-md-8">
                    <div class="prazo-sim-result" id="sim-result">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="sim-label">Nova data de término</div>
                                <div class="sim-value" id="sim-nova-data">—</div>
                            </div>
                            <div class="col-4">
                                <div class="sim-label">Prazo legal restante</div>
                                <div class="sim-value" id="sim-legal-rest">—</div>
                            </div>
                            <div class="col-4">
                                <div class="sim-label">Valor estimado necessário</div>
                                <div class="sim-value" id="sim-valor">—</div>
                            </div>
                        </div>
                        <div class="sim-alerta mt-2" id="sim-alerta" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Linha do tempo ───────────────────────────────────────────────────── -->
    <div class="gc-card p-4">
        <h2 class="show-section-title">Linha do tempo</h2>
        <div class="gc-timeline">
        <?php foreach ($timeline as $entry):
            $isProrrog = false;
            $tipos     = [];
            $valorNet  = 0;
            $descricao = '';
            foreach ($entry['aditivos'] as $ad) {
                $tipos[]   = $ad['tipo_aditivo'];
                $valorNet += (float)($ad['valor_acrescido'] ?? 0) - (float)($ad['valor_suprimido'] ?? 0);
                if (!$descricao) $descricao = $ad['objeto'] ?? '';
                if ($isProrrogacao($ad)) $isProrrog = true;
            }
            $tipos = array_unique(array_filter($tipos));
            [$iconCls, $dotCls] = match(true) {
                $isProrrog                                                     => ['bi-arrow-repeat', 'dot-info'],
                str_contains(strtolower($entry['evento'] ?? ''), 'apostila')  => ['bi-pen', 'dot-warning'],
                !empty($tipos)                                                 => ['bi-file-earmark-text', 'dot-primary'],
                default                                                        => ['bi-circle', 'dot-muted'],
            };
        ?>
        <div class="gc-timeline-item">
            <div class="gc-timeline-dot <?= $dotCls ?>"><i class="bi <?= $iconCls ?>"></i></div>
            <div class="gc-timeline-card">
                <div class="gc-timeline-head">
                    <span class="gc-timeline-order"><?= $entry['evento'] ?? 'Aditivo' ?> #<?= $entry['ordem'] ?></span>
                    <span class="gc-timeline-date"><?= e(date_br($entry['data'])) ?></span>
                </div>
                <?php if ($tipos): ?>
                <div class="gc-timeline-tipos">
                    <?php foreach ($tipos as $t): ?><span class="gc-tipo-badge"><?= e($t) ?></span><?php endforeach; ?>
                    <?php if ($isProrrog):
                        $mesesAdd = 0;
                        foreach ($entry['aditivos'] as $ad) {
                            $mesesAdd += $mesesPorProrrog[$ad['numero_aditivo']] ?? 0;
                        }
                        if ($mesesAdd > 0):
                    ?>
                    <span class="gc-tipo-badge badge-prorrog-meses">+<?= $mesesAdd ?> meses</span>
                    <?php endif; endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($descricao): ?><p class="gc-timeline-desc"><?= e($descricao) ?></p><?php endif; ?>
                <?php if ($valorNet != 0): ?>
                <div class="gc-timeline-valor <?= $valorNet > 0 ? 'valor-pos' : 'valor-neg' ?>">
                    <?= $valorNet > 0 ? '▲ +' : '▼ ' ?><?= e(money_br(abs($valorNet))) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── ABA: Documentos ────────────────────────────────────────────────────── -->
<?php if ($documentos): ?>
<div class="tab-pane fade" id="tab-documentos">
    <div class="gc-card p-4">
        <h2 class="show-section-title">Documentos do contrato</h2>
        <div class="show-docs-grid">
        <?php foreach ($documentos as $doc): ?>
            <a href="https://tjpa.thema.inf.br/grp//documentos/svlet/download?numero=<?= (int)$doc['numero_doc'] ?>&validacao=<?= urlencode($validacao) ?>"
               target="_blank" rel="noopener" class="show-doc-card">
                <div class="show-doc-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                <div class="show-doc-body">
                    <div class="show-doc-name"><?= e($doc['identificacao'] ?? 'Documento') ?></div>
                    <div class="show-doc-meta"><?= e(date_br($doc['data_documento'])) ?> · <?= e($doc['tipo'] ?? '') ?></div>
                </div>
                <i class="bi bi-download show-doc-dl"></i>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── ABA: Licitação ────────────────────────────────────────────────────── -->
<?php if ($licitacaoContratos): ?>
<div class="tab-pane fade" id="tab-licitacao">
    <div class="gc-card p-4">
        <h2 class="show-section-title">Contratos da licitação <?= e($contract['licitacao_numero']) ?></h2>
        <p class="text-muted small mb-3">Todos os contratos originados do mesmo processo licitatório, com valor total consolidado.</p>
        <div class="table-responsive">
            <table class="table show-table align-middle">
                <thead><tr><th>Contrato</th><th>Fornecedor</th><th>Situação</th><th class="text-end">Valor atualizado</th><th></th></tr></thead>
                <tbody>
                <tr class="show-row-highlight">
                    <td class="fw-semibold"><?= e($chaveLabel) ?> <span class="badge bg-primary ms-1 small">Este</span></td>
                    <td><?= e($contract['fornecedor_nome']) ?></td>
                    <td><span class="badge <?= e(badge_class($contract['situacao'])) ?>"><?= e($contract['situacao']) ?></span></td>
                    <td class="text-end fw-semibold"><?= e(money_br($vAtualizado)) ?></td>
                    <td></td>
                </tr>
                <?php foreach ($licitacaoContratos as $lc):
                    preg_match('/^([A-Za-z]+)(\d+)\/(\d+)$/', $lc['chave'], $lp);
                    $lcLabel = !empty($lp) ? ucfirst(strtolower($lp[1])).' nº '.ltrim($lp[2],'0').'/'.$lp[3] : $lc['chave'];
                ?>
                <tr>
                    <td><?= e($lcLabel) ?></td>
                    <td><?= e($lc['fornecedor_nome']) ?></td>
                    <td><span class="badge <?= e(badge_class($lc['situacao'])) ?>"><?= e($lc['situacao']) ?></span></td>
                    <td class="text-end"><?= e(money_br($lc['valor_global_atualizado'])) ?></td>
                    <td class="text-end"><a href="<?= e(url('/contratos/'.$lc['id'])) ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="show-tfoot">
                    <td colspan="3" class="text-end fw-semibold">Total consolidado</td>
                    <td class="text-end fw-bold"><?= e(money_br($vAtualizado + array_sum(array_column($licitacaoContratos, 'valor_global_atualizado')))) ?></td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── ABA: Acompanhamento ──────────────────────────────────────────────── -->
<div class="tab-pane fade" id="tab-acompanhamento">
    <?php include __DIR__ . '/partials/acompanhamento.php'; ?>
</div>

</div><!-- /tab-content -->

<script>
(function () {
    const btn = document.getElementById('btn-liquidacoes');
    if (!btn) return;
    btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Buscando…';
        document.getElementById('liq-status').textContent = '';

        const fd = new FormData();
        fd.append('_csrf', btn.dataset.csrf);
        fetch(btn.dataset.url, { method: 'POST', body: fd })
        .then(r => {
            if (!r.ok && r.status !== 200) throw new Error('HTTP ' + r.status);
            return r.text().then(txt => {
                try { return JSON.parse(txt); }
                catch(e) { throw new Error('Resposta inválida do servidor (não é JSON). Verifique se está logado.'); }
            });
        })
        .then(data => {
            if (!data.success) throw new Error(data.error || 'Erro desconhecido');

            const fmt = v => 'R$ ' + parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            const vAtual = <?= (float)$vAtualizado ?>;
            const vEmp   = <?= (float)$vExecutado ?>;

            // Liquidado
            const liq    = parseFloat(data.total_liquidado);
            const pctLiq = vEmp > 0 ? Math.min(100, (liq / vEmp * 100).toFixed(1)) : 0;
            document.getElementById('val-liquidado').innerHTML = fmt(liq);
            document.getElementById('sub-liquidado').innerHTML = pctLiq + '% do empenhado · serviço confirmado';

            // A liquidar
            const aLiq = vEmp - liq;
            const elALiq = document.getElementById('val-a-liquidar');
            const elSubALiq = document.getElementById('sub-a-liquidar');
            if (elALiq) elALiq.innerHTML = fmt(aLiq);
            if (elSubALiq) elSubALiq.textContent = aLiq > 0 ? 'Empenhado ainda não liquidado' : 'Totalmente liquidado';

            // Pipeline note
            const note = document.querySelector('.show-fin-pipeline-note');
            if (note) note.textContent = aLiq > 0 ? 'Ainda a liquidar: ' + fmt(aLiq) : 'Totalmente liquidado';

            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Atualizar liquidações';
            document.getElementById('liq-status').textContent = data.atualizados + ' empenhos atualizados';
        })
        .catch(e => {
            document.getElementById('liq-status').textContent = 'Erro: ' + e.message;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Tentar novamente';
        })
        .finally(() => { btn.disabled = false; });
    });
})();

// Simulador de prorrogação
(function () {
    const btnSim   = document.getElementById('btn-simulador');
    const simBox   = document.getElementById('prazo-simulador');
    const chevron  = document.getElementById('sim-chevron');
    const selMeses = document.getElementById('sim-meses');
    const inpCustom = document.getElementById('sim-meses-custom');
    if (!btnSim) return;

    const dataTermino   = '<?= $dataTermino ?>';
    const mesesTotais   = <?= $mesesTotais ?? 'null' ?>;
    const limiteMax     = <?= $limiteMaxMeses ?>;
    const burnRate      = <?= $burnRateMensal ?>;

    function fmt(v) {
        return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function addMonths(dateStr, months) {
        const d = new Date(dateStr + 'T12:00:00');
        d.setMonth(d.getMonth() + months);
        return d.toISOString().split('T')[0];
    }
    function fmtBR(iso) {
        const [y,m,d] = iso.split('-');
        return d+'/'+m+'/'+y;
    }

    function calcular() {
        let m = parseInt(selMeses.value === 'custom' ? inpCustom.value : selMeses.value) || 0;
        if (!m || !dataTermino) return;
        const novaData    = addMonths(dataTermino, m);
        const novoTotal   = (mesesTotais || 0) + m;
        const restante    = Math.max(0, limiteMax - novoTotal).toFixed(1);
        const valorEst    = burnRate * m;
        const alerta      = document.getElementById('sim-alerta');

        document.getElementById('sim-nova-data').textContent   = fmtBR(novaData);
        document.getElementById('sim-legal-rest').textContent  = restante + ' meses';
        document.getElementById('sim-valor').textContent       = burnRate > 0 ? fmt(valorEst) : '—';

        if (novoTotal > limiteMax) {
            alerta.style.display = '';
            alerta.className = 'sim-alerta alert alert-danger py-2';
            alerta.textContent = '⚠ Prorrogação excede o limite legal de 60 meses. Reduza o período.';
        } else if (novoTotal > limiteMax * 0.9) {
            alerta.style.display = '';
            alerta.className = 'sim-alerta alert alert-warning py-2';
            alerta.textContent = 'Atenção: restam apenas ' + restante + ' meses dentro do limite legal após esta prorrogação.';
        } else {
            alerta.style.display = 'none';
        }
    }

    btnSim.addEventListener('click', function () {
        const aberto = simBox.style.display !== 'none';
        simBox.style.display = aberto ? 'none' : '';
        chevron.className = aberto ? 'bi bi-chevron-down ms-auto' : 'bi bi-chevron-up ms-auto';
        if (!aberto) calcular();
    });
    selMeses.addEventListener('change', function () {
        inpCustom.style.display = this.value === 'custom' ? '' : 'none';
        calcular();
    });
    inpCustom.addEventListener('input', calcular);
    calcular();
})();

// Sync datas de aditivos (prorrogações)
(function () {
    const btn = document.getElementById('btn-sync-aditivos-show');
    if (!btn) return;
    btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sincronizando…';
        const fd = new FormData();
        fd.append('_csrf', '<?= e(\GestContratos\Core\Csrf::token()) ?>');
        fetch('<?= url('/sync/aditivos-datas') ?>', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { location.reload(); }
                else { btn.innerHTML = 'Erro: ' + (d.error || '?'); btn.disabled = false; }
            })
            .catch(() => { btn.innerHTML = 'Falha na conexão'; btn.disabled = false; });
    });
})();
</script>

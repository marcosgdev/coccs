<?php
$cards = [
    ['Contratos vigentes', $stats['contratos_vigentes'] ?? 0, 'bi-journal-check', 'Aba Contratos Vigentes: conta registros importados em que SITUACAO foi convertida para Vigente.'],
    ['Contratos expirados', $stats['contratos_expirados'] ?? 0, 'bi-journal-x', 'Aba Contratos Vigentes: conta registros em que TERMINO e anterior a data atual.'],
    ['Vencendo em 30 dias', $stats['vencendo_30'] ?? 0, 'bi-clock', 'Aba Contratos Vigentes: usa RESTANTE = TERMINO - HOJE, faixa de 0 a 30 dias.'],
    ['Vencendo em 60 dias', $stats['vencendo_60'] ?? 0, 'bi-clock-history', 'Aba Contratos Vigentes: usa RESTANTE = TERMINO - HOJE, faixa de 31 a 60 dias.'],
    ['Vencendo em 90 dias', $stats['vencendo_90'] ?? 0, 'bi-calendar-event', 'Aba Contratos Vigentes: usa RESTANTE = TERMINO - HOJE, faixa de 61 a 90 dias.'],
    ['ARPs vigentes', $stats['arps_vigentes'] ?? 0, 'bi-folder-check', 'Aba Contratos Vigentes: tipo ARP com SITUACAO Vigente.'],
    ['Valor global atualizado', money_br($stats['valor_global_atualizado'] ?? 0), 'bi-currency-dollar', 'Aba Contratos Vigentes: soma VALOR GLOBAL ATUALIZADO. Valores muito altos devem ser conferidos no lote de importacao.', $stats['valor_global_atualizado'] ?? 0],
    ['Valor executado', money_br($stats['valor_executado'] ?? 0), 'bi-cash-stack', 'Aba Contratos Vigentes: soma VALOR EXECUTADO, derivado das abas M.11 Contratos execucao e ARP execucao quando havia formula/cache.', $stats['valor_executado'] ?? 0],
    ['Sem fiscal', $stats['sem_fiscal'] ?? 0, 'bi-person-dash', 'Aba Contratos Vigentes: contratos vigentes sem Fiscal Demandante e sem Fiscal Tecnico.'],
    ['Sem gestor', $stats['sem_gestor'] ?? 0, 'bi-person-x', 'Aba Contratos Vigentes: contratos vigentes sem Gestor do Contrato.'],
    ['Prorrogacoes fora do prazo', $stats['prorrogacoes_fora_prazo'] ?? 0, 'bi-exclamation-triangle', 'Aba Contratos Vigentes: compara DATA RECEBIMENTO DO EXPEDIENTE com TERMINO - parametro de antecedencia.'],
    ['Orcamento estimado vencido', $stats['orcamento_vencido'] ?? 0, 'bi-arrow-repeat', 'Aba Contratos Vigentes: regra PRAZO ORCAMENTO ESTIMADO; padrao atual confere mais de 365 dias.'],
];

$chartBlocks = [
    ['contratosSituacao', 'Contratos por situacao', $charts['situacao'], 'Aba Contratos Vigentes: agrupamento por SITUACAO convertida em regra do sistema.'],
    ['contratosBaseLegal', 'Contratos por base legal', $charts['baseLegal'], 'Aba Contratos Vigentes: agrupamento pelo campo BASE LEGAL.'],
    ['contratosNatureza', 'Contratos por natureza', $charts['natureza'], 'Aba Contratos Vigentes: agrupamento por NATUREZA DA CONTRATACAO.'],
    ['execucaoAno', 'Execucao financeira por ano', $charts['execucaoAno'], 'Abas M.11 Contratos execucao e ARP execucao: soma Valor Executado Ano por exercicio.'],
];

$listBlocks = [
    ['contratosSetor', 'Contratos por setor', $charts['setor'], 'Aba Contratos Vigentes: agrupamento pelo campo SETOR DEMANDANTE.', 'contratos'],
    ['cargaServidor', 'Carga de fiscalizacao por servidor', $charts['cargaServidor'], 'Aba Contratos Vigentes: soma aparicoes vigentes como gestor, fiscais e substitutos, equivalente aos COUNTIFS da aba Gestao e fiscalizacao atual.', 'vinculos'],
];

$rankStyle = static function (float $value, float $max): string {
    $ratio = $max > 0 ? min(1, max(0, $value / $max)) : 0;
    $width = 18 + ($ratio * 82);
    $alpha = 0.08 + ($ratio * 0.30);
    return '--rank-width: ' . number_format($width, 2, '.', '') . '%; --rank-alpha: ' . number_format($alpha, 2, '.', '') . ';';
};
?>

<div class="row g-3 mb-4">
    <?php foreach ($cards as $card): ?>
        <?php [$label, $value, $icon, $hint] = $card; $rawValue = $card[4] ?? null; ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card" aria-label="<?= e($label) ?>" tabindex="0" data-bs-toggle="tooltip" data-bs-title="<?= e($hint) ?>">
                <div class="metric-icon"><i class="bi <?= e($icon) ?>"></i></div>
                <div class="metric-value"><?= e($value) ?></div>
                <div class="metric-label"><?= e($label) ?></div>
                <?php if (is_numeric($rawValue) && (float) $rawValue >= 1000000000): ?>
                    <div class="small text-danger mt-2">Conferir valor importado</div>
                <?php endif; ?>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <?php foreach ($listBlocks as [$id, $heading, $rows, $hint, $suffix]): ?>
        <?php
        $max = 0.0;
        foreach ($rows as $row) {
            $max = max($max, (float) ($row['total'] ?? 0));
        }
        ?>
        <div class="col-12 col-xl-6">
            <section class="gc-card p-3" aria-labelledby="<?= e($id) ?>Title">
                <h2 class="h6 fw-bold mb-3" id="<?= e($id) ?>Title">
                    <?= e($heading) ?>
                    <button class="btn btn-sm btn-link p-0 ms-1" type="button" data-bs-toggle="tooltip" data-bs-title="<?= e($hint) ?>" aria-label="Origem de <?= e($heading) ?>">
                        <i class="bi bi-info-circle"></i>
                    </button>
                </h2>
                <?php if (empty($rows)): ?>
                    <p class="text-secondary mb-0">Sem dados para exibir.</p>
                <?php else: ?>
                    <div class="rank-list" role="list" aria-label="<?= e($heading) ?>">
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $total = (float) ($row['total'] ?? 0);
                            $high = $max > 0 && ($total / $max) >= .8;
                            ?>
                            <div class="rank-item <?= $high ? 'rank-high' : '' ?>" role="listitem" style="<?= e($rankStyle($total, $max)) ?>">
                                <div class="rank-bar" aria-hidden="true"></div>
                                <div class="rank-content">
                                    <div class="rank-label"><?= e($row['label'] ?? 'Sem informacao') ?></div>
                                    <div class="rank-value" aria-label="<?= e((string) $total . ' ' . $suffix) ?>"><?= e((string) (int) $total) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <?php foreach ($chartBlocks as [$id, $heading, $data, $hint]): ?>
        <div class="col-12 col-xl-6">
            <section class="gc-card p-3">
                <h2 class="h6 fw-bold mb-3">
                    <?= e($heading) ?>
                    <button class="btn btn-sm btn-link p-0 ms-1" type="button" data-bs-toggle="tooltip" data-bs-title="<?= e($hint) ?>" aria-label="Origem do grafico <?= e($heading) ?>">
                        <i class="bi bi-info-circle"></i>
                    </button>
                </h2>
                <canvas id="<?= e($id) ?>" height="150" aria-label="<?= e($heading) ?>" role="img"></canvas>
            </section>
        </div>
    <?php endforeach; ?>
</div>

<?php ob_start(); ?>
<script>
const chartSets = <?= json_encode($chartBlocks, JSON_UNESCAPED_UNICODE) ?>;
const palette = ['#002952', '#D4AF37', 'rgba(0, 41, 82, .72)', 'rgba(212, 175, 55, .72)', 'rgba(0, 41, 82, .42)', 'rgba(212, 175, 55, .42)'];
chartSets.forEach(([id, title, rows]) => {
  const canvas = document.getElementById(id);
  if (!canvas) return;
  const labels = rows.map(row => row.label || row.exercicio || row.servidor || 'Sem informacao');
  const values = rows.map(row => Number(row.total || row.valor_executado_exercicio || row.registros || 0));
  new Chart(canvas, {
    type: id === 'contratosSituacao' ? 'doughnut' : 'bar',
    data: { labels, datasets: [{ label: title, data: values, backgroundColor: palette, borderColor: '#FFFFFF', borderWidth: 1 }] },
    options: {
      responsive: true,
      plugins: { legend: { display: id === 'contratosSituacao' }, tooltip: { enabled: true } },
      scales: id === 'contratosSituacao' ? {} : { y: { beginAtZero: true } }
    }
  });
});
</script>
<?php $scripts = ob_get_clean(); ?>

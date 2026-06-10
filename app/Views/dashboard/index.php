<?php
$cards = [
    ['Contratos vigentes', $stats['contratos_vigentes'] ?? 0, 'bi-journal-check', 'Aba Contratos Vigentes: conta registros importados em que SITUAÇÃO foi convertida para Vigente.'],
    ['Contratos expirados', $stats['contratos_expirados'] ?? 0, 'bi-journal-x', 'Aba Contratos Vigentes: conta registros em que TÉRMINO é anterior à data atual.'],
    ['Vencendo em 30 dias', $stats['vencendo_30'] ?? 0, 'bi-clock', 'Aba Contratos Vigentes: usa RESTANTE = TÉRMINO - HOJE, faixa de 0 a 30 dias.'],
    ['Vencendo em 60 dias', $stats['vencendo_60'] ?? 0, 'bi-clock-history', 'Aba Contratos Vigentes: usa RESTANTE = TÉRMINO - HOJE, faixa de 31 a 60 dias.'],
    ['Vencendo em 90 dias', $stats['vencendo_90'] ?? 0, 'bi-calendar-event', 'Aba Contratos Vigentes: usa RESTANTE = TÉRMINO - HOJE, faixa de 61 a 90 dias.'],
    ['ARPs vigentes', $stats['arps_vigentes'] ?? 0, 'bi-folder-check', 'Aba Contratos Vigentes: tipo ARP com SITUAÇÃO Vigente.'],
    ['Valor global atualizado', money_br($stats['valor_global_atualizado'] ?? 0), 'bi-currency-dollar', 'Aba Contratos Vigentes: soma VALOR GLOBAL ATUALIZADO. Valores muito altos devem ser conferidos no lote de importação.', $stats['valor_global_atualizado'] ?? 0],
    ['Valor executado', money_br($stats['valor_executado'] ?? 0), 'bi-cash-stack', 'Aba Contratos Vigentes: soma VALOR EXECUTADO, derivado das abas M.11 Contratos execução e ARP execução quando havia fórmula/cache.', $stats['valor_executado'] ?? 0],
    ['Sem fiscal', $stats['sem_fiscal'] ?? 0, 'bi-person-dash', 'Aba Contratos Vigentes: contratos vigentes sem Fiscal Demandante e sem Fiscal Técnico.'],
    ['Sem gestor', $stats['sem_gestor'] ?? 0, 'bi-person-x', 'Aba Contratos Vigentes: contratos vigentes sem Gestor do Contrato.'],
    ['Prorrogacoes fora do prazo', $stats['prorrogacoes_fora_prazo'] ?? 0, 'bi-exclamation-triangle', 'Aba Contratos Vigentes: compara DATA RECEBIMENTO DO EXPEDIENTE com TÉRMINO - parâmetro de antecedência.'],
    ['Orcamento estimado vencido', $stats['orcamento_vencido'] ?? 0, 'bi-arrow-repeat', 'Aba Contratos Vigentes: regra PRAZO ORÇAMENTO ESTIMADO; padrão atual confere mais de 365 dias.'],
];
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

<div class="row g-3">
    <?php
    $chartBlocks = [
        ['contratosSituacao', 'Contratos por situacao', $charts['situacao'], 'Aba Contratos Vigentes: agrupamento por SITUAÇÃO convertida em regra do sistema.'],
        ['contratosSetor', 'Contratos por setor', $charts['setor'], 'Aba Contratos Vigentes: agrupamento pelo campo SETOR DEMANDANTE.'],
        ['contratosBaseLegal', 'Contratos por base legal', $charts['baseLegal'], 'Aba Contratos Vigentes: agrupamento pelo campo BASE LEGAL.'],
        ['contratosNatureza', 'Contratos por natureza', $charts['natureza'], 'Aba Contratos Vigentes: agrupamento por NATUREZA DA CONTRATAÇÃO.'],
        ['execucaoAno', 'Execucao financeira por ano', $charts['execucaoAno'], 'Abas M.11 Contratos execução e ARP execução: soma Valor Executado Ano por exercício.'],
        ['cargaServidor', 'Carga de fiscalizacao por servidor', $charts['cargaServidor'], 'Aba Contratos Vigentes: soma aparições vigentes como gestor, fiscais e substitutos, equivalente aos COUNTIFS da aba Gestão e fiscalização atual.'],
    ];
    ?>
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
const palette = ['#5B3FD6', '#8B7BE8', '#1F2937', '#94A3B8', '#F3C74D', '#0F766E', '#DC2626', '#2563EB'];
chartSets.forEach(([id, title, rows]) => {
  const canvas = document.getElementById(id);
  if (!canvas) return;
  const labels = rows.map(row => row.label || row.exercicio || row.servidor || 'Sem informacao');
  const values = rows.map(row => Number(row.total || row.valor_executado_exercicio || row.registros || 0));
  new Chart(canvas, {
    type: id === 'contratosSituacao' ? 'doughnut' : 'bar',
    data: { labels, datasets: [{ label: title, data: values, backgroundColor: palette }] },
    options: {
      responsive: true,
      plugins: { legend: { display: id === 'contratosSituacao' }, tooltip: { enabled: true } },
      scales: id === 'contratosSituacao' ? {} : { y: { beginAtZero: true } }
    }
  });
});
</script>
<?php $scripts = ob_get_clean(); ?>

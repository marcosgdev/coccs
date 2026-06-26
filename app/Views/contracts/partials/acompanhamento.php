<?php
$cid      = $contract['id'];
$canWrite = GestContratos\Core\Auth::canWrite();

$tipoLabel = [
    'geral'       => ['Geral',       'secondary', 'bi-chat-left-text'],
    'reajuste'    => ['Reajuste',    'info',      'bi-arrow-up-circle'],
    'prorrogacao' => ['Prorrogação', 'warning',   'bi-calendar-plus'],
    'alerta'      => ['Alerta',      'danger',    'bi-exclamation-triangle'],
];
$resultadoLabel = [
    'aprovado'   => ['Aprovado',    'success'],
    'negado'     => ['Negado',      'danger'],
    'em_analise' => ['Em análise',  'warning'],
    'pendente'   => ['Pendente',    'secondary'],
];
$tipoProrrogLabel = [
    'legal'        => 'Prazo Legal',
    'emergencial'  => 'Emergencial',
    'excepcional'  => 'Excepcional',
];
?>

<div class="gc-card p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="show-section-title mb-0"><i class="bi bi-journal-check me-2"></i>Acompanhamento</h2>
        <?php if ($canWrite): ?>
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#form-acomp">
            <i class="bi bi-plus-lg me-1"></i>Nova anotação
        </button>
        <?php endif; ?>
    </div>

    <?php if ($canWrite): ?>
    <!-- Formulário colapsável -->
    <div class="collapse mb-4" id="form-acomp">
        <div class="border rounded p-3 bg-light">
            <form method="post" action="<?= e(url('/contratos/' . $cid . '/acompanhamento')) ?>" id="form-acomp-inner">
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tipo <span class="text-danger">*</span></label>
                        <select name="tipo" id="acomp-tipo" class="form-select form-select-sm" required>
                            <option value="geral">Anotação Geral</option>
                            <option value="reajuste">Reajuste</option>
                            <option value="prorrogacao">Controle de Prorrogação</option>
                            <option value="alerta">Alerta</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Data de referência</label>
                        <input type="date" name="data_referencia" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Título</label>
                        <input type="text" name="titulo" class="form-control form-control-sm" placeholder="Resumo breve…">
                    </div>
                </div>

                <!-- Campos: Prorrogação -->
                <div id="fields-prorrogacao" class="acomp-fields d-none">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Tipo de prorrogação</label>
                            <select name="tipo_prorrogacao" class="form-select form-select-sm">
                                <option value="">— Selecione —</option>
                                <option value="legal">Prazo Legal</option>
                                <option value="emergencial">Emergencial</option>
                                <option value="excepcional">Excepcional</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Prazo para apresentação</label>
                            <input type="date" name="prazo_apresentacao" class="form-control form-control-sm">
                            <div class="form-text">Data limite para solicitar</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Apresentado em</label>
                            <input type="date" name="apresentado_em" class="form-control form-control-sm">
                            <div class="form-text">Data real da solicitação</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Resultado</label>
                            <select name="resultado" class="form-select form-select-sm">
                                <option value="pendente">Pendente</option>
                                <option value="em_analise">Em análise</option>
                                <option value="aprovado">Aprovado</option>
                                <option value="negado">Negado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Campos: Reajuste -->
                <div id="fields-reajuste" class="acomp-fields d-none">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Índice</label>
                            <input type="text" name="indice_reajuste" class="form-control form-control-sm" placeholder="Ex: IPCA, INPC, IGP-M…">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Percentual (%)</label>
                            <input type="number" name="percentual_reajuste" class="form-control form-control-sm" step="0.0001" placeholder="Ex: 5.79">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Valor anterior (R$)</label>
                            <input type="number" name="valor_anterior" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Valor reajustado (R$)</label>
                            <input type="number" name="valor_reajustado" class="form-control form-control-sm" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Descrição / Observações</label>
                    <textarea name="descricao" class="form-control form-control-sm" rows="3" placeholder="Detalhes da anotação…"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#form-acomp">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timeline de anotações -->
    <?php if (empty($acompanhamentos)): ?>
        <p class="text-muted small mb-0">Nenhuma anotação registrada ainda.</p>
    <?php else: ?>
    <div class="acomp-timeline">
        <?php foreach ($acompanhamentos as $item):
            [$tLabel, $tCor, $tIco] = $tipoLabel[$item['tipo']] ?? ['Geral', 'secondary', 'bi-chat-left-text'];
            $dentroPrazo = $item['dentro_prazo'];
        ?>
        <div class="acomp-item border rounded p-3 mb-2">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge text-bg-<?= $tCor ?>"><i class="bi <?= $tIco ?> me-1"></i><?= $tLabel ?></span>

                    <?php if ($item['tipo'] === 'prorrogacao' && $item['tipo_prorrogacao']): ?>
                        <span class="badge text-bg-light border"><?= e($tipoProrrogLabel[$item['tipo_prorrogacao']] ?? $item['tipo_prorrogacao']) ?></span>
                    <?php endif; ?>

                    <?php if ($item['tipo'] === 'prorrogacao' && $dentroPrazo !== null): ?>
                        <?php if ($dentroPrazo): ?>
                            <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Dentro do prazo</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Fora do prazo</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($item['resultado'] && $item['tipo'] === 'prorrogacao'):
                        [$rLabel, $rCor] = $resultadoLabel[$item['resultado']] ?? [$item['resultado'], 'secondary'];
                    ?>
                        <span class="badge text-bg-<?= $rCor ?>"><?= e($rLabel) ?></span>
                    <?php endif; ?>

                    <?php if ($item['titulo']): ?>
                        <strong class="small"><?= e($item['titulo']) ?></strong>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small"><?= $item['data_referencia'] ? e(date_br($item['data_referencia'])) : '' ?></span>
                    <span class="text-muted small">·</span>
                    <span class="text-muted small"><?= e($item['autor'] ?? 'Sistema') ?></span>
                    <?php if ($canWrite): ?>
                    <form method="post" action="<?= e(url('/contratos/' . $cid . '/acompanhamento/' . $item['id'] . '/excluir')) ?>" class="d-inline" onsubmit="return confirm('Remover esta anotação?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Remover"><i class="bi bi-trash3"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($item['tipo'] === 'prorrogacao' && ($item['prazo_apresentacao'] || $item['apresentado_em'])): ?>
            <div class="mt-2 d-flex gap-3 flex-wrap" style="font-size:12px;color:#64748b;">
                <?php if ($item['prazo_apresentacao']): ?>
                    <span><i class="bi bi-calendar-check me-1"></i>Prazo limite: <strong><?= e(date_br($item['prazo_apresentacao'])) ?></strong></span>
                <?php endif; ?>
                <?php if ($item['apresentado_em']): ?>
                    <span><i class="bi bi-calendar-event me-1"></i>Apresentado em: <strong><?= e(date_br($item['apresentado_em'])) ?></strong></span>
                <?php endif; ?>
                <?php if ($item['prazo_apresentacao'] && $item['apresentado_em']):
                    $diff = (int) round((strtotime($item['apresentado_em']) - strtotime($item['prazo_apresentacao'])) / 86400);
                    if ($diff > 0): ?>
                        <span class="text-danger"><i class="bi bi-clock me-1"></i><?= $diff ?> dia<?= $diff > 1 ? 's' : '' ?> de atraso</span>
                    <?php elseif ($diff < 0): ?>
                        <span class="text-success"><i class="bi bi-clock me-1"></i><?= abs($diff) ?> dia<?= abs($diff) > 1 ? 's' : '' ?> de antecedência</span>
                    <?php else: ?>
                        <span class="text-warning"><i class="bi bi-clock me-1"></i>Apresentado no limite</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($item['tipo'] === 'reajuste' && ($item['indice_reajuste'] || $item['percentual_reajuste'])): ?>
            <div class="mt-2 d-flex gap-3 flex-wrap" style="font-size:12px;color:#64748b;">
                <?php if ($item['indice_reajuste']): ?>
                    <span><i class="bi bi-graph-up me-1"></i>Índice: <strong><?= e($item['indice_reajuste']) ?></strong></span>
                <?php endif; ?>
                <?php if ($item['percentual_reajuste'] !== null): ?>
                    <span><i class="bi bi-percent me-1"></i>Percentual: <strong><?= number_format((float)$item['percentual_reajuste'], 2, ',', '.') ?>%</strong></span>
                <?php endif; ?>
                <?php if ($item['valor_anterior']): ?>
                    <span>Valor anterior: <strong><?= e(money_br($item['valor_anterior'])) ?></strong></span>
                <?php endif; ?>
                <?php if ($item['valor_reajustado']): ?>
                    <span>Valor reajustado: <strong class="text-danger"><?= e(money_br($item['valor_reajustado'])) ?></strong></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($item['descricao']): ?>
            <p class="mb-0 mt-2 small text-secondary" style="white-space:pre-line"><?= e($item['descricao']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var sel = document.getElementById('acomp-tipo');
    if (!sel) return;
    function toggle() {
        document.querySelectorAll('.acomp-fields').forEach(function (el) { el.classList.add('d-none'); });
        var target = document.getElementById('fields-' + sel.value);
        if (target) target.classList.remove('d-none');
    }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>

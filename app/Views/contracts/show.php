<div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
    <a class="btn btn-outline-primary" href="<?= e(url('/contratos/' . $contract['id'] . '/editar')) ?>"><i class="bi bi-pencil"></i> Editar</a>
    <form method="post" action="<?= e(url('/contratos/' . $contract['id'] . '/duplicar')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-files"></i> Duplicar</button>
    </form>
    <form method="post" action="<?= e(url('/contratos/' . $contract['id'] . '/estrategico')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-warning" type="submit"><i class="bi bi-star"></i> Estrategico</button>
    </form>
    <form method="post" action="<?= e(url('/contratos/' . $contract['id'] . '/notificacao')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit"><i class="bi bi-bell"></i> Gerar notificacao</button>
    </form>
</div>

<section class="gc-card p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-3">
        <div>
            <h2 class="h4 fw-bold mb-1"><?= e($contract['chave']) ?></h2>
            <p class="text-secondary mb-0"><?= e($contract['fornecedor_nome']) ?></p>
        </div>
        <div class="d-flex gap-2 align-items-start">
            <span class="badge <?= e(badge_class($contract['situacao'])) ?>"><?= e($contract['situacao']) ?></span>
            <span class="badge <?= e(badge_class($contract['prazo'])) ?>"><?= e($contract['prazo']) ?></span>
            <?php if ($contract['contrato_estrategico']): ?><span class="badge text-bg-warning">Estrategico</span><?php endif; ?>
        </div>
    </div>
    <hr>
    <dl class="row detail-list">
        <?php
        $details = [
            'Tipo' => $contract['tipo'], 'Numero/Ano' => $contract['numero'] . '/' . $contract['ano'],
            'CNPJ/CPF' => $contract['cnpj_cpf'], 'Setor' => $contract['setor_nome'],
            'Natureza' => $contract['natureza_contratacao_nome'], 'Forma' => $contract['forma_contratacao_nome'],
            'Base legal' => $contract['base_legal_nome'], 'Processo' => $contract['processo'],
            'Inicio' => date_br($contract['data_inicio']), 'Termino' => date_br($contract['data_termino']),
            'Dias restantes' => $contract['dias_restantes'] ?? 'Indeterminado',
            'Trimestre' => $contract['trimestre_vencimento'],
            'Prazo prorrogacao' => date_br($contract['prazo_prorrogacao']),
            'Prorrogacao' => $contract['prorrogacao_no_prazo'],
            'Prazo legal' => $contract['prazo_legal_classificacao'],
            'Reajuste' => $contract['status_reajuste'],
            'Valor inicial' => money_br($contract['valor_global_inicial']),
            'Valor atualizado' => money_br($contract['valor_global_atualizado']),
            'Valor executado' => money_br($contract['valor_executado']),
            'Valor acumulado' => money_br($contract['valor_acumulado_executado']),
        ];
        ?>
        <?php foreach ($details as $label => $value): ?>
            <div class="col-12 col-md-6 col-xl-4 mb-3">
                <dt><?= e($label) ?></dt>
                <dd><?= e($value ?: '-') ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <section class="gc-card p-4 h-100">
            <h2 class="h5 fw-bold">Equipe</h2>
            <dl class="detail-list">
                <dt>Gestor</dt><dd><?= e($contract['gestor'] ?: '-') ?></dd>
                <dt>Gestor substituto</dt><dd><?= e($contract['gestor_substituto'] ?: '-') ?></dd>
                <dt>Fiscal demandante</dt><dd><?= e($contract['fiscal_demandante'] ?: '-') ?></dd>
                <dt>Fiscal tecnico</dt><dd><?= e($contract['fiscal_tecnico'] ?: '-') ?></dd>
                <dt>Fiscal substituto</dt><dd><?= e($contract['fiscal_substituto'] ?: '-') ?></dd>
                <dt>Fiscal administrativo</dt><dd><?= e($contract['fiscal_administrativo'] ?: '-') ?></dd>
                <dt>E-mails</dt><dd><?= e($contract['emails_equipe'] ?: '-') ?></dd>
            </dl>
        </section>
    </div>
    <div class="col-12 col-xl-6">
        <section class="gc-card p-4 h-100">
            <h2 class="h5 fw-bold">Notificacao automatica</h2>
            <div class="notification-text border rounded p-3 bg-light"><?= e($contract['texto_notificacao'] ?: 'Nenhum texto gerado.') ?></div>
        </section>
    </div>
    <div class="col-12">
        <section class="gc-card p-4">
            <h2 class="h5 fw-bold">Objeto e observacoes</h2>
            <p><?= nl2br(e($contract['objeto'])) ?></p>
            <hr>
            <p class="mb-0"><?= nl2br(e($contract['observacoes'])) ?></p>
        </section>
    </div>
</div>

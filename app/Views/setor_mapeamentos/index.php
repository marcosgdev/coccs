<?php $ativos = array_filter($rows, fn($r) => $r['ativo']); ?>

<!-- Formulário de nova regra -->
<div class="gc-card p-4 mb-4">
    <div class="fw-bold mb-1">Nova regra de mapeamento</div>
    <div class="small text-muted mb-3">
        Sempre que a sincronização receber <strong>Nome de origem</strong>, grava <strong>Nome correto</strong> no sistema.
        A regra é aplicada automaticamente em toda sincronização futura.
    </div>
    <form method="post" action="<?= e(url('/mapeamento-setores')) ?>">
        <?= csrf_field() ?>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Nome de origem <span class="text-danger">*</span></label>
                <input list="sugestoes-origem" name="nome_origem" class="form-control" required
                       placeholder="Ex: Almoxarifado Central">
                <datalist id="sugestoes-origem">
                    <?php foreach ($setoresBase as $s): ?>
                    <option value="<?= e($s) ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="form-text">Valor exato que vem da API/base de origem.</div>
            </div>
            <div class="col-auto d-flex align-items-center pb-1" style="font-size:1.3rem;color:#6c757d">→</div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Nome correto <span class="text-danger">*</span></label>
                <input list="sugestoes-destino" name="nome_destino" class="form-control" required
                       placeholder="Ex: Secretaria de Administração">
                <datalist id="sugestoes-destino">
                    <?php foreach ($setoresBase as $s): ?>
                    <option value="<?= e($s) ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="form-text">Nome que deve ser gravado no sistema.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Observação</label>
                <input name="observacao" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-plus-lg me-1"></i>Adicionar regra
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Aviso de aplicação -->
<?php if (count($ativos) > 0): ?>
<div class="alert alert-info d-flex align-items-start gap-2 mb-4" style="font-size:.85rem">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>
        <strong><?= count($ativos) ?> regra<?= count($ativos) !== 1 ? 's' : '' ?> ativa<?= count($ativos) !== 1 ? 's' : '' ?>.</strong>
        As regras são aplicadas automaticamente na próxima sincronização com a API.
        Para corrigir contratos já cadastrados, execute a sincronização em <a href="<?= e(url('/contratos')) ?>"><strong>Contratos</strong></a>.
    </div>
</div>
<?php endif; ?>

<!-- Tabela de regras -->
<div class="gc-card p-0 overflow-hidden">
    <?php if (!$rows): ?>
    <div class="p-5 text-center text-muted">
        <i class="bi bi-signpost-split" style="font-size:2rem;opacity:.3"></i>
        <p class="mt-3 mb-0">Nenhuma regra cadastrada ainda.</p>
    </div>
    <?php else: ?>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em">
            <tr>
                <th>Nome de origem (vem da API)</th>
                <th></th>
                <th>Nome correto (gravado no sistema)</th>
                <th>Observação</th>
                <th class="text-center">Status</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr class="<?= !$r['ativo'] ? 'text-muted' : '' ?>">
            <td>
                <span class="fw-semibold <?= !$r['ativo'] ? 'text-decoration-line-through' : '' ?>">
                    <?= e($r['nome_origem']) ?>
                </span>
            </td>
            <td style="color:#6c757d;font-size:1.1rem">→</td>
            <td class="fw-semibold text-primary"><?= e($r['nome_destino']) ?></td>
            <td class="small text-muted"><?= e($r['observacao'] ?? '—') ?></td>
            <td class="text-center">
                <form method="post" action="<?= e(url('/mapeamento-setores/' . $r['id'] . '/toggle')) ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="badge border-0 bg-<?= $r['ativo'] ? 'success' : 'secondary' ?>" style="cursor:pointer;font-size:.72rem">
                        <?= $r['ativo'] ? 'Ativa' : 'Inativa' ?>
                    </button>
                </form>
            </td>
            <td class="text-end">
                <form method="post" action="<?= e(url('/mapeamento-setores/' . $r['id'] . '/excluir')) ?>" class="d-inline"
                      onsubmit="return confirm('Remover esta regra de mapeamento?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

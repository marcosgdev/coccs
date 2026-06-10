<?php
$v = fn (string $key, mixed $default = '') => old($key, $contract[$key] ?? $default);
$input = function (string $name, string $label, string $type = 'text', string $col = 'col-12 col-md-6 col-xl-4', array $attrs = []) use ($v) {
    $attr = '';
    foreach ($attrs as $key => $value) {
        $attr .= ' ' . e($key) . '="' . e($value) . '"';
    }
    echo '<div class="' . e($col) . '">';
    echo '<label class="form-label" for="' . e($name) . '">' . e($label) . '</label>';
    echo '<input class="form-control" type="' . e($type) . '" id="' . e($name) . '" name="' . e($name) . '" value="' . e($v($name)) . '"' . $attr . '>';
    echo '</div>';
};
$textarea = function (string $name, string $label, int $rows = 3) use ($v) {
    echo '<div class="col-12"><label class="form-label" for="' . e($name) . '">' . e($label) . '</label>';
    echo '<textarea class="form-control" id="' . e($name) . '" name="' . e($name) . '" rows="' . $rows . '">' . e($v($name)) . '</textarea></div>';
};
?>

<form method="post" action="<?= e($action) ?>" class="gc-card p-4" novalidate>
    <?= csrf_field() ?>

    <h2 class="h5 fw-bold mb-3">Identificacao</h2>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4 col-xl-3">
            <label class="form-label" for="tipo">Tipo</label>
            <select class="form-select" id="tipo" name="tipo" required>
                <?php foreach (['CONTRATO', 'ARP'] as $option): ?>
                    <option value="<?= e($option) ?>" <?= $v('tipo', 'CONTRATO') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php $input('numero', 'Numero', 'text', 'col-12 col-md-4 col-xl-3', ['required' => 'required']); ?>
        <?php $input('ano', 'Ano', 'number', 'col-12 col-md-4 col-xl-3', ['required' => 'required']); ?>
        <?php $input('chave', 'Chave automatica (opcional)', 'text', 'col-12 col-md-6 col-xl-3'); ?>
        <?php $input('fornecedor_nome', 'Fornecedor', 'text', 'col-12 col-md-8', ['required' => 'required']); ?>
        <?php $input('cnpj_cpf', 'CNPJ/CPF', 'text', 'col-12 col-md-4'); ?>
        <?php $textarea('objeto', 'Objeto', 4); ?>
    </div>

    <h2 class="h5 fw-bold mb-3">Classificacao e processo</h2>
    <div class="row g-3 mb-4">
        <?php $input('natureza_contratacao_nome', 'Natureza da contratacao'); ?>
        <?php $input('forma_contratacao_nome', 'Forma de contratacao'); ?>
        <?php $input('tipo_contrato_nome', 'Tipo do contrato'); ?>
        <?php $input('licitacao_numero', 'Licitacao/dispensa/inexigibilidade'); ?>
        <?php $input('processo', 'Processo/protocolo'); ?>
        <?php $input('setor_nome', 'Setor demandante'); ?>
        <?php $input('base_legal_nome', 'Base legal', 'text', 'col-12 col-md-8'); ?>
        <div class="col-12 col-md-4">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="contrato_estrategico" name="contrato_estrategico" value="1" <?= $v('contrato_estrategico') ? 'checked' : '' ?>>
                <label class="form-check-label" for="contrato_estrategico">Contrato estrategico</label>
            </div>
        </div>
    </div>

    <h2 class="h5 fw-bold mb-3">Prazos e valores</h2>
    <div class="row g-3 mb-4">
        <?php $input('data_inicio', 'Inicio', 'date'); ?>
        <?php $input('data_termino', 'Termino', 'date'); ?>
        <?php $input('data_recebimento_prorrogacao', 'Recebimento da prorrogacao', 'date'); ?>
        <?php $input('data_orcamento_estimado', 'Data do orcamento estimado', 'date'); ?>
        <?php $input('valor_global_inicial', 'Valor global inicial', 'number', 'col-12 col-md-6 col-xl-3', ['step' => '0.01']); ?>
        <?php $input('valor_global_atualizado', 'Valor global atualizado', 'number', 'col-12 col-md-6 col-xl-3', ['step' => '0.01']); ?>
        <?php $input('valor_executado', 'Valor executado', 'number', 'col-12 col-md-6 col-xl-3', ['step' => '0.01']); ?>
        <?php $input('valor_acumulado_executado', 'Valor acumulado executado', 'number', 'col-12 col-md-6 col-xl-3', ['step' => '0.01']); ?>
        <?php $input('quantidade_aditivos', 'Quantidade de aditivos', 'number', 'col-12 col-md-6 col-xl-3'); ?>
    </div>

    <h2 class="h5 fw-bold mb-3">Gestao e fiscalizacao</h2>
    <div class="row g-3 mb-4">
        <?php $input('gestor', 'Gestor'); ?>
        <?php $input('gestor_substituto', 'Gestor substituto'); ?>
        <?php $input('fiscal_demandante', 'Fiscal demandante'); ?>
        <?php $input('fiscal_tecnico', 'Fiscal tecnico'); ?>
        <?php $input('fiscal_substituto', 'Fiscal substituto'); ?>
        <?php $input('fiscal_administrativo', 'Fiscal administrativo'); ?>
        <?php $textarea('emails_equipe', 'E-mails da equipe', 2); ?>
        <?php $textarea('observacoes', 'Observacoes', 3); ?>
        <?php $textarea('texto_notificacao', 'Texto de notificacao automatico', 5); ?>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/contratos')) ?>">Cancelar</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Salvar contrato</button>
    </div>
</form>

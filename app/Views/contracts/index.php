<?php $canWrite = GestContratos\Core\Auth::canWrite(); ?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-internos-btn" data-bs-toggle="tab" data-bs-target="#tab-internos" type="button" role="tab">
            <i class="bi bi-archive me-1"></i>Meus Contratos
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-tjpa-btn" data-bs-toggle="tab" data-bs-target="#tab-tjpa" type="button" role="tab">
            <i class="bi bi-cloud-download me-1"></i>Consulta TJPA
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ── Aba: Meus Contratos ──────────────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="tab-internos" role="tabpanel">

        <form class="filters mb-3" method="get" action="<?= e(url('/contratos')) ?>">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="q">Pesquisa</label>
                    <input class="form-control" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Chave, fornecedor, objeto...">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="ano">Ano</label>
                    <input class="form-control" id="ano" name="ano" value="<?= e($filters['ano'] ?? '') ?>">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="situacao">Situacao</label>
                    <select class="form-select" id="situacao" name="situacao">
                        <option value="">Todas</option>
                        <?php foreach (['Vigente', 'Expirado', 'Indeterminado'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= ($filters['situacao'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="setor_nome">Setor</label>
                    <input class="form-control" id="setor_nome" name="setor_nome" value="<?= e($filters['setor_nome'] ?? '') ?>">
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/contratos')) ?>" aria-label="Limpar filtros"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
        </form>

        <?php if ($canWrite): ?>
            <div class="d-flex justify-content-end gap-2 mb-3">
                <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#modalSyncTjpa">
                    <i class="bi bi-arrow-repeat me-1"></i>Sincronizar TJPA
                </button>
                <a class="btn btn-primary" href="<?= e(url('/contratos/novo')) ?>"><i class="bi bi-plus-lg"></i> Novo contrato</a>
            </div>
        <?php endif; ?>

        <!-- Modal de Sincronização TJPA -->
        <div class="modal fade" id="modalSyncTjpa" tabindex="-1" aria-labelledby="modalSyncTjpaLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSyncTjpaLabel"><i class="bi bi-arrow-repeat me-2"></i>Sincronizar com TJPA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="sync-csrf" value="<?= csrf_token() ?>">
                        <div id="sync-idle">
                            <p>Esta operação busca todos os <strong>contratos ativos</strong> na API de Dados Abertos do TJPA e sincroniza com o banco local:</p>
                            <ul class="small text-muted mb-3">
                                <li>Contratos novos serão <strong>criados</strong> automaticamente.</li>
                                <li>Contratos já existentes terão valores e datas <strong>atualizados</strong>.</li>
                                <li>Campos de gestão/fiscalização locais <strong>não são sobrescritos</strong>.</li>
                                <li>Pode levar de 30 s a 2 min dependendo do volume.</li>
                            </ul>
                            <div class="alert alert-warning mb-0" style="font-size:.82rem">
                                <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Sincronização do zero:</strong>
                                apaga todos os contratos locais (preserva ARPs) e reimporta tudo da API.
                                Use quando houver inconsistências nos dados.
                                <div class="mt-2">
                                    <button type="button" class="btn btn-danger btn-sm" id="sync-btn-reset">
                                        <i class="bi bi-trash3 me-1"></i>Apagar e reimportar do zero
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="sync-running" class="text-center py-3 d-none">
                            <div class="spinner-border text-success mb-3" role="status"></div>
                            <p class="mb-0" id="sync-running-msg">Consultando a API e sincronizando dados...</p>
                            <p class="text-muted small">Não feche esta janela.</p>
                        </div>
                        <div id="sync-result" class="d-none">
                            <div id="sync-result-body"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="sync-btn-close">Fechar</button>
                        <button type="button" class="btn btn-success" id="sync-btn-run">
                            <i class="bi bi-arrow-repeat me-1"></i>Sincronizar agora
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <section class="gc-card p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable w-100">
                    <thead>
                    <tr>
                        <th>Chave</th>
                        <th>Tipo</th>
                        <th>Fornecedor</th>
                        <th>Setor</th>
                        <th>Termino</th>
                        <th>Prazo</th>
                        <th>Situacao</th>
                        <th>Valor atualizado</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><a class="fw-semibold" href="<?= e(url('/contratos/' . $contract['id'])) ?>"><?= e($contract['chave']) ?></a></td>
                            <td><?= e($contract['tipo']) ?></td>
                            <td><?= e($contract['fornecedor_nome']) ?></td>
                            <td><?= e($contract['setor_nome']) ?></td>
                            <td><?= e(date_br($contract['data_termino'])) ?></td>
                            <td><span class="badge <?= e(badge_class($contract['prazo'])) ?>"><?= e($contract['prazo']) ?></span></td>
                            <td><span class="badge <?= e(badge_class($contract['situacao'])) ?>"><?= e($contract['situacao']) ?></span></td>
                            <td><?= e(money_br($contract['valor_global_atualizado'])) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/contratos/' . $contract['id'])) ?>" aria-label="Visualizar"><i class="bi bi-eye"></i></a>
                                <?php if ($canWrite): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/contratos/' . $contract['id'] . '/editar')) ?>" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div><!-- /tab-internos -->

    <!-- ── Aba: Consulta TJPA ───────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-tjpa" role="tabpanel">

        <section class="gc-card p-3 mb-3">
            <form id="tjpa-search-form" class="row g-3 align-items-end">
                <div class="col-12 col-lg-2">
                    <label class="form-label" for="tjpa-exercicio">Ano</label>
                    <input class="form-control" id="tjpa-exercicio" name="exercicio" placeholder="Ex.: 2024">
                </div>
                <div class="col-12 col-lg-2">
                    <label class="form-label" for="tjpa-numero">Nº Contrato</label>
                    <input class="form-control" id="tjpa-numero" name="numero" placeholder="Número">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="tjpa-nome">Fornecedor</label>
                    <input class="form-control" id="tjpa-nome" name="nomeContratado" placeholder="Nome parcial">
                </div>
                <div class="col-12 col-lg-2">
                    <label class="form-label" for="tjpa-doc">CNPJ / CPF</label>
                    <input class="form-control" id="tjpa-doc" name="documentoContratado" placeholder="Somente números">
                </div>
                <div class="col-12 col-lg-2">
                    <label class="form-label" for="tjpa-situacao">Situação</label>
                    <select class="form-select" id="tjpa-situacao" name="descricaoSituacaoContrato">
                        <option value="">Todas</option>
                        <option value="Ativo">Ativo</option>
                        <option value="Encerrado">Encerrado</option>
                        <option value="Rescindido">Rescindido</option>
                    </select>
                </div>
                <div class="col-12 col-lg-1 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
            <p class="text-muted small mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Quando o ano não for informado, a busca percorre todos os anos disponíveis (2020–2026) em paralelo — pode levar alguns segundos.
            </p>
        </section>

        <div id="tjpa-loading" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0">Consultando a API de Dados Abertos do TJPA...</p>
        </div>

        <div id="tjpa-results" class="d-none">
            <p class="text-secondary small mb-2" id="tjpa-count"></p>
            <section class="gc-card p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100">
                        <thead>
                        <tr>
                            <th>Nº Contrato</th>
                            <th>Ano</th>
                            <th>Fornecedor</th>
                            <th>Objeto</th>
                            <th>Unidade Gestora</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th class="text-end">Valor Original</th>
                            <th>Situação</th>
                        </tr>
                        </thead>
                        <tbody id="tjpa-tbody"></tbody>
                    </table>
                </div>
            </section>
        </div>

    </div><!-- /tab-tjpa -->

</div><!-- /tab-content -->

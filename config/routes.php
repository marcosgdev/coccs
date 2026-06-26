<?php

use GestContratos\Controllers\AdditivesController;
use GestContratos\Controllers\TrackingController;
use GestContratos\Controllers\SetorMapeamentosController;
use GestContratos\Controllers\SyncController;
use GestContratos\Controllers\TjpaApiController;
use GestContratos\Controllers\ArpsController;
use GestContratos\Controllers\AuditController;
use GestContratos\Controllers\AuthController;
use GestContratos\Controllers\AuxiliaryController;
use GestContratos\Controllers\ContractsController;
use GestContratos\Controllers\DashboardController;
use GestContratos\Controllers\DeadlinesController;
use GestContratos\Controllers\FinancialExecutionController;
use GestContratos\Controllers\ImportController;
use GestContratos\Controllers\ManualsController;
use GestContratos\Controllers\ManagementController;
use GestContratos\Controllers\NotificationsController;
use GestContratos\Controllers\ReportsController;
use GestContratos\Controllers\ServersController;
use GestContratos\Controllers\SettingsController;
use GestContratos\Controllers\SimpleResourceController;
use GestContratos\Controllers\UsersController;

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/perfil', [AuthController::class, 'profile']);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/contratos', [ContractsController::class, 'index']);
$router->get('/contratos/novo', [ContractsController::class, 'create']);
$router->post('/contratos', [ContractsController::class, 'store']);
$router->get('/contratos/{id}', [ContractsController::class, 'show']);
$router->get('/contratos/{id}/editar', [ContractsController::class, 'edit']);
$router->post('/contratos/{id}', [ContractsController::class, 'update']);
$router->post('/contratos/{id}/excluir', [ContractsController::class, 'delete']);
$router->post('/contratos/{id}/duplicar', [ContractsController::class, 'duplicate']);
$router->post('/contratos/{id}/encerrar', [ContractsController::class, 'close']);
$router->post('/contratos/{id}/estrategico', [ContractsController::class, 'toggleStrategic']);
$router->post('/contratos/{id}/notificacao', [ContractsController::class, 'generateNotification']);
$router->post('/contratos/{id}/liquidacoes', [ContractsController::class, 'syncLiquidacoes']);
$router->post('/contratos/{id}/acompanhamento', [TrackingController::class, 'store']);
$router->post('/contratos/{id}/acompanhamento/{aid}/excluir', [TrackingController::class, 'destroy']);

$resource = function (string $base, string $controller) use ($router): void {
    $router->get($base, [$controller, 'index']);
    $router->get($base . '/novo', [$controller, 'create']);
    $router->post($base, [$controller, 'store']);
    $router->get($base . '/{id}/editar', [$controller, 'edit']);
    $router->post($base . '/{id}', [$controller, 'update']);
    $router->post($base . '/{id}/excluir', [$controller, 'delete']);
};

$router->get('/arps', [ArpsController::class, 'index']);
$router->get('/arps/{id}', [ArpsController::class, 'show']);
$resource('/execucao-financeira', FinancialExecutionController::class);
$router->post('/aditivos/processo-status', [AdditivesController::class, 'processoStatus']);
$resource('/aditivos', AdditivesController::class);
$resource('/servidores', ServersController::class);
$resource('/usuarios', UsersController::class);
$resource('/configuracoes', SettingsController::class);

$simple = function (string $base, string $table, string $title, array $extra = [], array $roles = ['gestor-contratos'], bool $indexRequiresPermission = false) use ($router): void {
    $make = fn () => new SimpleResourceController($table, $title, $base, $extra, $roles, $indexRequiresPermission);
    $router->get($base, fn ($request) => $make()->index($request));
    $router->get($base . '/novo', fn ($request) => $make()->create());
    $router->post($base, fn ($request) => $make()->store($request));
    $router->get($base . '/{id}/editar', fn ($request, $id) => $make()->edit($request, $id));
    $router->post($base . '/{id}', fn ($request, $id) => $make()->update($request, $id));
    $router->post($base . '/{id}/excluir', fn ($request, $id) => $make()->delete($request, $id));
};

$adminOnly = ['administrador'];
$simple('/fornecedores', 'fornecedores', 'Fornecedores', [], $adminOnly, true);
$simple('/setores', 'setores', 'Setores', [], $adminOnly, true);
$simple('/naturezas-contratacao', 'naturezas_contratacao', 'Naturezas de Contratacao', [], $adminOnly, true);
$simple('/formas-contratacao', 'formas_contratacao', 'Formas de Contratacao', [], $adminOnly, true);
$simple('/tipos-contrato', 'tipos_contrato', 'Tipos de Contrato', [], $adminOnly, true);
$simple('/bases-legais', 'bases_legais', 'Bases Legais', [], $adminOnly, true);
$simple('/unidades', 'unidades', 'Unidades', [], $adminOnly, true);
$simple('/modelos-notificacao', 'modelos_notificacao', 'Modelos de Notificacao', [], $adminOnly, true);
$simple('/perfis', 'perfis', 'Perfis de Acesso', ['slug'], $adminOnly, true);

$router->get('/prazos', [DeadlinesController::class, 'index']);
$router->get('/gestao-fiscalizacao', [ManagementController::class, 'index']);
$router->get('/cadastros-auxiliares', [AuxiliaryController::class, 'index']);

$router->get('/notificacoes', [NotificationsController::class, 'index']);
$router->post('/notificacoes', [NotificationsController::class, 'store']);
$router->get('/notificacoes/redigir/{id}', [NotificationsController::class, 'compose']);
$router->post('/notificacoes/redigir/{id}', [NotificationsController::class, 'send']);
$router->post('/notificacoes/{id}/enviar', [NotificationsController::class, 'markSent']);

$router->get('/mapeamento-setores', [SetorMapeamentosController::class, 'index']);
$router->post('/mapeamento-setores', [SetorMapeamentosController::class, 'store']);
$router->post('/mapeamento-setores/{id}/toggle', [SetorMapeamentosController::class, 'toggle']);
$router->post('/mapeamento-setores/{id}/excluir', [SetorMapeamentosController::class, 'delete']);

$router->get('/relatorios', [ReportsController::class, 'index']);
$router->post('/relatorios/atas-valores', [ReportsController::class, 'uploadArpValues']);
$router->get('/relatorios/secretaria-pdf', [ReportsController::class, 'secretariaPdf']);
$router->get('/relatorios/secretaria-contratos', [ReportsController::class, 'secretariaContratos']);
$router->get('/relatorios/secretaria-arps', [ReportsController::class, 'secretariaArps']);
$router->get('/relatorios/bienios', [ReportsController::class, 'bienios']);
$router->get('/relatorios/aditivos-financeiros', [ReportsController::class, 'additivosFinanceiros']);
$router->get('/relatorios/prazo-vigencia', [ReportsController::class, 'prazoVigencia']);
$router->get('/relatorios/sem-gestor-fiscal', [ReportsController::class, 'semGestorFiscal']);

$router->get('/manuais/uso', [ManualsController::class, 'usage']);
$router->get('/manuais/manutencao', [ManualsController::class, 'maintenance']);
$router->get('/manuais/implantacao', [ManualsController::class, 'deployment']);

$router->get('/importacao', [ImportController::class, 'index']);
$router->post('/importacao/preview', [ImportController::class, 'preview']);
$router->post('/importacao/executar', [ImportController::class, 'run']);
$router->get('/importacao/duplicatas', [ImportController::class, 'duplicatas']);
$router->post('/importacao/duplicatas/limpar', [ImportController::class, 'limparDuplicatas']);
$router->post('/importacao/arp-execucao', [ImportController::class, 'arpExecution']);
$router->post('/importacao/lotes/{id}/desfazer', [ImportController::class, 'undo']);
$router->post('/importacao/lotes/{id}/excluir', [ImportController::class, 'deleteBatch']);
$router->get('/logs-importacao', [ImportController::class, 'logs']);

$router->get('/auditoria', [AuditController::class, 'index']);

$router->post('/sync/tjpa',            [SyncController::class, 'tjpa']);
$router->post('/sync/liquidacoes',     [SyncController::class, 'allLiquidacoes']);
$router->post('/sync/aditivos-datas',  [SyncController::class, 'syncAditivosDatas']);
$router->post('/sync/reset-contratos', [SyncController::class, 'resetContratos']);

$router->get('/api/tjpa/contratos', [TjpaApiController::class, 'contratos']);
$router->get('/api/tjpa/aditivos', [TjpaApiController::class, 'aditivos']);
$router->get('/api/tjpa/empenhos', [TjpaApiController::class, 'empenhos']);

<section class="gc-card manual-hero p-4 mb-4">
    <h2 class="h4 fw-bold">Manual de Manutencao Evolutiva e Corretiva</h2>
    <p class="mb-0 text-secondary">Referencia rapida para desenvolvedores, DBAs e equipes tecnicas manterem o GestContratos com seguranca.</p>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Visao geral da arquitetura</h2>
    <p>O sistema usa PHP com organizacao simples em MVC: controladores recebem a requisicao, modelos acessam o banco, servicos concentram regras de negocio e views renderizam HTML.</p>
    <ul>
        <li><strong>app/Controllers</strong>: fluxos de telas e permissoes.</li>
        <li><strong>app/Models</strong>: modelos de banco e consultas centrais.</li>
        <li><strong>app/Services</strong>: importacao, auditoria, upload e regras de contrato.</li>
        <li><strong>app/Views</strong>: templates HTML do sistema.</li>
        <li><strong>config/routes.php</strong>: mapa de URLs para controladores.</li>
        <li><strong>database/schema.sql</strong>: schema inicial e seeds essenciais.</li>
        <li><strong>public</strong>: entrada web, assets CSS/JS e imagens.</li>
        <li><strong>scripts</strong>: rotinas CLI de validacao, migracao e importacao.</li>
    </ul>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Banco de dados e tabelas principais</h2>
    <p>O banco MySQL/MariaDB armazena contratos, ARPs, execucoes financeiras, aditivos, notificacoes, logs, parametros, usuarios e perfis.</p>
    <ul>
        <li><strong>contratos</strong>: dados centrais, prazos, valores, equipe e situacao.</li>
        <li><strong>arps</strong>: atas de registro de precos e vigencia.</li>
        <li><strong>execucoes_financeiras</strong>: valores por exercicio.</li>
        <li><strong>import_batches</strong>: lotes de importacao e status.</li>
        <li><strong>logs_importacao</strong>: trilha detalhada de importacoes e simulacoes.</li>
        <li><strong>logs_auditoria</strong>: eventos de alteracao de dados.</li>
        <li><strong>parametros_sistema</strong>: limites de prazos e regras parametrizaveis.</li>
        <li><strong>usuarios/perfis</strong>: autenticacao e nivel de acesso.</li>
    </ul>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Regras de negocio</h2>
    <ul>
        <li>Calculos de prazo, situacao, chave e notificacao ficam em <strong>app/Services/ContractRulesService.php</strong>.</li>
        <li>Mapeamento e leitura da planilha ficam em <strong>app/Services/ExcelImportService.php</strong>.</li>
        <li>Indicadores do dashboard ficam em <strong>app/Models/Contract.php</strong> e <strong>app/Controllers/DashboardController.php</strong>.</li>
        <li>Relatorios ficam em <strong>app/Controllers/ReportsController.php</strong>.</li>
        <li>Permissoes ficam em <strong>app/Core/Auth.php</strong> e nas chamadas <strong>requirePermission()</strong> dos controladores.</li>
    </ul>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Como corrigir bugs comuns</h2>
    <ol>
        <li>Reproduza o erro com usuario adequado e anote a URL.</li>
        <li>Consulte <strong>storage/logs/app.log</strong> quando houver erro interno.</li>
        <li>Verifique se a rota existe em <strong>config/routes.php</strong>.</li>
        <li>Rode <code>php scripts/check_syntax.php</code> antes de testar no navegador.</li>
        <li>Quando o erro envolver importacao, confira <strong>logs_importacao</strong> e o lote em <strong>import_batches</strong>.</li>
        <li>Para problemas de data, confirme se a planilha entrega data serial do Excel ou texto valido.</li>
    </ol>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Como evoluir o sistema</h2>
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <h3 class="h6 fw-bold">Criar novo campo</h3>
            <ol>
                <li>Adicione a coluna no SQL/migration.</li>
                <li>Inclua o campo em <code>$fillable</code> do model.</li>
                <li>Adicione input na view ou no array <code>$fields</code>.</li>
                <li>Atualize importacao se o dado vier da planilha.</li>
                <li>Inclua o campo em relatorios, se necessario.</li>
            </ol>
        </div>
        <div class="col-12 col-lg-6">
            <h3 class="h6 fw-bold">Criar novo relatorio</h3>
            <ol>
                <li>Adicione um novo tipo no metodo <code>build()</code> de <strong>ReportsController</strong>.</li>
                <li>Use consultas parametrizadas quando houver entrada do usuario.</li>
                <li>Atualize a view de relatorios com a opcao nova.</li>
                <li>Teste HTML e exportacao CSV.</li>
            </ol>
        </div>
    </div>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Backup e cuidados antes de producao</h2>
    <ul>
        <li>Faca backup do banco antes de importar planilhas grandes ou aplicar scripts SQL.</li>
        <li>Guarde copia de <strong>.env</strong>, uploads e logs importantes.</li>
        <li>Teste migracoes em copia do banco antes de atualizar producao.</li>
        <li>Nao altere nomes de tabelas sem plano de migracao.</li>
        <li>Preserve logs de importacao e auditoria para rastreabilidade.</li>
        <li>Revise permissoes de pasta e credenciais apos qualquer transferencia de servidor.</li>
    </ul>
</section>

<section class="gc-card p-4">
    <h2 class="h5 fw-bold">Comandos uteis</h2>
    <pre class="bg-light border rounded p-3 mb-0"><code>composer install
php scripts/check_syntax.php
php scripts/migrate_import_batches.php
php scripts/import_spreadsheet.php "Contratos 2024.xlsm"
php -S 127.0.0.1:8080 -t public public/router.php</code></pre>
</section>

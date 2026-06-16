<section class="gc-card manual-hero p-4 mb-4">
    <h2 class="h4 fw-bold">Manual de Implantacao</h2>
    <p class="mb-0 text-secondary">Guia para publicar o GestContratos em servidor interno ou ambiente local controlado.</p>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Requisitos tecnicos</h2>
    <ul>
        <li>PHP 8.1 ou superior.</li>
        <li>Extensoes PHP: PDO, PDO MySQL, fileinfo, zip, mbstring, xml e extensoes exigidas pelo PhpSpreadsheet.</li>
        <li>MySQL 8 ou MariaDB compativel.</li>
        <li>Composer para instalar dependencias.</li>
        <li>Servidor web Apache, Nginx ou PHP embutido para uso local.</li>
        <li>Permissao de escrita em <strong>storage</strong> e area de uploads.</li>
    </ul>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Configuracao inicial</h2>
    <ol>
        <li>Copie o projeto para o servidor.</li>
        <li>Execute <code>composer install --no-dev</code> em producao.</li>
        <li>Crie o banco MySQL/MariaDB.</li>
        <li>Copie <strong>.env.example</strong> para <strong>.env</strong> e ajuste host, porta, banco, usuario, senha e <code>APP_URL</code>.</li>
        <li>Execute o SQL de <strong>database/schema.sql</strong> no banco criado.</li>
        <li>Confirme se o usuario administrador existe: <code>admin@gestcontratos.local</code>.</li>
        <li>Ajuste a senha do administrador antes de disponibilizar para usuarios reais.</li>
    </ol>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Permissoes de pasta</h2>
    <p>O usuario do servidor web precisa gravar em:</p>
    <ul>
        <li><strong>storage/logs</strong>, para logs da aplicacao.</li>
        <li><strong>storage/uploads</strong>, para planilhas e anexos quando usados.</li>
    </ul>
    <p class="mb-0">Nunca exponha <strong>storage</strong>, <strong>database</strong>, <strong>vendor</strong> ou <strong>.env</strong> como raiz publica. A raiz publica deve ser <strong>public</strong>.</p>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Apache/Nginx</h2>
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <h3 class="h6 fw-bold">Apache</h3>
            <ul>
                <li>Aponte o DocumentRoot para <strong>public</strong>.</li>
                <li>Habilite rewrite se usar URLs amigaveis pelo Apache.</li>
                <li>Proteja arquivos sensiveis fora da raiz publica.</li>
            </ul>
        </div>
        <div class="col-12 col-lg-6">
            <h3 class="h6 fw-bold">Nginx</h3>
            <ul>
                <li>Use <strong>public</strong> como root.</li>
                <li>Encaminhe requisicoes inexistentes para <strong>public/index.php</strong>.</li>
                <li>Use PHP-FPM com socket ou porta interna.</li>
            </ul>
        </div>
    </div>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Seguranca basica</h2>
    <ul>
        <li>Use HTTPS em producao.</li>
        <li>Troque senhas padrao imediatamente.</li>
        <li>Restrinja o acesso ao banco por rede e usuario.</li>
        <li>Configure backup automatico do banco e dos uploads.</li>
        <li>Mantenha <strong>APP_DEBUG=false</strong> em producao.</li>
        <li>Monitore <strong>storage/logs/app.log</strong>.</li>
        <li>Revise perfis periodicamente, especialmente administradores.</li>
    </ul>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Implantacao local/ad hoc</h2>
    <p>Para uso local em Windows, use o arquivo <strong>iniciar-local.bat</strong> criado na raiz do projeto. Ele verifica o MySQL, inicia o servidor PHP embutido e abre a URL correta.</p>
    <pre class="bg-light border rounded p-3 mb-0"><code>iniciar-local.bat</code></pre>
</section>

<section class="gc-card p-4 mb-4">
    <h2 class="h5 fw-bold">Docker</h2>
    <p>Este projeto esta preparado para PHP/MySQL simples e ja depende de uma configuracao local existente. Para evitar mudar a forma de operacao atual, a opcao recomendada neste momento e implantacao manual com PHP, Composer e MySQL/MariaDB.</p>
    <p class="mb-0">Caso a equipe queira Docker futuramente, crie um plano separado incluindo persistencia de banco, volume de uploads, variaveis de ambiente e rotina de backup.</p>
</section>

<section class="gc-card p-4">
    <h2 class="h5 fw-bold">Checklist pos-implantacao</h2>
    <ul>
        <li>Pagina de login abre pela URL final.</li>
        <li>Administrador consegue entrar.</li>
        <li>Usuario comum nao acessa importacao, logs, auditoria, cadastros auxiliares, configuracoes ou manual de implantacao.</li>
        <li>Dashboard carrega indicadores.</li>
        <li>Importacao de teste gera lote e logs.</li>
        <li>Backup do banco foi testado.</li>
        <li>HTTPS e permissao de pastas conferidos.</li>
    </ul>
</section>

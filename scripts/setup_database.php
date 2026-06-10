<?php

function ask(string $label, string $default = ''): string
{
    $suffix = $default !== '' ? " [{$default}]" : '';
    echo "{$label}{$suffix}: ";
    $value = trim((string) fgets(STDIN));
    return $value === '' ? $default : $value;
}

$root = dirname(__DIR__);

echo "Configuracao do banco GestContratos\n";
echo "Informe as credenciais do MySQL/MariaDB local.\n\n";

$host = ask('Host', '127.0.0.1');
$port = ask('Porta', '3306');
$database = ask('Banco', 'gestcontratos');
$username = ask('Usuario', 'root');
$password = ask('Senha');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$database}`");
    $pdo->exec((string) file_get_contents($root . '/database/schema.sql'));

    $env = <<<ENV
APP_NAME=GestContratos
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_HOST={$host}
DB_PORT={$port}
DB_DATABASE={$database}
DB_USERNAME={$username}
DB_PASSWORD={$password}
DB_CHARSET=utf8mb4

SESSION_NAME=gestcontratos_session
UPLOAD_MAX_MB=20
ENV;

    file_put_contents($root . '/.env', $env . PHP_EOL);

    echo "\nBanco configurado com sucesso.\n";
    echo "Acesse http://localhost:8080/login\n";
    echo "Usuario inicial: admin@gestcontratos.local\n";
    echo "Senha inicial: Admin@123\n";
} catch (Throwable $exception) {
    echo "\nErro ao configurar banco:\n";
    echo $exception->getMessage() . "\n";
    exit(1);
}

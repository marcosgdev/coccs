<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

new GestContratos\Core\Application(dirname(__DIR__));
$pdo = GestContratos\Core\Database::pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$statements = [
    "CREATE TABLE IF NOT EXISTS import_batches (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      arquivo VARCHAR(240) NOT NULL,
      modo ENUM('simulacao','importacao') NOT NULL,
      duplicate_mode VARCHAR(40) NOT NULL DEFAULT 'ignore',
      status VARCHAR(40) NOT NULL DEFAULT 'em_execucao',
      resultado LONGTEXT NULL,
      erros LONGTEXT NULL,
      started_by BIGINT UNSIGNED NULL,
      undone_by BIGINT UNSIGNED NULL,
      started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      finished_at DATETIME NULL,
      undone_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      deleted_at DATETIME NULL,
      INDEX idx_import_batches_status (status),
      INDEX idx_import_batches_modo (modo),
      INDEX idx_import_batches_started (started_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$columns = [
    'contratos' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'arps' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'execucoes_financeiras' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'servidores' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'setores' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'naturezas_contratacao' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'naturezas_despesa' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'formas_contratacao' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'tipos_contrato' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'bases_legais' => ['import_batch_id BIGINT UNSIGNED NULL'],
    'logs_importacao' => ['import_batch_id BIGINT UNSIGNED NULL', "modo ENUM('simulacao','importacao') NULL"],
];

foreach ($statements as $sql) {
    $pdo->exec($sql);
}

foreach ($columns as $table => $defs) {
    foreach ($defs as $definition) {
        [$column] = explode(' ', $definition, 2);
        if (! hasColumn($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$definition}");
        }
    }
}

foreach (['contratos', 'arps', 'execucoes_financeiras', 'servidores', 'setores', 'logs_importacao'] as $table) {
    $index = "idx_{$table}_batch";
    if (! hasIndex($pdo, $table, $index)) {
        $pdo->exec("CREATE INDEX {$index} ON {$table} (import_batch_id)");
    }
}

$pdo->exec('ALTER TABLE servidores MODIFY nome VARCHAR(600) NOT NULL');
foreach (['gestor', 'gestor_substituto', 'fiscal_demandante', 'fiscal_tecnico', 'fiscal_substituto', 'fiscal_administrativo'] as $column) {
    $pdo->exec("ALTER TABLE contratos MODIFY {$column} VARCHAR(600) NULL");
}
if (hasIndex($pdo, 'contratos', 'idx_contratos_fornecedor')) {
    $pdo->exec('DROP INDEX idx_contratos_fornecedor ON contratos');
}
$pdo->exec('ALTER TABLE contratos MODIFY fornecedor_nome TEXT NULL');
$pdo->exec('CREATE INDEX idx_contratos_fornecedor ON contratos (fornecedor_nome(191))');
$pdo->exec('ALTER TABLE arps MODIFY fornecedor_nome TEXT NULL');

echo "Migracao de lotes de importacao aplicada.\n";

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function hasIndex(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name');
    $stmt->execute(['table' => $table, 'index_name' => $index]);
    return (int) $stmt->fetchColumn() > 0;
}

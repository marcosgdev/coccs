<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = new GestContratos\Core\Application(dirname(__DIR__));
$pdo = GestContratos\Core\Database::pdo();

$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);

echo "Schema e seed essencial aplicados.\n";

<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use GestContratos\Core\Application;
use GestContratos\Models\ImportBatch;
use GestContratos\Services\ExcelImportService;

@set_time_limit(0);
ini_set('memory_limit', '1024M');

$root = dirname(__DIR__);
new Application($root);

$path = $argv[1] ?? $root . '/Contratos 2024.xlsm';
$simulate = in_array('--simulate', $argv, true);
$duplicateMode = in_array('--overwrite', $argv, true) ? 'overwrite' : 'ignore';

if (! file_exists($path)) {
    fwrite(STDERR, "Planilha nao encontrada: {$path}\n");
    exit(1);
}

$batchModel = new ImportBatch();
$batchId = $batchModel->create([
    'arquivo' => basename($path),
    'modo' => $simulate ? 'simulacao' : 'importacao',
    'duplicate_mode' => $duplicateMode,
    'status' => 'em_execucao',
    'started_by' => null,
    'started_at' => date('Y-m-d H:i:s'),
]);

try {
    $result = (new ExcelImportService())->import($path, $simulate, $duplicateMode, $batchId);
    $batchModel->update($batchId, [
        'status' => empty($result['errors']) ? 'concluido' : 'concluido_com_erros',
        'resultado' => json_encode($result, JSON_UNESCAPED_UNICODE),
        'erros' => ! empty($result['errors']) ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE) : null,
        'finished_at' => date('Y-m-d H:i:s'),
    ]);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $exception) {
    $batchModel->update($batchId, [
        'status' => 'erro',
        'erros' => json_encode([$exception->getMessage()], JSON_UNESCAPED_UNICODE),
        'finished_at' => date('Y-m-d H:i:s'),
    ]);
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

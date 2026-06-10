<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\ImportBatch;
use GestContratos\Models\ImportLog;
use GestContratos\Services\AuditService;
use GestContratos\Services\ImportBatchService;
use GestContratos\Services\ExcelImportService;
use GestContratos\Services\UploadService;

final class ImportController extends Controller
{
    public function index(): void
    {
        $this->requirePermission(['gestor-contratos']);
        $this->view('import/index', [
            'title' => 'Importacao da Planilha',
            'preview' => $_SESSION['import_preview'] ?? null,
            'lastFile' => $_SESSION['import_file'] ?? null,
            'result' => $_SESSION['import_result'] ?? null,
            'batches' => (new ImportBatchService())->recent(),
            'canHardDeleteBatches' => Auth::hasAnyRole(['administrador']),
        ]);
        unset($_SESSION['import_result']);
    }

    public function preview(Request $request): void
    {
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        try {
            $relative = (new UploadService())->store($request->files['planilha'] ?? [], 'imports', ['xlsm', 'xlsx']);
            if (! $relative) {
                throw new \RuntimeException('Envie uma planilha .xlsm ou .xlsx.');
            }
            $path = base_path($relative);
            $_SESSION['import_file'] = $relative;
            $_SESSION['import_preview'] = (new ExcelImportService())->preview($path);
            (new AuditService())->log('pre_visualizacao_importacao', 'logs_importacao', null, [], ['arquivo' => $relative]);
            flash('success', 'Pre-visualizacao gerada.');
        } catch (\Throwable $exception) {
            flash('danger', $exception->getMessage());
        }
        redirect('/importacao');
    }

    public function run(Request $request): void
    {
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        @set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $file = $_SESSION['import_file'] ?? null;
        if (! $file || ! file_exists(base_path($file))) {
            flash('danger', 'Gere a pre-visualizacao antes de importar.');
            redirect('/importacao');
        }

        $simulate = (bool) $request->input('simulate', false);
        $mode = (string) $request->input('duplicate_mode', 'ignore');
        $batchModel = new ImportBatch();
        $batchId = $batchModel->create([
            'arquivo' => basename($file),
            'modo' => $simulate ? 'simulacao' : 'importacao',
            'duplicate_mode' => $mode,
            'status' => 'em_execucao',
            'started_by' => Auth::id(),
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            $result = (new ExcelImportService())->import(base_path($file), $simulate, $mode, $batchId);
            $status = empty($result['errors']) ? 'concluido' : 'concluido_com_erros';
            $batchModel->update($batchId, [
                'status' => $status,
                'resultado' => json_encode($result, JSON_UNESCAPED_UNICODE),
                'erros' => ! empty($result['errors']) ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE) : null,
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['import_result'] = $result;
            (new AuditService())->log('execucao_importacao', 'logs_importacao', $batchId, [], $result);
            flash(empty($result['errors']) ? 'success' : 'warning', $simulate ? 'Simulacao concluida.' : 'Importacao concluida.');
        } catch (\Throwable $exception) {
            $batchModel->update($batchId, [
                'status' => 'erro',
                'erros' => json_encode([$exception->getMessage()], JSON_UNESCAPED_UNICODE),
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
            (new ImportLog())->create([
                'usuario_id' => Auth::id(),
                'arquivo' => basename($file),
                'aba' => 'Sistema',
                'linha' => null,
                'status' => 'erro',
                'import_batch_id' => $batchId,
                'modo' => $simulate ? 'simulacao' : 'importacao',
                'mensagem' => $exception->getMessage(),
                'dados' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            flash('danger', 'Falha na importacao: ' . $exception->getMessage());
        }

        redirect('/importacao');
    }

    public function logs(Request $request): void
    {
        $this->requirePermission(['gestor-contratos', 'auditoria-controle']);
        $this->view('import/logs', [
            'title' => 'Logs de Importacao',
            'logs' => $this->filteredLogs($request),
            'filters' => $request->query,
            'batches' => (new ImportBatchService())->recent(100),
        ]);
    }

    public function undo(Request $request, string $id): void
    {
        $this->requirePermission(['gestor-contratos']);
        $this->validateCsrf($request);
        (new ImportBatchService())->undo((int) $id, false);
        flash('success', 'Importacao desativada. Os registros do lote foram marcados como excluidos logicamente.');
        redirect('/importacao');
    }

    public function deleteBatch(Request $request, string $id): void
    {
        $this->requirePermission(['administrador']);
        $this->validateCsrf($request);
        (new ImportBatchService())->undo((int) $id, true);
        flash('success', 'Importacao excluida fisicamente.');
        redirect('/importacao');
    }

    private function filteredLogs(Request $request): array
    {
        $clauses = [];
        $params = [];
        foreach (['modo', 'status', 'aba', 'import_batch_id'] as $field) {
            $value = $request->query[$field] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $clauses[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        $stmt = \GestContratos\Core\Database::pdo()->prepare("SELECT * FROM logs_importacao {$where} ORDER BY id DESC LIMIT 3000");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

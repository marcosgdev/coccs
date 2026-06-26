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
        $this->requireCan(\GestContratos\Core\Auth::canImport());
        $this->view('import/index', [
            'title' => 'Importacao da Planilha',
            'preview' => $_SESSION['import_preview'] ?? null,
            'lastFile' => $_SESSION['import_file'] ?? null,
            'result' => $_SESSION['import_result'] ?? null,
            'batches' => (new ImportBatchService())->recent(),
            'canHardDeleteBatches' => Auth::isAdmin(),
        ]);
        unset($_SESSION['import_result']);
    }

    public function preview(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
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
        $this->requireCan(\GestContratos\Core\Auth::canImport());
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
        $operations = (array) ($request->body['operations'] ?? []);
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
            $result = (new ExcelImportService())->import(base_path($file), $simulate, $mode, $batchId, $operations);
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

    public function arpExecution(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
        $this->validateCsrf($request);
        try {
            $relative = (new UploadService())->store(
                $request->files['planilha'] ?? [],
                'imports',
                ['xlsx', 'xlsm', 'xls']
            );
            if (!$relative) {
                throw new \RuntimeException('Envie uma planilha .xlsx ou .xlsm.');
            }
            $simulate = ($request->body['mode'] ?? '') === 'simulate';
            $result   = (new ExcelImportService())->importArpExecution(base_path($relative), $simulate);

            $rowsRead = $result['rows_read'] ?? 0;
            $headers  = implode(' | ', array_slice($result['headers'] ?? [], 0, 5));
            $msg = $simulate
                ? "Simulação: {$result['updated']} ARP(s) seriam atualizadas (linhas lidas: {$rowsRead})."
                : "{$result['updated']} ARP(s) atualizadas (linhas lidas: {$rowsRead}).";
            if ($rowsRead === 0) {
                $msg .= " Cabeçalhos detectados: [{$headers}]. Verifique se a planilha tem cabeçalho na linha 1.";
            } elseif (!empty($result['not_found'])) {
                $sample = array_slice($result['not_found'], 0, 8);
                $msg .= ' Não encontradas no banco: ' . implode(', ', $sample);
                if (count($result['not_found']) > 8) {
                    $msg .= ' …e mais ' . (count($result['not_found']) - 8) . '.';
                }
                $msg .= " | Cabeçalhos: [{$headers}]";
            }
            flash($result['updated'] > 0 ? 'success' : 'warning', $msg);
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
        }
        redirect('/importacao');
    }

    public function logs(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
        $this->view('import/logs', [
            'title' => 'Logs de Importacao',
            'logs' => $this->filteredLogs($request),
            'filters' => $request->query,
            'batches' => (new ImportBatchService())->recent(100),
        ]);
    }

    public function duplicatas(): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
        $pdo = \GestContratos\Core\Database::pdo();

        // Agrupa por chave normalizada: tipo + número só com dígitos zero-padded(3) + ano
        // Detecta tanto "CONTRATO050/2024" quanto "CONTRATO50/2024" como o mesmo contrato
        $grupos = $pdo->query("
            SELECT
                tipo,
                SUBSTRING_INDEX(chave, '/', -1)                                                    AS ano,
                LPAD(REGEXP_REPLACE(SUBSTRING_INDEX(chave, '/', 1), '[^0-9]', ''), 3, '0')        AS num_normalizado,
                CONCAT(
                    tipo,
                    LPAD(REGEXP_REPLACE(SUBSTRING_INDEX(chave, '/', 1), '[^0-9]', ''), 3, '0'),
                    '/',
                    SUBSTRING_INDEX(chave, '/', -1)
                )                                                                                   AS chave_canonica,
                COUNT(*)                                                                            AS total,
                MIN(id)                                                                             AS manter_id,
                GROUP_CONCAT(id          ORDER BY id SEPARATOR ',')                                AS todos_ids,
                GROUP_CONCAT(chave       ORDER BY id SEPARATOR ' | ')                              AS todas_chaves,
                MAX(fornecedor_nome)                                                                AS fornecedor_nome
            FROM contratos
            WHERE deleted_at IS NULL
            GROUP BY tipo, ano, num_normalizado
            HAVING COUNT(*) > 1
            ORDER BY total DESC, chave_canonica ASC
        ")->fetchAll();

        $total_duplicatas = array_sum(array_map(fn($g) => $g['total'] - 1, $grupos));

        $this->view('import/duplicatas', [
            'title'            => 'Duplicatas de Contratos',
            'grupos'           => $grupos,
            'total_duplicatas' => $total_duplicatas,
        ]);
    }

    public function limparDuplicatas(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
        $this->validateCsrf($request);

        $pdo   = \GestContratos\Core\Database::pdo();
        $agora = date('Y-m-d H:i:s');

        // Passo 1: identifica os IDs a manter ANTES de deletar
        $manterIds = $pdo->query("
            SELECT MIN(id) AS manter_id
            FROM contratos
            WHERE deleted_at IS NULL
            GROUP BY tipo,
                     SUBSTRING_INDEX(chave, '/', -1),
                     LPAD(REGEXP_REPLACE(SUBSTRING_INDEX(chave, '/', 1), '[^0-9]', ''), 3, '0')
            HAVING COUNT(*) > 1
        ")->fetchAll(\PDO::FETCH_COLUMN);

        // Passo 2: soft-delete nos duplicados + renomeia a chave deles para liberar a UNIQUE key
        $stmt = $pdo->prepare("
            UPDATE contratos c1
            INNER JOIN (
                SELECT
                    MIN(id) AS manter_id,
                    SUBSTRING_INDEX(chave, '/', -1)                                                 AS ano,
                    LPAD(REGEXP_REPLACE(SUBSTRING_INDEX(chave, '/', 1), '[^0-9]', ''), 3, '0')    AS num_norm,
                    tipo
                FROM contratos
                WHERE deleted_at IS NULL
                GROUP BY tipo, ano, num_norm
                HAVING COUNT(*) > 1
            ) grp
                ON  c1.tipo = grp.tipo
                AND SUBSTRING_INDEX(c1.chave, '/', -1) = grp.ano
                AND LPAD(REGEXP_REPLACE(SUBSTRING_INDEX(c1.chave, '/', 1), '[^0-9]', ''), 3, '0') = grp.num_norm
                AND c1.id != grp.manter_id
            SET c1.deleted_at = ?,
                c1.chave      = CONCAT('_DEL', c1.id, '_', c1.chave)
            WHERE c1.deleted_at IS NULL
        ");
        $stmt->execute([$agora]);
        $removidos = $stmt->rowCount();

        // Passo 3: normaliza a chave SOMENTE dos keepers que faziam parte de grupos duplicados
        if ($manterIds) {
            $placeholders = implode(',', array_fill(0, count($manterIds), '?'));
            $pdo->prepare("
                UPDATE contratos
                SET chave = CONCAT(tipo,
                                   LPAD(REGEXP_REPLACE(SUBSTRING_INDEX(chave, '/', 1), '[^0-9]', ''), 3, '0'),
                                   '/',
                                   SUBSTRING_INDEX(chave, '/', -1))
                WHERE id IN ($placeholders)
                  AND deleted_at IS NULL
            ")->execute($manterIds);
        }

        (new AuditService())->log('limpeza_duplicatas', 'contratos', null, [], [
            'removidos' => $removidos,
            'modo'      => 'soft_delete',
        ]);

        flash('success', "{$removidos} registro(s) duplicado(s) removido(s). A chave dos registros mantidos foi normalizada.");
        redirect('/importacao/duplicatas');
    }

    public function undo(Request $request, string $id): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
        $this->validateCsrf($request);
        (new ImportBatchService())->undo((int) $id, false);
        flash('success', 'Importacao desativada. Os registros do lote foram marcados como excluidos logicamente.');
        redirect('/importacao');
    }

    public function deleteBatch(Request $request, string $id): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canImport());
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

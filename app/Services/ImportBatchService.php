<?php

namespace GestContratos\Services;

use GestContratos\Core\Auth;
use GestContratos\Core\Database;
use GestContratos\Models\ImportBatch;

final class ImportBatchService
{
    private const IMPORTED_TABLES = [
        'execucoes_financeiras',
        'contratos',
        'arps',
        'servidores',
        'setores',
        'naturezas_contratacao',
        'naturezas_despesa',
        'formas_contratacao',
        'tipos_contrato',
        'bases_legais',
    ];

    public function recent(int $limit = 50): array
    {
        return (new ImportBatch())->all('id DESC', [], $limit);
    }

    public function undo(int $batchId, bool $hardDelete = false): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach (self::IMPORTED_TABLES as $table) {
                if (! $this->hasColumn($table, 'import_batch_id')) {
                    continue;
                }

                if ($hardDelete) {
                    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE import_batch_id = :batch_id");
                } else {
                    $stmt = $pdo->prepare("UPDATE {$table}
                        SET deleted_at = COALESCE(deleted_at, NOW()), updated_at = NOW()
                        WHERE import_batch_id = :batch_id AND deleted_at IS NULL");
                }
                $stmt->execute(['batch_id' => $batchId]);
            }

            (new ImportBatch())->update($batchId, [
                'status' => $hardDelete ? 'excluido' : 'desfeito',
                'undone_by' => Auth::id(),
                'undone_at' => date('Y-m-d H:i:s'),
            ]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

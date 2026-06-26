<?php

namespace GestContratos\Models;

use GestContratos\Core\Database;

class ContractTracking
{
    public function forContract(int $contratoId): array
    {
        $stmt = Database::pdo()->prepare("
            SELECT a.*, u.nome AS autor
            FROM contrato_acompanhamentos a
            LEFT JOIN usuarios u ON u.id = a.created_by
            WHERE a.contrato_id = ? AND a.deleted_at IS NULL
            ORDER BY a.data_referencia DESC, a.created_at DESC
        ");
        $stmt->execute([$contratoId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $data['dentro_prazo'] = $this->computeDentroPrazo($data);
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $stmt = Database::pdo()->prepare("INSERT INTO contrato_acompanhamentos ($cols) VALUES ($phs)");
        $stmt->execute(array_values($data));
        return (int) Database::pdo()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM contrato_acompanhamentos WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, array $data): void
    {
        $data['dentro_prazo'] = $this->computeDentroPrazo($data);
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $stmt = Database::pdo()->prepare("UPDATE contrato_acompanhamentos SET $sets WHERE id = ?");
        $stmt->execute([...array_values($data), $id]);
    }

    public function delete(int $id): void
    {
        Database::pdo()->prepare(
            "UPDATE contrato_acompanhamentos SET deleted_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    private function computeDentroPrazo(array $data): ?int
    {
        if (empty($data['prazo_apresentacao']) || empty($data['apresentado_em'])) {
            return null;
        }
        return $data['apresentado_em'] <= $data['prazo_apresentacao'] ? 1 : 0;
    }
}

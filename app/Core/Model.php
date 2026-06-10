<?php

namespace GestContratos\Core;

use PDO;

abstract class Model
{
    protected string $table;
    protected array $fillable = [];
    protected string $primaryKey = 'id';

    protected function pdo(): PDO
    {
        return Database::pdo();
    }

    public function all(string $orderBy = 'id DESC', array $filters = [], int $limit = 500): array
    {
        [$where, $params] = $this->where($filters);
        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY {$orderBy} LIMIT {$limit}";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int|string $id): ?array
    {
        $stmt = $this->pdo()->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $data = $this->filter($data);
        if (in_array('created_at', $this->fillable, true)) {
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        }
        if (in_array('updated_at', $this->fillable, true)) {
            $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        }
        $columns = array_keys($data);
        $placeholders = array_map(fn ($column) => ':' . $column, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo()->lastInsertId();
    }

    public function update(int|string $id, array $data): void
    {
        $data = $this->filter($data);
        if (in_array('updated_at', $this->fillable, true)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        $sets = array_map(fn ($column) => "{$column} = :{$column}", array_keys($data));
        $data['id'] = $id;
        $sql = sprintf('UPDATE %s SET %s WHERE %s = :id', $this->table, implode(', ', $sets), $this->primaryKey);
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($data);
    }

    public function softDelete(int|string $id): void
    {
        $stmt = $this->pdo()->prepare("UPDATE {$this->table} SET deleted_at = NOW(), updated_at = NOW() WHERE {$this->primaryKey} = :id");
        $stmt->execute(['id' => $id]);
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->where($filters);
        $stmt = $this->pdo()->prepare("SELECT COUNT(*) FROM {$this->table} {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    protected function where(array $filters): array
    {
        $clauses = [];
        $params = [];
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if ($column === 'q') {
                continue;
            }
            $param = preg_replace('/[^a-z0-9_]/i', '_', $column);
            $clauses[] = "{$column} = :{$param}";
            $params[$param] = $value;
        }
        if (in_array('deleted_at', $this->fillable, true) && ! str_contains($this->table, 'logs')) {
            $clauses[] = 'deleted_at IS NULL';
        }
        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }

    protected function filter(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }
}

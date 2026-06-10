<?php

namespace GestContratos\Models;

use GestContratos\Core\Database;
use GestContratos\Core\Model;

final class Parameter extends Model
{
    protected string $table = 'parametros_sistema';
    protected array $fillable = ['chave', 'valor', 'descricao', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    public static function value(string $key, mixed $default = null): mixed
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT valor FROM parametros_sistema WHERE chave = :chave AND deleted_at IS NULL LIMIT 1');
            $stmt->execute(['chave' => $key]);
            $value = $stmt->fetchColumn();
            return $value === false ? $default : $value;
        } catch (\Throwable) {
            return $default;
        }
    }
}

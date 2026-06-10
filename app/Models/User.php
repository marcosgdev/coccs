<?php

namespace GestContratos\Models;

use GestContratos\Core\Database;
use GestContratos\Core\Model;

final class User extends Model
{
    protected string $table = 'usuarios';
    protected array $fillable = [
        'nome', 'email', 'password_hash', 'perfil_id', 'servidor_id', 'ativo',
        'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
    ];

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*, p.nome AS perfil_nome, p.slug AS perfil_slug
             FROM usuarios u
             LEFT JOIN perfis p ON p.id = u.perfil_id
             WHERE u.email = :email AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}

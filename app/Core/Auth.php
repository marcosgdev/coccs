<?php

namespace GestContratos\Core;

use GestContratos\Models\User;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return false;
        }
        if ((int) ($user['ativo'] ?? 0) !== 1) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['nome'],
            'email' => $user['email'],
            'role' => $user['perfil_slug'] ?? 'consulta-gerencial',
            'role_name' => $user['perfil_nome'] ?? 'Consulta Gerencial',
        ];
        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function hasAnyRole(array $roles): bool
    {
        $role = $_SESSION['user']['role'] ?? null;
        return $role === 'administrador' || in_array($role, $roles, true);
    }

    public static function canWrite(): bool
    {
        return self::hasAnyRole(['administrador', 'gestor-contratos', 'setor-demandante', 'gestor-fiscal']);
    }
}

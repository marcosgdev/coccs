<?php

namespace GestContratos\Core;

use GestContratos\Models\User;

final class Auth
{
    // Slugs novos (hierarquia da unidade)
    private const MASTER       = 'administrador';
    private const COORDENADOR  = 'coordenador';
    private const GERENTE      = 'gerente';
    private const SERVIDOR     = 'servidor';

    // Slugs legados mantidos por compatibilidade
    private const LEGADO_COORDENADOR = ['gestor-contratos'];
    private const LEGADO_SERVIDOR    = ['setor-demandante', 'gestor-fiscal'];
    private const LEGADO_GERENTE     = ['auditoria-controle'];

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
            'id'        => (int) $user['id'],
            'name'      => $user['nome'],
            'email'     => $user['email'],
            'role'      => $user['perfil_slug'] ?? self::SERVIDOR,
            'role_name' => $user['perfil_nome'] ?? 'Servidor',
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

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
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
        $role = self::role();
        return $role === self::MASTER || in_array($role, $roles, true);
    }

    public static function isAdmin(): bool
    {
        return self::role() === self::MASTER;
    }

    // ── Permissões granulares ─────────────────────────────────────────────────

    /** Criar e editar contratos, ARPs e aditivos */
    public static function canWrite(): bool
    {
        return self::hasAnyRole([
            self::COORDENADOR, self::GERENTE, self::SERVIDOR,
            ...self::LEGADO_COORDENADOR, ...self::LEGADO_SERVIDOR,
        ]);
    }

    /** Excluir contratos/aditivos e encerrar contratos */
    public static function canDelete(): bool
    {
        return self::hasAnyRole([self::COORDENADOR, ...self::LEGADO_COORDENADOR]);
    }

    /** Marcar contrato como estratégico */
    public static function canMarkStrategic(): bool
    {
        return self::hasAnyRole([self::COORDENADOR, self::GERENTE, ...self::LEGADO_COORDENADOR]);
    }

    /** Gerar e enviar notificações */
    public static function canNotify(): bool
    {
        return self::canWrite();
    }

    /** Acessar relatórios (Servidor não pode) */
    public static function canViewReports(): bool
    {
        return self::hasAnyRole([
            self::COORDENADOR, self::GERENTE,
            ...self::LEGADO_COORDENADOR, ...self::LEGADO_GERENTE,
        ]);
    }

    /** Importar planilhas e sincronizar com API */
    public static function canImport(): bool
    {
        return self::hasAnyRole([self::COORDENADOR, ...self::LEGADO_COORDENADOR]);
    }

    /** Sincronizar com a API TJPA */
    public static function canSync(): bool
    {
        return self::canImport();
    }

    /** Gerenciar usuários */
    public static function canManageUsers(): bool
    {
        return self::hasAnyRole([self::COORDENADOR]);
    }

    /** Acessar auditoria e logs */
    public static function canViewAudit(): bool
    {
        return self::hasAnyRole([
            self::COORDENADOR, ...self::LEGADO_GERENTE,
        ]);
    }
}

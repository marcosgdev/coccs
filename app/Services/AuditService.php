<?php

namespace GestContratos\Services;

use GestContratos\Core\Auth;
use GestContratos\Models\AuditLog;

final class AuditService
{
    public function log(string $action, string $table, int|string|null $recordId = null, array $before = [], array $after = []): void
    {
        try {
            (new AuditLog())->create([
                'usuario_id' => Auth::id(),
                'acao' => $action,
                'tabela' => $table,
                'registro_id' => $recordId,
                'valores_anteriores' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'valores_novos' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Auditoria nao deve bloquear a operacao principal.
        }
    }
}

<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class AuditLog extends Model
{
    protected string $table = 'logs_auditoria';
    protected array $fillable = [
        'usuario_id', 'acao', 'tabela', 'registro_id', 'valores_anteriores',
        'valores_novos', 'ip', 'user_agent', 'created_at',
    ];
}

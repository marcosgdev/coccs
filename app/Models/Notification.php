<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class Notification extends Model
{
    protected string $table = 'notificacoes';
    protected array $fillable = [
        'contrato_id', 'arp_id', 'tipo', 'assunto', 'texto', 'destinatarios',
        'status', 'data_envio', 'created_by', 'updated_by', 'created_at',
        'updated_at', 'deleted_at',
    ];
}

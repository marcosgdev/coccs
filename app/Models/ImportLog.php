<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class ImportLog extends Model
{
    protected string $table = 'logs_importacao';
    protected array $fillable = [
        'usuario_id', 'arquivo', 'aba', 'linha', 'status', 'import_batch_id', 'modo', 'mensagem', 'dados',
        'created_at',
    ];
}

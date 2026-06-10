<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class ImportBatch extends Model
{
    protected string $table = 'import_batches';
    protected array $fillable = [
        'arquivo', 'modo', 'duplicate_mode', 'status', 'resultado', 'erros',
        'started_by', 'undone_by', 'started_at', 'finished_at', 'undone_at',
        'created_at', 'updated_at', 'deleted_at',
    ];
}

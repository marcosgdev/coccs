<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class Additive extends Model
{
    protected string $table = 'aditivos';
    protected array $fillable = [
        'contrato_id', 'numero_aditivo', 'tipo_aditivo', 'data_aditivo', 'objeto',
        'valor_acrescido', 'valor_suprimido', 'nova_data_termino', 'justificativa',
        'anexo_path', 'observacoes', 'created_by', 'updated_by', 'created_at',
        'updated_at', 'deleted_at',
    ];
}

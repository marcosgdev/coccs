<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class Arp extends Model
{
    protected string $table = 'arps';
    protected array $fillable = [
        'numero_ata', 'ano', 'chave', 'fornecedor_id', 'fornecedor_nome', 'objeto',
        'vigencia_inicial', 'vigencia_final', 'valor_total', 'valor_por_fornecedor',
        'valor_executado', 'saldo', 'setor_id', 'setor_nome', 'observacoes', 'situacao',
        'dias_restantes', 'import_batch_id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
    ];
}

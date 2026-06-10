<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class FinancialExecution extends Model
{
    protected string $table = 'execucoes_financeiras';
    protected array $fillable = [
        'contrato_id', 'arp_id', 'chave', 'exercicio', 'valor_inicial',
        'valor_atualizado', 'valor_executado_exercicio', 'valor_acumulado',
        'saldo', 'observacoes', 'import_batch_id', 'created_by', 'updated_by', 'created_at',
        'updated_at', 'deleted_at',
    ];
}

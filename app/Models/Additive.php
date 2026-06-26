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

    public function forContract(int $contratoId): array
    {
        $stmt = \GestContratos\Core\Database::pdo()->prepare(
            'SELECT * FROM aditivos WHERE contrato_id = ? AND deleted_at IS NULL ORDER BY numero_aditivo ASC'
        );
        $stmt->execute([$contratoId]);
        return $stmt->fetchAll();
    }
}

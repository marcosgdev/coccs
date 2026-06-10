<?php

namespace GestContratos\Models;

use GestContratos\Core\Database;
use GestContratos\Core\Model;

final class Contract extends Model
{
    protected string $table = 'contratos';
    protected array $fillable = [
        'tipo', 'numero', 'ano', 'chave', 'fornecedor_id', 'fornecedor_nome', 'cnpj_cpf',
        'objeto', 'natureza_contratacao_id', 'natureza_contratacao_nome',
        'forma_contratacao_id', 'forma_contratacao_nome', 'tipo_contrato_id',
        'tipo_contrato_nome', 'licitacao_numero', 'processo', 'setor_id', 'setor_nome',
        'data_inicio', 'data_termino', 'valor_global_inicial', 'valor_global_atualizado',
        'valor_executado', 'valor_acumulado_executado', 'quantidade_aditivos',
        'base_legal_id', 'base_legal_nome', 'contrato_estrategico', 'gestor',
        'gestor_substituto', 'fiscal_demandante', 'fiscal_tecnico', 'fiscal_substituto',
        'fiscal_administrativo', 'emails_equipe', 'observacoes', 'situacao', 'prazo',
        'dias_contrato', 'dias_restantes', 'trimestre_vencimento', 'prazo_prorrogacao',
        'data_recebimento_prorrogacao', 'prorrogacao_no_prazo',
        'prazo_legal_classificacao', 'data_orcamento_estimado', 'status_reajuste',
        'texto_notificacao', 'encerrado_em', 'import_batch_id', 'created_by', 'updated_by',
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function search(array $filters = [], int $limit = 1000): array
    {
        $clauses = ['c.deleted_at IS NULL'];
        $params = [];

        foreach (['ano', 'situacao', 'prazo', 'setor_nome', 'fornecedor_nome', 'base_legal_nome', 'tipo', 'natureza_contratacao_nome', 'forma_contratacao_nome'] as $column) {
            if (! empty($filters[$column])) {
                $param = str_replace('.', '_', $column);
                $clauses[] = "c.{$column} = :{$param}";
                $params[$param] = $filters[$column];
            }
        }

        if (! empty($filters['q'])) {
            $clauses[] = '(c.chave LIKE :q OR c.numero LIKE :q OR c.fornecedor_nome LIKE :q OR c.objeto LIKE :q OR c.processo LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql = 'SELECT c.* FROM contratos c WHERE ' . implode(' AND ', $clauses) . ' ORDER BY c.data_termino IS NULL, c.data_termino ASC, c.id DESC LIMIT ' . (int) $limit;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByKey(string $key): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM contratos WHERE chave = :chave AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['chave' => $key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function dashboardStats(): array
    {
        $sql = "SELECT
            SUM(situacao = 'Vigente') AS contratos_vigentes,
            SUM(situacao = 'Expirado') AS contratos_expirados,
            SUM(dias_restantes BETWEEN 0 AND 30) AS vencendo_30,
            SUM(dias_restantes BETWEEN 31 AND 60) AS vencendo_60,
            SUM(dias_restantes BETWEEN 61 AND 90) AS vencendo_90,
            SUM(tipo = 'ARP' AND situacao = 'Vigente') AS arps_vigentes,
            COALESCE(SUM(valor_global_atualizado), 0) AS valor_global_atualizado,
            COALESCE(SUM(valor_executado), 0) AS valor_executado,
            SUM((gestor IS NULL OR gestor = '' OR gestor = 'sem indicação') AND situacao = 'Vigente') AS sem_gestor,
            SUM(((fiscal_demandante IS NULL OR fiscal_demandante = '' OR fiscal_demandante = 'sem indicação')
                AND (fiscal_tecnico IS NULL OR fiscal_tecnico = '' OR fiscal_tecnico = 'sem indicação')) AND situacao = 'Vigente') AS sem_fiscal,
            SUM(prorrogacao_no_prazo = 'Fora do prazo') AS prorrogacoes_fora_prazo,
            SUM(status_reajuste = 'Iniciar processo de reajuste') AS orcamento_vencido
            FROM contratos
            WHERE deleted_at IS NULL";
        return Database::pdo()->query($sql)->fetch() ?: [];
    }

    public function aggregate(string $column): array
    {
        $allowed = ['situacao', 'setor_nome', 'base_legal_nome', 'natureza_contratacao_nome'];
        if (! in_array($column, $allowed, true)) {
            return [];
        }
        $sql = "SELECT COALESCE(NULLIF({$column}, ''), 'Sem informacao') AS label, COUNT(*) AS total
                FROM contratos WHERE deleted_at IS NULL GROUP BY label ORDER BY total DESC LIMIT 12";
        return Database::pdo()->query($sql)->fetchAll();
    }
}

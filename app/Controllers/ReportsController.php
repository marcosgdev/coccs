<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Database;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;

final class ReportsController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $type = $request->query['tipo'] ?? 'contratos_vigentes';
        [$title, $rows] = $this->build($type, $request->query);

        if (($request->query['export'] ?? '') === 'csv') {
            $this->csv($title, $rows);
            return;
        }

        $this->view('reports/index', [
            'title' => 'Relatorios',
            'reportTitle' => $title,
            'type' => $type,
            'rows' => $rows,
            'filters' => $request->query,
        ]);
    }

    private function build(string $type, array $filters): array
    {
        $contracts = new Contract();
        return match ($type) {
            'contratos_expirados' => ['Contratos expirados', $contracts->search(array_merge($filters, ['situacao' => 'Expirado']))],
            'contratos_estrategicos' => ['Contratos estrategicos', Database::pdo()->query('SELECT * FROM contratos WHERE deleted_at IS NULL AND contrato_estrategico = 1 ORDER BY data_termino ASC')->fetchAll()],
            'sem_fiscal' => ['Contratos sem fiscal', Database::pdo()->query("SELECT * FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND ((fiscal_demandante IS NULL OR fiscal_demandante = '' OR fiscal_demandante = 'sem indicação') AND (fiscal_tecnico IS NULL OR fiscal_tecnico = '' OR fiscal_tecnico = 'sem indicação'))")->fetchAll()],
            'sem_gestor' => ['Contratos sem gestor', Database::pdo()->query("SELECT * FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND (gestor IS NULL OR gestor = '' OR gestor = 'sem indicação')")->fetchAll()],
            'arps_vigentes' => ['ARPs vigentes', Database::pdo()->query("SELECT * FROM arps WHERE deleted_at IS NULL AND situacao = 'Vigente' ORDER BY vigencia_final ASC")->fetchAll()],
            'execucao_ano' => ['Execucao financeira por exercicio', Database::pdo()->query('SELECT exercicio, COUNT(*) registros, SUM(valor_atualizado) valor_atualizado, SUM(valor_executado_exercicio) valor_executado_exercicio, SUM(saldo) saldo FROM execucoes_financeiras WHERE deleted_at IS NULL GROUP BY exercicio ORDER BY exercicio')->fetchAll()],
            'fornecedores_valor' => ['Ranking de fornecedores por valor', Database::pdo()->query('SELECT fornecedor_nome, COUNT(*) contratos, SUM(valor_global_atualizado) valor_global_atualizado, SUM(valor_executado) valor_executado FROM contratos WHERE deleted_at IS NULL GROUP BY fornecedor_nome ORDER BY valor_global_atualizado DESC LIMIT 100')->fetchAll()],
            'setores_valor' => ['Ranking de setores por valor', Database::pdo()->query('SELECT setor_nome, COUNT(*) contratos, SUM(valor_global_atualizado) valor_global_atualizado, SUM(valor_executado) valor_executado FROM contratos WHERE deleted_at IS NULL GROUP BY setor_nome ORDER BY valor_global_atualizado DESC LIMIT 100')->fetchAll()],
            default => ['Contratos vigentes', $contracts->search(array_merge($filters, ['situacao' => 'Vigente']))],
        };
    }

    private function csv(string $title, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . str_slug($title) . '.csv"');
        $out = fopen('php://output', 'w');
        if ($rows) {
            fputcsv($out, array_keys($rows[0]), ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
        }
        fclose($out);
    }
}

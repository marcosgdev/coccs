<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Database;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $contract = new Contract();
        $stats = $contract->dashboardStats();
        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'charts' => [
                'situacao' => $contract->aggregate('situacao'),
                'setor' => $contract->aggregate('setor_nome'),
                'baseLegal' => $contract->aggregate('base_legal_nome'),
                'natureza' => $contract->aggregate('natureza_contratacao_nome'),
                'execucaoAno' => $this->executionByYear(),
                'cargaServidor' => $this->serverLoad(),
            ],
        ]);
    }

    private function executionByYear(): array
    {
        $sql = 'SELECT exercicio AS label, COALESCE(SUM(valor_executado_exercicio),0) AS total
                FROM execucoes_financeiras
                WHERE deleted_at IS NULL
                GROUP BY exercicio ORDER BY exercicio';
        return Database::pdo()->query($sql)->fetchAll();
    }

    private function serverLoad(): array
    {
        $sql = "SELECT servidor AS label, SUM(total) AS total FROM (
                    SELECT gestor AS servidor, COUNT(*) total FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND gestor <> '' GROUP BY gestor
                    UNION ALL SELECT fiscal_demandante, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_demandante <> '' GROUP BY fiscal_demandante
                    UNION ALL SELECT fiscal_tecnico, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_tecnico <> '' GROUP BY fiscal_tecnico
                    UNION ALL SELECT gestor_substituto, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND gestor_substituto <> '' GROUP BY gestor_substituto
                    UNION ALL SELECT fiscal_substituto, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_substituto <> '' GROUP BY fiscal_substituto
                    UNION ALL SELECT fiscal_administrativo, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_administrativo <> '' GROUP BY fiscal_administrativo
                ) cargas WHERE servidor IS NOT NULL GROUP BY servidor ORDER BY total DESC LIMIT 12";
        return Database::pdo()->query($sql)->fetchAll();
    }
}

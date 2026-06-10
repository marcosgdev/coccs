<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Database;
use GestContratos\Core\Request;

final class ManagementController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $loads = Database::pdo()->query($this->loadSql())->fetchAll();
        $withoutManager = Database::pdo()->query("SELECT id, chave, fornecedor_nome, setor_nome FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND (gestor IS NULL OR gestor = '' OR gestor = 'sem indicação') ORDER BY data_termino ASC")->fetchAll();
        $withoutFiscal = Database::pdo()->query("SELECT id, chave, fornecedor_nome, setor_nome FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND ((fiscal_demandante IS NULL OR fiscal_demandante = '' OR fiscal_demandante = 'sem indicação') AND (fiscal_tecnico IS NULL OR fiscal_tecnico = '' OR fiscal_tecnico = 'sem indicação')) ORDER BY data_termino ASC")->fetchAll();

        $this->view('management/index', [
            'title' => 'Gestao e Fiscalizacao',
            'loads' => $loads,
            'withoutManager' => $withoutManager,
            'withoutFiscal' => $withoutFiscal,
        ]);
    }

    private function loadSql(): string
    {
        return "SELECT servidor, unidade, SUM(gestor) gestor, SUM(fiscal_demandante) fiscal_demandante,
                       SUM(fiscal_tecnico) fiscal_tecnico, SUM(gestor_substituto) gestor_substituto,
                       SUM(fiscal_substituto) fiscal_substituto, SUM(fiscal_administrativo) fiscal_administrativo,
                       SUM(total) total
                FROM (
                    SELECT gestor servidor, setor_nome unidade, COUNT(*) gestor, 0 fiscal_demandante, 0 fiscal_tecnico, 0 gestor_substituto, 0 fiscal_substituto, 0 fiscal_administrativo, COUNT(*) total
                    FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND gestor <> '' GROUP BY gestor, setor_nome
                    UNION ALL SELECT fiscal_demandante, setor_nome, 0, COUNT(*), 0, 0, 0, 0, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_demandante <> '' GROUP BY fiscal_demandante, setor_nome
                    UNION ALL SELECT fiscal_tecnico, setor_nome, 0, 0, COUNT(*), 0, 0, 0, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_tecnico <> '' GROUP BY fiscal_tecnico, setor_nome
                    UNION ALL SELECT gestor_substituto, setor_nome, 0, 0, 0, COUNT(*), 0, 0, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND gestor_substituto <> '' GROUP BY gestor_substituto, setor_nome
                    UNION ALL SELECT fiscal_substituto, setor_nome, 0, 0, 0, 0, COUNT(*), 0, COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_substituto <> '' GROUP BY fiscal_substituto, setor_nome
                    UNION ALL SELECT fiscal_administrativo, setor_nome, 0, 0, 0, 0, 0, COUNT(*), COUNT(*) FROM contratos WHERE deleted_at IS NULL AND situacao = 'Vigente' AND fiscal_administrativo <> '' GROUP BY fiscal_administrativo, setor_nome
                ) carga
                WHERE servidor IS NOT NULL AND servidor <> '' AND servidor <> 'sem indicação'
                GROUP BY servidor, unidade
                ORDER BY total DESC, servidor ASC";
    }
}

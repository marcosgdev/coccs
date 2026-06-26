<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Database;
use GestContratos\Core\Request;
use GestContratos\Services\SetorNormalizerService;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $pdo = Database::pdo();

        $this->view('dashboard/index', [
            'title'         => 'Dashboard',
            'kpis'          => $this->kpis($pdo),
            'alertasPrazos' => $this->alertasPrazos($pdo),
            'cargaSetor'    => $this->cargaSetor($pdo),
            'cargaFiscal'   => $this->cargaFiscal($pdo),
            'tendencias'    => $this->tendenciasBienio($pdo),
        ]);
    }

    private function kpis(\PDO $pdo): array
    {
        return $pdo->query("
            SELECT
                SUM(tipo='CONTRATO' AND situacao='Vigente')                                              AS c_vigentes,
                SUM(tipo='CONTRATO' AND situacao='Expirado')                                             AS c_expirados,
                SUM(tipo='CONTRATO' AND situacao='Vigente'
                    AND DATEDIFF(data_termino,CURDATE()) BETWEEN 0  AND 30)                              AS c_30d,
                SUM(tipo='CONTRATO' AND situacao='Vigente'
                    AND DATEDIFF(data_termino,CURDATE()) BETWEEN 31 AND 60)                              AS c_60d,
                SUM(tipo='CONTRATO' AND situacao='Vigente'
                    AND DATEDIFF(data_termino,CURDATE()) BETWEEN 61 AND 90)                              AS c_90d,
                SUM(tipo='ARP' AND situacao='Vigente')                                                   AS a_vigentes,
                SUM(tipo='ARP' AND situacao='Expirado')                                                  AS a_expiradas,
                SUM(tipo='ARP' AND situacao='Vigente'
                    AND DATEDIFF(data_termino,CURDATE()) BETWEEN 0  AND 30)                              AS a_30d,
                SUM(tipo='ARP' AND situacao='Vigente'
                    AND DATEDIFF(data_termino,CURDATE()) BETWEEN 31 AND 60)                              AS a_60d,
                SUM(tipo='ARP' AND situacao='Vigente'
                    AND DATEDIFF(data_termino,CURDATE()) BETWEEN 61 AND 90)                              AS a_90d,
                SUM(situacao='Vigente'
                    AND (gestor IS NULL OR gestor='' OR gestor='sem indicação'))                         AS sem_gestor,
                SUM(situacao='Vigente'
                    AND (fiscal_tecnico  IS NULL OR fiscal_tecnico ='' OR fiscal_tecnico ='sem indicação')
                    AND (fiscal_demandante IS NULL OR fiscal_demandante='' OR fiscal_demandante='sem indicação'))
                                                                                                         AS sem_fiscal,
                SUM(situacao='Vigente' AND COALESCE(quantidade_aditivos,0) > 0)                          AS com_aditivos,
                COUNT(DISTINCT CASE WHEN situacao='Vigente'
                    THEN COALESCE(NULLIF(setor_nome,''),'Sem setor') END)                                AS num_setores
            FROM contratos WHERE deleted_at IS NULL
        ")->fetch() ?: [];
    }

    private function alertasPrazos(\PDO $pdo): array
    {
        $sn = (new SetorNormalizerService($pdo))->sqlCase('setor_nome', "'Sem setor'");
        return $pdo->query("
            SELECT tipo, chave, fornecedor_nome,
                   $sn AS setor_nome,
                   data_termino,
                   DATEDIFF(data_termino, CURDATE()) AS dias,
                   gestor,
                   COALESCE(NULLIF(fiscal_tecnico,''), NULLIF(fiscal_demandante,'')) AS fiscal
            FROM contratos
            WHERE deleted_at IS NULL AND situacao = 'Vigente'
              AND DATEDIFF(data_termino, CURDATE()) BETWEEN 0 AND 90
            ORDER BY dias ASC
        ")->fetchAll();
    }

    private function cargaSetor(\PDO $pdo): array
    {
        $sn = (new SetorNormalizerService($pdo))->sqlCase('setor_nome', "'Sem setor'");
        return $pdo->query("
            SELECT
                $sn AS setor_nome,
                SUM(tipo='CONTRATO' AND situacao='Vigente')                   AS contratos,
                SUM(tipo='ARP'      AND situacao='Vigente')                   AS arps,
                SUM(situacao='Vigente')                                        AS total,
                SUM(situacao='Vigente'
                    AND DATEDIFF(data_termino,CURDATE()) BETWEEN 0 AND 90)    AS vencendo_90d,
                SUM(situacao='Vigente'
                    AND (gestor IS NULL OR gestor='' OR gestor='sem indicação')) AS sem_gestor
            FROM contratos
            WHERE deleted_at IS NULL
            GROUP BY 1
            HAVING total > 0
            ORDER BY total DESC
            LIMIT 15
        ")->fetchAll();
    }

    private function cargaFiscal(\PDO $pdo): array
    {
        return $pdo->query("
            SELECT servidor,
                   SUM(papel='gestor')      AS como_gestor,
                   SUM(papel='fiscal')      AS como_fiscal,
                   SUM(papel='sub')         AS como_sub,
                   COUNT(*)                  AS total
            FROM (
                SELECT NULLIF(TRIM(gestor),'') AS servidor, 'gestor' AS papel
                  FROM contratos WHERE deleted_at IS NULL AND situacao='Vigente'
                    AND gestor IS NOT NULL AND gestor <> '' AND gestor <> 'sem indicação'
                UNION ALL
                SELECT NULLIF(TRIM(fiscal_tecnico),''), 'fiscal'
                  FROM contratos WHERE deleted_at IS NULL AND situacao='Vigente'
                    AND fiscal_tecnico IS NOT NULL AND fiscal_tecnico <> '' AND fiscal_tecnico <> 'sem indicação'
                UNION ALL
                SELECT NULLIF(TRIM(fiscal_demandante),''), 'fiscal'
                  FROM contratos WHERE deleted_at IS NULL AND situacao='Vigente'
                    AND fiscal_demandante IS NOT NULL AND fiscal_demandante <> '' AND fiscal_demandante <> 'sem indicação'
                UNION ALL
                SELECT NULLIF(TRIM(gestor_substituto),''), 'sub'
                  FROM contratos WHERE deleted_at IS NULL AND situacao='Vigente'
                    AND gestor_substituto IS NOT NULL AND gestor_substituto <> ''
                UNION ALL
                SELECT NULLIF(TRIM(fiscal_substituto),''), 'sub'
                  FROM contratos WHERE deleted_at IS NULL AND situacao='Vigente'
                    AND fiscal_substituto IS NOT NULL AND fiscal_substituto <> ''
            ) t
            WHERE servidor IS NOT NULL AND servidor <> ''
            GROUP BY servidor
            ORDER BY total DESC
            LIMIT 15
        ")->fetchAll();
    }

    private function tendenciasBienio(\PDO $pdo): array
    {
        $config = [
            '2021-2023' => [2021, 2022],
            '2023-2025' => [2023, 2024],
            '2025-2027' => [2025, 2026],
        ];
        $result = [];
        foreach ($config as $label => [$a1, $a2]) {
            $st = $pdo->prepare("
                SELECT
                    SUM(tipo='CONTRATO')   AS contratos,
                    SUM(tipo='ARP')        AS arps,
                    COUNT(*)               AS total,
                    SUM(situacao='Vigente') AS vigentes,
                    SUM(COALESCE(quantidade_aditivos,0) > 0) AS com_aditivos,
                    ROUND(
                        SUM(situacao='Vigente'
                            AND gestor IS NOT NULL AND gestor <> '' AND gestor <> 'sem indicação')
                        / NULLIF(SUM(situacao='Vigente'), 0) * 100, 0
                    ) AS pct_gestor,
                    ROUND(
                        SUM(situacao='Vigente' AND (
                            (fiscal_tecnico IS NOT NULL AND fiscal_tecnico <> '' AND fiscal_tecnico <> 'sem indicação')
                            OR (fiscal_demandante IS NOT NULL AND fiscal_demandante <> '' AND fiscal_demandante <> 'sem indicação')
                        )) / NULLIF(SUM(situacao='Vigente'), 0) * 100, 0
                    ) AS pct_fiscal,
                    ROUND(AVG(DATEDIFF(data_termino, data_inicio) / 30.0), 1) AS prazo_medio_meses
                FROM contratos
                WHERE deleted_at IS NULL AND ano IN (?, ?)
            ");
            $st->execute([$a1, $a2]);
            $result[$label] = $st->fetch() ?: [];
        }
        return $result;
    }
}

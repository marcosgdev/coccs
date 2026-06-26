<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Csrf;
use GestContratos\Core\Database;
use GestContratos\Models\Additive;

final class AdditivesController extends ResourceController
{
    public function __construct()
    {
        $this->model = new Additive();
        $this->table = 'aditivos';
        $this->title = 'Aditivos';
        $this->route = '/aditivos';
        $this->columns = [
            'contrato_id' => 'Contrato ID', 'numero_aditivo' => 'Aditivo', 'tipo_aditivo' => 'Tipo',
            'data_aditivo' => 'Data', 'valor_acrescido' => 'Acrescimo', 'valor_suprimido' => 'Supressao',
            'nova_data_termino' => 'Novo termino',
        ];
        $this->fields = [
            ['name' => 'contrato_id', 'label' => 'ID do contrato', 'type' => 'number', 'required' => true],
            ['name' => 'numero_aditivo', 'label' => 'Numero do aditivo'],
            ['name' => 'tipo_aditivo', 'label' => 'Tipo do aditivo'],
            ['name' => 'data_aditivo', 'label' => 'Data', 'type' => 'date'],
            ['name' => 'objeto', 'label' => 'Objeto', 'type' => 'textarea'],
            ['name' => 'valor_acrescido', 'label' => 'Valor acrescido', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'valor_suprimido', 'label' => 'Valor suprimido', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'nova_data_termino', 'label' => 'Nova data de termino', 'type' => 'date'],
            ['name' => 'justificativa', 'label' => 'Justificativa', 'type' => 'textarea'],
            ['name' => 'observacoes', 'label' => 'Observacoes', 'type' => 'textarea'],
        ];
    }

    public function index(\GestContratos\Core\Request $request): void
    {
        $this->requireAuth();
        $pdo = Database::pdo();

        // Auto-cria tabela de flags de processo de renovação
        $pdo->exec("CREATE TABLE IF NOT EXISTS contrato_renovacao_flags (
            contrato_id INT NOT NULL PRIMARY KEY,
            status VARCHAR(50) NOT NULL DEFAULT 'aguardando',
            iniciado_em DATETIME NULL,
            iniciado_por VARCHAR(150) NULL,
            observacao TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ── Todos os aditivos ──────────────────────────────────────────────
        $items = $this->model->all($this->orderBy(), [], 2000);

        // ── Contratos com prorrogações ─────────────────────────────────────
        $stmt = $pdo->prepare("
            SELECT
                c.id, c.chave, c.fornecedor_nome, c.situacao,
                c.data_inicio, c.data_termino,
                c.valor_global_inicial, c.valor_global_atualizado, c.valor_executado,
                c.setor_nome, c.gestor, c.fiscal_tecnico,
                COUNT(a.id)                        AS qtd_prorrogacoes,
                MAX(a.data_aditivo)                AS ultima_prorrogacao,
                MAX(a.nova_data_termino)           AS ultima_nova_termino,
                SUM(COALESCE(a.valor_acrescido,0)) AS total_acrescido
            FROM contratos c
            JOIN aditivos a ON a.contrato_id = c.id AND a.deleted_at IS NULL
                AND (a.tipo_aditivo LIKE '%prorrog%' OR a.objeto LIKE '%prorrog%')
            WHERE c.deleted_at IS NULL
            GROUP BY c.id
            ORDER BY c.data_termino ASC
        ");
        $stmt->execute();
        $contratos = $stmt->fetchAll();

        // Flags de processo
        $flagsRows = $pdo->query("SELECT contrato_id, status, iniciado_em, iniciado_por FROM contrato_renovacao_flags")->fetchAll();
        $flags = array_column($flagsRows, null, 'contrato_id');

        $hoje         = time();
        $limiteMax    = 60;
        $leadTimeDias = 60;

        foreach ($contratos as &$c) {
            $tI = $c['data_inicio']  ? strtotime($c['data_inicio'])  : null;
            $tF = $c['data_termino'] ? strtotime($c['data_termino']) : null;

            $c['dias_restantes']     = $tF ? (int) round(($tF - $hoje) / 86400) : null;
            $c['meses_totais']       = ($tI && $tF) ? round(($tF - $tI) / (86400 * 30.4375), 1) : null;
            $c['pct_legal']          = $c['meses_totais'] ? min(100, round($c['meses_totais'] / $limiteMax * 100)) : null;
            $c['data_inicio_proc']   = $tF ? date('Y-m-d', $tF - $leadTimeDias * 86400) : null;
            $c['lead_venceu']        = $c['data_inicio_proc'] && $c['data_inicio_proc'] < date('Y-m-d');
            $c['dias_para_iniciar']  = $c['data_inicio_proc'] ? (int) round((strtotime($c['data_inicio_proc']) - $hoje) / 86400) : null;
            $c['flag_status']        = $flags[$c['id']]['status'] ?? 'aguardando';
            $c['flag_iniciado_em']   = $flags[$c['id']]['iniciado_em'] ?? null;
            $c['flag_iniciado_por']  = $flags[$c['id']]['iniciado_por'] ?? null;

            $score = 0;
            if ($c['dias_restantes'] !== null) {
                if ($c['dias_restantes'] < 0)       $score += 50;
                elseif ($c['dias_restantes'] < 30)  $score += 40;
                elseif ($c['dias_restantes'] < 90)  $score += 25;
                elseif ($c['dias_restantes'] < 180) $score += 10;
            }
            if ($c['pct_legal'] !== null) {
                if ($c['pct_legal'] >= 95)     $score += 40;
                elseif ($c['pct_legal'] >= 80) $score += 25;
                elseif ($c['pct_legal'] >= 60) $score += 10;
            }
            $c['score_risco'] = min(100, $score);
            [$c['score_label'], $c['score_cls']] = match(true) {
                $c['score_risco'] >= 70 => ['Crítico',  'danger'],
                $c['score_risco'] >= 40 => ['Atenção',  'warning'],
                $c['score_risco'] >= 15 => ['Moderado', 'info'],
                default                 => ['Saudável', 'success'],
            };
        }
        unset($c);
        usort($contratos, fn($a, $b) => $b['score_risco'] - $a['score_risco']);

        // ── Fila de Antecipação ────────────────────────────────────────────
        $queue = ['urgente' => [], 'semana' => [], 'mes' => [], 'trimestre' => [], 'planejado' => []];
        foreach ($contratos as $c) {
            if ($c['flag_status'] === 'concluido') continue;
            $d = $c['dias_para_iniciar'];
            if ($d === null) continue;
            if ($d < 0)        $queue['urgente'][]    = $c;
            elseif ($d <= 7)   $queue['semana'][]     = $c;
            elseif ($d <= 30)  $queue['mes'][]        = $c;
            elseif ($d <= 90)  $queue['trimestre'][]  = $c;
            else               $queue['planejado'][]  = $c;
        }

        // ── Gantt ─────────────────────────────────────────────────────────
        $ganttStart = $hoje;
        $ganttEnd   = strtotime('+12 months');
        $ganttSpan  = $ganttEnd - $ganttStart;
        $ganttMeses = [];
        for ($i = 0; $i < 12; $i++) {
            $ganttMeses[] = date('M/y', strtotime("+$i months"));
        }
        $ganttContratos = [];
        foreach ($contratos as $c) {
            $tF = $c['data_termino'] ? strtotime($c['data_termino']) : null;
            if (!$tF) continue;
            $barEnd   = min($ganttEnd, $tF);
            $barLeft  = 0;
            $barWidth = max(1, round(($barEnd - $ganttStart) / $ganttSpan * 100, 1));
            if ($barEnd <= $ganttStart) continue;

            $procStart  = $c['data_inicio_proc'] ? strtotime($c['data_inicio_proc']) : null;
            $procLeft   = ($procStart && $procStart > $ganttStart)
                ? round(($procStart - $ganttStart) / $ganttSpan * 100, 1) : null;

            $ganttContratos[] = $c + [
                'gantt_width'    => $barWidth,
                'gantt_proc_pct' => $procLeft,
            ];
        }
        usort($ganttContratos, fn($a, $b) =>
            strtotime($a['data_termino'] ?? '9999') <=> strtotime($b['data_termino'] ?? '9999')
        );

        // ── Índice de saúde ───────────────────────────────────────────────
        $totalContratos  = count($contratos);
        $mediaScore      = $totalContratos > 0 ? round(array_sum(array_column($contratos, 'score_risco')) / $totalContratos) : 0;
        $portfolioHealth = 100 - $mediaScore;
        [$healthLabel, $healthCls] = match(true) {
            $portfolioHealth >= 80 => ['Saudável',  'success'],
            $portfolioHealth >= 60 => ['Moderado',  'info'],
            $portfolioHealth >= 40 => ['Em Alerta', 'warning'],
            default                => ['Crítico',   'danger'],
        };

        // ── Mapa de calor ─────────────────────────────────────────────────
        $heatStmt = $pdo->query("
            SELECT DATE_FORMAT(data_termino,'%Y-%m') AS mes,
                   COUNT(*)                           AS total,
                   SUM(valor_global_atualizado)       AS valor_total,
                   AVG(DATEDIFF(data_termino, CURDATE())) AS media_dias
            FROM contratos
            WHERE deleted_at IS NULL AND data_termino >= CURDATE()
              AND data_termino <= DATE_ADD(CURDATE(), INTERVAL 24 MONTH)
            GROUP BY mes ORDER BY mes
        ");
        $heatRawFull = $heatStmt->fetchAll();
        $heatRaw  = array_column($heatRawFull, 'total', 'mes');
        $heatVal  = array_column($heatRawFull, 'valor_total', 'mes');
        $heatMax  = $heatRaw ? max($heatRaw) : 1;
        $heatData = [];
        for ($i = 0; $i < 24; $i++) {
            $key = date('Y-m', strtotime("+$i months"));
            $heatData[$key] = [
                'total' => $heatRaw[$key] ?? 0,
                'valor' => (float)($heatVal[$key] ?? 0),
            ];
        }

        // Contratos por mês para o painel de detalhe (clique no mês)
        $heatDetailStmt = $pdo->query("
            SELECT DATE_FORMAT(data_termino,'%Y-%m') AS mes,
                   id, chave, fornecedor_nome, gestor, situacao,
                   data_termino, valor_global_atualizado,
                   DATEDIFF(data_termino, CURDATE()) AS dias_restantes
            FROM contratos
            WHERE deleted_at IS NULL AND data_termino >= CURDATE()
              AND data_termino <= DATE_ADD(CURDATE(), INTERVAL 24 MONTH)
            ORDER BY data_termino ASC
        ");
        $heatDetailRaw = $heatDetailStmt->fetchAll();
        $heatDetail = [];
        foreach ($heatDetailRaw as $row) {
            $heatDetail[$row['mes']][] = $row;
        }

        // ── Previsão de carga ─────────────────────────────────────────────
        $cargaStmt = $pdo->query("
            SELECT DATE_FORMAT(DATE_SUB(data_termino, INTERVAL 60 DAY),'%Y-%m') AS mes_acao,
                   COUNT(*) AS total, SUM(valor_global_atualizado) AS valor_total
            FROM contratos
            WHERE deleted_at IS NULL
              AND data_termino BETWEEN DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                                   AND DATE_ADD(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY mes_acao ORDER BY mes_acao
        ");
        $cargaRaw = $cargaStmt->fetchAll();
        $cargaMeses = []; $cargaTotais = []; $cargaValores = [];
        foreach ($cargaRaw as $row) {
            $cargaMeses[]   = date('M/y', strtotime($row['mes_acao'] . '-01'));
            $cargaTotais[]  = (int) $row['total'];
            $cargaValores[] = round((float) $row['valor_total'] / 1e6, 2);
        }

        // ── Scorecard por gestor ──────────────────────────────────────────
        $gestores = [];
        foreach ($contratos as $c) {
            $g = trim($c['gestor'] ?? '') ?: 'Sem gestor atribuído';
            if (!isset($gestores[$g])) {
                $gestores[$g] = ['nome' => $g, 'total' => 0, 'critico' => 0, 'atencao' => 0, 'saudavel' => 0, 'scores' => [], 'contratos' => []];
            }
            $gestores[$g]['total']++;
            $gestores[$g]['scores'][]    = $c['score_risco'];
            $gestores[$g]['contratos'][] = $c;
            if ($c['score_risco'] >= 70)     $gestores[$g]['critico']++;
            elseif ($c['score_risco'] >= 40) $gestores[$g]['atencao']++;
            else                             $gestores[$g]['saudavel']++;
        }
        foreach ($gestores as &$g) {
            $g['score_medio'] = count($g['scores']) ? round(array_sum($g['scores']) / count($g['scores'])) : 0;
            [$g['score_label'], $g['score_cls']] = match(true) {
                $g['score_medio'] >= 70 => ['Crítico',  'danger'],
                $g['score_medio'] >= 40 => ['Atenção',  'warning'],
                $g['score_medio'] >= 15 => ['Moderado', 'info'],
                default                 => ['Saudável', 'success'],
            };
        }
        unset($g);
        usort($gestores, fn($a, $b) => $b['score_medio'] - $a['score_medio']);

        // ── Radar de Antecipação — DNA + Posição Relativa ─────────────────
        $dnaStmt = $pdo->prepare("
            SELECT a.contrato_id, c.chave, c.fornecedor_nome, c.gestor,
                   c.data_termino AS termino_atual,
                   a.data_aditivo, a.nova_data_termino,
                   COALESCE(a.numero_aditivo, '') AS numero_aditivo
            FROM aditivos a
            JOIN contratos c ON c.id = a.contrato_id
            WHERE a.deleted_at IS NULL
              AND (a.tipo_aditivo LIKE '%prorrog%' OR a.objeto LIKE '%prorrog%')
              AND a.data_aditivo IS NOT NULL AND a.nova_data_termino IS NOT NULL
            ORDER BY a.contrato_id, a.nova_data_termino ASC
        ");
        $dnaStmt->execute();
        $dnaRows = $dnaStmt->fetchAll();
        $dnaByContract = [];
        foreach ($dnaRows as $row) {
            $dnaByContract[$row['contrato_id']][] = $row;
        }

        $dnaStats = [];
        foreach ($dnaByContract as $cid => $rows) {
            // Deduplica por numero_aditivo — um mesmo aditivo pode gerar múltiplas linhas
            $seen = [];
            $unique = [];
            foreach ($rows as $row) {
                $key = $row['numero_aditivo'] !== '' ? $row['numero_aditivo'] : $row['data_aditivo'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique[] = $row;
                }
            }
            $rows = $unique;
            if (count($rows) < 3) continue; // mínimo 2 intervalos para padrão histórico
            $leadTimes = [];
            for ($i = 1; $i < count($rows); $i++) {
                $leadTimes[] = (int) round((strtotime($rows[$i-1]['nova_data_termino']) - strtotime($rows[$i]['data_aditivo'])) / 86400);
            }
            if (!$leadTimes) continue;
            $avg         = round(array_sum($leadTimes) / count($leadTimes));
            $trend       = count($leadTimes) >= 2 ? ($leadTimes[count($leadTimes)-1] - $leadTimes[0]) : 0;
            $termino     = $rows[0]['termino_atual'];
            $diasRest    = $termino ? (int) round((strtotime($termino) - $hoje) / 86400) : null;
            $idealStart  = ($termino && $avg > 0) ? date('Y-m-d', strtotime($termino) - $avg * 86400) : null;
            $posRelativa = ($diasRest !== null) ? $diasRest - $avg : null;
            // Fora da janela = contrato ainda tem mais de avg_lead+90 dias restantes
            $foraJanela  = ($diasRest !== null && $diasRest > $avg + 90);

            $dnaStats[] = [
                'contrato_id'     => $cid,
                'chave'           => $rows[0]['chave'],
                'fornecedor'      => $rows[0]['fornecedor_nome'],
                'gestor'          => $rows[0]['gestor'],
                'lead_times'      => $leadTimes,
                'avg_lead'        => $avg,
                'min_lead'        => min($leadTimes),
                'max_lead'        => max($leadTimes),
                'trend'           => $trend,
                'qtd'             => count($rows),
                'termino_atual'   => $termino,
                'dias_restantes'  => $diasRest,
                'ideal_start'     => $idealStart,
                'alerta_dna'      => $diasRest !== null && $idealStart && $idealStart < date('Y-m-d') && $diasRest > 0,
                'risco_tardio'    => $avg < 30,
                'posicao_relativa'=> $posRelativa,
                'fora_janela'     => $foraJanela,
            ];
        }
        usort($dnaStats, fn($a, $b) => ($a['dias_restantes'] ?? 9999) <=> ($b['dias_restantes'] ?? 9999));

        $this->view('additives/index', [
            'title'           => 'Aditivos',
            'items'           => $items,
            'columns'         => $this->columns,
            'route'           => $this->route,
            'contratos'       => $contratos,
            'limiteMax'       => $limiteMax,
            'queue'           => $queue,
            'ganttContratos'  => $ganttContratos,
            'ganttMeses'      => $ganttMeses,
            'portfolioHealth' => $portfolioHealth,
            'healthLabel'     => $healthLabel,
            'healthCls'       => $healthCls,
            'mediaScore'      => $mediaScore,
            'heatData'        => $heatData,
            'heatMax'         => $heatMax,
            'heatDetail'      => $heatDetail,
            'cargaMeses'      => json_encode($cargaMeses),
            'cargaTotais'     => json_encode($cargaTotais),
            'cargaValores'    => json_encode($cargaValores),
            'gestores'        => $gestores,
            'dnaStats'        => $dnaStats,
        ]);
    }

    // ── AJAX: atualiza status do processo de renovação ─────────────────────
    public function processoStatus(\GestContratos\Core\Request $request): void
    {
        header('Content-Type: application/json');
        if (!Auth::check()) { http_response_code(401); echo json_encode(['ok' => false]); return; }
        if (!Csrf::verify((string) ($request->body['_csrf'] ?? ''))) {
            http_response_code(403); echo json_encode(['ok' => false, 'error' => 'CSRF inválido']); return;
        }

        $cid    = (int) ($request->body['contrato_id'] ?? 0);
        $status = $request->body['status'] ?? 'aguardando';
        $obs    = substr($request->body['observacao'] ?? '', 0, 500);
        $user   = Auth::user();

        $allowed = ['aguardando', 'iniciado', 'em_revisao', 'aguardando_assinatura', 'concluido'];
        if (!$cid || !in_array($status, $allowed)) {
            http_response_code(422); echo json_encode(['ok' => false, 'error' => 'Dados inválidos']); return;
        }

        $pdo  = Database::pdo();
        $nome = $user['nome'] ?? ($user['email'] ?? 'Sistema');
        $stmt = $pdo->prepare("
            INSERT INTO contrato_renovacao_flags (contrato_id, status, iniciado_em, iniciado_por, observacao)
            VALUES (?, ?, NOW(), ?, ?)
            ON DUPLICATE KEY UPDATE
                status       = VALUES(status),
                iniciado_em  = IF(status = 'aguardando', NOW(), iniciado_em),
                iniciado_por = VALUES(iniciado_por),
                observacao   = VALUES(observacao)
        ");
        $stmt->execute([$cid, $status, $nome, $obs]);

        echo json_encode(['ok' => true, 'status' => $status]);
    }
}

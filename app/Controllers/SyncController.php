<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Csrf;
use GestContratos\Core\Database;
use GestContratos\Core\Request;
use GestContratos\Services\TjpaApiService;
use GestContratos\Services\TjpaSyncService;

final class SyncController extends Controller
{
    public function tjpa(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canSync());

        set_time_limit(600); // sync + liquidações pode demorar até 10 min

        $service = new TjpaSyncService();

        try {
            $result = $service->sync();
            $this->jsonResponse(['success' => true] + $result);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'success'  => false,
                'error'    => $e->getMessage(),
                'created'  => 0,
                'updated'  => 0,
                'errors'   => 1,
                'total'    => 0,
                'duration' => 0,
                'messages' => [],
            ]);
        }
    }

    public function allLiquidacoes(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(600);

        if (!\GestContratos\Core\Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Não autenticado']);
            exit;
        }

        if (!Csrf::verify((string) ($request->input('_csrf', '')))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            exit;
        }

        $pdo  = Database::pdo();
        $api  = new TjpaApiService();

        // Busca empenhos: sem liquidação, desatualizados (>7 dias) ou sem eventos na tabela de liquidacoes
        $rows = $pdo->query(
            'SELECT ce.empenho, ce.contrato_id
             FROM contrato_empenhos ce
             WHERE ce.valor_liquidado IS NULL
                OR ce.liquidado_em < DATE_SUB(NOW(), INTERVAL 7 DAY)
                OR NOT EXISTS (
                    SELECT 1 FROM contrato_liquidacoes cl WHERE cl.empenho = ce.empenho
                )
             ORDER BY ce.contrato_id'
        )->fetchAll();

        if (!$rows) {
            echo json_encode(['success' => true, 'atualizados' => 0, 'mensagem' => 'Nenhum empenho pendente.']);
            exit;
        }

        $empenhos = array_column($rows, 'empenho');
        $liquidacoes = $api->fetchLiquidacoes($empenhos);

        // Mapa empenho → contrato_id
        $stmtMap = $pdo->prepare('SELECT empenho, contrato_id FROM contrato_empenhos WHERE empenho = ?');

        $upd = $pdo->prepare(
            'UPDATE contrato_empenhos SET valor_liquidado=?, valor_pago=?, liquidado_em=NOW()
             WHERE empenho=?'
        );
        $insLiq = $pdo->prepare(
            'INSERT INTO contrato_liquidacoes
                (contrato_id, empenho, liquidacao, data_liquidacao, valor_liquidacao, observacao)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
                data_liquidacao=VALUES(data_liquidacao),
                valor_liquidacao=VALUES(valor_liquidacao),
                observacao=VALUES(observacao)'
        );

        $atualizados = 0;
        foreach ($liquidacoes as $emp => $vals) {
            $upd->execute([$vals['valorLiquidacao'], $vals['valorPago'], $emp]);
            $atualizados++;

            $stmtMap->execute([$emp]);
            $row = $stmtMap->fetch();
            if (!$row) continue;
            $contratoId = (int) $row['contrato_id'];

            foreach (($vals['liquidacoes'] ?? []) as $liq) {
                if (empty($liq['liquidacao']) || empty($liq['dataLiquidacao'])) continue;
                $data = preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $liq['dataLiquidacao'], $m)
                    ? "$m[3]-$m[2]-$m[1]" : null;
                if (!$data) continue;
                $insLiq->execute([
                    $contratoId, $emp,
                    $liq['liquidacao'], $data,
                    (float) ($liq['valorLiquidacao'] ?? 0),
                    $liq['observacao'] ?? null,
                ]);
            }
        }

        // Totais consolidados para atualizar o pipeline do dashboard
        $totais = $pdo->query(
            'SELECT YEAR(ce.data_empenho) AS exercicio,
                    SUM(ce.valor)                      AS empenhado,
                    COALESCE(SUM(cl_sum.liquidado), 0) AS liquidado,
                    COUNT(*)                           AS total_emp,
                    COUNT(cl_sum.empenho)              AS com_liq
             FROM contrato_empenhos ce
             LEFT JOIN (
                 SELECT empenho, SUM(valor_liquidacao) AS liquidado
                 FROM contrato_liquidacoes
                 GROUP BY empenho
             ) cl_sum ON cl_sum.empenho = ce.empenho
             WHERE ce.data_empenho IS NOT NULL
             GROUP BY YEAR(ce.data_empenho)
             ORDER BY exercicio'
        )->fetchAll();

        echo json_encode([
            'success'      => true,
            'pendentes'    => count($rows),
            'atualizados'  => $atualizados,
            'pipeline'     => $totais,
        ]);
        exit;
    }

    public function syncAditivosDatas(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        set_time_limit(600);

        if (!\GestContratos\Core\Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Não autenticado']);
            exit;
        }
        if (!Csrf::verify((string) ($request->input('_csrf', '')))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'CSRF inválido']);
            exit;
        }

        $pdo = Database::pdo();
        $api = new TjpaApiService();

        // Busca contratos que têm aditivos de prorrogação sem nova_data_termino
        $rows = $pdo->query(
            'SELECT DISTINCT c.id, c.numero, c.ano
             FROM contratos c
             JOIN aditivos a ON a.contrato_id = c.id
             WHERE a.deleted_at IS NULL
               AND a.nova_data_termino IS NULL
               AND (
                   LOWER(a.tipo_aditivo) LIKE "%prorrog%"
                   OR LOWER(a.objeto) LIKE "%prorrog%"
               )
             AND c.deleted_at IS NULL'
        )->fetchAll();

        if (!$rows) {
            echo json_encode(['success' => true, 'atualizados' => 0, 'mensagem' => 'Nenhum aditivo pendente.']);
            exit;
        }

        $contratos = array_map(fn($r) => [
            'numero'    => $r['numero'],
            'exercicio' => $r['ano'],
        ], $rows);

        // Mapa "numero/ano" → contrato_id
        $contratoMap = [];
        foreach ($rows as $r) {
            $contratoMap[$r['numero'] . '/' . $r['ano']] = (int) $r['id'];
        }

        $detalhes = $api->fetchAditivosDetalhados($contratos);

        $upd = $pdo->prepare(
            'UPDATE aditivos SET nova_data_termino = ?
             WHERE contrato_id = ? AND numero_aditivo = ? AND nova_data_termino IS NULL'
        );

        $atualizados = 0;
        foreach ($detalhes as $chave => $aditivos) {
            $cid = $contratoMap[$chave] ?? null;
            if (!$cid) continue;
            foreach ($aditivos as $ad) {
                $upd->execute([$ad['data_final'], $cid, $ad['numero_aditivo']]);
                $atualizados += $upd->rowCount();
            }
        }

        echo json_encode([
            'success'     => true,
            'contratos'   => count($rows),
            'atualizados' => $atualizados,
        ]);
        exit;
    }

    public function resetContratos(Request $request): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canSync());

        if (!Csrf::verify((string) ($request->input('_csrf', '')))) {
            http_response_code(403);
            $this->jsonResponse(['success' => false, 'error' => 'CSRF inválido']);
        }

        $pdo = Database::pdo();

        try {
            $pdo->beginTransaction();

            // IDs de contratos do tipo CONTRATO (preserva ARPs)
            $ids = $pdo->query(
                "SELECT id FROM contratos WHERE tipo = 'CONTRATO'"
            )->fetchAll(\PDO::FETCH_COLUMN);

            if ($ids) {
                $in = implode(',', array_map('intval', $ids));

                $pdo->exec("DELETE FROM aditivos               WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM contrato_empenhos      WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM contrato_liquidacoes   WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM contrato_itens         WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM contrato_eventos       WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM contrato_documentos    WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM contrato_responsaveis  WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM execucoes_financeiras  WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM notificacoes           WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM prorrogacoes           WHERE contrato_id IN ($in)");
                $pdo->exec("DELETE FROM contratos              WHERE id          IN ($in)");
            }

            $pdo->commit();

            $this->jsonResponse([
                'success'  => true,
                'deletados' => count($ids),
                'message'  => count($ids) . ' contratos removidos. Execute a sincronização para reimportar.',
            ]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function jsonResponse(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

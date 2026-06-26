<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Services\TjpaApiService;

final class TjpaApiController extends Controller
{
    private TjpaApiService $api;

    public function __construct()
    {
        $this->api = new TjpaApiService();
    }

    public function contratos(Request $request): void
    {
        $this->requireAuth();
        $q = $request->query;
        $data = $this->api->searchContratos([
            'exercicio'                 => $q['exercicio'] ?? '',
            'numero'                    => $q['numero'] ?? '',
            'nomeContratado'            => $q['nomeContratado'] ?? '',
            'documentoContratado'       => $q['documentoContratado'] ?? '',
            'descricaoSituacaoContrato' => $q['descricaoSituacaoContrato'] ?? '',
        ]);
        $this->jsonResponse($data);
    }

    public function aditivos(Request $request): void
    {
        $this->requireAuth();
        $q = $request->query;
        $data = $this->api->searchAditivos([
            'exercicioContrato' => $q['exercicioContrato'] ?? '',
            'numeroContrato'    => $q['numeroContrato'] ?? '',
        ]);
        $this->jsonResponse($data);
    }

    public function empenhos(Request $request): void
    {
        $this->requireAuth();
        $ids = trim($request->query['ids'] ?? '');
        if (!$ids) {
            $this->jsonResponse([]);
            return;
        }
        $list = array_filter(array_map('trim', explode(',', $ids)));
        $data = $this->api->getEmpenhosBatch($list);
        $this->jsonResponse($data);
    }

    private function jsonResponse(mixed $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

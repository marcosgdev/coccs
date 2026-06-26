<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\ContractTracking;
use GestContratos\Services\AuditService;

final class TrackingController extends Controller
{
    private ContractTracking $model;

    public function __construct()
    {
        $this->model = new ContractTracking();
    }

    public function store(Request $request, string $contratoId): void
    {
        $this->requireCan(Auth::canWrite());
        $this->validateCsrf($request);

        $tipo = $request->input('tipo', 'geral');
        $data = [
            'contrato_id'        => (int) $contratoId,
            'tipo'               => $tipo,
            'titulo'             => $request->input('titulo') ?: null,
            'descricao'          => $request->input('descricao') ?: null,
            'data_referencia'    => $request->input('data_referencia') ?: date('Y-m-d'),
            'created_by'         => Auth::id(),
        ];

        if ($tipo === 'prorrogacao') {
            $data['tipo_prorrogacao']   = $request->input('tipo_prorrogacao') ?: null;
            $data['prazo_apresentacao'] = $request->input('prazo_apresentacao') ?: null;
            $data['apresentado_em']     = $request->input('apresentado_em') ?: null;
            $data['resultado']          = $request->input('resultado') ?: 'pendente';
        }

        if ($tipo === 'reajuste') {
            $data['indice_reajuste']     = $request->input('indice_reajuste') ?: null;
            $data['percentual_reajuste'] = $request->input('percentual_reajuste') !== '' ? (float) str_replace(',', '.', $request->input('percentual_reajuste', '0')) : null;
            $data['valor_anterior']      = $request->input('valor_anterior') !== '' ? (float) str_replace(['.', ','], ['', '.'], $request->input('valor_anterior', '0')) : null;
            $data['valor_reajustado']    = $request->input('valor_reajustado') !== '' ? (float) str_replace(['.', ','], ['', '.'], $request->input('valor_reajustado', '0')) : null;
        }

        if ($tipo === 'alerta') {
            $data['data_referencia'] = $request->input('data_referencia') ?: date('Y-m-d');
        }

        $id = $this->model->create($data);
        (new AuditService())->log('criacao_acompanhamento', 'contrato_acompanhamentos', $id, [], $data);
        flash('success', 'Registro adicionado ao acompanhamento.');
        redirect('/contratos/' . $contratoId . '#tab-acompanhamento');
    }

    public function destroy(Request $request, string $contratoId, string $id): void
    {
        $this->requireCan(Auth::canWrite());
        $this->validateCsrf($request);
        $item = $this->model->find((int) $id);
        if ($item && (int) $item['contrato_id'] === (int) $contratoId) {
            $this->model->delete((int) $id);
            (new AuditService())->log('exclusao_acompanhamento', 'contrato_acompanhamentos', $id, $item, []);
            flash('success', 'Registro removido.');
        }
        redirect('/contratos/' . $contratoId . '#tab-acompanhamento');
    }
}

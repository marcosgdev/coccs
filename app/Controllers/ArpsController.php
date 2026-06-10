<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Request;
use GestContratos\Models\Arp;
use GestContratos\Services\ContractRulesService;

final class ArpsController extends ResourceController
{
    public function __construct()
    {
        $this->model = new Arp();
        $this->table = 'arps';
        $this->title = 'ARPs / Atas';
        $this->route = '/arps';
        $this->columns = [
            'chave' => 'Chave', 'fornecedor_nome' => 'Fornecedor', 'vigencia_final' => 'Termino',
            'valor_total' => 'Valor total', 'saldo' => 'Saldo', 'situacao' => 'Situacao',
        ];
        $this->fields = [
            ['name' => 'numero_ata', 'label' => 'Numero da ata', 'required' => true],
            ['name' => 'ano', 'label' => 'Ano', 'type' => 'number', 'required' => true],
            ['name' => 'fornecedor_nome', 'label' => 'Fornecedor'],
            ['name' => 'objeto', 'label' => 'Objeto', 'type' => 'textarea'],
            ['name' => 'vigencia_inicial', 'label' => 'Vigencia inicial', 'type' => 'date'],
            ['name' => 'vigencia_final', 'label' => 'Vigencia final', 'type' => 'date'],
            ['name' => 'valor_total', 'label' => 'Valor total', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'valor_por_fornecedor', 'label' => 'Valor por fornecedor', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'valor_executado', 'label' => 'Valor executado', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'setor_nome', 'label' => 'Setor demandante'],
            ['name' => 'observacoes', 'label' => 'Observacoes', 'type' => 'textarea'],
        ];
    }

    protected function prepareData(array $data, Request $request): array
    {
        $rules = new ContractRulesService();
        $data['chave'] = $rules->generateKey('ARP', $data['numero_ata'] ?? '', (int) ($data['ano'] ?? date('Y')));
        $end = ! empty($data['vigencia_final']) ? new \DateTimeImmutable($data['vigencia_final']) : null;
        $remaining = $rules->daysRemaining($end);
        $data['dias_restantes'] = is_int($remaining) ? $remaining : null;
        $data['situacao'] = $rules->situation($end);
        $data['saldo'] = (float) ($data['valor_total'] ?? 0) - (float) ($data['valor_executado'] ?? 0);
        return $data;
    }
}

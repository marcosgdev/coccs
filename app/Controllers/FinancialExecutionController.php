<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Request;
use GestContratos\Models\FinancialExecution;

final class FinancialExecutionController extends ResourceController
{
    public function __construct()
    {
        $this->model = new FinancialExecution();
        $this->table = 'execucoes_financeiras';
        $this->title = 'Execucao Financeira';
        $this->route = '/execucao-financeira';
        $this->columns = [
            'chave' => 'Contrato/ARP', 'exercicio' => 'Exercicio',
            'valor_atualizado' => 'Valor atualizado', 'valor_executado_exercicio' => 'Executado',
            'valor_acumulado' => 'Acumulado', 'saldo' => 'Saldo',
        ];
        $this->fields = [
            ['name' => 'chave', 'label' => 'Chave contrato/ARP', 'required' => true],
            ['name' => 'exercicio', 'label' => 'Exercicio', 'type' => 'number', 'required' => true],
            ['name' => 'valor_inicial', 'label' => 'Valor inicial', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'valor_atualizado', 'label' => 'Valor atualizado', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'valor_executado_exercicio', 'label' => 'Valor executado no exercicio', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'valor_acumulado', 'label' => 'Valor acumulado', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'observacoes', 'label' => 'Observacoes', 'type' => 'textarea'],
        ];
    }

    protected function prepareData(array $data, Request $request): array
    {
        $data['saldo'] = (float) ($data['valor_atualizado'] ?? 0) - (float) ($data['valor_acumulado'] ?? 0);
        return $data;
    }
}

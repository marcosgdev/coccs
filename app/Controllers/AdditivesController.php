<?php

namespace GestContratos\Controllers;

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
}

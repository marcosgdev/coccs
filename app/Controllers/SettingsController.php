<?php

namespace GestContratos\Controllers;

use GestContratos\Models\Parameter;

final class SettingsController extends ResourceController
{
    public function __construct()
    {
        $this->model = new Parameter();
        $this->table = 'parametros_sistema';
        $this->title = 'Configuracoes';
        $this->route = '/configuracoes';
        $this->roles = ['administrador'];
        $this->columns = ['chave' => 'Chave', 'valor' => 'Valor', 'descricao' => 'Descricao'];
        $this->fields = [
            ['name' => 'chave', 'label' => 'Chave', 'required' => true],
            ['name' => 'valor', 'label' => 'Valor', 'required' => true],
            ['name' => 'descricao', 'label' => 'Descricao', 'type' => 'textarea'],
        ];
    }
}

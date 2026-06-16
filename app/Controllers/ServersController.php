<?php

namespace GestContratos\Controllers;

use GestContratos\Models\GenericModel;

final class ServersController extends ResourceController
{
    public function __construct()
    {
        $this->model = new GenericModel('servidores', [
            'nome', 'matricula', 'cargo', 'unidade', 'email', 'telefone', 'ativo',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ]);
        $this->table = 'servidores';
        $this->title = 'Servidores';
        $this->route = '/servidores';
        $this->roles = ['administrador'];
        $this->indexRequiresPermission = true;
        $this->columns = ['nome' => 'Nome', 'matricula' => 'Matricula', 'unidade' => 'Unidade', 'email' => 'E-mail', 'ativo' => 'Ativo'];
        $this->fields = [
            ['name' => 'nome', 'label' => 'Nome', 'required' => true],
            ['name' => 'matricula', 'label' => 'Matricula'],
            ['name' => 'cargo', 'label' => 'Cargo'],
            ['name' => 'unidade', 'label' => 'Unidade'],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email'],
            ['name' => 'telefone', 'label' => 'Telefone'],
            ['name' => 'ativo', 'label' => 'Ativo', 'type' => 'checkbox'],
        ];
    }
}

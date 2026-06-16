<?php

namespace GestContratos\Controllers;

use GestContratos\Models\GenericModel;

final class SimpleResourceController extends ResourceController
{
    public function __construct(string $table, string $title, string $route, array $extraFields = [], array $roles = ['gestor-contratos'], bool $indexRequiresPermission = false)
    {
        $baseFields = [
            'nome', 'descricao', 'codigo', 'documento', 'email', 'telefone', 'ativo',
            'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
        ];
        $this->model = new GenericModel($table, array_values(array_unique(array_merge($baseFields, $extraFields))));
        $this->table = $table;
        $this->title = $title;
        $this->route = $route;
        $this->roles = $roles;
        $this->indexRequiresPermission = $indexRequiresPermission;
        $this->columns = ['nome' => 'Nome', 'codigo' => 'Codigo', 'descricao' => 'Descricao', 'ativo' => 'Ativo'];
        $this->fields = [
            ['name' => 'nome', 'label' => 'Nome', 'required' => true],
            ['name' => 'codigo', 'label' => 'Codigo'],
            ['name' => 'descricao', 'label' => 'Descricao', 'type' => 'textarea'],
            ['name' => 'documento', 'label' => 'CPF/CNPJ'],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email'],
            ['name' => 'telefone', 'label' => 'Telefone'],
            ['name' => 'ativo', 'label' => 'Ativo', 'type' => 'checkbox'],
        ];
    }
}

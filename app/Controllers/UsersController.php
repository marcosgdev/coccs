<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Request;
use GestContratos\Models\User;

final class UsersController extends ResourceController
{
    public function __construct()
    {
        $this->model = new User();
        $this->table = 'usuarios';
        $this->title = 'Usuarios';
        $this->route = '/usuarios';
        $this->roles = ['administrador'];
        $this->indexRequiresPermission = true;
        $this->columns = ['nome' => 'Nome', 'email' => 'E-mail', 'perfil_id' => 'Perfil ID', 'ativo' => 'Ativo'];
        $this->fields = [
            ['name' => 'nome', 'label' => 'Nome', 'required' => true],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
            ['name' => 'perfil_id', 'label' => 'Perfil ID', 'type' => 'number', 'required' => true],
            ['name' => 'password_hash', 'label' => 'Senha', 'type' => 'password'],
            ['name' => 'ativo', 'label' => 'Ativo', 'type' => 'checkbox'],
        ];
    }

    protected function prepareData(array $data, Request $request): array
    {
        if (! empty($data['password_hash'])) {
            $data['password_hash'] = password_hash((string) $data['password_hash'], PASSWORD_DEFAULT);
        } else {
            unset($data['password_hash']);
        }
        return $data;
    }
}

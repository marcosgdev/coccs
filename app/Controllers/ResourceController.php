<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Model;
use GestContratos\Core\Request;
use GestContratos\Services\AuditService;

abstract class ResourceController extends Controller
{
    protected Model $model;
    protected string $title;
    protected string $route;
    protected string $table;
    protected array $columns = [];
    protected array $fields = [];
    protected array $roles = ['gestor-contratos'];
    protected bool $indexRequiresPermission = false;

    public function index(Request $request): void
    {
        $this->indexRequiresPermission ? $this->requirePermission($this->roles) : $this->requireAuth();
        $items = $this->model->all($this->orderBy(), [], 1000);
        $this->view('shared/resource_index', [
            'title' => $this->title,
            'items' => $items,
            'columns' => $this->columns,
            'route' => $this->route,
        ]);
    }

    public function create(): void
    {
        $this->requireCan(Auth::canWrite());
        $this->view('shared/resource_form', [
            'title' => 'Novo registro - ' . $this->title,
            'item' => [],
            'fields' => $this->fields,
            'action' => url($this->route),
        ]);
    }

    public function store(Request $request): void
    {
        $this->requireCan(Auth::canWrite());
        $this->validateCsrf($request);
        $data = $this->prepareData($this->collect($request), $request);
        $data['created_by'] = Auth::id();
        $id = $this->model->create($data);
        (new AuditService())->log('criacao', $this->table, $id, [], $data);
        flash('success', 'Registro cadastrado.');
        redirect($this->route);
    }

    public function edit(Request $request, string $id): void
    {
        $this->requireCan(Auth::canWrite());
        $item = $this->model->find((int) $id);
        if (! $item) {
            flash('danger', 'Registro nao encontrado.');
            redirect($this->route);
        }
        $this->view('shared/resource_form', [
            'title' => 'Editar registro - ' . $this->title,
            'item' => $item,
            'fields' => $this->fields,
            'action' => url($this->route . '/' . $id),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $this->requireCan(Auth::canWrite());
        $this->validateCsrf($request);
        $before = $this->model->find((int) $id);
        $data = $this->prepareData($this->collect($request), $request);
        $data['updated_by'] = Auth::id();
        $this->model->update((int) $id, $data);
        (new AuditService())->log('edicao', $this->table, $id, $before ?? [], $data);
        flash('success', 'Registro atualizado.');
        redirect($this->route);
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireCan(Auth::canDelete());
        $this->validateCsrf($request);
        $before = $this->model->find((int) $id);
        $this->model->softDelete((int) $id);
        (new AuditService())->log('exclusao_logica', $this->table, $id, $before ?? [], []);
        flash('success', 'Registro excluido logicamente.');
        redirect($this->route);
    }

    protected function collect(Request $request): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            $name = $field['name'];
            $data[$name] = ($field['type'] ?? 'text') === 'checkbox' ? (int) ! empty($request->body[$name]) : ($request->body[$name] ?? null);
        }
        return $data;
    }

    protected function prepareData(array $data, Request $request): array
    {
        return $data;
    }

    protected function orderBy(): string
    {
        return 'id DESC';
    }
}

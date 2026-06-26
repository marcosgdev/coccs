<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Core\Request;
use GestContratos\Models\Contract;

final class ArpsController extends Controller
{
    private Contract $contracts;

    public function __construct()
    {
        $this->contracts = new Contract();
    }

    public function index(Request $request): void
    {
        $this->requireAuth();
        $situacaoDefault = array_key_exists('situacao', $request->query) ? [] : ['situacao' => 'Vigente'];
        $filters = array_merge($situacaoDefault, $request->query, ['tipo' => 'ARP']);
        $this->view('arps/index', [
            'title'   => 'Atas de Registro de Precos',
            'arps'    => $this->contracts->search($filters),
            'filters' => $request->query,
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $this->requireAuth();
        $arp = $this->contracts->find((int) $id);
        if (! $arp || $arp['tipo'] !== 'ARP') {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'ARP nao encontrada']);
            return;
        }
        redirect('/contratos/' . $id);
    }
}

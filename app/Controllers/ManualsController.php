<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;

final class ManualsController extends Controller
{
    public function usage(): void
    {
        $this->requireAuth();
        $this->view('manuals/usage', ['title' => 'Manual de Uso']);
    }

    public function maintenance(): void
    {
        $this->requireAuth();
        $this->view('manuals/maintenance', ['title' => 'Manual de Manutencao']);
    }

    public function deployment(): void
    {
        $this->requirePermission(['administrador']);
        $this->view('manuals/deployment', ['title' => 'Manual de Implantacao']);
    }
}

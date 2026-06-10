<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;

final class AuxiliaryController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('auxiliary/index', [
            'title' => 'Cadastros Auxiliares',
        ]);
    }
}

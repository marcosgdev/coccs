<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Controller;
use GestContratos\Models\AuditLog;

final class AuditController extends Controller
{
    public function index(): void
    {
        $this->requireCan(\GestContratos\Core\Auth::canViewAudit());
        $this->view('audit/index', [
            'title' => 'Auditoria',
            'logs' => (new AuditLog())->all('id DESC', [], 1000),
        ]);
    }
}

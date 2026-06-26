<?php

namespace GestContratos\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function requireAuth(): void
    {
        if (! Auth::check()) {
            flash('warning', 'Entre para acessar o sistema.');
            redirect('/login');
        }
    }

    protected function requirePermission(array $roles): void
    {
        $this->requireAuth();
        if (! Auth::hasAnyRole($roles)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acesso negado']);
            exit;
        }
    }

    protected function requireCan(bool $allowed): void
    {
        $this->requireAuth();
        if (! $allowed) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acesso negado']);
            exit;
        }
    }

    protected function validateCsrf(Request $request): void
    {
        if (! Csrf::verify((string) $request->input('_csrf', ''))) {
            http_response_code(419);
            flash('danger', 'Sessao expirada. Tente novamente.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    protected function backWithInput(string $message, string $type = 'danger'): never
    {
        $_SESSION['_old'] = $_POST;
        flash($type, $message);
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}

<?php

namespace GestContratos\Controllers;

use GestContratos\Core\Auth;
use GestContratos\Core\Controller;
use GestContratos\Core\Request;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Login'], 'layouts/auth');
    }

    public function login(Request $request): void
    {
        $this->validateCsrf($request);
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');

        if (! Auth::attempt($email, $password)) {
            $_SESSION['_old'] = ['email' => $email];
            flash('danger', 'Credenciais invalidas ou usuario inativo.');
            redirect('/login');
        }

        flash('success', 'Bem-vindo ao GestContratos.');
        redirect('/');
    }

    public function logout(Request $request): void
    {
        $this->validateCsrf($request);
        Auth::logout();
        redirect('/login');
    }

    public function profile(): void
    {
        $this->requireAuth();
        $this->view('settings/profile', ['title' => 'Meu perfil', 'user' => Auth::user()]);
    }
}

<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        return view('auth/login', ['title' => 'Entrar']);
    }

    public function doLogin()
    {
        $email = trim((string) $this->request->getPost('email'));
        $pass  = (string) $this->request->getPost('password');

        // throttling básico
        $throttler = service('throttler');

        $key = 'login-' . hash('sha256', $this->request->getIPAddress() . '|' . (string)$this->request->getUserAgent());
        if ($throttler->check($key, 10, MINUTE) === false) {
            return redirect()->back()->withInput()->with('errors', ['Muitas tentativas. Aguarde.']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
            return redirect()->back()->withInput()->with('errors', ['Dados inválidos.']);
        }

        $user = (new UserModel())->where('email', $email)->first();
        if (!$user || !password_verify($pass, $user['password_hash']) || (int)($user['is_active'] ?? 0) !== 1) {
            return redirect()->back()->withInput()->with('errors', ['Credenciais inválidas.']);
        }

        session()->regenerate();
        session()->set([
            'uid'    => $user['id'],
            'uname'  => $user['name'],
            'uemail' => $user['email'],
            'role'   => (int) $user['role'], // 0 - Vendedor | 1 - Gerente | 2 - Admin
        ]);

        $dest = session()->get('intend_url') ?: site_url('/');
        session()->remove('intend_url');

        return redirect()->to($dest);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'));
    }
}

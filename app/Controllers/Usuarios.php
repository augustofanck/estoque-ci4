<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Usuarios extends BaseController
{
    protected UserModel $model;

    public function __construct()
    {
        helper(['form', 'url', 'text']);
        $this->model = new UserModel();
    }

    private function ensureAdmin()
    {
        if ((int) session('role') !== 2) {
            return redirect()->to(site_url('/'))
                ->with('errors', ['Acesso negado.']);
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->ensureAdmin()) return $r;

        $users = $this->model->orderBy('id', 'DESC')->findAll();
        $roles = [0 => 'Vendedor', 1 => 'Gerente', 2 => 'Admin'];

        return view('usuarios/index', [
            'title' => 'Usuários',
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        if ($r = $this->ensureAdmin()) return $r;

        return view('usuarios/form', [
            'title' => 'Novo Usuário',
            'user'  => [],
        ]);
    }

    public function store()
    {
        if ($r = $this->ensureAdmin()) return $r;

        $post = $this->request->getPost();

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|max_length[150]|is_unique[users.email]',
            'role'     => 'required|in_list[0,1,2]',
            'password' => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'name'          => trim($post['name']),
            'email'         => strtolower(trim($post['email'])),
            'role'          => (int) $post['role'],
            'is_active'     => isset($post['is_active']) ? 1 : 0,
            'password_hash' => password_hash($post['password'], PASSWORD_DEFAULT),
        ];

        if (!$this->model->save($payload)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        return redirect()->to(site_url('usuarios'))->with('msg', 'Usuário criado com sucesso.');
    }

    public function edit($id)
    {
        if ($r = $this->ensureAdmin()) return $r;

        $user = $this->model->find($id);
        if (!$user) {
            return redirect()->to(site_url('usuarios'))->with('errors', ['Usuário não encontrado.']);
        }

        return view('usuarios/form', [
            'title' => 'Editar Usuário',
            'user'  => $user,
        ]);
    }

    public function update($id)
    {
        if ($r = $this->ensureAdmin()) return $r;

        $post = $this->request->getPost();

        $rules = [
            'name'  => 'required|min_length[2]|max_length[150]',
            'email' => "required|valid_email|max_length[150]|is_unique[users.email,id,{$id}]",
            'role'  => 'required|in_list[0,1,2]',
        ];

        // senha opcional no update
        if (!empty($post['password'])) {
            $rules['password'] = 'min_length[6]';
            $rules['password_confirm'] = 'matches[password]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $payload = [
            'id'        => (int) $id,
            'name'      => trim($post['name']),
            'email'     => strtolower(trim($post['email'])),
            'role'      => (int) $post['role'],
            'is_active' => isset($post['is_active']) ? 1 : 0,
        ];

        if (!empty($post['password'])) {
            $payload['password_hash'] = password_hash($post['password'], PASSWORD_DEFAULT);
        }

        if (!$this->model->save($payload)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }

        return redirect()->to(site_url('usuarios'))->with('msg', 'Usuário atualizado com sucesso.');
    }

    public function delete($id)
    {
        if ($r = $this->ensureAdmin()) return $r;

        $id = (int) $id;
        if ($id === (int) session('uid')) {
            return redirect()->to(site_url('usuarios'))->with('errors', ['Você não pode excluir seu próprio usuário.']);
        }

        $this->model->delete($id);
        return redirect()->to(site_url('usuarios'))->with('msg', 'Usuário excluído.');
    }
}

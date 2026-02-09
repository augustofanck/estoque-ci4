<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;

class Usuarios extends BaseController
{
    protected UsuarioModel $model;

    public function __construct()
    {
        helper(['form', 'url', 'text']);
        $this->model = new UsuarioModel();
    }

    private function ensureAdmin()
    {
        if ((int) session('role') !== 2) {
            return redirect()->to(site_url('/'))->with('errors', ['Acesso negado.']);
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
            'user' => [],
        ]);
    }

    public function store()
    {
        if ($r = $this->ensureAdmin()) return $r;

        $post = $this->request->getPost();
        $post['is_active'] = isset($post['is_active']) ? 1 : 0;

        $rules = $this->model->getRules('create');
        $validation = \Config\Services::validation();
        $validation->setRules($rules);

        if (!$validation->run($post)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'name'         => $post['name'],
            'email'        => $post['email'],
            'role'         => (int) $post['role'],
            'is_active'    => (int) $post['is_active'],
            'password_hash' => password_hash((string)$post['password'], PASSWORD_DEFAULT),
        ];

        $this->model->skipValidation(true)->insert($data);

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
            'user' => $user,
        ]);
    }

    public function update($id)
    {
        if ($r = $this->ensureAdmin()) return $r;

        $post = $this->request->getPost();
        $post['id']        = (int) $id;
        $post['is_active'] = isset($post['is_active']) ? 1 : 0;

        $rules = $this->model->getRules('update');
        $validation = \Config\Services::validation();
        $validation->setRules($rules);

        if (!$validation->run($post)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $data = [
            'id'        => $post['id'],
            'name'      => $post['name'],
            'email'     => $post['email'],
            'role'      => (int) $post['role'],
            'is_active' => (int) $post['is_active'],
        ];

        $plain = trim((string)($post['password'] ?? ''));
        if ($plain !== '') {
            $data['password_hash'] = password_hash($plain, PASSWORD_DEFAULT);
        }

        $this->model->skipValidation(true)->save($data);

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

<?php

namespace App\Controllers;

use App\Models\EstoqueTipoModel;

class EstoqueTipos extends BaseController
{
    /**
     * Tipos: somente GERENTE(1) ou ADMIN(2)
     */
    private function guard()
    {
        if (!has_min_role(1)) {
            return redirect()->to('/')->with('error', 'Acesso não permitido!');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;

        $tipoModel = new EstoqueTipoModel();

        $data = [
            'tipos' => $tipoModel->orderBy('nome', 'asc')->findAll(),
        ];

        return view('estoque_tipos/index', $data);
    }

    public function create()
    {
        if ($r = $this->guard()) return $r;

        return view('estoque_tipos/form', [
            'tipo' => null,
        ]);
    }

    public function store()
    {
        if ($r = $this->guard()) return $r;

        $tipoModel = new EstoqueTipoModel();
        $dados = $this->request->getPost();

        // normaliza checkbox
        if (!isset($dados['ativo'])) $dados['ativo'] = 0;

        if (!$tipoModel->insert($dados)) {
            return redirect()->back()->withInput()->with('errors', $tipoModel->errors());
        }

        return redirect()->to('/estoque-tipos')->with('success', 'Tipo criado com sucesso!');
    }

    public function edit($id)
    {
        if ($r = $this->guard()) return $r;

        $tipoModel = new EstoqueTipoModel();
        $tipo = $tipoModel->find($id);

        if (!$tipo) {
            return redirect()->to('/estoque-tipos')->with('error', 'Tipo não encontrado.');
        }

        return view('estoque_tipos/form', [
            'tipo' => $tipo,
        ]);
    }

    public function update($id)
    {
        if ($r = $this->guard()) return $r;

        $tipoModel = new EstoqueTipoModel();
        $dados = $this->request->getPost();

        if (!isset($dados['ativo'])) $dados['ativo'] = 0;

        if (!$tipoModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('errors', $tipoModel->errors());
        }

        return redirect()->to('/estoque-tipos')->with('success', 'Tipo atualizado!');
    }

    public function delete($id)
    {
        if ($r = $this->guard()) return $r;

        // Opcional: só admin pode excluir tipos
        // if (!is_admin()) return redirect()->to('/estoque-tipos')->with('error', 'Acesso não permitido!');

        $tipoModel = new EstoqueTipoModel();

        try {
            $tipoModel->delete($id);
            return redirect()->to('/estoque-tipos')->with('success', 'Tipo removido!');
        } catch (\Throwable $e) {
            // se tiver FK RESTRICT (itens usando o tipo), cai aqui
            return redirect()->to('/estoque-tipos')->with(
                'error',
                'Não foi possível remover: existem itens vinculados a este tipo.'
            );
        }
    }
}

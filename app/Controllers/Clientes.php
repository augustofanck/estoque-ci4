<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ClienteModel;

class Clientes extends BaseController
{
    protected ClienteModel $model;

    public function __construct()
    {
        helper(['form', 'url']);
        $this->model = new ClienteModel();
    }

    private function toDbDate(?string $v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $v)) {
            $d = \DateTime::createFromFormat('d/m/Y', $v);
            return $d && $d->format('d/m/Y') === $v ? $d->format('Y-m-d') : null;
        }
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $v)) return $v;
        return null;
    }

    private function fromDbDate(?string $v): string
    {
        $v = trim((string)$v);
        if ($v === '' || $v === null) return '';
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $v)) {
            $d = \DateTime::createFromFormat('Y-m-d', $v);
            return $d ? $d->format('d/m/Y') : '';
        }
        return $v;
    }

    public function index()
    {
        $q     = trim((string)$this->request->getGet('q'));
        $field = $this->request->getGet('field') ?: 'nome';
        $allowed = ['nome', 'documento', 'email', 'telefone', 'cidade'];
        if (!in_array($field, $allowed, true)) $field = 'nome';

        $builder = $this->model->orderBy('id', 'DESC');
        if ($q !== '') $builder->like($field, $q);
        $clientes = $builder->findAll();

        if ($this->request->isAJAX()) {
            return view('clientes/_rows', ['clientes' => $clientes]);
        }

        return view('clientes/index', [
            'title'   => 'Clientes',
            'clientes' => $clientes,
            'q'       => $q,
            'field'   => $field,
        ]);
    }

    public function create()
    {
        return view('clientes/form', ['title' => 'Novo Cliente', 'cliente' => []]);
    }

    public function store()
    {
        $data = $this->request->getPost();
        $data['termino_contrato'] = $this->toDbDate($data['termino_contrato'] ?? null);

        if (!$this->model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }
        return redirect()->to(site_url('clientes'))->with('msg', 'Cliente criado.');
    }

    public function edit($id)
    {
        $row = $this->model->find($id);
        if (!$row) return redirect()->to(site_url('clientes'))->with('errors', ['Registro não encontrado.']);
        $row['termino_contrato'] = $this->fromDbDate($row['termino_contrato'] ?? '');
        return view('clientes/form', ['title' => 'Editar Cliente', 'cliente' => $row]);
    }

    public function update($id)
    {
        $data = $this->request->getPost();
        $data['id'] = $id;
        $data['termino_contrato'] = $this->toDbDate($data['termino_contrato'] ?? null);

        if (!$this->model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }
        return redirect()->to(site_url('clientes'))->with('msg', 'Cliente atualizado.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to(site_url('clientes'))->with('msg', 'Cliente removido.');
    }
}

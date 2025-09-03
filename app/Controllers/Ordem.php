<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrdemModel;

class Ordem extends BaseController
{
    protected $model;

    private array $dateFields = [
        'data_compra',
        'dia_pagamento_laboratorio',
        'data_recebimento_laboratorio',
        'data_entrega_oculos',
        'dia_nota',
    ];

    private function toDbDate(?string $v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;

        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $v)) {
            $d = \DateTime::createFromFormat('d/m/Y', $v);
            return $d && $d->format('d/m/Y') === $v ? $d->format('Y-m-d') : null;
        }

        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $v)) {
            $d = \DateTime::createFromFormat('Y-m-d', $v);
            return $d ? $d->format('Y-m-d') : null;
        }

        return null;
    }

    private function fromDbDate(?string $v): string
    {
        $v = trim((string)$v);
        if ($v === '') return '';
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $v)) {
            $d = \DateTime::createFromFormat('Y-m-d', $v);
            return $d ? $d->format('d/m/Y') : '';
        }
        return $v;
    }

    private function normalizeDatesForSave(array $payload): array
    {
        foreach ($this->dateFields as $f) {
            if (array_key_exists($f, $payload)) {
                $payload[$f] = $this->toDbDate($payload[$f]);
            }
        }
        return $payload;
    }

    private function formatDatesForView(array $row): array
    {
        foreach ($this->dateFields as $f) {
            if (isset($row[$f]) && $row[$f] !== null && $row[$f] !== '') {
                $row[$f] = $this->fromDbDate($row[$f]);
            }
        }
        return $row;
    }

    public function __construct()
    {
        helper(['form', 'url', 'text']);
        $this->model = new OrdemModel();
    }

    public function index()
    {
        $q         = trim((string) $this->request->getGet('q'));
        $field     = $this->request->getGet('field') ?: 'nome_cliente';
        $vendedor  = trim((string) $this->request->getGet('vendedor')); // << filtro de vendedor

        // whitelist
        $allowed = ['nome_cliente', 'ordem_servico', 'vendedor']; // << permite buscar por vendedor via q+field
        if (!in_array($field, $allowed, true)) $field = 'nome_cliente';

        $builder = $this->model->orderBy('id', 'DESC');

        if ($q !== '') {
            $builder->like($field, $q);
        }

        // aplica filtro específico por vendedor (like para parcial)
        if ($vendedor !== '') {
            $builder->like('vendedor', $vendedor);
        }

        $ordens = $builder->findAll();

        if ($this->request->isAJAX()) {
            return view('ordens/_rows', ['ordens' => $ordens]);
        }

        return view('ordens/index', [
            'title'    => 'Ordens / Estoque',
            'ordens'   => $ordens,
            'q'        => $q,
            'field'    => $field,
            'vendedor' => $vendedor,
        ]);
    }

    public function create()
    {
        $data['title'] = 'Nova Ordem';
        $data['ordem'] = [];
        return view('ordens/form', $data);
    }

    private function normalizeMoney(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '0.00';

        $raw = preg_replace('/[^0-9.,]/', '', $raw);
        $lastComma = strrpos($raw, ',');
        $lastDot   = strrpos($raw, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }

        return is_numeric($raw) ? number_format((float)$raw, 2, '.', '') : '0.00';
    }

    private function normalizeMoneyArray(array $payload): array
    {
        $moneyFields = [
            'valor_venda',
            'valor_entrada',
            'valor_pago',
            'valor_armacao_1',
            'valor_armacao_2',
            'valor_lente_1',
            'valor_lente_2',
            'pagamento_laboratorio'
        ];
        foreach ($moneyFields as $f) {
            if (array_key_exists($f, $payload)) {
                $payload[$f] = $this->normalizeMoney((string)$payload[$f]);
            }
        }
        $payload['nota_gerada'] = isset($payload['nota_gerada']) ? 1 : 0;
        return $payload;
    }

    public function store()
    {
        $payload = $this->request->getPost();
        $payload = $this->normalizeMoneyArray($payload);
        $payload = $this->normalizeDatesForSave($payload);

        if (!$this->model->save($payload)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }
        return redirect()->to(site_url('ordens'))->with('msg', 'Registro criado com sucesso!');
    }

    public function edit($id)
    {
        $ordem = $this->model->find($id);
        if (!$ordem) {
            return redirect()->to(site_url('ordens'))->with('errors', ['Registro não encontrado.']);
        }
        $data['title'] = 'Editar Ordem';
        $data['ordem'] = $this->formatDatesForView($ordem);
        return view('ordens/form', $data);
    }

    public function update($id)
    {
        $payload = $this->request->getPost();
        $payload['id'] = $id;
        $payload = $this->normalizeMoneyArray($payload);
        $payload = $this->normalizeDatesForSave($payload);

        if (!$this->model->save($payload)) {
            return redirect()->back()->withInput()->with('errors', $this->model->errors());
        }
        return redirect()->to(site_url('ordens'))->with('msg', 'Registro atualizado com sucesso!');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to(site_url('ordens'))->with('msg', 'Registro excluído.');
    }
}

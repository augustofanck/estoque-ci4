<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrdemModel;
use App\Models\ClienteModel;
use App\Models\FormaPagamentoModel;
use App\Models\OrdemPagamentoModel;

class Ordem extends BaseController
{
    private OrdemPagamentoModel $pagamentoModel;
    private FormaPagamentoModel $formaPagamentoModel;

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
        $this->pagamentoModel = new OrdemPagamentoModel();
        $this->formaPagamentoModel = new FormaPagamentoModel();
    }

    public function index()
    {
        $q        = trim((string) $this->request->getGet('q'));
        $field    = $this->request->getGet('field') ?: 'nome_cliente';
        $vendedor = trim((string) $this->request->getGet('vendedor'));

        $applyDate    = (string) $this->request->getGet('apply_date') === '1';
        $dataIniRaw   = $this->request->getGet('data_ini');
        $dataFimRaw   = $this->request->getGet('data_fim');
        $dataIni      = $this->toDbDate($dataIniRaw);
        $dataFim      = $this->toDbDate($dataFimRaw);

        $map = [
            'nome_cliente'  => 'c.nome',
            'ordem_servico' => 'ordens.ordem_servico',
            'vendedor'      => 'ordens.vendedor',
        ];
        $col = $map[$field] ?? 'c.nome';

        $pagAgg = '(SELECT ordem_id,
                SUM(CASE WHEN status = "confirmado" AND deleted_at IS NULL THEN valor ELSE 0 END) AS total_pago,
                SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) AS qtd_pagamentos
           FROM ordens_pagamento
           GROUP BY ordem_id) op';

        $builder = $this->model
            ->select('ordens.*, c.nome AS cliente,
              COALESCE(op.total_pago, 0) AS total_pago,
              (ordens.valor_venda - COALESCE(op.total_pago, 0)) AS saldo,
              COALESCE(op.qtd_pagamentos, 0) AS qtd_pagamentos')
            ->join('clientes c', 'c.id = ordens.cliente_id', 'left')
            ->join($pagAgg, 'op.ordem_id = ordens.id', 'left', false)
            ->orderBy('ordens.id', 'DESC');


        if ($q !== '') {
            $builder->like($col, $q);
        }

        if ($vendedor !== '') {
            $builder->where('ordens.vendedor', $vendedor);
        }

        if ($applyDate) {
            if ($dataIni && $dataFim) {
                $builder->where('ordens.data_compra >=', $dataIni . ' 00:00:00')
                    ->where('ordens.data_compra <=', $dataFim . ' 23:59:59');
            } elseif ($dataIni) {
                $builder->where('ordens.data_compra >=', $dataIni . ' 00:00:00');
            } elseif ($dataFim) {
                $builder->where('ordens.data_compra <=', $dataFim . ' 23:59:59');
            }
        }

        $ordens = $builder->findAll();

        if ($this->request->isAJAX()) {
            return view('ordens/_rows', ['ordens' => $ordens]);
        }

        return view('ordens/index', [
            'title'       => 'Ordens / Estoque',
            'ordens'      => $ordens,
            'q'           => $q,
            'field'       => $field,
            'vendedor'    => $vendedor,
            'data_ini'    => $dataIni ?: '',
            'data_fim'    => $dataFim ?: '',
            'apply_date'  => $applyDate ? '1' : '0',
        ]);
    }

    public function create()
    {
        $clientes = (new ClienteModel())
            ->select('id, nome')->orderBy('nome', 'ASC')->findAll();

        $formasPagamento = $this->formaPagamentoModel
            ->select('id, nome')
            ->orderBy('nome', 'ASC')
            ->findAll();

        return view('ordens/form', [
            'title'          => 'Nova Ordem',
            'ordem'          => [],
            'clientes'       => $clientes,
            'formasPagamento' => $formasPagamento,
            'pagamentos'     => [],
            'financeiro'     => [
                'total_pago'     => 0,
                'saldo'          => 0,
                'qtd_pagamentos' => 0,
            ],
        ]);
    }


    private function normalizeMoney(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        $raw = preg_replace('/[^0-9.,]/', '', $raw);
        $lastComma = strrpos($raw, ',');
        $lastDot   = strrpos($raw, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }

        return is_numeric($raw) ? number_format((float)$raw, 2, '.', '') : null;
    }

    private function normalizeMoneyArray(array $payload): array
    {
        $moneyFields = [
            'valor_venda',
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

        $temSegundo = ($payload['tem_segundo_par'] ?? '0') === '1';
        if (!$temSegundo) {
            $payload['valor_armacao_2'] = null;
            $payload['valor_lente_2']   = null;
            $payload['tipo_lente_2']    = null;
        }

        $payload = $this->normalizeMoneyArray($payload);
        $payload = $this->normalizeDatesForSave($payload);

        $isAjax = $this->request->isAJAX();

        if (!$this->model->save($payload)) {
            $errors = $this->model->errors();

            if ($isAjax) {
                return $this->response->setStatusCode(422)->setJSON([
                    'ok'     => false,
                    'errors' => $errors,
                    'csrf'   => csrf_hash(),
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $errors);
        }

        if ($isAjax) {
            return $this->response->setJSON([
                'ok'   => true,
                'id'   => $this->model->getInsertID(),
                'msg'  => 'Registro criado com sucesso!',
                'csrf' => csrf_hash(),
            ]);
        }

        return redirect()
            ->to(site_url('ordens'))
            ->with('msg', 'Registro criado com sucesso!');
    }

    public function edit($id)
    {
        $ordem = $this->model->find($id);
        if (!$ordem) {
            return redirect()->to(site_url('ordens'))->with('errors', ['Registro não encontrado.']);
        }

        $clientes = (new ClienteModel())->select('id, nome')->orderBy('nome', 'ASC')->findAll();

        $formasPagamento = $this->formaPagamentoModel
            ->select('id, nome')
            ->orderBy('nome', 'ASC')
            ->findAll();

        $pagamentos = $this->pagamentoModel
            ->select('ordens_pagamento.*, fp.nome AS forma_nome')
            ->join('forma_pagamento fp', 'fp.id = ordens_pagamento.forma_pagamento_id', 'left')
            ->where('ordem_id', $id)
            ->orderBy('data_pagamento', 'DESC')
            ->findAll();

        $totalPago = 0.0;
        foreach ($pagamentos as $p) {
            if (($p['status'] ?? '') === 'confirmado') {
                $totalPago += (float)($p['valor'] ?? 0);
            }
        }

        $valorVenda = (float)($ordem['valor_venda'] ?? 0);
        $saldo = $valorVenda - $totalPago;

        return view('ordens/form', [
            'title'           => 'Editar Ordem',
            'ordem'           => $ordem,
            'clientes'        => $clientes,
            'formasPagamento' => $formasPagamento,
            'pagamentos'      => $pagamentos,
            'financeiro'      => [
                'total_pago'     => $totalPago,
                'saldo'          => $saldo,
                'qtd_pagamentos' => count($pagamentos),
            ],
        ]);
    }


    public function update($id)
    {
        $payload = $this->request->getPost();
        $payload['id'] = $id;

        $temSegundo = ($payload['tem_segundo_par'] ?? '0') === '1';
        if (!$temSegundo) {
            $payload['valor_armacao_2'] = null;
            $payload['valor_lente_2']   = null;
            $payload['tipo_lente_2']    = null;
        }

        $payload = $this->normalizeMoneyArray($payload);
        $payload = $this->normalizeDatesForSave($payload);

        $isAjax = $this->request->isAJAX();

        if (!$this->model->save($payload)) {
            $errors = $this->model->errors();

            if ($isAjax) {
                return $this->response->setStatusCode(422)->setJSON([
                    'ok'     => false,
                    'errors' => $errors,
                    'csrf'   => csrf_hash(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $errors);
        }

        if ($isAjax) {
            return $this->response->setJSON([
                'ok'   => true,
                'id'   => $id,
                'msg'  => 'Registro atualizado com sucesso!',
                'csrf' => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('ordens'))->with('msg', 'Registro atualizado com sucesso!');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to(site_url('ordens'))->with('msg', 'Registro excluído.');
    }

    public function addPagamento($ordemId)
    {
        $ordemId = (int) $ordemId;
        $ordem = $this->model->find($ordemId);

        if (!$ordem) {
            return redirect()->to(site_url('ordens'))->with('errors', ['Ordem não encontrada.']);
        }

        $valor = $this->normalizeMoney((string) $this->request->getPost('valor'));
        $formaId = $this->request->getPost('forma_pagamento_id');
        $dataRaw = (string) $this->request->getPost('data_pagamento');

        if ($valor === null || (float)$valor <= 0) {
            return redirect()->back()->withInput()->with('errors', ['Informe um valor de pagamento válido.']);
        }

        $formaId = ($formaId === '' || $formaId === null) ? null : (int)$formaId;

        $dataDb = $this->toDbDate($dataRaw);
        $dataPagamento = $dataDb ? ($dataDb . ' 00:00:00') : date('Y-m-d H:i:s');

        if (!$this->pagamentoModel->insert([
            'ordem_id'           => $ordemId,
            'forma_pagamento_id' => $formaId,
            'valor'              => $valor,
            'data_pagamento'     => $dataPagamento,
            'status'             => 'confirmado',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->pagamentoModel->errors());
        }

        return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
            ->with('success', 'Pagamento registrado com sucesso!');
    }

    public function deletePagamento($ordemId, $pagamentoId)
    {
        $ordemId = (int)$ordemId;
        $pagamentoId = (int)$pagamentoId;

        $pag = $this->pagamentoModel->find($pagamentoId);

        if (!$pag || (int)($pag['ordem_id'] ?? 0) !== $ordemId) {
            return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                ->with('errors', ['Pagamento não encontrado.']);
        }

        $this->pagamentoModel->delete($pagamentoId);

        return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
            ->with('success', 'Pagamento removido.');
    }
}

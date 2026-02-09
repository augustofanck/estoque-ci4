<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ClienteModel;
use App\Models\EstoqueItemModel;
use App\Models\FormaPagamentoModel;
use App\Models\OrdemItemModel;
use App\Models\OrdemModel;
use App\Models\OrdemPagamentoModel;

class Ordem extends BaseController
{
    protected $model;

    private OrdemPagamentoModel $pagamentoModel;
    private FormaPagamentoModel $formaPagamentoModel;
    private OrdemItemModel $ordemItemModel;
    private EstoqueItemModel $estoqueItemModel;

    private array $dateFields = [
        'data_compra',
        'dia_pagamento_laboratorio',
        'data_recebimento_laboratorio',
        'data_entrega_oculos',
        'dia_nota',
    ];

    public function __construct()
    {
        $this->model               = new OrdemModel();
        $this->pagamentoModel      = new OrdemPagamentoModel();
        $this->formaPagamentoModel = new FormaPagamentoModel();
        $this->ordemItemModel      = new OrdemItemModel();
        $this->estoqueItemModel    = new EstoqueItemModel();
    }

    private function moneyToFloat($v): float
    {
        $v = trim((string) $v);
        if ($v === '') return 0.0;
        // aceita "1.234,56" e "1234.56"
        $v = str_replace(['R$', ' '], '', $v);
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
        return (float) $v;
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
            'consulta',
            'pagamento_laboratorio',
            'desconto_percentual',
        ];

        foreach ($moneyFields as $f) {
            if (array_key_exists($f, $payload)) {
                $payload[$f] = $this->moneyToFloat($payload[$f]);
            }
        }

        return $payload;
    }

    private function normalizeDatesForSave(array $payload): array
    {
        foreach ($this->dateFields as $f) {
            if (!array_key_exists($f, $payload)) continue;
            $v = trim((string) $payload[$f]);
            if ($v === '') {
                $payload[$f] = null;
                continue;
            }
            if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $v)) {
                [$d, $m, $y] = explode('/', $v);
                $payload[$f] = sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
            }
        }

        return $payload;
    }

    private function syncSegundoParRefs(int $ordemId): void
    {
        // Regra: pega as 2 primeiras armações e 2 primeiras lentes da ordem (por ordem de inclusão)
        $rows = $this->ordemItemModel
            ->select('ordens_itens.produto_id, ei.categoria')
            ->join('estoque_itens ei', 'ei.id = ordens_itens.produto_id', 'left')
            ->where('ordens_itens.ordem_id', $ordemId)
            ->where('ordens_itens.tipo', 'produto')
            ->where('ordens_itens.produto_id IS NOT NULL', null, false)
            ->orderBy('ordens_itens.id', 'ASC')
            ->findAll();

        $armacoes = [];
        $lentes   = [];

        foreach ($rows as $r) {
            $pid = (int) ($r['produto_id'] ?? 0);
            $cat = (string) ($r['categoria'] ?? '');
            if ($pid <= 0) continue;

            if ($cat === 'armacao') $armacoes[] = $pid;
            if ($cat === 'lente')   $lentes[]   = $pid;
        }

        $this->model->update($ordemId, [
            'armacao_1_item_id' => $armacoes[0] ?? null,
            'armacao_2_item_id' => $armacoes[1] ?? null,
            'lente_1_item_id'   => $lentes[0]   ?? null,
            'lente_2_item_id'   => $lentes[1]   ?? null,
        ]);
    }

    private function recalcularTotaisOrdem(int $ordemId): array
    {
        $ordem = $this->model->find($ordemId);
        if (!$ordem) {
            return ['subtotal' => 0.0, 'desconto_valor' => 0.0, 'total' => 0.0];
        }

        $itens = $this->ordemItemModel
            ->select('id, quantidade, preco_unitario')
            ->where('ordem_id', $ordemId)
            ->orderBy('id', 'ASC')
            ->findAll();

        $subtotal = 0.0;

        foreach ($itens as $it) {
            $qtd   = (int) ($it['quantidade'] ?? 1);
            $preco = (float) ($it['preco_unitario'] ?? 0);
            $totalItem = round($qtd * $preco, 2);

            // mantém coluna total coerente
            $this->ordemItemModel->update((int)$it['id'], ['total' => $totalItem, 'desconto_valor' => 0.0]);

            $subtotal += $totalItem;
        }

        $descPercent = (float) ($ordem['desconto_percentual'] ?? 0);
        $descPercent = max(0.0, min(100.0, $descPercent));

        $descontoValor = round($subtotal * ($descPercent / 100), 2);
        $totalFinal    = max(0.0, round($subtotal - $descontoValor, 2));

        $this->model->update($ordemId, [
            'valor_venda' => $totalFinal,
        ]);

        return ['subtotal' => $subtotal, 'desconto_valor' => $descontoValor, 'total' => $totalFinal];
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

    public function store()
    {
        $payload = $this->request->getPost();

        // Agora o total vem dos itens do estoque -> na criação a ordem começa zerada.
        $payload['valor_venda']       = 0;
        $payload['valor_armacao_1']   = 0;
        $payload['valor_armacao_2']   = null;
        $payload['valor_lente_1']     = 0;
        $payload['valor_lente_2']     = null;
        $payload['tipo_lente_1']      = $payload['tipo_lente_1'] ?? null; // legado
        $payload['tipo_lente_2']      = null; // legado
        $payload['promocao_segundo_par'] = (($payload['promocao_segundo_par'] ?? '0') === '1') ? 1 : 0;

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

        $newId = (int) $this->model->getInsertID();
        $redirectUrl = site_url('ordens/' . $newId . '/edit');

        if ($isAjax) {
            return $this->response->setJSON([
                'ok'       => true,
                'id'       => $newId,
                'redirect' => $redirectUrl,
                'msg'      => 'Registro criado com sucesso!',
                'csrf'     => csrf_hash(),
            ]);
        }

        return redirect()->to($redirectUrl)->with('msg', 'Registro criado com sucesso!');
    }

    public function create()
    {
        // Clientes para o select
        $clientes = (new \App\Models\ClienteModel())
            ->select('id, nome')
            ->orderBy('nome', 'ASC')
            ->findAll();

        // Defaults da ordem (espelho)
        // Observação: estou usando old() porque seu store/update fazem redirect()->back()->withInput()
        // (mesmo que sua view nem sempre use old() diretamente, isso já te deixa pronto).
        $ordem = [
            'id' => null,
            'cliente_id' => old('cliente_id') ?: null,
            'status' => old('status') ?: 'aberta',
            'data_compra' => old('data_compra') ?: '', // se quiser pré-preencher hoje: date('d/m/Y')
            'ordem_servico' => old('ordem_servico') ?: '',
            'vendedor' => old('vendedor') ?: '',
            'desconto_percentual' => old('desconto_percentual') ?: '0.00',
            'promocao_segundo_par' => old('promocao_segundo_par') ? 1 : 0,
            'obs' => old('obs') ?: '',

            // Mantém compatibilidade com campo legado do schema (sem usar na tela)
            'valor_venda' => 0.00,

            // Campos de espelho de itens (podem existir/ser usados depois)
            'armacao_1_item_id' => null,
            'lente_1_item_id'   => null,
            'armacao_2_item_id' => null,
            'lente_2_item_id'   => null,
        ];

        // Ainda não tem itens no create
        $itens = [];

        // Totais para a tela (no create é tudo zero)
        $totais = [
            'subtotal'       => 0.00,
            'desconto_valor' => 0.00,
            'total'          => 0.00,
        ];

        return view('ordens/form', [
            'title'    => 'Nova Ordem',
            'ordem'    => $ordem,
            'clientes' => $clientes,
            'itens'    => $itens,
            'totais'   => $totais,
        ]);
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

        $itens = $this->ordemItemModel
            ->select('ordens_itens.*, ei.codigo, ei.titulo, ei.categoria')
            ->join('estoque_itens ei', 'ei.id = ordens_itens.produto_id', 'left')
            ->where('ordem_id', (int)$id)
            ->orderBy('ordens_itens.id', 'ASC')
            ->findAll();

        $totais = $this->recalcularTotaisOrdem((int)$id);
        $this->syncSegundoParRefs((int)$id);

        $totalPago = 0.0;
        foreach ($pagamentos as $p) {
            if (($p['status'] ?? '') === 'confirmado') {
                $totalPago += (float)($p['valor'] ?? 0);
            }
        }

        $saldo = (float)($ordem['valor_venda'] ?? 0) - $totalPago;

        return view('ordens/form', [
            'title'           => 'Editar Ordem',
            'ordem'           => $this->model->find($id),
            'clientes'        => $clientes,
            'formasPagamento' => $formasPagamento,
            'pagamentos'      => $pagamentos,
            'itens'           => $itens,
            'totais'          => $totais,
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

        // total vem dos itens (não aceitar edição manual)
        unset($payload['valor_venda']);
        unset($payload['valor_armacao_1'], $payload['valor_armacao_2'], $payload['valor_lente_1'], $payload['valor_lente_2']);

        $payload['promocao_segundo_par'] = (($payload['promocao_segundo_par'] ?? '0') === '1') ? 1 : 0;

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

        // se mudou desconto%, recalcula o total final
        $this->recalcularTotaisOrdem((int)$id);

        if ($isAjax) {
            return $this->response->setJSON([
                'ok'   => true,
                'id'   => $id,
                'msg'  => 'Registro atualizado com sucesso!',
                'csrf' => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('ordens/' . $id . '/edit'))->with('msg', 'Registro atualizado com sucesso!');
    }

    // -----------------
    // ITENS DA ORDEM
    // -----------------

    public function itensStore($ordemId)
    {
        $ordemId = (int) $ordemId;
        $payload = $this->request->getPost();

        $tipo = $payload['tipo'] ?? 'produto';
        $qtd  = max(1, (int)($payload['quantidade'] ?? 1));

        $produtoId  = null;
        $descricao  = null;
        $precoUnit  = 0.0;

        if ($tipo === 'produto') {
            $produtoId = (int)($payload['produto_id'] ?? 0);
            $produto = $this->estoqueItemModel
                ->select('id, codigo, titulo, preco_venda')
                ->where('id', $produtoId)
                ->where('ativo', 1)
                ->first();

            if (!$produto) {
                return $this->response->setStatusCode(404)->setJSON([
                    'ok'    => false,
                    'msg'   => 'Item de estoque não encontrado.',
                    'csrf'  => csrf_hash(),
                ]);
            }

            $precoUnit = (float)($produto['preco_venda'] ?? 0);
            $descricao = trim(($produto['codigo'] ?? '') . ' — ' . ($produto['titulo'] ?? ''));
        } else {
            $descricao = trim((string)($payload['descricao'] ?? 'Serviço'));
            $precoUnit = $this->moneyToFloat($payload['preco_unitario'] ?? '0');
        }

        $total = round($qtd * $precoUnit, 2);

        $this->ordemItemModel->insert([
            'ordem_id'      => $ordemId,
            'tipo'          => $tipo,
            'produto_id'    => $produtoId,
            'descricao'     => $descricao,
            'quantidade'    => $qtd,
            'preco_unitario' => $precoUnit,
            'desconto_valor' => 0.0,
            'total'         => $total,
        ]);

        $this->syncSegundoParRefs($ordemId);
        $totais = $this->recalcularTotaisOrdem($ordemId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'     => true,
                'totais' => $totais,
                'csrf'   => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))->with('msg', 'Item adicionado!');
    }

    public function itensUpdate($ordemId, $itemId)
    {
        $ordemId = (int) $ordemId;
        $itemId  = (int) $itemId;

        $qtd = max(1, (int)($this->request->getPost('quantidade') ?? 1));

        $item = $this->ordemItemModel
            ->where('id', $itemId)
            ->where('ordem_id', $ordemId)
            ->first();

        if (!$item) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON([
                    'ok'   => false,
                    'msg'  => 'Item da ordem não encontrado.',
                    'csrf' => csrf_hash(),
                ]);
            }
            return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                ->with('errors', ['Item da ordem não encontrado.']);
        }

        $precoUnit = (float)($item['preco_unitario'] ?? 0);
        $total     = round($qtd * $precoUnit, 2);

        $this->ordemItemModel->update($itemId, [
            'quantidade' => $qtd,
            'total'      => $total,
        ]);

        $this->syncSegundoParRefs($ordemId);
        $totais = $this->recalcularTotaisOrdem($ordemId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'     => true,
                'totais' => $totais,
                'csrf'   => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
            ->with('msg', 'Quantidade atualizada!');
    }


    public function itensDelete($ordemId, $itemId)
    {
        $ordemId = (int) $ordemId;
        $itemId  = (int) $itemId;

        $this->ordemItemModel->delete($itemId);

        $this->syncSegundoParRefs($ordemId);
        $totais = $this->recalcularTotaisOrdem($ordemId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'     => true,
                'totais' => $totais,
                'csrf'   => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))->with('msg', 'Item removido!');
    }
}

<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ClienteModel;
use App\Models\EstoqueItemModel;
use App\Models\FormaPagamentoModel;
use App\Models\OrdemItemModel;
use App\Models\OrdemModel;
use App\Models\OrdemPagamentoModel;
use App\Models\UsuarioModel;

class Ordem extends BaseController
{
    protected $model;

    private OrdemPagamentoModel $pagamentoModel;
    private FormaPagamentoModel $formaPagamentoModel;
    private OrdemItemModel $ordemItemModel;
    private EstoqueItemModel $estoqueItemModel;
    private UsuarioModel $usuarioModel;

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
        $this->usuarioModel        = new UsuarioModel();
    }

    private function isAdmin(): bool
    {
        return (int) session('role') === 2;
    }

    private function isGerente(): bool
    {
        return (int) session('role') === 1;
    }

    private function currentUserId(): int
    {
        return (int) (session('uid') ?? 0);
    }

    private function getUserNameById(int $id): ?string
    {
        if ($id <= 0) return null;
        $u = $this->usuarioModel->select('name')->where('id', $id)->first();
        $name = trim((string) ($u['name'] ?? ''));
        return $name !== '' ? $name : null;
    }

    private function getActiveUsers(): array
    {
        // Ajuste filtros se quiser (role, etc.). Aqui: ativos.
        return $this->usuarioModel
            ->select('id, name, email, role, is_active')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    private function applyVendedorRulesForSave(array $payload, ?array $ordemAtual = null): array
    {
        $isAdmin = $this->isAdmin();
        $uid     = $this->currentUserId();
        $isUpdate = is_array($ordemAtual);

        /**
         * 1) Regras de vendedor_id
         * - UPDATE: só admin pode alterar; não-admin não mexe.
         * - CREATE: não-admin define como usuário logado; admin pode definir/alterar.
         */
        if ($isAdmin) {
            if (array_key_exists('vendedor_id', $payload)) {
                $vid = $payload['vendedor_id'];

                if ($vid === '' || $vid === null) {
                    $payload['vendedor_id'] = null;
                } else {
                    $vid = (int) $vid;
                    $payload['vendedor_id'] = $vid > 0 ? $vid : null;
                }
            } else {
                $payload['vendedor_id'] = $isUpdate
                    ? ($ordemAtual['vendedor_id'] ?? null)
                    : ($uid > 0 ? $uid : null);
            }
        } else {
            // Não-admin: UPDATE não altera vendedor_id (nem por acidente)
            if ($isUpdate) {
                unset($payload['vendedor_id']);
            } else {
                // Não-admin: CREATE define vendedor_id como usuário logado
                $payload['vendedor_id'] = $uid > 0 ? $uid : null;
            }
        }

        $finalVendedorId = array_key_exists('vendedor_id', $payload)
            ? $payload['vendedor_id']
            : ($ordemAtual['vendedor_id'] ?? null);

        if (!empty($finalVendedorId)) {
            $payload['vendedor'] = null;
            return $payload;
        }

        $legadoAtual = trim((string)($ordemAtual['vendedor'] ?? ''));
        $legadoNovo  = trim((string)($payload['vendedor'] ?? ''));

        if ($legadoAtual !== '') {
            $payload['vendedor'] = $legadoAtual;
        } else {
            if ($legadoNovo === '') {
                $payload['vendedor'] = null;
            }
        }

        return $payload;
    }


    private function moneyToFloat($v): float
    {
        return (float) $this->normalizeMoney((string)$v);
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

            $this->ordemItemModel->update((int)$it['id'], ['total' => $totalItem, 'desconto_valor' => 0.0]);

            $subtotal += $totalItem;
        }

        $descPercent = (float) ($ordem['desconto_percentual'] ?? 0);
        $descPercent = max(0.0, min(100.0, $descPercent));

        $descontoValor = round($subtotal * ($descPercent / 100), 2);
        $totalFinal    = max(0.0, round($subtotal - $descontoValor, 2));

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

        $pagAgg = '(SELECT ordem_id,
                SUM(CASE WHEN status = "confirmado" AND deleted_at IS NULL THEN valor ELSE 0 END) AS total_pago,
                SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) AS qtd_pagamentos
           FROM ordens_pagamento
           GROUP BY ordem_id) op';

        $builder = $this->model
            ->select('ordens.*')
            ->select('c.nome AS cliente')
            ->select('COALESCE(op.total_pago, 0) AS total_pago', false)
            ->select('(ordens.valor_venda - COALESCE(op.total_pago, 0)) AS saldo', false)
            ->select('COALESCE(op.qtd_pagamentos, 0) AS qtd_pagamentos', false)
            ->select('u.name AS vendedor_nome')
            ->select("
                COALESCE(
                    u.name,
                    ordens.vendedor,
                    CASE WHEN ordens.vendedor_id IS NOT NULL THEN CONCAT('Usuário #', ordens.vendedor_id) END
                ) AS vendedor_exibicao
            ", false)
            ->select("
                CASE
                    WHEN ordens.vendedor_id IS NULL AND COALESCE(TRIM(ordens.vendedor), '') <> '' THEN 1
                    ELSE 0
                END AS vendedor_legado
            ", false)
            ->join('clientes c', 'c.id = ordens.cliente_id', 'left')
            ->join($pagAgg, 'op.ordem_id = ordens.id', 'left', false)
            ->join('users u', 'u.id = ordens.vendedor_id', 'left')
            ->orderBy('ordens.id', 'DESC');

        if ($q !== '') {
            // Campo de busca
            if ($field === 'nome_cliente') {
                $builder->like('c.nome', $q);
            } elseif ($field === 'ordem_servico') {
                $builder->like('ordens.ordem_servico', $q);
            } elseif ($field === 'vendedor') {
                if ($q === 'sem_vinculo') {
                    $builder->where('ordens.vendedor_id IS NULL', null, false);
                } elseif (ctype_digit($q)) {
                    $builder->where('ordens.vendedor_id', (int)$q);
                }
            } else {
                $builder->like('c.nome', $q);
            }
        }

        if ($vendedor !== '') {
            // filtro por vendedor: ID numérico OU "sem_vinculo"
            if ($vendedor === 'sem_vinculo') {
                $builder->where('ordens.vendedor_id IS NULL', null, false);
            } elseif (ctype_digit($vendedor)) {
                $builder->where('ordens.vendedor_id', (int)$vendedor);
            }
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

        $users = $this->getActiveUsers();

        return view('ordens/index', [
            'title'       => 'Ordens / Estoque',
            'ordens'      => $ordens,
            'q'           => $q,
            'field'       => $field,
            'vendedor'    => $vendedor,
            'data_ini'    => $dataIni ?: '',
            'data_fim'    => $dataFim ?: '',
            'apply_date'  => $applyDate ? '1' : '0',
            'users'       => $users,
        ]);
    }

    public function store()
    {
        $payload = $this->request->getPost();

        // Agora o total vem dos itens do estoque -> na criação a ordem começa zerada.
        $payload['valor_armacao_1']   = 0;
        $payload['valor_armacao_2']   = null;
        $payload['valor_lente_1']     = 0;
        $payload['valor_lente_2']     = null;
        $payload['tipo_lente_1']      = $payload['tipo_lente_1'] ?? null; // legado
        $payload['tipo_lente_2']      = null; // legado
        $payload['promocao_segundo_par'] = (($payload['promocao_segundo_par'] ?? '0') === '1') ? 1 : 0;

        // Regras de vendedor (vendedor_id + preserva vendedor legado)
        $payload = $this->applyVendedorRulesForSave($payload, null);

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
        $clientes = (new ClienteModel())
            ->select('id, nome')
            ->orderBy('nome', 'ASC')
            ->findAll();

        $ordem = [
            'id' => null,
            'cliente_id' => old('cliente_id') ?: null,
            'status' => old('status') ?: 'aberta',
            'data_compra' => old('data_compra') ?: '',
            'ordem_servico' => old('ordem_servico') ?: '',
            'vendedor' => old('vendedor') ?: '',
            'vendedor_id' => old('vendedor_id') ?: ($this->currentUserId() ?: null),
            'desconto_percentual' => old('desconto_percentual') ?: '0.00',
            'promocao_segundo_par' => old('promocao_segundo_par') ? 1 : 0,
            'obs' => old('obs') ?: '',
            'valor_venda' => 0.00,
            'armacao_1_item_id' => null,
            'lente_1_item_id'   => null,
            'armacao_2_item_id' => null,
            'lente_2_item_id'   => null,
            'consulta'               => old('consulta') ?: 0.00,
            'pagamento_laboratorio'  => old('pagamento_laboratorio') ?: 0.00,
            'nota_gerada'            => old('nota_gerada') ? 1 : 0,
            'dia_nota'               => old('dia_nota') ?: '',
        ];

        $itens = [];

        $totais = [
            'subtotal'       => 0.00,
            'desconto_valor' => 0.00,
            'total'          => 0.00,
        ];

        // Para a view: select de vendedores + flags
        $users = $this->getActiveUsers();
        $isAdmin = $this->isAdmin();
        $isGerente = $this->isGerente();

        return view('ordens/form', [
            'title'          => 'Nova Ordem',
            'ordem'          => $ordem,
            'clientes'       => $clientes,
            'itens'          => $itens,
            'totais'         => $totais,
            'users'          => $users,
            'isAdmin'        => $isAdmin,
            'isGerente'      => $isGerente,
            'isLegacyVenda'  => false,
            'vendedorExibicao' => $this->getUserNameById((int)($ordem['vendedor_id'] ?? 0)) ?? ($ordem['vendedor'] ?: ''),
        ]);
    }

    public function edit($id)
    {
        // Busca com JOIN pra ter nome do vendedor e fallback
        $ordemJoin = $this->model
            ->select('ordens.*, u.name AS vendedor_nome')
            ->join('users u', 'u.id = ordens.vendedor_id', 'left')
            ->where('ordens.id', (int)$id)
            ->first();

        if (!$ordemJoin) {
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

        $saldo = (float)($ordemJoin['valor_venda'] ?? 0) - $totalPago;

        // Flags para a view (LEGADO quando não há vendedor_id)
        $isLegacyVenda = empty($ordemJoin['vendedor_id']);
        $vendedorExibicao = trim((string)($ordemJoin['vendedor_nome'] ?? '')) !== ''
            ? $ordemJoin['vendedor_nome']
            : (string)($ordemJoin['vendedor'] ?? '');

        // Lista de users para admin atribuir vendedor_id
        $users = $this->getActiveUsers();

        return view('ordens/form', [
            'title'            => 'Editar Ordem',
            'ordem'            => $ordemJoin, // já vem com vendedor_nome
            'clientes'         => $clientes,
            'formasPagamento'  => $formasPagamento,
            'pagamentos'       => $pagamentos,
            'itens'            => $itens,
            'totais'           => $totais,
            'financeiro'       => [
                'total_pago'     => $totalPago,
                'saldo'          => $saldo,
                'qtd_pagamentos' => count($pagamentos),
            ],

            // Para a view implementar o comportamento pedido:
            // - input readonly com vendedorExibicao
            // - label "LEGADO" quando isLegacyVenda
            // - select para atribuir vendedor_id quando isAdmin && isLegacyVenda (ou quando quiser permitir troca)
            'users'            => $users,
            'isAdmin'          => $this->isAdmin(),
            'isGerente'        => $this->isGerente(),
            'isLegacyVenda'    => $isLegacyVenda,
            'vendedorExibicao' => $vendedorExibicao,
        ]);
    }

    public function update($id)
    {
        $ordemAtual = $this->model->find((int)$id);
        if (!$ordemAtual) {
            return redirect()->to(site_url('ordens'))->with('errors', ['Registro não encontrado.']);
        }

        $payload = $this->request->getPost();
        $payload['id'] = $id;

        // total vem dos itens (não aceitar edição manual)
        unset($payload['valor_armacao_1'], $payload['valor_armacao_2'], $payload['valor_lente_1'], $payload['valor_lente_2']);

        $payload['promocao_segundo_par'] = (($payload['promocao_segundo_par'] ?? '0') === '1') ? 1 : 0;

        // Regras de vendedor:
        // - mantém legado em ordens.vendedor
        // - admin pode atribuir vendedor_id
        // - não-admin não altera vendedor_id
        $payload = $this->applyVendedorRulesForSave($payload, $ordemAtual);

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

        // valida inteiro e bloqueia 0/negativo
        $qtdRaw = $payload['quantidade'] ?? null;
        $qtd = filter_var($qtdRaw, FILTER_VALIDATE_INT);
        $qtd = ($qtd === false) ? 0 : (int)$qtd;

        if ($qtd <= 0) {
            $msg = 'Quantidade inválida: não é permitido informar 0. Digite um número maior que zero.';

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'ok'   => false,
                    'msg'  => $msg,
                    'csrf' => csrf_hash(),
                ]);
            }

            return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                ->with('error', $msg)
                ->withInput();
        }

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
                $msg = 'Item de estoque não encontrado.';

                if ($this->request->isAJAX()) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'ok'   => false,
                        'msg'  => $msg,
                        'csrf' => csrf_hash(),
                    ]);
                }

                return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                    ->with('error', $msg)
                    ->withInput();
            }

            $precoUnit = (float)($produto['preco_venda'] ?? 0);
            $descricao = trim(($produto['codigo'] ?? '') . ' — ' . ($produto['titulo'] ?? ''));
        } else {
            $descricao = trim((string)($payload['descricao'] ?? 'Serviço'));
            $precoUnit = $this->moneyToFloat($payload['preco_unitario'] ?? '0');
        }

        $total = round($qtd * $precoUnit, 2);

        $this->ordemItemModel->insert([
            'ordem_id'       => $ordemId,
            'tipo'           => $tipo,
            'produto_id'     => $produtoId,
            'descricao'      => $descricao,
            'quantidade'     => $qtd,
            'preco_unitario' => $precoUnit,
            'desconto_valor' => 0.0,
            'total'          => $total,
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
            ->with('success', 'Item adicionado!');
    }

    public function itensUpdate($ordemId, $itemId)
    {
        $ordemId = (int) $ordemId;
        $itemId  = (int) $itemId;

        // valida inteiro e bloqueia 0/negativo
        $qtdPost = $this->request->getPost('quantidade');
        $qtd = filter_var($qtdPost, FILTER_VALIDATE_INT);
        $qtd = ($qtd === false) ? 0 : (int)$qtd;

        if ($qtd <= 0) {
            $msg = 'Quantidade inválida: não é permitido informar 0. Digite um número maior que zero.';

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON([
                    'ok'   => false,
                    'msg'  => $msg,
                    'csrf' => csrf_hash(),
                ]);
            }

            return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                ->with('error', $msg)
                ->withInput();
        }

        $item = $this->ordemItemModel
            ->where('id', $itemId)
            ->where('ordem_id', $ordemId)
            ->first();

        if (!$item) {
            $msg = 'Item da ordem não encontrado.';

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON([
                    'ok'   => false,
                    'msg'  => $msg,
                    'csrf' => csrf_hash(),
                ]);
            }

            return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                ->with('error', $msg);
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
            ->with('success', 'Quantidade atualizada!');
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

        return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))->with('msg', 'Item removido com sucesso!');
    }

    private function normalizeMoney(?string $raw): string
    {
        $raw = trim((string)$raw);
        if ($raw === '') return '0.00';

        // mantém só números e separadores
        $raw = preg_replace('/[^0-9.,]/', '', $raw);

        $lastComma = strrpos($raw, ',');
        $lastDot   = strrpos($raw, '.');

        // Se a última vírgula vem depois do último ponto, assume vírgula decimal
        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            $raw = str_replace('.', '', $raw);   // remove separador de milhar
            $raw = str_replace(',', '.', $raw);  // troca decimal
        } else {
            // caso padrão: ponto decimal, remove vírgulas (milhar)
            $raw = str_replace(',', '', $raw);
        }

        if (!is_numeric($raw)) return '0.00';

        $v = (float)$raw;
        if ($v < 0) $v = 0;

        return number_format($v, 2, '.', '');
    }

    public function addPagamento($ordemId)
    {
        $ordemId = (int) $ordemId;
        $ordem = $this->model->find($ordemId);

        if (!$ordem) {
            return redirect()->to(site_url('ordens'))->with('errors', ['Ordem não encontrada.']);
        }

        $valor = $this->normalizeMoney((string) $this->request->getPost('valor'));
        if ($valor === null || (float)$valor <= 0) {
            return redirect()->back()->withInput()->with('errors', ['Informe um valor de pagamento válido.']);
        }

        $formaId = $this->request->getPost('forma_pagamento_id');
        $formaId = ($formaId === '' || $formaId === null) ? null : (int)$formaId;

        $dataRaw = (string)$this->request->getPost('data_pagamento');
        $dataDb  = $this->toDbDate($dataRaw);
        $dataPagamento = $dataDb ? ($dataDb . ' 00:00:00') : date('Y-m-d H:i:s');

        $obs = trim((string)$this->request->getPost('obs'));

        // Total atual confirmado
        $row = $this->pagamentoModel
            ->select('COALESCE(SUM(valor),0) AS total', false)
            ->where('ordem_id', $ordemId)
            ->where('status', 'confirmado')
            ->first();

        $totalAtual = (float)($row['total'] ?? 0);
        $novoTotal  = $totalAtual + (float)$valor;

        $valorVenda = (float)($ordem['valor_venda'] ?? 0);
        $eps = 0.0001;

        // Tipo automático
        if ($novoTotal >= ($valorVenda - $eps) && $valorVenda > 0) {
            $tipo = 'quitacao';
        } elseif ($totalAtual <= $eps) {
            $tipo = 'entrada';
        } else {
            $tipo = 'parcial';
        }

        $valorVenda = (float)($ordem['valor_venda'] ?? 0);
        $eps = 0.0001;

        $row = $this->pagamentoModel
            ->select('COALESCE(SUM(valor),0) AS total', false)
            ->where('ordem_id', $ordemId)
            ->where('status', 'confirmado')
            ->first();

        $totalAtual = (float)($row['total'] ?? 0);
        $saldoAtual = $valorVenda - $totalAtual;

        if ($valorVenda > 0 && $saldoAtual <= $eps) {
            return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                ->with('errors', ['Ordem já quitada. Não é possível registrar novo pagamento.']);
        }

        if ($valorVenda > 0 && (float)$valor > ($saldoAtual + $eps)) {
            return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
                ->with('errors', ['O valor do pagamento excede o saldo atual da ordem.']);
        }

        if (!$this->pagamentoModel->insert([
            'ordem_id'           => $ordemId,
            'forma_pagamento_id' => $formaId,
            'valor'              => $valor,
            'status'             => 'confirmado',
            'data_pagamento'     => $dataPagamento,
            'tipo'               => $tipo,
            'origem'             => 'sistema',
            'obs'                => $obs !== '' ? $obs : null,
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->pagamentoModel->errors());
        }

        return redirect()->to(site_url('ordens/' . $ordemId . '/edit'))
            ->with('msg', 'Pagamento registrado com sucesso!');
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
            ->with('msg', 'Pagamento removido.');
    }
}

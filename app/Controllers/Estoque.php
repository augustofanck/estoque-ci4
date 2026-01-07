<?php

namespace App\Controllers;

use App\Models\EstoqueItemModel;
use App\Models\EstoqueTipoModel;
use App\Models\EstoqueMovimentoModel;

class Estoque extends BaseController
{
    /**
     * Estoque: somente GERENTE(1) ou ADMIN(2)
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

        $itemModel = new EstoqueItemModel();
        $tipoModel = new EstoqueTipoModel();

        $data = [
            'tipos' => $tipoModel->where('ativo', 1)->orderBy('nome', 'asc')->findAll(),
            'itens' => $itemModel->select('estoque_itens.*, estoque_tipos.nome as tipo_nome')
                ->join('estoque_tipos', 'estoque_tipos.id = estoque_itens.tipo_id', 'left')
                ->orderBy('estoque_itens.id', 'desc')
                ->findAll(),
        ];

        return view('estoque/index', $data);
    }

    public function create()
    {
        if ($r = $this->guard()) return $r;

        $tipoModel = new EstoqueTipoModel();
        return view('estoque/form', [
            'tipos' => $tipoModel->where('ativo', 1)->orderBy('nome', 'asc')->findAll(),
            'item'  => null,
        ]);
    }

    public function store()
    {
        if ($r = $this->guard()) return $r;

        $itemModel = new EstoqueItemModel();
        $dados = $this->request->getPost();

        // atributos pode vir como array -> salva em JSON
        if (isset($dados['atributos']) && is_array($dados['atributos'])) {
            $dados['atributos'] = json_encode($dados['atributos'], JSON_UNESCAPED_UNICODE);
        }

        if (!$itemModel->insert($dados)) {
            return redirect()->back()->withInput()->with('errors', $itemModel->errors());
        }

        return redirect()->to('/estoque')->with('success', 'Item criado com sucesso!');
    }

    public function edit($id)
    {
        if ($r = $this->guard()) return $r;

        $itemModel = new EstoqueItemModel();
        $tipoModel = new EstoqueTipoModel();

        $item = $itemModel->find($id);
        if (!$item) return redirect()->to('/estoque')->with('error', 'Item não encontrado.');

        return view('estoque/form', [
            'tipos' => $tipoModel->where('ativo', 1)->orderBy('nome', 'asc')->findAll(),
            'item'  => $item,
        ]);
    }

    public function update($id)
    {
        if ($r = $this->guard()) return $r;

        $itemModel = new EstoqueItemModel();
        $dados = $this->request->getPost();

        if (isset($dados['atributos']) && is_array($dados['atributos'])) {
            $dados['atributos'] = json_encode($dados['atributos'], JSON_UNESCAPED_UNICODE);
        }

        if (!$itemModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('errors', $itemModel->errors());
        }

        return redirect()->to('/estoque')->with('success', 'Item atualizado!');
    }

    public function delete($id)
    {
        if ($r = $this->guard()) return $r;

        // Opcional: só admin pode deletar
        // if (!is_admin()) return redirect()->to('/estoque')->with('error', 'Acesso não permitido!');

        $itemModel = new EstoqueItemModel();
        $itemModel->delete($id);

        return redirect()->to('/estoque')->with('success', 'Item removido!');
    }

    public function movimentar($id)
    {
        if ($r = $this->guard()) return $r;

        $itemModel = new EstoqueItemModel();
        $movModel  = new EstoqueMovimentoModel();

        $item = $itemModel->find($id);
        if (!$item) return redirect()->to('/estoque')->with('error', 'Item não encontrado.');

        $tipo   = (string) $this->request->getPost('tipo'); // E, S, A
        $qtd    = (int) $this->request->getPost('quantidade');
        $motivo = $this->request->getPost('motivo');
        $ref    = $this->request->getPost('referencia');

        // validação rápida (evita movimentação vazia/errada)
        if (!in_array($tipo, ['E', 'S', 'A'], true)) {
            return redirect()->back()->with('error', 'Tipo de movimentação inválido.');
        }
        if ($qtd <= 0) {
            return redirect()->back()->with('error', 'Quantidade deve ser maior que zero.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        // calcula novo saldo
        $novoSaldo = (int) $item['qtd_atual'];
        if ($tipo === 'E') $novoSaldo += $qtd;
        if ($tipo === 'S') $novoSaldo -= $qtd;
        if ($tipo === 'A') $novoSaldo  = $qtd;

        if ($novoSaldo < 0) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Saldo não pode ficar negativo.');
        }

        $ok1 = $itemModel->update($id, ['qtd_atual' => $novoSaldo]);

        $ok2 = $movModel->insert([
            'item_id'    => $id,
            'tipo'       => $tipo,
            'quantidade' => $qtd,
            'motivo'     => $motivo,
            'referencia' => $ref,
            'user_id'    => session('user_id') ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$ok1 || !$ok2 || $db->transStatus() === false) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Erro ao registrar movimentação.');
        }

        $db->transCommit();

        return redirect()->to('/estoque')->with('success', 'Movimentação registrada!');
    }

    public function relatorios()
    {
        if ($r = $this->guard()) return $r;

        $db = \Config\Database::connect();

        // filtros (GET)
        $ini = (string) ($this->request->getGet('ini') ?? '');
        $fim = (string) ($this->request->getGet('fim') ?? '');
        $tipoId = $this->request->getGet('tipo_id');    // int
        $itemId = $this->request->getGet('item_id');    // int
        $codigo = (string) ($this->request->getGet('codigo') ?? '');
        $movTipo = (string) ($this->request->getGet('mov_tipo') ?? ''); // E,S,A

        // defaults: últimos 30 dias
        $today = date('Y-m-d');
        if ($fim === '') $fim = $today;
        if ($ini === '') $ini = date('Y-m-d', strtotime($fim . ' -30 days'));

        // saneamento simples de datas (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini)) $ini = date('Y-m-d', strtotime('-30 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim)) $fim = $today;

        $iniDT = $ini . ' 00:00:00';
        $fimDT = $fim . ' 23:59:59';

        // lista de tipos (para filtro)
        $tipos = $db->table('estoque_tipos')
            ->select('id, nome')
            ->where('ativo', 1)
            ->orderBy('nome', 'asc')
            ->get()->getResultArray();

        // base: movimentos no período + joins
        $movQ = $db->table('estoque_movimentos m')
            ->select('
            m.id, m.tipo, m.quantidade, m.motivo, m.referencia, m.user_id, m.created_at,
            i.id as item_id, i.codigo, i.titulo, i.categoria,
            t.id as tipo_id, t.nome as tipo_nome
        ')
            ->join('estoque_itens i', 'i.id = m.item_id', 'inner')
            ->join('estoque_tipos t', 't.id = i.tipo_id', 'left')
            ->where('m.created_at >=', $iniDT)
            ->where('m.created_at <=', $fimDT)
            ->where('i.deleted_at IS NULL', null, false);

        // filtros opcionais
        if ($tipoId !== null && $tipoId !== '' && ctype_digit((string)$tipoId)) {
            $movQ->where('i.tipo_id', (int)$tipoId);
        }
        if ($itemId !== null && $itemId !== '' && ctype_digit((string)$itemId)) {
            $movQ->where('i.id', (int)$itemId);
        }
        if ($codigo !== '') {
            $movQ->like('i.codigo', $codigo);
        }
        if ($movTipo !== '' && in_array($movTipo, ['E', 'S', 'A'], true)) {
            $movQ->where('m.tipo', $movTipo);
        }

        // movimentos (lista)
        $movimentos = $movQ
            ->orderBy('m.created_at', 'desc')
            ->limit(300)
            ->get()->getResultArray();

        // resumo geral (entradas/saídas/ajustes)
        $resumo = $db->table('estoque_movimentos m')
            ->select("
            SUM(CASE WHEN m.tipo='E' THEN m.quantidade ELSE 0 END) AS total_entradas,
            SUM(CASE WHEN m.tipo='S' THEN m.quantidade ELSE 0 END) AS total_saidas,
            SUM(CASE WHEN m.tipo='A' THEN 1 ELSE 0 END) AS total_ajustes,
            COUNT(*) AS total_movs
        ", false)
            ->join('estoque_itens i', 'i.id = m.item_id', 'inner')
            ->where('m.created_at >=', $iniDT)
            ->where('m.created_at <=', $fimDT)
            ->where('i.deleted_at IS NULL', null, false);

        if ($tipoId !== null && $tipoId !== '' && ctype_digit((string)$tipoId)) {
            $resumo->where('i.tipo_id', (int)$tipoId);
        }
        if ($itemId !== null && $itemId !== '' && ctype_digit((string)$itemId)) {
            $resumo->where('i.id', (int)$itemId);
        }
        if ($codigo !== '') {
            $resumo->like('i.codigo', $codigo);
        }
        if ($movTipo !== '' && in_array($movTipo, ['E', 'S', 'A'], true)) {
            $resumo->where('m.tipo', $movTipo);
        }

        $resumo = $resumo->get()->getRowArray() ?? [
            'total_entradas' => 0,
            'total_saidas' => 0,
            'total_ajustes' => 0,
            'total_movs' => 0,
        ];

        // ranking por item (o que mais saiu/entrou no período)
        $rankingItens = $db->table('estoque_movimentos m')
            ->select("
            i.id as item_id, i.codigo, i.titulo,
            SUM(CASE WHEN m.tipo='E' THEN m.quantidade ELSE 0 END) AS entradas,
            SUM(CASE WHEN m.tipo='S' THEN m.quantidade ELSE 0 END) AS saidas
        ", false)
            ->join('estoque_itens i', 'i.id = m.item_id', 'inner')
            ->where('m.created_at >=', $iniDT)
            ->where('m.created_at <=', $fimDT)
            ->where('i.deleted_at IS NULL', null, false)
            ->groupBy('i.id')
            ->orderBy('saidas', 'desc')
            ->limit(10);

        if ($tipoId !== null && $tipoId !== '' && ctype_digit((string)$tipoId)) {
            $rankingItens->where('i.tipo_id', (int)$tipoId);
        }

        $rankingItens = $rankingItens->get()->getResultArray();

        return view('estoque/relatorios', [
            'filtros' => [
                'ini' => $ini,
                'fim' => $fim,
                'tipo_id' => $tipoId,
                'item_id' => $itemId,
                'codigo' => $codigo,
                'mov_tipo' => $movTipo,
            ],
            'tipos' => $tipos,
            'resumo' => $resumo,
            'movimentos' => $movimentos,
            'rankingItens' => $rankingItens,
        ]);
    }
}

<?php

namespace App\Controllers;

use App\Models\EstoqueItemModel;
use App\Models\EstoqueMovimentoModel;
use App\Models\EstoqueTipoModel;

class Estoque extends BaseController
{
    private EstoqueItemModel $itemModel;
    private EstoqueMovimentoModel $movModel;
    private EstoqueTipoModel $tipoModel;

    public function __construct()
    {
        $this->itemModel = new EstoqueItemModel();
        $this->movModel  = new EstoqueMovimentoModel();
        $this->tipoModel = new EstoqueTipoModel();
        helper(['form', 'url', 'text']);
    }

    /**
     * Normaliza moeda pt-BR / inputs variados para decimal com ponto.
     * Exemplos:
     *  "1.234,56" -> "1234.56"
     *  "1234,56"  -> "1234.56"
     *  "1234.56"  -> "1234.56"
     *  ""         -> "0.00"
     */
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

    public function index()
    {
        $f = $this->request->getGet();

        $tipo     = $f['tipo_id'] ?? null;
        $categoria = $f['categoria'] ?? null;
        $q        = trim((string)($f['q'] ?? ''));

        $builder = $this->itemModel
            ->select('estoque_itens.*, estoque_tipos.nome as tipo_nome')
            ->join('estoque_tipos', 'estoque_tipos.id = estoque_itens.tipo_id', 'left')
            ->where('estoque_itens.deleted_at IS NULL', null, false)
            ->orderBy('estoque_itens.id', 'DESC');

        if ($tipo) $builder->where('estoque_itens.tipo_id', (int)$tipo);
        if ($categoria) $builder->where('estoque_itens.categoria', (string)$categoria);

        if ($q !== '') {
            $builder->groupStart()
                ->like('estoque_itens.codigo', $q)
                ->orLike('estoque_itens.titulo', $q)
                ->orLike('estoque_itens.categoria', $q)
                ->groupEnd();
        }

        $itens = $builder->findAll();

        $tipos = $this->tipoModel
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('nome', 'ASC')
            ->findAll();

        return view('estoque/index', [
            'title'     => 'Estoque',
            'itens'     => $itens,
            'tipos'     => $tipos,
            'filtros'   => $f,
        ]);
    }

    public function create()
    {
        $tipos = $this->tipoModel
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('nome', 'ASC')
            ->findAll();

        return view('estoque/form', [
            'title' => 'Novo Item',
            'item'  => [
                'ativo'      => 1,
                'qtd_atual'  => 0,
                'qtd_minima' => 0,
                'preco_venda' => 0, // NOVO default para UI
            ],
            'tipos' => $tipos,
        ]);
    }

    public function store()
    {
        $dados = $this->request->getPost([
            'codigo',
            'tipo_id',
            'titulo',
            'categoria',
            'atributos',
            'qtd_minima',
            'preco_venda', // NOVO
        ]);

        // Normaliza preço de venda
        if (array_key_exists('preco_venda', $dados)) {
            $dados['preco_venda'] = $this->normalizeMoney($dados['preco_venda']);
        }

        // Atributos como JSON
        if (isset($dados['atributos']) && is_array($dados['atributos'])) {
            $dados['atributos'] = json_encode($dados['atributos'], JSON_UNESCAPED_UNICODE);
        }

        $dados['qtd_atual'] = 0;
        $dados['ativo'] = 1;

        if (!$this->itemModel->insert($dados)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->itemModel->errors());
        }

        return redirect()->to(site_url('estoque'))->with('success', 'Item criado com sucesso!');
    }

    public function edit($id)
    {
        $item = $this->itemModel->find($id);

        if (!$item) {
            return redirect()->to(site_url('estoque'))->with('error', 'Item não encontrado.');
        }

        $tipos = $this->tipoModel
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('nome', 'ASC')
            ->findAll();

        return view('estoque/form', [
            'title' => 'Editar Item',
            'item'  => $item,
            'tipos' => $tipos,
        ]);
    }

    public function update($id)
    {
        $dados = $this->request->getPost([
            'codigo',
            'tipo_id',
            'titulo',
            'categoria',
            'atributos',
            'qtd_minima',
            'ativo',
            'preco_venda', // NOVO
        ]);

        // Normaliza preço de venda
        if (array_key_exists('preco_venda', $dados)) {
            $dados['preco_venda'] = $this->normalizeMoney($dados['preco_venda']);
        }

        if (isset($dados['atributos']) && is_array($dados['atributos'])) {
            $dados['atributos'] = json_encode($dados['atributos'], JSON_UNESCAPED_UNICODE);
        }

        if (!$this->itemModel->update($id, $dados)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->itemModel->errors());
        }

        return redirect()->to(site_url('estoque'))->with('success', 'Item atualizado com sucesso!');
    }

    public function delete($id)
    {
        $this->itemModel->delete($id);
        return redirect()->to(site_url('estoque'))->with('success', 'Item removido.');
    }

    public function movimentos($id)
    {
        $item = $this->itemModel->find($id);

        if (!$item) {
            return redirect()->to(site_url('estoque'))->with('error', 'Item não encontrado.');
        }

        $movimentos = $this->movModel
            ->select('estoque_movimentos.*, users.name as usuario_nome')
            ->join('users', 'users.id = estoque_movimentos.user_id', 'left')
            ->where('estoque_movimentos.item_id', $id)
            ->orderBy('estoque_movimentos.id', 'DESC')
            ->findAll();

        return view('estoque/movimentos', [
            'title'      => 'Movimentos do Item',
            'item'       => $item,
            'movimentos' => $movimentos,
        ]);
    }

    public function ajustar($id)
    {
        $item = $this->itemModel->find($id);
        if (!$item) return redirect()->to(site_url('estoque'))->with('error', 'Item não encontrado.');

        $qtd = (int)$this->request->getPost('qtd');
        $obs = trim((string)$this->request->getPost('obs'));

        $delta = $qtd - (int)$item['qtd_atual'];

        if ($delta === 0) {
            return redirect()->back()->with('error', 'Nenhuma alteração na quantidade.');
        }

        $this->movModel->insert([
            'item_id'     => $id,
            'tipo'        => 'A',
            'qtd'         => $delta,
            'obs'         => $obs,
            'user_id'     => null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->itemModel->update($id, ['qtd_atual' => $qtd]);

        return redirect()->to(site_url("estoque/{$id}/movimentos"))->with('success', 'Quantidade ajustada!');
    }

    public function autocomplete()
    {
        $term = trim((string)($this->request->getGet('q') ?? $this->request->getGet('term') ?? ''));
        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;

        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        // Busca 1 a mais para detectar se existe próxima página (pagination.more)
        $limitPlusOne = $perPage + 1;

        $builder = $this->itemModel
            ->select('
            estoque_itens.id,
            estoque_itens.codigo,
            estoque_itens.titulo,
            estoque_itens.categoria,
            estoque_itens.qtd_atual,
            estoque_itens.preco_venda,
            estoque_tipos.nome AS tipo_nome
        ')
            ->join('estoque_tipos', 'estoque_tipos.id = estoque_itens.tipo_id', 'left')
            ->where('estoque_itens.ativo', 1)
            ->where('estoque_itens.deleted_at IS NULL', null, false);

        if ($term !== '') {
            $builder->groupStart()
                ->like('estoque_itens.codigo', $term)
                ->orLike('estoque_itens.titulo', $term)
                ->orLike('estoque_itens.categoria', $term)
                ->orLike('estoque_tipos.nome', $term)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('estoque_itens.codigo', 'ASC')
            ->limit($limitPlusOne, $offset)
            ->findAll();

        $more = count($rows) > $perPage;
        if ($more) {
            array_pop($rows); // remove o “extra”
        }

        $results = [];
        foreach ($rows as $r) {
            $codigo = (string)($r['codigo'] ?? '');
            $titulo = (string)($r['titulo'] ?? '');
            $text   = trim($codigo . ' — ' . ($titulo !== '' ? $titulo : 'Sem título'));

            $precoVenda = null;
            if (isset($r['preco_venda']) && $r['preco_venda'] !== '' && $r['preco_venda'] !== null) {
                // mantém em string "0.00" pro front decidir como exibir
                $precoVenda = number_format((float)$r['preco_venda'], 2, '.', '');
            }

            $results[] = [
                'id'             => (int)$r['id'],
                'text'           => $text,
                'codigo'         => $codigo,
                'titulo'         => $titulo,
                'categoria'      => (string)($r['categoria'] ?? ''),
                'tipo_nome'      => (string)($r['tipo_nome'] ?? ''),
                'qtd_atual'      => isset($r['qtd_atual']) ? (int)$r['qtd_atual'] : 0,

                // compatibilidade com o que você já usa no select2 (data-preco / preco_sugerido)
                'preco_sugerido' => $precoVenda,
                'preco_venda'    => $precoVenda,
            ];
        }

        return $this->response->setJSON([
            'results'    => $results,
            'pagination' => ['more' => $more],
        ]);
    }
}

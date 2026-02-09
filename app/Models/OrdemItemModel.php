<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdemItemModel extends Model
{
    protected $table            = 'ordens_itens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'ordem_id',
        'tipo',
        'produto_id',
        'descricao',
        'quantidade',
        'preco_unitario',
        'desconto_valor',
        'total',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'ordem_id'      => 'required|is_natural_no_zero',
        'tipo'          => 'required|in_list[produto,servico]',
        'produto_id'    => 'permit_empty|is_natural_no_zero',
        'descricao'     => 'permit_empty|max_length[255]',
        'quantidade'    => 'required|is_natural_no_zero',
        'preco_unitario' => 'required|decimal|greater_than_equal_to[0]',
        'desconto_valor' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'total'         => 'required|decimal|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'ordem_id' => [
            'required' => 'Ordem é obrigatória.',
        ],
        'tipo' => [
            'in_list' => 'Tipo inválido.',
        ],
        'quantidade' => [
            'is_natural_no_zero' => 'Quantidade deve ser maior que zero.',
        ],
        'preco_unitario' => [
            'decimal' => 'Preço unitário inválido.',
        ],
    ];

    protected $skipValidation = false;
}

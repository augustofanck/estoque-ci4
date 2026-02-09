<?php

namespace App\Models;

use CodeIgniter\Model;

class EstoqueItemModel extends Model
{
    protected $table            = 'estoque_itens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'codigo',
        'tipo_id',
        'titulo',
        'categoria',
        'atributos',
        'qtd_atual',
        'qtd_minima',
        'preco_venda',
        'ativo',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'codigo'      => 'required|max_length[80]',
        'tipo_id'     => 'required|is_natural_no_zero',
        'categoria'   => 'required|max_length[60]',
        'qtd_atual'   => 'permit_empty|integer|greater_than_equal_to[0]',
        'qtd_minima'  => 'permit_empty|integer|greater_than_equal_to[0]',
        'preco_venda' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'ativo'       => 'permit_empty|in_list[0,1]',
    ];

    protected array $casts = [
        'id'         => 'integer',
        'tipo_id'    => 'integer',
        'qtd_atual'  => 'integer',
        'qtd_minima' => 'integer',
        'ativo'      => 'integer',
        'preco_venda' => 'float',
    ];
}

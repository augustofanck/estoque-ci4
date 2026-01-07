<?php

namespace App\Models;

use CodeIgniter\Model;

class EstoqueItemModel extends Model
{
    protected $table      = 'estoque_itens';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'codigo',
        'tipo_id',
        'titulo',
        'categoria',
        'atributos',
        'qtd_atual',
        'qtd_minima',
        'ativo'
    ];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

    protected $validationRules = [
        'codigo'  => 'required|min_length[2]|max_length[80]|is_unique[estoque_itens.codigo,id,{id}]',
        'tipo_id' => 'required|is_natural_no_zero',
        'ativo'   => 'permit_empty|in_list[0,1]',
        'qtd_atual'  => 'permit_empty|integer',
        'qtd_minima' => 'permit_empty|integer',
    ];
}

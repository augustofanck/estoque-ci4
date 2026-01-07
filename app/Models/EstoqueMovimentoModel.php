<?php

namespace App\Models;

use CodeIgniter\Model;

class EstoqueMovimentoModel extends Model
{
    protected $table      = 'estoque_movimentos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['item_id', 'tipo', 'quantidade', 'motivo', 'referencia', 'user_id', 'created_at'];
    protected $useTimestamps = false;

    protected $validationRules = [
        'item_id'    => 'required|is_natural_no_zero',
        'tipo'       => 'required|in_list[E,S,A]',
        'quantidade' => 'required|integer',
    ];
}

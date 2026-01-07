<?php

namespace App\Models;

use CodeIgniter\Model;

class EstoqueTipoModel extends Model
{
    protected $table      = 'estoque_tipos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nome', 'descricao', 'ativo'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'nome' => 'required|min_length[2]|max_length[120]|is_unique[estoque_tipos.nome,id,{id}]',
        'ativo' => 'permit_empty|in_list[0,1]',
    ];
}

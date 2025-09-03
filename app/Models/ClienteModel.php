<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table      = 'clientes';    // nome da tabela
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nome',
        'cnpj_cpf',
        'termino_contrato',
        'created_at',
        'updated_at'
    ];
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class ClienteModel extends Model
{
    protected $table            = 'clientes';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'nome',
        'documento',
        'email',
        'telefone',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'termino_contrato'
    ];

    protected $validationMessages = [
        'cep' => [
            'regex_match' => 'CEP deve ter 8 dígitos (ex.: 01310930).'
        ],
    ];

    protected $validationRules = [
        'nome'  => 'required|min_length[2]|max_length[150]',
        'email' => 'permit_empty|valid_email|max_length[150]',
        'estado' => 'permit_empty|exact_length[2]',
        'cep' => 'permit_empty|regex_match[/^\d{8}$/]',
        'documento'   => 'permit_empty|regex_match[/^\d{11}$/]'
    ];
}

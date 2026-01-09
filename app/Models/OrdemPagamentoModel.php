<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdemPagamentoModel extends Model
{
    protected $table          = 'ordens_pagamento';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'ordem_id',
        'forma_pagamento_id',
        'valor',
        'status',
        'data_pagamento',
        'data_compensacao',
        'tipo',
        'origem',
        'obs',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $validationRules = [
        'ordem_id' => 'required|is_natural_no_zero',
        'valor'    => 'required|decimal',
    ];
}

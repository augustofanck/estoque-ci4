<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdemModel extends Model
{
    protected $table            = 'ordens';
    protected $primaryKey       = 'id';
    protected $useSoftDeletes   = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields    = [
        'cliente_id',
        'status',
        'data_compra',
        'ordem_servico',
        'nome_cliente',
        'valor_venda',
        'valor_entrada',
        'forma_pagamento_entrada',
        'valor_pago',
        'formas_pagamento',
        'valor_armacao_1',
        'valor_armacao_2',
        'tipo_lente_1',
        'tipo_lente_2',
        'valor_lente_1',
        'valor_lente_2',
        'consulta',
        'pagamento_laboratorio',
        'dia_pagamento_laboratorio',
        'data_recebimento_laboratorio',
        'data_entrega_oculos',
        'nota_gerada',
        'dia_nota',
        'vendedor',
    ];

    protected $validationRules = [
        'cliente_id' => 'required|is_natural_no_zero|is_not_unique[clientes.id]',
        'data_compra'   => 'permit_empty|valid_date',
        'valor_venda'   => 'required|decimal',
        'valor_pago'    => 'permit_empty|decimal',
        'valor_entrada' => 'permit_empty|decimal',
        'valor_armacao_1' => 'permit_empty|decimal',
        'valor_armacao_2' => 'permit_empty|decimal',
        'valor_lente_1' => 'permit_empty|decimal',
        'valor_lente_2' => 'permit_empty|decimal',
        'pagamento_laboratorio' => 'permit_empty|decimal',
        'data_compra'                  => 'permit_empty|valid_date[Y-m-d]',
        'dia_pagamento_laboratorio'    => 'permit_empty|valid_date[Y-m-d]',
        'data_recebimento_laboratorio' => 'permit_empty|valid_date[Y-m-d]',
        'data_entrega_oculos'          => 'permit_empty|valid_date[Y-m-d]',
        'dia_nota'                     => 'permit_empty|valid_date[Y-m-d]',
        'nota_gerada' => 'permit_empty|in_list[0,1]',
    ];


    protected $validationMessages = [
        'cliente_id' => [
            'required' => 'Selecione um cliente',
            'is_natural_no_zero' => 'Cliente inválido',
            'is_not_unique' => 'Cliente não encontrado'
        ],
        'data_compra' => [
            'required' => 'Informe a data da compra.',
            'valid_date' => 'Data da compra inválida.'
        ],
        'valor_venda' => [],
        'valor_pago' => [],
        'valor_entrada' => [],
        'valor_armacao_1' => [],
        'valor_armacao_2' => [],
        'valor_lente_1' => [],
        'valor_lente_2' => [],
        'pagamento_laboratorio' => [],
        'data_compra' => [],
        'dia_pagamento_laboratorio' => [],
        'data_recebimento_laboratorio' => [],
        'data_entrega_oculos' => [],
        'dia_nota' => [],
        'nota_gerada' => [],
    ];
}

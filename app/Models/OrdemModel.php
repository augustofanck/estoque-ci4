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
        'numero_nota',
        'vendedor',
        'obs',
    ];

    protected $validationRules = [
        'cliente_id'                => 'required|is_natural_no_zero|is_not_unique[clientes.id]',
        'data_compra'               => 'permit_empty|valid_date[Y-m-d]',

        'valor_venda'               => 'required',
        'valor_entrada'             => 'required',
        'valor_pago'                => 'required',
        'valor_armacao_1'           => 'required',
        'valor_lente_1'             => 'required',
        'consulta'                  => 'required',
        'pagamento_laboratorio'     => 'required',

        // opcionais
        'valor_armacao_2'           => 'permit_empty|decimal',
        'valor_lente_2'             => 'permit_empty|decimal',
        'dia_pagamento_laboratorio' => 'permit_empty|valid_date[Y-m-d]',
        'data_recebimento_laboratorio' => 'permit_empty|valid_date[Y-m-d]',
        'data_entrega_oculos'       => 'permit_empty|valid_date[Y-m-d]',
        'dia_nota'                  => 'permit_empty|valid_date[Y-m-d]',
        'numero_nota'               => 'permit_empty|decimal',
    ];

    protected $validationMessages = [
        'cliente_id' => [
            'required'           => 'Selecione um cliente.',
            'is_natural_no_zero' => 'Cliente inválido.',
            'is_not_unique'      => 'Cliente não encontrado.'
        ],
        'data_compra' => [
            'valid_date'         => 'Data da compra inválida.'
        ],
        'valor_venda' => [
            'required' => 'Informe o valor de venda.',
        ],
        'valor_entrada' => [
            'required' => 'Informe o valor de entrada.',
        ],
        'valor_pago' => [
            'required' => 'Informe o valor pago.',
        ],
        'valor_armacao_1' => [
            'required' => 'Informe o valor da armação.',
        ],
        'valor_lente_1' => [
            'required' => 'Informe o valor da lente.',
        ],
        'consulta' => [
            'required' => 'Informe o valor da consulta.',
        ],
        'pagamento_laboratorio' => [
            'required' => 'Informe o pagamento do laboratório.',
        ]
    ];

    // dentro de App\Models\OrdemModel

    public function relatorioPorPeriodo(string $dataInicio, string $dataFim): array
    {
        return $this->select('
            id,
            nome_cliente,
            data_compra AS dia_entrada,
            valor_venda,
            valor_entrada,
            dia_nota,
            numero_nota
        ')
            ->where('data_compra >=', $dataInicio)
            ->where('data_compra <=', $dataFim)
            ->orderBy('data_compra', 'ASC')
            ->findAll();
    }

    public function totaisPorPeriodo(string $dataInicio, string $dataFim): array
    {
        return $this->select('
            SUM(valor_venda)   AS total_venda,
            SUM(valor_entrada) AS total_entrada
        ')
            ->where('data_compra >=', $dataInicio)
            ->where('data_compra <=', $dataFim)
            ->first();
    }
}

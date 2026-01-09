<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class Relatorios extends BaseController
{
    public function index()
    {
        $data = [
            'titulo' => 'Relatórios',
            'tipos'  => [
                'ordens' => 'Relatório de Ordens',
            ],
        ];

        return view('relatorios/index', $data);
    }

    public function ordens()
    {
        $dataInicio = $this->request->getGet('data_inicio');
        $dataFim    = $this->request->getGet('data_fim');

        if (empty($dataInicio) || empty($dataFim)) {
            $dataInicio = date('Y-m-01');
            $dataFim    = date('Y-m-t');
        }

        $db = Database::connect();

        // Aggregador de pagamentos por ordem (novo módulo)
        $subPagSql = $db->table('ordens_pagamento op')
            ->select("
                op.ordem_id,
                COALESCE(SUM(CASE WHEN op.status='confirmado' THEN op.valor ELSE 0 END),0) AS total_pago,
                COALESCE(SUM(CASE WHEN op.status='confirmado' AND op.tipo='entrada' THEN op.valor ELSE 0 END),0) AS total_entrada,
                COALESCE(SUM(CASE WHEN op.status IN ('confirmado','pendente') THEN 1 ELSE 0 END),0) AS qtd_pagamentos,
                MAX(CASE WHEN op.status='confirmado' THEN op.data_pagamento ELSE NULL END) AS ultimo_pagamento
            ", false)
            ->where('op.deleted_at IS NULL', null, false)
            ->groupBy('op.ordem_id')
            ->getCompiledSelect();

        $builder = $db->table('ordens o');

        $builder->select("
            o.id,
            o.data_compra,
            o.valor_venda,
            o.dia_nota,
            o.numero_nota,
            c.nome AS cliente_nome,
            COALESCE(pg.total_pago, 0) AS total_pago,
            COALESCE(pg.total_entrada, 0) AS valor_entrada,
            COALESCE(pg.qtd_pagamentos, 0) AS qtd_pagamentos,
            pg.ultimo_pagamento,
            (o.valor_venda - COALESCE(pg.total_pago, 0)) AS saldo
        ", false);

        $builder->join('clientes c', 'c.id = o.cliente_id', 'left');
        $builder->join("($subPagSql) pg", "pg.ordem_id = o.id", 'left', false);

        $builder->where('o.data_compra >=', $dataInicio);
        $builder->where('o.data_compra <=', $dataFim);

        // Soft delete ordens
        $builder->where('o.deleted_at IS NULL', null, false);

        $builder->orderBy('o.data_compra', 'ASC');
        $ordens = $builder->get()->getResultArray();

        // Totais do período (somatório em PHP pra não brigar com SQL)
        $totais = [
            'total_venda'   => 0.0,
            'total_pago'    => 0.0,
            'total_entrada' => 0.0,
            'total_saldo'   => 0.0,
            'qtd_pagamentos' => 0,
        ];

        foreach ($ordens as $o) {
            $venda   = (float)($o['valor_venda'] ?? 0);
            $pago    = (float)($o['total_pago'] ?? 0);
            $entrada = (float)($o['valor_entrada'] ?? 0);
            $saldo   = (float)($o['saldo'] ?? 0);
            if ($saldo < 0) $saldo = 0;

            $totais['total_venda']   += $venda;
            $totais['total_pago']    += $pago;
            $totais['total_entrada'] += $entrada;
            $totais['total_saldo']   += $saldo;
            $totais['qtd_pagamentos'] += (int)($o['qtd_pagamentos'] ?? 0);
        }

        return view('relatorios/ordens', [
            'titulo'     => 'Relatório de Ordens',
            'dataInicio' => $dataInicio,
            'dataFim'    => $dataFim,
            'ordens'     => $ordens,
            'totais'     => $totais,
        ]);
    }
}

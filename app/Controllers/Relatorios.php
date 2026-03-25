<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;
use DateTime;

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

        // 1) Busca “flat”: 1 linha por pagamento (atual ou fallback legado)
        $rows = $db->table('vw_relatorio_vendas_pagamentos_flat')
            ->select('ordem_id, data_venda, valor_venda, numero_nota, pagamento_valor, pagamento_forma, pagamento_origem, pagamento_tipo, pagamento_data')
            ->where('data_venda >=', $dataInicio)
            ->where('data_venda <=', $dataFim)
            ->orderBy('data_venda', 'ASC')
            ->orderBy('ordem_id', 'ASC')
            ->orderBy('pagamento_data', 'ASC')
            ->get()
            ->getResultArray();

        // 2) Agrupa por ordem e monta array de pagamentos
        $orders = [];
        foreach ($rows as $r) {
            $id = (int)$r['ordem_id'];

            if (!isset($orders[$id])) {
                $orders[$id] = [
                    'Data' => $r['data_venda'],
                    'Valor de Venda' => (float)$r['valor_venda'],
                    'Número Nota' => $r['numero_nota'],
                    '_pagamentos' => [],
                ];
            }

            $valor   = (float)($r['pagamento_valor'] ?? 0);
            $forma   = trim((string)($r['pagamento_forma'] ?? ''));
            $origem  = (string)($r['pagamento_origem'] ?? 'sistema');
            $tipo    = (string)($r['pagamento_tipo'] ?? '');
            $pgtData = (string)($r['pagamento_data'] ?? '');

            $pgtDataFormatada = '';
            if ($pgtData !== '' && $pgtData !== '0000-00-00 00:00:00') {
                $dataObj = DateTime::createFromFormat('Y-m-d H:i:s', $pgtData);
                $pgtDataFormatada = $dataObj ? $dataObj->format('d/m/Y') : $pgtData;
            }

            if ($valor > 0 || $forma !== '') {
                $orders[$id]['_pagamentos'][] = sprintf(
                    '[%s/%s] %s: R$ %s%s',
                    strtoupper($origem),
                    strtoupper($tipo),
                    ($forma !== '' ? $forma : 'FORMA N/I'),
                    number_format($valor, 2, ',', '.'),
                    $pgtDataFormatada !== '' ? ' | Data: ' . $pgtDataFormatada : ''
                );
            }
        }

        // 3) Descobre quantas colunas Pagamento N serão necessárias
        $maxPag = 0;
        foreach ($orders as $o) {
            $maxPag = max($maxPag, count($o['_pagamentos']));
        }

        // 4) Monta o dataset final: Data | Valor | Pagamento 1..N | Número Nota
        $relatorio = [];
        foreach ($orders as $o) {
            $line = [
                'Data' => $o['Data'],
                'Valor de Venda' => $o['Valor de Venda'],
            ];

            for ($i = 0; $i < $maxPag; $i++) {
                $line['Pagamento ' . ($i + 1)] = $o['_pagamentos'][$i] ?? '';
            }

            $line['Número Nota'] = $o['Número Nota'];
            $relatorio[] = $line;
        }

        // (Opcional) Totais do período
        $totais = [
            'total_venda' => 0.0,
            'qtd_vendas'  => count($relatorio),
        ];
        foreach ($relatorio as $l) {
            $totais['total_venda'] += (float)($l['Valor de Venda'] ?? 0);
        }

        return view('relatorios/ordens', [
            'titulo'     => 'Relatório de Ordens',
            'dataInicio' => $dataInicio,
            'dataFim'    => $dataFim,
            'relatorio'  => $relatorio,
            'maxPag'     => $maxPag,
            'totais'     => $totais,
        ]);
    }
}

<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrdemModel;

class Relatorios extends BaseController
{
    protected $ordemModel;

    public function __construct()
    {
        $this->ordemModel = new OrdemModel();
    }

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

        // se não vier nada, usa mês atual
        if (empty($dataInicio) || empty($dataFim)) {
            $dataInicio = date('Y-m-01');
            $dataFim    = date('Y-m-t');
        }

        $ordens = $this->ordemModel->relatorioPorPeriodo($dataInicio, $dataFim);
        $totais = $this->ordemModel->totaisPorPeriodo($dataInicio, $dataFim);

        $data = [
            'titulo'     => 'Relatório de Ordens',
            'dataInicio' => $dataInicio,
            'dataFim'    => $dataFim,
            'ordens'     => $ordens,
            'totais'     => $totais,
        ];

        return view('relatorios/ordens', $data);
    }
}

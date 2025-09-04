<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrdemModel;
use App\Models\ClienteModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $range  = (string) ($this->request->getGet('range') ?? '30');     // 7|30|90
        $status = (string) ($this->request->getGet('status') ?? 'todos'); // todos|aberta|fechada

        $ordemModel   = new OrdemModel();
        $clienteModel = new ClienteModel();

        // ---------- Períodos ----------
        $dias       = ctype_digit($range) ? (int) $range : 30;
        $iniPeriodo = date('Y-m-d 00:00:00', strtotime('-' . ($dias - 1) . ' days'));
        $fimPeriodo = date('Y-m-d 23:59:59');

        $iniMes = date('Y-m-01 00:00:00');
        $fimMes = date('Y-m-t 23:59:59');

        // ---------- DB + helper "não deletado" ----------
        $db = \Config\Database::connect();
        $T  = 'ordens';

        $notDeleted = function (\CodeIgniter\Database\BaseBuilder $b, string $t) {
            $b->groupStart()
                ->where("$t.deleted_at IS NULL", null, false)
                ->orWhere("$t.deleted_at", '')
                ->orWhere("$t.deleted_at", '0000-00-00 00:00:00')
                ->groupEnd();
        };

        // ---------- KPI 1: Ordens no período (por data_compra) ----------
        $b1 = $db->table($T)
            ->select('COUNT(*) AS c')
            ->where('data_compra >=', $iniPeriodo)
            ->where('data_compra <=', $fimPeriodo);
        $notDeleted($b1, 'ordens');
        if ($status !== 'todos') {
            $b1->where('status', $status);
        }
        $ordensTotalPeriodo = (int) ($b1->get()->getRow('c') ?? 0);

        // ---------- KPI 2: Faturamento do mês ----------
        $b2 = $db->table($T)
            ->select('SUM(valor_venda) AS fat_sum')
            ->where('data_compra >=', $iniMes)
            ->where('data_compra <=', $fimMes);
        $notDeleted($b2, 'ordens');
        $fatRow = $b2->get()->getRowArray();
        $faturamentoEstimado = $fatRow['fat_sum'] !== null ? (float) $fatRow['fat_sum'] : 0.0;

        // ---------- KPI 3: Valor pago do mês ----------
        $b3 = $db->table($T)
            ->select('SUM(valor_pago) AS pago_sum')
            ->where('data_compra >=', $iniMes)
            ->where('data_compra <=', $fimMes);
        $notDeleted($b3, 'ordens');
        $pagoRow = $b3->get()->getRowArray();
        $valorPagoMes = $pagoRow['pago_sum'] !== null ? (float) $pagoRow['pago_sum'] : 0.0;

        // ---------- Custo da Operação por mês (últimos 6 meses, inclui mês vigente) ----------
        $meses = 6;
        $hojePrimeiroDia = new \DateTime('first day of this month');

        // inicializa meses com zero
        $custoPorMes = []; // ['YYYY-MM' => float]
        for ($i = $meses - 1; $i >= 0; $i--) {
            $dt = (clone $hojePrimeiroDia)->modify("-{$i} months");
            $custoPorMes[$dt->format('Y-m')] = 0.0;
        }

        $inicioJanela = (clone $hojePrimeiroDia)->modify('-' . ($meses - 1) . ' months')->format('Y-m-01 00:00:00');
        $fimJanela    = date('Y-m-t 23:59:59');

        $bCustos = $db->table($T)
            ->select('data_compra, valor_armacao_1, valor_armacao_2, valor_lente_1, valor_lente_2, consulta')
            ->where('data_compra >=', $inicioJanela)
            ->where('data_compra <=', $fimJanela);
        $notDeleted($bCustos, 'ordens');
        $ordensJanela = $bCustos->get()->getResultArray();

        foreach ($ordensJanela as $o) {
            if (empty($o['data_compra'])) continue;
            $mesKey = date('Y-m', strtotime($o['data_compra']));

            // consulta é DECIMAL(10,2) no banco: soma direta
            $soma = (float) ($o['valor_armacao_1'] ?? 0)
                + (float) ($o['valor_armacao_2'] ?? 0)
                + (float) ($o['valor_lente_1']   ?? 0)
                + (float) ($o['valor_lente_2']   ?? 0)
                + (float) ($o['consulta']        ?? 0);

            if (array_key_exists($mesKey, $custoPorMes)) {
                $custoPorMes[$mesKey] += $soma;
            }
        }

        // lista para a view (mês atual primeiro)
        $custo_operacao_meses = [];
        foreach (array_reverse($custoPorMes, true) as $ym => $valor) {
            $dt = \DateTime::createFromFormat('Y-m', $ym);
            $custo_operacao_meses[] = [
                'label' => $dt ? $dt->format('m/Y') : $ym,
                'valor' => (float) $valor,
            ];
        }

        // custo do mês atual
        $custoMesAtualKey = date('Y-m');
        $custoMesAtual    = $custoPorMes[$custoMesAtualKey] ?? 0.0;

        // ---------- KPI 4: Imposto (7% sobre faturamento) ----------
        $valorImposto = $faturamentoEstimado * 0.07;

        // ---------- KPI 5: Lucro ----------
        $valorLucro = $valorPagoMes * 0.9;

        $valorLucro = $valorLucro - $valorImposto - $custoMesAtual;

        // ---------- Últimas ordens ----------
        $bUlt = $db->table($T)
            ->select('ordens.id, ordens.status, ordens.data_compra, c.nome AS cliente')
            ->join('clientes c', 'c.id = ordens.cliente_id', 'left')
            ->orderBy('ordens.data_compra', 'DESC')
            ->limit(8);

        $notDeleted($bUlt, 'ordens'); // sempre
        $notDeleted($bUlt, 'c');      // opcional: só se quiser excluir clientes deletados também

        $ultimasOrdens = $bUlt->get()->getResultArray();

        // ---------- Montagem final ----------
        $data = [
            'title' => 'Dashboard',
            'filtros' => [
                'range'  => $range,
                'status' => $status,
            ],
            'stats' => [
                'periodo_label'        => $dias . ' dias',
                'ordens_total'         => $ordensTotalPeriodo,
                'faturamento_estimado' => $faturamentoEstimado,
                'valor_pago'           => $valorPagoMes,
                'valor_imposto'        => $valorImposto,
                'valor_lucro'          => $valorLucro,
            ],
            'role'                 => $role = role_level(),
            'canSeeAllFin'         => $role >= 1,
            'canSeeLimited'        => $role === 0,
            'custo_operacao_meses' => $custo_operacao_meses,
            'ultimas_ordens'       => $ultimasOrdens,
            'estoque_baixo'        => [],
            'relatorios_recentes'  => [],
        ];

        return view('dashboard/index', $data);
    }
}

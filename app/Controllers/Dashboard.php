<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrdemModel;
use App\Models\ClienteModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $range    = (string) ($this->request->getGet('range') ?? '30');      // 7|30|90
        $status   = (string) ($this->request->getGet('status') ?? 'todos');  // todos|aberta|fechada
        $kpiScope = (string) ($this->request->getGet('kpi')   ?? 'dia');     // dia|mes

        $db = \Config\Database::connect();
        $T  = 'ordens';

        $dias       = ctype_digit($range) ? (int) $range : 30;
        $iniPeriodo = date('Y-m-d 00:00:00', strtotime('-' . ($dias - 1) . ' days'));
        $fimPeriodo = date('Y-m-d 23:59:59');

        $iniMes = date('Y-m-01 00:00:00');
        $fimMes = date('Y-m-t 23:59:59');

        $ontem   = (new \DateTime('yesterday'))->format('Y-m-d');
        $iniOntem = $ontem . ' 00:00:00';
        $fimOntem = $ontem . ' 23:59:59';

        $notDeleted = function (\CodeIgniter\Database\BaseBuilder $b, string $t) {
            $b->groupStart()
                ->where("$t.deleted_at IS NULL", null, false)
                ->orWhere("$t.deleted_at", '')
                ->orWhere("$t.deleted_at", '0000-00-00 00:00:00')
                ->groupEnd();
        };

        // ---------- Funções auxiliares ----------
        $sumCols = function (array $row, array $cols): float {
            $s = 0.0;
            foreach ($cols as $c) {
                $s += (float)($row[$c] ?? 0);
            }
            return $s;
        };
        $colsCusto = ['valor_armacao_1', 'valor_armacao_2', 'valor_lente_1', 'valor_lente_2', 'consulta'];

        $sumBetween = function (string $col, string $ini, string $fim) use ($db, $T, $notDeleted) {
            $b = $db->table($T)->select("SUM($col) AS s")
                ->where('data_compra >=', $ini)->where('data_compra <=', $fim);
            $notDeleted($b, 'ordens');
            $row = $b->get()->getRowArray();
            return $row && $row['s'] !== null ? (float)$row['s'] : 0.0;
        };
        $countBetween = function (string $ini, string $fim, ?string $status) use ($db, $T, $notDeleted) {
            $b = $db->table($T)->select('COUNT(*) AS c')
                ->where('data_compra >=', $ini)->where('data_compra <=', $fim);
            if ($status && $status !== 'todos') $b->where('status', $status);
            $notDeleted($b, 'ordens');
            return (int)($b->get()->getRow('c') ?? 0);
        };
        $custoEntre = function (string $ini, string $fim) use ($db, $T, $notDeleted, $colsCusto, $sumCols) {
            $b = $db->table($T)->select(implode(',', array_merge(['data_compra'], $colsCusto)))
                ->where('data_compra >=', $ini)->where('data_compra <=', $fim);
            $notDeleted($b, 'ordens');
            $total = 0.0;
            foreach ($b->get()->getResultArray() as $o) {
                $total += $sumCols($o, $colsCusto);
            }
            return $total;
        };

        // ---------- KPI scope atual ----------
        if ($kpiScope === 'dia') {
            // KPIs baseados em ONTEM para manter "custo do dia anterior"
            $labelPeriodo = 'Ontem';
            $ordensTotal  = $countBetween($iniOntem, $fimOntem, $status);
            $fat          = $sumBetween('valor_venda', $iniOntem, $fimOntem);
            $pago         = $sumBetween('valor_pago',  $iniOntem, $fimOntem);
        } else {
            $labelPeriodo = 'Mês atual';
            $ordensTotal  = $countBetween($iniPeriodo, $fimPeriodo, $status);
            $fat          = $sumBetween('valor_venda', $iniMes, $fimMes);
            $pago         = $sumBetween('valor_pago',  $iniMes, $fimMes);
        }

        // custo do DIA ANTERIOR (sempre) para o cálculo de lucro exibido nos KPIs
        $custoDiaAnterior = $custoEntre($iniOntem, $fimOntem);

        $imposto = $fat * 0.07;
        $lucro   = ($pago * 0.9) - $imposto - $custoDiaAnterior;

        // ---------- Lista colapsável: últimos 14 dias ----------
        $diasLista = [];
        for ($i = 0; $i < 14; $i++) {
            $d = (new \DateTime("today"))->modify("-{$i} days")->format('Y-m-d');
            $ini = $d . ' 00:00:00';
            $fim = $d . ' 23:59:59';

            $ord = $countBetween($ini, $fim, $status);
            $fatD = $sumBetween('valor_venda', $ini, $fim);
            $pagD = $sumBetween('valor_pago',  $ini, $fim);
            $impD = $fatD * 0.07;
            $cusD = $custoEntre($ini, $fim);
            $lucD = ($pagD * 0.9) - $impD - $cusD;

            $diasLista[] = [
                'data_iso'   => $d,
                'label'      => date('d/m', strtotime($d)),
                'ordens'     => $ord,
                'faturamento' => $fatD,
                'valor_pago' => $pagD,
                'imposto'    => $impD,
                'custo'      => $cusD,
                'lucro'      => $lucD,
            ];
        }

        // ---------- Últimas ordens ----------
        $bUlt = $db->table($T)
            ->select('ordens.id, ordens.status, ordens.data_compra, c.nome AS cliente')
            ->join('clientes c', 'c.id = ordens.cliente_id', 'left')
            ->orderBy('ordens.data_compra', 'DESC')
            ->limit(8);
        $notDeleted($bUlt, 'ordens');
        $notDeleted($bUlt, 'c');
        $ultimasOrdens = $bUlt->get()->getResultArray();

        $lucroClass = $lucro >= 0 ? 'text-success' : 'text-danger';

        $data = [
            'title' => 'Dashboard',
            'filtros' => ['range' => $range, 'status' => $status],
            'kpi_scope' => $kpiScope,
            'stats' => [
                'periodo_label'        => $labelPeriodo,
                'ordens_total'         => $ordensTotal,
                'faturamento_estimado' => $fat,
                'valor_pago'           => $pago,
                'valor_imposto'        => $imposto,
                'valor_lucro'          => $lucro,
                'custo_dia_anterior'   => $custoDiaAnterior,
                'lucro_class'          => $lucroClass,
            ],
            'role'                 => $role = role_level(),
            'canSeeAllFin'         => $role >= 1,
            'canSeeLimited'        => $role === 0,
            'dias_ultimos'         => $diasLista,   // NOVO: para accordion
            'ultimas_ordens'       => $ultimasOrdens,
            'relatorios_recentes'  => [],
        ];

        return view('dashboard/index', $data);
    }
}

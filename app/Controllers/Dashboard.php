<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        $range     = (string) ($this->request->getGet('range') ?? '30');     // 7|30|90 (pode usar futuramente)
        $status    = (string) ($this->request->getGet('status') ?? 'todos'); // todos|aberta|fechada
        $kpiScope  = (string) ($this->request->getGet('kpi') ?? 'dia');      // dia|mes
        $mesFiltro = (string) ($this->request->getGet('mes') ?? '');         // YYYY-MM

        $db = \Config\Database::connect();

        // ---------- helpers ----------
        $notDeleted = function (\CodeIgniter\Database\BaseBuilder $b, string $alias) {
            $b->groupStart()
                ->where("$alias.deleted_at IS NULL", null, false)
                ->orWhere("$alias.deleted_at", '')
                ->orWhere("$alias.deleted_at", '0000-00-00 00:00:00')
                ->groupEnd();
        };

        $applyStatus = function (\CodeIgniter\Database\BaseBuilder $b, string $alias) use ($status) {
            if (!empty($status) && $status !== 'todos') {
                $b->where("$alias.status", $status);
            }
        };

        // ---------- períodos ----------
        $ontemDate = (new \DateTime('yesterday'))->format('Y-m-d');
        $iniOntemDt = $ontemDate . ' 00:00:00';
        $fimOntemDt = $ontemDate . ' 23:59:59';

        if ($kpiScope === 'dia') {
            $labelPeriodo = 'Ontem';
            $iniOrdDate = $ontemDate;
            $fimOrdDate = $ontemDate;

            $iniPayDt = $iniOntemDt;
            $fimPayDt = $fimOntemDt;
        } else {
            // mês selecionado ou mês atual
            if (!empty($mesFiltro)) {
                $iniOrdDate = date('Y-m-01', strtotime($mesFiltro . '-01'));
                $fimOrdDate = date('Y-m-t',  strtotime($mesFiltro . '-01'));
                $labelPeriodo = ucfirst(strftime('%B de %Y', strtotime($mesFiltro . '-01')));
            } else {
                $iniOrdDate = date('Y-m-01');
                $fimOrdDate = date('Y-m-t');
                $labelPeriodo = 'Mês atual';
            }

            $iniPayDt = $iniOrdDate . ' 00:00:00';
            $fimPayDt = $fimOrdDate . ' 23:59:59';
        }

        // ---------- KPIs (ORDENS / VENDAS) ----------
        $bOrd = $db->table('ordens o')->select("
            COUNT(*) AS ordens_total,
            COALESCE(SUM(o.valor_venda),0) AS faturamento,
            COALESCE(SUM(o.consulta),0) AS consultas,
            COALESCE(SUM(
                COALESCE(o.valor_armacao_1,0)
              + COALESCE(o.valor_armacao_2,0)
              + COALESCE(o.valor_lente_1,0)
              + COALESCE(o.valor_lente_2,0)
            ),0) AS custo_itens
        ", false);

        $bOrd->where('o.data_compra >=', $iniOrdDate)->where('o.data_compra <=', $fimOrdDate);
        $applyStatus($bOrd, 'o');
        $notDeleted($bOrd, 'o');

        $rowOrd = $bOrd->get()->getRowArray() ?: [];

        $ordensTotal = (int)($rowOrd['ordens_total'] ?? 0);
        $fat         = (float)($rowOrd['faturamento'] ?? 0);
        $consultas   = (float)($rowOrd['consultas'] ?? 0);
        $custoItens  = (float)($rowOrd['custo_itens'] ?? 0);

        // ---------- KPIs (PAGAMENTOS / CAIXA) ----------
        $bPay = $db->table('ordens_pagamento p')
            ->select("COALESCE(SUM(p.valor),0) AS recebido", false)
            ->join('ordens o', 'o.id = p.ordem_id', 'inner');

        $bPay->where('p.status', 'confirmado');
        $bPay->where('p.data_pagamento >=', $iniPayDt)->where('p.data_pagamento <=', $fimPayDt);

        $applyStatus($bPay, 'o');
        $notDeleted($bPay, 'p');
        $notDeleted($bPay, 'o');

        $rowPay = $bPay->get()->getRowArray() ?: [];
        $recebido = (float)($rowPay['recebido'] ?? 0);

        // ---------- SALDO EM ABERTO (ordens do período) ----------
        // subquery com total pago por ordem (confirmado)
        $subPago = $db->table('ordens_pagamento')
            ->select('ordem_id, COALESCE(SUM(valor),0) AS total_pago', false)
            ->where('status', 'confirmado')
            ->groupStart()
            ->where("deleted_at IS NULL", null, false)
            ->orWhere("deleted_at", '')
            ->orWhere("deleted_at", '0000-00-00 00:00:00')
            ->groupEnd()
            ->groupBy('ordem_id');

        $subSql = $subPago->getCompiledSelect();

        $bSaldo = $db->table('ordens o')
            ->select("COALESCE(SUM(GREATEST(o.valor_venda - COALESCE(pg.total_pago,0), 0)),0) AS saldo_aberto", false)
            ->join("($subSql) pg", "pg.ordem_id = o.id", 'left', false);

        $bSaldo->where('o.data_compra >=', $iniOrdDate)->where('o.data_compra <=', $fimOrdDate);
        $applyStatus($bSaldo, 'o');
        $notDeleted($bSaldo, 'o');

        $rowSaldo = $bSaldo->get()->getRowArray() ?: [];
        $saldoAberto = (float)($rowSaldo['saldo_aberto'] ?? 0);

        // ---------- IMPOSTO / LUCRO ----------
        $imposto = $fat * 0.07;

        // Mantive a lógica “parecida” com a antiga (que usava valor_pago)
        // Só que agora "recebido" vem da tabela de pagamentos no período.
        $lucro = ($recebido * 0.9) - $imposto - $consultas - $custoItens;
        $lucroClass = $lucro >= 0 ? 'text-success' : 'text-danger';

        // ---------- CUSTO DO DIA ANTERIOR (sempre ontem, para o rodapé) ----------
        $bCustoOntem = $db->table('ordens o')->select("
            COALESCE(SUM(
                COALESCE(o.valor_armacao_1,0)
              + COALESCE(o.valor_armacao_2,0)
              + COALESCE(o.valor_lente_1,0)
              + COALESCE(o.valor_lente_2,0)
            ),0) AS custo_ontem
        ", false);

        $bCustoOntem->where('o.data_compra', $ontemDate);
        $applyStatus($bCustoOntem, 'o');
        $notDeleted($bCustoOntem, 'o');
        $custoOntem = (float)(($bCustoOntem->get()->getRowArray()['custo_ontem'] ?? 0));

        // ---------- Últimos 14 dias (agregado, sem 300 queries) ----------
        $fim14Date = date('Y-m-d');
        $ini14Date = date('Y-m-d', strtotime('-13 days'));

        $ini14PayDt = $ini14Date . ' 00:00:00';
        $fim14PayDt = $fim14Date . ' 23:59:59';

        // ordens agrupadas por data_compra (DATE)
        $bOrd14 = $db->table('ordens o')->select("
            o.data_compra AS data,
            COUNT(*) AS ordens,
            COALESCE(SUM(o.valor_venda),0) AS faturamento,
            COALESCE(SUM(o.consulta),0) AS consultas,
            COALESCE(SUM(
                COALESCE(o.valor_armacao_1,0)
              + COALESCE(o.valor_armacao_2,0)
              + COALESCE(o.valor_lente_1,0)
              + COALESCE(o.valor_lente_2,0)
            ),0) AS custo
        ", false);

        $bOrd14->where('o.data_compra >=', $ini14Date)->where('o.data_compra <=', $fim14Date);
        $applyStatus($bOrd14, 'o');
        $notDeleted($bOrd14, 'o');
        $bOrd14->groupBy('o.data_compra');

        $mapOrd = [];
        foreach ($bOrd14->get()->getResultArray() as $r) {
            $mapOrd[$r['data']] = $r;
        }

        // pagamentos agrupados por DATE(data_pagamento)
        $bPay14 = $db->table('ordens_pagamento p')->select("
            DATE(p.data_pagamento) AS data,
            COALESCE(SUM(p.valor),0) AS recebido
        ", false)->join('ordens o', 'o.id = p.ordem_id', 'inner');

        $bPay14->where('p.status', 'confirmado');
        $bPay14->where('p.data_pagamento >=', $ini14PayDt)->where('p.data_pagamento <=', $fim14PayDt);
        $applyStatus($bPay14, 'o');
        $notDeleted($bPay14, 'p');
        $notDeleted($bPay14, 'o');
        $bPay14->groupBy('DATE(p.data_pagamento)');

        $mapPay = [];
        foreach ($bPay14->get()->getResultArray() as $r) {
            $mapPay[$r['data']] = $r;
        }

        // monta lista final
        $diasLista = [];
        for ($i = 0; $i < 14; $i++) {
            $d = (new \DateTime("today"))->modify("-{$i} days")->format('Y-m-d');

            $ordRow = $mapOrd[$d] ?? null;
            $payRow = $mapPay[$d] ?? null;

            $ord  = (int)($ordRow['ordens'] ?? 0);
            $fatD = (float)($ordRow['faturamento'] ?? 0);
            $conD = (float)($ordRow['consultas'] ?? 0);
            $cusD = (float)($ordRow['custo'] ?? 0);

            $recD = (float)($payRow['recebido'] ?? 0);

            $impD = $fatD * 0.07;
            $lucD = ($recD * 0.9) - $impD - $conD - $cusD;

            $diasLista[] = [
                'data_iso'    => $d,
                'label'       => date('d/m', strtotime($d)),
                'ordens'      => $ord,
                'faturamento' => $fatD,
                'valor_pago'  => $recD, // mantém chave antiga p/ view (agora é "recebido")
                'imposto'     => $impD,
                'consultas'   => $conD,
                'custo'       => $cusD,
                'lucro'       => $lucD,
            ];
        }

        // ---------- Últimas ordens ----------
        $bUlt = $db->table('ordens o')
            ->select('o.id, o.status, o.data_compra, c.nome AS cliente')
            ->join('clientes c', 'c.id = o.cliente_id', 'left')
            ->orderBy('o.data_compra', 'DESC')
            ->limit(8);

        $notDeleted($bUlt, 'o');
        $notDeleted($bUlt, 'c');
        $ultimasOrdens = $bUlt->get()->getResultArray();

        $role = role_level();

        $data = [
            'title'   => 'Dashboard',
            'filtros' => [
                'range'  => $range,
                'status' => $status,
                'mes'    => $mesFiltro,
            ],
            'kpi_scope' => $kpiScope,
            'stats' => [
                'periodo_label'        => $labelPeriodo,
                'ordens_total'         => $ordensTotal,
                'faturamento_estimado' => $fat,
                'valor_pago'           => $recebido, // agora é recebido (caixa)
                'saldo_aberto'         => $saldoAberto,
                'valor_imposto'        => $imposto,
                'valor_consultas'      => $consultas,
                'valor_custo_itens'    => $custoItens,
                'valor_lucro'          => $lucro,
                'lucro_class'          => $lucroClass,
                'custo_dia_anterior'   => $custoOntem,
            ],
            'role'                => $role,
            'canSeeAllFin'        => $role >= 1,
            'canSeeLimited'       => $role === 0,
            'dias_ultimos'        => $diasLista,
            'ultimas_ordens'      => $ultimasOrdens,
            'relatorios_recentes' => [],
        ];

        return view('dashboard/index', $data);
    }
}

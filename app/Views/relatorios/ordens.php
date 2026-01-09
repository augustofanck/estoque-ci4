<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="main-content">

    <h1><?= esc($titulo) ?></h1>

    <p>
        Período:
        <strong><?= date('d/m/Y', strtotime($dataInicio)) ?></strong>
        até
        <strong><?= date('d/m/Y', strtotime($dataFim)) ?></strong>
    </p>

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="<?= base_url('relatorios') ?>" class="btn btn-secondary">Voltar para filtros</a>

        <?php if (!empty($ordens)): ?>
            <button type="button" id="btnPdf" class="btn btn-outline-danger">
                Gerar PDF
            </button>
        <?php endif; ?>
    </div>

    <?php if (! empty($ordens)): ?>

        <div class="table-responsive">
            <table id="tblRelatorioOrdens" class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Dia de entrada</th>
                        <th>Valor venda</th>
                        <th>Entrada (R$)</th>
                        <th>Total pago</th>
                        <th>Saldo</th>
                        <th># Pg</th>
                        <th>Último pgto</th>
                        <th>Dia nota</th>
                        <th>Nº nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordens as $ordem): ?>
                        <?php
                        $valorVenda = (float)($ordem['valor_venda'] ?? 0);
                        $totalPago  = (float)($ordem['total_pago'] ?? 0);
                        $entrada    = (float)($ordem['valor_entrada'] ?? 0);
                        $saldo      = (float)($ordem['saldo'] ?? 0);
                        if ($saldo < 0) $saldo = 0;

                        $quitado = ($valorVenda > 0 && $saldo <= 0.0001);

                        $badgeTotalPago =
                            $quitado ? 'bg-success'
                            : ($totalPago > 0 ? 'bg-warning text-dark' : 'bg-secondary');

                        $badgeSaldo = ($saldo > 0.0001) ? 'bg-danger' : 'bg-success';

                        $ultimo = '-';
                        if (!empty($ordem['ultimo_pagamento'])) {
                            $ts = strtotime($ordem['ultimo_pagamento']);
                            $ultimo = $ts ? date('d/m/Y H:i', $ts) : (string)$ordem['ultimo_pagamento'];
                        }
                        ?>
                        <tr>
                            <td><?= esc($ordem['id']) ?></td>
                            <td><?= esc($ordem['cliente_nome'] ?? '-') ?></td>
                            <td><?= !empty($ordem['data_compra']) ? date('d/m/Y', strtotime($ordem['data_compra'])) : '-' ?></td>
                            <td>R$ <?= number_format($valorVenda, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($entrada, 2, ',', '.') ?></td>

                            <td>
                                <span class="badge <?= $badgeTotalPago ?>">
                                    R$ <?= number_format($totalPago, 2, ',', '.') ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?= $badgeSaldo ?>">
                                    R$ <?= number_format($saldo, 2, ',', '.') ?>
                                </span>
                            </td>

                            <td><?= (int)($ordem['qtd_pagamentos'] ?? 0) ?></td>
                            <td><?= esc($ultimo) ?></td>
                            <td><?= !empty($ordem['dia_nota']) ? date('d/m/Y', strtotime($ordem['dia_nota'])) : '-' ?></td>
                            <td><?= esc($ordem['numero_nota'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php if (! empty($totais)): ?>
                        <tr>
                            <th colspan="3">Totais no período</th>
                            <th>R$ <?= number_format((float)$totais['total_venda'], 2, ',', '.') ?></th>
                            <th>R$ <?= number_format((float)$totais['total_entrada'], 2, ',', '.') ?></th>
                            <th>R$ <?= number_format((float)$totais['total_pago'], 2, ',', '.') ?></th>
                            <th>R$ <?= number_format((float)$totais['total_saldo'], 2, ',', '.') ?></th>
                            <th><?= (int)$totais['qtd_pagamentos'] ?></th>
                            <th colspan="3"></th>
                        </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>

    <?php else: ?>

        <div class="alert alert-info">
            Nenhuma ordem encontrada para o período informado.
        </div>

    <?php endif; ?>

</div>

<!-- CDN: jsPDF + AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/3.0.3/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/5.0.2/jspdf.plugin.autotable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('btnPdf');
        const table = document.getElementById('tblRelatorioOrdens');
        if (!btn || !table) return;

        btn.addEventListener('click', function() {
            // jsPDF UMD -> window.jspdf.jsPDF
            const jsPDF = window.jspdf && window.jspdf.jsPDF;
            if (!jsPDF) {
                alert('jsPDF não carregou. Verifique bloqueadores ou CSP.');
                return;
            }

            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'pt',
                format: 'a4'
            });

            const titulo = <?= json_encode($titulo ?? 'Relatório') ?>;
            const periodo = "Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> até <?= date('d/m/Y', strtotime($dataFim)) ?>";

            doc.setFontSize(14);
            doc.text(titulo, 40, 35);
            doc.setFontSize(10);
            doc.text(periodo, 40, 55);

            // Exporta a tabela HTML (thead/tbody/tfoot)
            doc.autoTable({
                html: '#tblRelatorioOrdens',
                startY: 70,
                styles: {
                    fontSize: 8,
                    cellPadding: 3
                },
                headStyles: {
                    fillColor: [240, 240, 240]
                },
                margin: {
                    left: 40,
                    right: 40
                },
                tableWidth: 'auto'
            });

            const nomeArquivo = 'relatorio-ordens-<?= $dataInicio ?>_a_<?= $dataFim ?>.pdf';
            doc.save(nomeArquivo);
        });
    });
</script>

<?= $this->endSection() ?>
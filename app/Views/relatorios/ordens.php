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

        <?php if (!empty($relatorio)): ?>
            <button type="button" id="btnPdf" class="btn btn-outline-danger">
                Gerar PDF
            </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($relatorio)): ?>

        <div class="table-responsive">
            <table id="tblRelatorioOrdens" class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Valor de Venda</th>

                        <?php
                        $maxPag = (int)($maxPag ?? 0);
                        for ($i = 1; $i <= $maxPag; $i++): ?>
                            <th><?= esc('Pagamento ' . $i) ?></th>
                        <?php endfor; ?>

                        <th>Nº nota</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($relatorio as $row): ?>
                        <?php
                        $dataVenda = $row['Data'] ?? null;
                        $valorVenda = (float)($row['Valor de Venda'] ?? 0);
                        $numeroNota = $row['Número Nota'] ?? '-';
                        ?>
                        <tr>
                            <td>
                                <?= !empty($dataVenda) ? date('d/m/Y', strtotime($dataVenda)) : '-' ?>
                            </td>

                            <td>
                                R$ <?= number_format($valorVenda, 2, ',', '.') ?>
                            </td>

                            <?php for ($i = 1; $i <= $maxPag; $i++): ?>
                                <?php $col = 'Pagamento ' . $i; ?>
                                <td style="white-space: normal;">
                                    <?= esc($row[$col] ?? '') ?>
                                </td>
                            <?php endfor; ?>

                            <td><?= esc($numeroNota) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <tfoot>
                    <?php if (!empty($totais)): ?>
                        <tr>
                            <th>Totais no período</th>
                            <th>R$ <?= number_format((float)($totais['total_venda'] ?? 0), 2, ',', '.') ?></th>

                            <?php if ($maxPag > 0): ?>
                                <th colspan="<?= $maxPag ?>"></th>
                            <?php endif; ?>

                            <th>
                                Qtd. vendas: <?= (int)($totais['qtd_vendas'] ?? count($relatorio)) ?>
                            </th>
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

            const nomeArquivo = 'relatorio-vendas-<?= $dataInicio ?>_a_<?= $dataFim ?>.pdf';
            doc.save(nomeArquivo);
        });
    });
</script>

<?= $this->endSection() ?>
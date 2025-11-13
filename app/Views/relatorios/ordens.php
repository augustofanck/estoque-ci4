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

    <div class="mb-3">
        <a href="<?= base_url('relatorios') ?>" class="btn btn-secondary">Voltar para filtros</a>
    </div>

    <?php if (! empty($ordens)): ?>

        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Dia de entrada</th>
                    <th>Valor de venda</th>
                    <th>Valor de entrada</th>
                    <th>Dia da nota</th>
                    <th>Número da nota</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ordens as $ordem): ?>
                    <tr>
                        <td><?= esc($ordem['id']) ?></td>
                        <td><?= esc($ordem['nome_cliente']) ?></td>
                        <td><?= $ordem['dia_entrada'] ? date('d/m/Y', strtotime($ordem['dia_entrada'])) : '-' ?></td>
                        <td>R$ <?= number_format((float)$ordem['valor_venda'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format((float)$ordem['valor_entrada'], 2, ',', '.') ?></td>
                        <td><?= $ordem['dia_nota'] ? date('d/m/Y', strtotime($ordem['dia_nota'])) : '-' ?></td>
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
                        <th colspan="2"></th>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>

    <?php else: ?>

        <div class="alert alert-info">
            Nenhuma ordem encontrada para o período informado.
        </div>

    <?php endif; ?>

</div>

<?= $this->endSection() ?>
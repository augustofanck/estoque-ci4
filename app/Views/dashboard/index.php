<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Dashboard</h4>
    <div class="d-flex gap-2">
        <a href="<?= site_url('ordens/create') ?>" class="btn btn-primary">Nova Ordem</a>
        <a href="<?= site_url('clientes/create') ?>" class="btn btn-outline-secondary">Novo Cliente</a>
    </div>
</div>

<?php
$lucro = (float)($stats['valor_lucro'] ?? 0);
$lucroClass = $lucro >= 0 ? 'text-success' : 'text-danger';
?>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5 g-3 mb-3">

    <?php if (!empty($canSeeLimited) && $canSeeLimited): ?>

        <!-- VENDEDOR: mostra apenas Faturamento e Valor Recebido -->
        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Faturamento (estimado)</div>
                    <div class="display-6 fw-semibold">
                        R$ <?= number_format((float)($stats['faturamento_estimado'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted">Mês atual</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Valor Recebido</div>
                    <div class="display-6 fw-semibold">
                        R$ <?= number_format((float)($stats['valor_pago'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted">Mês atual</div>
                </div>
            </div>
        </div>

    <?php elseif (!empty($canSeeAllFin) && $canSeeAllFin): ?>

        <!-- ADMIN/GERENTE: mostra todos os cards -->
        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ordens (total)</div>
                    <div class="display-6 fw-semibold"><?= esc($stats['ordens_total'] ?? 0) ?></div>
                    <div class="small text-muted">Período atual</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Faturamento (estimado)</div>
                    <div class="display-6 fw-semibold">
                        R$ <?= number_format((float)($stats['faturamento_estimado'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted">Mês atual</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Valor Recebido</div>
                    <div class="display-6 fw-semibold">
                        R$ <?= number_format((float)($stats['valor_pago'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted">Mês atual</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Imposto (7%)</div>
                    <div class="display-6 fw-semibold">
                        R$ <?= number_format((float)($stats['valor_imposto'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted">Sobre faturamento</div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Lucro</div>
                    <div class="display-6 fw-semibold <?= $lucroClass ?>">
                        R$ <?= number_format($lucro, 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted">Total do mês vigente</div>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>


<!-- Custo da Operação por mês (lista) -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Custo da Operação por mês</strong>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($custo_operacao_meses)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($custo_operacao_meses as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><?= esc($m['label']) ?></span>
                        <span class="fw-semibold">R$ <?= number_format((float)$m['valor'], 2, ',', '.') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="p-4 text-center text-muted">Sem dados para o período.</div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <!-- Últimas ordens -->
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Últimas ordens</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php if (!empty($ultimas_ordens)): ?>
                                <?php foreach ($ultimas_ordens as $o): ?>
                                    <tr>
                                        <td><?= esc($o['id']) ?></td>
                                        <td><?= esc($o['cliente'] ?? '—') ?></td>
                                        <td><?= esc(date('d/m/Y', strtotime($o['data_compra'] ?? 'now'))) ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($o['status'] ?? '') === 'aberta' ? 'warning' : 'success' ?>">
                                                <?= esc(ucfirst($o['status'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= site_url('ordens/' . $o['id']) . '/edit' ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Sem registros.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="<?= site_url('ordens') ?>" class="btn btn-sm btn-light">Ver mais</a>
            </div>
        </div>
    </div>

    <!-- Relatórios recentes -->
    <div class="col-12 col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><strong>Relatórios recentes</strong></div>
            <div class="card-body">
                <?php if (!empty($relatorios_recentes)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($relatorios_recentes as $r): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <a href="<?= esc($r['url']) ?>" class="fw-semibold"><?= esc($r['titulo']) ?></a>
                                    <div class="small text-muted"><?= esc(date('d/m/Y', strtotime($r['data']))) ?></div>
                                </div>
                                <span class="badge bg-secondary"><?= esc($r['tipo'] ?? 'PDF') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-muted">Nenhum relatório nos últimos dias.</div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="<?= site_url('relatorios') ?>" class="btn btn-sm btn-light">Ver todos</a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
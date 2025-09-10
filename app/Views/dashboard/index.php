<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Dashboard</h4>
    <?php $kpiScope = $kpi_scope ?? 'dia';
    $toggleTo = $kpiScope === 'dia' ? 'mes' : 'dia';
    $toggleLabel = $kpiScope === 'dia' ? 'Ver valores do mês' : 'Ver valores do dia';
    $qs = $_GET;
    $qs['kpi'] = $toggleTo;
    $toggleUrl = current_url() . '?' . http_build_query($qs);
    ?>
    <div class="d-flex gap-2">
        <a href="<?= site_url('ordens/create') ?>" class="btn btn-primary">Nova Ordem</a>
        <a href="<?= site_url('clientes/create') ?>" class="btn btn-outline-secondary">Novo Cliente</a>
    </div>
    <a href="<?= esc($toggleUrl) ?>" class="btn btn-sm btn-outline-secondary"><?= esc($toggleLabel) ?></a>
</div>

<?php
$lucro = (float)($stats['valor_lucro'] ?? 0);
$lucroClass = $stats['lucro_class'] ?? ($lucro >= 0 ? 'text-success' : 'text-danger');
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
                    <div class="small text-muted"><?= esc($stats['periodo_label'] ?? '') ?></div>
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
                    <div class="small text-muted"><?= esc($stats['periodo_label'] ?? '') ?></div>
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
                    <div class="small text-muted"><?= esc($stats['periodo_label'] ?? '') ?></div>
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

        <div class="col-12">
            <div class="text-end small text-muted">
                Custo do dia anterior: R$ <?= number_format((float)($stats['custo_dia_anterior'] ?? 0), 2, ',', '.') ?>
            </div>
        </div>

    <?php endif; ?>

</div>

<!-- Últimos 14 dias (lista colapsável com KPIs) -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Últimos 14 dias</strong>
        <span class="small text-muted">Toque para detalhar</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($dias_ultimos)): ?>
            <div class="accordion" id="accUltimosDias">
                <?php foreach ($dias_ultimos as $i => $d):
                    $itemId = 'dia' . $i;
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="h-<?= $itemId ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#c-<?= $itemId ?>" aria-expanded="false"
                                aria-controls="c-<?= $itemId ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <span class="fw-semibold"><?= esc($d['label']) ?></span>
                                    <span class="small text-muted">
                                        Lucro: R$ <?= number_format((float)$d['lucro'], 2, ',', '.') ?>
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="c-<?= $itemId ?>" class="accordion-collapse collapse" aria-labelledby="h-<?= $itemId ?>"
                            data-bs-parent="#accUltimosDias">
                            <div class="accordion-body">
                                <div class="row row-cols-1 row-cols-md-3 row-cols-xxl-5 g-3">
                                    <div class="col">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Ordens (total)</div>
                                            <div class="fs-4 fw-semibold"><?= esc($d['ordens']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Faturamento (estimado)</div>
                                            <div class="fs-4 fw-semibold">
                                                R$ <?= number_format((float)$d['faturamento'], 2, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Valor Recebido</div>
                                            <div class="fs-4 fw-semibold">
                                                R$ <?= number_format((float)$d['valor_pago'], 2, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Imposto (7%)</div>
                                            <div class="fs-4 fw-semibold">
                                                R$ <?= number_format((float)$d['imposto'], 2, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Custo do dia</div>
                                            <div class="fs-4 fw-semibold">
                                                R$ <?= number_format((float)$d['custo'], 2, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Lucro</div>
                                            <?php $lc = ($d['lucro'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>
                                            <div class="fs-4 fw-semibold <?= $lc ?>">
                                                R$ <?= number_format((float)$d['lucro'], 2, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- row -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">Sem dados no período.</div>
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
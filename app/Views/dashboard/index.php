<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Utilitário para traduzir meses
function mesLabel($anoMes)
{
    $ts = strtotime($anoMes . '-01');
    return strftime('%B de %Y', $ts);
}
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'Portuguese_Brazil');
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="mb-0 fw-bold">📊 Dashboard</h4>

    <?php
    $kpiScope    = $kpi_scope ?? 'dia';
    $toggleTo    = $kpiScope === 'dia' ? 'mes' : 'dia';
    $toggleLabel = $kpiScope === 'dia' ? 'Ver valores do mês' : 'Ver valores do dia';
    $qs          = $_GET;
    $qs['kpi']   = $toggleTo;
    $toggleUrl   = current_url() . '?' . http_build_query($qs);
    $mesFiltro   = $filtros['mes'] ?? '';
    ?>

    <div class="d-flex flex-wrap gap-2 align-items-center">
        <!-- Select de meses -->
        <form method="get" class="d-flex gap-2 align-items-center">
            <select name="mes" id="mes" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                <option value="">-- Mês atual --</option>
                <?php for ($i = 0; $i < 12; $i++):
                    $mesOpt  = date('Y-m', strtotime("-$i months"));
                ?>
                    <option value="<?= $mesOpt ?>" <?= ($mesOpt === $mesFiltro) ? 'selected' : '' ?>>
                        <?= ucfirst(mesLabel($mesOpt)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <?php if (!empty($mesFiltro)): ?>
                <a href="<?= site_url('') ?>" class="btn btn-sm btn-outline-danger">
                    Limpar filtro
                </a>
            <?php endif; ?>
        </form>

        <!-- Botões rápidos -->
        <a href="<?= site_url('ordens/create') ?>" class="btn btn-sm btn-primary shadow-sm">+ Nova Ordem</a>
        <a href="<?= site_url('clientes/create') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">+ Novo Cliente</a>
        <a href="<?= esc($toggleUrl) ?>" class="btn btn-sm btn-outline-dark"><?= esc($toggleLabel) ?></a>
    </div>
</div>

<?php
$lucro      = (float)($stats['valor_lucro'] ?? 0);
$lucroClass = $stats['lucro_class'] ?? ($lucro >= 0 ? 'text-success' : 'text-danger');
$periodoLbl = $stats['periodo_label'] ?? ($mesFiltro ? ucfirst(mesLabel($mesFiltro)) : 'Mês atual');
?>

<!-- KPIs principais -->
<div class="row g-3 mb-4">

    <?php if (!empty($canSeeLimited) && $canSeeLimited): ?>

        <!-- VENDEDOR: apenas Faturamento e Valor Recebido -->
        <div class="col-sm-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Faturamento (estimado)</div>
                    <div class="h4 fw-bold text-primary">
                        R$ <?= number_format((float)($stats['faturamento_estimado'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Valor Recebido</div>
                    <div class="h4 fw-bold text-success">
                        R$ <?= number_format((float)($stats['valor_pago'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

    <?php elseif (!empty($canSeeAllFin) && $canSeeAllFin): ?>

        <!-- ADMIN/GERENTE: todos os cards -->
        <div class="col-sm-6 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Ordens</div>
                    <div class="h4 fw-bold"><?= esc($stats['ordens_total'] ?? 0) ?></div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Faturamento</div>
                    <div class="h4 fw-bold text-primary">
                        R$ <?= number_format((float)($stats['faturamento_estimado'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Recebido</div>
                    <div class="h4 fw-bold text-success">
                        R$ <?= number_format((float)($stats['valor_pago'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Consultas</div>
                    <div class="h4 fw-bold text-info">
                        R$ <?= number_format((float)($stats['valor_consultas'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Custo Itens</div>
                    <div class="h4 fw-bold text-warning">
                        R$ <?= number_format((float)($stats['valor_custo_itens'] ?? 0), 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-2">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Lucro</div>
                    <div class="h4 fw-bold <?= $lucroClass ?>">
                        R$ <?= number_format($lucro, 2, ',', '.') ?>
                    </div>
                    <div class="small text-muted"><?= esc($periodoLbl) ?></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="text-end small text-muted">
                💡 Custo do dia anterior:
                <strong>R$ <?= number_format((float)($stats['custo_dia_anterior'] ?? 0), 2, ',', '.') ?></strong>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Últimos 14 dias -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>📅 Últimos 14 dias</strong>
        <span class="small text-muted">Toque para detalhar</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($dias_ultimos)): ?>
            <div class="accordion" id="accUltimosDias">
                <?php foreach ($dias_ultimos as $i => $d): $itemId = 'dia' . $i; ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="h-<?= $itemId ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#c-<?= $itemId ?>" aria-expanded="false"
                                aria-controls="c-<?= $itemId ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <span class="fw-semibold"><?= esc($d['label']) ?></span>
                                    <span class="badge <?= ($d['lucro'] ?? 0) >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                        Lucro: R$ <?= number_format((float)$d['lucro'], 2, ',', '.') ?>
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="c-<?= $itemId ?>" class="accordion-collapse collapse" aria-labelledby="h-<?= $itemId ?>"
                            data-bs-parent="#accUltimosDias">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    <div class="col">
                                        <div class="p-2 border rounded text-center">
                                            <div class="small text-muted">Ordens</div>
                                            <div class="fw-bold"><?= esc($d['ordens']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-2 border rounded text-center">
                                            <div class="small text-muted">Faturamento</div>
                                            <div class="fw-bold text-primary">R$ <?= number_format((float)$d['faturamento'], 2, ',', '.') ?></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-2 border rounded text-center">
                                            <div class="small text-muted">Recebido</div>
                                            <div class="fw-bold text-success">R$ <?= number_format((float)$d['valor_pago'], 2, ',', '.') ?></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-2 border rounded text-center">
                                            <div class="small text-muted">Imposto</div>
                                            <div class="fw-bold text-danger">R$ <?= number_format((float)$d['imposto'], 2, ',', '.') ?></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-2 border rounded text-center">
                                            <div class="small text-muted">Consultas</div>
                                            <div class="fw-bold text-info">R$ <?= number_format((float)$d['consultas'], 2, ',', '.') ?></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-2 border rounded text-center">
                                            <div class="small text-muted">Custo</div>
                                            <div class="fw-bold text-warning">R$ <?= number_format((float)$d['custo'], 2, ',', '.') ?></div>
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

<!-- Últimas ordens + Relatórios -->
<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white"><strong>📑 Últimas ordens</strong></div>
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
                                            <a href="<?= site_url('ordens/' . $o['id']) . '/edit' ?>"
                                                class="btn btn-sm btn-outline-primary">Editar</a>
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

    <div class="col-12 col-xl-5">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white"><strong>📂 Relatórios recentes</strong></div>
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

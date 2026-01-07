<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');

$f = $filtros ?? [];
$ini = $f['ini'] ?? date('Y-m-d', strtotime('-30 days'));
$fim = $f['fim'] ?? date('Y-m-d');

$resumo = $resumo ?? ['total_entradas' => 0, 'total_saidas' => 0, 'total_ajustes' => 0, 'total_movs' => 0];

function mov_label(string $t): string
{
    return match ($t) {
        'E' => 'Entrada',
        'S' => 'Saída',
        'A' => 'Ajuste',
        default => '—',
    };
}
function mov_badge(string $t): string
{
    return match ($t) {
        'E' => 'bg-success',
        'S' => 'bg-danger',
        'A' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
}
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>


<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Relatórios de Estoque</h4>
            <div class="text-muted small">Movimentações por período + resumo</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('estoque') ?>" class="btn btn-outline-secondary">← Voltar</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white">
            <strong>🔎 Filtros</strong>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('estoque/relatorios') ?>">
                <div class="row g-3 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label">Início</label>
                        <input type="date" name="ini" class="form-control" value="<?= esc($ini) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Fim</label>
                        <input type="date" name="fim" class="form-control" value="<?= esc($fim) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach (($tipos ?? []) as $t): ?>
                                <?php
                                $tid = (int)$t['id'];
                                $sel = (string)($f['tipo_id'] ?? '') === (string)$tid ? 'selected' : '';
                                ?>
                                <option value="<?= $tid ?>" <?= $sel ?>><?= esc($t['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Movimento</label>
                        <?php $movTipo = (string)($f['mov_tipo'] ?? ''); ?>
                        <select name="mov_tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="E" <?= $movTipo === 'E' ? 'selected' : '' ?>>Entrada</option>
                            <option value="S" <?= $movTipo === 'S' ? 'selected' : '' ?>>Saída</option>
                            <option value="A" <?= $movTipo === 'A' ? 'selected' : '' ?>>Ajuste</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Código</label>
                        <input type="text" name="codigo" class="form-control"
                            value="<?= esc((string)($f['codigo'] ?? '')) ?>"
                            placeholder="Ex.: ARM">
                    </div>

                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Entradas</div>
                    <div class="fs-4 fw-bold"><?= (int)$resumo['total_entradas'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Saídas</div>
                    <div class="fs-4 fw-bold"><?= (int)$resumo['total_saidas'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Ajustes (qtde de eventos)</div>
                    <div class="fs-4 fw-bold"><?= (int)$resumo['total_ajustes'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Total de movimentações</div>
                    <div class="fs-4 fw-bold"><?= (int)$resumo['total_movs'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <strong>🧾 Movimentações (até 300)</strong>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($movimentos)): ?>
                        <div class="table-responsive">
                            <table id="tbMovimentacoes" class="table table-hover align-middle mb-0 w-100">
                                <thead class="table-light">
                                    <tr>
                                        <!-- coluna auxiliar oculta pra ordenação correta -->
                                        <th class="d-none">DataSort</th>

                                        <th style="width:190px;">Data</th>
                                        <th style="width:120px;">Tipo</th>
                                        <th style="width:90px;" class="text-center">Qtd</th>
                                        <th>Item</th>
                                        <th>Motivo</th>
                                        <th style="width:120px;">Ref.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimentos as $m): ?>
                                        <?php
                                        $t = (string)($m['tipo'] ?? '');
                                        $createdAt = (string)($m['created_at'] ?? '');
                                        $ts = $createdAt ? strtotime($createdAt) : 0;

                                        $dt = $createdAt ? date('d/m/Y H:i', $ts) : '—';
                                        $itemLabel = trim(($m['codigo'] ?? '') . ' ' . (($m['titulo'] ?? '') ? ('— ' . $m['titulo']) : ''));
                                        ?>
                                        <tr>
                                            <!-- para ordenar: número puro -->
                                            <td class="d-none"><?= (int)$ts ?></td>

                                            <td class="text-muted"><?= esc($dt) ?></td>
                                            <td>
                                                <span class="badge <?= mov_badge($t) ?>">
                                                    <?= esc(mov_label($t)) ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold"><?= (int)($m['quantidade'] ?? 0) ?></td>
                                            <td>
                                                <div class="fw-semibold"><?= esc($itemLabel ?: '—') ?></div>
                                                <div class="small text-muted"><?= esc($m['tipo_nome'] ?? '—') ?></div>
                                            </td>
                                            <td><?= esc($m['motivo'] ?? '—') ?></td>
                                            <td><?= esc($m['referencia'] ?? '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">Sem movimentações no período.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <strong>🔥 Top itens (saídas no período)</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($rankingItens)): ?>
                        <div class="list-group">
                            <?php foreach ($rankingItens as $r): ?>
                                <?php
                                $label = trim(($r['codigo'] ?? '') . ' ' . (($r['titulo'] ?? '') ? ('— ' . $r['titulo']) : ''));
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold"><?= esc($label ?: '—') ?></div>
                                        <div class="small text-muted">
                                            Entradas: <?= (int)($r['entradas'] ?? 0) ?> • Saídas: <?= (int)($r['saidas'] ?? 0) ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-danger rounded-pill"><?= (int)($r['saidas'] ?? 0) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">Sem dados para ranking.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.jQuery) return;

        const $table = $('#tbMovimentacoes');
        if (!$table.length) return;

        const dt = $table.DataTable({
            // Botões + filtro + paginação com layout bonitinho Bootstrap
            dom: "<'row'<'col-12 col-md-6 d-flex gap-2 align-items-center'B><'col-12 col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row'<'col-12 col-md-5'i><'col-12 col-md-7'p>>",

            buttons: [{
                    extend: 'pdfHtml5',
                    text: '🖨️ Gerar PDF',
                    className: 'btn btn-sm mb-2',
                    title: 'Movimentações de Estoque',
                    filename: function() {
                        const d = new Date();
                        const y = d.getFullYear();
                        const m = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        return `movimentacoes_estoque_${y}-${m}-${day}`;
                    },
                    exportOptions: {
                        // exclui a coluna 0 (DataSort) e exporta só as visíveis úteis
                        columns: [1, 2, 3, 4, 5, 6]
                    },
                    customize: function(doc) {
                        // PDF simples e legível
                        doc.pageMargins = [20, 20, 20, 20];
                        doc.defaultStyle.fontSize = 9;
                        doc.styles.tableHeader.fontSize = 10;

                        // Deixa a tabela usar largura total
                        const content = doc.content || [];
                        const tableNode = content.find(n => n.table);
                        if (tableNode && tableNode.table && tableNode.table.body && tableNode.table.body[0]) {
                            tableNode.table.widths = Array(tableNode.table.body[0].length).fill('*');
                        }
                    }
                },

                // Opcional: botão de print do navegador (vira PDF também se o user salvar como PDF)
                // {
                //   extend: 'print',
                //   text: '🧾 Imprimir',
                //   className: 'btn btn-outline-secondary btn-sm',
                //   exportOptions: { columns: [1,2,3,4,5,6] }
                // }
            ],

            // Ordena por DataSort (coluna 0) desc
            order: [
                [0, 'desc']
            ],

            columnDefs: [{
                    targets: 0,
                    visible: false,
                    searchable: false
                }, // DataSort
                {
                    targets: 3,
                    className: 'text-center'
                } // Qtd
            ],

            pageLength: 25,
            lengthMenu: [10, 25, 50, 100, 300],
            stateSave: true,
            autoWidth: false,
            responsive: false,

            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json'
            }
        });
    });
</script>



<?= $this->endSection() ?>
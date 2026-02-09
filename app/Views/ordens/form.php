<?php

/**
 * View: ordens/form.php
 *
 * Espera variáveis:
 * - $ordem (array) | [] no create
 * - $clientes (array)
 * - $itens (array) itens da ordem (idealmente com: id, tipo, quantidade, preco_unitario, total, produto_codigo, produto_titulo, descricao)
 */

$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');

$ordem    = $ordem ?? [];
$clientes = $clientes ?? [];
$itens    = $itens ?? [];

$isEdit = !empty($ordem['id']);

// Rotas (conforme seu Routes.php)
$action = $isEdit ? site_url('ordens/' . $ordem['id'] . '/update') : site_url('ordens');

// Promoção 2º par (detecção automática quando tiver item_id 2)
$autoSegundoPar  = !empty($ordem['armacao_2_item_id']) || !empty($ordem['lente_2_item_id']);
$promoSegundoPar = (int)($ordem['promocao_segundo_par'] ?? 0);
if ($promoSegundoPar === 0 && $autoSegundoPar) {
    $promoSegundoPar = 1;
}

// Helpers
$fmt = fn($v) => number_format((float)$v, 2, ',', '.');

// Totais: custo vem dos itens; venda vem do campo manual
$custoSubtotal = 0.0;
foreach ($itens as $i) {
    $qtd = (float)($i['quantidade'] ?? 1);
    $pu  = (float)($i['preco_unitario'] ?? 0);
    $tot = (float)($i['total'] ?? ($qtd * $pu));
    $custoSubtotal += $tot;
}

$descontoPercent = (float)($ordem['desconto_percentual'] ?? 0);
if ($descontoPercent < 0) $descontoPercent = 0;
if ($descontoPercent > 100) $descontoPercent = 100;

$vendaBruta   = (float)($ordem['valor_venda'] ?? 0);
$descontoVenda = $vendaBruta * ($descontoPercent / 100);
$vendaLiquida = $vendaBruta - $descontoVenda;
if ($vendaLiquida < 0) $vendaLiquida = 0;

$lucro  = $vendaLiquida - $custoSubtotal;
$margem = ($vendaLiquida > 0) ? (($lucro / $vendaLiquida) * 100) : 0;

$lucroClass = $lucro >= 0 ? 'text-bg-success' : 'text-bg-danger';

?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0"><?= $isEdit ? 'Editar Ordem #' . esc($ordem['id']) : 'Nova Ordem' ?></h3>
        <a href="<?= site_url('ordens') ?>" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>

    <!-- FORM ORDEM -->
    <form action="<?= $action ?>" method="post" class="card mb-3">
        <?= csrf_field() ?>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($ordem['cliente_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= esc(($c['nome'] ?? $c['nome_cliente'] ?? 'Cliente') . ' (#' . $c['id'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php $st = (string)($ordem['status'] ?? 'aberta'); ?>
                        <?php foreach (['aberta', 'em_andamento', 'finalizada', 'cancelada'] as $s): ?>
                            <option value="<?= $s ?>" <?= $st === $s ? 'selected' : '' ?>>
                                <?= strtoupper(str_replace('_', ' ', $s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Data compra</label>
                    <input type="text" name="data_compra" class="form-control" placeholder="dd/mm/aaaa"
                        value="<?= esc($ordem['data_compra'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">O.S.</label>
                    <input type="text" name="ordem_servico" class="form-control"
                        value="<?= esc($ordem['ordem_servico'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Vendedor</label>
                    <input type="text" name="vendedor" class="form-control"
                        value="<?= esc($ordem['vendedor'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Desconto (%)</label>
                    <input type="number" name="desconto_percentual" step="0.01" min="0" max="100" class="form-control"
                        value="<?= esc($ordem['desconto_percentual'] ?? '0.00') ?>">
                    <div class="form-text">Aplica sobre o valor de venda (manual).</div>
                </div>

                <!-- Venda manual -->
                <div class="col-md-4">
                    <label class="form-label">Valor de venda (manual)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" name="valor_venda" class="form-control" placeholder="0,00"
                            value="<?= esc($ordem['valor_venda'] ?? '0.00') ?>">
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check">
                        <!-- garante 0 quando desmarcado -->
                        <input type="hidden" name="promocao_segundo_par" value="0">
                        <input class="form-check-input" type="checkbox"
                            name="promocao_segundo_par" value="1" id="promoSegundoPar"
                            <?= $promoSegundoPar ? 'checked' : '' ?>>
                        <label class="form-check-label" for="promoSegundoPar">
                            Promoção 2º par
                            <?php if ($autoSegundoPar): ?>
                                <span class="badge text-bg-success ms-1">detectada</span>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea name="obs" class="form-control" rows="3"><?= esc($ordem['obs'] ?? '') ?></textarea>
                </div>

            </div>
        </div>

        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <?php if (!$isEdit): ?>
                <span class="text-muted align-self-center">Depois de salvar, você poderá adicionar itens.</span>
            <?php endif; ?>
        </div>
    </form>

    <!-- ITENS -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Itens da Ordem</strong>
        </div>

        <div class="card-body">
            <?php if (!$isEdit): ?>
                <div class="alert alert-info mb-0">Salve a ordem para liberar a inclusão de itens.</div>
            <?php else: ?>

                <!-- ADD ITEM -->
                <form action="<?= site_url('ordens/' . $ordem['id'] . '/itens') ?>"
                    method="post"
                    class="row g-2 align-items-end mb-3"
                    id="formAddItem">

                    <?= csrf_field() ?>

                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" id="tipoItem">
                            <option value="produto" selected>Produto</option>
                            <option value="servico">Serviço</option>
                        </select>
                    </div>

                    <div class="col-md-6" id="wrapProduto">
                        <label class="form-label">Produto (estoque)</label>
                        <select name="produto_id" id="produto_id" class="form-select" style="width:100%"></select>
                    </div>

                    <div class="col-md-5 d-none" id="wrapServico">
                        <label class="form-label">Descrição do serviço</label>
                        <input type="text" name="descricao" class="form-control"
                            placeholder="Ex: consulta / ajuste / manutenção">
                    </div>

                    <!-- QTD (troca col-md-2 -> col-md-1 quando for serviço) -->
                    <div class="col-md-2" id="wrapQtd">
                        <label class="form-label">Qtd</label>
                        <input type="number" name="quantidade" min="1" step="1" class="form-control" value="1" required>
                    </div>

                    <!-- PREÇO SERVIÇO (somente serviço) -->
                    <div class="col-md-2 d-none" id="wrapServicoPreco">
                        <label class="form-label">Custo (R$)</label>
                        <input type="text" name="preco_unitario" class="form-control" placeholder="0,00">
                        <div class="form-text">Para serviço, o custo é manual.</div>
                    </div>

                    <!-- PREÇO PRODUTO (hidden, preenchido via select2) -->
                    <input type="hidden" name="preco_unitario_produto" id="preco_unitario_produto" value="0">

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">Adicionar</button>
                    </div>
                </form>

                <!-- LISTA ITENS -->
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th style="width:110px">Tipo</th>
                                <th>Item</th>
                                <th style="width:100px" class="text-end">Qtd</th>
                                <th style="width:160px" class="text-end">Custo unit.</th>
                                <th style="width:160px" class="text-end">Custo total</th>
                                <th style="width:120px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itens)): ?>
                                <tr>
                                    <td colspan="7" class="text-muted">Nenhum item adicionado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($itens as $i): ?>
                                    <?php
                                    $idItem = (int)$i['id'];
                                    $tipo   = (string)($i['tipo'] ?? 'produto');
                                    $qtd    = (int)($i['quantidade'] ?? 1);
                                    $pu     = (float)($i['preco_unitario'] ?? 0);
                                    $tot    = (float)($i['total'] ?? ($qtd * $pu));

                                    $label = $tipo === 'produto'
                                        ? trim((string)($i['produto_codigo'] ?? '')) . ' — ' . trim((string)($i['produto_titulo'] ?? ''))
                                        : (string)($i['descricao'] ?? 'Serviço');
                                    ?>
                                    <tr>
                                        <td><?= $idItem ?></td>
                                        <td>
                                            <span class="badge <?= $tipo === 'produto' ? 'text-bg-primary' : 'text-bg-warning' ?>">
                                                <?= esc(strtoupper($tipo)) ?>
                                            </span>
                                        </td>
                                        <td><?= esc($label) ?></td>

                                        <td class="text-end">
                                            <form action="<?= site_url('ordens/' . $ordem['id'] . '/itens/' . $idItem) ?>"
                                                method="post"
                                                class="d-flex justify-content-end gap-2">
                                                <?= csrf_field() ?>
                                                <input type="number" name="quantidade" min="1" step="1"
                                                    class="form-control form-control-sm" style="width:90px"
                                                    value="<?= esc($qtd) ?>">
                                                <button class="btn btn-sm btn-outline-primary" title="Atualizar quantidade">OK</button>
                                            </form>
                                        </td>

                                        <td class="text-end">R$ <?= esc($fmt($pu)) ?></td>
                                        <td class="text-end">R$ <?= esc($fmt($tot)) ?></td>

                                        <td class="text-end">
                                            <form action="<?= site_url('ordens/' . $ordem['id'] . '/itens/' . $idItem . '/delete') ?>"
                                                method="post"
                                                onsubmit="return confirm('Remover este item?')"
                                                class="d-inline">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Select2 (se já estiver no layout, remova estes 3 includes daqui) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* mantém alinhado com input do Bootstrap */
    .select2-container .select2-selection--single {
        height: 38px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
    }
</style>

<script>
    (function() {
        const langBR = {
            errorLoading: () => 'Os resultados não puderam ser carregados.',
            inputTooLong: (args) => `Remova ${args.input.length - args.maximum} caractere(s).`,
            inputTooShort: (args) => `Digite mais ${args.minimum - args.input.length} caractere(s).`,
            loadingMore: () => 'Carregando mais resultados…',
            maximumSelected: (args) => `Você só pode selecionar ${args.maximum} item(ns).`,
            noResults: () => 'Nenhum resultado encontrado.',
            searching: () => 'Buscando…'
        };

        function toBRMoney(v) {
            const n = Number(v || 0);
            return n.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function fmtBRL(v) {
            const n = Number(v || 0);
            return n.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        const $tipo = $('#tipoItem');
        const $wrapProduto = $('#wrapProduto');
        const $wrapServico = $('#wrapServico');
        const $wrapServicoPreco = $('#wrapServicoPreco');
        const $wrapQtd = $('#wrapQtd');
        const $produtoMeta = $('#produtoMeta');

        // Toggla UI + classe da Qtd
        function syncTipoUI() {
            const t = $tipo.val();
            const isServico = (t === 'servico');

            $wrapProduto.toggleClass('d-none', isServico);
            $wrapServico.toggleClass('d-none', !isServico);
            $wrapServicoPreco.toggleClass('d-none', !isServico);

            // CRITICAL: Qtd col-md-2 (produto) -> col-md-1 (serviço)
            $wrapQtd.removeClass('col-md-2 col-md-1').addClass(isServico ? 'col-md-1' : 'col-md-2');

            // limpa meta quando troca
            if (isServico) {
                $produtoMeta.text('Serviço: custo será digitado manualmente.');
                $('#produto_id').val(null).trigger('change');
                $('#preco_unitario_produto').val('0');
            } else {
                $produtoMeta.text('Selecione um item para puxar o custo automaticamente.');
            }
        }
        $tipo.on('change', syncTipoUI);
        syncTipoUI();

        // Select2 Produto
        $('#produto_id').select2({
            placeholder: 'Buscar item no estoque…',
            allowClear: true,
            width: '100%',
            language: langBR,
            ajax: {
                url: <?= json_encode(site_url('estoque/autocomplete')) ?>,
                dataType: 'json',
                delay: 250,
                data: (params) => ({
                    q: params.term || '',
                    page: params.page || 1
                }),
                processResults: (data) => data
            },
            templateResult: (item) => {
                if (!item.id) return item.text;

                // Esperado do backend: preco_custo e (opcional) preco_venda
                const custo = item.preco_custo ?? item.preco_venda ?? 0;
                const saldo = (typeof item.qtd_atual !== 'undefined') ? ` • saldo: ${item.qtd_atual}` : '';
                const cat = item.categoria ? ` • ${item.categoria}` : '';

                return $(`
                <div class="d-flex justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold">${item.text}</div>
                        <div class="small text-muted">${cat}${saldo}</div>
                    </div>
                    <small class="text-muted">${fmtBRL(custo)}</small>
                </div>
            `);
            },
            templateSelection: (item) => item.text || item.id
        }).on('select2:select', function(e) {
            const d = e.params.data || {};
            const custo = d.preco_custo ?? d.preco_venda ?? 0;

            // hidden: custo unitário do produto
            $('#preco_unitario_produto').val(String(custo));

            // feedback ao usuário
            const saldo = (typeof d.qtd_atual !== 'undefined') ? `Saldo: ${d.qtd_atual}` : '';
            $produtoMeta.html(`Custo puxado do estoque: <strong>${fmtBRL(custo)}</strong>${saldo ? ' • ' + saldo : ''}`);
        }).on('select2:clear', function() {
            $('#preco_unitario_produto').val('0');
            $produtoMeta.text('Selecione um item para puxar o custo automaticamente.');
        });

        // Antes de enviar ADD ITEM:
        // - produto: injeta preco_unitario (custo) no campo do backend (reaproveitando o name preco_unitario)
        // - serviço: usa o input preco_unitario visível
        $('#formAddItem').on('submit', function() {
            const tipo = $tipo.val();
            if (tipo === 'produto') {
                // cria um input hidden "preco_unitario" com o custo do produto
                // (evita depender do campo de serviço)
                const v = $('#preco_unitario_produto').val() || '0';

                // remove anteriores para não duplicar
                $(this).find('input[name="preco_unitario"][data-auto="1"]').remove();

                $('<input>', {
                    type: 'hidden',
                    name: 'preco_unitario',
                    value: v,
                    'data-auto': '1'
                }).appendTo(this);
            }
        });

    })();
</script>

<?= $this->endSection() ?>
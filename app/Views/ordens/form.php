<?php

/**
 * View: ordens/form.php
 *
 * Espera variáveis:
 * - $ordem (array) | [] no create
 * - $clientes (array)
 * - $itens (array) itens da ordem
 * - $financeiro (array) resumo financeiro (total_pago, saldo, qtd_pagamentos)
 * - $pagamentos (array) lista de pagamentos da ordem
 * - $formasPagamento (array) lista de formas de pagamento (id, nome)
 * - $isGerente (bool)
 * - $users (array) lista de usuários (id, name) — somente para admin (ou vazio)
 * - $isAdmin (bool)
 * - $isLegacyVenda (bool) true quando NÃO há vínculo (vendedor_id NULL) e existe vendedor legado
 */

$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');

$ordem    = $ordem ?? [];
$clientes = $clientes ?? [];
$itens    = $itens ?? [];

$isEdit = !empty($ordem['id']);

// Rotas
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
$descontoPercent = max(0, min(100, $descontoPercent));

$vendaBruta    = (float)($ordem['valor_venda'] ?? 0);
$descontoVenda = $vendaBruta * ($descontoPercent / 100);
$vendaLiquida  = max(0, $vendaBruta - $descontoVenda);

$lucro  = $vendaLiquida - $custoSubtotal;
$margem = ($vendaLiquida > 0) ? (($lucro / $vendaLiquida) * 100) : 0;

$lucroClass = $lucro >= 0 ? 'text-bg-success' : 'text-bg-danger';



// Financeiro / pagamentos (para exibição)
// Observação: saldo é calculado (preferencialmente) com base na venda líquida.
$financeiro     = $financeiro ?? [];
$pagamentos     = $pagamentos ?? [];
$formasPagamento = $formasPagamento ?? [];

$totalPago = (float)($financeiro['total_pago'] ?? 0);
if ($totalPago <= 0 && !empty($pagamentos) && is_array($pagamentos)) {
    foreach ($pagamentos as $p) {
        $totalPago += (float)($p['valor'] ?? 0);
    }
}

$qtdPag = (int)($financeiro['qtd_pagamentos'] ?? (is_array($pagamentos) ? count($pagamentos) : 0));

$valorBasePag = ($vendaLiquida > 0)
    ? $vendaLiquida
    : (float)($ordem['valor_venda'] ?? 0);

$saldo = (float)($financeiro['saldo'] ?? ($valorBasePag - $totalPago));
$saldo = max(0.0, $saldo);

$quitado = ($valorBasePag > 0 && $saldo <= 0.0001);

$badgeTotalPago =
    $quitado ? 'bg-success'
    : ($totalPago > 0 ? 'bg-warning text-dark' : 'bg-secondary');

$badgeSaldo =
    ($saldo > 0.0001) ? 'bg-danger' : 'bg-success';

$saldoDisplay = $saldo;

// Vendedor (permissões + exibição)
$users   = $users ?? [];
$isAdmin = (bool)($isAdmin ?? false);
$isGerente = (bool)($isGerente ?? false);
$isLegacyVenda = (bool)($isLegacyVenda ?? false);

$canEditarVendedor      = $isGerente || $isAdmin;                   // só admin muda vínculo
$mostrarIndicadorLegado = $isAdmin && $isLegacyVenda; // LEGADO só aparece pra admin

// Sempre exibe algo no campo (pra qualquer perfil)
$vendedorExibicao = (string)($ordem['vendedor_nome'] ?? $ordem['vendedor'] ?? '');
$vendedorExibicao = trim($vendedorExibicao) !== '' ? $vendedorExibicao : '—';

?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <!-- FORM ORDEM -->
    <form action="<?= $action ?>" method="post">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0">
                <?= $isEdit ? 'Editar Ordem #' . esc($ordem['id']) : 'Nova Ordem' ?>
            </h3>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Salvar Ordem</button>
                <a href="<?= site_url('ordens') ?>" class="btn btn-outline-secondary">Voltar</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= esc($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= esc($error) ?></div>
        <?php endif; ?>

        <?= csrf_field() ?>

        <div class="card mb-3 card-body">
            <div class="row g-3">

                <!-- LINHA 1 (12 col) -->
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <div class="input-group">
                        <select name="cliente_id" id="cliente_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= ((int)($ordem['cliente_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                    <?= esc(($c['nome'] ?? $c['nome_cliente'] ?? 'Cliente') . ' (#' . $c['id'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                            Novo
                        </button>
                    </div>
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
                    <input type="text" name="data_compra" class="form-control date-mask" placeholder="dd/mm/aaaa"
                        value="<?= esc($ordem['data_compra'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">O.S.</label>
                    <input type="text" name="ordem_servico" class="form-control"
                        value="<?= esc($ordem['ordem_servico'] ?? '') ?>">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
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

                <!-- LINHA 2 (12 col) -->
                <div class="col-md-2">
                    <label class="form-label">
                        Vendedor
                        <?php if ($mostrarIndicadorLegado): ?>
                            <span class="badge text-bg-warning ms-1">LEGADO</span>
                        <?php endif; ?>
                    </label>

                    <input type="text"
                        class="form-control"
                        value="<?= esc($vendedorExibicao) ?>"
                        readonly>

                    <?php if ($mostrarIndicadorLegado): ?>
                        <div class="form-text text-warning">
                            Venda legado: sem vínculo. Regularize selecionando um vendedor ao lado.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($canEditarVendedor): ?>
                    <div class="col-md-4">
                        <label class="form-label">
                            <?= $isLegacyVenda ? 'Atribuir vendedor' : 'Alterar vendedor' ?>
                        </label>

                        <?php $selVend = (string) old('vendedor_id', (string)($ordem['vendedor_id'] ?? '')); ?>
                        <select name="vendedor_id" class="form-select">
                            <option value="">— Selecione —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= $selVend === (string)$u['id'] ? 'selected' : '' ?>>
                                    <?= esc($u['name'] ?? ('Usuário #' . (int)$u['id'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="form-text">
                            Vincula a ordem a um usuário vendedor (o nome legado é preservado para auditoria).
                        </div>
                    </div>
                <?php endif; ?>

                <?php $notaGerada = (int) old('nota_gerada', (int)($ordem['nota_gerada'] ?? 0)); ?>

                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check">
                        <input type="hidden" name="nota_gerada" value="0">
                        <input class="form-check-input" type="checkbox" id="nota_gerada" name="nota_gerada" value="1"
                            <?= $notaGerada ? 'checked' : '' ?>>
                        <label class="form-check-label" for="nota_gerada">Nota gerada?</label>
                    </div>
                </div>

                <div class="col-md-3 <?= $notaGerada ? '' : 'invisible pe-none' ?>" id="wrapDiaNota" aria-hidden="<?= $notaGerada ? 'false' : 'true' ?>">
                    <label class="form-label">Dia da nota</label>
                    <input type="text" name="dia_nota" class="form-control date-mask"
                        placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10"
                        value="<?= old('dia_nota', $ordem['dia_nota'] ?? '') ?>"
                        <?= $notaGerada ? '' : 'disabled' ?>>
                </div>



                <div class="col-md-3">
                    <label class="form-label">Valor da consulta</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" name="consulta" class="form-control" inputmode="decimal" placeholder="0,00"
                            value="<?= esc(old('consulta', $fmt($ordem['consulta']) ?? '')) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Valor do laboratório</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" name="pagamento_laboratorio" class="form-control" inputmode="decimal" placeholder="0,00" value="<?= esc(old('pagamento_laboratorio', $fmt($ordem['pagamento_laboratorio']) ?? '')) ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Valor de venda (manual)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" name="valor_venda" class="form-control" placeholder="0,00"
                            value="<?= esc(old('valor_venda', $fmt($ordem['valor_venda'] ?? 0))) ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Desconto (%)</label>
                    <input type="number" name="desconto_percentual" step="0.01" min="0" max="100" class="form-control"
                        value="<?= esc($ordem['desconto_percentual'] ?? '0.00') ?>">
                    <div class="form-text">Aplica sobre o valor de venda.</div>
                </div>

                <!-- LINHA 3 -->

                <?php if ($isAdmin): ?>

                    <div class="col-md-12">
                        <div class="p-2 border rounded-2 bg-light">
                            <div class="d-flex flex-wrap gap-3">
                                <div><strong>Custo:</strong> R$ <?= esc($fmt($custoSubtotal)) ?></div>
                                <div><strong>Venda líquida:</strong> R$ <?= esc($fmt($vendaLiquida)) ?></div>
                                <div>
                                    <strong>Lucro:</strong>
                                    <span class="badge <?= $lucroClass ?>">R$ <?= esc($fmt($lucro)) ?></span>
                                </div>
                                <div><strong>Margem:</strong> <?= esc($fmt($margem)) ?>%</div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea name="obs" class="form-control" rows="3"><?= esc($ordem['obs'] ?? '') ?></textarea>
                </div>

            </div>
        </div>
    </form>


    <!-- PAGAMENTOS -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Pagamentos</strong>

            <?php if ($isEdit): ?>
                <button type="button" class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#modalPagamento">
                    + Pagamento
                </button>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Total da ordem</label>
                    <div>
                        <span class="badge bg-light text-dark fs-6">
                            R$ <?= esc($fmt($valorBasePag)) ?>
                        </span>
                    </div>
                    <div class="form-text">Base: venda líquida (considera desconto).</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Total pago</label>
                    <div>
                        <span class="badge <?= $badgeTotalPago ?> fs-6">
                            R$ <?= esc($fmt($totalPago)) ?>
                        </span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Saldo</label>
                    <div>
                        <span class="badge <?= $badgeSaldo ?> fs-6">
                            R$ <?= esc($fmt($saldoDisplay)) ?>
                        </span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nº pagamentos</label>
                    <div>
                        <span class="badge bg-light text-dark fs-6">
                            <?= (int)$qtdPag ?>
                        </span>
                    </div>
                </div>

            </div>

            <?php if ($isEdit): ?>
                <hr class="my-4">

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Forma</th>
                                <th>Status</th>
                                <th>Obs.</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pagamentos)): ?>
                                <tr>
                                    <td colspan="6" class="text-muted text-center">Nenhum pagamento registrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pagamentos as $p): ?>
                                    <?php
                                    $st = (string)($p['status'] ?? '');
                                    $stClass = 'bg-secondary';
                                    if ($st === 'confirmado') $stClass = 'bg-success';
                                    elseif ($st === 'pendente') $stClass = 'bg-warning text-dark';
                                    elseif ($st === 'cancelado') $stClass = 'bg-secondary';
                                    elseif ($st === 'estornado') $stClass = 'bg-dark';

                                    $dataFmt = '';
                                    if (!empty($p['data_pagamento'])) {
                                        $ts = strtotime((string)$p['data_pagamento']);
                                        $dataFmt = $ts ? date('d/m/Y H:i', $ts) : (string)$p['data_pagamento'];
                                    }
                                    ?>
                                    <tr>
                                        <td><?= esc($dataFmt) ?></td>
                                        <td>R$ <?= number_format((float)($p['valor'] ?? 0), 2, ',', '.') ?></td>
                                        <td><?= esc($p['forma_nome'] ?? ($p['forma'] ?? '—')) ?></td>
                                        <td><span class="badge <?= $stClass ?>"><?= esc($st) ?></span></td>
                                        <td><?= esc($p['obs'] ?? '') ?></td>
                                        <td class="text-end">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return (confirm('Remover este pagamento?') && document.getElementById('delpay-<?= (int)$p['id'] ?>').submit());">
                                                Remover
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="alert alert-info mt-3 mb-0">
                    Salve a ordem para registrar pagamentos.
                </div>
            <?php endif; ?>

        </div>
    </div>

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
                                        ? trim((string)($i['codigo'] ?? '')) . ' — ' . trim((string)($i['titulo'] ?? ''))
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


<?php if ($isEdit): ?>
    <!-- Modal Pagamento -->
    <div class="modal fade" id="modalPagamento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <form method="post" action="<?= site_url('ordens/' . $ordem['id'] . '/pagamentos/add') ?>">
                    <div class="modal-body">
                        <?= csrf_field() ?>

                        <div class="alert alert-warning">
                            Se você alterou dados da ordem, salve antes de registrar o pagamento.
                        </div>

                        <?php $saldoJs = number_format($saldoDisplay, 2, '.', ''); ?>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="text-muted">Saldo atual:</span>
                                <span class="badge <?= ($saldoDisplay > 0.0001) ? 'bg-danger' : 'bg-success' ?> fs-6 ms-1">
                                    R$ <?= number_format($saldoDisplay, 2, ',', '.') ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                Máximo permitido: <strong>R$ <?= number_format($saldoDisplay, 2, ',', '.') ?></strong>
                            </small>
                        </div>

                        <div id="payErrors" class="alert alert-danger d-none"></div>

                        <input type="hidden" id="saldoAtualJs" value="<?= esc($saldoJs) ?>">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Valor*</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="text" name="valor" class="form-control" inputmode="decimal"
                                        placeholder="0,00" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Data do pagamento</label>
                                <input type="text" name="data_pagamento" class="form-control date-mask"
                                    placeholder="DD/MM/AAAA" maxlength="10">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Forma</label>
                                <select name="forma_pagamento_id" class="form-select">
                                    <option value="">Selecione...</option>
                                    <?php foreach (($formasPagamento ?? []) as $fp): ?>
                                        <option value="<?= (int)$fp['id'] ?>"><?= esc($fp['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Obs.</label>
                                <input type="text" name="obs" class="form-control" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isEdit && !empty($pagamentos)): ?>
    <?php foreach ($pagamentos as $p): ?>
        <form id="delpay-<?= (int)$p['id'] ?>"
            method="post"
            action="<?= site_url('ordens/' . $ordem['id'] . '/pagamentos/' . $p['id'] . '/delete') ?>"
            class="d-none">
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal Novo Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form id="formCliente" action="<?= site_url('clientes/store') ?>" method="post">
                <div class="modal-body">
                    <?= csrf_field() ?>

                    <div id="clienteErrors" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome*</label>
                            <input name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CPF</label>
                            <input id="cli_documento" name="documento" class="form-control" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Celular</label>
                            <input id="cli_telefone" name="telefone" class="form-control" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input name="email" type="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Endereço</label>
                            <input name="endereco" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cidade</label>
                            <input name="cidade" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">UF</label>
                            <input name="estado" class="form-control" maxlength="2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input id="cli_cep" name="cep" class="form-control" placeholder="00000-000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Término do contrato</label>
                            <input name="termino_contrato" class="form-control date-mask" placeholder="DD/MM/AAAA">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= base_url('js/form-masks.js') ?>?v=<?= urlencode((string)ENVIRONMENT) ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.FormsMasks && typeof window.FormsMasks.applyAll === 'function') {
            window.FormsMasks.applyAll(document);
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Modal Cliente (AJAX) ---
        const form = document.getElementById('formCliente');
        if (form) {
            const errorsBox = document.getElementById('clienteErrors');
            const modalEl = document.getElementById('modalCliente');
            const clienteSelect = document.getElementById('cliente_id');
            const csrfName = <?= json_encode(csrf_token()) ?>;

            // Máscaras (se Inputmask estiver disponível no layout)
            if (window.Inputmask) {
                const doc = document.getElementById('cli_documento');
                if (doc) Inputmask({
                    mask: "999.999.999-99",
                    clearIncomplete: true
                }).mask(doc);

                const tel = document.getElementById('cli_telefone');
                if (tel) Inputmask({
                    mask: ["(99) 9999-9999", "(99) 99999-9999"],
                    keepStatic: true,
                    clearIncomplete: true
                }).mask(tel);

                const cep = document.getElementById('cli_cep');
                if (cep) Inputmask({
                    mask: "99999-999",
                    clearIncomplete: true
                }).mask(cep);
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (errorsBox) {
                    errorsBox.classList.add('d-none');
                    errorsBox.innerHTML = '';
                }

                const fd = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: fd
                });

                const json = await res.json().catch(() => ({}));

                // Atualiza CSRF (cliente + demais forms da página)
                if (json.csrf) {
                    const csrfInputCliente = form.querySelector(`input[name="${csrfName}"]`);
                    if (csrfInputCliente) csrfInputCliente.value = json.csrf;

                    document.querySelectorAll(`input[name="${csrfName}"]`)
                        .forEach(el => el.value = json.csrf);
                }

                if (!res.ok || !json.ok) {
                    const errs = json.errors || {
                        geral: 'Erro ao salvar.'
                    };
                    if (errorsBox) {
                        errorsBox.innerHTML = Object.values(errs).map(msg => `<div>${msg}</div>`).join('');
                        errorsBox.classList.remove('d-none');
                    }
                    return;
                }

                // Sucesso: adiciona no select e seleciona
                if (clienteSelect) {
                    const opt = new Option(json.nome, json.id, true, true);
                    clienteSelect.add(opt);
                    clienteSelect.dispatchEvent(new Event('change'));
                }

                // Fecha modal
                if (window.bootstrap && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                } else if (modalEl) {
                    modalEl.classList.remove('show');
                }

                form.reset();
            });
        }

        // --- Validação do Modal de Pagamento ---
        const modalElPay = document.getElementById('modalPagamento');
        if (!modalElPay) return;

        const payForm = modalElPay.querySelector('form');
        const payErrors = document.getElementById('payErrors');
        const saldoInput = document.getElementById('saldoAtualJs');

        function parseBRMoney(str) {
            if (!str) return 0;
            return parseFloat(
                String(str)
                .replace(/\s/g, '')
                .replace(/\./g, '')
                .replace(',', '.')
                .replace(/[^0-9.]/g, '')
            ) || 0;
        }

        function showPayError(msg) {
            if (!payErrors) return;
            payErrors.innerHTML = msg;
            payErrors.classList.remove('d-none');
        }

        function clearPayError() {
            if (!payErrors) return;
            payErrors.innerHTML = '';
            payErrors.classList.add('d-none');
        }

        modalElPay.addEventListener('shown.bs.modal', () => {
            clearPayError();
            const valorField = payForm?.querySelector('input[name="valor"]');
            if (valorField) valorField.focus();
        });

        payForm?.addEventListener('submit', function(e) {
            clearPayError();

            const saldo = parseFloat(saldoInput?.value || '0') || 0;
            const valorField = payForm.querySelector('input[name="valor"]');
            const valor = parseBRMoney(valorField?.value);

            const eps = 0.0001;

            if (saldo <= eps) {
                e.preventDefault();
                showPayError('Esta ordem já está quitada. Não é possível registrar novo pagamento.');
                return;
            }

            if (valor <= eps) {
                e.preventDefault();
                showPayError('Informe um valor de pagamento válido.');
                return;
            }

            if (valor > (saldo + eps)) {
                e.preventDefault();
                showPayError(`O valor informado (R$ ${valor.toFixed(2).replace('.', ',')}) excede o saldo atual.
                        Lance no máximo o saldo (R$ ${saldo.toFixed(2).replace('.', ',')}).`);
                return;
            }
        });
    });
</script>


<!-- Select2 (se já estiver no layout, remova estes 3 includes daqui) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
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

        function syncTipoUI() {
            const t = $tipo.val();
            const isServico = (t === 'servico');

            $wrapProduto.toggleClass('d-none', isServico);
            $wrapServico.toggleClass('d-none', !isServico);
            $wrapServicoPreco.toggleClass('d-none', !isServico);

            $wrapQtd.removeClass('col-md-2 col-md-1').addClass(isServico ? 'col-md-1' : 'col-md-2');

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

            $('#preco_unitario_produto').val(String(custo));

            const saldo = (typeof d.qtd_atual !== 'undefined') ? `Saldo: ${d.qtd_atual}` : '';
            $produtoMeta.html(`Custo puxado do estoque: <strong>${fmtBRL(custo)}</strong>${saldo ? ' • ' + saldo : ''}`);
        }).on('select2:clear', function() {
            $('#preco_unitario_produto').val('0');
            $produtoMeta.text('Selecione um item para puxar o custo automaticamente.');
        });

        $('#formAddItem').on('submit', function() {
            const tipo = $tipo.val();
            if (tipo === 'produto') {
                const v = $('#preco_unitario_produto').val() || '0';

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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chk = document.getElementById('nota_gerada');
        const wrap = document.getElementById('wrapDiaNota');
        const inp = document.querySelector('input[name="dia_nota"]');

        if (!chk || !wrap || !inp) return;

        function syncDiaNota() {
            const on = chk.checked;

            // mantém o espaço, só “some” visualmente
            wrap.classList.toggle('invisible', !on);
            wrap.classList.toggle('pe-none', !on);
            wrap.setAttribute('aria-hidden', on ? 'false' : 'true');

            // não deixa enviar valor escondido
            inp.disabled = !on;
            if (!on) inp.value = '';
        }

        chk.addEventListener('change', syncDiaNota);
        syncDiaNota();
    });
</script>

<?= $this->endSection() ?>
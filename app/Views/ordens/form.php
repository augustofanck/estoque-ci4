<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = isset($ordem['id']);
$action = $isEdit ? site_url('ordens/' . $ordem['id'] . '/update') : site_url('ordens');
?>

<h1 class="h3 mb-3"><?= $isEdit ? 'Editar Ordem' : 'Nova Ordem' ?></h1>

<?php if ($msg = session()->getFlashdata('msg')): ?>
    <div class="alert alert-success"><?= esc($msg) ?></div>
<?php endif; ?>

<?php if ($errs = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <?= implode('<br>', array_map('esc', (array)$errs)) ?>
    </div>
<?php endif; ?>

<script>
    // Nome do campo CSRF (ex.: csrf_test_name)
    const CSRF_NAME = "<?= csrf_token() ?>";
</script>

<form id="formOrdem" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>

    <div id="errorsBox" class="alert alert-danger d-none"></div>

    <!-- Identificação -->
    <div class="card mb-3">
        <div class="card-header fw-semibold">Identificação</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data da compra</label>
                    <input type="text" name="data_compra" class="form-control date-mask"
                        placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10"
                        value="<?= old('data_compra', $ordem['data_compra'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">O.S. (nº)</label>
                    <input type="text" name="ordem_servico" class="form-control"
                        placeholder="Ex.: 1245"
                        value="<?= old('ordem_servico', $ordem['ordem_servico'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cliente*</label>
                    <div class="input-group">
                        <select name="cliente_id" id="cliente_id" class="form-select" required>
                            <option value="">Selecione um cliente...</option>
                            <?php foreach (($clientes ?? []) as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"
                                    <?= (isset($ordem['cliente_id']) && (int)$ordem['cliente_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                    <?= esc($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                            Novo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $fin = $financeiro ?? [];
    $totalPago = (float)($fin['total_pago'] ?? 0);
    $saldo     = (float)($fin['saldo'] ?? 0);
    $qtdPag    = (int)($fin['qtd_pagamentos'] ?? 0);

    $valorVenda = (float)($ordem['valor_venda'] ?? 0);
    $quitado = ($valorVenda > 0 && $saldo <= 0.0001);

    $badgeTotalPago =
        $quitado ? 'bg-success'
        : ($totalPago > 0 ? 'bg-warning text-dark' : 'bg-secondary');

    $badgeSaldo =
        ($saldo > 0.0001) ? 'bg-danger' : 'bg-success';

    $saldoDisplay = ($saldo < 0) ? 0 : $saldo;
    ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Valores e Pagamento</span>

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
                    <label class="form-label">Valor de venda</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_venda" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_venda', $ordem['valor_venda'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Total pago</label>
                    <div>
                        <span class="badge <?= $badgeTotalPago ?> fs-6">
                            R$ <?= number_format($totalPago, 2, ',', '.') ?>
                        </span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Saldo</label>
                    <div>
                        <span class="badge <?= $badgeSaldo ?> fs-6">
                            R$ <?= number_format($saldoDisplay, 2, ',', '.') ?>
                        </span>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nº pagamentos</label>
                    <div>
                        <span class="badge bg-light text-dark fs-6">
                            <?= $qtdPag ?>
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Valor da Consulta</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="consulta" class="form-control"
                            placeholder="0,00"
                            value="<?= old('consulta', $ordem['consulta'] ?? '') ?>">
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
                                <th>Tipo</th>
                                <th>Obs.</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pagamentos)): ?>
                                <tr>
                                    <td colspan="7" class="text-muted text-center">Nenhum pagamento registrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pagamentos as $p): ?>
                                    <?php
                                    $st = $p['status'] ?? '';
                                    $stClass = 'bg-secondary';
                                    if ($st === 'confirmado') $stClass = 'bg-success';
                                    elseif ($st === 'pendente') $stClass = 'bg-warning text-dark';
                                    elseif ($st === 'cancelado') $stClass = 'bg-secondary';
                                    elseif ($st === 'estornado') $stClass = 'bg-dark';

                                    $dataFmt = '';
                                    if (!empty($p['data_pagamento'])) {
                                        $ts = strtotime($p['data_pagamento']);
                                        $dataFmt = $ts ? date('d/m/Y H:i', $ts) : (string)$p['data_pagamento'];
                                    }
                                    ?>
                                    <tr>
                                        <td><?= esc($dataFmt) ?></td>
                                        <td>R$ <?= number_format((float)($p['valor'] ?? 0), 2, ',', '.') ?></td>
                                        <td><?= esc($p['forma_nome'] ?? '—') ?></td>
                                        <td><span class="badge <?= $stClass ?>"><?= esc($st) ?></span></td>
                                        <td><span class="badge bg-light text-dark"><?= esc($p['tipo'] ?? '') ?></span></td>
                                        <td><?= esc($p['obs'] ?? '') ?></td>
                                        <td class="text-end">
                                            <!-- PATCH: não usar submit aqui para não acionar o submit AJAX do formOrdem -->
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

    <?php
    $temSegundo = (old('tem_segundo_par') === '1')
        || ((float)($ordem['valor_armacao_2'] ?? 0) > 0)
        || ((float)($ordem['valor_lente_2']  ?? 0) > 0)
        || (($ordem['tipo_lente_2'] ?? '') !== '');
    ?>

    <!-- Itens e Lentes -->
    <div class="card mb-3">
        <div class="card-header fw-semibold">Itens e Lentes</div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Armação (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_armacao_1" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_armacao_1', $ordem['valor_armacao_1'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Lente (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_lente_1" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_lente_1', $ordem['valor_lente_1'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de Lente</label>
                    <input type="text" name="tipo_lente_1" class="form-control"
                        placeholder="Ex.: monofocal / antirreflexo"
                        value="<?= old('tipo_lente_1', $ordem['tipo_lente_1'] ?? '') ?>">
                </div>
            </div>

            <div id="grupoSegundoPar" class="<?= $temSegundo ? '' : 'mt-3 d-none' ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Armação 2 (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" inputmode="decimal" name="valor_armacao_2"
                                class="form-control" placeholder="0,00"
                                value="<?= old('valor_armacao_2', $ordem['valor_armacao_2'] ?? '') ?>"
                                <?= $temSegundo ? '' : 'disabled' ?>>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Lente 2 (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" inputmode="decimal" name="valor_lente_2"
                                class="form-control" placeholder="0,00"
                                value="<?= old('valor_lente_2', $ordem['valor_lente_2'] ?? '') ?>"
                                <?= $temSegundo ? '' : 'disabled' ?>>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de Lente 2</label>
                        <input type="text" name="tipo_lente_2" class="form-control"
                            placeholder="Ex.: monofocal / antirreflexo"
                            value="<?= old('tipo_lente_2', $ordem['tipo_lente_2'] ?? '') ?>"
                            <?= $temSegundo ? '' : 'disabled' ?>>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center mb-2 mt-3">
                <div class="form-check form-switch ms-auto">
                    <input type="hidden" name="tem_segundo_par" value="0">
                    <input class="form-check-input" type="checkbox" id="tem_segundo_par"
                        name="tem_segundo_par" value="1" <?= $temSegundo ? 'checked' : '' ?>>
                    <label class="form-check-label ms-2" for="tem_segundo_par">
                        Promoção: ativar segundo par (compre 1 leve 2)
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Laboratório e Entrega -->
    <div class="card mb-3">
        <div class="card-header fw-semibold">Laboratório e Entrega</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Pgto. p/ laboratório</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="pagamento_laboratorio" class="form-control"
                            placeholder="0,00"
                            value="<?= old('pagamento_laboratorio', $ordem['pagamento_laboratorio'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dia do pagamento (lab.)</label>
                    <input type="text" name="dia_pagamento_laboratorio" class="form-control date-mask"
                        placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10"
                        value="<?= old('dia_pagamento_laboratorio', $ordem['dia_pagamento_laboratorio'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Recebimento do óculos (lab.)</label>
                    <input type="text" name="data_recebimento_laboratorio" class="form-control date-mask"
                        placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10"
                        value="<?= old('data_recebimento_laboratorio', $ordem['data_recebimento_laboratorio'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Entrega do óculos</label>
                    <input type="text" name="data_entrega_oculos" class="form-control date-mask"
                        placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10"
                        value="<?= old('data_entrega_oculos', $ordem['data_entrega_oculos'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Fiscal -->
    <div class="card mb-4">
        <div class="card-header fw-semibold">Fiscal</div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-2">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="nota_gerada" name="nota_gerada"
                            <?= old('nota_gerada', $ordem['nota_gerada'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="nota_gerada">Nota gerada?</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dia da nota</label>
                    <input type="text" name="dia_nota" class="form-control date-mask"
                        placeholder="DD/MM/AAAA" inputmode="numeric" maxlength="10"
                        value="<?= old('dia_nota', $ordem['dia_nota'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número Nota</label>
                    <input type="text" name="numero_nota" class="form-control only-number" inputmode="numeric" maxlength="10"
                        value="<?= old('numero_nota', $ordem['numero_nota'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vendedor</label>
                    <input type="text" name="vendedor" class="form-control"
                        placeholder="Nome do Vendedor"
                        value="<?= old('vendedor', $ordem['vendedor'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Observações</label>
                    <textarea name="obs" id="obs" class="form-control" placeholder="Observações Gerais"><?= old('obs', $ordem['obs'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações -->
    <div class="d-flex gap-2">
        <a href="<?= site_url('ordens') ?>" class="btn btn-outline-secondary">Voltar</a>
        <button class="btn btn-primary"><?= $isEdit ? 'Salvar alterações' : 'Salvar' ?></button>
    </div>
</form>

<?php if ($isEdit): ?>
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

<script src="<?= base_url('js/form-masks.js') ?>?v=<?= urlencode((string)ENVIRONMENT) ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.FormsMasks) FormsMasks.applyAll(document);
    });
</script>

<?= $this->renderSection('page_scripts') ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formOrdem');
        const errorsBox = document.getElementById('errorsBox');
        const chk = document.getElementById('tem_segundo_par');
        const wrap = document.getElementById('grupoSegundoPar');

        if (chk && wrap) {
            function toggleSegundoPar() {
                const on = chk.checked;
                wrap.classList.toggle('d-none', !on);
                wrap.querySelectorAll('input').forEach(el => el.disabled = !on);
            }
            chk.addEventListener('change', toggleSegundoPar);
            toggleSegundoPar();
        }

        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                errorsBox.classList.add('d-none');
                errorsBox.innerHTML = '';

                const fd = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: fd
                });

                const json = await res.json().catch(() => ({}));

                if (json.csrf) {
                    document.querySelectorAll(`input[name="${CSRF_NAME}"]`)
                        .forEach(el => el.value = json.csrf);
                }

                if (!res.ok || !json.ok) {
                    const errs = json.errors || {
                        geral: 'Erro ao salvar.'
                    };
                    errorsBox.innerHTML = Object.values(errs).map(msg => `<div>${msg}</div>`).join('');
                    errorsBox.classList.remove('d-none');
                    return;
                }

                // PATCH: no edit, volta pra própria edição; no create, volta pra lista
                if (json.ok) {
                    window.location.href = "<?= $isEdit ? site_url('ordens/' . ($ordem['id'] ?? 0) . '/edit') : site_url('ordens') ?>";
                }
            });
        }
    });

    document.querySelectorAll('.only-number').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    });
</script>

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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Máscaras do modal (reutiliza Inputmask já carregado no layout)
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

        const form = document.getElementById('formCliente');
        const errorsBox = document.getElementById('clienteErrors');
        const modalEl = document.getElementById('modalCliente');
        const clienteSelect = document.getElementById('cliente_id');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            errorsBox.classList.add('d-none');
            errorsBox.innerHTML = '';

            const fd = new FormData(form);
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            });

            const json = await res.json().catch(() => ({}));

            // Atualiza CSRF (cliente + ordem)
            if (json.csrf) {
                const csrfInputCliente = form.querySelector(`input[name="${CSRF_NAME}"]`);
                if (csrfInputCliente) csrfInputCliente.value = json.csrf;

                document.querySelectorAll(`input[name="${CSRF_NAME}"]`)
                    .forEach(el => el.value = json.csrf);
            }

            if (!res.ok || !json.ok) {
                const errs = json.errors || {
                    geral: 'Erro ao salvar.'
                };
                errorsBox.innerHTML = Object.values(errs).map(msg => `<div>${msg}</div>`).join('');
                errorsBox.classList.remove('d-none');
                return;
            }

            // Sucesso
            if (clienteSelect) {
                const opt = new Option(json.nome, json.id, true, true);
                clienteSelect.add(opt);
                clienteSelect.dispatchEvent(new Event('change'));
            }

            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            form.reset();
        });
    });
</script>

<?= $this->endSection() ?>
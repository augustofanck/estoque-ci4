<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = isset($ordem['id']);
$action = $isEdit ? site_url('ordens/' . $ordem['id'] . '/update') : site_url('ordens');
?>

<h1 class="h3 mb-3"><?= $isEdit ? 'Editar Ordem' : 'Nova Ordem' ?></h1>

<form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>

    <!-- Identificação -->
    <div class="card mb-3">
        <div class="card-header fw-semibold">Identificação</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data da compra</label>
                    <input type="text" name="data_compra" class="form-control date-mask"
                        placeholder="dd/mm/aaaa" inputmode="numeric" maxlength="10"
                        value="<?= old('data_compra', $ordem['data_compra'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">O.S. (nº)</label>
                    <input type="text" name="ordem_servico" class="form-control"
                        placeholder="Ex.: 1245"
                        value="<?= old('ordem_servico', $ordem['ordem_servico'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cliente <span class="text-danger">*</span></label>
                    <select name="cliente_id" id="cliente_id" class="form-select" required>
                        <option value="">Selecione um cliente...</option>
                        <?php foreach (($clientes ?? []) as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"
                                <?= isset($ordem['cliente_id']) && (int)$ordem['cliente_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Valores e Pagamento -->
    <div class="card mb-3">
        <div class="card-header fw-semibold">Valores e Pagamento</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Valor de venda</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_venda" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_venda', $ordem['valor_venda'] ?? '0,00') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Entrada</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_entrada" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_entrada', $ordem['valor_entrada'] ?? '0,00') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Forma da entrada</label>
                    <select name="forma_pagamento_entrada" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="Pix" <?= old('forma_pagamento_entrada', $ordem['forma_pagamento_entrada'] ?? '') === 'Pix' ? 'selected' : '' ?>>Pix</option>
                        <option value="Cartão" <?= old('forma_pagamento_entrada', $ordem['forma_pagamento_entrada'] ?? '') === 'Cartão' ? 'selected' : '' ?>>Cartão</option>
                        <option value="Dinheiro" <?= old('forma_pagamento_entrada', $ordem['forma_pagamento_entrada'] ?? '') === 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Total pago</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_pago" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_pago', $ordem['valor_pago'] ?? '0,00') ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Formas de pagamento</label>
                    <select name="formas_pagamento" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="Pix" <?= old('formas_pagamento', $ordem['formas_pagamento'] ?? '') === 'Pix' ? 'selected' : '' ?>>Pix</option>
                        <option value="Cartão" <?= old('formas_pagamento', $ordem['formas_pagamento'] ?? '') === 'Cartão' ? 'selected' : '' ?>>Cartão</option>
                        <option value="Dinheiro" <?= old('formas_pagamento', $ordem['formas_pagamento'] ?? '') === 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                    </select>
                    <div class="form-text">Ex.: Pix, Cartão (3x), Dinheiro.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Valor da Consulta</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="consulta" class="form-control"
                            placeholder="0,00"
                            value="<?= old('consulta', $ordem['consulta'] ?? '0,00') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Itens e Lentes -->
    <div class="card mb-3">
        <div class="card-header fw-semibold">Itens e Lentes</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Armação 1 (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_armacao_1" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_armacao_1', $ordem['valor_armacao_1'] ?? '0,00') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Armação 2 (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_armacao_2" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_armacao_2', $ordem['valor_armacao_2'] ?? '0,00') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lente 1 (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_lente_1" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_lente_1', $ordem['valor_lente_1'] ?? '0,00') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lente 2 (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" inputmode="decimal" name="valor_lente_2" class="form-control"
                            placeholder="0,00"
                            value="<?= old('valor_lente_2', $ordem['valor_lente_2'] ?? '0,00') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipo de lente 1</label>
                    <input type="text" name="tipo_lente_1" class="form-control"
                        placeholder="Ex.: monofocal / antirreflexo"
                        value="<?= old('tipo_lente_1', $ordem['tipo_lente_1'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipo de lente 2</label>
                    <input type="text" name="tipo_lente_2" class="form-control"
                        placeholder="Ex.: multifocal / blue light"
                        value="<?= old('tipo_lente_2', $ordem['tipo_lente_2'] ?? '') ?>">
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
                            value="<?= old('pagamento_laboratorio', $ordem['pagamento_laboratorio'] ?? '0,00') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dia do pagamento (lab.)</label>
                    <input type="text" name="dia_pagamento_laboratorio" class="form-control date-mask"
                        placeholder="dd/mm/aaaa" inputmode="numeric" maxlength="10"
                        value="<?= old('dia_pagamento_laboratorio', $ordem['dia_pagamento_laboratorio'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Recebimento do óculos (lab.)</label>
                    <input type="text" name="data_recebimento_laboratorio" class="form-control date-mask"
                        placeholder="dd/mm/aaaa" inputmode="numeric" maxlength="10"
                        value="<?= old('data_recebimento_laboratorio', $ordem['data_recebimento_laboratorio'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Entrega do óculos</label>
                    <input type="text" name="data_entrega_oculos" class="form-control date-mask"
                        placeholder="dd/mm/aaaa" inputmode="numeric" maxlength="10"
                        value="<?= old('data_entrega_oculos', $ordem['data_entrega_oculos'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Fiscal -->
    <div class="card mb-4">
        <div class="card-header fw-semibold">Fiscal</div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="nota_gerada" name="nota_gerada"
                            <?= old('nota_gerada', $ordem['nota_gerada'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="nota_gerada">Nota gerada?</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dia da nota</label>
                    <input type="text" name="dia_nota" class="form-control date-mask"
                        placeholder="dd/mm/aaaa" inputmode="numeric" maxlength="10"
                        value="<?= old('dia_nota', $ordem['dia_nota'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vendedor</label>
                    <input type="text" name="vendedor" class="form-control"
                        placeholder="Nome do Vendedor"
                        value="<?= old('vendedor', $ordem['vendedor'] ?? '') ?>">
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

<?= $this->endSection() ?>
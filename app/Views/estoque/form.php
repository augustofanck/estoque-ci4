<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errors  = session()->getFlashdata('errors') ?? [];

$isEdit = !empty($item) && !empty($item['id']);
$id = $isEdit ? (int)$item['id'] : null;

$action = $isEdit
    ? site_url('estoque/' . $id . '/update')
    : site_url('estoque');

$atributosValue = '';
if ($isEdit && !empty($item['atributos'])) {
    $atributosValue = is_string($item['atributos']) ? $item['atributos'] : json_encode($item['atributos'], JSON_UNESCAPED_UNICODE);
}
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><?= $isEdit ? 'Editar item' : 'Novo item' ?></h4>
            <div class="text-muted small">Cadastre e mantenha seus itens organizados</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('estoque') ?>" class="btn btn-outline-secondary">← Voltar</a>
            <a href="<?= site_url('estoque-tipos') ?>" class="btn btn-outline-secondary">🏷️ Tipos</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors) && is_array($errors)): ?>
        <div class="alert alert-warning">
            <strong>Verifique os campos:</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $msg): ?>
                    <li><?= esc($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $action ?>" class="card shadow-sm border-0">
        <?= csrf_field() ?>

        <div class="card-header bg-white">
            <strong>🧾 Dados do item</strong>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Código *</label>
                    <input
                        type="text"
                        name="codigo"
                        class="form-control"
                        value="<?= esc(old('codigo', $item['codigo'] ?? '')) ?>"
                        maxlength="80"
                        required
                        placeholder="Ex.: ARM-0001">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo *</label>
                    <select name="tipo_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach (($tipos ?? []) as $t): ?>
                            <?php
                            $tid = (int)$t['id'];
                            $selected = (string)old('tipo_id', $item['tipo_id'] ?? '') === (string)$tid ? 'selected' : '';
                            ?>
                            <option value="<?= $tid ?>" <?= $selected ?>>
                                <?= esc($t['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Não achou o tipo? <a href="<?= site_url('estoque-tipos/create') ?>">Criar tipo</a>
                    </div>
                </div>

                <!-- <div class="col-md-4">
                    <label class="form-label">Categoria</label>
                    <input
                        type="text"
                        name="categoria"
                        class="form-control"
                        value="<?= esc(old('categoria', $item['categoria'] ?? 'armacao')) ?>"
                        maxlength="60"
                        placeholder="armacao">
                    <div class="form-text">Campo base para futuros itens (lentes, acessórios, etc.).</div>
                </div> -->

                <div class="col-md-8">
                    <label class="form-label">Título</label>
                    <input
                        type="text"
                        name="titulo"
                        class="form-control"
                        value="<?= esc(old('titulo', $item['titulo'] ?? '')) ?>"
                        maxlength="160"
                        placeholder="Ex.: Armação preta acetato">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Qtd atual</label>
                    <input
                        type="number"
                        name="qtd_atual"
                        class="form-control"
                        value="<?= esc(old('qtd_atual', $item['qtd_atual'] ?? 0)) ?>"
                        step="1">
                    <div class="form-text">Preferível ajustar via “Movimentar”.</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Qtd mínima</label>
                    <input
                        type="number"
                        name="qtd_minima"
                        class="form-control"
                        value="<?= esc(old('qtd_minima', $item['qtd_minima'] ?? 0)) ?>"
                        step="1">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <?php $ativoVal = (int)old('ativo', $item['ativo'] ?? 1); ?>
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo"
                            name="ativo" value="1" <?= $ativoVal === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Ativo</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-between">
            <a href="<?= site_url('estoque') ?>" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Salvar alterações' : 'Criar item' ?>
            </button>
        </div>
    </form>

</div>

<?= $this->endSection() ?>
<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errors  = session()->getFlashdata('errors') ?? [];

$isEdit = !empty($tipo) && !empty($tipo['id']);
$id = $isEdit ? (int)$tipo['id'] : null;

$action = $isEdit
    ? site_url('estoque-tipos/' . $id . '/update')
    : site_url('estoque-tipos');
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><?= $isEdit ? 'Editar tipo' : 'Novo tipo' ?></h4>
            <div class="text-muted small">Tipos alimentam o select do item no estoque</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('estoque-tipos') ?>" class="btn btn-outline-secondary">← Voltar</a>
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
            <strong>🏷️ Dados do tipo</strong>
        </div>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input
                        type="text"
                        name="nome"
                        class="form-control"
                        value="<?= esc(old('nome', $tipo['nome'] ?? '')) ?>"
                        maxlength="120"
                        required
                        placeholder="Ex.: Armação - Metal">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Descrição</label>
                    <input
                        type="text"
                        name="descricao"
                        class="form-control"
                        value="<?= esc(old('descricao', $tipo['descricao'] ?? '')) ?>"
                        maxlength="255"
                        placeholder="Opcional">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <?php $ativoVal = (int)old('ativo', $tipo['ativo'] ?? 1); ?>
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo"
                            name="ativo" value="1" <?= $ativoVal === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Ativo</label>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer bg-white d-flex justify-content-between">
            <a href="<?= site_url('estoque-tipos') ?>" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Salvar alterações' : 'Criar tipo' ?>
            </button>
        </div>
    </form>

</div>

<?= $this->endSection() ?>
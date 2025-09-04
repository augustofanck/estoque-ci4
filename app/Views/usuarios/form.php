<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = !empty($user['id']);
$action = $isEdit ? site_url('usuarios/' . $user['id'] . '/update') : site_url('usuarios/store');
?>

<h1 class="h4 mb-3"><?= $isEdit ? 'Editar Usuário' : 'Novo Usuário' ?></h1>

<?php if ($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome*</label>
                    <input name="name" class="form-control" required
                        value="<?= esc(old('name', $user['name'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail*</label>
                    <input name="email" type="email" class="form-control" required
                        value="<?= esc(old('email', $user['email'] ?? '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Papel*</label>
                    <?php $roleVal = (string) old('role', (string)($user['role'] ?? '0')); ?>
                    <select name="role" class="form-select" required>
                        <option value="2" <?= $roleVal === '2' ? 'selected' : '' ?>>Admin</option>
                        <option value="1" <?= $roleVal === '1' ? 'selected' : '' ?>>Gerente</option>
                        <option value="0" <?= $roleVal === '0' ? 'selected' : '' ?>>Vendedor</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                            <?= (int) old('is_active', (int)($user['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Ativo</label>
                    </div>
                </div>

                <div class="col-md-4"></div>

                <div class="col-md-6">
                    <label class="form-label"><?= $isEdit ? 'Nova senha' : 'Senha*' ?></label>
                    <input name="password" type="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
                    <?php if ($isEdit): ?>
                        <div class="form-text">Deixe em branco para manter a senha atual.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= $isEdit ? 'Confirmar nova senha' : 'Confirmar senha*' ?></label>
                    <input name="password_confirm" type="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="<?= site_url('usuarios') ?>" class="btn btn-outline-secondary">Voltar</a>
        <button class="btn btn-primary"><?= $isEdit ? 'Salvar alterações' : 'Criar usuário' ?></button>
    </div>
</form>

<?= $this->endSection() ?>
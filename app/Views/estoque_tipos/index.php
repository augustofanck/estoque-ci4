<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errors  = session()->getFlashdata('errors') ?? [];
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Tipos de Estoque</h4>
            <div class="text-muted small">Tipos dinâmicos (ex.: Armação, Infantil, Metal, etc.)</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('estoque') ?>" class="btn btn-outline-secondary">← Voltar</a>
            <a href="<?= site_url('estoque-tipos/create') ?>" class="btn btn-primary">➕ Novo tipo</a>
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

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <strong>🏷️ Lista de tipos</strong>
        </div>

        <div class="card-body p-0">
            <?php if (!empty($tipos)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:80px;">#</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th style="width:120px;" class="text-center">Status</th>
                                <th style="width:220px;" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipos as $t): ?>
                                <?php
                                $id = (int)($t['id'] ?? 0);
                                $ativo = (int)($t['ativo'] ?? 1);
                                ?>
                                <tr>
                                    <td class="text-muted">#<?= $id ?></td>
                                    <td class="fw-semibold"><?= esc($t['nome'] ?? '') ?></td>
                                    <td><?= esc($t['descricao'] ?? '—') ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $ativo ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $ativo ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="<?= site_url('estoque-tipos/' . $id . '/edit') ?>" class="btn btn-outline-secondary btn-sm">
                                                ✏️ Editar
                                            </a>
                                            <a href="<?= site_url('estoque-tipos/' . $id . '/delete') ?>"
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Remover este tipo? Se houver itens vinculados, não será permitido.');">
                                                🗑️ Excluir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    Nenhum tipo cadastrado.
                    <div class="mt-2">
                        <a class="btn btn-primary btn-sm" href="<?= site_url('estoque-tipos/create') ?>">➕ Criar primeiro tipo</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
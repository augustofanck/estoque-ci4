<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Usuários</h1>
    <a href="<?= site_url('usuarios/create') ?>" class="btn btn-primary">Novo Usuário</a>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Papel</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): foreach ($users as $u): ?>
                        <tr>
                            <td><?= (int) $u['id'] ?></td>
                            <td><?= esc($u['name'] ?? '-') ?></td>
                            <td><?= esc($u['email'] ?? '-') ?></td>
                            <td>
                                <?php
                                $rmap = [0 => 'Vendedor', 1 => 'Gerente', 2 => 'Admin'];
                                $badge = ['secondary', 'info', 'primary'][(int)($u['role'] ?? 0)] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= esc($rmap[(int)($u['role'] ?? 0)] ?? '—') ?></span>
                            </td>
                            <td>
                                <?php if ((int)($u['is_active'] ?? 0) === 1): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('usuarios/' . $u['id'] . '/edit') ?>">Editar</a>
                                <a class="btn btn-sm btn-outline-danger" href="<?= site_url('usuarios/' . $u['id'] . '/delete') ?>"
                                    onclick="return confirm('Excluir este usuário?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Nenhum usuário.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
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
            <h4 class="mb-0">Estoque</h4>
            <div class="text-muted small">Gerencie itens, tipos e movimentações</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('estoque/relatorios') ?>" class="btn btn-outline-secondary">
                📊 Relatórios
            </a>
            <a href="<?= site_url('estoque-tipos') ?>" class="btn btn-outline-secondary">
                🏷️ Tipos
            </a>
            <a href="<?= site_url('estoque/create') ?>" class="btn btn-primary">
                ➕ Novo item
            </a>
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
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>📦 Itens cadastrados</strong>
            <span class="small text-muted">Clique em “Movimentar” para entrada/saída/ajuste</span>
        </div>

        <div class="card-body p-0">
            <?php if (!empty($itens)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:80px;">#</th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th class="text-center" style="width:110px;">Qtd</th>
                                <th class="text-center" style="width:120px;">Mínimo</th>
                                <th class="text-center" style="width:120px;">Status</th>
                                <th class="text-end" style="width:320px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $i): ?>
                                <?php
                                $id        = (int)($i['id'] ?? 0);
                                $codigo    = (string)($i['codigo'] ?? '');
                                $tipoNome  = (string)($i['tipo_nome'] ?? '—');
                                $titulo    = (string)($i['titulo'] ?? '');
                                $qtd       = (int)($i['qtd_atual'] ?? 0);
                                $min       = (int)($i['qtd_minima'] ?? 0);
                                $ativo     = (int)($i['ativo'] ?? 1);

                                $abaixoMin = $min > 0 && $qtd < $min;
                                $badgeClass = !$ativo ? 'bg-secondary' : ($abaixoMin ? 'bg-danger' : 'bg-success');
                                $badgeText  = !$ativo ? 'Inativo' : ($abaixoMin ? 'Baixo' : 'OK');

                                $modalId = 'movItem' . $id;
                                ?>
                                <tr>
                                    <td class="text-muted">#<?= $id ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($codigo) ?></div>
                                        <div class="small text-muted"><?= esc($i['categoria'] ?? 'armacao') ?></div>
                                    </td>
                                    <td><?= esc($tipoNome) ?></td>
                                    <td><?= esc($titulo ?: '—') ?></td>
                                    <td class="text-center fw-bold"><?= $qtd ?></td>
                                    <td class="text-center"><?= $min ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $badgeClass ?>"><?= esc($badgeText) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#<?= $modalId ?>">
                                                🔁 Movimentar
                                            </button>

                                            <a href="<?= site_url('estoque/' . $id . '/edit') ?>" class="btn btn-outline-secondary btn-sm">
                                                ✏️ Editar
                                            </a>

                                            <a href="<?= site_url('estoque/' . $id . '/delete') ?>"
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Remover este item do estoque?');">
                                                🗑️ Excluir
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal movimentação -->
                                <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post" action="<?= site_url('estoque/' . $id . '/movimentar') ?>">
                                                <?= csrf_field() ?>

                                                <div class="modal-header">
                                                    <div>
                                                        <h5 class="modal-title mb-0">Movimentar item</h5>
                                                        <div class="small text-muted">
                                                            <?= esc($codigo) ?> <?= $titulo ? '— ' . esc($titulo) : '' ?>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label class="form-label">Tipo de movimentação</label>
                                                            <select name="tipo" class="form-select" required>
                                                                <option value="E">Entrada (+)</option>
                                                                <option value="S">Saída (-)</option>
                                                                <option value="A">Ajuste (define a quantidade exata)</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label">Quantidade</label>
                                                            <input type="number" name="quantidade" class="form-control" min="1" step="1" required>
                                                            <div class="form-text">
                                                                Para “Ajuste”, informe o valor final desejado (ex.: 12).
                                                            </div>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label">Motivo (opcional)</label>
                                                            <input type="text" name="motivo" class="form-control" maxlength="255"
                                                                placeholder="Ex.: compra, devolução, perda, conferência...">
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label">Referência (opcional)</label>
                                                            <input type="text" name="referencia" class="form-control" maxlength="80"
                                                                placeholder="Ex.: OS 123 / NF 456 / Ordem #...">
                                                        </div>

                                                        <div class="col-12">
                                                            <div class="alert alert-light mb-0">
                                                                <div class="small text-muted">Saldo atual</div>
                                                                <div class="fw-bold"><?= $qtd ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Salvar movimentação</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Modal -->

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    Sem itens no estoque.
                    <div class="mt-2">
                        <a class="btn btn-primary btn-sm" href="<?= site_url('estoque/create') ?>">➕ Cadastrar primeiro item</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
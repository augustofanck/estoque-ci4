<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3"><?= esc($title ?? 'Cliente') ?></h1>

<form method="post" action="<?= isset($cliente['id']) ? site_url('clientes/' . $cliente['id'] . '/update') : site_url('clientes/store') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nome*</label>
            <input name="nome" class="form-control" required value="<?= esc($cliente['nome'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Documento</label>
            <input name="documento" class="form-control" value="<?= esc($cliente['documento'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Telefone</label>
            <input name="telefone" class="form-control" value="<?= esc($cliente['telefone'] ?? '') ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input name="email" type="email" class="form-control" value="<?= esc($cliente['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Endereço</label>
            <input name="endereco" class="form-control" value="<?= esc($cliente['endereco'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Cidade</label>
            <input name="cidade" class="form-control" value="<?= esc($cliente['cidade'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">UF</label>
            <input name="estado" class="form-control" maxlength="2" value="<?= esc($cliente['estado'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">CEP</label>
            <input name="cep" class="form-control" value="<?= esc($cliente['cep'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Término do contrato</label>
            <input name="termino_contrato" class="form-control date-mask" placeholder="dd/mm/aaaa" value="<?= esc($cliente['termino_contrato'] ?? '') ?>">
        </div>

        <div class="col-12">
            <button class="btn btn-primary"><?= isset($cliente['id']) ? 'Salvar' : 'Criar' ?></button>
            <a href="<?= site_url('clientes') ?>" class="btn btn-light">Cancelar</a>
        </div>
    </div>
</form>

<?= $this->endSection() ?>
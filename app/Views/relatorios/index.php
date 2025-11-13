<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="main-content">

    <h1><?= esc($titulo) ?></h1>

    <form action="<?= base_url('relatorios/ordens') ?>" method="get" class="row g-3">

        <div class="col-md-4">
            <label for="tipo_relatorio" class="form-label">Tipo de relatório</label>
            <select name="tipo_relatorio" id="tipo_relatorio" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($tipos as $key => $label): ?>
                    <option value="<?= esc($key) ?>">
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label for="data_inicio" class="form-label">Data inicial</label>
            <input type="date" name="data_inicio" id="data_inicio" class="form-control" required>
        </div>

        <div class="col-md-3">
            <label for="data_fim" class="form-label">Data final</label>
            <input type="date" name="data_fim" id="data_fim" class="form-control" required>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
                Gerar relatório
            </button>
        </div>

    </form>

</div>

<?= $this->endSection() ?>
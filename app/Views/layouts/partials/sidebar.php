<div class="card sidebar">
    <div class="card-body">
        <h6 class="text-uppercase text-muted mb-3">Navegação</h6>

        <div class="mb-2">
            <button class="btn btn-light btn-toggle text-start w-100" data-bs-toggle="collapse" data-bs-target="#secDashboard" aria-expanded="<?= $seg1 === '' ? 'true' : 'false' ?>">Dashboard</button>
            <div id="secDashboard" class="collapse <?= $seg1 === '' ? 'show' : '' ?>">
                <div class="list-group ms-2 mt-2">
                    <a href="<?= site_url('/') ?>" class="list-group-item list-group-item-action <?= $seg1 === '' ? 'active' : '' ?>">Início</a>
                </div>
            </div>
        </div>

        <div class="mb-2">
            <button class="btn btn-light btn-toggle text-start w-100" data-bs-toggle="collapse" data-bs-target="#secOrdens" aria-expanded="<?= $seg1 === 'ordens' ? 'true' : 'false' ?>">Ordens</button>
            <div id="secOrdens" class="collapse <?= $showIf('ordens') ?>">
                <div class="list-group ms-2 mt-2">
                    <a href="<?= site_url('ordens') ?>" class="list-group-item list-group-item-action <?= $is('ordens') && $seg2 === '' ? 'active' : '' ?>">Listar</a>
                    <a href="<?= site_url('ordens/create') ?>" class="list-group-item list-group-item-action <?= $is('ordens', 'create') ? 'active' : '' ?>">Novo</a>
                </div>
            </div>
        </div>

        <div class="mb-2">
            <button class="btn btn-light btn-toggle text-start w-100" data-bs-toggle="collapse" data-bs-target="#secClientes" aria-expanded="<?= $seg1 === 'clientes' ? 'true' : 'false' ?>">Clientes</button>
            <div id="secClientes" class="collapse <?= $showIf('clientes') ?>">
                <div class="list-group ms-2 mt-2">
                    <a href="<?= site_url('clientes') ?>" class="list-group-item list-group-item-action <?= $is('clientes') && $seg2 === '' ? 'active' : '' ?>">Listar</a>
                    <a href="<?= site_url('clientes/create') ?>" class="list-group-item list-group-item-action <?= $is('clientes', 'create') ? 'active' : '' ?>">Novo</a>
                </div>
            </div>
        </div>

        <div class="mb-2">
            <button class="btn btn-light btn-toggle text-start w-100" data-bs-toggle="collapse" data-bs-target="#secRelatorios" aria-expanded="<?= $seg1 === 'relatorios' ? 'true' : 'false' ?>">Relatórios</button>
            <div id="secRelatorios" class="collapse <?= $showIf('relatorios') ?>">
                <div class="list-group ms-2 mt-2">
                    <a href="<?= site_url('relatorios') ?>" class="list-group-item list-group-item-action <?= $is('relatorios') && $seg2 === '' ? 'active' : '' ?>">Visão geral</a>
                    <a class="list-group-item list-group-item-action disabled">Em breve</a>
                </div>
            </div>
        </div>
    </div>
</div>
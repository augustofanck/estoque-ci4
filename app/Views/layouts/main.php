<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title><?= esc($title ?? 'App') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media (min-width: 992px) {
            .sidebar {
                position: sticky;
                top: 1rem;
                height: calc(100vh - 2rem);
                overflow-y: auto;
            }
        }

        body {
            background: #f8f9fa;
        }

        .sidebar .list-group-item {
            border: 0;
        }

        .sidebar .list-group-item.active {
            background: #0d6efd;
        }

        .btn-toggle {
            width: 100%;
            text-align: left;
        }

        .btn-toggle::after {
            content: '▸';
            float: right;
            transform: rotate(0deg);
            transition: transform .15s;
        }

        .btn-toggle[aria-expanded="true"]::after {
            transform: rotate(90deg);
        }
    </style>
</head>

<body>
    <?php
    // Pega todos os segmentos e usa fallback seguro
    $uri      = service('uri');
    $segments = $uri->getSegments(); // array: [seg1, seg2, ...] (sem barra inicial)

    $seg1 = strtolower($segments[0] ?? ''); // 1º segmento ou ''
    $seg2 = strtolower($segments[1] ?? ''); // 2º segmento ou ''

    $is = function ($ctrl, $act = null) use ($seg1, $seg2) {
        $ctrl = strtolower($ctrl);
        if ($act === null) {
            return $seg1 === $ctrl;
        }
        return $seg1 === $ctrl && $seg2 === strtolower($act);
    };

    $showIf = function ($ctrl) use ($seg1) {
        return $seg1 === strtolower($ctrl) ? 'show' : '';
    };
    ?>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= site_url('/') ?>">Lumina</a>

            <!-- Botão do menu (mobile) -->
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="container-fluid my-3">
        <div class="row">
            <!-- Sidebar (desktop) -->
            <aside class="col-lg-2 d-none d-lg-block">
                <div class="card sidebar">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted mb-3">Navegação</h6>

                        <!-- Seções colapsáveis -->
                        <div class="mb-2">
                            <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#secDashboard"
                                aria-expanded="<?= $seg1 === '' ? 'true' : 'false' ?>">
                                Dashboard
                            </button>
                            <div id="secDashboard" class="collapse <?= $seg1 === '' ? 'show' : '' ?>">
                                <div class="list-group ms-2 mt-2">
                                    <a href="<?= site_url('/') ?>" class="list-group-item list-group-item-action <?= $seg1 === '' ? 'active' : '' ?>">Início</a>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#secOrdens"
                                aria-expanded="<?= $seg1 === 'ordens' ? 'true' : 'false' ?>">
                                Ordens
                            </button>
                            <div id="secOrdens" class="collapse <?= $showIf('ordens') ?>">
                                <div class="list-group ms-2 mt-2">
                                    <a href="<?= site_url('ordens') ?>" class="list-group-item list-group-item-action <?= $is('ordens') && $seg2 === '' ? 'active' : '' ?>">Listar</a>
                                    <a href="<?= site_url('ordens/create') ?>" class="list-group-item list-group-item-action <?= $is('ordens', 'create') ? 'active' : '' ?>">Novo</a>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#secClientes"
                                aria-expanded="<?= $seg1 === 'clientes' ? 'true' : 'false' ?>">
                                Clientes
                            </button>
                            <div id="secClientes" class="collapse <?= $showIf('clientes') ?>">
                                <div class="list-group ms-2 mt-2">
                                    <a href="<?= site_url('clientes') ?>" class="list-group-item list-group-item-action <?= $is('clientes') && $seg2 === '' ? 'active' : '' ?>">Listar</a>
                                    <a href="<?= site_url('clientes/create') ?>" class="list-group-item list-group-item-action <?= $is('clientes', 'create') ? 'active' : '' ?>">Novo</a>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#secRelatorios"
                                aria-expanded="<?= $seg1 === 'relatorios' ? 'true' : 'false' ?>">
                                Relatórios
                            </button>
                            <div id="secRelatorios" class="collapse <?= $showIf('relatorios') ?>">
                                <div class="list-group ms-2 mt-2">
                                    <a href="<?= site_url('relatorios') ?>" class="list-group-item list-group-item-action <?= $is('relatorios') && $seg2 === '' ? 'active' : '' ?>">Visão geral</a>
                                    <a class="list-group-item list-group-item-action disabled">Em breve</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Sidebar (mobile/offcanvas) -->
            <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
                </div>
                <div class="offcanvas-body">
                    <div class="mb-2">
                        <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#mSecDashboard"
                            aria-expanded="<?= $seg1 === '' ? 'true' : 'false' ?>">
                            Dashboard
                        </button>
                        <div id="mSecDashboard" class="collapse <?= $seg1 === '' ? 'show' : '' ?>">
                            <div class="list-group ms-2 mt-2">
                                <a href="<?= site_url('/') ?>" class="list-group-item list-group-item-action <?= $seg1 === '' ? 'active' : '' ?>">Início</a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#mSecOrdens"
                            aria-expanded="<?= $seg1 === 'ordens' ? 'true' : 'false' ?>">
                            Ordens
                        </button>
                        <div id="mSecOrdens" class="collapse <?= $showIf('ordens') ?>">
                            <div class="list-group ms-2 mt-2">
                                <a href="<?= site_url('ordens') ?>" class="list-group-item list-group-item-action <?= $is('ordens') && $seg2 === '' ? 'active' : '' ?>">Listar</a>
                                <a href="<?= site_url('ordens/create') ?>" class="list-group-item list-group-item-action <?= $is('ordens', 'create') ? 'active' : '' ?>">Novo</a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#mSecClientes"
                            aria-expanded="<?= $seg1 === 'clientes' ? 'true' : 'false' ?>">
                            Clientes
                        </button>
                        <div id="mSecClientes" class="collapse <?= $showIf('clientes') ?>">
                            <div class="list-group ms-2 mt-2">
                                <a href="<?= site_url('clientes') ?>" class="list-group-item list-group-item-action <?= $is('clientes') && $seg2 === '' ? 'active' : '' ?>">Listar</a>
                                <a href="<?= site_url('clientes/create') ?>" class="list-group-item list-group-item-action <?= $is('clientes', 'create') ? 'active' : '' ?>">Novo</a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-light btn-toggle" data-bs-toggle="collapse" data-bs-target="#mSecRelatorios"
                            aria-expanded="<?= $seg1 === 'relatorios' ? 'true' : 'false' ?>">
                            Relatórios
                        </button>
                        <div id="mSecRelatorios" class="collapse <?= $showIf('relatorios') ?>">
                            <div class="list-group ms-2 mt-2">
                                <a href="<?= site_url('relatorios') ?>" class="list-group-item list-group-item-action <?= $is('relatorios') && $seg2 === '' ? 'active' : '' ?>">Visão geral</a>
                                <a class="list-group-item list-group-item-action disabled">Em breve</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conteúdo -->
            <main class="col-12 col-lg-10">
                <?php if (session()->getFlashdata('msg')): ?>
                    <div class="alert alert-success"><?= esc(session('msg')) ?></div>
                <?php endif; ?>

                <?php if ($errors = session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/inputmask@5.0.9/dist/inputmask.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".date-mask").forEach(function(el) {
                const v = (el.value || "").trim();
                if (/^\d{4}-\d{2}-\d{2}$/.test(v)) {
                    const [y, m, d] = v.split("-");
                    el.value = `${d}/${m}/${y}`;
                }
            });
            Inputmask({
                    mask: "99/99/9999",
                    clearIncomplete: true,
                    showMaskOnFocus: false,
                    showMaskOnHover: false
                })
                .mask(document.querySelectorAll(".date-mask"));

            const docCpf = document.getElementById('documento'); // máscara CPF
            if (!docCpf) return;

            Inputmask({
                mask: "999.999.999-99",
                clearIncomplete: true,
                showMaskOnFocus: true,
                showMaskOnHover: false,
                onBeforePaste: v => (v || "").replace(/\D+/g, "")
            }).mask(docCpf);

            const cel = document.getElementById('telefone');
            if (!cel) return;

            Inputmask({
                mask: ["(99) 9999-9999", "(99) 99999-9999"], // 10 ou 11 dígitos
                keepStatic: true,
                clearIncomplete: true,
                showMaskOnFocus: true,
                showMaskOnHover: false,
                onBeforePaste: v => (v || "").replace(/\D+/g, "")
            }).mask(cel);

            const cep = document.getElementById('cep');
            if (!cep) return;

            Inputmask({
                mask: "99999-999",
                keepStatic: true,
                clearIncomplete: true,
                showMaskOnFocus: true,
                showMaskOnHover: false,
                onBeforePaste: v => (v || "").replace(/\D+/g, "")
            }).mask(cep);
        });
    </script>
</body>

</html>
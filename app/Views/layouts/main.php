<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title><?= esc($title ?? 'App') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link href="<?= base_url('css/app.css') ?>" rel="stylesheet">
    <link rel="icon" href="<?= base_url('assets/images/logo2.png') ?>" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <?= $this->renderSection('head') ?>
</head>
<?php
$uri = service('uri');
$segments = $uri->getSegments();
$seg1 = strtolower($segments[0] ?? '');
$seg2 = strtolower($segments[1] ?? '');
$is = function ($ctrl, $act = null) use ($seg1, $seg2) {
    $ctrl = strtolower($ctrl);
    return $act === null ? ($seg1 === $ctrl) : ($seg1 === $ctrl && $seg2 === strtolower($act));
};
$showIf = function ($ctrl) use ($seg1) {
    return $seg1 === strtolower($ctrl) ? 'show' : '';
};
?>

<body>
    <a class="visually-hidden-focusable position-absolute p-2" href="#main">Ir para conteúdo</a>

    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= site_url('/') ?>">
                <img src="<?= base_url('assets/images/logo2.png') ?>" alt="Lumina" width="32" height="32" class="align-text-top">
                <span class="d-none d-sm-inline">Lumina</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <?php if (session()->get('uid')): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                            <?= esc(session('uname') ?? 'Usuário') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted"><?= esc(session('uemail') ?? '') ?></span></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="<?= site_url('logout') ?>">Sair</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline-light" href="<?= site_url('login') ?>">Entrar</a>
                <?php endif; ?>
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
    </nav>



    <div class="container-fluid main-container py-3">
        <div class="row g-3">
            <!-- Sidebar desktop -->
            <aside class="col-lg-2 d-none d-lg-block">
                <?= view('layouts/partials/sidebar', compact('seg1', 'seg2', 'is', 'showIf')) ?>
            </aside>

            <!-- Offcanvas mobile -->
            <?= view('layouts/partials/sidebar_offcanvas', compact('seg1', 'seg2', 'is', 'showIf')) ?>

            <!-- Conteúdo -->
            <main id="main" class="col-12 col-lg-10">
                <?php if (session()->getFlashdata('msg')): ?>
                    <div class="alert alert-success"><?= esc(session('msg')) ?></div>
                <?php endif; ?>
                <?php if ($errors = session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/inputmask@5.0.9/dist/inputmask.min.js"></script>
    <script src="<?= base_url('js/form-masks.js') ?>?v=<?= urlencode((string)ENVIRONMENT) ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.FormsMasks) FormsMasks.applyAll(document);
        });
    </script>
    <?= $this->renderSection('page_scripts') ?>

</body>

</html>
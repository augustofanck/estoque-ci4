<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title><?= esc($title ?? 'Entrar') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="<?= base_url('css/app.css') ?>" rel="stylesheet">
</head>

<body class="d-flex flex-column" style="min-height:100vh;">

    <!-- NAVBAR -->
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('/') ?>">Lumina</a>

            <?php if (session()->get('uid')): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
            <?php endif; ?>
        </div>
    </nav>

    <main class="container d-flex align-items-center justify-content-center flex-grow-1">
        <?= $this->renderSection('content') ?>
    </main>

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
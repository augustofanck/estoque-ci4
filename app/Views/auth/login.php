<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-center py-5">
    <div class="auth-wrap w-100" style="max-width: 420px;">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">Entrar</h1>

                <?php if ($errors = session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= site_url('login') ?>" id="loginForm" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input name="email" type="email" class="form-control" autofocus required value="<?= esc(old('email')) ?>">
                    </div>

                    <div class="mb-2 position-relative">
                        <label class="form-label">Senha</label>
                        <div class="input-group">
                            <input id="password" name="password" type="password" class="form-control" required placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;">
                            <button type="button" class="btn btn-outline-secondary" id="togglePwd" tabindex="-1">Mostrar</button>
                        </div>
                        <div id="capsWarn" class="form-text text-warning d-none">Caps Lock ativado.</div>
                    </div>

                    <div class="d-grid mt-3">
                        <button class="btn btn-primary" type="submit" id="btnLogin">Entrar</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="#" class="small text-muted disabled">Esqueci minha senha</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pwd = document.getElementById('password');
        const btn = document.getElementById('togglePwd');
        const caps = document.getElementById('capsWarn');
        btn.addEventListener('click', function() {
            const t = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
            pwd.setAttribute('type', t);
            btn.textContent = t === 'password' ? 'Mostrar' : 'Ocultar';
        });
        pwd.addEventListener('keydown', e => {
            caps.classList.toggle('d-none', !e.getModifierState || !e.getModifierState('CapsLock'));
        });
        pwd.addEventListener('keyup', e => {
            caps.classList.toggle('d-none', !e.getModifierState || !e.getModifierState('CapsLock'));
        });

        // feedback simples no submit
        const form = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btnLogin');
        form.addEventListener('submit', function() {
            btnLogin.disabled = true;
            btnLogin.textContent = 'Entrando...';
        });
    });
</script>
<?= $this->endSection() ?>
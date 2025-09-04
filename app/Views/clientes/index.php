<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Clientes</h1>
    <a href="<?= site_url('clientes/create') ?>" class="btn btn-primary">Novo Cliente</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <select id="field" class="form-select">
                    <option value="nome" <?= ($field ?? 'nome') === 'nome' ? 'selected' : '' ?>>Nome</option>
                    <option value="documento" <?= ($field ?? '') === 'documento' ? 'selected' : '' ?>>Documento</option>
                    <option value="email" <?= ($field ?? '') === 'email' ? 'selected' : '' ?>>E-mail</option>
                    <option value="telefone" <?= ($field ?? '') === 'telefone' ? 'selected' : '' ?>>Telefone</option>
                    <option value="cidade" <?= ($field ?? '') === 'cidade' ? 'selected' : '' ?>>Cidade</option>
                </select>
            </div>
            <div class="col-md-7">
                <input id="q" type="text" class="form-control" placeholder="Digite para filtrar..." value="<?= esc($q ?? '') ?>">
            </div>
            <div class="col-md-2 text-muted small text-md-end">Busca em tempo real</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Documento</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Cidade/UF</th>
                    <th>Término Contrato</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="rows">
                <?= view('clientes/_rows', ['clientes' => $clientes]) ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    (function() {
        const qEl = document.getElementById('q');
        const fEl = document.getElementById('field');
        const rows = document.getElementById('rows');
        const debounce = (fn, ms = 300) => {
            let t;
            return (...a) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...a), ms);
            }
        };
        async function fetchRows() {
            const params = new URLSearchParams({
                q: qEl.value.trim(),
                field: fEl.value
            });
            const url = '<?= site_url('clientes') ?>?' + params.toString();
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!res.ok) return;
            rows.innerHTML = await res.text();
            window.history.replaceState({}, '', url);
        }
        const run = debounce(fetchRows, 250);
        qEl.addEventListener('input', run);
        fEl.addEventListener('change', fetchRows);
    })();
</script>

<?= $this->endSection() ?>
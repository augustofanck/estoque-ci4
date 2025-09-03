<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Ordens / Estoque</h1>
    <a href="<?= site_url('ordens/create') ?>" class="btn btn-primary">Nova Ordem</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <select id="field" class="form-select">
                    <option value="nome_cliente" <?= ($field ?? 'nome_cliente') === 'nome_cliente' ? 'selected' : '' ?>>Nome do cliente</option>
                    <option value="ordem_servico" <?= ($field ?? '') === 'ordem_servico' ? 'selected' : '' ?>>Nº da O.S.</option>
                    <option value="vendedor" <?= ($field ?? '') === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                </select>
            </div>
            <div class="col-md-4">
                <input id="q" type="text" class="form-control"
                    placeholder="Digite para filtrar..."
                    value="<?= esc($q ?? '') ?>">
            </div>
            <div class="col-md-2 text-muted small text-md-end">
                Busca em tempo real
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Data compra</th>
                    <th>O.S.</th>
                    <th>Cliente</th>
                    <th>Vendedor</th> <!-- nova coluna -->
                    <th>Valor venda</th>
                    <th>Pago</th>
                    <th>Entrega óculos</th>
                    <th>Nota?</th>
                    <th>Laboratório?</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="rows">
                <?= view('ordens/_rows', ['ordens' => $ordens]) ?>
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
            const url = '<?= site_url('ordens') ?>?' + params.toString();
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
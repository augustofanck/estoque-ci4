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

            <div class="col-md-2">
                <input id="data_ini" type="date" class="form-control"
                    value="<?= esc($data_ini ?? '') ?>" placeholder="Início">
            </div>
            <div class="col-md-2">
                <input id="data_fim" type="date" class="form-control"
                    value="<?= esc($data_fim ?? '') ?>" placeholder="Fim">
            </div>

            <div class="col-md-1 d-grid">
                <button id="btnApplyDate" type="button" class="btn btn-outline-secondary btn-sm">
                    Aplicar
                </button>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col text-muted small">
                Busca em tempo real.
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
                    <th>Vendedor</th>
                    <th>Valor venda</th>
                    <th>Total pago</th>
                    <th>Saldo</th>
                    <th># pag.</th>
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
        const dIni = document.getElementById('data_ini');
        const dFim = document.getElementById('data_fim');
        const btn = document.getElementById('btnApplyDate');
        const rows = document.getElementById('rows');

        // estado: se o filtro de datas está ativo
        let appliedDate = <?= json_encode((string)($apply_date ?? '0')) ?> === '1';

        const debounce = (fn, ms = 300) => {
            let t;
            return (...a) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...a), ms);
            };
        };

        function buildParams(forceDate = false) {
            const params = new URLSearchParams({
                q: qEl.value.trim(),
                field: fEl.value
            });

            // inclui datas só quando clicar em Aplicar (ou se já estiver aplicado)
            if (forceDate || appliedDate) {
                params.set('apply_date', '1');
                params.set('data_ini', dIni.value || '');
                params.set('data_fim', dFim.value || '');
            }
            return params;
        }

        async function fetchRows(params) {
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

        // busca em tempo real para q/field; mantém datas se já aplicadas
        const run = debounce(() => fetchRows(buildParams(false)), 250);
        qEl.addEventListener('input', run);
        fEl.addEventListener('change', () => fetchRows(buildParams(false)));

        // botão "Aplicar": ativa o filtro de data_compra
        btn.addEventListener('click', () => {
            appliedDate = true;
            fetchRows(buildParams(true));
        });
    })();
</script>


<?= $this->endSection() ?>
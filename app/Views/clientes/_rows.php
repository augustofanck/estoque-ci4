<?php if (!empty($clientes)): foreach ($clientes as $c): ?>
        <tr>
            <td><?= esc($c['id']) ?></td>
            <td><?= esc($c['nome']) ?></td>
            <td>
                <?php
                $cpf = $c['documento'] ?? '';
                $cpfFmt = preg_match('/^\d{11}$/', $cpf)
                    ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf)
                    : $cpf;
                echo esc($cpfFmt ?: '—');
                ?>
            </td>
            <td><?= esc($c['telefone'] ?? '—') ?></td>
            <td><?= esc($c['email'] ?? '—') ?></td>
            <td><?= esc(($c['cidade'] ?? '—') . (isset($c['estado']) && $c['estado'] ? '/' . $c['estado'] : '')) ?></td>
            <td><?= !empty($c['termino_contrato']) ? date('d/m/Y', strtotime($c['termino_contrato'])) : '—' ?></td>
            <td class="text-nowrap">
                <a href="<?= site_url('clientes/' . $c['id'] . '/edit') ?>" class="btn btn-sm btn-warning">Editar</a>
                <a href="<?= site_url('clientes/' . $c['id'] . '/delete') ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Excluir este registro?')">Excluir</a>
            </td>
        </tr>
    <?php endforeach;
else: ?>
    <tr>
        <td colspan="8" class="text-center text-muted">Nenhum registro.</td>
    </tr>
<?php endif; ?>
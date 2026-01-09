<?php if (!empty($ordens)): foreach ($ordens as $o): ?>
        <tr>
            <td><?= esc($o['id']) ?></td>

            <!-- Data da compra -->
            <td>
                <?php if (!empty($o['data_compra'])): ?>
                    <?= date('d/m/Y', strtotime($o['data_compra'])) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>

            <td><?= esc($o['ordem_servico'] ?? '-') ?></td>
            <td><?= esc($o['cliente'] ?? '—') ?></td>

            <!-- NOVO: Vendedor -->
            <td><?= esc($o['vendedor'] ?? '—') ?></td>

            <?php
            $valorVenda = (float)($o['valor_venda'] ?? 0);
            $totalPago  = (float)($o['total_pago'] ?? 0);
            $saldo      = (float)($o['saldo'] ?? ($valorVenda - $totalPago));
            $qtdPag     = (int)($o['qtd_pagamentos'] ?? 0);

            $quitado = ($valorVenda > 0 && $saldo <= 0.0001);

            $badgeTotalPago =
                $quitado ? 'bg-success'
                : ($totalPago > 0 ? 'bg-warning text-dark' : 'bg-secondary');

            $badgeSaldo =
                ($saldo > 0.0001) ? 'bg-danger'
                : 'bg-success';

            $saldoDisplay = ($saldo < 0) ? 0 : $saldo;
            ?>

            <td>
                <span class="badge bg-secondary">
                    R$ <?= number_format($valorVenda, 2, ',', '.') ?>
                </span>
            </td>

            <td>
                <span class="badge <?= $badgeTotalPago ?>">
                    R$ <?= number_format($totalPago, 2, ',', '.') ?>
                </span>
            </td>

            <td>
                <span class="badge <?= $badgeSaldo ?>">
                    R$ <?= number_format($saldoDisplay, 2, ',', '.') ?>
                </span>
            </td>

            <td>
                <span class="badge bg-light text-dark">
                    <?= $qtdPag ?>
                </span>
            </td>



            <!-- Data entrega óculos -->
            <td>
                <?php if (!empty($o['data_entrega_oculos'])): ?>
                    <?= date('d/m/Y', strtotime($o['data_entrega_oculos'])) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>

            <td>
                <?php if ((int)$o['nota_gerada'] === 1): ?>
                    <span class="badge bg-success">Sim</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Não</span>
                <?php endif; ?>
            </td>

            <td>
                <?php if (!empty($o['pagamento_laboratorio']) && (float)$o['pagamento_laboratorio'] > 0): ?>
                    <span class="badge bg-success">Pago</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Não Pago</span>
                <?php endif; ?>
            </td>

            <td class="text-nowrap">
                <a href="<?= site_url('ordens/' . $o['id'] . '/edit') ?>" class="btn btn-sm btn-warning">Editar</a>
                <a href="<?= site_url('ordens/' . $o['id'] . '/delete') ?>"
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Excluir este registro?')">Excluir</a>
            </td>
        </tr>
    <?php endforeach;
else: ?>
    <tr>
        <td colspan="13" class="text-center text-muted">Nenhum registro.</td>
    </tr>
<?php endif; ?>
<h1>Pick list: all paid orders waiting to be packed</h1>
<p class="muted"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?> · printed <?= e(date('j M Y, H:i')) ?></p>
<?php if (!$agg): ?>
  <p>Nothing to pack right now.</p>
<?php else: ?>
  <h2>Items to take off the shelf, in shelf order</h2>
  <table>
    <thead><tr><th style="width:34px">✔</th><th>Shelf</th><th>Item</th><th>Colour</th><th>Size</th><th>SKU</th><th style="width:60px">Total qty</th><th>In orders</th></tr></thead>
    <tbody>
    <?php foreach ($agg as $r): ?>
      <tr><td><span class="box"></span></td><td><strong><?= e($r['bin_location'] ?: '-') ?></strong></td><td><?= e($r['name']) ?></td><td><?= e($r['colour']) ?></td><td><strong><?= e($r['size'] === 'STD' ? '—' : $r['size']) ?></strong></td><td><?= e($r['sku']) ?></td><td><strong><?= (int)$r['qty'] ?></strong></td><td><?= (int)$r['orders'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <h2>Orders</h2>
  <table>
    <thead><tr><th>Order</th><th>Customer</th><th>Delivery</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?><tr><td><strong><?= e($o['order_no']) ?></strong></td><td><?= e($o['name']) ?></td><td><?= $o['delivery_method'] === 'pickup' ? 'Pickup' : e($o['city'] . ', ' . $o['state']) ?></td></tr><?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

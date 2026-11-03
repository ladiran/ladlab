<?php
$site = Settings::get('site_name');
$pickup = $o['delivery_method'] === 'pickup';
?>
<?php if ($type === 'label'): ?>
  <div class="label">
    <div class="muted">TO</div>
    <div class="to"><strong><?= e($o['name']) ?></strong><br><?= e($o['address_line']) ?><br><?= e($o['city']) ?>, <?= e($o['state']) ?><br>Nigeria</div>
    <div class="big">☎ <?= e($o['phone']) ?></div>
    <hr>
    <div>Order <strong><?= e($o['order_no']) ?></strong><?= $o['tracking_no'] ? ' · Waybill ' . e($o['tracking_no']) : '' ?></div>
    <div class="muted">From: <?= e($site) ?><?= Settings::get('phone') ? ' · ' . e(Settings::get('phone')) : '' ?></div>
  </div>
<?php else: ?>
  <h1><?= $type === 'slip' ? 'Packing slip' : 'Pick list' ?>: <?= e($o['order_no']) ?></h1>
  <p class="muted"><?= e($site) ?> · <?= e(fmt_date($o['created_at'], 'j M Y')) ?></p>
  <?php if ($type === 'slip'): ?>
    <h2>Ship to</h2>
    <p><strong><?= e($o['name']) ?></strong> · <?= e($o['phone']) ?><br><?= $pickup ? 'Customer pickup' : e($o['address_line'] . ', ' . $o['city'] . ', ' . $o['state']) ?></p>
  <?php endif; ?>
  <table>
    <thead><tr><?php if ($type === 'pick'): ?><th style="width:34px">✔</th><th>Shelf</th><?php endif; ?><th>Item</th><th>Colour</th><th>Size</th><th>SKU</th><th style="width:50px">Qty</th><?php if ($type === 'slip'): ?><th>Price</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr><?php if ($type === 'pick'): ?><td><span class="box"></span></td><td><strong><?= e($i['bin_location'] ?: '-') ?></strong></td><?php endif; ?>
        <td><?= e($i['name']) ?></td><td><?= e($i['colour']) ?></td><td><strong><?= e($i['size'] === 'STD' ? '—' : $i['size']) ?></strong></td><td><?= e($i['sku']) ?></td><td><strong><?= (int)$i['qty'] ?></strong></td><?php if ($type === 'slip'): ?><td><?= e(Shop::naira($i['line_total'])) ?></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($type === 'slip'): ?>
    <p style="margin-top:14px">Subtotal <?= e(Shop::naira($o['subtotal'])) ?><?= $o['discount'] > 0 ? ' · Discount −' . e(Shop::naira($o['discount'])) : '' ?> · Delivery <?= e(Shop::naira($o['shipping_fee'])) ?> · <strong>Total <?= e(Shop::naira($o['total'])) ?></strong></p>
    <p>Thank you for supporting <?= e($site) ?>! Problems with your order? Contact us within 7 days with your order number.</p>
  <?php endif; ?>
<?php endif; ?>

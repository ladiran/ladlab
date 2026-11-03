<?php partial('shop_tabs', ['tab' => $tab]); ?>
<p><a href="<?= e(url('/admin/shop/stock')) ?>">← Back to stock</a></p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>When</th><th>Item</th><th>Change</th><th>Now</th><th>Reason</th><th>Order</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $l): ?><tr><td><?= e(fmt_date($l['created_at'], 'j M Y, H:i')) ?></td><td><?= e($l['label']) ?></td><td><strong style="color:<?= $l['change_qty'] < 0 ? '#a02727' : '#14603f' ?>"><?= $l['change_qty'] > 0 ? '+' : '' ?><?= (int)$l['change_qty'] ?></strong></td><td><?= (int)$l['stock_after'] ?></td><td><small><?= e($l['reason']) ?> <span class="muted">(<?= e($l['actor']) ?>)</span></small></td><td><?= $l['order_no'] ? '<a href="' . e(url('/admin/orders/' . $l['order_id'])) . '">' . e($l['order_no']) . '</a>' : '' ?></td></tr><?php endforeach; ?>
  </tbody>
</table></div>

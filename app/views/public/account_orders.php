<section class="page-hero"><div class="wrap"><h1>My orders</h1></div></section>
<section class="section">
  <div class="wrap">
    <?php partial('account_tabs', ['tab' => $tab]); ?>
    <?php if ($orders): ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Order</th><th>Placed</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><a href="<?= e(url('/order/' . $o['order_no'] . '/' . $o['access_token'])) ?>"><strong><?= e($o['order_no']) ?></strong></a></td>
          <td><?= e(fmt_date($o['created_at'], 'j M Y, H:i')) ?></td>
          <td><?= e(Shop::naira($o['total'])) ?></td>
          <td><span class="status st-<?= e($o['status']) ?>"><?= e(Shop::statusLabel($o)) ?></span><?= $o['tracking_no'] ? ' <small class="muted">' . e($o['courier'] . ' ' . $o['tracking_no']) . '</small>' : '' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php else: ?><p class="empty">You have not placed any orders yet. <a href="<?= e(url('/shop')) ?>">Visit the shop</a>.</p><?php endif; ?>
  </div>
</section>

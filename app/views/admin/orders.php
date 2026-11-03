<?php
partial('shop_tabs', ['tab' => $tab]);
$ql = function (array $over) use ($status, $q, $view) {
    $x = array_filter(array_merge(['status' => $status, 'q' => $q, 'view' => $view === 'board' ? 'board' : ''], $over), fn($v) => $v !== '' && $v !== null);
    return url('/admin/orders' . ($x ? '?' . http_build_query($x) : ''));
};
?>
<div class="cards-row">
  <a class="stat-card" href="<?= e($ql(['status' => 'awaiting_payment', 'view' => ''])) ?>"><strong><?= (int)$counts['awaiting'] ?></strong><span>awaiting payment<?= $counts['receipts'] ? ' (' . (int)$counts['receipts'] . ' with receipt)' : '' ?></span></a>
  <a class="stat-card" href="<?= e($ql(['status' => 'paid', 'view' => ''])) ?>"><strong><?= (int)$counts['topack'] ?></strong><span>paid: to pack</span></a>
  <a class="stat-card" href="<?= e($ql(['status' => 'shipped', 'view' => ''])) ?>"><strong><?= (int)$counts['transit'] ?></strong><span>shipped, in transit</span></a>
  <div class="stat-card"><strong><?= e(Shop::naira($counts['month'])) ?></strong><span>paid this month (<?= e(Shop::naira($counts['all'])) ?> all time)</span></div>
</div>

<p class="actions">
  <a class="btn <?= $view === 'list' ? 'btn-navy' : 'btn-ghost' ?>" href="<?= e($ql(['view' => ''])) ?>">☰ List</a>
  <a class="btn <?= $view === 'board' ? 'btn-navy' : 'btn-ghost' ?>" href="<?= e($ql(['view' => 'board', 'status' => ''])) ?>">▦ Board</a>
  <a class="btn btn-ghost" href="<?= e(url('/admin/orders/pick-list')) ?>" target="_blank">🖨 Pick list for all paid orders</a>
  <a class="btn btn-ghost" href="<?= e(url('/admin/orders/export.csv')) ?>">⬇ Export this month (CSV)</a>
</p>

<?php if ($view === 'board'): ?>
<div class="board">
  <?php $titles = ['awaiting_payment' => '⏳ Awaiting payment', 'paid' => '📥 To pack', 'packed' => '📦 Packed / ready', 'shipped' => '🚚 Shipped', 'delivered' => '✅ Delivered (latest)']; ?>
  <?php foreach ($board as $st => $list): ?>
    <section class="board-col">
      <h2><?= e($titles[$st]) ?> <span class="count-pill"><?= count($list) ?></span></h2>
      <?php foreach ($list as $o): ?>
        <article class="board-card">
          <p><a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['order_no']) ?></strong></a> · <?= e(Shop::naira($o['total'])) ?><?= $o['receipt_path'] && $st === 'awaiting_payment' ? ' <span class="tag tag-verified">receipt</span>' : '' ?></p>
          <p class="small"><?= e($o['name']) ?> <span class="muted">· <?= $o['delivery_method'] === 'pickup' ? 'pickup' : e($o['state']) ?></span></p>
          <p class="small muted"><?= e(mb_strimwidth((string)$o['summary'], 0, 90, '…')) ?></p>
          <?php
            $next = match ($st) {
                'awaiting_payment' => ['confirm_payment', 'Confirm payment', 'btn-gold'],
                'paid' => ['pack', 'Mark packed', 'btn-navy'],
                'packed' => $o['delivery_method'] === 'pickup' ? ['deliver', 'Mark collected', 'btn-navy'] : null,
                default => null,
            };
          ?>
          <div class="row-actions">
            <?php if ($next): ?><form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/action')) ?>" class="inline" data-confirm="<?= e($next[1]) ?> for <?= e($o['order_no']) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="<?= e($next[0]) ?>"><input type="hidden" name="return" value="board"><button class="btn btn-sm <?= $next[2] ?>" type="submit"><?= e($next[1]) ?></button></form><?php endif; ?>
            <?php if (in_array($st, ['paid', 'packed'], true) && $o['delivery_method'] === 'ship'): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/orders/' . $o['id'])) ?>#ship">Ship…</a><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$list): ?><p class="muted small">Nothing here.</p><?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
<?php else: ?>
<form method="get" action="<?= e(url('/admin/orders')) ?>" class="filters" style="margin:12px 0">
  <select name="status" aria-label="Status"><option value="">All statuses</option><?php foreach (Shop::STATUS as $k => $label): ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Order no., name, email or phone…" aria-label="Search">
  <button class="btn btn-navy" type="submit">Filter</button>
</form>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $o): ?>
    <tr class="<?= $o['status'] === 'awaiting_payment' && $o['receipt_path'] ? 'unread' : '' ?>">
      <td><a href="<?= e(url('/admin/orders/' . $o['id'])) ?>"><strong><?= e($o['order_no']) ?></strong></a></td>
      <td><?= e($o['name']) ?><br><small class="muted"><?= $o['delivery_method'] === 'pickup' ? 'pickup' : e($o['state']) ?></small></td>
      <td><small><?= e(mb_strimwidth((string)$o['summary'], 0, 70, '…')) ?></small></td>
      <td><?= e(Shop::naira($o['total'])) ?></td>
      <td><span class="status st-<?= e($o['status']) ?>"><?= e(Shop::statusLabel($o)) ?></span><?= $o['status'] === 'awaiting_payment' && $o['receipt_path'] ? '<br><small>📎 receipt uploaded</small>' : '' ?></td>
      <td><?= e(time_ago($o['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Pages">
    <?php if ($page > 1): ?><a href="<?= e($ql(['page' => $page - 1])) ?>">← Newer</a><?php else: ?><span></span><?php endif; ?>
    <span>Page <?= (int)$page ?> of <?= (int)$pages ?> (<?= (int)$total ?> orders)</span>
    <?php if ($page < $pages): ?><a href="<?= e($ql(['page' => $page + 1])) ?>">Older →</a><?php else: ?><span></span><?php endif; ?>
  </nav>
<?php endif; ?>
<?php else: ?><p class="empty">No orders found.</p><?php endif; ?>
<?php endif; ?>

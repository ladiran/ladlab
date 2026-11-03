<section class="page-hero"><div class="wrap"><h1>My account</h1><p class="lead">Hello, <?= e($user['name']) ?>.</p></div></section>
<section class="section">
  <div class="wrap">
    <?php partial('account_tabs', ['tab' => $tab]); ?>
    <div class="grid g2">
      <div class="card">
        <h2>Recent orders</h2>
        <?php if ($orders): ?>
          <table class="table"><tbody>
          <?php foreach ($orders as $o): ?>
            <tr><td><a href="<?= e(url('/order/' . $o['order_no'] . '/' . $o['access_token'])) ?>"><strong><?= e($o['order_no']) ?></strong></a><br><small class="muted"><?= e(fmt_date($o['created_at'], 'j M Y')) ?></small></td><td><?= e(Shop::naira($o['total'])) ?></td><td><span class="status st-<?= e($o['status']) ?>"><?= e(Shop::statusLabel($o)) ?></span></td></tr>
          <?php endforeach; ?>
          </tbody></table>
          <p><a href="<?= e(url('/account/orders')) ?>">All orders →</a></p>
        <?php else: ?>
          <p class="empty">No orders yet.</p><p><a class="btn btn-navy" href="<?= e(url('/shop')) ?>">Visit the shop</a></p>
        <?php endif; ?>
      </div>
      <div class="card">
        <h2>My details</h2>
        <p><?= e($user['name']) ?><br><?= e($user['email']) ?><?= $user['phone'] ? '<br>' . e($user['phone']) : '' ?></p>
        <p><?= $user['address_line'] ? e($user['address_line'] . ', ' . $user['city'] . ', ' . $user['state']) : '<span class="muted">No delivery address saved yet.</span>' ?></p>
        <p><a class="btn btn-ghost" href="<?= e(url('/account/profile')) ?>">Edit details</a></p>
        <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="btn btn-ghost-dark" type="submit">Sign out</button></form>
      </div>
    </div>
  </div>
</section>

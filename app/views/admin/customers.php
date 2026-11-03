<?php partial('shop_tabs', ['tab' => $tab]); ?>
<form method="get" action="<?= e(url('/admin/customers')) ?>" class="filters" style="margin-bottom:12px">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email…" aria-label="Search">
  <button class="btn btn-navy" type="submit">Search</button>
</form>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Spent (paid)</th><th>Joined</th><th>Last sign-in</th><th>2-step</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><strong><?= e($r['name']) ?></strong><br><small class="muted"><?= e($r['email']) ?></small><?= !empty($r['email_verified_at']) ? ' <small title="Email verified" style="color:#14603f">✔</small>' : ' <small title="Email not verified" style="color:#a02727">⚠ not verified</small>' ?></td><td><?= e($r['phone']) ?></td><td><a href="<?= e(url('/admin/orders?q=' . rawurlencode($r['email']))) ?>"><?= (int)$r['orders'] ?></a></td><td><?= e(Shop::naira($r['spent'])) ?></td><td><?= e(fmt_date($r['created_at'], 'j M Y')) ?></td><td><?= $r['last_login'] ? e(time_ago($r['last_login'])) : '<span class="muted">never</span>' ?></td><td><?php if (!empty($r['totp_enabled_at'])): ?>✔ on <form method="post" action="<?= e(url('/admin/security/reset-2fa/' . $r['id'])) ?>" class="inline" data-confirm="Reset two-step verification for this customer? Only do this after confirming who they are."><?= csrf_field() ?><input type="hidden" name="back" value="admin/customers"><input type="password" name="current_password" required placeholder="Your password" aria-label="Your password" style="width:9rem"><button class="btn btn-sm btn-ghost" type="submit">Reset</button></form><?php else: ?><span class="muted">off</span><?php endif; ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No customer accounts yet. (Guests who check out without an account appear only in Orders.)</p><?php endif; ?>

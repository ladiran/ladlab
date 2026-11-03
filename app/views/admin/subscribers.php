<p class="actions">
  <strong><?= (int)$confirmed ?></strong> confirmed subscribers.
  <a class="btn btn-navy" href="<?= e(url('/admin/subscribers/export.csv')) ?>">⬇ Export confirmed (CSV)</a>
</p>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Joined</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['email']) ?></td>
      <td><?= e($r['name']) ?></td>
      <td><?php if ($r['unsubscribed_at']): ?><span class="status status-archived">unsubscribed</span><?php elseif ($r['confirmed']): ?><span class="status status-replied">confirmed</span><?php else: ?><span class="status status-new">unconfirmed</span><?php endif; ?></td>
      <td><?= e(fmt_date($r['created_at'], 'j M Y')) ?></td>
      <td><form method="post" action="<?= e(url('/admin/subscribers/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Remove this subscriber?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Remove</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No subscribers yet.</p><?php endif; ?>

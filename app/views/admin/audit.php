<p class="muted">The last 200 actions taken in the admin area.</p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e(fmt_date($r['created_at'], 'j M Y, H:i')) ?></td><td><?= e((string)$r['email']) ?></td><td><?= e($r['action']) ?></td><td><?= e($r['detail']) ?></td><td><?= e($r['ip']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

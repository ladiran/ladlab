<p class="filters-row">
  <a class="pill <?= $status === '' ? 'on' : '' ?>" href="<?= e(url('/admin/enquiries')) ?>">Active</a>
  <?php foreach ($statuses as $k => $label): ?><a class="pill <?= $status === $k ? 'on' : '' ?>" href="<?= e(url('/admin/enquiries?status=' . $k)) ?>"><?= e($label) ?></a><?php endforeach; ?>
</p>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>From</th><th>About</th><th>Received</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'new' ? 'unread' : '' ?>">
      <td><a href="<?= e(url('/admin/enquiries/' . $r['id'])) ?>"><strong><?= e($r['name']) ?></strong></a><br><small class="muted"><?= e($r['email']) ?></small><?= ($r['mail_status'] ?? '') === 'failed' ? '<br><span class="tag tag-breaking" title="The email alert for this message did not go out, so you were not told by email">⚠ email alert failed</span>' : '' ?></td>
      <td><?= e(SiteController::ENQUIRY_TYPES[$r['type']] ?? $r['type']) ?><br><small class="muted"><?= e(excerpt($r['subject'] ?: $r['message'], 70)) ?></small></td>
      <td><?= e(fmt_date($r['created_at'], 'j M Y, H:i')) ?></td>
      <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No enquiries here.</p><?php endif; ?>

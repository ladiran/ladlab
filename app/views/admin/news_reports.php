<?php partial('news_tabs', ['tab' => $tab]); ?>
<p class="muted">Readers can press “Report a problem” on any story. Hiding a reported story takes it off the site immediately and you can restore it later.</p>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Story</th><th>Reader’s note</th><th>Received</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'open' ? 'unread' : '' ?>">
      <td><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($r['title']) ?></a><br><small class="muted"><?= e($r['source_name']) ?><?= $r['item_status'] === 'hidden' ? ' · currently hidden' : '' ?></small></td>
      <td><?= $r['message'] !== '' ? nl2br(e($r['message'])) : '<span class="muted">(no note)</span>' ?></td>
      <td><?= e(time_ago($r['created_at'])) ?></td>
      <td><span class="status status-<?= $r['status'] === 'open' ? 'new' : 'archived' ?>"><?= e($r['status']) ?></span></td>
      <td class="row-actions">
        <?php if ($r['status'] === 'open'): ?>
          <form method="post" action="<?= e(url('/admin/news/reports/' . $r['id'] . '/resolve')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Hide story</button></form>
          <form method="post" action="<?= e(url('/admin/news/reports/' . $r['id'] . '/dismiss')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit">Dismiss</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No reports. 🎉</p><?php endif; ?>

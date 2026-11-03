<p class="muted">Articles written by registered users. Nothing appears on the blog until you approve it. <a href="<?= e(url('/admin/posts')) ?>">All blog posts →</a></p>
<nav class="tabs sub" aria-label="Filter">
  <?php foreach ($statuses as $k => $l): ?><a class="<?= $status === $k ? 'on' : '' ?>" href="<?= e(url('/admin/articles?status=' . $k)) ?>"><?= e($l) ?><?php if ($counts[$k]): ?> <span class="count"><?= (int)$counts[$k] ?></span><?php endif; ?></a><?php endforeach; ?>
</nav>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Article</th><th>Author</th><th>Submitted</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'pending' ? 'unread' : '' ?>">
      <td><a href="<?= e(url('/admin/articles/' . $r['id'])) ?>"><strong><?= e($r['title']) ?></strong></a></td>
      <td><?= e($r['author_name']) ?><br><small class="muted"><?= e($r['author_email']) ?></small></td>
      <td><?= $r['submitted_at'] ? e(time_ago($r['submitted_at'])) : '<span class="muted">not submitted</span>' ?></td>
      <td class="row-actions"><a class="btn btn-sm btn-gold" href="<?= e(url('/admin/articles/' . $r['id'])) ?>"><?= $r['status'] === 'pending' ? 'Review' : 'Open' ?></a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">Nothing here.</p><?php endif; ?>

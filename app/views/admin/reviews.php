<?php partial('course_tabs', ['tab' => $tab]); ?>
<p class="muted">Students can review a course once they are enrolled. Approve the ones you want on the course page; hide or delete the rest.</p>
<nav class="tabs sub" aria-label="Filter">
  <?php foreach (['pending' => 'To approve', 'published' => 'Published', 'hidden' => 'Hidden'] as $k => $l): ?><a class="<?= $status === $k ? 'on' : '' ?>" href="<?= e(url('/admin/reviews?status=' . $k)) ?>"><?= e($l) ?></a><?php endforeach; ?>
</nav>
<?php if ($rows): foreach ($rows as $r): ?>
  <article class="card review">
    <p class="rating-line"><span class="stars" aria-hidden="true"><?= e(Community::stars((float)$r['rating'])) ?></span> <strong><?= e($r['name']) ?></strong> <span class="muted small">on <?= e($r['course_title']) ?> · <?= e(time_ago($r['created_at'])) ?></span></p>
    <?php if ($r['comment']): ?><p><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
    <form method="post" action="<?= e(url('/admin/reviews/' . $r['id'])) ?>" class="inline-edit">
      <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($status) ?>">
      <?php if ($r['status'] !== 'published'): ?><button class="btn btn-sm btn-gold" type="submit" name="action" value="published">Publish</button><?php endif; ?>
      <?php if ($r['status'] !== 'hidden'): ?><button class="btn btn-sm btn-ghost" type="submit" name="action" value="hidden">Hide</button><?php endif; ?>
      <button class="btn btn-sm btn-danger" type="submit" name="action" value="delete">Delete</button>
    </form>
  </article>
<?php endforeach; else: ?><p class="empty">Nothing here.</p><?php endif; ?>

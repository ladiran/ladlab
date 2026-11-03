<?php partial('course_tabs', ['tab' => $tab]); ?>
<p class="muted">Questions students ask under lessons. An answered question becomes visible to every enrolled student of that lesson, and the asker is emailed.</p>
<nav class="tabs sub" aria-label="Filter">
  <?php foreach (['open' => 'Waiting for an answer', 'answered' => 'Answered', 'hidden' => 'Hidden'] as $k => $l): ?><a class="<?= $status === $k ? 'on' : '' ?>" href="<?= e(url('/admin/qa?status=' . $k)) ?>"><?= e($l) ?></a><?php endforeach; ?>
</nav>
<?php if ($rows): foreach ($rows as $q): ?>
  <article class="card qa-item">
    <p><strong><?= e($q['name']) ?></strong> <span class="muted small">· <?= e($q['course_title']) ?> › <a href="<?= e(url('/learn/' . $q['course_slug'] . '/lesson/' . $q['lesson_id'])) ?>" target="_blank"><?= e($q['lesson_title']) ?></a> · <?= e(time_ago($q['created_at'])) ?></span></p>
    <p><?= nl2br(e($q['question'])) ?></p>
    <form method="post" action="<?= e(url('/admin/qa/' . $q['id'])) ?>" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="return" value="<?= e($status) ?>">
      <label for="a<?= (int)$q['id'] ?>"><?= $q['answer'] ? 'Your answer (edit to update)' : 'Your answer' ?></label>
      <textarea id="a<?= (int)$q['id'] ?>" name="answer" rows="3"><?= e((string)$q['answer']) ?></textarea>
      <div class="form-actions">
        <button class="btn btn-gold" type="submit" name="action" value="answer"><?= $q['answer'] ? 'Update answer' : 'Post answer and email student' ?></button>
        <?php if ($q['status'] !== 'hidden'): ?><button class="btn btn-ghost-dark" type="submit" name="action" value="hide">Hide</button><?php endif; ?>
      </div>
    </form>
    <form method="post" action="<?= e(url('/admin/qa/' . $q['id'])) ?>" data-confirm="Delete this question for good?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
  </article>
<?php endforeach; else: ?><p class="empty">Nothing here.</p><?php endif; ?>

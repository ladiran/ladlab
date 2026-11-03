<?php
partial('course_tabs', ['tab' => $tab]);
$old = $_SESSION['old'] ?? null;
?>
<p><a href="<?= e(url('/admin/courses')) ?>">← Courses</a> · <strong><?= e($c['title']) ?></strong></p>
<form method="post" action="<?= e(url('/admin/courses/' . $c['id'] . '/announcements')) ?>" class="card form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>">
  <?= csrf_field() ?>
  <h2>Post an announcement</h2>
  <p class="muted">Shown on the course page to enrolled students. Good for new lessons, exam dates and corrections.</p>
  <?php partial('field', ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'o' => ['required' => true, 'max' => 200], 'value' => $old['title'] ?? '']); ?>
  <?php partial('field', ['name' => 'body', 'type' => 'markdown', 'label' => 'Message', 'o' => ['rows' => 6, 'required' => true], 'value' => $old['body'] ?? '']); ?>
  <?php partial('field', ['name' => 'email_students', 'type' => 'checkbox', 'label' => 'Also email it to the ' . (int)$students . ' active student' . ($students === 1 ? '' : 's'), 'o' => [], 'value' => '1']); ?>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Post announcement</button></div>
</form>
<h2 style="margin-top:28px">Earlier announcements</h2>
<?php if ($rows): foreach ($rows as $a): ?>
  <article class="card announce">
    <h3><?= e($a['title']) ?></h3>
    <p class="muted small"><?= e(fmt_date($a['created_at'], 'j M Y, H:i')) ?><?= (int)$a['emailed'] ? ' · emailed to ' . (int)$a['emailed'] : '' ?></p>
    <div class="prose"><?= md($a['body']) ?></div>
    <form method="post" action="<?= e(url('/admin/announcements/' . $a['id'] . '/delete')) ?>" data-confirm="Delete this announcement?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
  </article>
<?php endforeach; else: ?><p class="empty">None yet.</p><?php endif; ?>
<?php clear_old(); ?>

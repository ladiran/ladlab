<?php partial('course_tabs', ['tab' => $tab]); ?>
<?php if (Auth::isAdmin()): ?>
<p class="muted">Build courses here: modules, lessons (with equations, video and PDFs), quizzes and a final exam. Students pay by bank transfer and you confirm under <a href="<?= e(url('/admin/enrolments')) ?>">Students &amp; payments</a>.</p>
<?php else: ?>
<p class="muted">Your courses: build modules, lessons (with equations, video and PDFs), quizzes and a final exam. New courses start as drafts; the administrator sets the price and publishes them. You see only your own courses and their students.</p>
<?php endif; ?>
<p class="actions"><a class="btn btn-gold" href="<?= e(url('/admin/courses/new')) ?>">＋ Add a course</a></p>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Course</th><?php if (Auth::isAdmin()): ?><th><?= e(Lecturers::label()) ?></th><?php endif; ?><th>Price</th><th>Status</th><th>Content</th><th>Students</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'draft' ? 'row-hidden' : '' ?>">
      <td><a href="<?= e(url('/admin/courses/' . $r['id'] . '/curriculum')) ?>"><strong><?= e($r['title']) ?></strong></a><?php if ($r['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?><br><small class="muted"><?= e($r['level']) ?></small></td>
      <?php if (Auth::isAdmin()): ?><td><?= $r['lecturer_name'] ? e($r['lecturer_name']) : '<span class="muted">none</span>' ?></td><?php endif; ?>
      <td><?= (int)$r['price'] === 0 ? 'Free' : e(Shop::naira($r['price'])) ?></td>
      <td><span class="status <?= $r['status'] === 'published' ? 'status-replied' : ($r['status'] === 'soon' ? 'status-new' : 'status-archived') ?>"><?= ['published' => 'Published', 'draft' => 'Draft', 'soon' => 'Coming soon'][$r['status']] ?></span><?php if ($lw = Learn::lockWindow($r)): ?><br><small class="muted">🔒 <?= $lw['active'] ? 'Locked until ' : 'Lock scheduled, ends ' ?><?= e(Learn::lockText($lw['until'])) ?></small><?php endif; ?></td>
      <td><?= (int)$r['lessons'] ?> lessons · <?= (int)$r['quizzes'] ?> quizzes</td>
      <td><?= (int)$r['students'] ?><?= $r['pending'] ? ' <span class="tag tag-new">' . (int)$r['pending'] . ' to confirm</span>' : '' ?></td>
      <td class="row-actions">
        <a class="btn btn-sm btn-gold" href="<?= e(url('/admin/courses/' . $r['id'] . '/curriculum')) ?>">Curriculum</a>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/courses/' . $r['id'])) ?>">Details</a>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/courses/' . $r['id'] . '/announcements')) ?>">Announce</a>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/progress/' . $r['id'])) ?>">Progress</a>
        <?php if (Auth::isAdmin()): ?><form method="post" action="<?= e(url('/admin/courses/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this course with ALL its lessons, quizzes, enrolments and progress? This cannot be undone."><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty"><?= Auth::isAdmin() ? 'No courses yet.' : 'You have no courses yet. Press “Add a course” to start one, or ask the administrator to assign you an existing course.' ?></p><?php endif; ?>

<?php partial('course_tabs', ['tab' => $tab]); $isAdmin = Auth::isAdmin(); ?>
<p class="muted"><?= $isAdmin ? 'Every course. Open one to see each student: lessons completed, scores and overall performance.' : 'Your courses. Open one to see how your students are doing. You see only the students of your own courses.' ?></p>
<?php if ($courses): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Course</th><?php if ($isAdmin): ?><th><?= e(Lecturers::label()) ?></th><?php endif; ?><th>Students</th><th>Started</th><th>Completed</th><th>Average progress</th><th>Check-up average</th><th>Quiz average</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($courses as $c): $s = $c['sum']; ?>
    <tr>
      <td><a href="<?= e(url('/admin/progress/' . $c['id'])) ?>"><strong><?= e($c['title']) ?></strong></a><?php if ($c['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?><br><small class="muted"><?= e(['published' => 'Published', 'draft' => 'Draft', 'soon' => 'Coming soon'][$c['status']] ?? $c['status']) ?></small></td>
      <?php if ($isAdmin): ?><td><?= $c['lecturer_name'] ? e($c['lecturer_name']) : '<span class="muted">none</span>' ?></td><?php endif; ?>
      <td><?= (int)$s['students'] ?></td>
      <td><?= (int)$s['started'] ?></td>
      <td><?= (int)$s['completed'] ?></td>
      <td><?= $s['avg_progress'] !== null ? (int)$s['avg_progress'] . '%' : '<span class="muted">n/a</span>' ?></td>
      <td><?= $s['avg_check'] !== null ? (int)$s['avg_check'] . '%' : '<span class="muted">n/a</span>' ?></td>
      <td><?= $s['avg_quiz'] !== null ? (int)$s['avg_quiz'] . '%' : '<span class="muted">n/a</span>' ?></td>
      <td class="row-actions"><a class="btn btn-sm btn-gold" href="<?= e(url('/admin/progress/' . $c['id'])) ?>">Open</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No courses yet.</p><?php endif; ?>

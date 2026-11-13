<?php
partial('course_tabs', ['tab' => $tab]);
$base = '/admin/progress/' . $c['id'];
$pc = fn($v) => $v === null ? '<span class="muted">n/a</span>' : (int)$v . '%';
$num = fn($v) => rtrim(rtrim(number_format((float)$v, 1), '0'), '.');
$sortLink = fn(string $key, string $label) => '<a href="' . e(url($base . '?sort=' . $key)) . '"' . ($sort === $key ? ' style="font-weight:700"' : '') . '>' . e($label) . ($sort === $key ? ' ▾' : '') . '</a>';
?>
<p><a href="<?= e(url('/admin/progress')) ?>">← Student progress</a> · <a href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>">Curriculum</a><?php if ($lecturer): ?> · <?= e(Lecturers::label()) ?>: <strong><?= e($lecturer) ?></strong><?php endif; ?></p>

<div class="stat-grid">
  <div class="card stat"><p class="stat-n"><?= (int)$sum['students'] ?></p><p class="muted small">students enrolled</p></div>
  <div class="card stat"><p class="stat-n"><?= (int)$sum['started'] ?></p><p class="muted small">have started</p></div>
  <div class="card stat"><p class="stat-n"><?= (int)$sum['completed'] ?></p><p class="muted small">completed (<?= $sum['students'] ? (int)round($sum['completed'] * 100 / $sum['students']) : 0 ?>%)</p></div>
  <div class="card stat"><p class="stat-n"><?= $sum['avg_progress'] !== null ? (int)$sum['avg_progress'] . '%' : 'n/a' ?></p><p class="muted small">average lessons done</p></div>
  <div class="card stat"><p class="stat-n"><?= $sum['avg_check'] !== null ? (int)$sum['avg_check'] . '%' : 'n/a' ?></p><p class="muted small">average check-up score</p></div>
  <div class="card stat"><p class="stat-n"><?= $sum['avg_quiz'] !== null ? (int)$sum['avg_quiz'] . '%' : 'n/a' ?></p><p class="muted small">average quiz and exam score</p></div>
</div>

<h2>Students</h2>
<?php if ($rows): ?>
<p class="small muted">Sort by: <?= $sortLink('name', 'name') ?> · <?= $sortLink('progress', 'progress') ?> · <?= $sortLink('check', 'check-up score') ?> · <?= $sortLink('quiz', 'quiz average') ?> · <?= $sortLink('last', 'latest activity') ?> &nbsp; <a class="btn btn-sm btn-ghost" href="<?= e(url($base . '/csv')) ?>">⬇ Download as spreadsheet (CSV)</a></p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Student</th><th>Status</th><th>Lessons done</th><th>Check-up score</th><th>Quizzes</th><th>Final exam</th><th>Latest activity</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= e(url($base . '/student/' . $r['user_id'])) ?>"><strong><?= e($r['name']) ?></strong></a><br><small class="muted"><?= e($r['email']) ?> · enrolled <?= e(fmt_date($r['enrolled_at'], 'j M Y')) ?></small></td>
      <td><span class="status <?= $r['status'] === 'Completed' ? 'status-replied' : ($r['status'] === 'In progress' ? 'status-new' : 'status-archived') ?>"><?= e($r['status']) ?></span><?php if ($r['cert_code']): ?><br><small class="muted">🏅 certificate</small><?php endif; ?></td>
      <td><?= (int)$r['lessons_done'] ?> / <?= (int)$r['lessons_total'] ?> <span class="muted small">(<?= (int)$r['pct'] ?>%)</span><div class="progress small-bar"><span style="width:<?= (int)$r['pct'] ?>%"></span></div></td>
      <td><?php if ($r['check_pct'] !== null): ?><?= e($num($r['check_earned'])) ?> / <?= e($num($r['check_max'])) ?> <span class="muted small">(<?= (int)$r['check_pct'] ?>%)</span><?php else: ?><span class="muted">n/a</span><?php endif; ?></td>
      <td><?= (int)$r['quizzes_passed'] ?> / <?= (int)$r['quizzes_total'] ?> passed<br><small class="muted">average <?= $pc($r['quiz_avg']) ?></small></td>
      <td><?= $r['final_best'] !== null ? (int)$r['final_best'] . '%' : '<span class="muted">not taken</span>' ?></td>
      <td><?= $r['last_activity'] ? '<span title="' . e(fmt_date($r['last_activity'], 'j M Y, g:i A')) . '">' . e(time_ago($r['last_activity'])) . '</span>' : '<span class="muted">never</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No students are enrolled in this course yet.</p><?php endif; ?>

<h2>Lesson by lesson</h2>
<?php if ($funnel): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Module</th><th>Lesson</th><th>Finished by</th><th>Average check-up score</th></tr></thead>
  <tbody>
  <?php foreach ($funnel as $f): $p = $sum['students'] ? (int)round($f['done'] * 100 / $sum['students']) : 0; ?>
    <tr><td class="muted small"><?= e($f['module_title']) ?></td><td><?= e($f['title']) ?></td>
      <td><?= (int)$f['done'] ?> of <?= (int)$sum['students'] ?> <span class="muted small">(<?= $p ?>%)</span></td>
      <td><?= $f['check_pct'] !== null ? (int)$f['check_pct'] . '% <span class="muted small">(' . (int)$f['check_n'] . ' answers)</span>' : '<span class="muted">n/a</span>' ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<p class="small muted">A lesson that many students never finish, or with a low check-up average, may need explaining again.</p>
<?php else: ?><p class="empty">This course has no published lessons yet.</p><?php endif; ?>

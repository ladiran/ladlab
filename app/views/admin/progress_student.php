<?php
partial('course_tabs', ['tab' => $tab]);
$en = $s['enrol'];
$num = fn($v) => rtrim(rtrim(number_format((float)$v, 1), '0'), '.');
$pc = fn($v) => $v === null ? 'n/a' : (int)$v . '%';
$status = !empty($en['completed_at']) ? 'Completed' : ($s['lessons_done'] > 0 || $s['quiz_avg'] !== null ? 'In progress' : 'Not started');
?>
<p><a href="<?= e(url('/admin/progress/' . $c['id'])) ?>">← <?= e($c['title']) ?></a></p>
<section class="card">
  <h2 style="margin-top:0"><?= e($en['name']) ?></h2>
  <p class="muted"><?= e($en['email']) ?> · enrolled <?= e(fmt_date($en['paid_at'] ?: $en['created_at'], 'j M Y')) ?> · <strong><?= e($status) ?></strong><?= !empty($en['completed_at']) ? ' on ' . e(fmt_date($en['completed_at'], 'j M Y')) : '' ?><?= $s['cert'] ? ' · 🏅 certificate ' . e($s['cert']['code']) : '' ?></p>
</section>

<h2>Overall performance</h2>
<div class="stat-grid">
  <div class="card stat"><p class="stat-n"><?= (int)$s['lessons_done'] ?> / <?= (int)$s['lessons_total'] ?></p><p class="muted small">lessons completed (<?= (int)$s['pct'] ?>%)</p></div>
  <div class="card stat"><p class="stat-n"><?= e($pc($s['check_pct'])) ?></p><p class="muted small">check-up score<?= $s['check_max'] > 0 ? ' (' . e($num($s['check_earned'])) . ' of ' . e($num($s['check_max'])) . ' points)' : '' ?></p></div>
  <div class="card stat"><p class="stat-n"><?= e($pc($s['quiz_avg'])) ?></p><p class="muted small">quiz and exam average (best attempts)</p></div>
  <div class="card stat"><p class="stat-n"><?= (int)$s['quizzes_passed'] ?> / <?= (int)$s['quizzes_total'] ?></p><p class="muted small">quizzes and exams passed</p></div>
  <div class="card stat"><p class="stat-n"><?= $s['final_best'] !== null && $s['final'] && array_filter(array_column($s['final'], 'best'), fn($b) => $b !== null) ? (int)$s['final_best'] . '%' : 'n/a' ?></p><p class="muted small">final exam (best)</p></div>
</div>
<p class="small muted">Check-up scores come from the short question after each lesson. The student sees them only for information: they are not part of the grade or the certificate. Quiz and exam scores use the best attempt at each.</p>

<?php foreach ($s['modules'] as $mi => $m): ?>
<section class="card" style="margin-top:16px">
  <h3 style="margin-top:0">Module <?= $mi + 1 ?>: <?= e($m['module']['title']) ?></h3>
  <p class="small muted"><?= (int)$m['done'] ?> of <?= (int)$m['total'] ?> lessons completed · check-up score for this module: <strong><?= $m['check_max'] > 0 ? e($num($m['check_earned'])) . ' of ' . e($num($m['check_max'])) . ' (' . (int)$m['check_pct'] . '%)' : 'n/a' ?></strong></p>
  <table class="table"><thead><tr><th>Lesson</th><th>Completed</th><th>Check-up</th></tr></thead><tbody>
    <?php foreach ($m['lessons'] as $l): ?>
      <tr><td><?= e($l['title']) ?></td>
        <td><?= $l['completed_at'] ? '✔ ' . e(fmt_date($l['completed_at'], 'j M Y')) : '<span class="muted">not yet</span>' ?></td>
        <td><?= $l['earned'] !== null ? e($num($l['earned'])) . ' / ' . (int)$l['max'] : '<span class="muted">n/a</span>' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$m['lessons']): ?><tr><td colspan="3" class="muted">No published lessons.</td><td></td></tr><?php endif; ?>
  </tbody></table>
  <?php foreach ($m['quizzes'] as $qz): ?>
    <p>📝 <strong><?= e($qz['quiz']['title']) ?></strong>:
      <?php if ($qz['attempts']): ?>best <strong><?= (int)$qz['best'] ?>%</strong> <?= $qz['passed'] ? '(passed)' : '(not passed yet; pass mark ' . (int)$qz['quiz']['pass_mark'] . '%)' ?>, <?= count($qz['attempts']) ?> attempt<?= count($qz['attempts']) === 1 ? '' : 's' ?> (<?= e(implode(', ', array_map(fn($a) => (int)$a['score'] . '%', $qz['attempts']))) ?>)
      <?php else: ?><span class="muted">not attempted</span><?php endif; ?></p>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>

<?php if ($s['final']): ?>
<section class="card" style="margin-top:16px">
  <h3 style="margin-top:0">Final exam</h3>
  <?php foreach ($s['final'] as $qz): ?>
    <p>🎓 <strong><?= e($qz['quiz']['title']) ?></strong>:
      <?php if ($qz['attempts']): ?>best <strong><?= (int)$qz['best'] ?>%</strong> <?= $qz['passed'] ? '(passed)' : '(not passed yet; pass mark ' . (int)$qz['quiz']['pass_mark'] . '%)' ?>, <?= count($qz['attempts']) ?> attempt<?= count($qz['attempts']) === 1 ? '' : 's' ?> (<?= e(implode(', ', array_map(fn($a) => (int)$a['score'] . '%', $qz['attempts']))) ?>)
      <?php else: ?><span class="muted">not taken</span><?php endif; ?></p>
  <?php endforeach; ?>
</section>
<?php endif; ?>

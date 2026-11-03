<?php partial('course_tabs', ['tab' => $tab]); ?>
<p><a href="<?= e(url('/admin/enrolments')) ?>">← All enrolments</a></p>
<div class="two-col">
  <section class="card">
    <h2><?= e($e['enrol_no']) ?> <span class="status st-<?= $e['status'] === 'active' ? 'paid' : ($e['status'] === 'cancelled' ? 'cancelled' : 'awaiting_payment') ?>"><?= e(Learn::ENROL_STATUS[$e['status']]) ?></span></h2>
    <p><strong><?= e($e['name']) ?></strong><br><a href="mailto:<?= e($e['email']) ?>"><?= e($e['email']) ?></a><?= $e['phone'] ? '<br>' . e($e['phone']) : '' ?></p>
    <p>Course: <a href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>"><?= e($c['title']) ?></a><br>
      Amount: <strong><?= e(Shop::naira($e['total'])) ?></strong><?= $e['discount'] > 0 ? ' (coupon ' . e($e['coupon_code']) . ', −' . e(Shop::naira($e['discount'])) . ')' : '' ?><br>
      Enrolled <?= e(fmt_date($e['created_at'], 'j M Y, H:i')) ?><?= $e['paid_at'] ? ' · access since ' . e(fmt_date($e['paid_at'], 'j M Y')) : '' ?><?= $e['completed_at'] ? ' · completed ' . e(fmt_date($e['completed_at'], 'j M Y')) : '' ?></p>
    <?php if ($e['status'] === 'awaiting_payment'): ?>
      <p>Check your bank for <strong><?= e(Shop::naira($e['total'])) ?></strong> with reference <strong><?= e($e['enrol_no']) ?></strong>.</p>
      <?php if ($e['receipt_path']): ?><p><a class="btn btn-navy" href="<?= e(url('/admin/enrolments/' . $e['id'] . '/receipt')) ?>" target="_blank">📎 View uploaded receipt</a></p><?php endif; ?>
      <form method="post" action="<?= e(url('/admin/enrolments/' . $e['id'] . '/action')) ?>" class="inline" data-confirm="Confirm that the money has reached your account?"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><button class="btn btn-gold" type="submit">✔ Confirm payment received</button></form>
    <?php endif; ?>
    <?php if ($e['status'] !== 'cancelled'): ?>
      <form method="post" action="<?= e(url('/admin/enrolments/' . $e['id'] . '/action')) ?>" class="inline" data-confirm="Cancel this enrolment? The student loses access."><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><button class="btn btn-danger" type="submit">Cancel enrolment</button></form>
    <?php endif; ?>
    <?php if ($cert): ?><p>🏅 Certificate <a href="<?= e(url('/certificate/' . $cert['code'])) ?>" target="_blank"><?= e($cert['code']) ?></a> issued <?= e(fmt_date($cert['issued_at'], 'j M Y')) ?></p><?php endif; ?>
  </section>
  <section class="card">
    <h2>Progress: <?= (int)$progress['pct'] ?>%</h2>
    <div class="progress"><span style="width:<?= (int)$progress['pct'] ?>%"></span></div>
    <p class="muted small"><?= (int)$progress['done'] ?> of <?= (int)$progress['total'] ?> items done</p>
    <?php if ($attempts): ?>
      <h3>Quiz attempts</h3>
      <table class="table"><tbody><?php foreach ($attempts as $a): ?><tr><td><?= e($a['title']) ?></td><td><strong><?= (int)$a['score'] ?>%</strong></td><td><?= $a['passed'] ? '✔ passed' : 'not passed' ?></td><td><small class="muted"><?= e(time_ago($a['submitted_at'])) ?></small></td></tr><?php endforeach; ?></tbody></table>
    <?php endif; ?>
  </section>
</div>
<section class="card" style="margin-top:20px">
  <h2>Lessons</h2>
  <?php foreach ($cur['modules'] as $mi => $m): ?>
    <p><strong><?= $mi + 1 ?>. <?= e($m['module']['title']) ?></strong></p>
    <ul class="plain-links"><?php foreach ($m['lessons'] as $l): ?><li><?= isset($progress['lessons'][(int)$l['id']]) ? '✔' : '○' ?> <?= e($l['title']) ?></li><?php endforeach; ?>
    <?php foreach ($m['quizzes'] as $q): $qp = $progress['quizzes'][(int)$q['id']] ?? null; ?><li><?= $qp && $qp['passed'] ? '✔' : '○' ?> 📝 <?= e($q['title']) ?><?= $qp ? ' <small class="muted">best ' . (int)$qp['best'] . '%, ' . (int)$qp['attempts'] . ' attempt(s)</small>' : '' ?></li><?php endforeach; ?></ul>
  <?php endforeach; ?>
  <?php foreach ($cur['final'] as $q): $qp = $progress['quizzes'][(int)$q['id']] ?? null; ?><p><?= $qp && $qp['passed'] ? '✔' : '○' ?> 🎓 <?= e($q['title']) ?><?= $qp ? ' <small class="muted">best ' . (int)$qp['best'] . '%</small>' : '' ?></p><?php endforeach; ?>
</section>

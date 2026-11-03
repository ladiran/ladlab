<?php partial('course_tabs', ['tab' => $tab]); ?>
<div class="cards-row">
  <a class="stat-card" href="<?= e(url('/admin/enrolments?status=awaiting_payment')) ?>"><strong><?= (int)$pending ?></strong><span>payments to confirm</span></a>
  <div class="stat-card"><strong><?= e(Shop::naira($revenue)) ?></strong><span>course revenue (confirmed)</span></div>
</div>
<form method="get" action="<?= e(url('/admin/enrolments')) ?>" class="filters" style="margin:12px 0">
  <select name="status" aria-label="Status"><option value="">All</option><?php foreach (Learn::ENROL_STATUS as $k => $l): ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
  <select name="course" aria-label="Course"><option value="">All courses</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $course === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or ENR-…" aria-label="Search">
  <button class="btn btn-navy" type="submit">Filter</button>
</form>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Enrolment</th><th>Student</th><th>Course</th><th>Amount</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'awaiting_payment' && $r['receipt_path'] ? 'unread' : '' ?>">
      <td><a href="<?= e(url('/admin/enrolments/' . $r['id'])) ?>"><strong><?= e($r['enrol_no']) ?></strong></a><br><small class="muted"><?= e(time_ago($r['created_at'])) ?></small></td>
      <td><?= e($r['name']) ?><br><small class="muted"><?= e($r['email']) ?></small></td>
      <td><?= e($r['course_title']) ?></td>
      <td><?= $r['source'] === 'manual' ? '<span class="tag">given free</span>' : ($r['source'] === 'free' ? '<span class="tag">free course</span>' : e(Shop::naira($r['total']))) ?></td>
      <td><span class="status st-<?= $r['status'] === 'active' ? 'paid' : ($r['status'] === 'cancelled' ? 'cancelled' : 'awaiting_payment') ?>"><?= e(Learn::ENROL_STATUS[$r['status']]) ?></span><?= $r['receipt_path'] && $r['status'] === 'awaiting_payment' ? '<br><small>📎 receipt uploaded</small>' : '' ?></td>
      <td class="row-actions">
        <?php if ($r['status'] === 'awaiting_payment'): ?>
          <form method="post" action="<?= e(url('/admin/enrolments/' . $r['id'] . '/action')) ?>" class="inline" data-confirm="Confirm that <?= e(Shop::naira($r['total'])) ?> has reached your account?"><?= csrf_field() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="return" value="list"><button class="btn btn-sm btn-gold" type="submit">Confirm payment</button></form>
        <?php endif; ?>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/enrolments/' . $r['id'])) ?>">Open</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No enrolments yet.</p><?php endif; ?>

<section class="card" style="margin-top:24px">
  <h2>Give a student access (free)</h2>
  <p class="muted">For scholarships, your own class groups or testing. The student must already have an account.</p>
  <form method="post" action="<?= e(url('/admin/enrolments/grant')) ?>" class="form filters">
    <?= csrf_field() ?>
    <input type="email" name="email" placeholder="student@email.com" required aria-label="Student email">
    <select name="course_id" required aria-label="Course"><option value="">Choose course…</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-navy" type="submit">Give access</button>
  </form>
</section>

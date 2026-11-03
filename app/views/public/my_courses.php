<section class="page-hero"><div class="wrap"><h1>My courses</h1></div></section>
<section class="section">
  <div class="wrap">
    <?php partial('account_tabs', ['tab' => $tab]); ?>
    <?php if ($rows): ?>
      <div class="stack">
      <?php foreach ($rows as $r): $p = $r['progress']; ?>
        <article class="card my-course">
          <div>
            <h2><a href="<?= e(url('/learn/' . $r['slug'])) ?>"><?= e($r['title']) ?></a></h2>
            <?php if ($r['status'] === 'active' && $p): ?>
              <div class="progress"><span style="width:<?= (int)$p['pct'] ?>%"></span></div>
              <p class="small muted"><?= (int)$p['pct'] ?>% complete · <?= (int)$p['done'] ?> of <?= (int)$p['total'] ?> items<?= $r['completed_at'] ? ' · completed ' . e(fmt_date($r['completed_at'], 'j M Y')) : '' ?></p>
            <?php else: ?>
              <p><span class="status st-awaiting_payment">Awaiting payment</span> <small class="muted"><?= e($r['enrol_no']) ?> · <?= e(Shop::naira($r['total'])) ?></small></p>
            <?php endif; ?>
          </div>
          <div class="my-course-actions">
            <?php if ($r['status'] === 'active'): ?>
              <a class="btn btn-gold" href="<?= e(url('/learn/' . $r['slug'] . ($r['last_lesson_id'] ? '/lesson/' . $r['last_lesson_id'] : ''))) ?>"><?= $p && $p['done'] ? 'Continue' : 'Start' ?></a>
              <?php if ($r['cert_code']): ?><a class="btn btn-navy" href="<?= e(url('/certificate/' . $r['cert_code'])) ?>">🏅 Certificate</a><?php endif; ?>
            <?php else: ?>
              <a class="btn btn-gold" href="<?= e(url('/enrolment/' . $r['enrol_no'])) ?>">Payment details</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">You have not enrolled in a course yet. <a href="<?= e(url('/learn')) ?>">Browse the courses</a>.</p>
    <?php endif; ?>
  </div>
</section>

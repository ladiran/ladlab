<?php
$soon = $c['status'] === 'soon';
$price = (int)$c['price'];
$active = $enrol && $enrol['status'] === 'active';
$hours = $cur['minutes'] > 0 ? round($cur['minutes'] / 60, 1) : 0;
$loggedIn = (bool)$u;
?>
<?php if (!empty($lockNote)): ?><div class="wrap"><p class="flash flash-info">🔒 <?= $lockNote['active'] ? '<strong>This course is locked for students right now</strong>, until ' : '<strong>A lockdown is scheduled</strong> from ' . e(Learn::lockText($lockNote['from'])) . ' until ' ?><?= e(Learn::lockText($lockNote['until'])) ?>. Administrators are not affected. Change it under Course details.</p></div><?php endif; ?>
<section class="course-hero">
  <div class="wrap course-hero-grid">
    <div>
      <p class="eyebrow"><?= e($c['level']) ?> · <?= e($c['subject']) ?></p>
      <h1><?= e($c['title']) ?><?php if ($c['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></h1>
      <p class="lead"><?= e($c['subtitle']) ?></p>
      <?php if (!$soon): ?>
      <p class="course-facts"><span>📚 <?= (int)$cur['lessons'] ?> lessons</span><span>📝 <?= (int)$cur['quizzes'] ?> quizzes</span><?= $hours ? '<span>🎬 ' . e((string)$hours) . ' h video</span>' : '' ?><span>🏅 Certificate</span><?= $students > 4 ? '<span>👥 ' . (int)$students . ' students</span>' : '' ?></p>
      <?php endif; ?>
    </div>
    <aside class="card enrol-box">
      <?php if ($soon): ?>
        <h2>Coming soon</h2><p>This course is being prepared. Join the weekly digest to be told when it opens.</p>
        <a class="btn btn-gold btn-block" href="#newsletter">Notify me</a>
      <?php elseif ($active): ?>
        <h2>You are enrolled ✔</h2>
        <?php if ($progress): ?><div class="progress" role="progressbar" aria-valuenow="<?= (int)$progress['pct'] ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= (int)$progress['pct'] ?>%"></span></div><p class="small muted"><?= (int)$progress['pct'] ?>% complete (<?= (int)$progress['done'] ?> of <?= (int)$progress['total'] ?> items)</p><?php endif; ?>
        <?php if ($resume): ?><a class="btn btn-gold btn-block" href="<?= e(url('/learn/' . $c['slug'] . '/lesson/' . $resume)) ?>"><?= $progress && $progress['done'] > 0 ? 'Continue learning' : 'Start the course' ?></a><?php endif; ?>
        <?php if (CourseChat::enabled($c)): ?><a class="btn btn-ghost btn-block" style="margin-top:8px" href="<?= e(url('/learn/' . $c['slug'] . '/chat')) ?>">💬 Course chat</a><?php endif; ?>
        <?php if ($enrol['completed_at']): ?><p class="small"><a href="<?= e(url('/account/courses')) ?>">🏅 View your certificate</a></p><?php endif; ?>
      <?php elseif ($enrol && $enrol['status'] === 'awaiting_payment'): ?>
        <h2>Payment pending</h2>
        <p>Your enrolment <strong><?= e($enrol['enrol_no']) ?></strong> is waiting for payment of <strong><?= e(Shop::naira($enrol['total'])) ?></strong>.</p>
        <a class="btn btn-gold btn-block" href="<?= e(url('/enrolment/' . $enrol['enrol_no'])) ?>">See payment details</a>
      <?php else: ?>
        <p class="price big"><strong><?= $price === 0 ? 'Free' : e(Shop::naira($price)) ?></strong><?php if ($c['compare_price'] && $c['compare_price'] > $price): ?> <s><?= e(Shop::naira($c['compare_price'])) ?></s><?php endif; ?></p>
        <form method="post" action="<?= e(url('/learn/' . $c['slug'] . '/enrol')) ?>" class="form">
          <?= csrf_field() ?>
          <?php if ($price > 0): ?><label class="sr-only" for="ecoupon">Coupon code</label><input type="text" id="ecoupon" name="coupon" placeholder="Coupon code (optional)" maxlength="40" autocomplete="off" style="margin-bottom:10px"><?php endif; ?>
          <button class="btn btn-gold btn-block btn-lg" type="submit"><?= $price === 0 ? 'Enrol for free' : 'Enrol now' ?></button>
        </form>
        <?php if (!$loggedIn): ?><p class="small muted center">You will be asked to sign in or create a free account.</p><?php endif; ?>
        <?php if ($price > 0): ?><p class="small muted center">Pay by bank transfer. Access starts when we confirm your payment.</p><?php endif; ?>
        <?php if ($first): ?><p class="center"><a href="<?= e(url('/learn/' . $c['slug'] . '/lesson/' . $first)) ?>">▶ Try the free preview lesson</a></p><?php endif; ?>
      <?php endif; ?>
      <?php if ($isAdmin && !$soon): ?><p class="small center"><a href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>">✎ Edit this course (admin)</a> · <a href="<?= e(url('/learn/' . $c['slug'] . '/chat')) ?>">💬 Chat</a></p><?php endif; ?>
    </aside>
  </div>
</section>

<section class="section">
  <div class="wrap course-main">
    <div>
      <?php if ($c['description']): ?><div class="prose"><h2>About this course</h2><?= md($c['description']) ?></div><?php endif; ?>
      <?php if ($c['outcomes']): ?><div class="prose"><h2>What you will learn</h2><?= md($c['outcomes']) ?></div><?php endif; ?>
      <?php if ($c['requirements']): ?><div class="prose"><h2>Before you start</h2><?= md($c['requirements']) ?></div><?php endif; ?>

      <?php if (!$soon && ($cur['modules'] || $cur['final'])): ?>
      <h2 style="margin-top:2rem">Curriculum</h2>
      <div class="curriculum">
        <?php foreach ($cur['modules'] as $mi => $m): ?>
          <details class="module" <?= $mi === 0 ? 'open' : '' ?>>
            <summary><strong>Module <?= $mi + 1 ?>: <?= e($m['module']['title']) ?></strong> <span class="muted small"><?= count($m['lessons']) ?> lessons<?= $m['quizzes'] ? ' · quiz' : '' ?></span></summary>
            <ul>
              <?php foreach ($m['lessons'] as $l): $done = $progress && isset($progress['lessons'][(int)$l['id']]); $can = $active || $l['is_free_preview']; ?>
                <li>
                  <span class="lesson-ico"><?= $done ? '✔' : ($can ? '▶' : '🔒') ?></span>
                  <?php if ($can): ?><a href="<?= e(url('/learn/' . $c['slug'] . '/lesson/' . $l['id'])) ?>"><?= e($l['title']) ?></a><?php else: ?><?= e($l['title']) ?><?php endif; ?>
                  <?php if ($l['is_free_preview']): ?><span class="tag tag-verified">Free preview</span><?php endif; ?>
                  <?php if ($l['video_minutes']): ?><span class="muted small"><?= (int)$l['video_minutes'] ?> min</span><?php endif; ?>
                </li>
              <?php endforeach; ?>
              <?php foreach ($m['quizzes'] as $qz): ?>
                <li><span class="lesson-ico"><?= $progress && !empty($progress['quizzes'][(int)$qz['id']]['passed']) ? '✔' : '📝' ?></span> <?= $active ? '<a href="' . e(url('/learn/' . $c['slug'] . '/quiz/' . $qz['id'])) . '">' . e($qz['title']) . '</a>' : e($qz['title']) ?> <span class="muted small"><?= (int)$qz['question_count'] ?> questions</span></li>
              <?php endforeach; ?>
            </ul>
          </details>
        <?php endforeach; ?>
        <?php foreach ($cur['final'] as $qz): ?>
          <div class="module final"><strong>🎓 <?= $active ? '<a href="' . e(url('/learn/' . $c['slug'] . '/quiz/' . $qz['id'])) . '">' . e($qz['title']) . '</a>' : e($qz['title']) ?></strong> <span class="muted small">pass mark <?= (int)$qz['pass_mark'] ?>%<?= $qz['time_limit_minutes'] ? ' · ' . (int)$qz['time_limit_minutes'] . ' min' : '' ?></span></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <aside>
      <?php if ($rating['count'] > 0): ?><div class="card"><p class="rating-line big"><span class="stars" aria-hidden="true"><?= e(Community::stars($rating['avg'])) ?></span> <strong><?= e(number_format($rating['avg'], 1)) ?></strong> <span class="muted">from <?= (int)$rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?></span></p></div><div style="height:16px"></div><?php endif; ?>
      <div class="card">
        <h3>Taught by</h3>
        <p><strong><?= e(setting('owner_name')) ?></strong><br><span class="muted"><?= e(setting('owner_title')) ?></span><br><span class="muted small"><?= e(setting('owner_institution')) ?></span></p>
        <p><a href="<?= e(url('/about')) ?>">About the instructor →</a></p>
      </div>
      <div class="card" style="margin-top:16px"><h3>This course includes</h3><ul class="plain-links"><li>📐 Lesson notes with worked equations</li><li>📝 Quizzes after each module</li><li>🎓 A final exam</li><li>🏅 A certificate with a verification code</li></ul></div>
    </aside>
  </div>
</section>
<?php if (!$soon): partial('course_community', compact('c', 'u', 'enrol', 'active', 'rating', 'reviews', 'announcements', 'myReview')); endif; ?>
<?php if ($soon): ?><section class="band" id="newsletter"><div class="wrap band-inner"><div><h2>Tell me when it opens</h2><p>One short email when enrolment starts.</p></div><?php partial('newsletter_form'); ?></div></section><?php endif; ?>

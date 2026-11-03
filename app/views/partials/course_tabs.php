<?php
$pending = 0;
$openQ = 0;
$newRev = 0;
try {
    $pending = (int)Db::val("SELECT COUNT(*) FROM enrolments WHERE status = 'awaiting_payment'");
    $openQ = (int)Db::val("SELECT COUNT(*) FROM lesson_questions WHERE status = 'open'");
    $newRev = (int)Db::val("SELECT COUNT(*) FROM course_reviews WHERE status = 'pending'");
} catch (Throwable) {
}
?>
<nav class="tabs" aria-label="Courses">
  <a class="<?= ($tab ?? '') === 'courses' ? 'on' : '' ?>" href="<?= e(url('/admin/courses')) ?>">Courses</a>
  <a class="<?= ($tab ?? '') === 'enrolments' ? 'on' : '' ?>" href="<?= e(url('/admin/enrolments')) ?>">Students &amp; payments<?php if ($pending): ?> <span class="count"><?= $pending ?></span><?php endif; ?></a>
  <a class="<?= ($tab ?? '') === 'qa' ? 'on' : '' ?>" href="<?= e(url('/admin/qa')) ?>">Questions<?php if ($openQ): ?> <span class="count"><?= $openQ ?></span><?php endif; ?></a>
  <a class="<?= ($tab ?? '') === 'reviews' ? 'on' : '' ?>" href="<?= e(url('/admin/reviews')) ?>">Reviews<?php if ($newRev): ?> <span class="count"><?= $newRev ?></span><?php endif; ?></a>
  <a href="<?= e(url('/admin/settings/payments')) ?>">Bank details</a>
  <a href="<?= e(url('/learn')) ?>" target="_blank" rel="noopener">View Learn ↗</a>
</nav>

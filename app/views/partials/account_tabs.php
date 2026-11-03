<?php if (Auth::check() && !Auth::hasTwoFactor() && ($tab ?? '') !== 'security'): ?>
<div class="flash flash-error sec-nag"><strong>Your account is not protected by two-step verification.</strong> Anyone who learns your password could sign in as you. <a href="<?= e(url('/account/security')) ?>">Turn it on now (2 minutes)</a></div>
<?php endif; ?>
<?php if (Auth::check() && !EmailVerify::verified(Auth::user())): ?>
<div class="flash flash-info"><strong>Your email address is not verified.</strong> <a href="<?= e(url('/account/verify')) ?>">Verify my email</a> (we send a 6-digit code). It is needed before you can submit articles.</div>
<?php endif; ?>
<nav class="tabs" aria-label="My account">
  <a class="<?= ($tab ?? '') === 'overview' ? 'on' : '' ?>" href="<?= e(url('/account')) ?>">Overview</a>
  <a class="<?= ($tab ?? '') === 'orders' ? 'on' : '' ?>" href="<?= e(url('/account/orders')) ?>">My orders</a>
  <?php if (module_enabled('learn')): ?><a class="<?= ($tab ?? '') === 'courses' ? 'on' : '' ?>" href="<?= e(url('/account/courses')) ?>">My courses</a><?php endif; ?>
  <a class="<?= ($tab ?? '') === 'articles' ? 'on' : '' ?>" href="<?= e(url('/account/articles')) ?>">My articles</a>
  <a class="<?= ($tab ?? '') === 'profile' ? 'on' : '' ?>" href="<?= e(url('/account/profile')) ?>">My details &amp; password</a>
  <a class="<?= ($tab ?? '') === 'security' ? 'on' : '' ?>" href="<?= e(url('/account/security')) ?>">Security</a>
  <?php if (Auth::isAdmin()): ?><a href="<?= e(url('/admin')) ?>">Admin area →</a><?php endif; ?>
</nav>

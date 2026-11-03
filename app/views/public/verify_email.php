<section class="section">
  <div class="wrap auth-wrap">
    <div class="card auth-card">
      <h1>Verify your email</h1>
      <p>We sent a <strong>6-digit code</strong> to <strong><?= e($masked) ?></strong>. Type it below to confirm that this address is yours. It works for 15 minutes.</p>
      <?php if ($test): ?><p class="flash flash-info small">Test mode: no email is sent. The code was written to <code>storage/logs/mail.log</code>.</p><?php endif; ?>
      <form method="post" action="<?= e(url('/verify-email')) ?>" class="form" autocomplete="off">
        <?= csrf_field() ?>
        <label>Code from the email
          <input type="text" name="code" required autofocus inputmode="numeric" pattern="[0-9 ]*" maxlength="8" placeholder="123456" autocomplete="one-time-code" style="font-size:1.4rem;letter-spacing:.3em;text-align:center">
        </label>
        <button class="btn btn-gold btn-block" type="submit">Verify and continue</button>
      </form>
      <?php if ($left > 0 && $left < 5): ?><p class="small muted"><?= (int)$left ?> tr<?= $left === 1 ? 'y' : 'ies' ?> left for this code.</p><?php endif; ?>
      <form method="post" action="<?= e(url('/verify-email/resend')) ?>" class="center" style="margin-top:12px"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">Send a new code</button></form>
      <p class="small muted center">Nothing arrived? Look in the spam folder, then press "Send a new code".</p>
      <?php if (!$signedIn): ?>
        <form method="post" action="<?= e(url('/verify-email/cancel')) ?>" class="center"><?= csrf_field() ?><button class="btn btn-sm btn-ghost-dark" type="submit">Wrong email address? Start again</button></form>
      <?php endif; ?>
    </div>
  </div>
</section>

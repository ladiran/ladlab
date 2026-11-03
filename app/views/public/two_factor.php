<?php $cls = $admin ? 'login-card card' : 'card narrow-card'; ?>
<?php if (!$admin): ?><section class="section"><div class="wrap"><?php endif; ?>
<div class="<?= $cls ?>" style="<?= $admin ? '' : 'margin:0 auto' ?>">
  <h1><?= $recovery ? 'Use a recovery code' : 'Two-step verification' ?></h1>
  <p class="muted"><?= $recovery ? 'Enter one of the recovery codes you saved when you turned this on. Each code works once.' : 'Open your authenticator app (Google Authenticator, Microsoft Authenticator, Authy…) and type the 6-digit code for ' . e(setting('site_name')) . '.' ?></p>
  <form method="post" action="<?= e(url('/login/2fa')) ?>" class="form" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="mode" value="<?= $recovery ? 'recovery' : 'totp' ?>">
    <label><?= $recovery ? 'Recovery code' : 'Code from your app' ?>
      <?php if ($recovery): ?><input type="text" name="code" required autofocus maxlength="14" placeholder="XXXXX-XXXXX" autocapitalize="characters" spellcheck="false">
      <?php else: ?><input type="text" name="code" required autofocus inputmode="numeric" pattern="[0-9 ]*" maxlength="8" placeholder="123456" autocomplete="one-time-code"><?php endif; ?>
    </label>
    <button class="btn btn-gold btn-block" type="submit">Verify and sign in</button>
  </form>
  <p class="small center"><?= $recovery ? '<a href="' . e(url('/login/2fa')) . '">← Use the app code instead</a>' : '<a href="' . e(url('/login/2fa?recovery=1')) . '">Lost your phone? Use a recovery code</a>' ?></p>
  <p class="small muted center"><a href="<?= e(url($admin ? '/admin/login' : '/login')) ?>">Cancel and go back</a></p>
</div>
<?php if (!$admin): ?></div></section><?php endif; ?>

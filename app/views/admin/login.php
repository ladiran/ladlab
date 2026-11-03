<div class="login-card card">
  <h1><?= e(setting('site_name')) ?></h1>
  <p class="muted">Administrator sign in</p>
  <form method="post" action="<?= e(url('/admin/login')) ?>" class="form">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" required autofocus autocomplete="username"></label>
    <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
    <p class="auth-forgot"><a href="<?= e(url('/forgot-password')) ?>">Forgot your password?</a></p>
    <button class="btn btn-gold btn-block" type="submit">Sign in</button>
  </form>
  <p class="small muted center"><a href="<?= e(url('/')) ?>">← Back to the website</a></p>
</div>

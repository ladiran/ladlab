<section class="section">
  <div class="wrap auth-wrap">
    <div class="card auth-card">
      <h1>Forgot your password?</h1>
      <p class="muted">Enter the email you signed up with and we will send you a link to choose a new password.</p>
      <form method="post" action="<?= e(url('/forgot-password')) ?>" class="form">
        <?= csrf_field() ?><?= honeypot() ?>
        <label>Email
          <input type="email" name="email" required autofocus autocomplete="username" maxlength="190">
        </label>
        <button class="btn btn-gold btn-block" type="submit">Email me a reset link</button>
      </form>
      <p class="center small"><a href="<?= e(url('/login')) ?>">← Back to sign in</a></p>
    </div>
  </div>
</section>

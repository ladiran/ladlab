<section class="section">
  <div class="wrap auth-wrap">
    <div class="card auth-card">
      <h1>Create an account</h1>
      <p class="muted">Track your orders and check out faster. Prefer not to? You can always <a href="<?= e(url('/shop')) ?>">shop as a guest</a>.</p>
      <form method="post" action="<?= e(url('/register')) ?>" class="form">
        <?= csrf_field() ?><?= honeypot() ?>
        <label>Your name *
          <input type="text" name="name" value="<?= e((string)old('name')) ?>" required maxlength="150" autocomplete="name">
        </label>
        <label>Email *
          <input type="email" name="email" value="<?= e((string)old('email')) ?>" required maxlength="190" autocomplete="email">
        </label>
        <label>Phone / WhatsApp <small class="muted">(for delivery updates)</small>
          <input type="tel" name="phone" value="<?= e((string)old('phone')) ?>" maxlength="40" autocomplete="tel">
        </label>
        <div class="form-row">
          <label>Password * <small class="muted">(8+ characters)</small>
            <input type="password" name="password" required minlength="10" autocomplete="new-password">
          </label>
          <label>Repeat password *
            <input type="password" name="password2" required minlength="10" autocomplete="new-password">
          </label>
        </div>
        <label class="check"><input type="checkbox" name="agree" value="1" required> I accept the <a href="<?= e(url('/privacy')) ?>" target="_blank">privacy policy</a></label>
        <button class="btn btn-gold btn-block" type="submit">Create my account</button>
      </form>
      <p class="center small">Already have an account? <a href="<?= e(url('/login')) ?>"><strong>Sign in</strong></a></p>
    </div>
  </div>
</section>
<?php clear_old(); ?>

<section class="section">
  <div class="wrap auth-wrap">
    <div class="card auth-card">
      <h1>Sign in</h1>
      <p class="muted">Welcome back. Sign in to see your orders and check out faster.</p>
      <form method="post" action="<?= e(url('/login')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Email
          <input type="email" name="email" value="<?= e((string)old('email')) ?>" required autofocus autocomplete="username" maxlength="190">
        </label>
        <label>Password
          <input type="password" name="password" required autocomplete="current-password">
        </label>
        <p class="auth-forgot"><a href="<?= e(url('/forgot-password')) ?>">Forgot your password?</a></p>
        <button class="btn btn-gold btn-block" type="submit">Sign in</button>
      </form>
      <p class="center small">New here? <a href="<?= e(url('/register')) ?>"><strong>Create an account</strong></a></p>
      <?php if (!empty($_SESSION['cart'])): ?><p class="center small muted">Your cart is saved. You can also <a href="<?= e(url('/checkout')) ?>">check out as a guest</a>.</p><?php endif; ?>
    </div>
  </div>
</section>
<?php clear_old(); ?>

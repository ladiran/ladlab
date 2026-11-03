<?php $min = 10; ?>
<section class="section">
  <div class="wrap auth-wrap">
    <div class="card auth-card">
      <h1>Choose a new password</h1>
      <p class="muted">At least <?= $min ?> characters.</p>
      <form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" class="form">
        <?= csrf_field() ?>
        <label>New password
          <input type="password" name="password" required minlength="<?= $min ?>" autofocus autocomplete="new-password">
        </label>
        <label>Repeat new password
          <input type="password" name="password2" required minlength="<?= $min ?>" autocomplete="new-password">
        </label>
        <button class="btn btn-gold btn-block" type="submit">Change my password</button>
      </form>
    </div>
  </div>
</section>

<section class="card narrow-card">
  <form method="post" action="<?= e(url('/admin/account')) ?>" class="form">
    <?= csrf_field() ?>
    <label>Name <input type="text" name="name" value="<?= e($user['name']) ?>" required maxlength="150"></label>
    <label>Email (used to sign in) <input type="email" name="email" value="<?= e($user['email']) ?>" required maxlength="190"></label>
    <hr>
    <p class="muted">To change your password, enter a new one (at least 10 characters). Leave blank to keep the current password.</p>
    <label>New password <input type="password" name="password" minlength="10" autocomplete="new-password"></label>
    <label>Repeat new password <input type="password" name="password2" minlength="10" autocomplete="new-password"></label>
    <hr>
    <label>Current password (required to save any change) <input type="password" name="current_password" required autocomplete="current-password"></label>
    <button class="btn btn-gold" type="submit">Save</button>
  </form>
</section>

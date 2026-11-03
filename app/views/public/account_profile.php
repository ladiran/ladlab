<section class="page-hero"><div class="wrap"><h1>My details &amp; password</h1></div></section>
<section class="section">
  <div class="wrap">
    <?php partial('account_tabs', ['tab' => $tab]); ?>
    <div class="grid g2">
      <form method="post" action="<?= e(url('/account/profile')) ?>" class="form card">
        <h2>My details</h2>
        <?= csrf_field() ?>
        <label>Name <input type="text" name="name" value="<?= e($user['name']) ?>" required maxlength="150"></label>
        <label>Email <input type="email" value="<?= e($user['email']) ?>" disabled></label>
        <label>Phone / WhatsApp <input type="tel" name="phone" value="<?= e($user['phone']) ?>" maxlength="40"></label>
        <h3>Default delivery address</h3>
        <label>Street address <input type="text" name="address_line" value="<?= e($user['address_line']) ?>" maxlength="200"></label>
        <div class="form-row">
          <label>City / town <input type="text" name="city" value="<?= e($user['city']) ?>" maxlength="100"></label>
          <label>State
            <select name="state"><option value="">Choose…</option><?php foreach (NigeriaStates::LIST as $s): ?><option <?= $user['state'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
          </label>
        </div>
        <button class="btn btn-gold" type="submit">Save details</button>
      </form>
      <div>
        <form method="post" action="<?= e(url('/account/password')) ?>" class="form card">
          <h2>Change password</h2>
          <?= csrf_field() ?>
          <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
          <label>New password <input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
          <label>Repeat new password <input type="password" name="password2" required minlength="10" autocomplete="new-password"></label>
          <button class="btn btn-navy" type="submit">Change password</button>
        </form>
        <?php if ($user['role'] !== 'admin'): ?>
        <form method="post" action="<?= e(url('/account/delete')) ?>" class="form card" style="margin-top:20px" data-confirm="Delete your account? This cannot be undone.">
          <h2>Delete my account</h2>
          <p class="muted small">Removes your login and saved details. Past orders are kept only for accounting.</p>
          <?= csrf_field() ?>
          <label>Your password <input type="password" name="current_password" required autocomplete="current-password"></label>
          <button class="btn btn-danger" type="submit">Delete my account</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

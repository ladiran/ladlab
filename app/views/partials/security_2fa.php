<?php
/** Two-step verification panel. Expects: $user, $base, $enabled, $setup, $codes, $remaining, $isAdmin, $required, $events */
?>
<section class="card sec-card <?= $enabled ? 'sec-on' : 'sec-off' ?>" id="2fa">
  <h2>Two-step verification <span class="status <?= $enabled ? 'status-replied' : 'st-awaiting_payment' ?>"><?= $enabled ? 'ON' : 'OFF' ?></span></h2>

  <?php if ($codes): ?>
    <div class="flash flash-info"><strong>Save these recovery codes now.</strong> They are shown only once. Each works one time if you lose your phone. Store them in a password manager or print them and keep them somewhere safe.</div>
    <ul class="recovery-codes"><?php foreach ($codes as $c): ?><li><code><?= e($c) ?></code></li><?php endforeach; ?></ul>
    <p><button class="btn btn-sm btn-ghost" type="button" data-copy="<?= e(implode("\n", $codes)) ?>">Copy all</button> <button class="btn btn-sm btn-ghost" type="button" data-print>Print</button></p>
  <?php endif; ?>

  <?php if (!$enabled && !$setup): ?>
    <div class="flash flash-error"><strong>Strongly recommended.</strong> Right now anyone who learns your password can sign in as you<?= $isAdmin ? ' and control the entire website, its customers, orders and payments' : ' and see your orders and details' ?>. Two-step verification adds a 6-digit code from your phone, so a stolen password is not enough.</div>
    <p>It works with <strong>Google Authenticator</strong>, <strong>Microsoft Authenticator</strong>, Authy, 1Password, Bitwarden and any other TOTP app. It takes about two minutes.</p>
    <form method="post" action="<?= e(url('/' . $base . '/2fa/start')) ?>"><?= csrf_field() ?><button class="btn btn-gold btn-lg" type="submit">Set up two-step verification</button></form>

  <?php elseif (!$enabled && $setup): ?>
    <ol class="steps">
      <li><strong>Install an authenticator app</strong> on your phone (Google Authenticator or Microsoft Authenticator are free).</li>
      <li><strong>Scan this picture</strong> with the app (choose “Add account” → “Scan QR code”).<br><span class="qr-box"><?= $setup['svg'] ?></span><br>
        <small class="muted">Cannot scan? Choose “Enter a setup key” in the app and type: <code class="setup-key"><?= e($setup['grouped']) ?></code> (account: <?= e($user['email']) ?>, type: time-based).</small></li>
      <li><strong>Type the 6-digit code</strong> the app now shows:
        <form method="post" action="<?= e(url('/' . $base . '/2fa/confirm')) ?>" class="inline-edit" autocomplete="off"><?= csrf_field() ?>
          <input type="text" name="code" required inputmode="numeric" pattern="[0-9 ]*" maxlength="8" placeholder="123456" autocomplete="one-time-code" aria-label="6-digit code">
          <button class="btn btn-gold" type="submit">Turn on</button></form></li>
    </ol>
    <form method="post" action="<?= e(url('/' . $base . '/2fa/cancel')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit">Cancel</button></form>

  <?php else: ?>
    <p>✔ Signing in needs your password <strong>and</strong> a code from your authenticator app.</p>
    <p class="<?= $remaining <= 2 ? 'low' : 'muted' ?>">Recovery codes left: <strong><?= (int)$remaining ?></strong> of 10.<?= $remaining <= 3 ? ' Create new ones below.' : '' ?></p>
    <details class="sec-details"><summary>Create new recovery codes</summary>
      <form method="post" action="<?= e(url('/' . $base . '/2fa/recovery')) ?>" class="form inline-edit"><?= csrf_field() ?>
        <input type="password" name="current_password" required placeholder="Your password" autocomplete="current-password" aria-label="Your password"><button class="btn btn-navy" type="submit">Create new codes</button></form>
      <p class="small muted">The old codes stop working.</p></details>
    <details class="sec-details"><summary>Turn off two-step verification</summary>
      <?php if ($required): ?><p class="muted"><?= ($user['role'] ?? '') === 'lecturer' ? 'It is compulsory for lecturers, so it cannot be turned off.' : 'Administrators are required to use it, so it cannot be turned off.' ?></p>
      <?php else: ?>
      <p class="small">We advise leaving it on. To turn it off, enter your password and a current code (or a recovery code).</p>
      <form method="post" action="<?= e(url('/' . $base . '/2fa/disable')) ?>" class="form" autocomplete="off"><?= csrf_field() ?>
        <input type="password" name="current_password" required placeholder="Your password" autocomplete="current-password" aria-label="Your password">
        <input type="text" name="code" required placeholder="123456 or recovery code" aria-label="Code" maxlength="14">
        <button class="btn btn-sm btn-danger" type="submit">Turn off</button></form>
      <?php endif; ?></details>
  <?php endif; ?>
</section>

<section class="card sec-card">
  <h2>Signed-in browsers and devices</h2>
  <p class="muted">If you ever think someone else has your password, press this. Every other browser is signed out at once. (Changing your password does the same.)</p>
  <form method="post" action="<?= e(url('/' . $base . '/signout-all')) ?>" data-confirm="Sign out of every other browser and device?"><?= csrf_field() ?><button class="btn btn-navy" type="submit">Sign out everywhere else</button></form>
</section>

<section class="card sec-card">
  <h2>Your recent sign-ins</h2>
  <?php if ($events): ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>When</th><th>Result</th><th>Address</th><th>Browser</th></tr></thead><tbody>
    <?php foreach ($events as $ev): ?><tr><td><?= e(fmt_date($ev['created_at'], 'j M, H:i')) ?></td><td><?= $ev['success'] ? '✔ signed in' : '<span class="low">✖ failed</span>' ?> <small class="muted">(<?= e($ev['method']) ?>)</small></td><td><?= e($ev['ip']) ?></td><td><small><?= e(mb_substr($ev['user_agent'], 0, 70)) ?></small></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <p class="small muted">Do not recognise one? Press “Sign out everywhere else” and change your password.</p>
  <?php else: ?><p class="muted">Nothing recorded yet.</p><?php endif; ?>
</section>

<?php $icons = ['ok' => '✅', 'warn' => '⚠️', 'bad' => '⛔']; ?>
<p class="muted">Your site's security at a glance, with plain-language advice. <strong><?= (int)$score['bad'] ?></strong> to fix, <strong><?= (int)$score['warn'] ?></strong> to consider, <strong><?= (int)$score['ok'] ?></strong> good.</p>

<?php partial('security_2fa', get_defined_vars()); ?>

<section class="card sec-card">
  <h2>Security checklist</h2>
  <ul class="checklist">
    <?php foreach ($checks as $c): ?>
      <li class="chk chk-<?= e($c['status']) ?>">
        <span class="chk-ico" aria-hidden="true"><?= $icons[$c['status']] ?></span>
        <div><strong><?= e($c['title']) ?></strong><?php if ($c['detail'] !== ''): ?><br><span class="muted"><?= e($c['detail']) ?></span><?php endif; ?>
          <?php if ($c['action']): ?>
            <form method="post" action="<?= e(url('/admin/security/' . ($c['action']['do'] === 'encrypt' ? 'encrypt' : 'delete-installer'))) ?>" class="inline-edit sec-act"><?= csrf_field() ?>
              <input type="password" name="current_password" required placeholder="Your password" autocomplete="current-password" aria-label="Your password"><button class="btn btn-sm btn-gold" type="submit"><?= e($c['action']['label']) ?></button></form>
          <?php endif; ?></div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="card sec-card">
  <h2>Settings</h2>
  <form method="post" action="<?= e(url('/admin/security/settings')) ?>" class="form admin-form"><?= csrf_field() ?>
    <?php partial('field', ['name' => 'require_2fa_admin', 'type' => 'checkbox', 'label' => 'Require two-step verification for every administrator', 'o' => [], 'value' => Settings::get('require_2fa_admin') === '1' ? '1' : '0']); ?>
    <?php partial('field', ['name' => 'security_alert_emails', 'type' => 'checkbox', 'label' => 'Email people about security events (new browser sign-in, password or two-step changes)', 'o' => [], 'value' => $alerts ? '1' : '0']); ?>
    <div class="field"><label for="sp">Confirm with your password</label><input type="password" id="sp" name="current_password" required autocomplete="current-password"></div>
    <div class="form-actions"><button class="btn btn-gold" type="submit">Save settings</button></div>
  </form>
</section>

<section class="card sec-card">
  <h2>Recent sign-in activity (everyone)</h2>
  <p class="muted"><?= (int)$failed24 ?> failed attempt<?= $failed24 === 1 ? '' : 's' ?> in the last 24 hours.</p>
  <div class="table-wrap"><table class="table"><thead><tr><th>When</th><th>Who</th><th>Result</th><th>Address</th><th>Browser</th></tr></thead><tbody>
    <?php foreach ($activity as $ev): ?><tr class="<?= $ev['success'] ? '' : 'row-hidden' ?>"><td><?= e(fmt_date($ev['created_at'], 'j M, H:i')) ?></td><td><?= e($ev['name'] ?: $ev['email']) ?></td><td><?= $ev['success'] ? '✔' : '<span class="low">✖ failed</span>' ?> <small class="muted">(<?= e($ev['method']) ?>)</small></td><td><?= e($ev['ip']) ?></td><td><small><?= e(mb_substr($ev['user_agent'], 0, 60)) ?></small></td></tr><?php endforeach; ?>
    <?php if (!$activity): ?><tr><td colspan="5" class="muted">Nothing recorded yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>

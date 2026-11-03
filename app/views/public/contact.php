<section class="page-hero"><div class="wrap"><h1>Contact</h1><p class="lead">Questions, consulting, speaking invitations, workshops or supervision: send a message and I will reply by email.</p></div></section>
<?php $res = $_SESSION['contact_result'] ?? null; unset($_SESSION['contact_result']); ?>
<section class="section">
  <?php if ($res): ?>
  <div class="wrap"><div id="result" class="result-box <?= $res['ok'] ? 'result-ok' : 'result-fail' ?>" role="<?= $res['ok'] ? 'status' : 'alert' ?>" tabindex="-1">
    <span class="result-ico" aria-hidden="true"><?= $res['ok'] ? '✅' : '⚠️' ?></span>
    <div><strong><?= e($res['title']) ?></strong><p><?= e($res['text']) ?></p></div>
  </div></div>
  <?php endif; ?>
  <div class="wrap contact-grid">
    <form method="post" action="<?= e(url('/contact')) ?>" class="form card" novalidate>
      <?= csrf_field() ?>
      <?= honeypot() ?>
      <label>Your name *
        <input type="text" name="name" value="<?= e((string)old('name')) ?>" required maxlength="150" autocomplete="name">
      </label>
      <div class="form-row">
        <label>Email *
          <input type="email" name="email" value="<?= e((string)old('email')) ?>" required maxlength="190" autocomplete="email">
        </label>
        <label>Phone / WhatsApp
          <input type="tel" name="phone" value="<?= e((string)old('phone')) ?>" maxlength="40" autocomplete="tel">
        </label>
      </div>
      <label>What is this about?
        <select name="type">
          <?php foreach ($types as $k => $label): ?><option value="<?= e($k) ?>" <?= (old('type') ?: $selectedType) === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Subject
        <input type="text" name="subject" value="<?= e((string)old('subject')) ?>" maxlength="200">
      </label>
      <label>Message *
        <textarea name="message" rows="7" required minlength="10" maxlength="5000"><?= e((string)old('message')) ?></textarea>
      </label>
      <button class="btn btn-gold" type="submit">Send message</button>
      <p class="small muted">Your details are used only to reply to you. See the <a href="<?= e(url('/privacy')) ?>">privacy policy</a>.</p>
    </form>
    <aside class="contact-side">
      <div class="card">
        <h2>Other ways to reach me</h2>
        <ul class="plain-links">
          <?php if (setting('contact_email')): ?><li>✉ <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
          <?php if (setting('phone')): ?><li>☎ <?= e(setting('phone')) ?></li><?php endif; ?>
          <?php if (setting('whatsapp')): ?><li>💬 <a href="https://wa.me/<?= e(setting('whatsapp')) ?>" target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a></li><?php endif; ?>
          <?php if (setting('city')): ?><li>📍 <?= e(setting('city')) ?></li><?php endif; ?>
          <?php if (!setting('contact_email') && !setting('phone') && !setting('whatsapp')): ?><li class="muted">Use the form: direct contact details will appear here once added in the admin area.</li><?php endif; ?>
        </ul>
      </div>
    </aside>
  </div>
</section>
<?php clear_old(); ?>

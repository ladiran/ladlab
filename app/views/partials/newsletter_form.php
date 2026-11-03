<form method="post" action="<?= e(url('/subscribe')) ?>" class="newsletter <?= !empty($compact) ? 'compact' : '' ?>">
  <?= csrf_field() ?>
  <?= honeypot() ?>
  <input type="hidden" name="return" value="<?= e(request_path()) ?>">
  <label class="sr-only" for="nl-email-<?= !empty($compact) ? 'f' : 'b' ?>">Email address</label>
  <input type="email" id="nl-email-<?= !empty($compact) ? 'f' : 'b' ?>" name="email" placeholder="your@email.com" required maxlength="190" autocomplete="email">
  <button type="submit" class="btn btn-gold">Subscribe</button>
  <?php if (empty($compact)): ?><small>One short email a week. Unsubscribe any time.</small><?php endif; ?>
</form>

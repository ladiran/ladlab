<section class="page-hero"><div class="wrap"><h1>Report a problem</h1><p class="lead">About: <strong><?= e($r['title']) ?></strong></p></div></section>
<section class="section">
  <div class="wrap narrow-wide">
    <form method="post" action="<?= e(url('/resources/' . $r['id'] . '/report')) ?>" class="card form">
      <?= csrf_field() ?>
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="field"><label for="rr_reason">What is the problem?</label>
        <select id="rr_reason" name="reason"><option value="copyright">I believe this infringes a copyright</option><option value="broken">The file or link does not work</option><option value="wrong">It is wrong or misleading</option><option value="other">Something else</option></select></div>
      <div class="field"><label for="rr_details">Details</label><textarea id="rr_details" name="details" rows="6" required minlength="10" maxlength="3000"></textarea>
        <small class="hint">For a copyright concern: who owns the work, and where is it lawfully available? Our <a href="<?= e(url('/copyright')) ?>">takedown policy</a> lists what to include.</small></div>
      <div class="form-row">
        <div class="field"><label for="rr_name">Your name (optional)</label><input type="text" id="rr_name" name="name" maxlength="150"></div>
        <div class="field"><label for="rr_email">Your email (so we can reply)</label><input type="email" id="rr_email" name="email" maxlength="190"></div>
      </div>
      <button class="btn btn-navy" type="submit">Send report</button>
    </form>
  </div>
</section>
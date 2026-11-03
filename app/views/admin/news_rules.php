<?php partial('news_tabs', ['tab' => $tab]); ?>
<div class="two-col">
  <section class="card">
    <h2>Blocked words &amp; phrases</h2>
    <p class="muted">A story whose headline or excerpt contains any of these is never published (sponsored posts, clickbait, pseudoscience…). Not case-sensitive.</p>
    <form method="post" action="<?= e(url('/admin/news/terms')) ?>" class="form">
      <?= csrf_field() ?>
      <label>Add terms (one per line, or comma-separated)<textarea name="term" rows="3" placeholder="e.g. miracle cure"></textarea></label>
      <button class="btn btn-gold" type="submit">Add</button>
    </form>
    <div class="terms">
      <?php foreach ($terms as $t): ?>
        <form method="post" action="<?= e(url('/admin/news/terms/' . $t['id'] . '/delete')) ?>" class="inline term-chip"><?= csrf_field() ?><?= e($t['term']) ?> <button type="submit" aria-label="Remove <?= e($t['term']) ?>" title="Remove">×</button></form>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="card">
    <h2>Thresholds, keywords &amp; digest</h2>
    <p class="muted">How “breaking” is decided, how long stories are re-checked, ticker length, the science-topic words for general news feeds, and the daily email summary.</p>
    <p><a class="btn btn-navy" href="<?= e(url('/admin/settings/news')) ?>">Edit news rules →</a></p>
    <h3>The rules in plain words</h3>
    <ul>
      <li>Only whitelisted sources over HTTPS; links must stay on the source’s own domains.</li>
      <li><strong>BREAKING</strong> needs a breaking word in the headline <em>and</em> a Tier 1 authority (allowed to break news) or enough independent Tier 1/2 sources within the window. Press releases and sources on probation never qualify.</li>
      <li>Published stories are re-checked against the original page; removed or substantially changed stories are hidden or updated.</li>
      <li>Sources that keep failing, or suddenly flood the system, pause themselves.</li>
    </ul>
  </section>
</div>

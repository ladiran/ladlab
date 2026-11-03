<?php
$portrait = setting('portrait') ? upload_url(setting('portrait')) : asset('img/portrait-placeholder.svg');
$members = nl_lines(setting('memberships'));
$socials = Content::socials();
?>
<section class="page-hero">
  <div class="wrap">
    <h1>About</h1>
  </div>
</section>
<section class="section">
  <div class="wrap about-grid">
    <aside class="about-side">
      <img src="<?= e($portrait) ?>" alt="<?= e(setting('owner_name')) ?>" class="portrait" width="320" height="390">
      <h2><?= e(setting('owner_name')) ?></h2>
      <p class="muted"><?= e(setting('owner_title')) ?><br><?= e(setting('owner_institution')) ?></p>
      <p>
        <?php if ($cv): ?>
          <a class="btn btn-gold" href="<?= e(upload_url($cv['path'])) ?>" target="_blank" rel="noopener">Download CV (PDF)</a>
        <?php else: ?>
          <span class="btn btn-disabled" title="The CV can be uploaded in the admin area">CV coming soon</span>
        <?php endif; ?>
      </p>
      <p><a class="btn btn-outline-dark" href="<?= e(url('/contact')) ?>">Contact me</a></p>
      <?php if ($socials): ?>
        <ul class="plain-links"><?php foreach ($socials as $label => $href): ?><li><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?> ↗</a></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </aside>
    <div class="prose">
      <h2>Biography</h2>
      <?= md(setting('bio_full')) ?>

      <?php if ($timeline): ?>
        <h2>Timeline</h2>
        <ol class="timeline">
          <?php foreach ($timeline as $t): ?>
            <li><span class="when"><?= e($t['year']) ?></span><div><strong><?= e($t['title']) ?></strong><?php if ($t['detail']): ?><br><span class="muted"><?= e($t['detail']) ?></span><?php endif; ?><?php if ($t['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></div></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

      <?php if ($awards): ?>
        <h2>Awards &amp; honours</h2>
        <ul class="awards">
          <?php foreach ($awards as $a): ?>
            <li><strong><?= e($a['title']) ?></strong><?= $a['year'] ? ' (' . (int)$a['year'] . ')' : '' ?><?php if ($a['org']): ?><br><span class="muted"><?= e($a['org']) ?></span><?php endif; ?><?php if ($a['description']): ?><br><?= e($a['description']) ?><?php endif; ?><?php if ($a['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($members): ?>
        <h2>Memberships &amp; affiliations</h2>
        <ul><?php foreach ($members as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>
</section>

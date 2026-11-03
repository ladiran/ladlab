<section class="page-hero"><div class="wrap">
  <p class="small"><a href="<?= e(url('/toolbox')) ?>" style="color:var(--sky-light)">← All free tools</a></p>
  <h1><?= e($t['title']) ?></h1>
  <p class="lead"><?= e($t['description']) ?></p>
  <p><a class="btn btn-gold btn-lg" href="<?= e(url('/toolbox/go/' . $t['id'])) ?>" target="_blank" rel="noopener noreferrer">Open <?= e(Toolbox::host($t['url'])) ?> ↗</a></p>
</div></section>
<section class="section">
  <div class="wrap narrow-wide">
    <p class="tool-tags"><span class="tag"><?= e(Toolbox::KINDS[$t['kind']] ?? '') ?></span> <span class="tag"><?= e($t['subject']) ?></span> <span class="tag tag-verified"><?= e(Toolbox::LICENSES[$t['license']] ?? 'Free') ?></span> <span class="muted small">by <?= e($t['provider'] !== '' ? $t['provider'] : Toolbox::host($t['url'])) ?></span></p>
    <?php if (trim((string)$t['notes']) !== ''): ?><div class="prose"><?= md($t['notes']) ?></div><?php endif; ?>
    <?php partial('toolbox_suggest', ['back' => 'toolbox/' . $t['id']]); ?>
  </div>
</section>

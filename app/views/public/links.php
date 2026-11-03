<section class="section links-card-wrap">
  <div class="wrap narrow center">
    <?php if (setting('portrait')): ?><img class="links-portrait" src="<?= e(upload_url(setting('portrait'))) ?>" alt="<?= e(setting('owner_name')) ?>"><?php endif; ?>
    <h1><?= e(setting('owner_name')) ?></h1>
    <p class="muted"><?= e(setting('owner_title')) ?></p>
    <ul class="link-buttons">
      <?php foreach ($rows as $r): $href = str_starts_with($r['url'], '/') ? url($r['url']) : $r['url']; ?>
        <li><a class="btn btn-navy btn-block btn-lg" href="<?= e($href) ?>" <?= str_starts_with($r['url'], '/') ? '' : 'target="_blank" rel="noopener noreferrer"' ?>><?= $r['icon'] !== '' ? '<span aria-hidden="true">' . e($r['icon']) . '</span> ' : '' ?><?= e($r['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="page-hero"><div class="wrap"><h1>In the media</h1><p class="lead">Interviews, articles, radio, TV and podcasts featuring <?= e(setting('owner_name')) ?>.</p><p><a class="btn btn-outline" href="<?= e(url('/speaking#media-kit')) ?>">Media kit and bios</a> <a class="btn btn-gold" href="<?= e(url('/contact?type=general')) ?>">Press enquiries</a></p></div></section>
<section class="section">
  <div class="wrap">
    <?php if ($byYear): foreach ($byYear as $year => $items): ?>
      <h2 class="section-title"><?= e((string)$year) ?></h2>
      <ul class="press-list">
        <?php foreach ($items as $i): ?>
          <li class="press">
            <p><span class="tag"><?= e(ucfirst($i['kind'])) ?></span> <strong><?= e($i['outlet']) ?></strong><?= $i['pub_date'] ? ' <span class="muted small">· ' . e(fmt_date($i['pub_date'], 'j M Y')) . '</span>' : '' ?><?php if ($i['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></p>
            <h3><?= $i['url'] ? '<a href="' . e($i['url']) . '" target="_blank" rel="noopener noreferrer">' . e($i['title']) . '</a>' : e($i['title']) ?></h3>
            <?php if ($i['quote']): ?><blockquote><?= e($i['quote']) ?></blockquote><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endforeach; else: ?><p class="empty">Media coverage will be listed here.</p><?php endif; ?>
  </div>
</section>

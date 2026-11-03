<?php
$base = '/news';
$qs = function (array $over) use ($filters) {
    $q = array_filter(array_merge($filters, $over), fn($v) => $v !== '' && $v !== null);
    return url('/news' . ($q ? '?' . http_build_query($q) : ''));
};
$regions = ['' => 'All', 'nigeria' => '🇳🇬 Nigeria', 'africa' => 'Africa', 'world' => 'World'];
?>
<section class="page-hero">
  <div class="wrap">
    <h1>Science News</h1>
    <p class="lead">Headlines from trusted sources in Nigeria and around the world, gathered automatically and always linked to the original. <a href="<?= e(url('/news/sources')) ?>">How we choose and check sources →</a></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($breaking): ?>
      <div class="breaking-box" role="region" aria-label="Breaking now">
        <strong>🔴 Breaking now</strong>
        <ul><?php foreach ($breaking as $b): ?><li><a href="<?= e($b['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($b['title']) ?></a> <small class="muted">· <?= e($b['source_name']) ?>, <?= e(time_ago($b['published_at'])) ?></small></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="news-filters">
      <p class="filters-row">
        <?php foreach ($regions as $k => $label): ?><a class="pill <?= $filters['region'] === $k && $filters['view'] === '' ? 'on' : '' ?>" href="<?= e($qs(['region' => $k, 'view' => '', 'page' => ''])) ?>"><?= e($label) ?></a><?php endforeach; ?>
        <a class="pill pill-breaking <?= $filters['view'] === 'breaking' ? 'on' : '' ?>" href="<?= e($qs(['view' => 'breaking', 'region' => '', 'page' => ''])) ?>">🔴 Breaking</a>
        <a class="pill" href="<?= e(url('/news/nobel')) ?>">🏅 Nobel Prize</a>
      </p>
      <p class="filters-row">
        <?php foreach (NewsEngine::TOPICS as $k => $label): if ($k === 'nobel') continue; ?><a class="pill pill-sm <?= $filters['topic'] === $k ? 'on' : '' ?>" href="<?= e($qs(['topic' => $filters['topic'] === $k ? '' : $k, 'page' => ''])) ?>"><?= e($label) ?></a><?php endforeach; ?>
      </p>
      <form method="get" action="<?= e(url('/news')) ?>" class="filters">
        <?php foreach (['region', 'topic', 'view'] as $k): if ($filters[$k] !== ''): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($filters[$k]) ?>"><?php endif; endforeach; ?>
        <label class="sr-only" for="nq">Search news</label>
        <input type="search" id="nq" name="q" value="<?= e($filters['q']) ?>" placeholder="Search science news…" maxlength="80">
        <button class="btn btn-navy" type="submit">Search</button>
        <?php if ($filters['q']): ?><a class="btn btn-ghost-dark" href="<?= e($qs(['q' => '', 'page' => ''])) ?>">Clear</a><?php endif; ?>
      </form>
    </div>

    <?php if ($items): ?>
      <p class="muted small"><?= (int)$total ?> stor<?= $total === 1 ? 'y' : 'ies' ?></p>
      <div class="news-list">
        <?php foreach ($items as $idx => $n): partial('news_card', ['n' => $n, 'lead' => $idx === 0 && $page === 1 && $filters['q'] === '']); endforeach; ?>
      </div>
      <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Pages">
          <?php if ($page > 1): ?><a href="<?= e($qs(['page' => $page - 1])) ?>">← Newer</a><?php else: ?><span></span><?php endif; ?>
          <span>Page <?= (int)$page ?> of <?= (int)$pages ?></span>
          <?php if ($page < $pages): ?><a href="<?= e($qs(['page' => $page + 1])) ?>">Older →</a><?php else: ?><span></span><?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="empty">No stories match. <?= $filters['view'] === 'breaking' ? 'Nothing is breaking right now.' : 'New stories appear automatically every few minutes.' ?></p>
    <?php endif; ?>

    <p class="small muted news-disclaimer">Headlines and excerpts belong to their publishers and link to the original. We do not rewrite them. Spotted a mistake? Use “Report a problem” on any story.</p>
  </div>
</section>
<section class="band"><div class="wrap band-inner"><div><h2>Never miss a discovery</h2><p>Join the weekly science digest.</p></div><?php partial('newsletter_form'); ?></div></section>
<?php clear_old(); ?>

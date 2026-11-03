<?php
?>
<section class="page-hero">
  <div class="wrap">
    <h1>🏅 Nobel Prize <?= (int)$year ?></h1>
    <p class="lead">Official laureates and citations, straight from NobelPrize.org. Prizes appear here the moment they are announced.</p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <p class="filters-row">
      <?php for ($y = $now; $y >= $now - 5; $y--): ?><a class="pill <?= $y === $year ? 'on' : '' ?>" href="<?= e(url('/news/nobel?year=' . $y)) ?>"><?= $y ?></a><?php endfor; ?>
    </p>

    <?php if ($data === null): ?>
      <p class="empty">Nobel Prize data could not be loaded right now (it is fetched from NobelPrize.org). Please try again shortly.</p>
    <?php else: ?>
      <div class="grid g2">
        <?php foreach ($categories as $code => [$label, $path]): $p = $data[$code] ?? null; ?>
          <article class="card nobel-card">
            <h2><?= e($p['full'] ?? 'The Nobel Prize in ' . $label) ?></h2>
            <?php if ($p && $p['laureates']): ?>
              <?php if ($p['date']): ?><p class="muted small">Announced <?= e(fmt_date($p['date'], 'j F Y')) ?></p><?php endif; ?>
              <ul class="laureates">
                <?php foreach ($p['laureates'] as $l): ?>
                  <li><strong><?= e($l['name']) ?></strong><?= $l['portion'] ? ' <span class="muted small">(' . e($l['portion']) . ')</span>' : '' ?><?php if ($l['motivation']): ?><br><span class="citation"><?= e(ucfirst($l['motivation'])) ?>.</span><?php endif; ?></li>
                <?php endforeach; ?>
              </ul>
              <p><a href="https://www.nobelprize.org/prizes/<?= e($path) ?>/<?= (int)$year ?>/summary/" target="_blank" rel="noopener noreferrer">Official prize page ↗</a></p>
            <?php else: ?>
              <p class="muted"><?= $year === $now ? 'Not yet announced.' : 'No laureates are listed for this category in the data.' ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
      <p class="muted small">Source: NobelPrize.org open data (CC0). Citations are quoted exactly as published by the Nobel Foundation.</p>
    <?php endif; ?>

    <?php if ($items): ?>
      <h2 class="section-title" style="margin-top:2rem">The Nobel Prize in the news</h2>
      <div class="news-list">
        <?php $items = (function (array $rows) { foreach ($rows as &$r) { $r['reported_by'] = [$r['source_name']]; } return $rows; })($items); foreach ($items as $n): partial('news_card', ['n' => $n]); endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php clear_old(); ?>

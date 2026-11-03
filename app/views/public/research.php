<section class="page-hero"><div class="wrap"><h1>Research</h1></div></section>

<section class="section">
  <div class="wrap">
    <div class="prose narrow"><?= md(setting('research_intro', "My research focuses on the atmosphere: how the West African monsoon works, how aerosols and clouds interact, and what that means for weather, climate and people in the region.\n\nBelow you will find my research areas, publications, talks and media. Interested in collaborating? [Get in touch](/contact).")) ?></div>
  </div>
</section>

<?php if ($projects): ?>
<section class="section section-alt">
  <div class="wrap">
    <h2 class="section-title">Research areas &amp; projects</h2>
    <div class="grid g3">
      <?php foreach ($projects as $pr): ?>
        <article class="card project">
          <?php if ($pr['image']): ?><img src="<?= e(upload_url($pr['image'])) ?>" alt="" loading="lazy"><?php endif; ?>
          <span class="tag"><?= e(ucfirst($pr['status'])) ?></span><?php if ($pr['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?>
          <h3><?= e($pr['title']) ?></h3>
          <p><?= nl2br(e((string)$pr['summary'])) ?></p>
          <?php if ($pr['url']): ?><p><a href="<?= e($pr['url']) ?>" target="_blank" rel="noopener noreferrer">More information →</a></p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" id="publications">
  <div class="wrap">
    <h2 class="section-title">Publications</h2>
    <form method="get" action="<?= e(url('/research')) ?>#publications" class="filters">
      <label class="sr-only" for="q">Search</label>
      <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Search title, author, topic…">
      <label class="sr-only" for="year">Year</label>
      <select id="year" name="year"><option value="">All years</option><?php foreach ($years as $y): ?><option value="<?= (int)$y ?>" <?= (string)$filters['year'] === (string)$y ? 'selected' : '' ?>><?= (int)$y ?></option><?php endforeach; ?></select>
      <label class="sr-only" for="type">Type</label>
      <select id="type" name="type"><option value="">All types</option><?php foreach ($types as $t): ?><option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e(Importer::TYPES[$t] ?? $t) ?></option><?php endforeach; ?></select>
      <button class="btn btn-navy" type="submit">Filter</button>
      <?php if ($filters['q'] || $filters['year'] || $filters['type']): ?><a class="btn btn-ghost-dark" href="<?= e(url('/research#publications')) ?>">Clear</a><?php endif; ?>
    </form>
    <p class="muted"><?= count($publications) ?> publication<?= count($publications) === 1 ? '' : 's' ?></p>
    <?php if ($publications): ?>
      <div class="stack">
        <?php $lastYear = null; foreach ($publications as $p): ?>
          <?php if ($p['year'] !== $lastYear): $lastYear = $p['year']; ?><h3 class="year-head"><?= $p['year'] ? (int)$p['year'] : 'Undated' ?></h3><?php endif; ?>
          <?php partial('publication', ['p' => $p, 'full' => true]); ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">No publications match your search.</p>
    <?php endif; ?>
  </div>
</section>

<?php if ($talks): ?>
<section class="section section-alt">
  <div class="wrap">
    <h2 class="section-title">Talks &amp; media</h2>
    <ul class="talks">
      <?php foreach ($talks as $t): ?>
        <li>
          <span class="when"><?= e(fmt_date($t['talk_date'], 'M Y')) ?></span>
          <div>
            <strong><?= $t['url'] ? '<a href="' . e($t['url']) . '" target="_blank" rel="noopener noreferrer">' . e($t['title']) . '</a>' : e($t['title']) ?></strong>
            <span class="tag"><?= e(ucfirst($t['kind'])) ?></span><?php if ($t['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?><br>
            <span class="muted"><?= e(trim($t['event'] . ($t['location'] ? ', ' . $t['location'] : ''))) ?></span>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="band"><div class="wrap band-inner"><div><h2>Want to collaborate?</h2><p>I welcome collaborations, student projects and data-sharing partnerships.</p></div><a class="btn btn-gold" href="<?= e(url('/contact?type=consulting')) ?>">Get in touch</a></div></section>

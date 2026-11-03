<section class="page-hero"><div class="wrap"><h1>Free resources</h1><p class="lead">Formula sheets, notes and problem sets for physics students and teachers. Free to download and share.</p></div></section>
<section class="section">
  <div class="wrap">
    <?php if ($byTopic): foreach ($byTopic as $topic => $items): ?>
      <h2 class="section-title"><?= e((string)$topic) ?></h2>
      <div class="grid g3">
        <?php foreach ($items as $r): ?>
          <article class="card resource">
            <h3>📄 <?= e($r['title']) ?></h3>
            <?php if ($r['description'] !== ''): ?><p class="muted"><?= e($r['description']) ?></p><?php endif; ?>
            <p class="res-rights"><?php $deed = Rights::deed($r['license']); ?><span class="tag tag-verified"><?= $deed ? '<a href="' . e($deed) . '" target="_blank" rel="noopener noreferrer">' . e(Rights::short($r['license'])) . '</a>' : e(Rights::short($r['license'])) ?></span><?php if ($r['author'] !== ''): ?> <span class="small muted">by <?= $r['source_url'] !== '' ? '<a href="' . e($r['source_url']) . '" target="_blank" rel="noopener noreferrer">' . e($r['author']) . '</a>' : e($r['author']) ?></span><?php endif; ?></p>
            <p><a class="btn btn-gold" href="<?= e(url('/resources/' . $r['id'] . '/download')) ?>">Download<?= $r['pdf_file'] !== '' ? ' (PDF)' : '' ?></a> <?php if ((int)$r['downloads'] >= 10): ?><span class="muted small"><?= (int)$r['downloads'] ?> downloads</span><?php endif; ?> <a class="small" href="<?= e(url('/resources/' . $r['id'] . '/report')) ?>">Report a problem</a></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endforeach; else: ?><p class="empty">Free downloads will appear here soon.</p><?php endif; ?>
    <p class="muted small center" style="margin-top:1.5rem">Each item shows its licence. Free to download does not always mean free to copy or sell: follow the licence. Concerned about something here? Read our <a href="<?= e(url('/copyright')) ?>">copyright and takedown policy</a>.</p>
    <?php if (module_enabled('learn')): ?><p class="center" style="margin-top:2rem">Want the full lessons and quizzes? <a href="<?= e(url('/learn')) ?>">See the courses →</a></p><?php endif; ?>
  </div>
</section>

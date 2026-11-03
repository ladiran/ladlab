<?php
/** @var array $p */
$typeLabel = Importer::TYPES[$p['type']] ?? 'Other';
$cite = trim(($p['authors'] ? $p['authors'] . ' ' : '') . ($p['year'] ? '(' . $p['year'] . '). ' : '') . $p['title'] . '. ' . ($p['venue'] ? $p['venue'] . '. ' : '') . ($p['doi'] ? 'https://doi.org/' . $p['doi'] : ''));
$link = $p['doi'] ? 'https://doi.org/' . $p['doi'] : $p['url'];
?>
<article class="pub card">
  <div class="pub-meta">
    <span class="tag"><?= e($typeLabel) ?></span>
    <?php if ($p['year']): ?><span class="pub-year"><?= (int)$p['year'] ?></span><?php endif; ?>
    <?php if ($p['is_sample']): ?><span class="badge-sample">sample</span><?php endif; ?>
  </div>
  <h3><?= $link ? '<a href="' . e($link) . '" target="_blank" rel="noopener noreferrer">' . e($p['title']) . '</a>' : e($p['title']) ?></h3>
  <?php if ($p['authors']): ?><p class="pub-authors"><?= e($p['authors']) ?></p><?php endif; ?>
  <?php if ($p['venue']): ?><p class="pub-venue"><em><?= e($p['venue']) ?></em></p><?php endif; ?>
  <?php if (!empty($full) && $p['abstract']): ?><details><summary>Abstract</summary><p><?= nl2br(e($p['abstract'])) ?></p></details><?php endif; ?>
  <p class="pub-links">
    <?php if ($p['doi']): ?><a href="https://doi.org/<?= e($p['doi']) ?>" target="_blank" rel="noopener noreferrer">DOI</a><?php endif; ?>
    <?php if ($p['pdf_file']): ?><a href="<?= e(upload_url($p['pdf_file'])) ?>" target="_blank" rel="noopener">PDF</a><?php endif; ?>
    <?php if (!$p['doi'] && $p['url']): ?><a href="<?= e($p['url']) ?>" target="_blank" rel="noopener noreferrer">Link</a><?php endif; ?>
    <button type="button" class="linklike" data-copy="<?= e($cite) ?>">Copy citation</button>
  </p>
</article>

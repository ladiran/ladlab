<?php
/** One resource card. Expects $t (a toolbox_links row). */
$thumb = Toolbox::thumb($t['url']);
?>
<article class="card tool">
  <?php if ($thumb): ?><a class="tool-thumb" href="<?= e(url('/toolbox/go/' . $t['id'])) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= e($thumb) ?>" alt="" loading="lazy"><span class="play" aria-hidden="true">▶</span></a><?php endif; ?>
  <div class="tool-body">
    <p class="tool-meta"><span class="tool-ico" aria-hidden="true"><?= e(Toolbox::ICONS[$t['kind']] ?? '🔗') ?></span> <?= e(Toolbox::KINDS[$t['kind']] ?? '') ?> · <?= e($t['subject']) ?></p>
    <h3><a href="<?= e(url('/toolbox/go/' . $t['id'])) ?>" target="_blank" rel="noopener noreferrer"><?= e($t['title']) ?> <span aria-hidden="true">↗</span></a></h3>
    <?php if ($t['description'] !== ''): ?><p class="muted"><?= e($t['description']) ?></p><?php endif; ?>
    <p class="tool-tags">
      <span class="tag tag-verified"><?= e(Toolbox::LICENSES[$t['license']] ?? 'Free') ?></span>
      <?php if ($t['audience'] !== 'all'): ?><span class="tag"><?= e(Toolbox::AUDIENCES[$t['audience']]) ?></span><?php endif; ?>
      <span class="muted small"><?= e($t['provider'] !== '' ? $t['provider'] : Toolbox::host($t['url'])) ?></span>
    </p>
    <?php if (trim((string)$t['notes']) !== ''): ?><p class="small"><a href="<?= e(url('/toolbox/' . $t['id'])) ?>">📝 Read the notes: how to install and start</a></p><?php endif; ?>
  </div>
</article>

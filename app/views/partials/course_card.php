<?php
/** @var array $c a row from Learn::catalogue() */
$soon = $c['status'] === 'soon';
$tag = $soon ? 'div' : 'a';
$hours = (int)$c['minutes'] > 0 ? round($c['minutes'] / 60, 1) : 0;
?>
<<?= $tag ?> class="card course-card <?= $soon ? 'is-soon' : '' ?>" <?= $soon ? '' : 'href="' . e(url('/learn/' . $c['slug'])) . '"' ?>>
  <div class="course-cover" <?= $c['cover_image'] ? '' : 'aria-hidden="true"' ?>>
    <?php if ($c['cover_image']): ?><img src="<?= e(upload_url($c['cover_image'])) ?>" alt="" loading="lazy">
    <?php else: ?><span class="cover-sym"><?= ['mechanics' => '⚙', 'electricity' => '⚡', 'thermodynamics' => '🔥', 'waves' => '〰', 'modern' => '⚛'][explode('-', preg_replace('/^university-physics-i-/', '', $c['slug']))[0]] ?? '∫' ?></span><?php endif; ?>
    <?php if ($soon): ?><span class="tag tag-new cover-badge">Coming soon</span>
    <?php elseif ($c['is_featured']): ?><span class="tag tag-verified cover-badge">Featured</span><?php endif; ?>
    <?php if (!$soon && !Auth::isAdmin() && ($lk = Learn::lock($c))): ?><span class="tag tag-new cover-badge">🔒 Back <?= e(Learn::lockText($lk['until'])) ?></span><?php endif; ?>
  </div>
  <div class="course-body">
    <p class="course-level"><?= e($c['level']) ?> · <?= e($c['subject']) ?></p>
    <h3><?= e($c['title']) ?><?php if ($c['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></h3>
    <p class="muted"><?= e($c['subtitle']) ?></p>
    <?php if (!$soon): ?>
      <?php if ((int)($c['rating_count'] ?? 0) > 0): ?><p class="rating-line"><span class="stars" aria-hidden="true"><?= e(Community::stars((float)$c['rating_avg'])) ?></span> <strong><?= e(number_format((float)$c['rating_avg'], 1)) ?></strong> <span class="muted small">(<?= (int)$c['rating_count'] ?>)</span></p><?php endif; ?>
    <p class="course-meta"><?= (int)$c['lesson_count'] ?> lessons<?= $hours ? ' · ' . e((string)$hours) . ' h of video' : '' ?> · certificate</p>
      <p class="price"><strong><?= (int)$c['price'] === 0 ? 'Free' : e(Shop::naira($c['price'])) ?></strong><?php if ($c['compare_price'] && $c['compare_price'] > $c['price']): ?> <s><?= e(Shop::naira($c['compare_price'])) ?></s><?php endif; ?></p>
    <?php else: ?><p class="small muted">Leave your email below to hear when it opens.</p><?php endif; ?>
  </div>
</<?= $tag ?>>

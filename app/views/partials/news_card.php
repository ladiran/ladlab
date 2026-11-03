<?php
/** @var array $n a row from NewsRepo::list() */
form_started('newsreport'); // anti-spam timer for the "Report a problem" form
$tier = (int)$n['tier'];
$count = count($n['reported_by']);
$kindLabel = $n['kind'] === 'press_release' ? 'Press release' : null;
?>
<article class="news-card <?= !empty($lead) ? 'news-lead' : '' ?>" id="news-<?= (int)$n['id'] ?>">
  <?php if ($n['show_images'] && $n['image_url']): ?>
    <a class="news-img" href="<?= e($n['url']) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= e($n['image_url']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"></a>
  <?php endif; ?>
  <div class="news-body">
    <p class="news-badges">
      <?php if ($n['is_breaking']): ?><span class="tag tag-breaking">BREAKING</span><?php endif; ?>
      <span class="tag tier tier-<?= $tier ?>" title="<?= e(NewsEngine::TIER_LABELS[$tier] ?? '') ?>"><?= e(NewsEngine::TIER_LABELS[$tier] ?? '') ?></span>
      <?php if ($tier === 1): ?><span class="tag tag-verified">✔ Verified source</span><?php endif; ?>
      <?php if ($kindLabel && $tier !== 3): ?><span class="tag"><?= e($kindLabel) ?></span><?php endif; ?>
      <?php if ($n['source_status'] === 'probation'): ?><span class="tag tag-new">New source</span><?php endif; ?>
      <?php if ($n['was_updated']): ?><span class="tag tag-updated">Updated</span><?php endif; ?>
      <span class="tag"><?= e(NewsEngine::TOPICS[$n['topic']] ?? 'More science') ?></span>
      <?php if ($n['region'] === 'nigeria'): ?><span class="tag">🇳🇬 Nigeria</span><?php endif; ?>
    </p>
    <h3><a href="<?= e($n['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($n['title']) ?> <span class="ext" aria-hidden="true">↗</span></a></h3>
    <?php if ($n['summary']): ?><p class="news-summary"><?= e($n['summary']) ?></p><?php endif; ?>
    <p class="news-meta">
      <strong><?= e($n['source_name']) ?></strong> · <time datetime="<?= e(date('c', strtotime($n['published_at']))) ?>" title="<?= e(fmt_date($n['published_at'], 'j M Y, H:i')) ?>"><?= e(time_ago($n['published_at'])) ?></time>
      <?php if ($count > 1): ?> · <span title="<?= e(implode(', ', $n['reported_by'])) ?>">Reported by <?= $count ?> sources</span><?php endif; ?>
    </p>
    <p class="news-actions">
      <a href="<?= e($n['url']) ?>" target="_blank" rel="noopener noreferrer">Read original ↗</a>
      <details class="report">
        <summary>Report a problem</summary>
        <form method="post" action="<?= e(url('/news/report')) ?>">
          <?= csrf_field() ?><?= honeypot() ?>
          <input type="hidden" name="item_id" value="<?= (int)$n['id'] ?>">
          <input type="hidden" name="return" value="<?= e(request_path()) ?>">
          <label class="sr-only" for="rp-<?= (int)$n['id'] ?>">What is wrong with this story?</label>
          <textarea id="rp-<?= (int)$n['id'] ?>" name="message" rows="2" maxlength="500" placeholder="What is wrong? (optional)"></textarea>
          <button class="btn btn-sm btn-navy" type="submit">Send report</button>
        </form>
      </details>
    </p>
  </div>
</article>

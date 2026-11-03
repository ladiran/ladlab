<?php /** @var array $post */ ?>
<a class="card post-card" href="<?= e(url('/blog/' . $post['slug'])) ?>">
  <?php if ($post['cover_image']): ?><img src="<?= e(upload_url($post['cover_image'])) ?>" alt="" loading="lazy"><?php else: ?><div class="post-cover-ph" aria-hidden="true">☼</div><?php endif; ?>
  <div class="post-card-body">
    <p class="post-date"><?= e(fmt_date($post['published_at'])) ?><?php $au = Content::author($post); if ($au['name'] !== ''): ?> · By <?= e($au['name']) ?><?php endif; ?><?php if ($post['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></p>
    <h3><?= e($post['title']) ?></h3>
    <p><?= e($post['excerpt'] ?: excerpt($post['body'], 130)) ?></p>
  </div>
</a>

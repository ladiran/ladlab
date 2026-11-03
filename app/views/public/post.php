<article>
  <section class="page-hero">
    <div class="wrap narrow">
      <p class="post-date"><a href="<?= e(url('/blog')) ?>">← Blog</a> · <?= e(fmt_date($post['published_at'])) ?><?php $au = Content::author($post); if ($au['name'] !== ''): ?> · By <strong><?= e($au['name']) ?></strong><?php endif; ?><?php if ($post['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></p>
      <h1><?= e($post['title']) ?></h1>
      <?php if ($post['excerpt']): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
    </div>
  </section>
  <section class="section">
    <div class="wrap narrow">
      <?php if ($post['cover_image']): ?><img class="post-cover" src="<?= e(upload_url($post['cover_image'])) ?>" alt=""><?php endif; ?>
      <div class="prose"><?= !empty($post['is_user_article']) ? md_ugc($post['body']) : md($post['body']) ?></div>
      <?php if ($post['tags']): ?>
        <p class="tag-list"><?php foreach (explode(',', $post['tags']) as $t): $t = trim($t); if ($t === '') continue; ?><a class="tag" href="<?= e(url('/blog?tag=' . rawurlencode($t))) ?>"><?= e($t) ?></a> <?php endforeach; ?></p>
      <?php endif; ?>
      <?php if ($au['name'] !== ''): ?>
      <aside class="author-box card" aria-label="About the author">
        <div class="author-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($au['name'], 0, 1))) ?></div>
        <div>
          <p class="author-label"><?= $au['user'] ? 'Guest article by' : 'Written by' ?></p>
          <p class="author-name"><strong><?= e($au['name']) ?></strong></p>
          <?php if ($au['email'] !== ''): ?><p class="author-contact">✉ <a href="mailto:<?= e($au['email']) ?>?subject=<?= e(rawurlencode('About your article: ' . $post['title'])) ?>"><?= e($au['email']) ?></a></p><?php endif; ?>
          <p class="small muted"><?= $au['user'] ? 'Guest articles express the views of their authors.' : '' ?><?= !$au['user'] ? '<a href="' . e(url('/about')) . '">More about the author →</a>' : '' ?></p>
        </div>
      </aside>
      <?php endif; ?>
      <?php $shareUrl = rawurlencode(abs_url('blog/' . $post['slug'])); $shareText = rawurlencode($post['title']); ?>
      <p class="share">Share:
        <a href="https://wa.me/?text=<?= $shareText ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a> ·
        <a href="https://twitter.com/intent/tweet?text=<?= $shareText ?>&amp;url=<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer">X</a> ·
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
      </p>
    </div>
  </section>
</article>
<section class="band"><div class="wrap band-inner"><div><h2>Enjoyed this?</h2><p>Get new articles in your inbox.</p></div><?php partial('newsletter_form'); ?></div></section>

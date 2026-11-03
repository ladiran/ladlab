<section class="page-hero"><div class="wrap"><h1>Blog</h1><?php if ($tag): ?><p class="lead">Articles tagged “<?= e($tag) ?>” · <a href="<?= e(url('/blog')) ?>">show all</a></p><?php endif; ?></div></section>
<section class="section">
  <div class="wrap">
    <?php if ($tags): ?>
      <p class="tag-list"><?php foreach (array_slice($tags, 0, 12, true) as $t => $n): ?><a class="tag <?= $tag === $t ? 'tag-on' : '' ?>" href="<?= e(url('/blog?tag=' . rawurlencode($t))) ?>"><?= e($t) ?> (<?= (int)$n ?>)</a> <?php endforeach; ?></p>
    <?php endif; ?>
    <?php if ($posts): ?>
      <div class="grid g3">
        <?php foreach ($posts as $post): partial('post_card', ['post' => $post]); endforeach; ?>
      </div>
      <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Pages">
          <?php if ($page > 1): ?><a href="<?= e(url('/blog?page=' . ($page - 1) . ($tag ? '&tag=' . rawurlencode($tag) : ''))) ?>">← Newer</a><?php endif; ?>
          <span>Page <?= (int)$page ?> of <?= (int)$pages ?></span>
          <?php if ($page < $pages): ?><a href="<?= e(url('/blog?page=' . ($page + 1) . ($tag ? '&tag=' . rawurlencode($tag) : ''))) ?>">Older →</a><?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="empty">No articles yet. Check back soon.</p>
    <?php endif; ?>
  </div>
</section>
<section class="band"><div class="wrap band-inner"><div><h2>New articles by email</h2><p>Join the weekly digest.</p></div><?php partial('newsletter_form'); ?></div></section>

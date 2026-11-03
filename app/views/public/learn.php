<section class="page-hero">
  <div class="wrap">
    <h1>Learn physics</h1>
    <p class="lead">University-level courses taught clearly: notes with worked equations, video, quizzes and a certificate when you finish.</p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <form method="get" action="<?= e(url('/learn')) ?>" class="filters">
      <label class="sr-only" for="lq">Search courses</label>
      <input type="search" id="lq" name="q" value="<?= e($filters['q']) ?>" placeholder="Search courses…" maxlength="60">
      <label class="check"><input type="checkbox" name="price" value="free" <?= $filters['price'] === 'free' ? 'checked' : '' ?>> Free courses only</label>
      <button class="btn btn-navy" type="submit">Search</button>
      <?php if ($filters['q'] || $filters['price']): ?><a class="btn btn-ghost-dark" href="<?= e(url('/learn')) ?>">Clear</a><?php endif; ?>
    </form>
    <?php if ($courses): ?>
      <div class="grid g3 courses">
        <?php foreach ($courses as $c): partial('course_card', ['c' => $c]); endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">No courses match your search.</p>
    <?php endif; ?>
    <div class="trust-row">
      <span>📐 Equations rendered clearly on every device</span>
      <span>📝 Quizzes after every module</span>
      <span>🏅 Verifiable certificate</span>
      <span>🏦 Pay by bank transfer</span>
    </div>
  </div>
</section>
<section class="band"><div class="wrap band-inner"><div><h2>Be first to know when a course opens</h2><p>New courses are announced in the weekly science digest.</p></div><?php partial('newsletter_form'); ?></div></section>

<?php
$portrait = setting('portrait') ? upload_url(setting('portrait')) : asset('img/portrait-placeholder.svg');
$heroBg = setting('hero_image') ? ' style="background-image:linear-gradient(120deg,rgba(11,31,58,.88),rgba(30,111,217,.72)),url(' . e(upload_url(setting('hero_image'))) . ')"' : '';
$pillars = [
    ['🎓', 'Learn', 'University-level physics, taught clearly: video, notes, worked examples and quizzes.', '/learn', 'learn'],
    ['👕', 'Shop', 'Original physics and atmosphere T-shirts. Wear the science.', '/shop', 'shop'],
    ['📰', 'News', 'Trusted science news from Nigeria and the world, with breaking stories.', '/news', 'news'],
    ['🔬', 'Research', 'Publications, projects, talks and collaboration.', '/research', null],
];
?>
<section class="hero"<?= $heroBg ?>>
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <p class="eyebrow"><?= e(setting('hero_eyebrow')) ?></p>
      <h1><?= e(setting('hero_title')) ?></h1>
      <p class="lead"><?= e(setting('hero_text')) ?></p>
      <div class="hero-actions">
        <?php if (module_enabled('learn')): ?>
          <a class="btn btn-gold" href="<?= e(url('/learn')) ?>">Explore courses</a>
        <?php else: ?>
          <a class="btn btn-gold" href="#newsletter">Get notified when courses open</a>
        <?php endif; ?>
        <a class="btn btn-outline" href="<?= e(url('/about')) ?>">About me</a>
      </div>
      <?php if ($stats): ?>
        <dl class="stats">
          <?php foreach ($stats as $s): ?><div><dt><?= e($s['value']) ?></dt><dd><?= e($s['label']) ?></dd></div><?php endforeach; ?>
        </dl>
      <?php endif; ?>
    </div>
    <div class="hero-photo">
      <img src="<?= e($portrait) ?>" alt="<?= e(setting('owner_name')) ?>" width="360" height="440">
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <h2 class="section-title">What you will find here</h2>
    <div class="grid g4">
      <?php foreach ($pillars as [$icon, $name, $text, $href, $mod]): ?>
        <a class="card pillar" href="<?= e(url($href)) ?>">
          <span class="pillar-icon"><?= $icon ?></span>
          <h3><?= e($name) ?> <?php if ($mod && !module_enabled($mod)): ?><small class="soon-tag">coming soon</small><?php endif; ?></h3>
          <p><?= e($text) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!empty($news)): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head"><h2 class="section-title">Latest science news</h2><a href="<?= e(url('/news')) ?>">All news →</a></div>
    <div class="news-list">
      <?php foreach ($news as $idx => $n): partial('news_card', ['n' => $n, 'lead' => $idx === 0]); endforeach; ?>
    </div>
    <p class="small muted news-disclaimer">Headlines link to the original publishers. <a href="<?= e(url('/news/sources')) ?>">How we choose and check sources</a> · <a href="<?= e(url('/news/nobel')) ?>">🏅 Nobel Prize centre</a></p>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($shop)): ?>
<section class="section section-alt">
  <div class="wrap">
    <div class="section-head"><h2 class="section-title">Science you can wear</h2><a href="<?= e(url('/shop')) ?>">Visit the shop →</a></div>
    <div class="grid g3 products">
      <?php foreach ($shop as $p): partial('product_card', ['p' => $p]); endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="section section-alt">
  <div class="wrap">
    <div class="section-head"><h2 class="section-title">Featured research</h2><a href="<?= e(url('/research')) ?>">All publications →</a></div>
    <div class="stack">
      <?php foreach ($featured as $p): partial('publication', ['p' => $p]); endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head"><h2 class="section-title">From the blog</h2><a href="<?= e(url('/blog')) ?>">All articles →</a></div>
    <div class="grid g3">
      <?php foreach ($posts as $post): partial('post_card', ['post' => $post]); endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section-alt">
  <div class="wrap about-snippet">
    <div>
      <h2 class="section-title">Meet <?= e(setting('owner_name')) ?></h2>
      <p class="muted"><?= e(setting('owner_title')) ?> · <?= e(setting('owner_institution')) ?></p>
      <p><?= nl2br(e(setting('bio_short'))) ?></p>
      <p>
        <a class="btn btn-navy" href="<?= e(url('/about')) ?>">Read my story</a>
        <?php if ($cv): ?><a class="btn btn-outline-dark" href="<?= e(upload_url($cv['path'])) ?>" target="_blank" rel="noopener">Download CV</a><?php endif; ?>
      </p>
    </div>
    <?php if ($testimonials): ?>
    <div class="testimonials">
      <?php foreach ($testimonials as $t): ?>
        <figure class="quote">
          <blockquote>“<?= e($t['quote']) ?>”</blockquote>
          <figcaption><strong><?= e($t['name']) ?></strong><?= $t['role'] ? ', ' . e($t['role']) : '' ?><?php if ($t['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <h2 class="section-title">Work with me</h2>
    <p class="muted"><?= e(setting('work_intro')) ?> <a href="<?= e(url('/services')) ?>">All services</a> · <a href="<?= e(url('/speaking')) ?>">Speaking and media kit</a></p>
    <div class="grid g4">
      <?php foreach ($services as $s): ?>
        <a class="card work" href="<?= e(url('/services#s' . $s['id'])) ?>"><h3><?= $s['icon'] !== '' ? e($s['icon']) . ' ' : '' ?><?= e($s['title']) ?></h3><p><?= e($s['summary']) ?></p><span class="more">Read more →</span></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="band" id="newsletter">
  <div class="wrap band-inner">
    <div><h2>Get the weekly science digest</h2><p>A short email with one idea explained clearly, new articles, and news of course and shop launches.</p></div>
    <?php partial('newsletter_form'); ?>
  </div>
</section>

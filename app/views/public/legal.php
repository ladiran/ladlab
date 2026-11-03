<section class="page-hero"><div class="wrap"><h1><?= e($page['title']) ?></h1></div></section>
<section class="section"><div class="wrap narrow prose"><?= md($page['body']) ?><p class="muted small">Last updated <?= e(fmt_date($page['updated_at'])) ?></p></div></section>

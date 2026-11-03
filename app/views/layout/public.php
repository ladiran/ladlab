<?php
/** @var string $content */
$site = setting('site_name');
$pageTitle = $title !== '' ? $title . ' | ' . $site : $site . ': ' . setting('tagline');
$desc = $meta_desc !== '' ? $meta_desc : setting('meta_description');
$ogImg = $og_image ?: (setting('og_image') ? abs_url(setting('og_image')) : '');
$favicon = setting('favicon') ? upload_url(setting('favicon')) : asset('img/favicon.svg');
$path = request_path();
$nav = [
    ['Learn', '/learn', 'learn'],
    ['Shop', '/shop', 'shop'],
    ['News', '/news', 'news'],
    ['Research', '/research', null],
    ['Free tools', '/toolbox', null],
    ['Blog', '/blog', null],
    ['About', '/about', null],
];
$isActive = fn(string $href) => $path === $href || ($href !== '/' && str_starts_with($path, $href . '/'));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="robots" content="<?= e(Seo::robotsContent((bool)$noindex)) ?>">
<link rel="canonical" href="<?= e(Seo::canonical()) ?>">
<link rel="icon" href="<?= e($favicon) ?>">
<link rel="alternate" type="application/rss+xml" title="<?= e($site) ?>: blog" href="<?= e(abs_url('blog/feed.xml')) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:type" content="<?= e($og_type) ?>">
<meta property="og:url" content="<?= e(Seo::canonical()) ?>">
<meta property="og:locale" content="en_NG">
<meta property="og:site_name" content="<?= e($site) ?>">
<?php if ($ogImg): ?><meta property="og:image" content="<?= e($ogImg) ?>">
<?php endif; ?><meta name="twitter:card" content="<?= $ogImg ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<?php if ($ogImg): ?><meta name="twitter:image" content="<?= e($ogImg) ?>">
<?php endif; ?><?php foreach (['google-site-verification' => 'seo_google_verification', 'msvalidate.01' => 'seo_bing_verification', 'yandex-verification' => 'seo_yandex_verification'] as $mn => $sk): $vv = setting($sk); if ($vv !== ''): ?><meta name="<?= e($mn) ?>" content="<?= e($vv) ?>">
<?php endif; endforeach; ?><?php
$graph = (array)$jsonld;
if ($body_class === 'home') {
    $graph = array_merge(Seo::site(), $graph);
} elseif (!$noindex) {
    $graph[] = Seo::breadcrumbs((string)$title);
}
echo Seo::jsonLdTags($graph);
?>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= (int)@filemtime(ROOT . '/public/assets/css/app.css') ?>">
</head>
<?php $newsDue = false; try { $newsDue = NewsEngine::due(); } catch (Throwable) { } ?>
<body class="<?= e($body_class) ?>"<?= $newsDue ? ' data-news-tick="' . e(url('/news/tick')) . '"' : '' ?>>
<a class="skip" href="#main">Skip to content</a>

<?php
$bar = ['label' => '', 'breaking' => false, 'items' => []];
try {
    if (NewsRepo::enabled()) {
        $bar = NewsRepo::bar();
    }
} catch (Throwable) {
    // news tables not installed yet
}
$chars = 0;
foreach ($bar['items'] as $t) {
    $chars += mb_strlen($t['title']) + mb_strlen($t['source_name']) + 14;
}
?>
<?php if ($bar['items']): ?>
<div class="ticker <?= $bar['breaking'] ? '' : 'ticker-latest' ?>" role="region" aria-label="<?= $bar['breaking'] ? 'Breaking science news' : 'Latest science news' ?>" data-ticker>
  <div class="wrap ticker-inner">
    <a class="ticker-label" href="<?= e(url($bar['breaking'] ? '/news?view=breaking' : '/news')) ?>"><?= e($bar['label']) ?></a>
    <div class="ticker-viewport">
      <div class="ticker-track" style="--ticker-speed: <?= max(25, (int)round($chars / 11)) ?>s">
        <?php foreach ([false, true] as $dup): ?>
        <ul class="ticker-set"<?= $dup ? ' aria-hidden="true"' : '' ?>>
          <?php foreach ($bar['items'] as $t): ?>
            <li><a href="<?= e($t['url']) ?>" target="_blank" rel="noopener noreferrer"<?= $dup ? ' tabindex="-1"' : '' ?>><?= e($t['title']) ?></a> <small>· <?= e($t['source_name']) ?>, <?= e(time_ago($t['published_at'])) ?></small></li>
          <?php endforeach; ?>
        </ul>
        <?php endforeach; ?>
      </div>
    </div>
    <button type="button" class="ticker-toggle" data-ticker-toggle aria-pressed="false" aria-label="Pause scrolling" title="Pause / play">⏸</button>
  </div>
</div>
<?php endif; ?>

<?php
$me = Auth::check() ? Auth::user() : null;
$cartCount = module_enabled('shop') ? Cart::count() : 0;
?>
<div class="utility">
  <div class="wrap utility-inner">
    <nav class="utility-links" aria-label="Account and cart">
      <?php if (module_enabled('shop')): ?><a href="<?= e(url('/cart')) ?>">🛒 Cart<?php if ($cartCount): ?> <span class="count"><?= (int)$cartCount ?></span><?php endif; ?></a><?php endif; ?>
      <?php if ($me): ?>
        <a href="<?= e(url('/account')) ?>">👤 <?= e(explode(' ', trim($me['name']))[0]) ?></a>
        <?php if ($me['role'] === 'admin'): ?><a href="<?= e(url('/admin')) ?>">Admin</a><?php endif; ?>
        <form method="post" action="<?= e(url('/logout')) ?>" class="inline"><?= csrf_field() ?><button type="submit" class="utility-btn">Logout</button></form>
      <?php else: ?>
        <a href="<?= e(url('/login')) ?>">Login</a>
        <a href="<?= e(url('/register')) ?>" class="utility-register">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</div>

<header class="site-header">
  <div class="wrap bar">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($site) ?> home">
      <?php if (setting('logo')): ?>
        <img src="<?= e(upload_url(setting('logo'))) ?>" alt="<?= e($site) ?>" class="brand-logo">
      <?php else: ?>
        <svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="22" r="9" fill="#f5a623"/><path d="M3 29h34M8 34h24" stroke="#7ec0ff" stroke-width="2.4" stroke-linecap="round"/><path d="M20 4v5M7 11l3.5 3.5M33 11l-3.5 3.5" stroke="#f5a623" stroke-width="2.4" stroke-linecap="round"/></svg>
        <span class="brand-text"><?= e($site) ?></span>
      <?php endif; ?>
    </a>
    <button class="nav-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="Menu"><span></span><span></span><span></span></button>
    <nav id="main-nav" class="main-nav" aria-label="Main">
      <?php foreach ($nav as [$label, $href, $module]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $isActive($href) ? 'active' : '' ?>"><?= e($label) ?><?php if ($module && !module_enabled($module)): ?> <small class="soon-tag">soon</small><?php endif; ?></a>
      <?php endforeach; ?>
      <a href="<?= e(url('/contact')) ?>" class="btn btn-gold btn-sm nav-cta">Contact</a>
    </nav>
  </div>
</header>

<?php $flashes = take_flashes(); if ($flashes): ?>
<div class="wrap flashes" role="status">
  <?php foreach ($flashes as $f): ?><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<main id="main"><?= $content ?></main>

<?php form_started('newsletter'); ?>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <strong class="footer-brand"><?= e($site) ?></strong>
      <p><?= e(setting('footer_text')) ?></p>
      <p class="muted-light"><?= e(setting('owner_name')) ?><br><?= e(setting('owner_institution')) ?></p>
      <?php $socials = Content::socials(); if ($socials): ?>
        <p class="socials"><?php foreach ($socials as $label => $href): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a> <?php endforeach; ?></p>
      <?php endif; ?>
    </div>
    <div>
      <strong>Explore</strong>
      <ul class="footer-links">
        <li><a href="<?= e(url('/learn')) ?>">Learn</a></li>
        <li><a href="<?= e(url('/shop')) ?>">Shop</a></li>
        <li><a href="<?= e(url('/news')) ?>">News</a></li>
        <li><a href="<?= e(url('/research')) ?>">Research</a></li>
        <li><a href="<?= e(url('/blog')) ?>">Blog</a></li>
        <li><a href="<?= e(url('/about')) ?>">About</a></li>
        <li><a href="<?= e(url('/services')) ?>">Services</a></li>
        <li><a href="<?= e(url('/speaking')) ?>">Speaking and media kit</a></li>
        <li><a href="<?= e(url('/events')) ?>">Events</a></li>
        <li><a href="<?= e(url('/press')) ?>">In the media</a></li>
        <li><a href="<?= e(url('/toolbox')) ?>">Free tools for researchers</a></li>
        <li><a href="<?= e(url('/resources')) ?>">Free downloads</a></li>
        <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
        <?php if (module_enabled('shop')): ?><li><a href="<?= e(url('/sales-terms')) ?>">Terms of Sale</a></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <strong>Weekly science digest</strong>
      <?php partial('newsletter_form', ['compact' => true]); ?>
    </div>
  </div>
  <div class="wrap footer-base">
    <span>© <?= date('Y') ?> <?= e($site) ?></span>
    <span><a href="<?= e(url('/privacy')) ?>">Privacy</a> · <a href="<?= e(url('/terms')) ?>">Terms</a> · <a href="<?= e(url('/copyright')) ?>">Copyright &amp; takedown</a></span>
  </div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>?v=<?= (int)@filemtime(ROOT . '/public/assets/js/app.js') ?>" defer></script>
</body>
</html>

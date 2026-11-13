<?php
/** @var string $content */
$user = Auth::user();
$path = request_path();
$newCount = (int)Db::val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
$pendingArticles = 0;
try {
    $pendingArticles = (int)Db::val("SELECT COUNT(*) FROM posts WHERE is_user_article = 1 AND status = 'pending'");
} catch (Throwable) {
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?: 'Admin') ?> | <?= e(Auth::isAdmin() ? 'Admin' : Lecturers::label()) ?></title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= (int)@filemtime(ROOT . '/public/assets/css/app.css') ?>">
</head>
<?php $newsDue = false; try { $newsDue = NewsEngine::due(); } catch (Throwable) { } ?>
<body class="admin"<?= $newsDue ? ' data-news-tick="' . e(url('/news/tick')) . '"' : '' ?>>
<div class="admin-shell">
  <aside class="admin-side" id="admin-side">
    <a class="admin-brand" href="<?= e(url(Auth::isAdmin() ? '/admin' : '/admin/courses')) ?>"><?= e(setting('site_name')) ?> <small><?= e(Auth::isAdmin() ? 'Admin' : Lecturers::label()) ?></small></a>
    <nav>
      <?php foreach (AdminBase::menu() as [$label, $href, $icon]): ?>
        <?php $p = '/' . $href; $active = $p === $path || ($href !== 'admin' && str_starts_with($path, $p . '/')) || ($href === 'admin' && $path === '/admin'); ?>
        <a href="<?= e(url($href)) ?>" class="<?= $active ? 'active' : '' ?>"><span class="ico"><?= $icon ?></span> <?= e($label) ?><?php if ($href === 'admin/enquiries' && $newCount): ?> <span class="count"><?= $newCount ?></span><?php endif; ?><?php if ($href === 'admin/articles' && $pendingArticles): ?> <span class="count"><?= $pendingArticles ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <button class="nav-toggle admin-toggle" aria-expanded="false" aria-controls="admin-side" aria-label="Menu"><span></span><span></span><span></span></button>
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View website ↗</a>
      <span class="spacer"></span>
      <span class="who"><?= e($user['name'] ?? '') ?></span>
      <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit">Sign out</button></form>
    </header>
    <?php $pendingUpdates = []; try { $pendingUpdates = Migrator::pending(); } catch (Throwable) { } ?>
    <?php if ($pendingUpdates && request_path() !== '/admin'): ?><div class="flash flash-error"><strong>A database update is waiting.</strong> New files need it before every feature works. <a href="<?= e(url('/admin')) ?>">Open the dashboard and press “Apply update now”.</a></div><?php endif; ?>
    <?php if (!Auth::hasTwoFactor($user) && !str_starts_with($path, '/admin/security')): ?>
      <?php if (Auth::isAdmin()): ?><div class="flash flash-error sec-nag"><strong>Your administrator account has no two-step verification.</strong> If someone learns your password they control the whole website, its customers and payments. <a href="<?= e(url('/admin/security')) ?>">Turn it on now (2 minutes)</a></div>
      <?php else: ?><div class="flash flash-error sec-nag"><strong>Your account has no two-step verification.</strong> You can see your students' details, so please protect it. <a href="<?= e(url('/account/security')) ?>">Turn it on now (2 minutes)</a></div><?php endif; ?>
    <?php endif; ?>
    <?php $flashes = take_flashes(); foreach ($flashes as $f): ?><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
    <div class="admin-content">
      <h1 class="admin-title"><?= e($title) ?></h1>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>?v=<?= (int)@filemtime(ROOT . '/public/assets/js/app.js') ?>" defer></script>
</body>
</html>

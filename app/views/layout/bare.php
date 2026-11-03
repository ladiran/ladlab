<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> | <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= (int)@filemtime(ROOT . '/public/assets/css/app.css') ?>">
</head>
<body class="bare">
<?php $flashes = take_flashes(); ?>
<main class="bare-main">
  <?php foreach ($flashes as $f): ?><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
  <?= $content ?>
</main>
</body>
</html>

<?php
$open = (int)Db::val("SELECT COUNT(*) FROM news_reports WHERE status = 'open'");
$tabs = [
    'stories' => ['Stories', 'admin/news'],
    'sources' => ['Sources', 'admin/news/sources'],
    'rules' => ['Rules', 'admin/news/rules'],
    'reports' => ['Reader reports', 'admin/news/reports'],
    'log' => ['Activity log', 'admin/news/log'],
];
?>
<nav class="tabs" aria-label="News sections">
  <?php foreach ($tabs as $k => [$label, $href]): ?>
    <a class="<?= ($tab ?? '') === $k ? 'on' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?><?php if ($k === 'reports' && $open): ?> <span class="count"><?= $open ?></span><?php endif; ?></a>
  <?php endforeach; ?>
  <a href="<?= e(url('/news')) ?>" target="_blank" rel="noopener">View public News ↗</a>
</nav>

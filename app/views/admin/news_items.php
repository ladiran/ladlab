<?php partial('news_tabs', ['tab' => $tab]); ?>

<div class="cards-row">
  <div class="stat-card"><strong><?= (int)$stats['published'] ?></strong><span>published stories</span></div>
  <div class="stat-card"><strong><?= (int)$stats['breaking'] ?></strong><span>breaking now (ticker)</span></div>
  <div class="stat-card"><strong><?= (int)$stats['hidden'] ?></strong><span>hidden stories</span></div>
</div>

<section class="card note">
  <p><strong>Last update:</strong> <?= $lastRunAt ? e(time_ago($lastRunAt)) . ' (' . e(fmt_date($lastRunAt, 'j M Y, H:i')) . ')' : 'never' ?><br><span class="muted"><?= e($lastRun ?: 'No update has run yet. Press “Fetch news now”; after that it updates itself (see Automatic updates below).') ?></span></p>
  <div class="actions">
    <form method="post" action="<?= e(url('/admin/news/fetch')) ?>" data-confirm="Fetch all sources now? This can take up to a minute."><?= csrf_field() ?><button class="btn btn-gold" type="submit" <?= $paused ? 'disabled' : '' ?>>⟳ Fetch news now</button></form>
    <form method="post" action="<?= e(url('/admin/news/pause')) ?>" data-confirm="<?= $paused ? 'Turn the news section back on?' : 'Pause ALL news? Fetching stops and the News section and ticker are hidden from visitors.' ?>"><?= csrf_field() ?><button class="btn <?= $paused ? 'btn-gold' : 'btn-danger' ?>" type="submit"><?= $paused ? '▶ News is PAUSED: resume' : '⏸ Pause all news (kill-switch)' ?></button></form>
    <a class="btn btn-ghost" href="<?= e(url('/admin/settings/news')) ?>">Thresholds &amp; keywords</a>
  </div>
</section>

<section class="card note" id="schedule">
  <h2>Automatic updates</h2>
  <p>The news refreshes itself <strong>every <?= (int)$intervalH ?> hour<?= $intervalH === 1 ? '' : 's' ?></strong> (change it under <a href="<?= e(url('/admin/settings/news')) ?>">Thresholds &amp; keywords</a>). <?= $paused ? '<strong>News is paused, so nothing will run.</strong>' : 'Next update: <strong>' . ($nextAt <= time() ? 'due now, at the next visit' : e(date('H:i', $nextAt)) . ' or just after') . '</strong>.' ?></p>
  <p class="muted">Out of the box this happens whenever someone visits the website after the time is up. To make it run exactly on time even when nobody visits, schedule the check once on the computer that hosts the site (it only does work when an update is due, so every 15 minutes is fine):</p>
  <details class="sec-details"><summary>Windows (WAMP): Task Scheduler</summary>
    <p class="small">Open PowerShell as yourself and run (change the PHP folder to match your WAMP version):</p>
    <pre class="code-block">schtasks /Create /SC MINUTE /MO 15 /TN "ClearSkyNews" /TR "\"C:\wamp64\bin\php\php8.3.14\php.exe\" \"<?= e(str_replace('/', '\\', ROOT)) ?>\cron\fetch_news.php\"" /F</pre></details>
  <details class="sec-details"><summary>Linux: crontab</summary>
    <p class="small">Run <code>crontab -e</code> and add this line:</p>
    <pre class="code-block">*/15 * * * * php <?= e(ROOT) ?>/cron/fetch_news.php &gt;&gt; <?= e(ROOT) ?>/storage/logs/news-cron.log 2&gt;&amp;1</pre></details>
</section>

<form method="get" action="<?= e(url('/admin/news')) ?>" class="filters" style="margin:16px 0">
  <select name="status" aria-label="Status"><option value="">Any status</option><option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option><option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Hidden</option></select>
  <select name="source" aria-label="Source"><option value="">All sources</option><?php foreach ($sources as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $source === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select>
  <select name="flag" aria-label="Breaking"><option value="">Breaking or not</option><option value="breaking" <?= $flag === 'breaking' ? 'selected' : '' ?>>Breaking only</option></select>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search headlines…" aria-label="Search">
  <button class="btn btn-navy" type="submit">Filter</button>
</form>

<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Story</th><th>Source</th><th>When</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] === 'hidden' ? 'row-hidden' : '' ?>">
      <td>
        <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener noreferrer"><strong><?= e($r['title']) ?></strong></a><br>
        <?php if ($r['is_breaking']): ?><span class="tag tag-breaking">BREAKING</span> <?php endif; ?>
        <?php if ($r['status'] === 'hidden'): ?><span class="tag tag-hiddenx">Hidden (<?= e($r['hidden_by']) ?>)</span> <small class="muted"><?= e($r['hidden_reason']) ?></small><?php endif; ?>
        <?php if ($r['was_updated']): ?><span class="tag tag-updated">Updated</span> <?php endif; ?>
        <?php if (!$r['is_primary'] && $r['status'] === 'published'): ?><span class="tag">duplicate of another story</span> <?php endif; ?>
        <span class="tag"><?= e(NewsEngine::TOPICS[$r['topic']] ?? $r['topic']) ?></span> <span class="tag"><?= e(NewsEngine::REGIONS[$r['region']] ?? $r['region']) ?></span>
      </td>
      <td><?= e($r['source_name']) ?><br><span class="tag tier tier-<?= (int)$r['tier'] ?>">Tier <?= (int)$r['tier'] ?></span></td>
      <td><?= e(time_ago($r['published_at'])) ?><br><small class="muted"><?= e(fmt_date($r['published_at'], 'j M, H:i')) ?></small></td>
      <td class="row-actions">
        <?php if ($r['status'] === 'published'): ?>
          <form method="post" action="<?= e(url('/admin/news/items/' . $r['id'] . '/hide')) ?>" class="inline" data-confirm="Hide this story from the website?"><?= csrf_field() ?><input type="hidden" name="return" value="admin/news"><button class="btn btn-sm btn-danger" type="submit">Hide</button></form>
        <?php else: ?>
          <form method="post" action="<?= e(url('/admin/news/items/' . $r['id'] . '/restore')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="return" value="admin/news"><button class="btn btn-sm btn-ghost" type="submit">Restore</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Pages">
    <?php $base = '/admin/news?' . http_build_query(array_filter(['status' => $status, 'source' => $source, 'flag' => $flag, 'q' => $q])); ?>
    <?php if ($page > 1): ?><a href="<?= e(url($base . '&page=' . ($page - 1))) ?>">← Newer</a><?php else: ?><span></span><?php endif; ?>
    <span>Page <?= (int)$page ?> of <?= (int)$pages ?> (<?= (int)$total ?> stories)</span>
    <?php if ($page < $pages): ?><a href="<?= e(url($base . '&page=' . ($page + 1))) ?>">Older →</a><?php else: ?><span></span><?php endif; ?>
  </nav>
<?php endif; ?>
<?php else: ?><p class="empty">No stories yet. Press “Fetch news now”.</p><?php endif; ?>

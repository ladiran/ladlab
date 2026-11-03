<?php partial('news_tabs', ['tab' => $tab]); ?>
<p class="muted">Your approved starter list is loaded. Add any other source with its RSS/Atom address; every source goes through the same accuracy rules.</p>
<p class="actions"><a class="btn btn-gold" href="<?= e(url('/admin/news/sources/new')) ?>">＋ Add a news source</a></p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Source</th><th>Tier</th><th>Region</th><th>Status</th><th>Last fetch</th><th>24 h / total</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $s):
      $failing = $s['status'] !== 'paused' && (int)$s['consecutive_failures'] >= 3; ?>
    <tr class="<?= $s['status'] === 'paused' ? 'row-hidden' : '' ?>">
      <td><a href="<?= e(url('/admin/news/sources/' . $s['id'])) ?>"><strong><?= e($s['name']) ?></strong></a><?php if ($s['is_seed']): ?> <span class="badge-sample" title="Part of the approved starter list">starter</span><?php endif; ?><br>
        <small class="muted"><?= e(preg_replace('#^https?://#', '', mb_strimwidth($s['feed_url'], 0, 60, '…'))) ?></small></td>
      <td><span class="tag tier tier-<?= (int)$s['tier'] ?>">Tier <?= (int)$s['tier'] ?></span><?php if ($s['kind'] === 'press_release'): ?><br><small class="muted">press release</small><?php endif; ?></td>
      <td><?= e(NewsEngine::REGIONS[$s['region']] ?? $s['region']) ?></td>
      <td>
        <?php if ($s['status'] === 'paused'): ?><span class="status status-archived">Paused</span>
        <?php elseif ($failing): ?><span class="status status-new" style="background:#fdeeee;color:#8c2222">Failing</span>
        <?php elseif ($s['status'] === 'probation'): ?><span class="status status-new">Probation</span>
        <?php else: ?><span class="status status-replied">Active</span><?php endif; ?>
        <?php if ($s['status'] === 'paused' && $s['paused_reason']): ?><br><small class="muted"><?= e($s['paused_reason']) ?></small>
        <?php elseif ($s['last_error']): ?><br><small class="muted"><?= e($s['last_error']) ?></small><?php endif; ?>
      </td>
      <td><?= $s['last_success_at'] ? e(time_ago($s['last_success_at'])) : '<span class="muted">never</span>' ?></td>
      <td><?= (int)$s['items_24h'] ?> / <?= (int)$s['items_total'] ?></td>
      <td class="row-actions">
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/news/sources/' . $s['id'])) ?>">Edit / Test</a>
        <?php if ($s['status'] === 'paused'): ?>
          <form method="post" action="<?= e(url('/admin/news/sources/' . $s['id'] . '/resume')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-gold" type="submit">Resume</button></form>
        <?php else: ?>
          <form method="post" action="<?= e(url('/admin/news/sources/' . $s['id'] . '/pause')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit">Pause</button></form>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/admin/news/sources/' . $s['id'] . '/delete')) ?>" class="inline" data-confirm="Remove <?= e($s['name']) ?> and ALL of its stories?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Remove</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

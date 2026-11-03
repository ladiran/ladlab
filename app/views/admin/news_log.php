<?php partial('news_tabs', ['tab' => $tab]); ?>
<p class="muted">Every automatic and manual decision the news system makes, with the reason. Kept for 60 days.</p>
<form method="get" action="<?= e(url('/admin/news/log')) ?>" class="filters" style="margin-bottom:12px">
  <select name="event" aria-label="Event"><option value="">All events</option><?php foreach ($events as $ev): ?><option value="<?= e($ev) ?>" <?= $event === $ev ? 'selected' : '' ?>><?= e($ev) ?></option><?php endforeach; ?></select>
  <button class="btn btn-navy" type="submit">Filter</button>
</form>
<div class="table-wrap"><table class="table">
  <thead><tr><th>When</th><th>Event</th><th>Detail</th><th>By</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e(fmt_date($r['created_at'], 'j M, H:i:s')) ?></td><td><span class="tag"><?= e($r['event']) ?></span></td><td><?= e($r['detail']) ?><?= $r['source_name'] ? ' <small class="muted">[' . e($r['source_name']) . ']</small>' : '' ?></td><td><?= e($r['actor']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

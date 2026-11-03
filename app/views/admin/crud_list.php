<?php
if (($def['tab_of'] ?? '') === 'shop') {
    partial('shop_tabs', ['tab' => $def['tab']]);
}
$labels = [];
$checks = [];
foreach ($def['fields'] as [$n, $t, $l, $o]) {
    if ($t === 'select') {
        $labels[$n] = $o['options'] ?? [];
    }
    if ($t === 'checkbox') {
        $checks[$n] = true;
    }
}
?>
<p class="muted"><?= e($def['help'] ?? '') ?></p>
<p class="actions">
  <?php if (($def['can_create'] ?? true) !== false): ?><a class="btn btn-gold" href="<?= e(url('/admin/' . $def['slug'] . '/new')) ?>">＋ Add <?= e(strtolower($def['label'])) ?></a><?php endif; ?>
  <?php if ($def['slug'] === 'toolbox'): ?>
    <a class="btn btn-navy" href="<?= e(url('/admin/toolbox/bulk')) ?>">＋ Add many at once</a>
    <form method="post" action="<?= e(url('/admin/toolbox/check')) ?>" class="inline" data-confirm="Check up to 40 links now? This can take a minute."><?= csrf_field() ?><button class="btn btn-ghost" type="submit">✔ Check links</button></form>
    <a class="btn btn-ghost" href="<?= e(url('/toolbox')) ?>" target="_blank">View the public page ↗</a>
  <?php endif; ?>
  <?php if ($def['slug'] === 'publications'): ?><a class="btn btn-navy" href="<?= e(url('/admin/publications/import')) ?>">⬆ Import (DOI, ORCID, BibTeX, CSV)</a><?php endif; ?>
</p>
<?php if ($rows): ?>
<div class="table-wrap">
<table class="table">
  <thead><tr><?php foreach ($def['list'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><?php if (empty($def['no_visible'])): ?><th>Shown</th><?php endif; ?><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <?php $first = true; foreach ($def['list'] as $col => $label): $v = $r[$col] ?? ''; ?>
        <td>
          <?php
            if (isset($checks[$col])) { $v = $v ? '✔' : '—'; }
            elseif (isset($labels[$col][$v])) { $v = $labels[$col][$v]; }
            elseif ($col === 'expires_on') { $v = $v ? fmt_date($v, 'j M Y') : 'never'; }
            elseif ($col === 'published_at' || $col === 'updated_at') { $v = fmt_date($v, 'j M Y'); }
            elseif ($col === 'link_status') { $v = ['ok' => '✔ working', 'broken' => '✖ broken (hidden)', 'blocked' => '? check by hand', 'unknown' => 'not checked'][$v] ?? $v; }
            elseif (in_array($col, ['talk_date', 'start_date', 'pub_date'], true)) { $v = $v ? fmt_date($v, 'j M Y') : ''; }
          ?>
          <?php if ($first): ?><a href="<?= e(url('/admin/' . $def['slug'] . '/' . $r['id'])) ?>"><strong><?= e(excerpt((string)$v, 90)) ?></strong></a><?php else: ?><?= e((string)$v) ?><?php endif; ?>
          <?php if ($first && !empty($r['is_sample'])): ?> <span class="badge-sample">sample</span><?php endif; ?>
        </td>
      <?php $first = false; endforeach; ?>
      <?php if (empty($def['no_visible'])): ?><td><?= !empty($r['is_visible']) ? '✔' : '<span class="muted">hidden</span>' ?></td><?php endif; ?>
      <td class="row-actions">
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/' . $def['slug'] . '/' . $r['id'])) ?>">Edit</a>
        <?php if (($def['can_delete'] ?? true) !== false): ?>
        <form method="post" action="<?= e(url('/admin/' . $def['slug'] . '/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this <?= e(strtolower($def['label'])) ?>?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php else: ?>
<p class="empty">Nothing here yet.</p>
<?php endif; ?>

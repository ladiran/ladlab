<?php
$action = $row ? '/admin/' . $def['slug'] . '/' . $row['id'] : '/admin/' . $def['slug'] . '/new';
$old = $_SESSION['old'] ?? null; // values typed before a validation error (or pre-filled by an import)
?>
<?php if (($def['tab_of'] ?? '') === 'shop') { partial('shop_tabs', ['tab' => $def['tab']]); } ?>
<p><a href="<?= e(url('/admin/' . $def['slug'])) ?>">← Back to <?= e(strtolower($def['plural'])) ?></a></p>
<?php if ($row && !empty($row['is_sample'])): ?><p class="flash flash-info">This is sample content. Saving it makes it your own, and removes the other samples of this kind from the website.</p><?php endif; ?>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>">
  <?= csrf_field() ?>
  <?php foreach ($def['fields'] as [$name, $type, $label, $o]):
      $o = $o ?? [];
      $fallback = $row[$name] ?? ($o['default'] ?? '');
      if ($type === 'image' || $type === 'pdf') {
          $value = $row[$name] ?? '';
      } elseif ($old !== null) {
          $value = $type === 'checkbox' ? (isset($old[$name]) && $old[$name] !== '0' ? '1' : '0') : ($old[$name] ?? $fallback);
      } else {
          $value = $fallback;
      }
      partial('field', ['name' => $name, 'type' => $type, 'label' => $label, 'o' => $o, 'value' => $value]);
  endforeach; ?>
  <div class="form-actions">
    <button class="btn btn-gold" type="submit">Save</button>
    <a class="btn btn-ghost-dark" href="<?= e(url('/admin/' . $def['slug'])) ?>">Cancel</a>
  </div>
</form>
<?php clear_old(); ?>

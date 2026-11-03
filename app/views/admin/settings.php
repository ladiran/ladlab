<form method="post" action="<?= e(url('/admin/settings/' . $group)) ?>" enctype="multipart/form-data" class="form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>">
  <?= csrf_field() ?>
  <?php foreach ($def['fields'] as [$key, $type, $label, $default, $help]):
      $value = old($key, null);
      if ($value === null) {
          $value = ($type === 'image') ? Settings::get($key) : Settings::get($key);
      }
      partial('field', ['name' => $key, 'type' => $type, 'label' => $label, 'o' => ['help' => $help], 'value' => $value]);
  endforeach; ?>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Save changes</button></div>
</form>
<?php clear_old(); ?>

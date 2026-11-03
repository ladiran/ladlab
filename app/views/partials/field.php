<?php
/**
 * Renders one admin form field.
 * Expects: $name, $type, $label, $o (options: help, options, rows, required, max, min), $value (current value)
 */
$o = $o ?? [];
$id = 'f_' . $name;
$help = $o['help'] ?? '';
$req = !empty($o['required']);
?>
<div class="field field-<?= e($type) ?>">
<?php if ($type === 'checkbox'): ?>
  <label class="check"><input type="checkbox" name="<?= e($name) ?>" value="1" <?= (string)$value === '1' ? 'checked' : '' ?>> <?= e($label) ?></label>
<?php else: ?>
  <label for="<?= e($id) ?>"><?= e($label) ?><?= $req ? ' <span class="req">*</span>' : '' ?></label>
  <?php switch ($type):
    case 'textarea': ?>
      <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int)($o['rows'] ?? 4) ?>" <?= isset($o['max']) ? 'maxlength="' . (int)$o['max'] . '"' : '' ?>><?= e((string)$value) ?></textarea>
    <?php break;
    case 'markdown': ?>
      <div class="md-editor" data-md-editor>
        <div class="md-toolbar" role="toolbar" aria-label="Formatting">
          <button type="button" data-md="bold" title="Bold"><b>B</b></button>
          <button type="button" data-md="italic" title="Italic"><i>I</i></button>
          <button type="button" data-md="h2" title="Heading">H</button>
          <button type="button" data-md="ul" title="Bullet list">• List</button>
          <button type="button" data-md="ol" title="Numbered list">1. List</button>
          <button type="button" data-md="quote" title="Quote">❝</button>
          <button type="button" data-md="link" title="Link">Link</button>
          <button type="button" data-md="preview" class="md-preview-btn">Preview</button>
        </div>
        <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int)($o['rows'] ?? 12) ?>"><?= e((string)$value) ?></textarea>
        <div class="md-preview prose" hidden></div>
      </div>
    <?php break;
    case 'select': ?>
      <select id="<?= e($id) ?>" name="<?= e($name) ?>">
        <?php foreach (($o['options'] ?? []) as $k => $lab): ?><option value="<?= e((string)$k) ?>" <?= (string)$value === (string)$k ? 'selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?>
      </select>
    <?php break;
    case 'image': ?>
      <?php if ($value): ?><div class="current-file"><img src="<?= e(upload_url((string)$value)) ?>" alt="" class="thumb"><label class="check"><input type="checkbox" name="remove_<?= e($name) ?>" value="1"> Remove this image</label></div><?php endif; ?>
      <input type="file" id="<?= e($id) ?>" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
      <small class="hint"><?= $value ? 'Choose a new file to replace it.' : 'JPG, PNG, WebP or GIF, up to 8 MB.' ?></small>
    <?php break;
    case 'pdf': ?>
      <?php if ($value): ?><div class="current-file">📄 <a href="<?= e(upload_url((string)$value)) ?>" target="_blank" rel="noopener">Current PDF</a> <label class="check"><input type="checkbox" name="remove_<?= e($name) ?>" value="1"> Remove</label></div><?php endif; ?>
      <input type="file" id="<?= e($id) ?>" name="<?= e($name) ?>" accept="application/pdf">
      <small class="hint">PDF, up to 15 MB.</small>
    <?php break;
    case 'datetime': ?>
      <input type="datetime-local" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value ? date('Y-m-d\TH:i', strtotime((string)$value)) : '') ?>">
    <?php break;
    case 'date': ?>
      <input type="date" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>">
    <?php break;
    case 'number': ?>
      <input type="number" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>" <?= isset($o['min']) ? 'min="' . (int)$o['min'] . '"' : '' ?> <?= isset($o['max']) ? 'max="' . (int)$o['max'] . '"' : '' ?>>
    <?php break;
    default: // text, url, email ?>
      <input type="<?= $type === 'url' ? 'url' : ($type === 'email' ? 'email' : 'text') ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>" <?= isset($o['max']) ? 'maxlength="' . (int)$o['max'] . '"' : '' ?> <?= $req ? 'required' : '' ?>>
  <?php endswitch; ?>
<?php endif; ?>
<?php if ($help): ?><small class="hint"><?= e($help) ?></small><?php endif; ?>
</div>

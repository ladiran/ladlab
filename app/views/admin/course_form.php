<?php
partial('course_tabs', ['tab' => $tab]);
$old = $_SESSION['old'] ?? null;
$v = function (string $k, $default = '') use ($c, $old) {
    return $old !== null ? ($old[$k] ?? $default) : ($c[$k] ?? $default);
};
$F = function (string $name, string $type, string $label, string $help = '', array $o = []) use ($v) {
    partial('field', ['name' => $name, 'type' => $type, 'label' => $label, 'o' => ['help' => $help] + $o, 'value' => $v($name, $o['default'] ?? '')]);
};
$action = $c ? '/admin/courses/' . $c['id'] : '/admin/courses/new';
?>
<p><a href="<?= e(url('/admin/courses')) ?>">← All courses</a><?= $c ? ' · <a href="' . e(url('/admin/courses/' . $c['id'] . '/curriculum')) . '">Curriculum →</a>' : '' ?></p>
<?php if ($c && $c['is_sample']): ?><p class="flash flash-info">This is a sample course. Saving it makes it your own and hides the other sample courses.</p><?php endif; ?>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>">
  <?= csrf_field() ?>
  <?php $F('title', 'text', 'Course title', '', ['required' => true, 'max' => 200]); ?>
  <?php $F('subtitle', 'text', 'One-line summary', 'Shown under the title and on the catalogue card.', ['max' => 250]); ?>
  <div class="form-row">
    <?php $F('subject', 'text', 'Subject', '', ['max' => 40, 'default' => 'Physics']); ?>
    <?php $F('level', 'text', 'Level', 'e.g. University Year 1', ['max' => 40, 'default' => 'University Year 1']); ?>
  </div>
  <?php $F('description', 'markdown', 'About this course', 'Supports Markdown and maths such as $F = ma$.', ['rows' => 7]); ?>
  <?php $F('outcomes', 'markdown', 'What students will learn', 'A bullet list works well.', ['rows' => 5]); ?>
  <?php $F('requirements', 'markdown', 'Before you start', '', ['rows' => 3]); ?>
  <div class="field"><label for="f_cover">Cover image (optional)</label>
    <?php if ($c && $c['cover_image']): ?><div class="current-file"><img src="<?= e(upload_url($c['cover_image'])) ?>" alt="" class="thumb"><label class="check"><input type="checkbox" name="remove_cover" value="1"> Remove</label></div><?php endif; ?>
    <input type="file" id="f_cover" name="cover_image" accept="image/jpeg,image/png,image/webp"><small class="hint">A drawn symbol is used when there is no cover.</small></div>
  <div class="form-row">
    <?php $F('price', 'number', 'Price (₦)', '0 makes the course free.', ['min' => 0, 'default' => '0']); ?>
    <?php $F('compare_price', 'number', '“Was” price (₦), optional', 'Shown crossed out.', ['min' => 0]); ?>
  </div>
  <div class="form-row">
    <?php partial('field', ['name' => 'status', 'type' => 'select', 'label' => 'Status', 'o' => ['options' => ['draft' => 'Draft (hidden)', 'published' => 'Published (open for enrolment)', 'soon' => 'Coming soon (teaser only)']], 'value' => $v('status', 'draft')]); ?>
    <?php $F('pass_mark', 'number', 'Default pass mark (%)', '', ['min' => 1, 'max' => 100, 'default' => '60']); ?>
  </div>
  <div class="form-row">
    <?php partial('field', ['name' => 'certificate_enabled', 'type' => 'checkbox', 'label' => 'Issue a certificate when the student finishes', 'o' => [], 'value' => $old !== null ? (isset($old['certificate_enabled']) ? '1' : '0') : (string)($c['certificate_enabled'] ?? '1')]); ?>
    <?php partial('field', ['name' => 'is_featured', 'type' => 'checkbox', 'label' => 'Feature this course', 'o' => [], 'value' => $old !== null ? (isset($old['is_featured']) ? '1' : '0') : (string)($c['is_featured'] ?? '0')]); ?>
  </div>
  <div class="form-row">
    <?php partial('field', ['name' => 'chat_enabled', 'type' => 'checkbox', 'label' => 'Chat room for enrolled students and you', 'o' => [], 'value' => $old !== null ? (isset($old['chat_enabled']) ? '1' : '0') : (string)($c['chat_enabled'] ?? '1')]); ?>
  </div>
  <?php
    $lw = $c ? Learn::lockWindow($c) : null;
    $lockIn = fn(?string $d) => $d ? date('Y-m-d\TH:i', (int)strtotime($d)) : '';
    $lockFrom = $old !== null ? (string)($old['lock_from'] ?? '') : ($lw ? $lockIn($lw['from']) : '');
    $lockUntil = $old !== null ? (string)($old['lock_until'] ?? '') : ($lw ? $lockIn($lw['until']) : '');
  ?>
  <fieldset class="options">
    <legend>Lock this course for a period (optional)</legend>
    <p class="small muted">While the lock is on, students and visitors who open the course see: “This course is currently not available, please check back by …” with the time the lock ends. <strong>You (the administrator) are never locked out.</strong> The lock must end in the future, and it must not start in the past. Times are Nigeria time (WAT). It lifts by itself when the end time arrives.</p>
    <?php if ($lw): ?><p class="flash flash-info small">🔒 <?= $lw['active'] ? 'Locked right now, until ' : 'Scheduled: ' . e(Learn::lockText($lw['from'])) . ' until ' ?><?= e(Learn::lockText($lw['until'])) ?>.</p><?php endif; ?>
    <div class="form-row">
      <div class="field"><label for="f_lfrom">Lock starts (leave empty to lock immediately)</label><input type="datetime-local" id="f_lfrom" name="lock_from" value="<?= e($lockFrom) ?>"></div>
      <div class="field"><label for="f_luntil">Lock ends (course opens again)</label><input type="datetime-local" id="f_luntil" name="lock_until" value="<?= e($lockUntil) ?>" min="<?= e(date('Y-m-d\TH:i')) ?>"></div>
    </div>
    <?php if ($lw): ?><label class="check"><input type="checkbox" name="lock_clear" value="1"> Remove the lock now (open the course to students immediately)</label><?php endif; ?>
    <small class="hint">To remove a lock, tick the box above (or empty both boxes) and save.</small>
  </fieldset>
  <div class="form-row">
    <?php $F('sort_order', 'number', 'Display order', 'Lower first.', ['default' => '0']); ?>
    <?php $F('slug', 'text', 'Web address ending', 'Leave empty to create from the title.', ['max' => 150]); ?>
  </div>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Save course</button><a class="btn btn-ghost-dark" href="<?= e(url('/admin/courses')) ?>">Cancel</a></div>
</form>
<?php clear_old(); ?>

<?php
partial('course_tabs', ['tab' => $tab]);
$old = $_SESSION['old'] ?? null;
$v = function (string $k, $default = '') use ($l, $old) {
    return $old !== null ? ($old[$k] ?? $default) : ($l[$k] ?? $default);
};
$action = $l ? '/admin/lessons/' . $l['id'] : '/admin/courses/' . $c['id'] . '/lessons/new';
$vtype = $v('video_type', 'none');
$F = function (string $name, string $type, string $label, string $help = '', array $o = []) use ($v) {
    partial('field', ['name' => $name, 'type' => $type, 'label' => $label, 'o' => ['help' => $help] + $o, 'value' => $v($name, $o['default'] ?? '')]);
};
$selMod = (int)$v('module_id', $l['module_id'] ?? $preselect);
?>
<p><a href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>">← Curriculum: <?= e($c['title']) ?></a><?= $l ? ' · <a href="' . e(url('/learn/' . $c['slug'] . '/lesson/' . $l['id'])) . '" target="_blank">Preview ↗</a>' : '' ?></p>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>">
  <?= csrf_field() ?>
  <div class="form-row">
    <?php $F('title', 'text', 'Lesson title', '', ['required' => true, 'max' => 200]); ?>
    <div class="field"><label for="f_module">Module</label><select id="f_module" name="module_id" required><?php foreach ($modules as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $selMod === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['title']) ?></option><?php endforeach; ?></select></div>
  </div>
  <?php $F('objectives', 'textarea', 'Learning objective(s)', 'Shown in a box above the lesson: “By the end of this lesson you can…”', ['rows' => 2, 'max' => 2000]); ?>
  <?php $F('content', 'markdown', 'Lesson notes', 'Markdown with maths: write $F = ma$ inline, or $$W = \int \vec F \cdot d\vec r$$ on its own lines. Press Preview to see the rendered equations.', ['rows' => 22]); ?>

  <fieldset class="options">
    <legend>Video</legend>
    <label class="check"><input type="radio" name="video_type" value="none" <?= $vtype === 'none' ? 'checked' : '' ?>> No video</label>
    <label class="check"><input type="radio" name="video_type" value="youtube" <?= $vtype === 'youtube' ? 'checked' : '' ?>> YouTube (use an <em>unlisted</em> video)</label>
    <label class="check"><input type="radio" name="video_type" value="vimeo" <?= $vtype === 'vimeo' ? 'checked' : '' ?>> Vimeo</label>
    <label class="check"><input type="radio" name="video_type" value="file" <?= $vtype === 'file' ? 'checked' : '' ?>> A video file on this site (streamed only to enrolled students)</label>
    <div class="field"><label for="f_vurl">YouTube / Vimeo link</label><input type="text" id="f_vurl" name="video_url" value="<?= e((string)($old['video_url'] ?? ($l && in_array($l['video_type'], ['youtube', 'vimeo'], true) ? ($l['video_type'] === 'youtube' ? 'https://youtu.be/' : 'https://vimeo.com/') . $l['video_ref'] : ''))) ?>" placeholder="https://youtu.be/…"></div>
    <div class="field"><label for="f_vfile">Upload MP4 / WebM</label><input type="file" id="f_vfile" name="video_file" accept="video/mp4,video/webm">
      <small class="hint">Limit on this server: <?= e((string)ini_get('upload_max_filesize')) ?>. Larger? Copy the file into <code>storage/private/videos</code> and type its name below.</small>
      <?php if ($l && $l['video_type'] === 'file'): ?><small class="hint">Current file: <code><?= e(basename($l['video_ref'])) ?></code> (kept unless you upload or name another).</small><?php endif; ?></div>
    <div class="field"><label for="f_vname">…or name of a file already in storage/private/videos</label><input type="text" id="f_vname" name="video_name" value="<?= e((string)($old['video_name'] ?? '')) ?>" placeholder="lesson-1.mp4" maxlength="120"></div>
    <?php $F('video_minutes', 'number', 'Video length (minutes)', '', ['min' => 0, 'max' => 1000, 'default' => '0']); ?>
  </fieldset>

  <h2>Downloads</h2>
  <?php if ($files): ?><ul class="plain-links"><?php foreach ($files as $f): ?><li>📄 <?= e($f['title']) ?> <label class="check small"><input type="checkbox" name="remove_file[]" value="<?= (int)$f['id'] ?>"> remove</label></li><?php endforeach; ?></ul><?php endif; ?>
  <div class="form-row">
    <div class="field"><label for="f_nf">Add PDF(s) (notes, problem sets)</label><input type="file" id="f_nf" name="new_files[]" multiple accept="application/pdf"></div>
    <?php $F('new_file_title', 'text', 'Name for a single new PDF (optional)', '', ['max' => 190]); ?>
  </div>

  <h2>Publishing</h2>
  <div class="form-row">
    <?php partial('field', ['name' => 'is_free_preview', 'type' => 'checkbox', 'label' => 'Free preview (anyone can open this lesson, even without enrolling)', 'o' => [], 'value' => $old !== null ? (isset($old['is_free_preview']) ? '1' : '0') : (string)($l['is_free_preview'] ?? '0')]); ?>
    <?php partial('field', ['name' => 'status', 'type' => 'select', 'label' => 'Status', 'o' => ['options' => ['published' => 'Published', 'draft' => 'Draft (hidden from students)']], 'value' => $v('status', 'published')]); ?>
  </div>
  <?php if ($l): ?><?php $F('sort_order', 'number', 'Order within the module', 'Or use the arrows on the curriculum page.', []); ?><?php endif; ?>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Save lesson</button><a class="btn btn-ghost-dark" href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>">Cancel</a></div>
</form>
<?php if ($l): ?>
<section class="card" style="margin-top:24px" id="bank">
  <h2>Check-up questions for this lesson</h2>
  <p>After a student finishes this lesson, the site asks them <strong>one question drawn at random</strong> from this bank (you can change how many). They must answer before moving to the next lesson. Use <em>multiple choice</em> with options A to D, or a <em>short text answer</em> as a fill-in-the-gap (write the blank as ____ in the question and list the accepted words). The more questions in the bank, the more different the check-ups are for different students. The answer is shown to the student with your explanation; it is only for their information.</p>
  <p><?= $bankCount ? '<strong>' . (int)$bankCount . ' question' . ($bankCount === 1 ? '' : 's') . '</strong> in the bank' . ($bank && $bank['status'] === 'draft' ? ' <span class="tag tag-new">switched off (draft)</span>' : '') : '<span class="muted">No questions yet: students are not asked anything after this lesson.</span>' ?></p>
  <form method="post" action="<?= e(url('/admin/lessons/' . $l['id'] . '/bank')) ?>"><?= csrf_field() ?><button class="btn btn-gold" type="submit"><?= $bank ? 'Manage the question bank' : 'Create the question bank' ?></button></form>
</section>
<?php endif; ?>
<?php clear_old(); ?>

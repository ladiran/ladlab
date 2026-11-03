<?php
partial('course_tabs', ['tab' => $tab]);
$old = $_SESSION['old'] ?? null;
$v = function (string $k, $default = '') use ($qn, $old) {
    return $old !== null ? ($old[$k] ?? $default) : ($qn[$k] ?? $default);
};
$type = $v('type', 'mcq');
$optText = '';
$tfCorrect = 'true';
if ($old !== null) {
    $optText = (string)($old['options'] ?? '');
    $tfCorrect = (string)($old['tf_correct'] ?? 'true');
} elseif ($qn) {
    foreach ($qn['options'] as $o) {
        if (in_array($qn['type'], ['mcq', 'multi'], true)) {
            $optText .= ($o['is_correct'] ? '*' : '') . $o['label'] . "\n";
        } elseif ($qn['type'] === 'truefalse' && $o['is_correct']) {
            $tfCorrect = strtolower($o['label']) === 'false' ? 'false' : 'true';
        }
    }
}
$action = $qn ? '/admin/questions/' . $qn['id'] : '/admin/quizzes/' . $quiz['id'] . '/questions/new';
?>
<p><a href="<?= e(url('/admin/quizzes/' . $quiz['id'])) ?>">← Quiz: <?= e($quiz['title']) ?></a></p>
<form method="post" action="<?= e(url($action)) ?>" class="form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>" data-qform>
  <?= csrf_field() ?>
  <div class="form-row">
    <div class="field"><label for="f_type">Question type</label><select id="f_type" name="type" data-qtype><?php foreach (Learn::Q_TYPES as $k => $lab): ?><option value="<?= e($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="f_pts">Points</label><input type="number" id="f_pts" name="points" min="1" max="100" value="<?= (int)$v('points', 1) ?>"></div>
  </div>
  <?php partial('field', ['name' => 'prompt', 'type' => 'markdown', 'label' => 'Question', 'o' => ['rows' => 4, 'help' => 'Maths works here: $v = v_0 + at$.', 'required' => true], 'value' => $v('prompt')]); ?>

  <div class="qbox" data-for="mcq multi">
    <div class="field"><label for="f_opts">Options (one per line)</label>
      <textarea id="f_opts" name="options" rows="6" placeholder="*the correct answer&#10;a wrong answer&#10;another wrong answer"><?= e($optText) ?></textarea>
      <small class="hint">Start the correct option(s) with <strong>*</strong>. Multiple choice (one answer): exactly one *. Select-all: one or more.</small></div>
  </div>
  <div class="qbox" data-for="truefalse">
    <fieldset class="options"><legend>Correct answer</legend>
      <label class="check"><input type="radio" name="tf_correct" value="true" <?= $tfCorrect === 'true' ? 'checked' : '' ?>> True</label>
      <label class="check"><input type="radio" name="tf_correct" value="false" <?= $tfCorrect === 'false' ? 'checked' : '' ?>> False</label></fieldset>
  </div>
  <div class="qbox" data-for="numeric">
    <div class="form-row">
      <div class="field"><label for="f_na">Correct numeric answer</label><input type="text" id="f_na" name="num_answer" value="<?= e((string)$v('num_answer', '')) ?>" inputmode="decimal"></div>
      <div class="field"><label for="f_nt">Tolerance (± %)</label><input type="number" id="f_nt" name="num_tolerance" step="0.1" min="0" max="100" value="<?= e((string)$v('num_tolerance', '1')) ?>"><small class="hint">1 means answers within 1% are accepted. Use 0 for exact (e.g. counting).</small></div>
      <div class="field"><label for="f_nu">Unit (optional)</label><input type="text" id="f_nu" name="num_unit" value="<?= e((string)$v('num_unit', '')) ?>" maxlength="28" placeholder="m/s^2"><small class="hint">If set and the student types a unit, it must match.</small></div>
    </div>
  </div>
  <div class="qbox" data-for="short">
    <div class="field"><label for="f_acc">Accepted answers (one per line)</label><textarea id="f_acc" name="accepted" rows="4"><?= e((string)$v('accepted', '')) ?></textarea><small class="hint">Capitals, punctuation and extra spaces are ignored when marking.</small></div>
  </div>

  <?php partial('field', ['name' => 'explanation', 'type' => 'markdown', 'label' => 'Explanation (shown after the quiz is submitted)', 'o' => ['rows' => 3], 'value' => $v('explanation')]); ?>
  <div class="form-actions">
    <button class="btn btn-gold" type="submit">Save question</button>
    <?php if (!$qn): ?><button class="btn btn-navy" type="submit" name="again" value="1">Save and add another</button><?php endif; ?>
    <a class="btn btn-ghost-dark" href="<?= e(url('/admin/quizzes/' . $quiz['id'])) ?>">Cancel</a>
  </div>
</form>
<?php clear_old(); ?>

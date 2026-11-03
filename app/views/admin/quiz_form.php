<?php
partial('course_tabs', ['tab' => $tab]);
$old = $_SESSION['old'] ?? null;
$v = function (string $k, $default = '') use ($q, $old) {
    return $old !== null ? ($old[$k] ?? $default) : ($q[$k] ?? $default);
};
$F = function (string $name, string $type, string $label, string $help = '', array $o = []) use ($v) {
    partial('field', ['name' => $name, 'type' => $type, 'label' => $label, 'o' => ['help' => $help] + $o, 'value' => $v($name, $o['default'] ?? '')]);
};
$action = $q ? '/admin/quizzes/' . $q['id'] : '/admin/courses/' . $c['id'] . '/quizzes/new';
$kind = $q ? $q['kind'] : ($final ? 'final' : 'quiz');
$kind = $old['kind'] ?? $kind;
$selMod = (int)($old['module_id'] ?? ($q['module_id'] ?? $preselect));
$qlist = $questions ?? [];
$isCheck = $q && $q['kind'] === 'check';
?>
<?php if ($isCheck): ?>
<p><a href="<?= e(url('/admin/lessons/' . $q['lesson_id'])) ?>">← Lesson: <?= e($lesson['title'] ?? '') ?></a></p>
<h2>Check-up question bank</h2>
<p class="muted">Students are asked the number of questions below, drawn at random from this bank, after they finish the lesson. Add several questions so different students get different ones. Use <strong>Multiple choice</strong> (options A to D) or <strong>Short text answer</strong> for fill-in-the-gap: write the blank as ____ in the question and list every accepted word, one per line.</p>
<form method="post" action="<?= e(url($action)) ?>" class="form admin-form">
  <?= csrf_field() ?>
  <div class="form-row">
    <?php $F('questions_to_draw', 'number', 'Questions asked per student (1 to 10)', '', ['min' => 1, 'max' => 10, 'default' => '1']); ?>
    <?php partial('field', ['name' => 'status', 'type' => 'select', 'label' => 'Status', 'o' => ['options' => ['published' => 'On: students are asked', 'draft' => 'Off: no check-up for this lesson']], 'value' => $v('status', 'published')]); ?>
  </div>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Save</button>
    <?php if ($lesson): ?><a class="btn btn-ghost" href="<?= e(url('/learn/' . $c['slug'] . '/lesson/' . $lesson['id'])) ?>" target="_blank">See the lesson ↗</a><?php endif; ?></div>
</form>
<?php else: ?>
<p><a href="<?= e(url('/admin/courses/' . $c['id'] . '/curriculum')) ?>">← Curriculum: <?= e($c['title']) ?></a></p>
<form method="post" action="<?= e(url($action)) ?>" class="form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>">
  <?= csrf_field() ?>
  <?php $F('title', 'text', 'Quiz title', '', ['required' => true, 'max' => 200]); ?>
  <div class="form-row">
    <?php partial('field', ['name' => 'kind', 'type' => 'select', 'label' => 'Type', 'o' => ['options' => ['quiz' => 'Module quiz', 'final' => 'Final exam (whole course)']], 'value' => $kind]); ?>
    <div class="field"><label for="f_mod">Module (for module quizzes)</label><select id="f_mod" name="module_id"><?php foreach ($modules as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $selMod === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['title']) ?></option><?php endforeach; ?></select></div>
  </div>
  <?php $F('instructions', 'markdown', 'Instructions shown before the quiz', '', ['rows' => 3]); ?>
  <div class="form-row">
    <?php $F('pass_mark', 'number', 'Pass mark (%)', '', ['min' => 1, 'max' => 100, 'default' => (string)$c['pass_mark']]); ?>
    <?php $F('time_limit_minutes', 'number', 'Time limit (minutes, 0 = none)', '', ['min' => 0, 'default' => '0']); ?>
  </div>
  <div class="form-row">
    <?php $F('max_attempts', 'number', 'Maximum attempts (0 = unlimited)', '', ['min' => 0, 'default' => '0']); ?>
    <?php $F('questions_to_draw', 'number', 'Draw this many questions at random (0 = all)', 'Build a bigger question bank and give each student a different selection.', ['min' => 0, 'default' => '0']); ?>
  </div>
  <div class="form-row">
    <?php partial('field', ['name' => 'shuffle', 'type' => 'checkbox', 'label' => 'Shuffle question and option order', 'o' => [], 'value' => $old !== null ? (isset($old['shuffle']) ? '1' : '0') : (string)($q['shuffle'] ?? '1')]); ?>
    <?php partial('field', ['name' => 'status', 'type' => 'select', 'label' => 'Status', 'o' => ['options' => ['published' => 'Published', 'draft' => 'Draft (hidden)']], 'value' => $v('status', 'published')]); ?>
  </div>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Save quiz</button>
    <?php if ($q): ?><a class="btn btn-ghost" href="<?= e(url('/learn/' . $c['slug'] . '/quiz/' . $q['id'])) ?>" target="_blank">Try it as a student ↗</a><?php endif; ?></div>
</form>
<?php endif; ?>

<?php if ($q): ?>
<section class="card" style="margin-top:24px">
  <h2>Questions (<?= count($qlist) ?>)</h2>
  <?php if ($qlist): ?>
  <table class="table"><tbody>
    <?php foreach ($qlist as $n => $qn): ?>
      <tr>
        <td><?= $n + 1 ?>.</td>
        <td><a href="<?= e(url('/admin/questions/' . $qn['id'])) ?>"><?= e(excerpt((string)$qn['prompt'], 110)) ?></a><br><span class="tag"><?= e(Learn::Q_TYPES[$qn['type']]) ?></span> <span class="muted small"><?= (int)$qn['points'] ?> pt<?= $qn['points'] > 1 ? 's' : '' ?></span></td>
        <td class="row-actions">
          <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/questions/' . $qn['id'])) ?>">Edit</a>
          <?php foreach (['up' => '↑', 'down' => '↓'] as $dir => $sym): ?><form method="post" action="<?= e(url('/admin/questions/' . $qn['id'] . '/move')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn btn-sm btn-ghost" type="submit" aria-label="Move <?= $dir ?>"><?= $sym ?></button></form><?php endforeach; ?>
          <form method="post" action="<?= e(url('/admin/questions/' . $qn['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this question?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">✕</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody></table>
  <?php else: ?><p class="muted">No questions yet.</p><?php endif; ?>
  <p><a class="btn btn-gold" href="<?= e(url('/admin/quizzes/' . $q['id'] . '/questions/new')) ?>">＋ Add a question</a></p>
  <form method="post" action="<?= e(url('/admin/quizzes/' . $q['id'] . '/delete')) ?>" data-confirm="Delete this <?= $isCheck ? 'question bank' : 'quiz' ?> and all its questions?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete this <?= $isCheck ? 'question bank' : 'quiz' ?></button></form>
</section>
<?php endif; ?>
<?php clear_old(); ?>

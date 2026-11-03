<?php
$base = '/learn/' . $c['slug'];
$answered = $row && $row['answered_at'] !== null;
?>
<section class="page-hero"><div class="wrap"><p class="post-date"><a href="<?= e(url($base . '/lesson/' . $l['id'])) ?>" style="color:var(--sun)">← <?= e($l['title']) ?></a></p><h1>Check-up question</h1></div></section>
<section class="section">
  <div class="wrap narrow">
    <?php if ($isAdmin): ?>
      <div class="card">
        <h2>Administrator preview</h2>
        <p>Students answer <?= count($questions) ?: 'one or more' ?> question(s) drawn at random from this lesson’s bank (<?= (int)$bankSize ?> question<?= $bankSize === 1 ? '' : 's' ?> in the bank) before they can move on. Administrators skip the check-up.</p>
        <p><a class="btn btn-ghost" href="<?= e(url($base . '/lesson/' . $l['id'])) ?>">Back to the lesson</a> <a class="btn btn-navy" href="<?= e(url('/admin/lessons/' . $l['id'])) ?>">Edit the question bank</a></p>
      </div>
    <?php elseif (!$answered): ?>
      <p class="lead-sm">Answer this question to finish the lesson and move on. It helps you check what you have just learnt; <strong>your answer is not a grade</strong> and is not kept on your record.</p>
      <form method="post" action="<?= e(url($base . '/lesson/' . $l['id'] . '/check')) ?>" class="quiz-form">
        <?= csrf_field() ?>
        <?php foreach ($questions as $i => $qn): ?>
          <fieldset class="card question">
            <legend><span class="qnum"><?= $i + 1 ?></span> <span class="pts"><?= (int)$qn['points'] ?> pt<?= $qn['points'] > 1 ? 's' : '' ?></span></legend>
            <div class="prose q-prompt"><?= md($qn['prompt']) ?></div>
            <?php partial('question_fields', ['qn' => $qn]); ?>
          </fieldset>
        <?php endforeach; ?>
        <div class="form-actions"><button class="btn btn-gold btn-lg" type="submit">Submit my answer</button><a class="btn btn-ghost-dark" href="<?= e(url($base . '/lesson/' . $l['id'])) ?>">Back to the lesson</a></div>
      </form>
    <?php else: ?>
      <?php foreach ($questions as $i => $qn): $r = $res[$qn['id']] ?? null; if (!$r) continue; ?>
        <div class="card question <?= $r['ok'] ? 'q-ok' : 'q-bad' ?>">
          <p><span class="qnum"><?= $i + 1 ?></span> <strong><?= $r['ok'] ? '✔ Correct' : '✘ Not quite' ?></strong> <span class="pts"><?= rtrim(rtrim(number_format((float)$r['earned'], 1), '0'), '.') ?> / <?= (int)$r['max'] ?></span></p>
          <div class="prose"><?= md($qn['prompt']) ?></div>
          <p class="small"><strong>Your answer:</strong>
            <?php
              $g = $r['given'];
              if (in_array($qn['type'], ['mcq', 'truefalse'], true)) { $m = array_values(array_filter($qn['options'], fn($o) => (int)$o['id'] === (int)$g)); echo $m ? e($m[0]['label']) : '<em>no answer</em>'; }
              elseif ($qn['type'] === 'multi') { $m = array_filter($qn['options'], fn($o) => in_array((int)$o['id'], array_map('intval', (array)$g), true)); echo $m ? e(implode('; ', array_column($m, 'label'))) : '<em>no answer</em>'; }
              else { echo ($g === null || $g === '') ? '<em>no answer</em>' : e((string)$g); }
            ?>
          </p>
          <?php if (!$r['ok']): ?><p class="small"><strong>Correct answer:</strong> <?= e((string)$r['expected']) ?></p><?php endif; ?>
          <?php if ($qn['explanation']): ?><div class="explain prose"><strong>Explanation.</strong> <?= md($qn['explanation']) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <p class="form-actions">
        <a class="btn btn-gold btn-lg" href="<?= e(url('/' . $nextUrl)) ?>"><?= $moduleLast ? 'Finish the module and see my score →' : 'Next lesson →' ?></a>
        <a class="btn btn-ghost-dark" href="<?= e(url($base . '/lesson/' . $l['id'])) ?>">Review the lesson</a>
      </p>
    <?php endif; ?>
  </div>
</section>

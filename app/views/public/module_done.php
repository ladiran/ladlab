<?php
$base = '/learn/' . $c['slug'];
$pct = $score['pct'];
?>
<section class="page-hero"><div class="wrap"><p class="post-date"><a href="<?= e(url($base)) ?>" style="color:var(--sun)">← <?= e($c['title']) ?></a></p><h1>Module <?= (int)$num ?> complete</h1><p class="lead"><?= e($m['title']) ?></p></div></section>
<section class="section">
  <div class="wrap narrow">
    <?php if ($isAdmin): ?><p class="flash flash-info small">Administrator preview: students reach this page after finishing every lesson of the module.</p><?php endif; ?>
    <div class="card result-card <?= $pct !== null && $pct >= 60 ? 'res-pass' : 'res-fail' ?>">
      <?php if ($pct !== null): ?>
        <p class="score"><?= (int)$pct ?>%</p>
        <h2>Your score: <?= rtrim(rtrim(number_format($score['earned'], 1), '0'), '.') ?> out of <?= rtrim(rtrim(number_format($score['max'], 1), '0'), '.') ?></h2>
        <p class="muted">Total of your check-up answers in this module. It is only to show you how you are doing: it is <strong>not recorded as a grade</strong> and does not affect your progress or certificate.</p>
      <?php else: ?>
        <h2>Well done: you finished every lesson in this module</h2>
        <p class="muted">This module has no check-up questions, so there is no score to show.</p>
      <?php endif; ?>
    </div>

    <?php if ($pct !== null): ?>
    <div class="card">
      <h3>Lesson by lesson</h3>
      <ul class="plain-links">
        <?php foreach ($score['lessons'] as $x): ?>
          <li><?php if ($x['earned'] === null): ?>○ <?= e($x['title']) ?> <span class="muted small">(no check-up)</span><?php else: ?><?= $x['earned'] >= $x['max'] ? '✔' : '✘' ?> <a href="<?= e(url($base . '/lesson/' . $x['id'] . '/check')) ?>"><?= e($x['title']) ?></a> <span class="muted small"><?= rtrim(rtrim(number_format($x['earned'], 1), '0'), '.') ?> / <?= (int)$x['max'] ?></span><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <p class="form-actions">
      <a class="btn btn-navy btn-lg" href="<?= e(url($base . '/module/' . $m['id'] . '/pdf')) ?>">📄 Download the full module PDF</a>
      <?php if ($next): ?>
        <a class="btn btn-gold btn-lg" href="<?= e(url($base . '/lesson/' . $next['lesson'])) ?>">Next module: <?= e($next['module']['title']) ?> →</a>
      <?php else: ?>
        <a class="btn btn-gold btn-lg" href="<?= e(url($base)) ?>">Back to the course</a>
        <?php if ($cert): ?><a class="btn btn-navy" href="<?= e(url('/certificate/' . $cert['code'])) ?>">🏅 View my certificate</a><?php endif; ?>
      <?php endif; ?>
    </p>
    <?php if (!$hasPdf): ?><p class="small muted">No ready-made PDF has been uploaded for this module, so the button opens all the module’s notes on one page: choose “Save as PDF” in the print window.</p><?php endif; ?>
  </div>
</section>

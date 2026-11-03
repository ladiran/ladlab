<?php $passed = (bool)$a['passed']; $ans = json_decode((string)$a['answers'], true) ?: []; ?>
<section class="page-hero"><div class="wrap"><p class="post-date"><a href="<?= e(url('/learn/' . $c['slug'])) ?>" style="color:var(--sun)">← <?= e($c['title']) ?></a></p><h1>Results: <?= e($q['title']) ?></h1></div></section>
<section class="section">
  <div class="wrap narrow">
    <div class="card result-card <?= $passed ? 'res-pass' : 'res-fail' ?>">
      <p class="score"><?= (int)$a['score'] ?>%</p>
      <h2><?= $passed ? '🎉 You passed' : 'Not quite yet' ?></h2>
      <p><?= $late ? 'The time limit ran out, so the answers were not counted.' : 'Pass mark: ' . (int)$q['pass_mark'] . '%.' ?><?= $left !== null ? ' Attempts left: <strong>' . (int)$left . '</strong>.' : '' ?></p>
      <p>
        <?php if (!$passed && ($left === null || $left > 0)): ?><form method="post" action="<?= e(url('/learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/start')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-gold" type="submit">Try again</button></form><?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('/learn/' . $c['slug'])) ?>">Back to the course</a>
        <?php if ($cert): ?><a class="btn btn-navy" href="<?= e(url('/certificate/' . $cert['code'])) ?>">🏅 View my certificate</a><?php endif; ?>
      </p>
    </div>
    <?php if (!$late): foreach ($questions as $i => $qn): $r = $res[$qn['id']] ?? null; if (!$r) continue; ?>
      <div class="card question <?= $r['ok'] ? 'q-ok' : 'q-bad' ?>">
        <p><span class="qnum"><?= $i + 1 ?></span> <strong><?= $r['ok'] ? '✔ Correct' : '✘ Not correct' ?></strong> <span class="pts"><?= rtrim(rtrim(number_format((float)$r['earned'], 1), '0'), '.') ?> / <?= (int)$r['max'] ?></span></p>
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
    <?php endforeach; endif; ?>
  </div>
</section>

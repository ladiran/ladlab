<section class="page-hero"><div class="wrap"><p class="post-date"><a href="<?= e(url('/learn/' . $c['slug'])) ?>" style="color:var(--sun)">← <?= e($c['title']) ?></a></p><h1><?= e($q['title']) ?></h1></div></section>
<section class="section">
  <div class="wrap narrow">
    <div class="card">
      <?php if ($q['instructions']): ?><div class="prose"><?= md($q['instructions']) ?></div><?php endif; ?>
      <ul class="plain-links">
        <li>📝 <strong><?= (int)($q['questions_to_draw'] > 0 && $q['questions_to_draw'] < $qcount ? $q['questions_to_draw'] : $qcount) ?></strong> questions<?= $q['questions_to_draw'] > 0 && $q['questions_to_draw'] < $qcount ? ' (drawn at random from a bank of ' . (int)$qcount . ')' : '' ?></li>
        <li>🎯 Pass mark: <strong><?= (int)$q['pass_mark'] ?>%</strong></li>
        <li>⏱ <?= $q['time_limit_minutes'] ? '<strong>' . (int)$q['time_limit_minutes'] . ' minutes</strong> once you start' : 'No time limit' ?></li>
        <li>🔁 <?= $q['max_attempts'] ? 'Up to <strong>' . (int)$q['max_attempts'] . '</strong> attempts' : 'Retake as often as you like' ?><?= count($attempts) ? ' (' . count($attempts) . ' used)' : '' ?></li>
      </ul>
      <form method="post" action="<?= e(url('/learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/start')) ?>"><?= csrf_field() ?>
        <button class="btn btn-gold btn-lg" type="submit"><?= $open ? 'Resume your attempt' : ($attempts ? 'Try again' : 'Start') ?></button>
      </form>
    </div>
    <?php if ($attempts): ?>
      <h2 style="margin-top:2rem">Your attempts</h2>
      <table class="table"><thead><tr><th>When</th><th>Score</th><th>Result</th><th></th></tr></thead><tbody>
        <?php foreach ($attempts as $a): ?><tr><td><?= e(fmt_date($a['submitted_at'], 'j M Y, H:i')) ?></td><td><strong><?= (int)$a['score'] ?>%</strong></td><td><?= $a['passed'] ? '<span class="status status-replied">Passed</span>' : '<span class="status status-new">Not yet</span>' ?></td><td><a href="<?= e(url('/learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/result/' . $a['id'])) ?>">Review</a></td></tr><?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</section>

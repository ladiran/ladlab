<section class="page-hero"><div class="wrap"><p class="post-date"><span style="color:var(--sun)"><?= e($c['title']) ?></span></p><h1><?= e($q['title']) ?></h1>
  <?php if ($a['deadline_at']): ?><p class="lead" id="timer-wrap">⏱ Time left: <strong data-countdown="<?= (int)max(0, strtotime($a['deadline_at']) - time()) ?>">…</strong></p><?php endif; ?>
</div></section>
<section class="section">
  <div class="wrap narrow">
    <form method="post" action="<?= e(url('/learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/attempt/' . $a['id'] . '/submit')) ?>" class="quiz-form" data-quiz-form>
      <?= csrf_field() ?>
      <?php foreach ($questions as $i => $qn): ?>
        <fieldset class="card question">
          <legend><span class="qnum"><?= $i + 1 ?></span> <span class="pts"><?= (int)$qn['points'] ?> pt<?= $qn['points'] > 1 ? 's' : '' ?></span></legend>
          <div class="prose q-prompt"><?= md($qn['prompt']) ?></div>
          <?php if (in_array($qn['type'], ['mcq', 'truefalse'], true)): ?>
            <?php foreach ($qn['options'] as $o): ?><label class="opt"><input type="radio" name="answers[<?= (int)$qn['id'] ?>]" value="<?= (int)$o['id'] ?>"> <span class="prose"><?= md($o['label']) ?></span></label><?php endforeach; ?>
          <?php elseif ($qn['type'] === 'multi'): ?>
            <p class="small muted">Select all that apply.</p>
            <?php foreach ($qn['options'] as $o): ?><label class="opt"><input type="checkbox" name="answers[<?= (int)$qn['id'] ?>][]" value="<?= (int)$o['id'] ?>"> <span class="prose"><?= md($o['label']) ?></span></label><?php endforeach; ?>
          <?php elseif ($qn['type'] === 'numeric'): ?>
            <label class="num-answer">Your answer<?= $qn['num_unit'] !== '' ? ' (in ' . e($qn['num_unit']) . ')' : '' ?>
              <input type="text" inputmode="decimal" name="answers[<?= (int)$qn['id'] ?>]" autocomplete="off" maxlength="60" placeholder="e.g. 12.5"></label>
          <?php else: ?>
            <label class="num-answer">Your answer <input type="text" name="answers[<?= (int)$qn['id'] ?>]" autocomplete="off" maxlength="200"></label>
          <?php endif; ?>
        </fieldset>
      <?php endforeach; ?>
      <div class="form-actions"><button class="btn btn-gold btn-lg" type="submit" data-confirm-unanswered>Submit answers</button><a class="btn btn-ghost-dark" href="<?= e(url('/learn/' . $c['slug'])) ?>">Leave for now</a></div>
      <p class="small muted">You can leave and come back: <?= $a['deadline_at'] ? 'the clock keeps running.' : 'your attempt stays open, but answers are saved only when you submit.' ?></p>
    </form>
  </div>
</section>

<?php /** The answer inputs of one question. Expects $qn (question with options). */ ?>
<?php if (in_array($qn['type'], ['mcq', 'truefalse'], true)): ?>
  <?php foreach ($qn['options'] as $oi => $o): ?><label class="opt"><input type="radio" name="answers[<?= (int)$qn['id'] ?>]" value="<?= (int)$o['id'] ?>"> <?= $qn['type'] === 'mcq' ? '<strong>' . e(chr(65 + $oi)) . '.</strong> ' : '' ?><span class="prose"><?= md($o['label']) ?></span></label><?php endforeach; ?>
<?php elseif ($qn['type'] === 'multi'): ?>
  <p class="small muted">Select all that apply.</p>
  <?php foreach ($qn['options'] as $oi => $o): ?><label class="opt"><input type="checkbox" name="answers[<?= (int)$qn['id'] ?>][]" value="<?= (int)$o['id'] ?>"> <strong><?= e(chr(65 + $oi)) ?>.</strong> <span class="prose"><?= md($o['label']) ?></span></label><?php endforeach; ?>
<?php elseif ($qn['type'] === 'numeric'): ?>
  <label class="num-answer">Your answer<?= $qn['num_unit'] !== '' ? ' (in ' . e($qn['num_unit']) . ')' : '' ?>
    <input type="text" inputmode="decimal" name="answers[<?= (int)$qn['id'] ?>]" autocomplete="off" maxlength="60" placeholder="e.g. 12.5"></label>
<?php else: ?>
  <label class="num-answer">Your answer (fill in the gap) <input type="text" name="answers[<?= (int)$qn['id'] ?>]" autocomplete="off" maxlength="200"></label>
<?php endif; ?>

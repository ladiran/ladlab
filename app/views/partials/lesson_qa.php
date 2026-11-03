<?php /** Questions and answers under a lesson. Expects $c, $l, $questions. */ ?>
<section class="card qa" id="questions">
  <h3>Questions about this lesson</h3>
  <?php if ($questions): foreach ($questions as $q): ?>
    <div class="qa-item">
      <p><strong><?= e(Community::shortName($q['name'])) ?></strong> <span class="muted small"><?= e(time_ago($q['created_at'])) ?></span><?= $q['status'] === 'open' ? ' <span class="tag tag-new">waiting for an answer</span>' : '' ?></p>
      <p><?= nl2br(e($q['question'])) ?></p>
      <?php if ($q['answer'] !== null && $q['answer'] !== ''): ?><div class="qa-answer"><strong><?= e(setting('owner_name')) ?>:</strong><div class="prose"><?= md($q['answer']) ?></div></div><?php endif; ?>
    </div>
  <?php endforeach; else: ?><p class="muted">No questions yet. Ask the first one.</p><?php endif; ?>
  <form method="post" action="<?= e(url('/learn/' . $c['slug'] . '/lesson/' . $l['id'] . '/ask')) ?>" class="form">
    <?= csrf_field() ?>
    <label for="qa_q">Ask your lecturer</label>
    <textarea id="qa_q" name="question" rows="3" maxlength="1500" required placeholder="What would you like to understand better?"></textarea>
    <button class="btn btn-navy" type="submit">Send question</button>
  </form>
</section>
<?php
$base = '/learn/' . $c['slug'];
$activeModule = (int)$l['module_id'];
$total = $progress ? $progress['total'] : 0;
?>
<div class="lesson-shell">
  <aside class="lesson-side" id="lesson-side">
    <p class="side-title"><a href="<?= e(url($base)) ?>">← <?= e($c['title']) ?></a></p>
    <?php if ($progress): ?><div class="progress small-bar"><span style="width:<?= (int)$progress['pct'] ?>%"></span></div><p class="small muted"><?= (int)$progress['pct'] ?>% complete</p><?php endif; ?>
    <?php foreach ($cur['modules'] as $mi => $m): ?>
      <details class="module" <?= (int)$m['module']['id'] === $activeModule ? 'open' : '' ?>>
        <summary><strong><?= $mi + 1 ?>. <?= e($m['module']['title']) ?></strong></summary>
        <ul>
          <?php foreach ($m['lessons'] as $x): $can = $active || $x['is_free_preview']; $d = $progress && isset($progress['lessons'][(int)$x['id']]); ?>
            <li class="<?= (int)$x['id'] === (int)$l['id'] ? 'current' : '' ?>">
              <span class="lesson-ico"><?= $d ? '✔' : ($can ? '○' : '🔒') ?></span>
              <?php if ($can): ?><a href="<?= e(url($base . '/lesson/' . $x['id'])) ?>"><?= e($x['title']) ?></a><?php else: ?><span class="muted"><?= e($x['title']) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
          <?php if ($active && $m['lessons'] && !array_filter($m['lessons'], fn($x) => !isset($progress['lessons'][(int)$x['id']]))): ?>
            <li><span class="lesson-ico">📄</span> <a href="<?= e(url($base . '/module/' . $m['module']['id'])) ?>">Module summary &amp; PDF</a></li>
          <?php endif; ?>
          <?php foreach ($m['quizzes'] as $qz): ?>
            <li><span class="lesson-ico"><?= $progress && !empty($progress['quizzes'][(int)$qz['id']]['passed']) ? '✔' : '📝' ?></span> <?= $active ? '<a href="' . e(url($base . '/quiz/' . $qz['id'])) . '">' . e($qz['title']) . '</a>' : '<span class="muted">' . e($qz['title']) . '</span>' ?></li>
          <?php endforeach; ?>
        </ul>
      </details>
    <?php endforeach; ?>
    <?php foreach ($cur['final'] as $qz): ?><p class="final-link">🎓 <?= $active ? '<a href="' . e(url($base . '/quiz/' . $qz['id'])) . '">' . e($qz['title']) . '</a>' : '<span class="muted">' . e($qz['title']) . '</span>' ?></p><?php endforeach; ?>
    <?php if ($active && CourseChat::enabled($c)): ?><p class="final-link">💬 <a href="<?= e(url($base . '/chat')) ?>">Course chat</a></p><?php endif; ?>
  </aside>

  <main class="lesson-main" id="main-lesson">
    <p class="small"><a href="<?= e(url('/learn')) ?>">Learn</a> › <a href="<?= e(url($base)) ?>"><?= e($c['title']) ?></a></p>
    <h1><?= e($l['title']) ?><?php if ($l['status'] !== 'published'): ?> <span class="tag tag-new">Draft</span><?php endif; ?></h1>
    <p class="small muted"><?= $l['is_free_preview'] ? '<span class="tag tag-verified">Free preview</span> ' : '' ?><?= $l['video_minutes'] ? (int)$l['video_minutes'] . ' min video · ' : '' ?>Lesson <?= e((string)(array_search((int)$l['id'], array_map(fn($r) => (int)$r['id'], Learn::lessonOrder((int)$c['id'])), true) + 1)) ?> of <?= (int)$cur['lessons'] ?></p>

    <?php if (!$allowed): ?>
      <div class="card lock-box">
        <h2>🔒 This lesson is for enrolled students</h2>
        <p>Enrol in <strong><?= e($c['title']) ?></strong> to unlock all <?= (int)$cur['lessons'] ?> lessons, the quizzes and your certificate. Each module has a free preview lesson so you can try it first.</p>
        <p><a class="btn btn-gold" href="<?= e(url($base)) ?>">See the course and enrol</a> <?php if (!$u): ?><a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Sign in</a><?php endif; ?></p>
      </div>
    <?php else: ?>
      <?php if ($l['video_type'] === 'file' && $l['video_ref']): ?>
        <div class="video-wrap"><video controls preload="metadata" controlsList="nodownload" playsinline src="<?= e(url('/learn/video/' . $l['id'])) ?>">Your browser cannot play this video.</video></div>
      <?php elseif ($l['video_type'] === 'youtube' && $l['video_ref']): ?>
        <div class="video-wrap"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($l['video_ref']) ?>?rel=0" title="<?= e($l['title']) ?>" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
      <?php elseif ($l['video_type'] === 'vimeo' && $l['video_ref']): ?>
        <div class="video-wrap"><iframe src="https://player.vimeo.com/video/<?= e($l['video_ref']) ?>?dnt=1" title="<?= e($l['title']) ?>" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
      <?php endif; ?>

      <?php if ($l['objectives']): ?><div class="objectives card"><strong>🎯 By the end of this lesson you can:</strong><br><?= e($l['objectives']) ?></div><?php endif; ?>
      <div class="prose lesson-content"><?= md($l['content']) ?></div>

      <?php if ($files): ?>
        <div class="card"><h3>Downloads</h3><ul class="plain-links"><?php foreach ($files as $f): ?><li>📄 <a href="<?= e(url('/learn/file/' . $f['id'])) ?>"><?= e($f['title']) ?></a> <span class="muted small">(PDF, <?= e(number_format($f['size'] / 1024, 0)) ?> KB)</span></li><?php endforeach; ?></ul></div>
      <?php endif; ?>

      <nav class="lesson-nav">
        <?php if ($prev): ?><a class="btn btn-ghost" href="<?= e(url($base . '/lesson/' . $prev)) ?>">← Previous</a><?php else: ?><span></span><?php endif; ?>
        <?php if ($active && $u && !$manager && $needsCheck): ?>
          <a class="btn btn-gold" href="<?= e(url($base . '/lesson/' . $l['id'] . '/check')) ?>">Answer a quick question to continue →</a>
        <?php elseif ($active && $u && !$manager): ?>
          <form method="post" action="<?= e(url($base . '/lesson/' . $l['id'] . '/complete')) ?>"><?= csrf_field() ?>
            <button class="btn btn-gold" type="submit"><?= $done ? '✔ Completed: ' : 'Mark complete and ' ?><?= $moduleLast ? 'finish the module →' : 'continue →' ?></button>
          </form>
        <?php elseif ($next): ?>
          <a class="btn btn-gold" href="<?= e(url($base . '/lesson/' . $next)) ?>">Next lesson →</a>
        <?php else: ?><span></span><?php endif; ?>
      </nav>
      <?php if ($active && $u): partial('lesson_qa', compact('c', 'l', 'questions')); endif; ?>
      <?php if (!$active): ?><p class="flash flash-info small">You are viewing a free preview. <a href="<?= e(url($base)) ?>">Enrol</a> to unlock the rest of the course and track your progress.</p><?php endif; ?>
    <?php endif; ?>
  </main>
</div>

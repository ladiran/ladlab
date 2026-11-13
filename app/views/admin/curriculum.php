<?php partial('course_tabs', ['tab' => $tab]); $cid = (int)$c['id']; ?>
<p><a href="<?= e(url('/admin/courses')) ?>">← <?= Auth::isAdmin() ? 'All courses' : 'My courses' ?></a> · <a href="<?= e(url('/admin/courses/' . $cid)) ?>">Course details</a> · <a href="<?= e(url('/admin/progress/' . $cid)) ?>">Student progress</a> · <a href="<?= e(url('/learn/' . $c['slug'])) ?>" target="_blank">Preview as student ↗</a> · <a href="<?= e(url('/learn/' . $c['slug'] . '/chat')) ?>" target="_blank">💬 Course chat ↗</a></p>
<p class="muted"><strong><?= (int)$cur['lessons'] ?></strong> lessons · <strong><?= (int)$cur['quizzes'] ?></strong> quizzes · <?= (int)$cur['minutes'] ?> minutes of video. Use the arrows to reorder. Drafts are hidden from students.</p>

<?php foreach ($cur['modules'] as $mi => $m): $mod = $m['module']; ?>
<section class="card module-card">
  <div class="module-head">
    <form method="post" action="<?= e(url('/admin/modules/' . $mod['id'])) ?>" class="inline-edit">
      <?= csrf_field() ?>
      <strong class="mnum">Module <?= $mi + 1 ?></strong>
      <input type="text" name="title" value="<?= e($mod['title']) ?>" required maxlength="200" aria-label="Module title">
      <input type="text" name="summary" value="<?= e($mod['summary']) ?>" maxlength="400" placeholder="Short summary (optional)" aria-label="Module summary">
      <button class="btn btn-sm btn-ghost" type="submit">Save</button>
    </form>
    <div class="row-actions">
      <?php foreach (['up' => '↑', 'down' => '↓'] as $dir => $sym): ?><form method="post" action="<?= e(url('/admin/modules/' . $mod['id'] . '/move')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn btn-sm btn-ghost" type="submit" aria-label="Move <?= $dir ?>"><?= $sym ?></button></form><?php endforeach; ?>
      <form method="post" action="<?= e(url('/admin/modules/' . $mod['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this module with all its lessons and its quiz?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
    </div>
  </div>
  <form method="post" action="<?= e(url('/admin/modules/' . $mod['id'] . '/pdf')) ?>" enctype="multipart/form-data" class="inline-edit module-pdf">
    <?= csrf_field() ?>
    <span class="small">📄 <strong>Complete module PDF</strong> (students download it after finishing the module):</span>
    <?php if ($mod['pdf_path'] !== ''): ?>
      <span class="small"><?= e($mod['pdf_title'] ?: 'module.pdf') ?> (<?= e(number_format($mod['pdf_size'] / 1024, 0)) ?> KB)</span>
      <label class="check small"><input type="checkbox" name="remove_pdf" value="1"> remove</label>
    <?php else: ?><span class="small muted">none yet: students get a printable page of the notes</span><?php endif; ?>
    <input type="file" name="module_pdf" accept="application/pdf" aria-label="Module PDF">
    <button class="btn btn-sm btn-ghost" type="submit">Save PDF</button>
  </form>
  <table class="table"><tbody>
    <?php foreach ($m['lessons'] as $l): ?>
      <tr class="<?= $l['status'] === 'draft' ? 'row-hidden' : '' ?>">
        <td>▶ <a href="<?= e(url('/admin/lessons/' . $l['id'])) ?>"><strong><?= e($l['title']) ?></strong></a>
          <?= $l['is_free_preview'] ? ' <span class="tag tag-verified">free preview</span>' : '' ?><?= $l['status'] === 'draft' ? ' <span class="tag tag-new">draft</span>' : '' ?><?= $l['video_type'] !== 'none' ? ' <span class="tag">🎬 ' . e($l['video_type']) . '</span>' : '' ?> <a class="tag" href="<?= e(url('/admin/lessons/' . $l['id'] . '#bank')) ?>" title="Check-up question bank">❓ <?= (int)$l['bank_count'] ?></a></td>
        <td class="row-actions">
          <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/lessons/' . $l['id'])) ?>">Edit</a>
          <?php foreach (['up' => '↑', 'down' => '↓'] as $dir => $sym): ?><form method="post" action="<?= e(url('/admin/lessons/' . $l['id'] . '/move')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn btn-sm btn-ghost" type="submit" aria-label="Move <?= $dir ?>"><?= $sym ?></button></form><?php endforeach; ?>
          <form method="post" action="<?= e(url('/admin/lessons/' . $l['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this lesson?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">✕</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php foreach ($m['quizzes'] as $q): ?>
      <tr class="<?= $q['status'] === 'draft' ? 'row-hidden' : '' ?>"><td>📝 <a href="<?= e(url('/admin/quizzes/' . $q['id'])) ?>"><strong><?= e($q['title']) ?></strong></a> <span class="muted small"><?= (int)$q['question_count'] ?> questions</span></td><td class="row-actions"><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/quizzes/' . $q['id'])) ?>">Edit</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$m['lessons'] && !$m['quizzes']): ?><tr><td class="muted">No lessons yet.</td><td></td></tr><?php endif; ?>
  </tbody></table>
  <p class="actions"><a class="btn btn-sm btn-gold" href="<?= e(url('/admin/courses/' . $cid . '/lessons/new?module=' . $mod['id'])) ?>">＋ Add lesson</a> <a class="btn btn-sm btn-navy" href="<?= e(url('/admin/courses/' . $cid . '/quizzes/new?module=' . $mod['id'])) ?>">＋ Add quiz</a></p>
</section>
<?php endforeach; ?>

<section class="card">
  <h2>Final exam</h2>
  <?php foreach ($cur['final'] as $q): ?><p>🎓 <a href="<?= e(url('/admin/quizzes/' . $q['id'])) ?>"><strong><?= e($q['title']) ?></strong></a> <span class="muted small"><?= (int)$q['question_count'] ?> questions · pass <?= (int)$q['pass_mark'] ?>%</span></p><?php endforeach; ?>
  <?php if (!$cur['final']): ?><p class="muted">No final exam yet. Students earn the certificate after finishing every lesson and passing every quiz, including this one.</p><?php endif; ?>
  <p><a class="btn btn-sm btn-navy" href="<?= e(url('/admin/courses/' . $cid . '/quizzes/new?module=final')) ?>">＋ Add final exam</a></p>
</section>

<section class="card">
  <h2>Add a module</h2>
  <form method="post" action="<?= e(url('/admin/courses/' . $cid . '/modules')) ?>" class="form inline-edit">
    <?= csrf_field() ?>
    <input type="text" name="title" required maxlength="200" placeholder="Module title, e.g. Newton’s laws" aria-label="Module title">
    <input type="text" name="summary" maxlength="400" placeholder="Short summary (optional)" aria-label="Summary">
    <button class="btn btn-gold" type="submit">Add module</button>
  </form>
</section>

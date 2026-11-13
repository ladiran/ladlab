<?php
partial('course_tabs', ['tab' => $tab]);
$low = mb_strtolower($label);
$old = $_SESSION['old'] ?? null;
?>
<p class="muted">A <?= e($low) ?> is a registered member you trust to teach. <strong>Two-step verification is compulsory for every <?= e($low) ?>:</strong> until they turn it on, and while it is off, none of their <?= e($low) ?> rights work and they cannot turn it off. They sign in at the admin sign-in page and see only their own area: the courses assigned to them (and new ones they start as drafts), their students' questions and their students' progress. You manage every course and every <?= e($low) ?>. You can change what they are called under Site sections (Settings).</p>

<section class="card">
  <h2>Make a member a <?= e($low) ?></h2>
  <form method="post" action="<?= e(url('/admin/lecturers/add')) ?>" class="form">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="field"><label for="lc_email">Email address the member registered with</label><input type="email" id="lc_email" name="email" required value="<?= e((string)($old['email'] ?? '')) ?>" placeholder="name@example.com"><small class="hint">They must already have an account with a verified email address.</small></div>
    </div>
    <div class="field"><label for="lc_bio">Short bio (optional, shown on their courses)</label><textarea id="lc_bio" name="bio" rows="2" maxlength="600"><?= e((string)($old['bio'] ?? '')) ?></textarea></div>
    <button class="btn btn-gold" type="submit">Make a <?= e($low) ?></button>
  </form>
</section>
<?php clear_old(); ?>

<h2><?= e($label) ?>s (<?= count($rows) ?>)</h2>
<?php if ($rows): ?>
<?php foreach ($rows as $r): ?>
  <section class="card" style="margin-bottom:14px">
    <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:flex-start">
      <div>
        <h3 style="margin:0"><?= e($r['name']) ?></h3>
        <p class="small muted"><?= e($r['email']) ?> · since <?= e(fmt_date($r['lecturer_since'], 'j M Y')) ?> · last sign-in <?= $r['last_login'] ? e(time_ago($r['last_login'])) : 'never' ?> · <?= !empty($r['totp_enabled_at']) ? '🔐 two-step verification on' : '<span style="color:#a02727">⚠ two-step verification is off: their ' . e($low) . ' area stays closed until they turn it on</span>' ?></p>
        <p class="small"><strong><?= (int)$r['courses'] ?></strong> course<?= (int)$r['courses'] === 1 ? '' : 's' ?>, <strong><?= (int)$r['students'] ?></strong> enrolled student<?= (int)$r['students'] === 1 ? '' : 's' ?>
          <?php foreach ($courses[(int)$r['id']] ?? [] as $c): ?><br>🎓 <a href="<?= e(url('/admin/courses/' . $c['id'])) ?>"><?= e($c['title']) ?></a> · <a href="<?= e(url('/admin/progress/' . $c['id'])) ?>">progress</a><?php endforeach; ?></p>
      </div>
      <form method="post" action="<?= e(url('/admin/lecturers/' . $r['id'] . '/remove')) ?>" data-confirm="Make <?= e($r['name']) ?> an ordinary member again? Their courses stay but will have no <?= e($low) ?>, and they are signed out now."><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Remove the <?= e($low) ?> role</button></form>
    </div>
    <form method="post" action="<?= e(url('/admin/lecturers/' . $r['id'] . '/bio')) ?>" class="form">
      <?= csrf_field() ?>
      <label for="b<?= (int)$r['id'] ?>">Short bio (shown on their courses)</label>
      <textarea id="b<?= (int)$r['id'] ?>" name="bio" rows="2" maxlength="600"><?= e($r['lecturer_bio']) ?></textarea>
      <button class="btn btn-sm btn-navy" type="submit">Save bio</button>
    </form>
  </section>
<?php endforeach; ?>
<?php else: ?><p class="empty">No <?= e($low) ?>s yet.</p><?php endif; ?>

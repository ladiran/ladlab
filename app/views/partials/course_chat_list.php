<?php
/** The messages of a course chat. Expects $c (course), $viewer (user), $messages (oldest first), $mutedIds. */
$chatBase = '/learn/' . $c['slug'] . '/chat';
$isAdminViewer = $viewer['role'] === 'admin';
$today = date('Y-m-d');
?>
<?php if (!$messages): ?>
  <p class="chat-empty muted">No messages yet. Say hello to your classmates!</p>
<?php endif; ?>
<?php foreach ($messages as $m):
  $mine = (int)$m['user_id'] === (int)$viewer['id'];
  $lect = ($m['role'] ?? '') === 'admin';
  $ts = strtotime((string)$m['created_at']);
  $when = $ts ? (date('Y-m-d', $ts) === $today ? date('g:i A', $ts) : date('j M, g:i A', $ts)) : '';
  $isMuted = in_array((int)$m['user_id'], $mutedIds, true);
?>
  <div class="chat-msg<?= $mine ? ' mine' : '' ?><?= $lect ? ' lecturer' : '' ?>" id="m<?= (int)$m['id'] ?>">
    <div class="chat-meta">
      <strong><?= e(CourseChat::label($m)) ?></strong>
      <?php if ($lect): ?><span class="tag tag-verified">Lecturer</span><?php endif; ?>
      <span class="muted small"><?= e($when) ?></span>
      <?php if ($mine || $isAdminViewer): ?>
        <form method="post" action="<?= e(url($chatBase . '/' . (int)$m['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this message?"><?= csrf_field() ?><button class="chat-act" type="submit" title="Delete this message" aria-label="Delete this message">🗑</button></form>
      <?php endif; ?>
      <?php if ($isAdminViewer && !$lect && !$mine): ?>
        <form method="post" action="<?= e(url($chatBase . '/mute/' . (int)$m['user_id'])) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="mute" value="<?= $isMuted ? '0' : '1' ?>"><button class="chat-act" type="submit" title="<?= $isMuted ? 'Let this student post again' : 'Stop this student from posting' ?>"><?= $isMuted ? '🔊 unmute' : '🔇 mute' ?></button></form>
      <?php endif; ?>
    </div>
    <div class="chat-text"><?= nl2br(e((string)$m['body'])) ?></div>
  </div>
<?php endforeach; ?>

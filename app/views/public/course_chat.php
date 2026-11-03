<?php $base = '/learn/' . $c['slug']; ?>
<section class="page-hero"><div class="wrap"><p class="post-date"><a href="<?= e(url($base)) ?>" style="color:var(--sun)">← <?= e($c['title']) ?></a></p><h1>Course chat</h1>
  <p class="lead">Talk with your classmates and your lecturer. <?= (int)$people ?> student<?= $people === 1 ? '' : 's' ?> enrolled.</p></div></section>
<section class="section">
  <div class="wrap narrow">
    <?php if ($isAdmin && !CourseChat::enabled($c)): ?><p class="flash flash-info small">The chat is switched off for students (Admin → Course details). Only you can see it.</p><?php endif; ?>
    <div class="chat-box" data-chat data-chat-poll="<?= e(url($base . '/chat/poll')) ?>">
      <div class="chat-list" data-chat-list data-sig="<?= e($sig) ?>" role="log" aria-live="polite" aria-label="Chat messages"><?= $list ?></div>
      <?php if ($muted): ?>
        <p class="flash flash-info">The lecturer has switched off your messages in this chat. You can still read it.</p>
      <?php else: ?>
        <form method="post" action="<?= e(url($base . '/chat')) ?>" class="chat-form" data-chat-form id="end">
          <?= csrf_field() ?>
          <label class="sr-only" for="chat_msg">Your message</label>
          <textarea id="chat_msg" name="message" rows="2" maxlength="<?= (int)CourseChat::MAX_LEN ?>" placeholder="Write a message… (Enter to send, Shift+Enter for a new line)" required></textarea>
          <div class="chat-send"><button class="btn btn-gold" type="submit">Send</button><span class="small muted" data-chat-status role="status"></span></div>
        </form>
      <?php endif; ?>
    </div>
    <p class="small muted">Be kind and keep it about the course. Messages are plain text and visible to everyone enrolled. The lecturer can remove messages and mute people. You are shown by first name and initial only.</p>
  </div>
</section>

<p><a href="<?= e(url('/admin/enquiries')) ?>">← All enquiries</a></p>
<section class="card">
  <dl class="meta">
    <dt>From</dt><dd><?= e($row['name']) ?> · <a href="mailto:<?= e($row['email']) ?>?subject=<?= e(rawurlencode('Re: ' . ($row['subject'] ?: 'your enquiry'))) ?>"><?= e($row['email']) ?></a></dd>
    <?php if ($row['phone']): ?><dt>Phone</dt><dd><?= e($row['phone']) ?></dd><?php endif; ?>
    <dt>About</dt><dd><?= e($types[$row['type']] ?? $row['type']) ?></dd>
    <?php if ($row['subject']): ?><dt>Subject</dt><dd><?= e($row['subject']) ?></dd><?php endif; ?>
    <dt>Received</dt><dd><?= e(fmt_date($row['created_at'], 'j M Y, H:i')) ?></dd>
    <?php if (($row['mail_status'] ?? '') !== ''): ?><dt>Email alert</dt><dd><?= $row['mail_status'] === 'sent' ? '✔ delivered to you' : '<strong style="color:#a02727">⚠ not delivered: this message reached you only through the website. Check your email settings.</strong>' ?></dd><?php endif; ?>
    <dt>Status</dt><dd><span class="status status-<?= e($row['status']) ?>"><?= e($row['status']) ?></span></dd>
  </dl>
  <div class="message-body"><?= nl2br(e($row['message'])) ?></div>
  <div class="actions">
    <a class="btn btn-gold" href="mailto:<?= e($row['email']) ?>?subject=<?= e(rawurlencode('Re: ' . ($row['subject'] ?: 'your enquiry'))) ?>">Reply by email</a>
    <?php foreach (['replied' => 'Mark replied', 'archived' => 'Archive', 'new' => 'Mark unread'] as $k => $label): if ($row['status'] === $k) continue; ?>
      <form method="post" action="<?= e(url('/admin/enquiries/' . $row['id'] . '/status')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="status" value="<?= e($k) ?>"><button class="btn btn-ghost" type="submit"><?= e($label) ?></button></form>
    <?php endforeach; ?>
    <form method="post" action="<?= e(url('/admin/enquiries/' . $row['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this enquiry permanently?"><?= csrf_field() ?><button class="btn btn-danger" type="submit">Delete</button></form>
  </div>
</section>

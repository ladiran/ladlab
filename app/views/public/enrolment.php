<?php $awaiting = $e['status'] === 'awaiting_payment'; ?>
<section class="page-hero"><div class="wrap"><h1>Enrolment <?= e($e['enrol_no']) ?></h1><p class="lead"><?= e($c['title']) ?> · <span class="status st-<?= $e['status'] === 'active' ? 'paid' : ($e['status'] === 'cancelled' ? 'cancelled' : 'awaiting_payment') ?>"><?= e(Learn::ENROL_STATUS[$e['status']] ?? $e['status']) ?></span></p></div></section>
<section class="section">
  <div class="wrap narrow">
    <?php if ($e['status'] === 'active'): ?>
      <div class="card note"><h2>You are enrolled 🎉</h2><p>Your access is active.</p><p><a class="btn btn-gold" href="<?= e(url('/learn/' . $c['slug'])) ?>">Go to the course</a></p></div>
    <?php elseif ($e['status'] === 'cancelled'): ?>
      <p class="flash flash-error">This enrolment was cancelled. You can enrol again from the <a href="<?= e(url('/learn/' . $c['slug'])) ?>">course page</a>.</p>
    <?php else: ?>
      <div class="card pay-box">
        <h2>Pay by bank transfer</h2>
        <p>Please transfer <strong><?= e(Shop::naira($e['total'])) ?></strong><?= $e['discount'] > 0 ? ' (after your ' . e(Shop::naira($e['discount'])) . ' discount)' : '' ?> to:</p>
        <dl class="bank">
          <div><dt>Bank</dt><dd><?= e(Settings::get('bank_name')) ?></dd></div>
          <div><dt>Account name</dt><dd><?= e(Settings::get('bank_account_name')) ?></dd></div>
          <div><dt>Account number</dt><dd><strong data-copy="<?= e(Settings::get('bank_account_number')) ?>" class="copyable"><?= e(Settings::get('bank_account_number')) ?></strong> <small class="muted">(tap to copy)</small></dd></div>
          <div><dt>Amount</dt><dd><strong><?= e(Shop::naira($e['total'])) ?></strong> (exact)</dd></div>
          <div><dt>Reference / narration</dt><dd><strong data-copy="<?= e($e['enrol_no']) ?>" class="copyable"><?= e($e['enrol_no']) ?></strong></dd></div>
        </dl>
        <?php if (Settings::get('bank_account_number') === '0123456789'): ?><p class="flash flash-info small">These are placeholder bank details (shown while the site is being set up).</p><?php endif; ?>
        <p class="small">Your access starts as soon as we confirm your payment. In the meantime you can try the free preview lessons.</p>
      </div>
      <div class="card" style="margin-top:16px">
        <h2>Already paid? Upload your receipt</h2>
        <?php if ($e['receipt_path']): ?><p class="flash flash-success small">✔ Receipt received <?= e(time_ago($e['receipt_at'])) ?>. We will confirm your payment shortly.</p><?php endif; ?>
        <form method="post" action="<?= e(url('/enrolment/' . $e['enrol_no'] . '/receipt')) ?>" enctype="multipart/form-data" class="form">
          <?= csrf_field() ?>
          <label>Photo or PDF of the transfer receipt <input type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
          <button class="btn btn-navy" type="submit">Upload receipt</button>
        </form>
      </div>
      <form method="post" action="<?= e(url('/enrolment/' . $e['enrol_no'] . '/cancel')) ?>" data-confirm="Cancel this enrolment?" style="margin-top:16px"><?= csrf_field() ?><button class="btn btn-ghost-dark" type="submit">Cancel this enrolment</button></form>
    <?php endif; ?>
  </div>
</section>
<?php clear_old(); ?>

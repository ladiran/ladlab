<?php
$awaiting = $o['status'] === 'awaiting_payment';
$steps = $o['delivery_method'] === 'pickup'
    ? ['awaiting_payment' => 'Order placed', 'paid' => 'Payment confirmed', 'packed' => 'Ready for pickup', 'delivered' => 'Collected']
    : ['awaiting_payment' => 'Order placed', 'paid' => 'Payment confirmed', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$order = array_keys($steps);
$cur = array_search($o['status'], $order, true);
$dead = in_array($o['status'], ['cancelled', 'expired'], true);
$orderUrl = url('/order/' . $o['order_no'] . '/' . $o['access_token']);
?>
<section class="page-hero">
  <div class="wrap">
    <h1>Order <?= e($o['order_no']) ?></h1>
    <p class="lead"><span class="status st-<?= e($o['status']) ?>"><?= e(Shop::statusLabel($o)) ?></span> · placed <?= e(fmt_date($o['created_at'], 'j M Y, H:i')) ?></p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <p class="small muted">Keep this page's address: it is your link to follow this order. We have also emailed it to <?= e($o['email']) ?>.</p>

    <?php if (!$dead): ?>
    <ol class="steps">
      <?php foreach ($steps as $k => $label): $i = array_search($k, $order, true); ?><li class="<?= $cur !== false && $i < $cur ? 'done' : ($i === $cur ? 'now' : '') ?>"><?= e($label) ?></li><?php endforeach; ?>
    </ol>
    <?php else: ?>
      <p class="flash flash-error"><strong>This order is <?= e($o['status']) ?>.</strong> <?= $o['status'] === 'expired' ? 'We did not receive payment in time, so the items were released.' : '' ?> You are welcome to <a href="<?= e(url('/shop')) ?>">place a new order</a>.</p>
    <?php endif; ?>

    <div class="grid g2">
      <div>
        <?php if ($awaiting): ?>
        <div class="card pay-box">
          <h2>Pay by bank transfer</h2>
          <p>Please transfer <strong><?= e(Shop::naira($o['total'])) ?></strong> to:</p>
          <dl class="bank">
            <div><dt>Bank</dt><dd><?= e(Settings::get('bank_name')) ?></dd></div>
            <div><dt>Account name</dt><dd><?= e(Settings::get('bank_account_name')) ?></dd></div>
            <div><dt>Account number</dt><dd><strong data-copy="<?= e(Settings::get('bank_account_number')) ?>" class="copyable"><?= e(Settings::get('bank_account_number')) ?></strong> <small class="muted">(tap to copy)</small></dd></div>
            <div><dt>Amount</dt><dd><strong><?= e(Shop::naira($o['total'])) ?></strong> (exact)</dd></div>
            <div><dt>Reference / narration</dt><dd><strong data-copy="<?= e($o['order_no']) ?>" class="copyable"><?= e($o['order_no']) ?></strong></dd></div>
          </dl>
          <?php if ($o['expires_at']): ?><p class="small">Your items are reserved until <strong><?= e(fmt_date($o['expires_at'], 'j M Y, H:i')) ?></strong>. After that, unpaid orders are released.</p><?php endif; ?>
          <?php if (Settings::get('bank_account_number') === '0123456789'): ?><p class="flash flash-info small">These are placeholder bank details (shown while the site is being set up).</p><?php endif; ?>
        </div>
        <div class="card" style="margin-top:16px">
          <h2>Already paid? Upload your receipt</h2>
          <?php if ($o['receipt_path']): ?><p class="flash flash-success small">✔ Receipt received <?= e(time_ago($o['receipt_at'])) ?>. We will confirm your payment shortly. You can upload another one to replace it.</p><?php endif; ?>
          <form method="post" action="<?= e($orderUrl . '/receipt') ?>" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <label>Photo or PDF of the transfer receipt <input type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
            <button class="btn btn-navy" type="submit">Upload receipt</button>
          </form>
          <p class="small muted">Optional, but it helps us confirm faster. We confirm your order once the money reaches our account.</p>
        </div>
        <?php endif; ?>

        <?php if ($o['status'] === 'shipped'): ?>
          <div class="card note"><h2>On its way</h2><p><?= $o['courier'] ? 'Courier: <strong>' . e($o['courier']) . '</strong><br>' : '' ?><?= $o['tracking_no'] ? 'Tracking number: <strong>' . e($o['tracking_no']) . '</strong><br>' : '' ?>Shipped <?= e(fmt_date($o['shipped_at'], 'j M Y')) ?>.</p></div>
        <?php elseif ($o['status'] === 'packed' && $o['delivery_method'] === 'pickup'): ?>
          <div class="card note"><h2>Ready for pickup</h2><p><?= nl2br(e(Settings::get('pickup_address'))) ?></p></div>
        <?php endif; ?>

        <div class="card" style="margin-top:16px">
          <h2><?= $o['delivery_method'] === 'pickup' ? 'Pickup' : 'Delivery address' ?></h2>
          <p><?= e($o['name']) ?> · <?= e($o['phone']) ?><br>
            <?= $o['delivery_method'] === 'pickup' ? 'You will collect this order.' : e($o['address_line'] . ', ' . $o['city'] . ', ' . $o['state']) ?></p>
          <?php if ($o['customer_note']): ?><p class="muted small">Your note: <?= e($o['customer_note']) ?></p><?php endif; ?>
        </div>
      </div>

      <div>
        <div class="card">
          <h2>Items</h2>
          <ul class="mini-lines">
            <?php foreach ($items as $i): ?><li><span><?= (int)$i['qty'] ?> × <?= e($i['name']) ?><?php $vt = Shop::variantText($i['colour'], $i['size']); ?><?= $vt !== '' ? '<br><small class="muted">' . e($vt) . '</small>' : '' ?></span><strong><?= e(Shop::naira($i['line_total'])) ?></strong></li><?php endforeach; ?>
          </ul>
          <dl class="totals">
            <div><dt>Subtotal</dt><dd><?= e(Shop::naira($o['subtotal'])) ?></dd></div>
            <?php if ($o['discount'] > 0): ?><div><dt>Discount (<?= e($o['coupon_code']) ?>)</dt><dd>−<?= e(Shop::naira($o['discount'])) ?></dd></div><?php endif; ?>
            <div><dt>Delivery</dt><dd><?= $o['shipping_fee'] > 0 ? e(Shop::naira($o['shipping_fee'])) : 'Free' ?></dd></div>
            <div class="grand"><dt>Total</dt><dd><?= e(Shop::naira($o['total'])) ?></dd></div>
          </dl>
        </div>
        <div class="card" style="margin-top:16px">
          <h2>Order history</h2>
          <ul class="timeline-list">
            <?php foreach (array_reverse($events) as $ev): ?><li><span class="when"><?= e(fmt_date($ev['created_at'], 'j M, H:i')) ?></span> <?= e($ev['detail']) ?></li><?php endforeach; ?>
          </ul>
        </div>
        <?php if ($awaiting): ?>
          <form method="post" action="<?= e($orderUrl . '/cancel') ?>" data-confirm="Cancel this order?" style="margin-top:16px"><?= csrf_field() ?><button class="btn btn-ghost-dark" type="submit">Cancel this order</button></form>
        <?php endif; ?>
        <p class="small muted" style="margin-top:16px">Questions? <a href="<?= e(url('/contact')) ?>">Contact us</a><?php if (Settings::get('whatsapp')): ?> or <a href="https://wa.me/<?= e(Settings::get('whatsapp')) ?>?text=<?= rawurlencode('Hello, about order ' . $o['order_no']) ?>" target="_blank" rel="noopener noreferrer">WhatsApp us</a><?php endif; ?>, quoting <?= e($o['order_no']) ?>.</p>
      </div>
    </div>
  </div>
</section>
<?php clear_old(); ?>

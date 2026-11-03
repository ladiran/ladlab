<?php
partial('shop_tabs', ['tab' => $tab]);
$s = $o['status'];
$pickup = $o['delivery_method'] === 'pickup';
$act = url('/admin/orders/' . $o['id'] . '/action');
$couriers = ['GIG Logistics', 'Kwik', 'DHL', 'FedEx', 'Gokada / bike rider', 'Bus park / waybill', 'Other'];
?>
<p><a href="<?= e(url('/admin/orders')) ?>">← All orders</a></p>
<div class="order-head card">
  <div>
    <h2 style="margin:0"><?= e($o['order_no']) ?> <span class="status st-<?= e($s) ?>"><?= e(Shop::statusLabel($o)) ?></span></h2>
    <p class="muted small">Placed <?= e(fmt_date($o['created_at'], 'j M Y, H:i')) ?><?= $o['paid_at'] ? ' · paid ' . e(fmt_date($o['paid_at'], 'j M, H:i')) : '' ?><?= $s === 'awaiting_payment' && $o['expires_at'] ? ' · hold ends ' . e(fmt_date($o['expires_at'], 'j M, H:i')) : '' ?></p>
  </div>
  <div class="print-links">
    <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/orders/' . $o['id'] . '/print/pick')) ?>" target="_blank">🖨 Pick list</a>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/orders/' . $o['id'] . '/print/slip')) ?>" target="_blank">🖨 Packing slip</a>
    <?php if (!$pickup): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/orders/' . $o['id'] . '/print/label')) ?>" target="_blank">🖨 Address label</a><?php endif; ?>
  </div>
</div>

<div class="two-col">
  <section class="card">
    <h2>What to do next</h2>
    <?php if ($s === 'awaiting_payment'): ?>
      <p>Check your bank for <strong><?= e(Shop::naira($o['total'])) ?></strong> with reference <strong><?= e($o['order_no']) ?></strong> (or the customer's name).</p>
      <?php if ($o['receipt_path']): ?><p><a class="btn btn-navy" href="<?= e(url('/admin/orders/' . $o['id'] . '/receipt')) ?>" target="_blank">📎 View uploaded receipt</a> <small class="muted"><?= e(time_ago($o['receipt_at'])) ?></small></p><?php else: ?><p class="muted small">No receipt uploaded yet.</p><?php endif; ?>
      <form method="post" action="<?= e($act) ?>" class="form" data-confirm="Confirm that the money has reached your account?">
        <?= csrf_field() ?><input type="hidden" name="action" value="confirm_payment">
        <label>Note (optional) <input type="text" name="note" maxlength="200" placeholder="e.g. paid from GTBank, 14:05"></label>
        <button class="btn btn-gold" type="submit">✔ Confirm payment received</button>
      </form>
    <?php elseif ($s === 'paid'): ?>
      <form method="post" action="<?= e($act) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="pack"><button class="btn btn-navy" type="submit">📦 Mark as packed<?= $pickup ? ' (ready for pickup)' : '' ?></button></form>
    <?php endif; ?>

    <?php if (!$pickup && in_array($s, ['paid', 'packed'], true)): ?>
      <form method="post" action="<?= e($act) ?>" class="form" id="ship" style="margin-top:14px">
        <?= csrf_field() ?><input type="hidden" name="action" value="ship">
        <h3>Hand to courier</h3>
        <div class="form-row">
          <label>Courier <select name="courier"><option value="">Choose…</option><?php foreach ($couriers as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></label>
          <label>Tracking / waybill no. <input type="text" name="tracking_no" maxlength="80"></label>
        </div>
        <button class="btn btn-gold" type="submit">🚚 Mark as shipped (emails the customer)</button>
      </form>
    <?php endif; ?>
    <?php if (($s === 'shipped') || ($pickup && in_array($s, ['packed', 'paid'], true))): ?>
      <form method="post" action="<?= e($act) ?>" class="inline" data-confirm="Mark as <?= $pickup ? 'collected' : 'delivered' ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="deliver"><button class="btn btn-gold" type="submit">✅ Mark as <?= $pickup ? 'collected' : 'delivered' ?></button></form>
    <?php endif; ?>
    <?php if ($s === 'delivered'): ?><p>✅ Completed.</p><?php endif; ?>
    <?php if (in_array($s, ['cancelled', 'expired'], true)): ?><p>This order is <?= e($s) ?>; the items were returned to stock.</p><?php endif; ?>

    <?php if (in_array($s, ['awaiting_payment', 'paid', 'packed'], true)): ?>
      <form method="post" action="<?= e($act) ?>" class="form" style="margin-top:16px" data-confirm="Cancel this order and return the items to stock?<?= $s !== 'awaiting_payment' ? ' The customer has already paid, so you will need to refund them.' : '' ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
        <label>Reason (shown in the history) <input type="text" name="note" maxlength="200"></label>
        <button class="btn btn-danger" type="submit">Cancel order</button>
      </form>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Customer &amp; delivery</h2>
    <p><strong><?= e($o['name']) ?></strong><br><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a><br><?= e($o['phone']) ?>
      <?php if (preg_match('/^[0-9+\s()\-]{7,}$/', $o['phone'])): $digits = preg_replace('/\D/', '', $o['phone']); if (str_starts_with($digits, '0')) { $digits = '234' . substr($digits, 1); } ?>
        · <a href="https://wa.me/<?= e($digits) ?>?text=<?= rawurlencode('Hello ' . $o['name'] . ', about your order ' . $o['order_no']) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a><?php endif; ?></p>
    <p><?= $pickup ? '<strong>Pickup</strong>' : e($o['address_line'] . ', ' . $o['city'] . ', ' . $o['state']) ?></p>
    <?php if ($o['customer_note']): ?><p class="flash flash-info small">Customer note: <?= e($o['customer_note']) ?></p><?php endif; ?>
    <?php if ($customer): ?><p class="small muted">Account: <?= e($customer['name']) ?> (guest orders are not linked)</p><?php else: ?><p class="small muted">Guest checkout</p><?php endif; ?>
    <?php if ($o['courier'] || $o['tracking_no']): ?><p class="small">Courier: <?= e($o['courier']) ?> · Tracking: <?= e($o['tracking_no']) ?></p><?php endif; ?>
    <p class="small"><a href="<?= e(Shop::orderUrl($o)) ?>" target="_blank">Customer's order page ↗</a></p>
    <form method="post" action="<?= e(url('/admin/orders/' . $o['id'] . '/note')) ?>" class="form">
      <?= csrf_field() ?>
      <label>Private note <textarea name="admin_note" rows="2" maxlength="490"><?= e($o['admin_note']) ?></textarea></label>
      <button class="btn btn-sm btn-ghost" type="submit">Save note</button>
    </form>
  </section>
</div>

<section class="card" style="margin-top:20px">
  <h2>Items</h2>
  <table class="table">
    <thead><tr><th>Item</th><th>SKU / shelf</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr><td><strong><?= e($i['name']) ?></strong><br><small class="muted"><?= e(Shop::variantText($i['colour'], $i['size'])) ?></small></td><td><small><?= e($i['sku']) ?><?= $i['bin_location'] ? '<br>shelf ' . e($i['bin_location']) : '' ?></small></td><td><?= (int)$i['qty'] ?></td><td><?= e(Shop::naira($i['unit_price'])) ?></td><td><?= e(Shop::naira($i['line_total'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="4" style="text-align:right">Subtotal</td><td><?= e(Shop::naira($o['subtotal'])) ?></td></tr>
      <?php if ($o['discount'] > 0): ?><tr><td colspan="4" style="text-align:right">Discount (<?= e($o['coupon_code']) ?>)</td><td>−<?= e(Shop::naira($o['discount'])) ?></td></tr><?php endif; ?>
      <tr><td colspan="4" style="text-align:right">Delivery</td><td><?= e(Shop::naira($o['shipping_fee'])) ?></td></tr>
      <tr><td colspan="4" style="text-align:right"><strong>Total</strong></td><td><strong><?= e(Shop::naira($o['total'])) ?></strong></td></tr>
    </tfoot>
  </table>
</section>

<section class="card" style="margin-top:20px">
  <h2>History</h2>
  <table class="table"><tbody>
    <?php foreach ($events as $ev): ?><tr><td style="white-space:nowrap"><?= e(fmt_date($ev['created_at'], 'j M, H:i')) ?></td><td><?= e($ev['detail']) ?></td><td><small class="muted"><?= e($ev['actor']) ?></small></td></tr><?php endforeach; ?>
  </tbody></table>
</section>

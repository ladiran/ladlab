<?php $pickupOn = Settings::get('pickup_enabled') === '1'; ?>
<section class="page-hero"><div class="wrap"><h1>Checkout</h1></div></section>
<section class="section">
  <div class="wrap">
    <?php if (!$user): ?><p class="small">Have an account? <a href="<?= e(url('/login')) ?>">Sign in</a> to fill in your details automatically.</p><?php endif; ?>
    <div class="cart-layout">
      <form method="post" action="<?= e(url('/checkout')) ?>" class="form card checkout-form" novalidate
            data-checkout data-subtotal="<?= (int)$q['subtotal'] ?>" data-discount="<?= (int)$q['discount'] ?>" data-free-over="<?= (int)Settings::get('free_shipping_over') ?>"
            data-default-fee="<?= (int)Settings::get('default_shipping_fee') ?>" data-fees='<?= e(json_encode($rates, JSON_UNESCAPED_UNICODE)) ?>'>
        <?= csrf_field() ?><?= honeypot() ?>
        <h2>Your details</h2>
        <div class="form-row">
          <label>Full name * <input type="text" name="name" value="<?= e($val['name']) ?>" required maxlength="150" autocomplete="name"></label>
          <label>Phone / WhatsApp * <input type="tel" name="phone" value="<?= e($val['phone']) ?>" required maxlength="40" autocomplete="tel"></label>
        </div>
        <label>Email * <small class="muted">(we send your order details here)</small> <input type="email" name="email" value="<?= e($val['email']) ?>" required maxlength="190" autocomplete="email"></label>

        <h2>Delivery</h2>
        <?php if ($pickupOn): ?>
        <fieldset class="options">
          <label class="check"><input type="radio" name="delivery_method" value="ship" <?= $method === 'ship' ? 'checked' : '' ?> data-method> Deliver to me</label>
          <label class="check"><input type="radio" name="delivery_method" value="pickup" <?= $method === 'pickup' ? 'checked' : '' ?> data-method> I will pick it up (free)</label>
        </fieldset>
        <div data-pickup-box <?= $method === 'pickup' ? '' : 'hidden' ?>><div class="card note"><strong>Pickup:</strong><br><?= nl2br(e(Settings::get('pickup_address'))) ?></div></div>
        <?php else: ?><input type="hidden" name="delivery_method" value="ship"><?php endif; ?>
        <div data-ship-box <?= $method === 'pickup' ? 'hidden' : '' ?>>
          <label>Street address * <input type="text" name="address_line" value="<?= e($val['address_line']) ?>" maxlength="200" autocomplete="street-address"></label>
          <div class="form-row">
            <label>City / town * <input type="text" name="city" value="<?= e($val['city']) ?>" maxlength="100" autocomplete="address-level2"></label>
            <label>State *
              <select name="state" data-state><option value="">Choose your state…</option><?php foreach (NigeriaStates::LIST as $s): ?><option <?= $val['state'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
            </label>
          </div>
        </div>
        <label>Note for us <small class="muted">(optional)</small> <textarea name="customer_note" rows="2" maxlength="500" placeholder="Landmark, preferred courier, gift message…"><?= e($val['customer_note']) ?></textarea></label>

        <?php if (!$user): ?>
        <div class="options">
          <label class="check"><input type="checkbox" name="create_account" value="1" data-create <?= old('create_account') ? 'checked' : '' ?>> Create an account so I can track this order and check out faster next time</label>
          <div data-account-box <?= old('create_account') ? '' : 'hidden' ?>><label>Choose a password <small class="muted">(10+ characters)</small> <input type="password" name="password" minlength="10" autocomplete="new-password"></label></div>
        </div>
        <?php endif; ?>

        <label class="check"><input type="checkbox" name="agree" value="1" required> I accept the <a href="<?= e(url('/sales-terms')) ?>" target="_blank">Terms of Sale</a> and <a href="<?= e(url('/privacy')) ?>" target="_blank">Privacy Policy</a></label>
        <button class="btn btn-gold btn-lg" type="submit">Place order: pay by bank transfer</button>
        <p class="small muted">After you place the order you will see our bank details and your order number to use as the transfer reference. Your items are reserved for <?= (int)Settings::get('order_hold_hours') ?> hours.</p>
      </form>

      <aside class="card cart-summary">
        <h2>Your order</h2>
        <ul class="mini-lines">
          <?php foreach ($lines as $l): ?><li><span><?= (int)$l['qty'] ?> × <?= e($l['name']) ?><?php $vt = Shop::variantText($l['colour'], $l['size']); ?><?= $vt !== '' ? '<br><small class="muted">' . e($vt) . '</small>' : '' ?></span><strong><?= e(Shop::naira($l['line_total'])) ?></strong></li><?php endforeach; ?>
        </ul>
        <dl class="totals">
          <div><dt>Subtotal</dt><dd><?= e(Shop::naira($q['subtotal'])) ?></dd></div>
          <?php if ($q['discount'] > 0): ?><div><dt>Discount (<?= e($coupon['code']) ?>)</dt><dd>−<?= e(Shop::naira($q['discount'])) ?></dd></div><?php endif; ?>
          <div><dt>Delivery</dt><dd data-fee-out><?= $method === 'pickup' ? 'Free (pickup)' : ($val['state'] ? e(Shop::naira($q['shipping'])) : 'choose your state') ?></dd></div>
          <div class="grand"><dt>Total</dt><dd data-total-out><?= e(Shop::naira($val['state'] || $method === 'pickup' ? $q['total'] : $q['subtotal'] - $q['discount'])) ?></dd></div>
        </dl>
        <?php if ($coupon): ?>
          <form method="post" action="<?= e(url('/cart/coupon/remove')) ?>"><?= csrf_field() ?><input type="hidden" name="return" value="checkout"><small>Coupon <strong><?= e($coupon['code']) ?></strong> applied. <button class="linklike" type="submit">Remove</button></small></form>
        <?php else: ?>
          <form method="post" action="<?= e(url('/cart/coupon')) ?>" class="coupon-form"><?= csrf_field() ?><input type="hidden" name="return" value="checkout"><label class="sr-only" for="cc">Coupon code</label><input type="text" id="cc" name="coupon" placeholder="Coupon code" maxlength="40" autocomplete="off"><button class="btn btn-sm btn-ghost" type="submit">Apply</button></form>
        <?php endif; ?>
        <p class="small"><a href="<?= e(url('/cart')) ?>">← Edit cart</a></p>
      </aside>
    </div>
  </div>
</section>
<?php clear_old(); ?>

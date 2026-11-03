<section class="page-hero"><div class="wrap"><h1>Your cart</h1></div></section>
<section class="section">
  <div class="wrap">
    <?php if (!$lines): ?>
      <div class="empty"><p>Your cart is empty.</p><p><a class="btn btn-navy" href="<?= e(url('/shop')) ?>">Browse the shop</a></p></div>
    <?php else: ?>
    <div class="cart-layout">
      <form method="post" action="<?= e(url('/cart/update')) ?>" class="cart-lines">
        <?= csrf_field() ?>
        <?php foreach ($lines as $l): ?>
          <div class="cart-line card">
            <div class="cart-thumb"><?php if ($l['image']): ?><img src="<?= e(upload_url($l['image'])) ?>" alt=""><?php else: partial('tee_svg', ['hex' => $l['hex'], 'text' => $l['design_text'] ?: $l['name'], 'style' => $l['design_style']]); endif; ?></div>
            <div class="cart-info">
              <h3><a href="<?= e(url('/shop/' . $l['slug'])) ?>"><?= e($l['name']) ?></a></h3>
              <p class="muted small"><?php $vt = Shop::variantText($l['colour'], $l['size']); ?><?= $vt !== '' ? e($vt) . ' · ' : '' ?><?= e(Shop::naira($l['price'])) ?> each<?= $l['stock'] <= 3 ? ' · <strong class="low">Only ' . (int)$l['stock'] . ' left</strong>' : '' ?></p>
              <label class="qty small">Qty
                <input type="number" name="qty[<?= (int)$l['variant_id'] ?>]" value="<?= (int)$l['qty'] ?>" min="0" max="<?= min($l['stock'], Cart::MAX_PER_LINE) ?>">
              </label>
            </div>
            <div class="cart-total">
              <strong><?= e(Shop::naira($l['line_total'])) ?></strong>
              <button class="linklike small" type="submit" formaction="<?= e(url('/cart/remove')) ?>" name="variant_id" value="<?= (int)$l['variant_id'] ?>" formnovalidate>Remove</button>
            </div>
          </div>
        <?php endforeach; ?>
        <p><button class="btn btn-ghost" type="submit">Update quantities</button> <a class="btn btn-ghost-dark" href="<?= e(url('/shop')) ?>">Continue shopping</a></p>
      </form>

      <aside class="card cart-summary">
        <h2>Summary</h2>
        <dl class="totals">
          <div><dt>Subtotal</dt><dd><?= e(Shop::naira($q['subtotal'])) ?></dd></div>
          <?php if ($q['discount'] > 0): ?><div><dt>Discount (<?= e($coupon['code']) ?>)</dt><dd>−<?= e(Shop::naira($q['discount'])) ?></dd></div><?php endif; ?>
          <div class="muted"><dt>Delivery</dt><dd>calculated at checkout</dd></div>
        </dl>
        <?php if ($coupon): ?>
          <form method="post" action="<?= e(url('/cart/coupon/remove')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="return" value="cart"><small>Coupon <strong><?= e($coupon['code']) ?></strong> applied. <button class="linklike" type="submit">Remove</button></small></form>
        <?php else: ?>
          <form method="post" action="<?= e(url('/cart/coupon')) ?>" class="coupon-form">
            <?= csrf_field() ?><input type="hidden" name="return" value="cart">
            <label class="sr-only" for="coupon">Coupon code</label>
            <input type="text" id="coupon" name="coupon" placeholder="Coupon code" maxlength="40" autocomplete="off">
            <button class="btn btn-sm btn-ghost" type="submit">Apply</button>
          </form>
        <?php endif; ?>
        <a class="btn btn-gold btn-block btn-lg" href="<?= e(url('/checkout')) ?>">Checkout</a>
        <p class="small muted center">You pay by bank transfer after placing the order.</p>
      </aside>
    </div>
    <?php endif; ?>
  </div>
</section>

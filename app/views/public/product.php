<?php
$firstColour = $colours[0] ?? ['name' => '', 'hex' => '#0b1f3a'];
$total = array_sum($stockMap);
$wa = Settings::get('whatsapp');
$simple = ($p['product_type'] ?? 'apparel') === 'simple';
form_started('stockalert');
// First in-stock colour is pre-selected.
$sel = $firstColour;
foreach ($colours as $c) {
    foreach ($stockMap as $k => $q) {
        if (str_starts_with($k, $c['name'] . '|') && $q > 0) {
            $sel = $c;
            break 2;
        }
    }
}
?>
<section class="section">
  <div class="wrap">
    <p class="small"><a href="<?= e(url('/shop')) ?>">← All products</a><?php if (!empty($p['category'])): ?> · <?php $cs = Db::val('SELECT slug FROM shop_categories WHERE id = ?', [$p['category_id']]); ?><?= $cs ? '<a href="' . e(url('/shop/category/' . $cs)) . '">' . e($p['category']) . '</a>' : e($p['category']) ?><?php endif; ?><?php if (Cart::count()): ?> · <a href="<?= e(url('/cart')) ?>">View cart (<?= Cart::count() ?>)</a><?php endif; ?></p>
    <div class="product-layout">
      <div class="product-gallery" data-gallery>
        <div class="product-main">
          <?php if ($p['images']): ?>
            <img src="<?= e(upload_url($p['images'][0]['path'])) ?>" alt="<?= e($p['images'][0]['alt'] ?: $p['name']) ?>" data-main-img>
          <?php else: ?>
            <?php if ($simple): partial('product_placeholder', ['name' => $p['name']]); else: ?><div data-tee-preview><?php partial('tee_svg', ['hex' => $sel['hex'], 'text' => $p['design_text'] ?: $p['name'], 'style' => $p['design_style']]); ?></div><?php endif; ?>
          <?php endif; ?>
        </div>
        <?php if (count($p['images']) > 1): ?>
          <div class="thumbs">
            <?php foreach ($p['images'] as $i => $img): ?><button type="button" class="thumb-btn <?= $i === 0 ? 'on' : '' ?>" data-src="<?= e(upload_url($img['path'])) ?>" aria-label="Photo <?= $i + 1 ?>"><img src="<?= e(upload_url($img['path'])) ?>" alt="" loading="lazy"></button><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="product-buy">
        <h1><?= e($p['name']) ?><?php if ($p['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></h1>
        <?php if ($p['tagline']): ?><p class="lead"><?= e($p['tagline']) ?></p><?php endif; ?>
        <p class="price big"><strong><?= e(Shop::naira($p['price'])) ?></strong><?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?> <s><?= e(Shop::naira($p['compare_price'])) ?></s><?php endif; ?></p>

        <?php if ($total <= 0): ?>
          <p class="flash flash-error">Sold out right now.</p>
          <form method="post" action="<?= e(url('/shop/' . $p['slug'] . '/notify')) ?>" class="notify-form">
            <?= csrf_field() ?>
            <label for="na_email"><strong>Email me when it is back</strong></label>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="inline-edit"><input type="email" id="na_email" name="email" required maxlength="190" placeholder="you@email.com" value="<?= e((string)(Auth::check() ? (Auth::user()['email'] ?? '') : '')) ?>"><button class="btn btn-navy" type="submit">Notify me</button></div>
            <p class="small muted">One email, only about this product.<?= $wa ? ' Or <a href="https://wa.me/' . e($wa) . '?text=' . rawurlencode('Hello! Please tell me when "' . $p['name'] . '" is back in stock.') . '" target="_blank" rel="noopener noreferrer">ask us on WhatsApp</a>.' : '' ?></p>
          </form>
        <?php else: ?>
        <form method="post" action="<?= e(url('/cart/add')) ?>" class="buy-form" data-buy data-stock='<?= e(json_encode($stockMap, JSON_UNESCAPED_UNICODE)) ?>'>
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <?php if ($simple): ?>
            <?php if (count($colours) > 1): ?>
            <fieldset class="choice">
              <legend>Choose: <strong data-colour-name><?= e($sel['name']) ?></strong></legend>
              <?php foreach ($colours as $c): ?>
                <label class="size-opt"><input type="radio" name="colour" value="<?= e($c['name']) ?>" data-hex="<?= e($c['hex']) ?>" <?= $c['name'] === $sel['name'] ? 'checked' : '' ?> required><span><?= e($c['name']) ?></span></label>
              <?php endforeach; ?>
            </fieldset>
            <?php else: ?>
              <input type="radio" name="colour" value="<?= e($colours[0]['name']) ?>" data-hex="<?= e($colours[0]['hex']) ?>" checked hidden>
            <?php endif; ?>
            <input type="radio" name="size" value="STD" checked hidden>
            <p class="small stock-note" data-stock-note aria-live="polite"></p>
          <?php else: ?>
          <fieldset class="choice">
            <legend>Colour: <strong data-colour-name><?= e($sel['name']) ?></strong></legend>
            <?php foreach ($colours as $c): ?>
              <label class="colour-opt" title="<?= e($c['name']) ?>"><input type="radio" name="colour" value="<?= e($c['name']) ?>" data-hex="<?= e($c['hex']) ?>" <?= $c['name'] === $sel['name'] ? 'checked' : '' ?> required><span class="swatch lg" style="background:<?= e($c['hex']) ?>"></span><span class="sr-only"><?= e($c['name']) ?></span></label>
            <?php endforeach; ?>
          </fieldset>
          <fieldset class="choice">
            <legend>Size <small class="muted">(<a href="#size-guide">size guide</a>)</small></legend>
            <?php foreach ($sizes as $s): ?>
              <label class="size-opt"><input type="radio" name="size" value="<?= e($s) ?>" required><span><?= e($s) ?></span></label>
            <?php endforeach; ?>
            <p class="small stock-note" data-stock-note aria-live="polite"></p>
          </fieldset>
          <?php endif; ?>
          <div class="buy-row">
            <label class="qty">Qty
              <select name="qty"><?php for ($i = 1; $i <= 5; $i++): ?><option><?= $i ?></option><?php endfor; ?></select>
            </label>
            <button class="btn btn-gold btn-lg" type="submit">Add to cart</button>
          </div>
        </form>
        <?php endif; ?>

        <ul class="buy-points">
          <li>📦 <?= e(Settings::get('dispatch_text')) ?></li>
          <li>🏦 Pay by bank transfer after you order</li>
          <?php if (Settings::get('pickup_enabled') === '1'): ?><li>🛍 Free pickup available</li><?php endif; ?>
          <?php if ($wa): ?><li>💬 <a href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Hello! I would like to order: ' . $p['name']) ?>" target="_blank" rel="noopener noreferrer">Order or ask on WhatsApp</a></li><?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="grid g2 product-text">
      <div class="prose">
        <h2><?= $simple ? 'About this product' : 'About this tee' ?></h2>
        <?= md($p['description']) ?>
      </div>
      <div class="prose">
        <?php if ($simple): ?>
          <?php if (trim((string)$p['details']) !== ''): ?><h2>Details</h2><?= md($p['details']) ?><?php endif; ?>
        <?php else: ?>
        <h2>Fabric &amp; care</h2>
        <?= md($p['details']) ?>
        <h2 id="size-guide">Size guide</h2>
        <?= md(Settings::get('size_guide')) ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($related): ?>
      <h2 class="section-title" style="margin-top:2.5rem">You may also like</h2>
      <div class="grid g3 products"><?php foreach (array_slice($related, 0, 3) as $r): partial('product_card', ['p' => $r]); endforeach; ?></div>
    <?php endif; ?>
  </div>
</section>

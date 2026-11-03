<?php
/** @var array $p product row from Shop::products() */
$total = (int)$p['total_stock'];
$firstHex = $p['swatches'][0]['colour_hex'] ?? '#0b1f3a';
?>
<a class="card product-card" href="<?= e(url('/shop/' . $p['slug'])) ?>">
  <div class="product-media">
    <?php if ($p['image']): ?>
      <img src="<?= e(upload_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
    <?php else: ?>
      <?php if ($p['product_type'] === 'simple'): partial('product_placeholder', ['name' => $p['name']]); else: partial('tee_svg', ['hex' => $firstHex, 'text' => $p['design_text'] ?: $p['name'], 'style' => $p['design_style']]); endif; ?>
    <?php endif; ?>
    <?php if ($total <= 0): ?><span class="tag tag-breaking prod-badge">Sold out</span>
    <?php elseif ($total <= Shop::LOW_STOCK_BADGE): ?><span class="tag tag-new prod-badge">Low stock</span>
    <?php elseif ($p['is_featured']): ?><span class="tag tag-verified prod-badge">Popular</span><?php endif; ?>
  </div>
  <div class="product-info">
    <h3><?= e($p['name']) ?><?php if ($p['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></h3>
    <p class="price"><strong><?= e(Shop::naira($p['price'])) ?></strong><?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?> <s><?= e(Shop::naira($p['compare_price'])) ?></s><?php endif; ?></p>
    <?php if ($p['swatches'] && $p['product_type'] !== 'simple'): ?><p class="swatches" aria-label="Colours"><?php foreach ($p['swatches'] as $s): ?><span class="swatch" style="background:<?= e($s['colour_hex']) ?>" title="<?= e($s['colour']) ?>"></span><?php endforeach; ?></p><?php endif; ?>
  </div>
</a>

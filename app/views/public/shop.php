<section class="page-hero">
  <div class="wrap">
    <?php if (!empty($current)): ?>
      <p class="small"><a href="<?= e(url('/shop')) ?>" style="color:var(--sky-light)">← Whole shop</a></p>
      <h1><?= e($current['name']) ?></h1>
      <?php if ($current['description'] !== ''): ?><p class="lead"><?= e($current['description']) ?></p><?php endif; ?>
    <?php else: ?>
      <h1>The ClearSky shop</h1>
      <p class="lead"><?= e(Settings::get('shop_intro')) ?></p>
    <?php endif; ?>
    <p class="lead-sub">📦 <?= e(Settings::get('dispatch_text')) ?> · 🏦 Pay by bank transfer</p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <?php if (count($categories) > 1): ?>
    <nav class="chips" aria-label="Shop categories">
      <a class="chip <?= empty($current) ? 'on' : '' ?>" href="<?= e(url('/shop')) ?>">All</a>
      <?php foreach ($categories as $c): ?><a class="chip <?= ($current['slug'] ?? '') === $c['slug'] ? 'on' : '' ?>" href="<?= e(url('/shop/category/' . $c['slug'])) ?>"><?= e($c['name']) ?> <small>(<?= (int)$c['items'] ?>)</small></a><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <form method="get" action="<?= e(url(!empty($current) ? '/shop/category/' . $current['slug'] : '/shop')) ?>" class="filters">
      <label class="sr-only" for="sq">Search</label>
      <input type="search" id="sq" name="q" value="<?= e($filters['q']) ?>" placeholder="Search the shop…" maxlength="60">
      <label class="sr-only" for="ss">Sort</label>
      <select id="ss" name="sort">
        <option value="">Featured</option>
        <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
        <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
      </select>
      <button class="btn btn-navy" type="submit">Apply</button>
      <?php if ($filters['q'] || $filters['sort']): ?><a class="btn btn-ghost-dark" href="<?= e(url(!empty($current) ? '/shop/category/' . $current['slug'] : '/shop')) ?>">Clear</a><?php endif; ?>
    </form>
    <?php if ($products): ?>
      <p class="muted small"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></p>
      <div class="grid g3 products">
        <?php foreach ($products as $p): partial('product_card', ['p' => $p]); endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">No products match your search.</p>
    <?php endif; ?>
    <div class="trust-row">
      <span>📦 <?= e(Settings::get('dispatch_text')) ?></span>
      <span>🏦 Pay by bank transfer</span>
      <?php if (Settings::get('pickup_enabled') === '1'): ?><span>🛍 Pickup available</span><?php endif; ?>
      <?php if (Settings::get('whatsapp')): ?><span>💬 <a href="https://wa.me/<?= e(Settings::get('whatsapp')) ?>" target="_blank" rel="noopener noreferrer">Questions? WhatsApp us</a></span><?php endif; ?>
    </div>
  </div>
</section>
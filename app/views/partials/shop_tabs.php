<?php
$pending = (int)Db::val("SELECT COUNT(*) FROM orders WHERE status IN ('awaiting_payment','paid')");
$low = count(Shop::lowStock());
$tabs = [
    'orders' => ['Orders', 'admin/orders', $pending],
    'products' => ['Products', 'admin/shop/products', 0],
    'categories' => ['Categories', 'admin/categories', 0],
    'stock' => ['Stock', 'admin/shop/stock', $low],
    'shipping' => ['Delivery fees', 'admin/shop/shipping', 0],
    'coupons' => ['Coupons', 'admin/coupons', 0],
    'customers' => ['Customers', 'admin/customers', 0],
    'settings' => ['Shop settings', 'admin/settings/shop', 0],
];
?>
<nav class="tabs" aria-label="Shop sections">
  <?php foreach ($tabs as $k => [$label, $href, $n]): ?>
    <a class="<?= ($tab ?? '') === $k ? 'on' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?><?php if ($n): ?> <span class="count"><?= $n ?></span><?php endif; ?></a>
  <?php endforeach; ?>
  <a href="<?= e(url('/shop')) ?>" target="_blank" rel="noopener">View shop ↗</a>
</nav>

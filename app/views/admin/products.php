<?php partial('shop_tabs', ['tab' => $tab]); ?>
<p class="muted">Everything you sell. Add T-shirts (colours and sizes) or one-size products such as books, mugs and teaching aids, and put each in a <a href="<?= e(url('/admin/categories')) ?>">category</a>.</p>
<p class="actions"><a class="btn btn-gold" href="<?= e(url('/admin/shop/products/new')) ?>">＋ Add a product</a> <a class="btn btn-navy" href="<?= e(url('/admin/categories/new')) ?>">＋ Add a category</a></p>
<?php if ($rows): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Status</th><th>Stock</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['status'] !== 'active' ? 'row-hidden' : '' ?>">
      <td><a href="<?= e(url('/admin/shop/products/' . $r['id'])) ?>"><strong><?= e($r['name']) ?></strong></a><?php if ($r['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?><br><small class="muted"><?= $r['product_type'] === 'simple' ? 'One-size product' : 'Colours & sizes' ?><?= (int)$r['waiting'] ? ' · ' . (int)$r['waiting'] . ' waiting for restock' : '' ?></small></td>
      <td><?= e($r['category']) ?></td>
      <td><?= e(Shop::naira($r['price'])) ?></td>
      <td><span class="status <?= $r['status'] === 'active' ? 'status-replied' : 'status-archived' ?>"><?= $r['status'] === 'active' ? 'On sale' : 'Draft' ?></span></td>
      <td><?= (int)$r['total_stock'] ?><?= $r['low'] ? ' <span class="tag tag-new">' . (int)$r['low'] . ' low</span>' : '' ?></td>
      <td class="row-actions">
        <a class="btn btn-sm btn-ghost" href="<?= e(url('/admin/shop/products/' . $r['id'])) ?>">Edit</a>
        <form method="post" action="<?= e(url('/admin/shop/products/' . $r['id'] . '/duplicate')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit" title="Make a copy to edit">Copy</button></form>
        <form method="post" action="<?= e(url('/admin/shop/products/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this product and its photos?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="empty">No products yet.</p><?php endif; ?>
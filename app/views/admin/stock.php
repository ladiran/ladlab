<?php partial('shop_tabs', ['tab' => $tab]); ?>
<form method="get" action="<?= e(url('/admin/shop/stock')) ?>" class="filters" style="margin-bottom:12px">
  <select name="product" aria-label="Product"><option value="">All products</option><?php foreach ($products as $pr): ?><option value="<?= (int)$pr['id'] ?>" <?= $product === (int)$pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option><?php endforeach; ?></select>
  <label class="check"><input type="checkbox" name="low" value="1" <?= $lowOnly ? 'checked' : '' ?>> Low stock only</label>
  <button class="btn btn-navy" type="submit">Filter</button>
  <a class="btn btn-ghost" href="<?= e(url('/admin/shop/stock/log')) ?>">Full movement log</a>
</form>
<p class="muted">A row turns amber when stock is at or below its re-order level. The shelf location is printed on pick lists.</p>
<?php if ($rows): ?>
<form method="post" action="<?= e(url('/admin/shop/stock')) ?>">
  <?= csrf_field() ?><input type="hidden" name="product" value="<?= (int)$product ?>">
  <div class="table-wrap"><table class="table stock-table">
    <thead><tr><th>Product</th><th>Colour</th><th>Size</th><th>SKU</th><th>Shelf</th><th>Re-order at</th><th>In stock</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $low = (int)$r['stock'] <= (int)$r['reorder_level']; ?>
      <tr class="<?= $low ? 'low-row' : '' ?>">
        <td><?= e($r['name']) ?><?= $r['is_sample'] ? ' <span class="badge-sample">sample</span>' : '' ?></td>
        <td><span class="swatch" style="background:<?= e($r['colour_hex']) ?>"></span> <?= e($r['colour']) ?></td>
        <td><strong><?= e($r['size'] === 'STD' ? '—' : $r['size']) ?></strong></td>
        <td><small><?= e($r['sku']) ?></small></td>
        <td><input type="text" name="v[<?= (int)$r['id'] ?>][bin]" value="<?= e($r['bin_location']) ?>" maxlength="40" aria-label="Shelf for <?= e($r['sku']) ?>" style="width:5.5rem"></td>
        <td><input type="number" name="v[<?= (int)$r['id'] ?>][reorder]" value="<?= (int)$r['reorder_level'] ?>" min="0" max="1000" aria-label="Re-order level" style="width:4.5rem"></td>
        <td><input type="number" name="v[<?= (int)$r['id'] ?>][stock]" value="<?= (int)$r['stock'] ?>" min="0" max="100000" aria-label="Stock for <?= e($r['sku']) ?>" style="width:5rem"></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="form-actions" style="margin-top:12px">
    <label>Reason for any stock changes <input type="text" name="reason" maxlength="90" placeholder="e.g. New delivery from printer" style="width:20rem"></label>
    <button class="btn btn-gold" type="submit">Save stock</button>
  </div>
</form>
<?php else: ?><p class="empty">No stock rows match.</p><?php endif; ?>

<h2 style="margin-top:2rem">Latest movements</h2>
<div class="table-wrap"><table class="table">
  <thead><tr><th>When</th><th>Item</th><th>Change</th><th>Now</th><th>Reason</th></tr></thead>
  <tbody>
  <?php foreach ($log as $l): ?><tr><td><?= e(fmt_date($l['created_at'], 'j M, H:i')) ?></td><td><?= e($l['label']) ?></td><td><strong style="color:<?= $l['change_qty'] < 0 ? '#a02727' : '#14603f' ?>"><?= $l['change_qty'] > 0 ? '+' : '' ?><?= (int)$l['change_qty'] ?></strong></td><td><?= (int)$l['stock_after'] ?></td><td><small><?= e($l['reason']) ?> <span class="muted">(<?= e($l['actor']) ?>)</span></small></td></tr><?php endforeach; ?>
  </tbody>
</table></div>

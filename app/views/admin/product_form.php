<?php
partial('shop_tabs', ['tab' => $tab]);
$old = $_SESSION['old'] ?? null;
$v = function (string $k, $default = '') use ($p, $old) {
    if ($old !== null) {
        return $old[$k] ?? $default;
    }
    return $p[$k] ?? $default;
};
$action = $p ? '/admin/shop/products/' . $p['id'] : '/admin/shop/products/new';
$F = function (string $name, string $type, string $label, string $help = '', array $o = []) use ($v) {
    partial('field', ['name' => $name, 'type' => $type, 'label' => $label, 'o' => ['help' => $help] + $o, 'value' => $v($name, $o['default'] ?? '')]);
};
$active = array_filter($variants, fn($x) => (int)$x['is_active'] === 1);
$byColour = [];
foreach ($active as $x) {
    $byColour[$x['colour']][$x['size']] = $x;
}
$sizesOrder = $p ? Shop::parseSizes((string)$p['sizes']) : [];
$ptype = (string)$v('product_type', 'apparel');
$allCats = Db::all('SELECT id, name FROM shop_categories ORDER BY sort_order, name');
$defaultCat = (int)($allCats[0]['id'] ?? 0);
$optionsText = '';
if ($p && $p['product_type'] === 'simple') {
    $optionsText = implode("\n", array_filter(array_map(fn($c) => $c['name'] === 'Standard' ? '' : $c['name'], Shop::parseColours((string)$p['colours']))));
}
?>
<p><a href="<?= e(url('/admin/shop/products')) ?>">← All products</a><?= $p ? ' · <a href="' . e(url('/shop/' . $p['slug'])) . '" target="_blank">View in shop ↗</a>' : '' ?></p>
<?php if ($p && $p['is_sample']): ?><p class="flash flash-info">This is a sample product. Saving it makes it your own, and hides the other samples.</p><?php endif; ?>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="form admin-form" data-preview-url="<?= e(url('/admin/markdown-preview')) ?>">
  <?= csrf_field() ?>
  <h2>Details</h2>
  <?php $F('name', 'text', 'Product name', 'e.g. “Isobar Tee”', ['required' => true, 'max' => 150]); ?>
  <div class="form-row">
    <?php $F('price', 'number', 'Price (₦)', 'Whole Naira, e.g. 6500', ['required' => true, 'min' => 0]); ?>
    <?php $F('compare_price', 'number', '“Was” price (₦), optional', 'Shown crossed out next to the price.', ['min' => 0]); ?>
  </div>
  <?php $F('tagline', 'text', 'One-line description', 'Shown under the name.', ['max' => 200]); ?>
  <?php $F('description', 'markdown', 'Description', '', ['rows' => 6]); ?>
  <?php $F('details', 'markdown', 'Fabric & care', '', ['rows' => 5]); ?>

  <h2>Kind of product</h2>
  <div class="field"><label for="f_ptype">What are you selling?</label>
    <select id="f_ptype" name="product_type" data-ptype>
      <option value="apparel" <?= $ptype === 'apparel' ? 'selected' : '' ?>>Clothing: comes in colours and sizes (T-shirts, caps)</option>
      <option value="simple" <?= $ptype === 'simple' ? 'selected' : '' ?>>One-size item: a single stock count (books, mugs, pens, teaching aids)</option>
    </select></div>
  <div class="ptype-box" data-for="apparel">
    <h2>Colours &amp; sizes</h2>
    <?php $F('colours', 'textarea', 'Colours (one per line)', 'Format: Name|#hexcolour. Example: Navy|#0b1f3a', ['rows' => 4, 'default' => "Navy|#0b1f3a\nWhite|#ffffff"]); ?>
    <?php $F('sizes', 'text', 'Sizes (comma-separated)', 'Example: S,M,L,XL,XXL', ['max' => 190, 'default' => 'S,M,L,XL,XXL']); ?>
  </div>
  <div class="ptype-box" data-for="simple">
    <h2>Options (optional)</h2>
    <?php $F('options', 'textarea', 'Choices, one per line', 'Leave empty if there is only one version. Example for a book: Paperback (one line), Hardback (next line). Each choice has its own stock count.', ['rows' => 3, 'default' => $optionsText]); ?>
  </div>

  <h2>Photos</h2>
  <?php if ($images): ?>
    <div class="img-grid">
      <?php foreach ($images as $i => $img): ?>
        <figure class="card"><img src="<?= e(upload_url($img['path'])) ?>" alt="" class="thumb">
          <label class="check small"><input type="radio" name="main_image" value="<?= (int)$img['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>> Main photo</label>
          <label class="check small"><input type="checkbox" name="remove_image[]" value="<?= (int)$img['id'] ?>"> Delete</label>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php else: ?><p class="muted small">No photos yet: a drawn placeholder is shown in the shop until you upload some.</p><?php endif; ?>
  <div class="field"><label for="f_images">Add photos (up to 8, JPG/PNG/WebP, 8 MB each)</label><input type="file" id="f_images" name="images[]" multiple accept="image/jpeg,image/png,image/webp"></div>

  <div class="ptype-box" data-for="apparel">
  <h2>Placeholder artwork</h2>
  <p class="muted small">Only used for the drawn T-shirt shown while there are no photos.</p>
  <div class="form-row">
    <?php $F('design_text', 'text', 'Wording on the shirt', '', ['max' => 80]); ?>
    <?php partial('field', ['name' => 'design_style', 'type' => 'select', 'label' => 'Style', 'o' => ['options' => ['text' => 'Text only', 'stripes' => 'Layered stripes', 'curves' => 'Curved lines']], 'value' => $v('design_style', 'text')]); ?>
  </div>
  </div>

  <h2>Shop listing</h2>
  <div class="form-row">
    <?php partial('field', ['name' => 'status', 'type' => 'select', 'label' => 'Status', 'o' => ['options' => ['draft' => 'Draft (hidden)', 'active' => 'On sale'], 'help' => 'Draft products are not shown to customers.'], 'value' => $v('status', 'draft')]); ?>
    <div class="field"><label for="f_cat">Category (shop section)</label>
      <select id="f_cat" name="category_id"><?php foreach ($allCats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$v('category_id', $defaultCat) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <small class="hint">Or type a new category below. <a href="<?= e(url('/admin/categories')) ?>">Manage categories</a></small></div>
  </div>
  <div class="form-row">
    <?php $F('new_category', 'text', 'New category (optional)', 'Typing a name here creates the category and puts this product in it.', ['max' => 80, 'default' => '']); ?>
  </div>
  <div class="form-row">
    <?php partial('field', ['name' => 'is_featured', 'type' => 'checkbox', 'label' => 'Feature at the top of the shop (“Popular”)', 'o' => [], 'value' => $old !== null ? (isset($old['is_featured']) ? '1' : '0') : (string)($p['is_featured'] ?? '0')]); ?>
    <?php $F('sort_order', 'number', 'Display order', 'Lower numbers first.', ['default' => '0']); ?>
  </div>
  <?php $F('slug', 'text', 'Web address ending', 'Leave empty to create from the name.', ['max' => 150]); ?>

  <?php if ($p): ?>
  <h2>Stock</h2>
  <?php if ($byColour): ?>
    <p class="muted small">Enter how many you have on the shelf. Changes are logged. For shelf locations and re-order levels, use the <a href="<?= e(url('/admin/shop/stock?product=' . $p['id'])) ?>">Stock</a> tab.</p>
    <div class="table-wrap"><table class="table stock-grid">
      <thead><tr><th>Colour</th><?php foreach ($sizesOrder as $s): ?><th><?= e($s) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($byColour as $colour => $row): $first = reset($row); ?>
        <tr><td><?php if ($ptype !== 'simple'): ?><span class="swatch" style="background:<?= e($first['colour_hex']) ?>"></span> <?php endif; ?><strong><?= e($colour) ?></strong></td>
          <?php foreach ($sizesOrder as $s): $x = $row[$s] ?? null; ?><td><?php if ($x): ?><input type="number" name="stock[<?= (int)$x['id'] ?>]" value="<?= (int)$x['stock'] ?>" min="0" max="100000" aria-label="<?= e($colour . ' ' . $s) ?> stock" class="<?= (int)$x['stock'] <= (int)$x['reorder_level'] ? 'low' : '' ?>"><?php else: ?>–<?php endif; ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else: ?><p class="muted">Save the product first to create the stock boxes.</p><?php endif; ?>
  <?php else: ?>
    <p class="muted small">After you save, the stock boxes for each colour and size appear here.</p>
  <?php endif; ?>

  <div class="form-actions"><button class="btn btn-gold" type="submit">Save product</button><a class="btn btn-ghost-dark" href="<?= e(url('/admin/shop/products')) ?>">Cancel</a></div>
</form>
<?php clear_old(); ?>

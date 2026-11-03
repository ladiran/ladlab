<?php
declare(strict_types=1);

/** Products: details, photos, colours/sizes and the stock grid. */
final class AdminProductsController extends AdminBase
{
    public function index(): void
    {
        $rows = Db::all(
            "SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS total_stock,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 AND v.stock <= v.reorder_level) AS low,
                    (SELECT COUNT(*) FROM stock_alerts a WHERE a.product_id = p.id AND a.notified_at IS NULL) AS waiting
             FROM products p ORDER BY p.status = 'active' DESC, p.sort_order, p.id DESC"
        );
        $this->page('admin/products', ['title' => 'Products', 'tab' => 'products', 'rows' => $rows]);
    }

    public function form(?string $id = null): void
    {
        $p = null;
        $variants = $images = [];
        if ($id !== null) {
            $p = Db::one('SELECT * FROM products WHERE id = ?', [(int)$id]);
            if (!$p) {
                abort(404);
            }
            $variants = Db::all('SELECT * FROM product_variants WHERE product_id = ? ORDER BY is_active DESC, id', [$p['id']]);
            $images = Db::all('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$p['id']]);
        }
        $this->page('admin/product_form', ['title' => $p ? 'Edit: ' . $p['name'] : 'Add a product', 'tab' => 'products', 'p' => $p, 'variants' => $variants, 'images' => $images]);
    }

    private static function files(string $key): array
    {
        $out = [];
        $f = $_FILES[$key] ?? null;
        if (!$f || !is_array($f['name'])) {
            return $out;
        }
        foreach ($f['name'] as $i => $name) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }

    public function save(?string $id = null): void
    {
        $this->verify();
        $existing = null;
        if ($id !== null) {
            $existing = Db::one('SELECT * FROM products WHERE id = ?', [(int)$id]);
            if (!$existing) {
                abort(404);
            }
        }
        $in = fn(string $k) => trim((string)($_POST[$k] ?? ''));
        $errors = [];
        $d = [
            'name' => mb_substr($in('name'), 0, 150),
            'product_type' => $in('product_type') === 'simple' ? 'simple' : 'apparel',
            'tagline' => mb_substr($in('tagline'), 0, 200),
            'description' => mb_substr($in('description'), 0, 20000),
            'details' => mb_substr($in('details'), 0, 20000),
            'status' => $in('status') === 'active' ? 'active' : 'draft',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'sort_order' => (int)$in('sort_order'),
            'colours' => mb_substr($in('colours'), 0, 2000),
            'sizes' => mb_substr($in('sizes'), 0, 190),
            'design_text' => mb_substr($in('design_text'), 0, 80),
            'design_style' => in_array($in('design_style'), ['text', 'stripes', 'curves'], true) ? $in('design_style') : 'text',
        ];
        if ($d['name'] === '') {
            $errors[] = 'Please enter the product name.';
        }
        if (!preg_match('/^\d{1,7}$/', $in('price'))) {
            $errors[] = 'Please enter the price as a whole number of Naira (e.g. 6500).';
        }
        $d['price'] = (int)$in('price');
        if ($in('compare_price') !== '' && !preg_match('/^\d{1,7}$/', $in('compare_price'))) {
            $errors[] = 'The “was” price must be a whole number of Naira, or left empty.';
        }
        $d['compare_price'] = $in('compare_price') !== '' ? (int)$in('compare_price') : null;
        if ($d['product_type'] === 'simple') {
            // One-size products (books, mugs, teaching aids): the "options" lines become the variants, with a single hidden size.
            $lines = array_filter(array_map(fn($l) => trim(explode('|', $l)[0]), nl_lines($in('options'))));
            $d['colours'] = implode("\n", array_map(fn($l) => mb_substr($l, 0, 40) . '|#cccccc', $lines ?: ['Standard']));
            $d['sizes'] = 'STD';
        }
        $colours = Shop::parseColours($d['colours']);
        $sizes = Shop::parseSizes($d['sizes']);
        if (!$colours) {
            $errors[] = 'Add at least one colour (one per line, e.g. Navy|#0b1f3a).';
        }
        if (!$sizes) {
            $errors[] = 'Add at least one size (comma-separated, e.g. S,M,L,XL).';
        }
        // Category: a new name typed in the box wins; otherwise the chosen one; otherwise the first category.
        $newCat = mb_substr($in('new_category'), 0, 80);
        $catId = 0;
        if ($newCat !== '') {
            $catId = (int)Db::val('SELECT id FROM shop_categories WHERE LOWER(name) = LOWER(?)', [$newCat]);
            if (!$catId) {
                $cslug = slugify($newCat);
                $cbase = $cslug;
                $ci = 2;
                while (Db::val('SELECT 1 FROM shop_categories WHERE slug = ?', [$cslug])) {
                    $cslug = $cbase . '-' . $ci++;
                }
                $max = (int)Db::val('SELECT COALESCE(MAX(sort_order), 0) FROM shop_categories');
                $catId = Db::insert('shop_categories', ['name' => $newCat, 'slug' => $cslug, 'sort_order' => $max + 10]);
            }
        } else {
            $catId = (int)$in('category_id');
            if (!Db::val('SELECT 1 FROM shop_categories WHERE id = ?', [$catId])) {
                $catId = (int)Db::val('SELECT id FROM shop_categories ORDER BY sort_order, id LIMIT 1');
            }
        }
        if (!$catId) {
            $catId = Db::insert('shop_categories', ['name' => 'Products', 'slug' => 'products', 'sort_order' => 10]);
        }
        $d['category_id'] = $catId;
        $d['category'] = (string)Db::val('SELECT name FROM shop_categories WHERE id = ?', [$catId]);
        if (count($colours) * count($sizes) > 80) {
            $errors[] = 'That is too many colour and size combinations (maximum 80).';
        }
        $slug = slugify($in('slug') !== '' ? $in('slug') : $d['name']);
        $base = $slug;
        $n = 2;
        while (Db::val('SELECT 1 FROM products WHERE slug = ? AND id <> ?', [$slug, $existing['id'] ?? 0])) {
            $slug = $base . '-' . $n++;
        }
        $d['slug'] = $slug;

        $newImages = [];
        foreach (array_slice(self::files('images'), 0, 8) as $f) {
            try {
                $newImages[] = Upload::image($f, 1400);
            } catch (RuntimeException $e) {
                $errors[] = 'Photo "' . $f['name'] . '": ' . $e->getMessage();
            }
        }
        if ($errors) {
            foreach ($newImages as $path) {
                Upload::remove($path);
            }
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect($existing ? 'admin/shop/products/' . $existing['id'] : 'admin/shop/products/new');
        }

        if ($existing) {
            Db::update('products', $d, 'id = ?', [$existing['id']]);
            $pid = (int)$existing['id'];
            if ((int)$existing['is_sample'] === 1) {
                Db::update('products', ['is_sample' => 0], 'id = ?', [$pid]); // editing a placeholder makes it yours
            }
        } else {
            $pid = Db::insert('products', $d + ['is_sample' => 0]);
        }

        // Photos: delete ticked ones, add new, apply "main photo" choice.
        foreach ((array)($_POST['remove_image'] ?? []) as $imgId) {
            $img = Db::one('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int)$imgId, $pid]);
            if ($img) {
                Upload::remove($img['path']);
                Db::delete('product_images', 'id = ?', [$img['id']]);
            }
        }
        $order = (int)Db::val('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?', [$pid]);
        foreach ($newImages as $path) {
            Db::insert('product_images', ['product_id' => $pid, 'path' => $path, 'alt' => $d['name'], 'sort_order' => ++$order]);
        }
        $main = (int)($_POST['main_image'] ?? 0);
        if ($main && Db::val('SELECT 1 FROM product_images WHERE id = ? AND product_id = ?', [$main, $pid])) {
            Db::update('product_images', ['sort_order' => 0], 'id = ?', [$main]);
        }

        Shop::syncVariants($pid);

        // Stock grid (only variants that belong to this product).
        $actor = 'admin';
        foreach ((array)($_POST['stock'] ?? []) as $vid => $qty) {
            if (!is_numeric($qty) || (int)$qty < 0 || (int)$qty > 100000) {
                continue;
            }
            if (Db::val('SELECT 1 FROM product_variants WHERE id = ? AND product_id = ?', [(int)$vid, $pid])) {
                Shop::setStock((int)$vid, (int)$qty, 'Stock edited on product page', $actor);
            }
        }
        Audit::log($existing ? 'product_update' : 'product_create', $d['name']);
        clear_old();
        flash('success', $existing ? 'Product saved.' : 'Product created. Now enter the stock for each colour and size below.');
        redirect('admin/shop/products/' . $pid);
    }

    /** Copies a product (details, colours/sizes, photos left out) as a draft with zero stock, ready to edit. */
    public function duplicate(string $id): void
    {
        $this->verify();
        $p = Db::one('SELECT * FROM products WHERE id = ?', [(int)$id]);
        if (!$p) {
            abort(404);
        }
        unset($p['id'], $p['created_at'], $p['updated_at']);
        $p['name'] = mb_substr($p['name'], 0, 140) . ' (copy)';
        $base = $p['slug'] = slugify($p['name']);
        $n = 2;
        while (Db::val('SELECT 1 FROM products WHERE slug = ?', [$p['slug']])) {
            $p['slug'] = $base . '-' . $n++;
        }
        $p['status'] = 'draft';
        $p['is_featured'] = 0;
        $p['is_sample'] = 0;
        $new = Db::insert('products', $p);
        Shop::syncVariants($new);
        Audit::log('product_duplicate', $p['name']);
        flash('success', 'Copied as a draft. Change the name, photos and stock, then set it to “On sale”.');
        redirect('admin/shop/products/' . $new);
    }

    public function delete(string $id): void
    {
        $this->verify();
        $p = Db::one('SELECT * FROM products WHERE id = ?', [(int)$id]);
        if ($p) {
            foreach (Db::all('SELECT path FROM product_images WHERE product_id = ?', [$p['id']]) as $img) {
                Upload::remove($img['path']);
            }
            Db::delete('products', 'id = ?', [$p['id']]);
            Audit::log('product_delete', $p['name']);
            flash('success', '“' . $p['name'] . '” was deleted. Past orders keep their own record of it.');
        }
        redirect('admin/shop/products');
    }
}

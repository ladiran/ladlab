<?php
declare(strict_types=1);

/** Stock overview: levels, re-order points, shelf locations and the stock movement log. */
final class AdminStockController extends AdminBase
{
    public function index(): void
    {
        $product = (int)($_GET['product'] ?? 0);
        $lowOnly = ($_GET['low'] ?? '') === '1';
        $where = ['v.is_active = 1'];
        $p = [];
        if ($product) {
            $where[] = 'v.product_id = ?';
            $p[] = $product;
        }
        if ($lowOnly) {
            $where[] = 'v.stock <= v.reorder_level';
        }
        $this->page('admin/stock', [
            'title' => 'Stock',
            'tab' => 'stock',
            'product' => $product,
            'lowOnly' => $lowOnly,
            'products' => Db::all('SELECT id, name FROM products ORDER BY name'),
            'rows' => Db::all('SELECT v.*, p.name, p.is_sample FROM product_variants v JOIN products p ON p.id = v.product_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.name, v.colour, v.id LIMIT 500', $p),
            'log' => Db::all('SELECT * FROM stock_log ORDER BY id DESC LIMIT 25'),
        ]);
    }

    public function save(): void
    {
        $this->verify();
        $reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 90) ?: 'Manual stock adjustment';
        $changed = 0;
        foreach ((array)($_POST['v'] ?? []) as $vid => $f) {
            $vid = (int)$vid;
            if (!Db::val('SELECT 1 FROM product_variants WHERE id = ?', [$vid]) || !is_array($f)) {
                continue;
            }
            $stock = isset($f['stock']) && is_numeric($f['stock']) ? max(0, min(100000, (int)$f['stock'])) : null;
            $reorder = isset($f['reorder']) && is_numeric($f['reorder']) ? max(0, min(1000, (int)$f['reorder'])) : null;
            $bin = mb_substr(trim((string)($f['bin'] ?? '')), 0, 40);
            $cur = Db::one('SELECT stock, reorder_level, bin_location FROM product_variants WHERE id = ?', [$vid]);
            if ($stock !== null && $stock !== (int)$cur['stock']) {
                Shop::setStock($vid, $stock, $reason, 'admin');
                $changed++;
            }
            $upd = ['bin_location' => $bin];
            if ($reorder !== null) {
                $upd['reorder_level'] = $reorder;
            }
            Db::update('product_variants', $upd, 'id = ?', [$vid]);
        }
        Audit::log('stock_update', "$changed stock changes");
        flash('success', $changed ? "$changed stock level" . ($changed === 1 ? '' : 's') . ' updated and logged.' : 'Saved.');
        $back = (int)($_POST['product'] ?? 0);
        redirect('admin/shop/stock' . ($back ? '?product=' . $back : ''));
    }

    public function log(): void
    {
        $this->page('admin/stock_log', [
            'title' => 'Stock movements',
            'tab' => 'stock',
            'rows' => Db::all('SELECT l.*, o.order_no FROM stock_log l LEFT JOIN orders o ON o.id = l.order_id ORDER BY l.id DESC LIMIT 300'),
        ]);
    }
}

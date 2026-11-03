<?php
declare(strict_types=1);

/** Session shopping cart: variant id → quantity. Prices and stock are always re-read from the database. */
final class Cart
{
    private static function vt(array $v): string
    {
        $t = Shop::variantText($v['colour'], $v['size']);
        return $t !== '' ? ' (' . $t . ')' : '';
    }

    public const MAX_PER_LINE = 10;

    public static function count(): int
    {
        return array_sum($_SESSION['cart'] ?? []);
    }

    public static function add(int $variantId, int $qty): string
    {
        $v = Db::one(
            "SELECT v.*, p.name, p.status AS pstatus FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?",
            [$variantId]
        );
        if (!$v || !(int)$v['is_active'] || $v['pstatus'] !== 'active') {
            throw new RuntimeException('That item is not available.');
        }
        $have = (int)($_SESSION['cart'][$variantId] ?? 0);
        $qty = max(1, min(self::MAX_PER_LINE, $qty));
        $want = min(self::MAX_PER_LINE, $have + $qty);
        $stock = (int)$v['stock'];
        if ($stock <= 0) {
            throw new RuntimeException('Sorry, ' . $v['name'] . self::vt($v) . ' is sold out.');
        }
        if ($want > $stock) {
            $want = $stock;
            $_SESSION['cart'][$variantId] = $want;
            return 'Only ' . $stock . ' left in stock, so we set the quantity to ' . $stock . '.';
        }
        $_SESSION['cart'][$variantId] = $want;
        return 'Added to your cart.';
    }

    public static function set(int $variantId, int $qty): void
    {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$variantId]);
        } elseif (isset($_SESSION['cart'][$variantId])) {
            $_SESSION['cart'][$variantId] = min(self::MAX_PER_LINE, $qty);
        }
    }

    public static function remove(int $variantId): void
    {
        unset($_SESSION['cart'][$variantId]);
    }

    public static function clear(): void
    {
        unset($_SESSION['cart'], $_SESSION['coupon']);
    }

    /**
     * Cart lines with current price and stock. Lines that are no longer available are dropped and quantities are
     * capped to stock; $notes receives a message for each change.
     * @return array<int, array>
     */
    public static function lines(?array &$notes = null): array
    {
        $notes = [];
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            return [];
        }
        $ids = array_map('intval', array_keys($cart));
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = Db::all(
            "SELECT v.*, p.name, p.slug, p.price, p.status AS pstatus, p.design_text, p.design_style, p.is_sample,
                    (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image
             FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id IN ($in)",
            $ids
        );
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = $r;
        }
        $lines = [];
        foreach ($cart as $vid => $qty) {
            $vid = (int)$vid;
            $v = $byId[$vid] ?? null;
            if (!$v || !(int)$v['is_active'] || $v['pstatus'] !== 'active' || (int)$v['stock'] <= 0) {
                unset($_SESSION['cart'][$vid]);
                $notes[] = ($v ? $v['name'] . (Shop::variantText($v['colour'], $v['size']) !== '' ? ' (' . Shop::variantText($v['colour'], $v['size']) . ')' : '') : 'An item') . ' is no longer available and was removed from your cart.';
                continue;
            }
            $q = min((int)$qty, (int)$v['stock'], self::MAX_PER_LINE);
            if ($q < (int)$qty) {
                $_SESSION['cart'][$vid] = $q;
                $notes[] = 'Only ' . $v['stock'] . ' of ' . $v['name'] . self::vt($v) . ' left: quantity reduced.';
            }
            $lines[] = [
                'variant_id' => $vid, 'product_id' => (int)$v['product_id'], 'name' => $v['name'], 'slug' => $v['slug'], 'colour' => $v['colour'],
                'hex' => $v['colour_hex'], 'size' => $v['size'], 'sku' => $v['sku'], 'price' => (int)$v['price'], 'qty' => $q, 'stock' => (int)$v['stock'],
                'line_total' => (int)$v['price'] * $q, 'image' => $v['image'], 'design_text' => $v['design_text'], 'design_style' => $v['design_style'],
            ];
        }
        return $lines;
    }
}

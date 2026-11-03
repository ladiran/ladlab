<?php
declare(strict_types=1);

/** Shop logic: catalogue queries, totals, order placement, order status changes, stock and customer emails. */
final class Shop
{
    public const STATUS = [
        'awaiting_payment' => 'Awaiting payment', 'paid' => 'Paid: to pack', 'packed' => 'Packed', 'shipped' => 'Shipped',
        'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'expired' => 'Expired',
    ];
    public const LOW_STOCK_BADGE = 8;

    public static function statusLabel(array $order): string
    {
        if ($order['delivery_method'] === 'pickup') {
            if ($order['status'] === 'packed') {
                return 'Ready for pickup';
            }
            if ($order['status'] === 'delivered') {
                return 'Collected';
            }
        }
        return self::STATUS[$order['status']] ?? $order['status'];
    }

    /* ------------------------------------------------------------------ catalogue */

    /** Sample products show only while there is no real (on-sale) product. */
    public static function sampleFlag(): int
    {
        return Db::val("SELECT 1 FROM products WHERE is_sample = 0 AND status = 'active' LIMIT 1") ? 0 : 1;
    }

    public static function products(array $f = [], int $limit = 0): array
    {
        $where = ["p.status = 'active'", 'p.is_sample = ?'];
        $params = [self::sampleFlag()];
        if (!empty($f['q'])) {
            $where[] = '(p.name LIKE ? OR p.tagline LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$f['q']) . '%';
            array_push($params, $like, $like);
        }
        if (!empty($f['category'])) {
            $where[] = 'p.category_id = (SELECT id FROM shop_categories WHERE slug = ?)';
            $params[] = $f['category'];
        }
        $order = match ($f['sort'] ?? '') {
            'price_asc' => 'p.price ASC, p.id DESC',
            'price_desc' => 'p.price DESC, p.id DESC',
            'newest' => 'p.id DESC',
            default => 'p.is_featured DESC, p.sort_order ASC, p.id DESC',
        };
        $rows = Db::all(
            "SELECT p.*,
               (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image,
               (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS total_stock
             FROM products p WHERE " . implode(' AND ', $where) . " ORDER BY $order" . ($limit ? ' LIMIT ' . (int)$limit : ''),
            $params
        );
        foreach ($rows as &$r) {
            $r['swatches'] = Db::all('SELECT colour, colour_hex FROM product_variants WHERE product_id = ? AND is_active = 1 AND stock > 0 GROUP BY colour, colour_hex ORDER BY MIN(id)', [$r['id']]);
        }
        return $rows;
    }

    /** Visible categories that have at least one product on sale (name, slug, description, image, items). */
    public static function categories(): array
    {
        return Db::all(
            "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active' AND p.is_sample = ?) AS items
             FROM shop_categories c WHERE c.is_visible = 1
             HAVING items > 0 ORDER BY c.sort_order, c.name",
            [self::sampleFlag()]
        );
    }

    public static function category(string $slug): ?array
    {
        return Db::one('SELECT * FROM shop_categories WHERE slug = ? AND is_visible = 1', [$slug]);
    }

    /** Text under a product name in carts and orders: "Navy · Size M", or just the option for one-size products. */
    public static function variantText(string $colour, string $size): string
    {
        if ($size === 'STD') {
            return $colour === 'Standard' ? '' : $colour;
        }
        return $colour . ' · Size ' . $size;
    }

    /** Emails everyone waiting for this product the first time it is back in stock. */
    public static function notifyBackInStock(int $productId): void
    {
        $p = Db::one("SELECT name, slug FROM products WHERE id = ? AND status = 'active'", [$productId]);
        if (!$p) {
            return;
        }
        $site = Settings::get('site_name');
        foreach (Db::all('SELECT id, email FROM stock_alerts WHERE product_id = ? AND notified_at IS NULL', [$productId]) as $a) {
            Mailer::send($a['email'], "[$site] Back in stock: {$p['name']}", "Good news! \"{$p['name']}\" is available again:
" . abs_url('shop/' . $p['slug']) . "

Stock is limited, so order soon.

$site
(You asked us to tell you. This is the only email we will send about it.)");
            Db::update('stock_alerts', ['notified_at' => date('Y-m-d H:i:s')], 'id = ?', [$a['id']]);
        }
    }

    public static function product(string $slug): ?array
    {
        $p = Db::one("SELECT * FROM products WHERE slug = ? AND status = 'active'", [$slug]);
        if (!$p) {
            return null;
        }
        $p['images'] = Db::all('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$p['id']]);
        $p['variants'] = Db::all('SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY id', [$p['id']]);
        return $p;
    }

    public static function naira(int|string|null $amount): string
    {
        return '₦' . number_format((int)$amount);
    }

    /* ------------------------------------------------------------------ colours, sizes and variants */

    /** @return array<int, array{name:string,hex:string}> */
    public static function parseColours(string $text): array
    {
        $out = [];
        foreach (nl_lines($text) as $line) {
            [$name, $hex] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $name = mb_substr($name, 0, 40);
            if ($name === '' || isset($out[mb_strtolower($name)])) {
                continue;
            }
            $out[mb_strtolower($name)] = ['name' => $name, 'hex' => preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? strtolower($hex) : '#cccccc'];
        }
        return array_values($out);
    }

    /** @return string[] */
    public static function parseSizes(string $text): array
    {
        $out = [];
        foreach (explode(',', $text) as $s) {
            $s = strtoupper(trim($s));
            if ($s !== '' && preg_match('/^[A-Z0-9.\/\-]{1,8}$/', $s)) {
                $out[$s] = $s;
            }
        }
        return array_slice(array_values($out), 0, 12);
    }

    /** Makes the variant rows match the product's colour and size lists. Existing stock is kept; removed combinations are switched off. */
    public static function syncVariants(int $productId): void
    {
        $p = Db::one('SELECT * FROM products WHERE id = ?', [$productId]);
        if (!$p) {
            return;
        }
        $colours = self::parseColours((string)$p['colours']);
        $sizes = self::parseSizes((string)$p['sizes']);
        $want = [];
        foreach ($colours as $c) {
            foreach ($sizes as $s) {
                $want[mb_strtolower($c['name']) . '|' . $s] = [$c, $s];
            }
        }
        $have = [];
        foreach (Db::all('SELECT * FROM product_variants WHERE product_id = ?', [$productId]) as $v) {
            $have[mb_strtolower($v['colour']) . '|' . $v['size']] = $v;
        }
        foreach ($want as $key => [$c, $s]) {
            if (isset($have[$key])) {
                Db::update('product_variants', ['is_active' => 1, 'colour' => $c['name'], 'colour_hex' => $c['hex']], 'id = ?', [$have[$key]['id']]);
                continue;
            }
            $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', explode('-', (string)$p['slug'])[0]), 0, 10)) . '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $c['name']), 0, 6)) . '-' . $s;
            $sku = 'P' . $productId . '-' . $base;
            Db::insert('product_variants', ['product_id' => $productId, 'colour' => $c['name'], 'colour_hex' => $c['hex'], 'size' => $s, 'sku' => $sku, 'stock' => 0]);
        }
        foreach ($have as $key => $v) {
            if (!isset($want[$key]) && (int)$v['is_active'] === 1) {
                Db::update('product_variants', ['is_active' => 0], 'id = ?', [$v['id']]);
            }
        }
    }

    /* ------------------------------------------------------------------ delivery and coupons */

    public static function shippingFee(string $state, int $netSubtotal): int
    {
        $free = (int)Settings::get('free_shipping_over');
        if ($free > 0 && $netSubtotal >= $free) {
            return 0;
        }
        if ($state !== '') {
            $fee = Db::val('SELECT fee FROM shipping_rates WHERE state = ?', [$state]);
            if ($fee !== null) {
                return (int)$fee;
            }
        }
        return (int)Settings::get('default_shipping_fee');
    }

    /** @return array{0:?array,1:string} [coupon row or null, error message] */
    public static function coupon(string $code, int $subtotal): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return [null, ''];
        }
        $c = Db::one('SELECT * FROM coupons WHERE code = ?', [$code]);
        if (!$c || !(int)$c['is_active']) {
            return [null, 'That coupon code is not valid.'];
        }
        if ($c['expires_on'] && $c['expires_on'] < date('Y-m-d')) {
            return [null, 'That coupon has expired.'];
        }
        if ((int)$c['max_uses'] > 0 && (int)$c['used_count'] >= (int)$c['max_uses']) {
            return [null, 'That coupon has been fully used.'];
        }
        if ($subtotal < (int)$c['min_order']) {
            return [null, 'This coupon needs an order of at least ' . self::naira($c['min_order']) . '.'];
        }
        return [$c, ''];
    }

    public static function discountFor(array $coupon, int $subtotal): int
    {
        $d = $coupon['type'] === 'percent' ? intdiv($subtotal * min(100, (int)$coupon['value']), 100) : (int)$coupon['value'];
        return max(0, min($d, $subtotal));
    }

    /** @return array{subtotal:int,discount:int,shipping:int,total:int,coupon:?array,coupon_error:string} */
    public static function quote(array $lines, string $couponCode, string $method, string $state): array
    {
        $subtotal = array_sum(array_column($lines, 'line_total'));
        [$coupon, $err] = self::coupon($couponCode, $subtotal);
        $discount = $coupon ? self::discountFor($coupon, $subtotal) : 0;
        $shipping = $method === 'pickup' ? 0 : self::shippingFee($state, $subtotal - $discount);
        return ['subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping, 'total' => $subtotal - $discount + $shipping, 'coupon' => $coupon, 'coupon_error' => $err];
    }

    /* ------------------------------------------------------------------ stock */

    /** Changes stock by a delta (never below zero) and records it. Returns the new stock level. */
    public static function addStock(int $variantId, int $delta, string $reason, ?int $orderId = null, string $actor = 'system'): int
    {
        $pid = (int)Db::val('SELECT product_id FROM product_variants WHERE id = ?', [$variantId]);
        $before = $delta > 0 ? (int)Db::val('SELECT COALESCE(SUM(stock), 0) FROM product_variants WHERE product_id = ? AND is_active = 1', [$pid]) : 1;
        Db::run('UPDATE product_variants SET stock = GREATEST(0, stock + ?) WHERE id = ?', [$delta, $variantId]);
        $v = Db::one('SELECT v.stock, v.sku, v.colour, v.size, p.name FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?', [$variantId]);
        if (!$v) {
            return 0;
        }
        Db::insert('stock_log', [
            'variant_id' => $variantId, 'label' => mb_substr($v['name'] . ' · ' . $v['colour'] . ' · ' . $v['size'], 0, 155), 'change_qty' => $delta,
            'stock_after' => (int)$v['stock'], 'reason' => mb_substr($reason, 0, 95), 'order_id' => $orderId, 'actor' => $actor,
        ]);
        if ($delta > 0 && $before <= 0) {
            try {
                self::notifyBackInStock($pid);
            } catch (Throwable) {
                // alerts table not installed yet
            }
        }
        return (int)$v['stock'];
    }

    public static function setStock(int $variantId, int $qty, string $reason, string $actor): void
    {
        $cur = Db::val('SELECT stock FROM product_variants WHERE id = ?', [$variantId]);
        if ($cur === null || (int)$cur === $qty) {
            return;
        }
        self::addStock($variantId, $qty - (int)$cur, $reason, null, $actor);
    }

    public static function lowStock(): array
    {
        return Db::all(
            "SELECT v.*, p.name, p.slug FROM product_variants v JOIN products p ON p.id = v.product_id
             WHERE v.is_active = 1 AND p.status = 'active' AND p.is_sample = ? AND v.stock <= v.reorder_level ORDER BY v.stock, p.name",
            [self::sampleFlag()]
        );
    }

    /* ------------------------------------------------------------------ orders */

    public static function event(int $orderId, string $event, string $detail = '', string $actor = 'system'): void
    {
        Db::insert('order_events', ['order_id' => $orderId, 'event' => $event, 'detail' => mb_substr($detail, 0, 395), 'actor' => $actor]);
    }

    public static function order(int $id): ?array
    {
        return Db::one('SELECT * FROM orders WHERE id = ?', [$id]);
    }

    public static function items(int $orderId): array
    {
        return Db::all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    public static function orderUrl(array $o): string
    {
        return abs_url('order/' . $o['order_no'] . '/' . $o['access_token']);
    }

    /**
     * Creates an order from the cart lines in one transaction: stock is re-checked under lock, reserved, and prices are
     * taken from the database (never from the browser). Throws RuntimeException with a customer-friendly message.
     */
    public static function placeOrder(array $in, array $lines, string $couponCode, ?int $userId): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $items = [];
            $subtotal = 0;
            foreach ($lines as $l) {
                $v = Db::one(
                    "SELECT v.*, p.name AS pname, p.price, p.status AS pstatus FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id = ? FOR UPDATE",
                    [$l['variant_id']]
                );
                $vt = self::variantText($l['colour'], $l['size']);
                $label = $l['name'] . ($vt !== '' ? ' (' . $vt . ')' : '');
                if (!$v || !(int)$v['is_active'] || $v['pstatus'] !== 'active') {
                    throw new RuntimeException("$label is no longer available. Please remove it from your cart.");
                }
                if ((int)$v['stock'] < (int)$l['qty']) {
                    throw new RuntimeException((int)$v['stock'] > 0
                        ? "Only {$v['stock']} of $label left in stock. Please reduce the quantity."
                        : "$label has just sold out. Please remove it from your cart.");
                }
                $line = (int)$v['price'] * (int)$l['qty'];
                $subtotal += $line;
                $items[] = ['v' => $v, 'qty' => (int)$l['qty'], 'line' => $line];
            }

            $coupon = null;
            if ($couponCode !== '') {
                $c = Db::one('SELECT * FROM coupons WHERE code = ? FOR UPDATE', [strtoupper($couponCode)]);
                [$coupon, $err] = $c ? self::coupon($c['code'], $subtotal) : [null, 'That coupon code is not valid.'];
                if (!$coupon) {
                    throw new RuntimeException($err ?: 'That coupon code is not valid.');
                }
            }
            $discount = $coupon ? self::discountFor($coupon, $subtotal) : 0;
            $method = $in['delivery_method'] === 'pickup' ? 'pickup' : 'ship';
            $shipping = $method === 'pickup' ? 0 : self::shippingFee($in['state'], $subtotal - $discount);
            $hold = max(1, (int)Settings::get('order_hold_hours'));

            $orderId = Db::insert('orders', [
                'access_token' => bin2hex(random_bytes(16)), 'user_id' => $userId, 'email' => $in['email'], 'name' => $in['name'], 'phone' => $in['phone'],
                'delivery_method' => $method, 'address_line' => $method === 'ship' ? $in['address_line'] : '', 'city' => $method === 'ship' ? $in['city'] : '',
                'state' => $method === 'ship' ? $in['state'] : '', 'subtotal' => $subtotal, 'discount' => $discount, 'shipping_fee' => $shipping,
                'total' => $subtotal - $discount + $shipping, 'coupon_code' => $coupon ? $coupon['code'] : '', 'status' => 'awaiting_payment',
                'expires_at' => date('Y-m-d H:i:s', time() + $hold * 3600), 'customer_note' => $in['customer_note'], 'ip' => client_ip(),
            ]);
            $prefix = preg_replace('/[^A-Za-z]/', '', Settings::get('order_prefix')) ?: 'CSA';
            $orderNo = strtoupper(substr($prefix, 0, 6)) . '-' . (1000 + $orderId);
            Db::update('orders', ['order_no' => $orderNo], 'id = ?', [$orderId]);

            foreach ($items as $it) {
                $v = $it['v'];
                Db::insert('order_items', [
                    'order_id' => $orderId, 'product_id' => $v['product_id'], 'variant_id' => $v['id'], 'name' => $v['pname'], 'size' => $v['size'],
                    'colour' => $v['colour'], 'sku' => $v['sku'], 'bin_location' => $v['bin_location'], 'unit_price' => $v['price'], 'qty' => $it['qty'], 'line_total' => $it['line'],
                ]);
                self::addStock((int)$v['id'], -$it['qty'], 'Reserved for order ' . $orderNo, $orderId, 'customer');
            }
            if ($coupon) {
                Db::run('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$coupon['id']]);
            }
            self::event($orderId, 'placed', 'Order placed. Waiting for bank transfer of ' . self::naira($subtotal - $discount + $shipping) . '.', 'customer');
            $pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e instanceof RuntimeException ? $e : new RuntimeException('Sorry, your order could not be placed. Please try again.', 0, $e);
        }
    }

    /**
     * Status changes. $action: confirm_payment | pack | ship | deliver | cancel | expire.
     * Throws RuntimeException if the order is not in a state that allows the action.
     */
    public static function act(int $orderId, string $action, array $in = [], string $actor = 'admin'): string
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $o = Db::one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$o) {
                throw new RuntimeException('Order not found.');
            }
            $s = $o['status'];
            $now = date('Y-m-d H:i:s');
            $msg = '';
            switch ($action) {
                case 'confirm_payment':
                    self::need($s === 'awaiting_payment', 'Only orders awaiting payment can be confirmed as paid.');
                    Db::update('orders', ['status' => 'paid', 'paid_at' => $now, 'expires_at' => null], 'id = ?', [$orderId]);
                    self::event($orderId, 'paid', 'Payment confirmed.' . (($in['note'] ?? '') !== '' ? ' ' . $in['note'] : ''), $actor);
                    $kind = 'paid';
                    $msg = 'Payment confirmed. The order is ready to pack.';
                    break;
                case 'pack':
                    self::need($s === 'paid', 'Only paid orders can be marked as packed.');
                    Db::update('orders', ['status' => 'packed', 'packed_at' => $now], 'id = ?', [$orderId]);
                    self::event($orderId, 'packed', $o['delivery_method'] === 'pickup' ? 'Packed and ready for pickup.' : 'Packed.', $actor);
                    $kind = $o['delivery_method'] === 'pickup' ? 'ready' : null;
                    $msg = 'Marked as packed.';
                    break;
                case 'ship':
                    self::need($o['delivery_method'] === 'ship' && in_array($s, ['paid', 'packed'], true), 'Only paid or packed delivery orders can be marked as shipped.');
                    $courier = mb_substr(trim((string)($in['courier'] ?? '')), 0, 60);
                    $tracking = mb_substr(trim((string)($in['tracking_no'] ?? '')), 0, 80);
                    Db::update('orders', ['status' => 'shipped', 'shipped_at' => $now, 'packed_at' => $o['packed_at'] ?: $now, 'courier' => $courier, 'tracking_no' => $tracking], 'id = ?', [$orderId]);
                    self::event($orderId, 'shipped', 'Shipped' . ($courier !== '' ? " with $courier" : '') . ($tracking !== '' ? ", tracking $tracking" : '') . '.', $actor);
                    $kind = 'shipped';
                    $msg = 'Marked as shipped. The customer has been emailed.';
                    break;
                case 'deliver':
                    self::need(($o['delivery_method'] === 'ship' && $s === 'shipped') || ($o['delivery_method'] === 'pickup' && in_array($s, ['packed', 'paid'], true)), 'This order cannot be marked as delivered yet.');
                    Db::update('orders', ['status' => 'delivered', 'delivered_at' => $now], 'id = ?', [$orderId]);
                    self::event($orderId, 'delivered', $o['delivery_method'] === 'pickup' ? 'Collected by the customer.' : 'Delivered.', $actor);
                    $kind = 'delivered';
                    $msg = 'Marked as delivered.';
                    break;
                case 'cancel':
                case 'expire':
                    self::need(in_array($s, $action === 'expire' ? ['awaiting_payment'] : ['awaiting_payment', 'paid', 'packed'], true), 'This order can no longer be cancelled.');
                    foreach (self::items($orderId) as $it) {
                        if ($it['variant_id']) {
                            self::addStock((int)$it['variant_id'], (int)$it['qty'], ($action === 'expire' ? 'Order expired: ' : 'Order cancelled: ') . $o['order_no'], $orderId, $actor);
                        }
                    }
                    if ($o['coupon_code'] !== '') {
                        Db::run('UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE code = ?', [$o['coupon_code']]);
                    }
                    $status = $action === 'expire' ? 'expired' : 'cancelled';
                    Db::update('orders', ['status' => $status, 'cancelled_at' => $now, 'expires_at' => null], 'id = ?', [$orderId]);
                    $wasPaid = in_array($s, ['paid', 'packed'], true);
                    self::event($orderId, $status, ($action === 'expire' ? 'Not paid in time: released the reserved items.' : 'Order cancelled.') . ($wasPaid ? ' Payment had been received: refund by bank transfer is needed.' : '') . (($in['note'] ?? '') !== '' ? ' ' . $in['note'] : ''), $actor);
                    $kind = $status;
                    $msg = $wasPaid ? 'Order cancelled and stock returned. Remember to refund the customer by bank transfer.' : 'Order cancelled and stock returned.';
                    break;
                default:
                    throw new RuntimeException('Unknown action.');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        if (!empty($kind)) {
            self::mail(self::order($orderId), $kind);
        }
        return $msg;
    }

    private static function need(bool $cond, string $message): void
    {
        if (!$cond) {
            throw new RuntimeException($message);
        }
    }

    /** Releases the stock of unpaid orders whose hold time ended (skips orders where the customer already uploaded a receipt). */
    public static function expireUnpaid(bool $force = false): int
    {
        $last = Settings::get('shop_last_cleanup');
        if (!$force && $last !== '' && strtotime($last) > time() - 300) {
            return 0;
        }
        Settings::set('shop_last_cleanup', date('Y-m-d H:i:s'));
        $n = 0;
        foreach (Db::all("SELECT id FROM orders WHERE status = 'awaiting_payment' AND receipt_path = '' AND expires_at IS NOT NULL AND expires_at < NOW()") as $r) {
            try {
                self::act((int)$r['id'], 'expire', [], 'system');
                $n++;
            } catch (RuntimeException) {
                // changed by someone else meanwhile
            }
        }
        return $n;
    }

    /* ------------------------------------------------------------------ emails */

    public static function bankBlock(array $o): string
    {
        return "Bank: " . Settings::get('bank_name') . "\nAccount name: " . Settings::get('bank_account_name') . "\nAccount number: " . Settings::get('bank_account_number')
            . "\nAmount: " . self::naira($o['total']) . "\nReference / narration: " . $o['order_no'];
    }

    private static function itemsBlock(array $o): string
    {
        $lines = [];
        foreach (self::items((int)$o['id']) as $i) {
            $lines[] = "  {$i['qty']} × {$i['name']}" . (self::variantText($i['colour'], $i['size']) !== '' ? ' (' . self::variantText($i['colour'], $i['size']) . ')' : '') . ': ' . self::naira($i['line_total']);
        }
        $lines[] = '  Delivery: ' . self::naira($o['shipping_fee']) . ($o['discount'] > 0 ? '   Discount: -' . self::naira($o['discount']) : '') . '   TOTAL: ' . self::naira($o['total']);
        return implode("\n", $lines);
    }

    public static function mail(array $o, string $kind): void
    {
        $site = Settings::get('site_name');
        $link = self::orderUrl($o);
        $hi = 'Hello ' . $o['name'] . ',';
        $items = self::itemsBlock($o);
        $bodies = [
            'placed' => ["Order {$o['order_no']} received", "$hi\n\nThank you for your order {$o['order_no']}.\n\n$items\n\nPLEASE PAY BY BANK TRANSFER:\n" . self::bankBlock($o) . "\n\nYour items are reserved until " . ($o['expires_at'] ? date('j M Y, H:i', strtotime((string)$o['expires_at'])) : 'further notice') . ". After you pay, you can upload your receipt on your order page to speed things up:\n$link\n\nWe confirm your order once the money reaches our account."],
            'paid' => ["Payment received for order {$o['order_no']}", "$hi\n\nWe have received your payment for order {$o['order_no']}. Thank you!\n\n" . ($o['delivery_method'] === 'pickup' ? "We will email you when it is ready for pickup." : 'We are packing it now (' . Settings::get('dispatch_text') . ").") . "\n\nTrack your order: $link"],
            'ready' => ["Order {$o['order_no']} is ready for pickup", "$hi\n\nYour order is packed and ready for pickup.\n\n" . Settings::get('pickup_address') . "\n\nOrder page: $link"],
            'shipped' => ["Order {$o['order_no']} has shipped", "$hi\n\nYour order has been handed to the courier" . ($o['courier'] !== '' ? " ({$o['courier']})" : '') . ".\n" . ($o['tracking_no'] !== '' ? "Tracking number: {$o['tracking_no']}\n" : '') . "\nDelivering to: {$o['address_line']}, {$o['city']}, {$o['state']}\n\nOrder page: $link"],
            'delivered' => ["Order {$o['order_no']}: " . ($o['delivery_method'] === 'pickup' ? 'collected' : 'delivered'), "$hi\n\nYour order {$o['order_no']} is marked as " . ($o['delivery_method'] === 'pickup' ? 'collected' : 'delivered') . ". We hope you love it! If anything is wrong, reply to this email or contact us within 7 days.\n\nThank you for supporting $site."],
            'cancelled' => ["Order {$o['order_no']} cancelled", "$hi\n\nYour order {$o['order_no']} has been cancelled.\n\nIf you had already paid, we will refund you by bank transfer; please reply with your account details if we do not contact you shortly.\n\nOrder page: $link"],
            'expired' => ["Order {$o['order_no']} expired", "$hi\n\nWe did not receive payment for order {$o['order_no']} in time, so the reserved items were released.\n\nIf you still want them, simply place a new order: " . abs_url('shop')],
        ];
        if (isset($bodies[$kind])) {
            Mailer::send($o['email'], '[' . $site . '] ' . $bodies[$kind][0], $bodies[$kind][1]);
        }
    }

    public static function mailAdmin(string $subject, string $body): void
    {
        $to = Settings::get('notify_email') ?: Settings::get('contact_email');
        if ($to) {
            Mailer::send($to, '[' . Settings::get('site_name') . '] ' . $subject, $body);
        }
    }
}

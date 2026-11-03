<?php
declare(strict_types=1);

final class ShopController
{
    private function guard(): void
    {
        if (!module_enabled('shop')) {
            (new SiteController())->soon();
            exit;
        }
    }

    private static function intOr(mixed $v, int $d = 0): int
    {
        return is_numeric($v) ? (int)$v : $d;
    }

    /* ------------------------------------------------------------------ catalogue */

    public function index(): void
    {
        $this->guard();
        Shop::expireUnpaid();
        $this->listing(trim((string)($_GET['category'] ?? '')));
    }

    public function category(string $slug): void
    {
        $this->guard();
        Shop::expireUnpaid();
        $this->listing($slug, true);
    }

    private function listing(string $catSlug, bool $page = false): void
    {
        $f = [
            'q' => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60),
            'sort' => in_array($_GET['sort'] ?? '', ['price_asc', 'price_desc', 'newest'], true) ? $_GET['sort'] : '',
            'category' => '',
        ];
        $cats = Shop::categories();
        $current = null;
        foreach ($cats as $c) {
            if ($c['slug'] === $catSlug) {
                $current = $c;
            }
        }
        if ($page && !$current) {
            abort(404);
        }
        $f['category'] = $current['slug'] ?? '';
        view('public/shop', [
            'title' => $current ? $current['name'] . ' | Shop' : 'Shop',
            'meta_desc' => $current && $current['description'] !== '' ? $current['description'] : Settings::get('shop_intro'),
            'products' => Shop::products($f),
            'filters' => $f,
            'categories' => $cats,
            'current' => $current,
        ]);
    }

    public function notify(string $slug): void
    {
        $this->guard();
        csrf_check();
        $p = Db::one("SELECT id, name FROM products WHERE slug = ? AND status = 'active'", [$slug]);
        if (!$p) {
            abort(404);
        }
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        if (!form_human('stockalert') || !rate_ok('stockalert', 6, 3600)) {
            flash('error', 'Please wait a moment and try again.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            flash('error', 'Please enter a valid email address.');
        } else {
            Db::run('INSERT IGNORE INTO stock_alerts (product_id, email) VALUES (?, ?)', [$p['id'], $email]);
            Db::run('UPDATE stock_alerts SET notified_at = NULL WHERE product_id = ? AND email = ?', [$p['id'], $email]);
            flash('success', 'Done. We will email you once, when it is back in stock.');
        }
        redirect('shop/' . $slug);
    }

    public function product(string $slug): void
    {
        $this->guard();
        $p = Shop::product($slug);
        if (!$p || (int)$p['is_sample'] !== Shop::sampleFlag()) {
            abort(404);
        }
        $map = [];
        foreach ($p['variants'] as $v) {
            $map[$v['colour'] . '|' . $v['size']] = (int)$v['stock'];
        }
        view('public/product', [
            'title' => $p['name'],
            'meta_desc' => $p['tagline'] ?: excerpt((string)$p['description'], 155),
            'og_image' => $p['images'] ? abs_url($p['images'][0]['path']) : '',
            'og_type' => 'product', 'noindex' => (bool)$p['is_sample'], 'jsonld' => [Seo::product($p)],
            'p' => $p,
            'colours' => Shop::parseColours((string)$p['colours']),
            'sizes' => Shop::parseSizes((string)$p['sizes']),
            'stockMap' => $map,
            'related' => array_slice(array_values(array_filter(Shop::products($p['category_id'] ? ['category' => (string)Db::val('SELECT slug FROM shop_categories WHERE id = ?', [$p['category_id']])] : [], 8), fn($r) => (int)$r['id'] !== (int)$p['id'])), 0, 3),
        ]);
    }

    /* ------------------------------------------------------------------ cart */

    public function cart(): void
    {
        $this->guard();
        $lines = Cart::lines($notes);
        foreach ($notes as $n) {
            flash('info', $n);
        }
        $code = (string)($_SESSION['coupon'] ?? '');
        $q = Shop::quote($lines, $code, 'ship', '');
        if ($code !== '' && !$q['coupon']) {
            unset($_SESSION['coupon']);
            flash('info', $q['coupon_error']);
            $q = Shop::quote($lines, '', 'ship', '');
        }
        view('public/cart', ['title' => 'Your cart', 'lines' => $lines, 'q' => $q, 'coupon' => $q['coupon']]);
    }

    public function cartAdd(): void
    {
        $this->guard();
        csrf_check();
        $pid = self::intOr($_POST['product_id'] ?? 0);
        $colour = (string)($_POST['colour'] ?? '');
        $size = (string)($_POST['size'] ?? '');
        $back = Db::val('SELECT slug FROM products WHERE id = ?', [$pid]);
        $back = $back ? 'shop/' . $back : 'shop';
        if ($colour === '' || $size === '') {
            flash('error', 'Please choose a colour and a size.');
            redirect($back);
        }
        $v = Db::one('SELECT id FROM product_variants WHERE product_id = ? AND colour = ? AND size = ? AND is_active = 1', [$pid, $colour, $size]);
        if (!$v) {
            flash('error', 'That colour and size combination is not available.');
            redirect($back);
        }
        try {
            $msg = Cart::add((int)$v['id'], self::intOr($_POST['qty'] ?? 1, 1));
            flash('success', $msg);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect($back);
    }

    public function cartUpdate(): void
    {
        $this->guard();
        csrf_check();
        foreach (($_POST['qty'] ?? []) as $vid => $qty) {
            Cart::set((int)$vid, self::intOr($qty));
        }
        flash('success', 'Cart updated.');
        redirect('cart');
    }

    public function cartRemove(): void
    {
        $this->guard();
        csrf_check();
        Cart::remove(self::intOr($_POST['variant_id'] ?? 0));
        redirect('cart');
    }

    public function couponApply(): void
    {
        $this->guard();
        csrf_check();
        $code = strtoupper(mb_substr(trim((string)($_POST['coupon'] ?? '')), 0, 40));
        $lines = Cart::lines();
        [$c, $err] = Shop::coupon($code, array_sum(array_column($lines, 'line_total')));
        if ($c) {
            $_SESSION['coupon'] = $c['code'];
            flash('success', 'Coupon ' . $c['code'] . ' applied.');
        } else {
            flash('error', $err ?: 'Please enter a coupon code.');
        }
        redirect(($_POST['return'] ?? '') === 'checkout' ? 'checkout' : 'cart');
    }

    public function couponRemove(): void
    {
        $this->guard();
        csrf_check();
        unset($_SESSION['coupon']);
        redirect(($_POST['return'] ?? '') === 'checkout' ? 'checkout' : 'cart');
    }

    /* ------------------------------------------------------------------ checkout */

    public function checkout(): void
    {
        $this->guard();
        Shop::expireUnpaid();
        $lines = Cart::lines($notes);
        foreach ($notes as $n) {
            flash('info', $n);
        }
        if (!$lines) {
            flash('info', 'Your cart is empty.');
            redirect('shop');
        }
        $u = Auth::user();
        $v = fn(string $k, string $fallback = '') => (string)old($k, $fallback);
        $method = $v('delivery_method', 'ship') === 'pickup' && Settings::get('pickup_enabled') === '1' ? 'pickup' : 'ship';
        $state = $v('state', $u['state'] ?? '');
        $code = (string)($_SESSION['coupon'] ?? '');
        $q = Shop::quote($lines, $code, $method, $state);
        if ($code !== '' && !$q['coupon']) {
            unset($_SESSION['coupon']);
            flash('info', $q['coupon_error']);
            $q = Shop::quote($lines, '', $method, $state);
        }
        form_started('checkout');
        $rates = [];
        foreach (Db::all('SELECT state, fee FROM shipping_rates') as $r) {
            $rates[$r['state']] = (int)$r['fee'];
        }
        view('public/checkout', [
            'title' => 'Checkout',
            'lines' => $lines, 'q' => $q, 'coupon' => $q['coupon'], 'user' => $u, 'method' => $method, 'rates' => $rates,
            'val' => [
                'name' => $v('name', $u['name'] ?? ''), 'email' => $v('email', $u['email'] ?? ''), 'phone' => $v('phone', $u['phone'] ?? ''),
                'address_line' => $v('address_line', $u['address_line'] ?? ''), 'city' => $v('city', $u['city'] ?? ''), 'state' => $state,
                'customer_note' => $v('customer_note'),
            ],
        ]);
    }

    public function place(): void
    {
        $this->guard();
        csrf_check();
        if (!form_human('checkout')) {
            flash('error', 'Please wait a moment and press “Place order” again.');
            redirect('checkout');
        }
        if (!rate_ok('checkout', 10, 3600)) {
            abort(429, 'Too many orders from this connection. Please try again later.');
        }
        $lines = Cart::lines($notes);
        if (!$lines) {
            redirect('cart');
        }
        $in = [
            'name' => mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150),
            'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'delivery_method' => ($_POST['delivery_method'] ?? 'ship') === 'pickup' ? 'pickup' : 'ship',
            'address_line' => mb_substr(trim((string)($_POST['address_line'] ?? '')), 0, 200),
            'city' => mb_substr(trim((string)($_POST['city'] ?? '')), 0, 100),
            'state' => (string)($_POST['state'] ?? ''),
            'customer_note' => mb_substr(trim((string)($_POST['customer_note'] ?? '')), 0, 500),
        ];
        $errors = [];
        if ($in['delivery_method'] === 'pickup' && Settings::get('pickup_enabled') !== '1') {
            $in['delivery_method'] = 'ship';
        }
        if ($in['name'] === '') {
            $errors[] = 'Please enter your name.';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || strlen($in['email']) > 190) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!preg_match('/^[0-9+()\-\s]{7,40}$/', $in['phone'])) {
            $errors[] = 'Please enter a phone number we can reach you on (for the courier).';
        }
        if ($in['delivery_method'] === 'ship') {
            if ($in['address_line'] === '' || $in['city'] === '') {
                $errors[] = 'Please enter your delivery address and city.';
            }
            if (!in_array($in['state'], NigeriaStates::LIST, true)) {
                $errors[] = 'Please choose your state.';
            }
        }
        if (empty($_POST['agree'])) {
            $errors[] = 'Please tick the box to accept the Terms of Sale.';
        }
        $wantsAccount = !empty($_POST['create_account']) && !Auth::check();
        $pw = (string)($_POST['password'] ?? '');
        if ($wantsAccount) {
            if ($p = AccountController::passwordProblem($pw, $in['email'])) {
                $errors[] = $p;
            } elseif (($existingAcct = Db::one('SELECT * FROM users WHERE email = ?', [$in['email']])) && !EmailVerify::required($existingAcct)) {
                $errors[] = 'An account with this email already exists. Please sign in first, or untick “Create an account”.';
            }
        }
        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect('checkout');
        }

        $userId = Auth::check() ? (int)$_SESSION['uid'] : null;
        $verifyUid = 0;
        try {
            if ($wantsAccount) {
                $fields = [
                    'name' => $in['name'], 'phone' => $in['phone'], 'password_hash' => Auth::hash($pw), 'role' => 'customer',
                    'address_line' => $in['address_line'], 'city' => $in['city'], 'state' => $in['state'],
                ];
                if (!empty($existingAcct)) {
                    Db::update('users', $fields, 'id = ?', [$existingAcct['id']]); // an earlier unconfirmed sign-up with this address is replaced
                    $userId = (int)$existingAcct['id'];
                } else {
                    $userId = Db::insert('users', ['email' => $in['email']] + $fields);
                }
                Audit::log('register', $in['email'] . ' (at checkout)');
                $verifyUid = $userId; // the account is confirmed by email after the order is placed; the order itself does not wait
            }
            $orderId = Shop::placeOrder($in, $lines, (string)($_SESSION['coupon'] ?? ''), $userId);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            remember_input($_POST);
            redirect('cart');
        }
        Cart::clear();
        clear_old();
        $o = Shop::order($orderId);
        Shop::mail($o, 'placed');
        Shop::mailAdmin('New order ' . $o['order_no'] . ' (' . Shop::naira($o['total']) . ')', "New order from {$o['name']} ({$o['email']}, {$o['phone']}).\nTotal: " . Shop::naira($o['total']) . "\nDelivery: " . ($o['delivery_method'] === 'pickup' ? 'pickup' : $o['address_line'] . ', ' . $o['city'] . ', ' . $o['state']) . "\n\nOpen in admin: " . abs_url('admin/orders/' . $orderId));
        if ($verifyUid) {
            EmailVerify::begin($verifyUid);
            $sent = EmailVerify::send($verifyUid);
            flash('info', $sent === 'sent'
                ? 'Your order is placed. To finish creating your account, enter the 6-digit code we emailed to ' . EmailVerify::mask($o['email']) . ' at ' . abs_url('verify-email') . ' (your order is safe either way).'
                : 'Your order is placed. We could not email the account code; ask for a new one at ' . abs_url('verify-email') . '.');
        }
        redirect('order/' . $o['order_no'] . '/' . $o['access_token']);
    }

    /* ------------------------------------------------------------------ order page (guest link or signed-in owner) */

    private function findOrder(string $no, string $token): array
    {
        $o = Db::one('SELECT * FROM orders WHERE order_no = ?', [$no]);
        if (!$o || !hash_equals((string)$o['access_token'], $token)) {
            abort(404);
        }
        return $o;
    }

    public function order(string $no, string $token): void
    {
        $o = $this->findOrder($no, $token);
        Shop::expireUnpaid();
        $o = Shop::order((int)$o['id']);
        form_started('receipt');
        view('public/order', [
            'title' => 'Order ' . $o['order_no'],
            'meta_desc' => '',
            'o' => $o,
            'items' => Shop::items((int)$o['id']),
            'events' => Db::all('SELECT * FROM order_events WHERE order_id = ? ORDER BY id', [$o['id']]),
        ]);
    }

    public function receipt(string $no, string $token): void
    {
        csrf_check();
        $o = $this->findOrder($no, $token);
        $back = 'order/' . $o['order_no'] . '/' . $o['access_token'];
        if (!form_human('receipt')) {
            flash('error', 'Please wait a moment and try again.');
            redirect($back);
        }
        if (!rate_ok('receipt', 10, 3600)) {
            abort(429, 'Too many uploads. Please try again later.');
        }
        if ($o['status'] !== 'awaiting_payment') {
            flash('info', 'This order is no longer waiting for payment.');
            redirect($back);
        }
        try {
            $path = Upload::receipt($_FILES['receipt'] ?? []);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect($back);
        }
        Upload::removePrivate($o['receipt_path']);
        Db::update('orders', ['receipt_path' => $path, 'receipt_at' => date('Y-m-d H:i:s')], 'id = ?', [$o['id']]);
        Shop::event((int)$o['id'], 'receipt', 'Customer uploaded a payment receipt.', 'customer');
        Shop::mailAdmin('Receipt uploaded for order ' . $o['order_no'], "{$o['name']} uploaded a payment receipt for {$o['order_no']} (" . Shop::naira($o['total']) . ").\nCheck your bank, then confirm payment: " . abs_url('admin/orders/' . $o['id']));
        flash('success', 'Thank you! We have your receipt and will confirm your payment as soon as we see it in our account.');
        redirect($back);
    }

    public function cancel(string $no, string $token): void
    {
        csrf_check();
        $o = $this->findOrder($no, $token);
        try {
            Shop::act((int)$o['id'], 'cancel', [], 'customer');
            flash('success', 'Your order was cancelled.');
        } catch (RuntimeException $e) {
            flash('error', 'This order can no longer be cancelled online. Please contact us.');
        }
        if ($verifyUid) {
            EmailVerify::begin($verifyUid);
            $sent = EmailVerify::send($verifyUid);
            flash('info', $sent === 'sent'
                ? 'Your order is placed. To finish creating your account, enter the 6-digit code we emailed to ' . EmailVerify::mask($o['email']) . ' at ' . abs_url('verify-email') . ' (your order is safe either way).'
                : 'Your order is placed. We could not email the account code; ask for a new one at ' . abs_url('verify-email') . '.');
        }
        redirect('order/' . $o['order_no'] . '/' . $o['access_token']);
    }
}

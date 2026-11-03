<?php
declare(strict_types=1);

/** Orders: list/board, order detail with status actions, printable slips, receipts, CSV export. */
final class AdminOrdersController extends AdminBase
{
    private const PAID = "('paid','packed','shipped','delivered')";

    public function index(): void
    {
        Shop::expireUnpaid();
        $status = isset(Shop::STATUS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $view = ($_GET['view'] ?? '') === 'board' ? 'board' : 'list';
        $where = ['1 = 1'];
        $p = [];
        if ($status !== '') {
            $where[] = 'o.status = ?';
            $p[] = $status;
        }
        if ($q !== '') {
            $where[] = '(o.order_no LIKE ? OR o.name LIKE ? OR o.email LIKE ? OR o.phone LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            array_push($p, $like, $like, $like, $like);
        }
        $per = 40;
        $total = (int)Db::val('SELECT COUNT(*) FROM orders o WHERE ' . implode(' AND ', $where), $p);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
        $rows = Db::all(
            'SELECT o.*, (SELECT GROUP_CONCAT(CONCAT(i.qty, "× ", i.name, " ", i.colour, " ", i.size) SEPARATOR "; ") FROM order_items i WHERE i.order_id = o.id) AS summary
             FROM orders o WHERE ' . implode(' AND ', $where) . ' ORDER BY o.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per),
            $p
        );

        $board = [];
        if ($view === 'board') {
            foreach (['awaiting_payment', 'paid', 'packed', 'shipped', 'delivered'] as $st) {
                $board[$st] = Db::all(
                    'SELECT o.*, (SELECT GROUP_CONCAT(CONCAT(i.qty, "× ", i.name, " ", i.colour, " ", i.size) SEPARATOR "; ") FROM order_items i WHERE i.order_id = o.id) AS summary
                     FROM orders o WHERE o.status = ? ORDER BY o.id ' . ($st === 'delivered' ? 'DESC LIMIT 8' : 'ASC LIMIT 50'),
                    [$st]
                );
            }
        }
        $this->page('admin/orders', [
            'title' => 'Orders',
            'tab' => 'orders',
            'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages,
            'status' => $status, 'q' => $q, 'view' => $view, 'board' => $board,
            'counts' => [
                'awaiting' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'awaiting_payment'"),
                'receipts' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'awaiting_payment' AND receipt_path <> ''"),
                'topack' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'paid'"),
                'transit' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'shipped'"),
                'month' => (int)Db::val('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ' . self::PAID . " AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"),
                'all' => (int)Db::val('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ' . self::PAID),
            ],
        ]);
    }

    public function show(string $id): void
    {
        $o = Shop::order((int)$id);
        if (!$o) {
            abort(404);
        }
        $this->page('admin/order', [
            'title' => 'Order ' . $o['order_no'],
            'tab' => 'orders',
            'o' => $o,
            'items' => Shop::items((int)$o['id']),
            'events' => Db::all('SELECT * FROM order_events WHERE order_id = ? ORDER BY id DESC', [$o['id']]),
            'customer' => $o['user_id'] ? Db::one('SELECT id, name, email FROM users WHERE id = ?', [$o['user_id']]) : null,
        ]);
    }

    public function action(string $id): void
    {
        $this->verify();
        $action = (string)($_POST['action'] ?? '');
        try {
            $msg = Shop::act((int)$id, $action, [
                'note' => mb_substr(trim((string)($_POST['note'] ?? '')), 0, 200),
                'courier' => (string)($_POST['courier'] ?? ''),
                'tracking_no' => (string)($_POST['tracking_no'] ?? ''),
            ], 'admin');
            Audit::log('order_' . $action, (string)$id);
            flash('success', $msg);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        $back = (string)($_POST['return'] ?? '');
        redirect($back === 'board' ? 'admin/orders?view=board' : 'admin/orders/' . (int)$id);
    }

    public function note(string $id): void
    {
        $this->verify();
        Db::update('orders', ['admin_note' => mb_substr(trim((string)($_POST['admin_note'] ?? '')), 0, 490)], 'id = ?', [(int)$id]);
        flash('success', 'Note saved.');
        redirect('admin/orders/' . (int)$id);
    }

    /** Streams a payment receipt (stored outside the public web root). */
    public function receipt(string $id): void
    {
        $o = Shop::order((int)$id);
        $rel = $o['receipt_path'] ?? '';
        $full = $rel ? realpath(ROOT . '/' . $rel) : false;
        $base = realpath(ROOT . '/storage/private/receipts');
        if (!$full || !$base || !str_starts_with($full, $base . DIRECTORY_SEPARATOR) || !is_file($full)) {
            abort(404);
        }
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="receipt-' . $o['order_no'] . '.' . $ext . '"');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
        header('Cache-Control: private, no-store');
        readfile($full);
        exit;
    }

    /** Printable pages: pick (one order), slip (packing slip), label (shipping label). */
    public function printOne(string $id, string $type): void
    {
        $o = Shop::order((int)$id);
        if (!$o || !in_array($type, ['pick', 'slip', 'label'], true)) {
            abort(404);
        }
        view('admin/print_order', ['title' => ucfirst($type) . ' ' . $o['order_no'], 'o' => $o, 'items' => Shop::items((int)$o['id']), 'type' => $type], 'layout/print');
    }

    /** One combined pick list for every paid order waiting to be packed, grouped by shelf location. */
    public function pickList(): void
    {
        $orders = Db::all("SELECT * FROM orders WHERE status = 'paid' ORDER BY id");
        $agg = Db::all(
            "SELECT i.name, i.colour, i.size, i.sku, i.bin_location, SUM(i.qty) AS qty, COUNT(DISTINCT i.order_id) AS orders
             FROM order_items i JOIN orders o ON o.id = i.order_id WHERE o.status = 'paid' GROUP BY i.name, i.colour, i.size, i.sku, i.bin_location ORDER BY i.bin_location, i.name, i.colour, i.size"
        );
        view('admin/print_picklist', ['title' => 'Pick list', 'orders' => $orders, 'agg' => $agg], 'layout/print');
    }

    public function export(): void
    {
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['from'] ?? '')) ? $_GET['from'] : date('Y-m-01');
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['to'] ?? '')) ? $_GET['to'] : date('Y-m-d');
        $rows = Db::all(
            'SELECT o.*, (SELECT GROUP_CONCAT(CONCAT(i.qty, "x ", i.name, " ", i.colour, " ", i.size) SEPARATOR "; ") FROM order_items i WHERE i.order_id = o.id) AS items_text
             FROM orders o WHERE DATE(o.created_at) BETWEEN ? AND ? ORDER BY o.id',
            [$from, $to]
        );
        Audit::log('orders_export', "$from to $to, " . count($rows) . ' rows');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders-' . $from . '-to-' . $to . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['order_no', 'date', 'status', 'name', 'email', 'phone', 'delivery', 'state', 'items', 'subtotal', 'discount', 'delivery_fee', 'total', 'coupon', 'paid_at', 'courier', 'tracking']);
        foreach ($rows as $r) {
            $line = [$r['order_no'], $r['created_at'], $r['status'], $r['name'], $r['email'], $r['phone'], $r['delivery_method'], $r['state'], $r['items_text'], $r['subtotal'], $r['discount'], $r['shipping_fee'], $r['total'], $r['coupon_code'], $r['paid_at'], $r['courier'], $r['tracking_no']];
            foreach ($line as $k => $v) {
                if (is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) {
                    $line[$k] = "'" . $v; // spreadsheet formula-injection guard
                }
            }
            fputcsv($out, $line);
        }
        fclose($out);
    }

    public function customers(): void
    {
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $where = "u.role = 'customer'";
        $p = [];
        if ($q !== '') {
            $where .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $p = [$like, $like];
        }
        $this->page('admin/customers', [
            'title' => 'Customers',
            'tab' => 'customers',
            'q' => $q,
            'rows' => Db::all(
                "SELECT u.id, u.name, u.email, u.phone, u.created_at, u.last_login, u.totp_enabled_at, u.email_verified_at,
                        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders,
                        (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.user_id = u.id AND o.status IN " . self::PAID . ") AS spent
                 FROM users u WHERE $where ORDER BY u.id DESC LIMIT 300",
                $p
            ),
        ]);
    }
}

<?php
declare(strict_types=1);

/** Delivery fee per state. Default fee, free-delivery threshold and pickup live in Shop settings. */
final class AdminShippingController extends AdminBase
{
    public function index(): void
    {
        $rates = [];
        foreach (Db::all('SELECT state, fee FROM shipping_rates') as $r) {
            $rates[$r['state']] = (int)$r['fee'];
        }
        $this->page('admin/shipping', ['title' => 'Delivery fees', 'tab' => 'shipping', 'rates' => $rates]);
    }

    public function save(): void
    {
        $this->verify();
        $bulk = trim((string)($_POST['bulk'] ?? ''));
        $n = 0;
        foreach (NigeriaStates::LIST as $s) {
            $key = md5($s);
            $raw = $_POST['fee'][$key] ?? null;
            if ($bulk !== '' && ctype_digit($bulk) && !empty($_POST['apply_all'])) {
                $raw = $bulk;
            }
            if ($raw === null || !preg_match('/^\d{1,6}$/', (string)$raw)) {
                continue;
            }
            Db::run('INSERT INTO shipping_rates (state, fee) VALUES (?, ?) ON DUPLICATE KEY UPDATE fee = VALUES(fee)', [$s, (int)$raw]);
            $n++;
        }
        Audit::log('shipping_rates', "$n states");
        flash('success', 'Delivery fees saved.');
        redirect('admin/shop/shipping');
    }
}

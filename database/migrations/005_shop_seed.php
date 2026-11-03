<?php
declare(strict_types=1);

/**
 * Phase 3 seed: six placeholder T-shirt designs with sizes, colours and stock; placeholder delivery fees for every state;
 * a Terms of Sale template; and switches the Shop on. Everything here is placeholder content you edit in Admin.
 */
return function (PDO $pdo): void {
    require_once ROOT . '/app/core/NigeriaStates.php';

    if ((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
        $details = "**Placeholder text: replace with your real fabric details.**\n\n- 100% combed cotton, 180 gsm\n- Screen-printed design that will not crack or fade\n- Unisex fit; see the size guide\n- Machine wash cold, inside out. Do not tumble dry.";
        $navy = 'Navy|#0b1f3a';
        $white = 'White|#ffffff';
        $designs = [
            ['E = mc² Tee', 'e-mc2-tee', 'text', 'E = mc²', 'The most famous equation in physics, on a tee.', [$navy, $white], 6500, 10],
            ['Atmosphere Layers Tee', 'atmosphere-layers-tee', 'stripes', 'LAYERS', 'Troposphere to exosphere, layer by layer.', [$navy, $white], 6500, 20],
            ['Isobar Tee', 'isobar-tee', 'curves', 'ISOBAR', 'The pressure lines of a weather map.', [$navy, $white, 'Sky|#7ec0ff'], 6500, 30],
            ['Harmattan Season Tee', 'harmattan-season-tee', 'text', 'HARMATTAN', 'For the dusty, golden dry season.', ['Sand|#d9b88f', $navy], 6500, 40],
            ['Vector A × B Tee', 'vector-a-cross-b-tee', 'text', 'A × B', 'Cross products and good taste.', [$navy, $white], 6500, 50],
            ['Entropy Happens Tee', 'entropy-happens-tee', 'text', 'ENTROPY HAPPENS', 'Everything falls apart eventually. Look good meanwhile.', [$navy, $white], 7000, 60],
        ];
        $insP = $pdo->prepare('INSERT INTO products (slug, name, category, tagline, description, details, price, status, is_featured, sort_order, colours, sizes, design_text, design_style, is_sample)
            VALUES (?, ?, \'T-shirts\', ?, ?, ?, ?, \'active\', ?, ?, ?, \'S,M,L,XL,XXL\', ?, ?, 1)');
        $insV = $pdo->prepare('INSERT INTO product_variants (product_id, colour, colour_hex, size, sku, stock, reorder_level, bin_location) VALUES (?, ?, ?, ?, ?, ?, 3, ?)');
        $stock = ['S' => 6, 'M' => 10, 'L' => 10, 'XL' => 8, 'XXL' => 4];
        foreach ($designs as $i => [$name, $slug, $style, $text, $tagline, $colours, $price, $order]) {
            $desc = "**Sample product.** Replace this description, the price, photos and stock in Admin → Shop → Products.\n\n$tagline";
            $insP->execute([$slug, $name, $tagline, $desc, $details, $price, $i < 3 ? 1 : 0, $order, implode("\n", $colours), $text, $style]);
            $pid = (int)$pdo->lastInsertId();
            foreach ($colours as $c) {
                [$cn, $hex] = explode('|', $c);
                foreach ($stock as $size => $qty) {
                    $q = ($slug === 'entropy-happens-tee' && $size === 'M') ? 2 : $qty;
                    $sku = strtoupper('TEE-' . preg_replace('/[^A-Za-z0-9]+/', '', explode('-', $slug)[0]) . '-' . preg_replace('/[^A-Za-z0-9]+/', '', $cn) . '-' . $size);
                    $insV->execute([$pid, $cn, $hex, $size, $sku, $q, 'A' . ($i + 1)]);
                }
            }
        }
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM shipping_rates')->fetchColumn() === 0) {
        $sw = ['Ogun', 'Oyo', 'Osun', 'Ondo', 'Ekiti'];
        $mid = ['FCT (Abuja)', 'Edo', 'Delta', 'Kwara', 'Kogi', 'Anambra', 'Enugu', 'Imo', 'Abia', 'Ebonyi', 'Rivers', 'Bayelsa', 'Akwa Ibom', 'Cross River', 'Niger', 'Nasarawa', 'Plateau', 'Benue'];
        $ins = $pdo->prepare('INSERT INTO shipping_rates (state, fee) VALUES (?, ?)');
        foreach (NigeriaStates::LIST as $s) {
            $fee = $s === 'Lagos' ? 2500 : (in_array($s, $sw, true) ? 3000 : (in_array($s, $mid, true) ? 3500 : 4500));
            $ins->execute([$s, $fee]);
        }
    }

    $terms = "This is a starting template. Please review it and adapt it to how you actually sell.\n\n## Orders and payment\n\nPrices are in Nigerian Naira. After you place an order you pay by bank transfer to the account shown, using your order number as the reference. Your order is held for a limited time (shown at checkout) while we wait for payment. Items are reserved for you during this time and released if payment is not received. Your order is confirmed only after we have received your payment.\n\n## Delivery\n\nWe dispatch confirmed orders within the time shown on the website and send you a tracking number where the courier provides one. Delivery times depend on the courier and your location. You can choose to collect your order if pickup is offered.\n\n## Returns and exchanges\n\nIf your item arrives damaged or faulty, contact us within 7 days of delivery with your order number and a photo and we will replace it or refund you. Please check the size guide before ordering; unworn items in original condition can be exchanged for another size within 7 days, subject to stock, with the delivery cost paid by the customer.\n\n## Your data\n\nWe use your details only to process and deliver your order and to contact you about it. See the privacy policy.";
    $pdo->prepare("INSERT IGNORE INTO pages (slug, title, body) VALUES ('sales-terms', 'Terms of Sale', ?)")->execute([$terms]);
    $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES ('enable_shop', '1') ON DUPLICATE KEY UPDATE svalue = '1'")->execute();
};

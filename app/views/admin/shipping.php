<?php partial('shop_tabs', ['tab' => $tab]); ?>
<p class="muted">The fee a customer pays for each state (Naira). Pickup is always free. The default fee (<?= e(Shop::naira(Settings::get('default_shipping_fee'))) ?>), free-delivery threshold<?= (int)Settings::get('free_shipping_over') > 0 ? ' (' . e(Shop::naira(Settings::get('free_shipping_over'))) . ')' : ' (off)' ?> and pickup address are in <a href="<?= e(url('/admin/settings/shop')) ?>">Shop settings</a>.</p>
<p class="flash flash-info small">The fees loaded at install are placeholders. Set your real courier prices below.</p>
<form method="post" action="<?= e(url('/admin/shop/shipping')) ?>" class="form">
  <?= csrf_field() ?>
  <div class="card" style="margin-bottom:14px">
    <label class="check"><input type="checkbox" name="apply_all" value="1"> Set every state to this fee: </label>
    <input type="number" name="bulk" min="0" max="999999" placeholder="e.g. 3500" style="width:10rem">
  </div>
  <div class="rates-grid">
    <?php foreach (NigeriaStates::LIST as $s): ?>
      <label><span><?= e($s) ?></span><input type="number" name="fee[<?= md5($s) ?>]" value="<?= (int)($rates[$s] ?? Settings::get('default_shipping_fee')) ?>" min="0" max="999999"></label>
    <?php endforeach; ?>
  </div>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Save delivery fees</button></div>
</form>

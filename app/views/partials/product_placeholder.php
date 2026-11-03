<?php
/** Placeholder picture for one-size products (books, mugs, teaching aids) that have no photo yet. Expects $name, optional $icon. */
$icon = $icon ?? '📦';
?>
<div class="product-ph" role="img" aria-label="<?= e($name) ?>"><span class="ph-icon"><?= e($icon) ?></span><span class="ph-name"><?= e($name) ?></span></div>
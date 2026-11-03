<?php
/**
 * Placeholder T-shirt artwork (used until you upload real photos).
 * Expects: $hex garment colour, $text design wording, $style (text | stripes | curves).
 */
$hex = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($hex ?? '')) ? $hex : '#0b1f3a';
$r = hexdec(substr($hex, 1, 2));
$g = hexdec(substr($hex, 3, 2));
$b = hexdec(substr($hex, 5, 2));
$dark = (0.299 * $r + 0.587 * $g + 0.114 * $b) < 140;
$ink = $dark ? '#ffffff' : '#0b1f3a';
$style = $style ?? 'text';
$text = (string)($text ?? '');
$words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
$lines = count($words) > 1 && mb_strlen($text) > 9 ? [implode(' ', array_slice($words, 0, (int)ceil(count($words) / 2))), implode(' ', array_slice($words, (int)ceil(count($words) / 2)))] : [$text];
$longest = max(array_map('mb_strlen', $lines) ?: [1]);
$size = max(9, min(26, (int)floor(88 / max(1, $longest * 0.62))));
$y0 = match ($style) { 'stripes' => 134, 'curves' => 168, default => 118 - (count($lines) - 1) * ($size * 0.6) };
?>
<svg class="tee" viewBox="0 0 200 220" role="img" aria-label="<?= e($text) ?> T-shirt design preview" style="--g:<?= e($hex) ?>;--ink:<?= $ink ?>">
  <path d="M62 14 L18 42 L33 86 L54 76 L54 204 L146 204 L146 76 L167 86 L182 42 L138 14 Q100 40 62 14Z" fill="var(--g)" stroke="rgba(0,0,0,.18)" stroke-width="1.5" stroke-linejoin="round"/>
  <path d="M68 15 Q100 46 132 15" fill="none" stroke="rgba(0,0,0,.22)" stroke-width="2"/>
  <?php if ($style === 'stripes'): ?>
    <g fill="var(--ink)">
      <rect x="72" y="58" width="56" height="5" rx="2.5" opacity=".95"/><rect x="72" y="68" width="56" height="5" rx="2.5" opacity=".78"/><rect x="72" y="78" width="56" height="5" rx="2.5" opacity=".6"/>
      <rect x="72" y="88" width="56" height="5" rx="2.5" opacity=".42"/><rect x="72" y="98" width="56" height="5" rx="2.5" opacity=".26"/>
    </g>
  <?php elseif ($style === 'curves'): ?>
    <g fill="none" stroke="var(--ink)" stroke-width="2.2" stroke-linecap="round">
      <path d="M66 118 Q100 52 134 118" opacity=".95"/><path d="M73 126 Q100 72 127 126" opacity=".75"/><path d="M80 134 Q100 92 120 134" opacity=".55"/><path d="M87 142 Q100 112 113 142" opacity=".4"/>
    </g>
  <?php endif; ?>
  <?php foreach ($lines as $i => $ln): ?>
    <text x="100" y="<?= round($y0 + $i * $size * 1.15, 1) ?>" text-anchor="middle" font-family="Georgia,serif" font-weight="700" font-size="<?= $size ?>" fill="var(--ink)"><?= e($ln) ?></text>
  <?php endforeach; ?>
</svg>

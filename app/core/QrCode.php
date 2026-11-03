<?php
declare(strict_types=1);

/**
 * Minimal QR Code generator (byte mode, error correction level L, versions 1-10: up to 271 bytes), output as inline SVG.
 * Written so that the 2FA set-up page needs no external service and no JavaScript.
 */
final class QrCode
{
    /** version => [EC codewords per block, [[blocks, data codewords per block], ...]] */
    private const EC = [
        1 => [7, [[1, 19]]], 2 => [10, [[1, 34]]], 3 => [15, [[1, 55]]], 4 => [20, [[1, 80]]], 5 => [26, [[1, 108]]],
        6 => [18, [[2, 68]]], 7 => [20, [[2, 78]]], 8 => [24, [[2, 97]]], 9 => [30, [[2, 116]]], 10 => [18, [[2, 68], [2, 69]]],
    ];
    private const ALIGN = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50]];

    private int $version;
    private int $size;
    /** @var bool[][] [row][col] */
    private array $m = [];
    /** @var bool[][] */
    private array $fn = [];

    /** @return bool[][] the module matrix (true = dark), without the quiet zone */
    public static function matrix(string $text): array
    {
        $q = new self($text);
        return $q->m;
    }

    public static function svg(string $text, string $label = 'QR code'): string
    {
        $m = self::matrix($text);
        $n = count($m);
        $path = '';
        for ($y = 0; $y < $n; $y++) {
            $x = 0;
            while ($x < $n) {
                if ($m[$y][$x]) {
                    $s = $x;
                    while ($x < $n && $m[$y][$x]) {
                        $x++;
                    }
                    $path .= "M$s,{$y}h" . ($x - $s) . 'v1h-' . ($x - $s) . 'z';
                } else {
                    $x++;
                }
            }
        }
        $t = $n + 8;
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 ' . $t . ' ' . $t . '" width="220" height="220" shape-rendering="crispEdges" role="img" aria-label="'
            . htmlspecialchars($label, ENT_QUOTES) . '"><rect x="-4" y="-4" width="' . $t . '" height="' . $t . '" fill="#fff"/><path d="' . $path . '" fill="#000"/></svg>';
    }

    private function __construct(string $text)
    {
        $data = array_values(unpack('C*', $text) ?: []);
        $len = count($data);
        $v = 0;
        for ($i = 1; $i <= 10; $i++) {
            $cap = 0;
            foreach (self::EC[$i][1] as [$cnt, $dpb]) {
                $cap += $cnt * $dpb;
            }
            if ($cap * 8 >= 4 + ($i < 10 ? 8 : 16) + 8 * $len) {
                $v = $i;
                break;
            }
        }
        if ($v === 0) {
            throw new InvalidArgumentException('Text too long for this QR code generator.');
        }
        $this->version = $v;
        $this->size = $v * 4 + 17;
        $codewords = $this->buildCodewords($data, $v);
        $this->m = array_fill(0, $this->size, array_fill(0, $this->size, false));
        $this->fn = $this->m;
        $this->drawFunctionPatterns();
        $this->drawCodewords($codewords);
        $best = 0;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $this->applyMask($mask);
            $this->drawFormat($mask);
            $p = $this->penalty();
            if ($p < $bestPenalty) {
                $bestPenalty = $p;
                $best = $mask;
            }
            $this->applyMask($mask); // undo
        }
        $this->applyMask($best);
        $this->drawFormat($best);
    }

    /* ---------------------------------------------------------------- data and error correction */

    private function buildCodewords(array $data, int $v): array
    {
        [$ecLen, $groups] = self::EC[$v];
        $dataCw = 0;
        foreach ($groups as [$c, $d]) {
            $dataCw += $c * $d;
        }
        $bits = [];
        $put = function (int $val, int $n) use (&$bits): void {
            for ($i = $n - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };
        $put(0b0100, 4);
        $put(count($data), $v < 10 ? 8 : 16);
        foreach ($data as $b) {
            $put($b, 8);
        }
        $put(0, min(4, $dataCw * 8 - count($bits)));
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        $bytes = [];
        foreach (array_chunk($bits, 8) as $chunk) {
            $bytes[] = bindec(implode('', $chunk));
        }
        for ($pad = 0xEC; count($bytes) < $dataCw; $pad ^= 0xEC ^ 0x11) {
            $bytes[] = $pad;
        }
        // split into blocks
        $blocks = [];
        $pos = 0;
        foreach ($groups as [$cnt, $dpb]) {
            for ($i = 0; $i < $cnt; $i++) {
                $blocks[] = array_slice($bytes, $pos, $dpb);
                $pos += $dpb;
            }
        }
        $div = self::rsDivisor($ecLen);
        $ecs = array_map(fn($b) => self::rsRemainder($b, $div), $blocks);
        $out = [];
        $max = max(array_map('count', $blocks));
        for ($i = 0; $i < $max; $i++) {
            foreach ($blocks as $b) {
                if ($i < count($b)) {
                    $out[] = $b[$i];
                }
            }
        }
        for ($i = 0; $i < $ecLen; $i++) {
            foreach ($ecs as $e) {
                $out[] = $e[$i];
            }
        }
        return $out;
    }

    private static function gfMul(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        return $z & 0xFF;
    }

    private static function rsDivisor(int $degree): array
    {
        $r = array_fill(0, $degree, 0);
        $r[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $r[$j] = self::gfMul($r[$j], $root);
                if ($j + 1 < $degree) {
                    $r[$j] ^= $r[$j + 1];
                }
            }
            $root = self::gfMul($root, 2);
        }
        return $r;
    }

    private static function rsRemainder(array $data, array $div): array
    {
        $r = array_fill(0, count($div), 0);
        foreach ($data as $b) {
            $factor = $b ^ array_shift($r);
            $r[] = 0;
            foreach ($div as $i => $coef) {
                $r[$i] ^= self::gfMul($coef, $factor);
            }
        }
        return $r;
    }

    /* ---------------------------------------------------------------- drawing */

    private function setFn(int $x, int $y, bool $dark): void
    {
        $this->m[$y][$x] = $dark;
        $this->fn[$y][$x] = true;
    }

    private function drawFunctionPatterns(): void
    {
        $n = $this->size;
        for ($i = 0; $i < $n; $i++) {
            $this->setFn(6, $i, $i % 2 === 0);
            $this->setFn($i, 6, $i % 2 === 0);
        }
        foreach ([[3, 3], [$n - 4, 3], [3, $n - 4]] as [$cx, $cy]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $d = max(abs($dx), abs($dy));
                    $x = $cx + $dx;
                    $y = $cy + $dy;
                    if ($x >= 0 && $x < $n && $y >= 0 && $y < $n) {
                        $this->setFn($x, $y, $d !== 2 && $d !== 4);
                    }
                }
            }
        }
        $pos = self::ALIGN[$this->version];
        $k = count($pos);
        for ($i = 0; $i < $k; $i++) {
            for ($j = 0; $j < $k; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $k - 1) || ($i === $k - 1 && $j === 0)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $this->setFn($pos[$i] + $dx, $pos[$j] + $dy, max(abs($dx), abs($dy)) !== 1);
                    }
                }
            }
        }
        $this->drawFormat(0);
        if ($this->version >= 7) {
            $rem = $this->version;
            for ($i = 0; $i < 12; $i++) {
                $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
            }
            $bits = ($this->version << 12) | $rem;
            for ($i = 0; $i < 18; $i++) {
                $bit = (($bits >> $i) & 1) === 1;
                $a = $n - 11 + $i % 3;
                $b = intdiv($i, 3);
                $this->setFn($a, $b, $bit);
                $this->setFn($b, $a, $bit);
            }
        }
    }

    /** Format bits for error-correction level L (01) and the given mask. */
    public static function formatBits(int $mask): int
    {
        $data = (1 << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        return (($data << 10) | $rem) ^ 0x5412;
    }

    private function drawFormat(int $mask): void
    {
        $bits = self::formatBits($mask);
        $n = $this->size;
        $bit = fn(int $i): bool => (($bits >> $i) & 1) === 1;
        for ($i = 0; $i <= 5; $i++) {
            $this->setFn(8, $i, $bit($i));
        }
        $this->setFn(8, 7, $bit(6));
        $this->setFn(8, 8, $bit(7));
        $this->setFn(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $this->setFn(14 - $i, 8, $bit($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $this->setFn($n - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFn(8, $n - 15 + $i, $bit($i));
        }
        $this->setFn(8, $n - 8, true);
    }

    private function drawCodewords(array $cw): void
    {
        $n = $this->size;
        $i = 0;
        $total = count($cw) * 8;
        for ($right = $n - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $n; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $n - 1 - $vert : $vert;
                    if (!$this->fn[$y][$x] && $i < $total) {
                        $this->m[$y][$x] = (($cw[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                        $i++;
                    }
                }
            }
        }
    }

    private function applyMask(int $mask): void
    {
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->fn[$y][$x]) {
                    continue;
                }
                $inv = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => $x * $y % 2 + $x * $y % 3 === 0,
                    6 => ($x * $y % 2 + $x * $y % 3) % 2 === 0,
                    default => (($x + $y) % 2 + $x * $y % 3) % 2 === 0,
                };
                if ($inv) {
                    $this->m[$y][$x] = !$this->m[$y][$x];
                }
            }
        }
    }

    private function penalty(): int
    {
        $n = $this->size;
        $p = 0;
        for ($pass = 0; $pass < 2; $pass++) {
            for ($a = 0; $a < $n; $a++) {
                $run = 1;
                $line = [];
                for ($b = 0; $b < $n; $b++) {
                    $line[] = $pass === 0 ? $this->m[$a][$b] : $this->m[$b][$a];
                }
                for ($b = 1; $b < $n; $b++) {
                    if ($line[$b] === $line[$b - 1]) {
                        $run++;
                        if ($run === 5) {
                            $p += 3;
                        } elseif ($run > 5) {
                            $p++;
                        }
                    } else {
                        $run = 1;
                    }
                }
                $s = implode('', array_map(fn($d) => $d ? '1' : '0', $line));
                $p += 40 * (substr_count($s, '10111010000') + substr_count($s, '00001011101'));
            }
        }
        for ($y = 0; $y < $n - 1; $y++) {
            for ($x = 0; $x < $n - 1; $x++) {
                $c = $this->m[$y][$x];
                if ($c === $this->m[$y][$x + 1] && $c === $this->m[$y + 1][$x] && $c === $this->m[$y + 1][$x + 1]) {
                    $p += 3;
                }
            }
        }
        $dark = 0;
        foreach ($this->m as $row) {
            $dark += count(array_filter($row));
        }
        $total = $n * $n;
        $k = (int)ceil(abs($dark * 20 - $total * 10) / $total) - 1;
        return $p + max(0, $k) * 10;
    }
}

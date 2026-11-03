<?php
declare(strict_types=1);

/**
 * Small, safe Markdown renderer. All input is HTML-escaped first, so authored text can never inject markup.
 * Supports: # headings, **bold**, *italic*, `code`, [links](url), ![images](url), lists, > quotes, --- rules, ``` code blocks.
 */
final class Markdown
{
    /** True while rendering text written by a visitor: no pictures, and links are marked nofollow/ugc. */
    private static bool $ugc = false;

    public static function renderUntrusted(string $src): string
    {
        self::$ugc = true;
        try {
            return self::render($src);
        } finally {
            self::$ugc = false;
        }
    }

    public static function render(string $src): string
    {
        $lines = explode("\n", self::extractMath(str_replace(["\r\n", "\r"], "\n", $src)));
        $out = [];
        $para = [];
        $n = count($lines);

        $flush = function () use (&$para, &$out): void {
            if ($para) {
                $joined = trim(implode("\n", $para));
                $out[] = preg_match('/^\x02D\d+\x02$/', $joined)
                    ? '<div class="math-display">' . $joined . '</div>'
                    : '<p>' . self::inline($joined) . '</p>';
                $para = [];
            }
        };

        for ($i = 0; $i < $n; $i++) {
            $line = rtrim($lines[$i]);

            if (trim($line) === '') {
                $flush();
                continue;
            }
            if (str_starts_with(ltrim($line), '```')) {
                $flush();
                $code = [];
                for ($i++; $i < $n && !str_starts_with(ltrim($lines[$i]), '```'); $i++) {
                    $code[] = $lines[$i];
                }
                $out[] = '<pre><code>' . e(implode("\n", $code)) . '</code></pre>';
                continue;
            }
            if (preg_match('/^(#{1,4})\s+(.+)$/', $line, $m)) {
                $flush();
                $level = strlen($m[1]) + 1; // # becomes h2: the page title already uses h1
                $out[] = "<h$level>" . self::inline($m[2]) . "</h$level>";
                continue;
            }
            if (preg_match('/^(-{3,}|\*{3,})$/', trim($line))) {
                $flush();
                $out[] = '<hr>';
                continue;
            }
            if (preg_match('/^>\s?/', $line)) {
                $flush();
                $q = [];
                for (; $i < $n && preg_match('/^>\s?(.*)$/', rtrim($lines[$i]), $mm); $i++) {
                    $q[] = $mm[1];
                }
                $i--;
                $out[] = '<blockquote>' . self::inline(implode("\n", $q)) . '</blockquote>';
                continue;
            }
            if (preg_match('/^\s*[-*+]\s+/', $line)) {
                $flush();
                $items = [];
                for (; $i < $n && preg_match('/^\s*[-*+]\s+(.*)$/', rtrim($lines[$i]), $mm); $i++) {
                    $items[] = '<li>' . self::inline($mm[1]) . '</li>';
                }
                $i--;
                $out[] = '<ul>' . implode('', $items) . '</ul>';
                continue;
            }
            if (preg_match('/^\s*\d+[.)]\s+/', $line)) {
                $flush();
                $items = [];
                for (; $i < $n && preg_match('/^\s*\d+[.)]\s+(.*)$/', rtrim($lines[$i]), $mm); $i++) {
                    $items[] = '<li>' . self::inline($mm[1]) . '</li>';
                }
                $i--;
                $out[] = '<ol>' . implode('', $items) . '</ol>';
                continue;
            }
            $para[] = $line;
        }
        $flush();
        return self::restoreMath(implode("\n", $out));
    }

    /** @var string[] rendered MathML waiting to be put back after text formatting */
    private static array $math = [];

    /** Pulls $$display$$ and $inline$ maths out of the text (never inside code) and leaves safe placeholders. */
    private static function extractMath(string $src): string
    {
        self::$math = [];
        $parts = preg_split('/(```.*?```)/s', $src, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$src];
        foreach ($parts as $k => $part) {
            if ($k % 2 === 1) {
                continue; // fenced code block
            }
            $codes = [];
            $part = preg_replace_callback('/`[^`\n]+`/', function ($m) use (&$codes) {
                $codes[] = $m[0];
                return "\x03" . (count($codes) - 1) . "\x03";
            }, $part);
            $part = preg_replace_callback('/\$\$(.+?)\$\$/s', function ($m) {
                self::$math[] = Latex::render($m[1], true);
                return "\n\n\x02D" . (count(self::$math) - 1) . "\x02\n\n";
            }, $part);
            $part = preg_replace_callback('/(?<![\\\\$\d\w])\$(?![\s$])((?:\\\\.|[^$\n\\\\])+?)(?<![\s\\\\])\$(?![\d$])/', function ($m) {
                self::$math[] = Latex::render($m[1], false);
                return "\x02I" . (count(self::$math) - 1) . "\x02";
            }, $part);
            $parts[$k] = preg_replace_callback('/\x03(\d+)\x03/', fn($m) => $codes[(int)$m[1]], $part);
        }
        return implode('', $parts);
    }

    private static function restoreMath(string $html): string
    {
        if (!self::$math) {
            return $html;
        }
        $html = preg_replace_callback('/\x02[DI](\d+)\x02/', fn($m) => self::$math[(int)$m[1]] ?? '', $html);
        self::$math = [];
        return $html;
    }

    public static function inline(string $text): string
    {
        $text = e($text);

        // Protect code spans from further formatting.
        $codes = [];
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$codes) {
            $codes[] = '<code>' . $m[1] . '</code>';
            return "\x01" . (count($codes) - 1) . "\x01";
        }, $text);

        // Images, then links. URLs must be http(s), mailto, site-relative or an anchor.
        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)\)/', function ($m) {
            if (self::$ugc) {
                return $m[1]; // visitors cannot place pictures from other sites in their articles
            }
            $src = self::safeUrl(html_entity_decode($m[2], ENT_QUOTES));
            return $src === null ? $m[0] : '<img src="' . e($src) . '" alt="' . $m[1] . '" loading="lazy">';
        }, $text);
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $href = self::safeUrl(html_entity_decode($m[2], ENT_QUOTES));
            if ($href === null) {
                return $m[0];
            }
            $ext = preg_match('#^https?://#i', $href) ? ' target="_blank" rel="' . (self::$ugc ? 'nofollow ugc ' : '') . 'noopener noreferrer"' : '';
            return '<a href="' . e($href) . '"' . $ext . '>' . $m[1] . '</a>';
        }, $text);

        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/s', '<em>$1</em>', $text);
        $text = preg_replace('/\n/', "<br>\n", $text);

        return preg_replace_callback('/\x01(\d+)\x01/', fn($m) => $codes[(int)$m[1]], $text);
    }

    private static function safeUrl(string $u): ?string
    {
        $u = trim($u);
        if (str_starts_with($u, '//')) {
            return null;
        }
        if (str_starts_with($u, '/')) {
            // Site-relative link: keep it working when the site lives in a sub-folder.
            return (BASE_PATH !== '' && !str_starts_with($u, BASE_PATH . '/')) ? BASE_PATH . $u : $u;
        }
        if (preg_match('#^(https?://|mailto:|\#)#i', $u)) {
            return $u;
        }
        // Allow site-relative paths like uploads/images/x.jpg
        if (preg_match('#^[A-Za-z0-9_./-]+$#', $u) && !str_contains($u, '..')) {
            return url($u);
        }
        return null;
    }
}

<?php
declare(strict_types=1);

/** The "Free tools" directory: curated links to free learning, teaching and research resources. */
final class Toolbox
{
    public const KINDS = [
        'video' => 'Video lessons', 'course' => 'Free courses & books', 'tutorial' => 'Tutorials & guides', 'software' => 'Software to download',
        'online' => 'Online tools', 'data' => 'Data & maps', 'papers' => 'Papers & literature',
    ];
    public const ICONS = ['video' => '▶', 'course' => '📘', 'tutorial' => '🧭', 'software' => '💾', 'online' => '🧮', 'data' => '🌍', 'papers' => '📄'];
    public const AUDIENCES = ['all' => 'Everyone', 'students' => 'Students', 'researchers' => 'Researchers', 'teachers' => 'Teachers'];
    public const LICENSES = [
        'free' => 'Free', 'open' => 'Open source / open licence', 'read' => 'Free to read online', 'account' => 'Free with a free account',
        'plan' => 'Free plan available', 'eligible' => 'Free for eligible institutions', 'academic' => 'Free for academic use',
    ];

    /** YouTube thumbnail for a single-video link, or ''. Channel and playlist links have none. */
    public static function thumb(string $url): string
    {
        $v = Learn::parseVideoUrl($url);
        return $v && $v[0] === 'youtube' ? 'https://i.ytimg.com/vi/' . $v[1] . '/mqdefault.jpg' : '';
    }

    public static function host(string $url): string
    {
        return preg_replace('/^www\./', '', (string)parse_url($url, PHP_URL_HOST));
    }

    /** Links people may see (broken ones are hidden automatically after a link check). */
    public static function visible(array $f = []): array
    {
        $where = ["is_visible = 1", "link_status <> 'broken'"];
        $p = [];
        foreach (['kind' => self::KINDS, 'audience' => self::AUDIENCES] as $k => $allowed) {
            if (!empty($f[$k]) && isset($allowed[$f[$k]])) {
                $where[] = $k === 'audience' ? "(audience = ? OR audience = 'all')" : "$k = ?";
                $p[] = $f[$k];
            }
        }
        if (!empty($f['subject'])) {
            $where[] = 'subject = ?';
            $p[] = $f['subject'];
        }
        if (!empty($f['q'])) {
            $where[] = '(title LIKE ? OR description LIKE ? OR provider LIKE ? OR subject LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$f['q']) . '%';
            array_push($p, $like, $like, $like, $like);
        }
        return Db::all('SELECT * FROM toolbox_links WHERE ' . implode(' AND ', $where) . ' ORDER BY is_featured DESC, sort_order, title', $p);
    }

    public static function subjects(): array
    {
        return array_column(Db::all("SELECT subject, COUNT(*) n FROM toolbox_links WHERE is_visible = 1 AND link_status <> 'broken' GROUP BY subject ORDER BY subject"), 'subject');
    }

    /** True for a plain http(s) address. */
    public static function validUrl(string $u): bool
    {
        return (bool)preg_match('#^https?://[^\s/$.?\#][^\s]*$#i', $u) && strlen($u) <= 600;
    }

    /** Checks one address: 'ok', 'blocked' (the site refuses automatic checks, so verify by hand), 'broken' (404/410/server error/unknown domain) or 'unknown' (timed out or unreachable from this server: try again later). */
    public static function check(string $url): string
    {
        if (!str_starts_with(strtolower($url), 'https://')) {
            return 'unknown';
        }
        $r = Http::get($url, ['timeout' => 12, 'max_bytes' => 40000]);
        $c = (int)$r['code'];
        if ($c >= 200 && $c < 400) {
            return 'ok';
        }
        if (in_array($c, [401, 403, 405, 429, 451, 999], true)) {
            return 'blocked';
        }
        if ($c === 404 || $c === 410 || $c >= 500 || ($c === 0 && stripos($r['error'], 'resolve') !== false)) {
            return 'broken'; // gone, server error, or the domain no longer exists
        }
        return 'unknown';
    }
}

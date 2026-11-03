<?php
declare(strict_types=1);

/** Everything search engines need: robots rules, canonical addresses, structured data (JSON-LD), the sitemap and IndexNow pings. */
final class Seo
{
    /** Page addresses that must never be indexed (private, personal, redirects, forms). Prefix match on the site path. */
    private const PRIVATE = [
        '#^/(admin|account|cart|checkout|order|enrolment|login|register|logout|forgot-password|reset-password|newsletter|certificate|subscribe|install\.php)(/|$)#',
        '#^/learn/(video|file)/#', '#^/learn/[^/]+/(quiz|enrol|review)#', '#^/learn/[^/]+/lesson/\d+/(complete|ask)#',
        '#^/toolbox/(go|suggest)#', '#^/resources/\d+/(download|report)#', '#^/news/(tick|report)#', '#^/shop/[^/]+/notify#', '#^/my-#',
    ];
    /** Folders listed as Disallow in robots.txt (crawling them is pointless). */
    private const ROBOTS_DISALLOW = ['/admin', '/account', '/cart', '/checkout', '/order/', '/enrolment/', '/login', '/register', '/logout', '/forgot-password', '/reset-password', '/newsletter/', '/certificate/', '/learn/video/', '/learn/file/', '/toolbox/go/', '/news/tick', '/install.php', '/storage/', '/config/', '/app/', '/database/', '/cron/', '/tools/'];

    /** May this site be indexed at all? Only a "live website" install (APP_ENV=production), or ALLOW_INDEXING=1. */
    public static function indexingAllowed(): bool
    {
        return env('APP_ENV', 'local') === 'production' || env('ALLOW_INDEXING', '0') === '1';
    }

    public static function isPrivatePath(string $path): bool
    {
        foreach (self::PRIVATE as $re) {
            if (preg_match($re, $path)) {
                return true;
            }
        }
        return false;
    }

    /** True when the address carries filters or searches (any query value except the page number): duplicates of the base page. */
    public static function hasFilterParams(): bool
    {
        foreach (array_keys($_GET) as $k) {
            if ($k !== 'page') {
                return true;
            }
        }
        return false;
    }

    /** The one address a page should be known by: no filters, no tracking parameters; "page" kept for page 2 and beyond. */
    public static function canonical(): string
    {
        $path = request_path();
        $full = abs_url('');
        $origin = substr($full, 0, strlen($full) - strlen(url(''))); // scheme + host (or APP_URL)
        $base = str_ends_with(BASE_PATH, '/public') ? substr(BASE_PATH, 0, -7) : BASE_PATH; // reached through /public/: use the clean address
        $u = $origin . $base . ($path === '/' ? '/' : $path);
        $page = (int)($_GET['page'] ?? 1);
        return $page > 1 ? $u . '?page=' . $page : $u;
    }
    /** Value of the robots meta tag for this request. */
    public static function robotsContent(bool $noindex = false): string
    {
        if (!self::indexingAllowed() || $noindex || self::isPrivatePath(request_path())) {
            return 'noindex, nofollow';
        }
        if (self::hasFilterParams()) {
            return 'noindex, follow'; // searches and filters: let crawlers follow links, but keep the duplicates out of results
        }
        return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    public static function robotsTxt(): string
    {
        if (!self::indexingAllowed()) {
            return "# Indexing is off: this is a test installation (APP_ENV is not production).\nUser-agent: *\nDisallow: /\n";
        }
        $out = "User-agent: *\n";
        foreach (self::ROBOTS_DISALLOW as $p) {
            $out .= 'Disallow: ' . url($p) . "\n";
        }
        $out .= "Allow: /\n\n# AI-training crawlers can be refused by adding their names here if you wish, for example:\n# User-agent: GPTBot\n# Disallow: /\n\nSitemap: " . abs_url('sitemap.xml') . "\n";
        return $out;
    }

    /* ------------------------------------------------------------------ structured data */

    private static function clean(array $a): array
    {
        return array_filter($a, fn($v) => $v !== '' && $v !== null && $v !== []);
    }

    private static function iso(?string $d): string
    {
        return $d ? date('c', strtotime($d)) : '';
    }

    private static function img(?string $rel): string
    {
        return $rel ? abs_url($rel) : '';
    }

    /** WebSite + Person for the home page. */
    public static function site(): array
    {
        $same = array_values(Content::socials());
        $person = self::clean([
            '@type' => 'Person', '@id' => abs_url('') . '#person', 'name' => setting('owner_name'), 'jobTitle' => setting('owner_title'),
            'affiliation' => setting('owner_institution') !== '' ? ['@type' => 'Organization', 'name' => setting('owner_institution')] : '',
            'image' => self::img(setting('portrait')), 'url' => abs_url(''), 'sameAs' => $same,
        ]);
        $site = self::clean([
            '@type' => 'WebSite', '@id' => abs_url('') . '#website', 'name' => setting('site_name'), 'url' => abs_url(''),
            'description' => setting('meta_description'), 'inLanguage' => 'en', 'publisher' => ['@id' => abs_url('') . '#person'],
        ]);
        return [$site, $person];
    }

    public static function article(array $p): array
    {
        return self::clean([
            '@type' => 'BlogPosting', 'headline' => mb_substr($p['title'], 0, 110), 'description' => $p['excerpt'] ?: excerpt((string)$p['body'], 155),
            'image' => self::img($p['cover_image'] ?? ''), 'datePublished' => self::iso($p['published_at'] ?? $p['created_at'] ?? null), 'dateModified' => self::iso($p['updated_at'] ?? null),
            'author' => self::clean(['@type' => 'Person', 'name' => Content::author($p)['name'], 'url' => Content::author($p)['user'] ? '' : abs_url('about')]),
            'publisher' => ['@type' => 'Person', 'name' => setting('owner_name')], 'mainEntityOfPage' => abs_url('blog/' . $p['slug']),
            'keywords' => $p['tags'] ?? '',
        ]);
    }

    public static function product(array $p): array
    {
        $stock = array_sum(array_map(fn($v) => (int)$v['stock'], $p['variants'] ?? []));
        $img = array_map(fn($i) => self::img($i['path']), $p['images'] ?? []);
        return self::clean([
            '@type' => 'Product', 'name' => $p['name'], 'description' => $p['tagline'] ?: excerpt((string)$p['description'], 200), 'image' => $img,
            'sku' => 'P' . $p['id'], 'category' => $p['category'] ?? '', 'brand' => ['@type' => 'Brand', 'name' => setting('site_name')],
            'offers' => [
                '@type' => 'Offer', 'url' => abs_url('shop/' . $p['slug']), 'priceCurrency' => 'NGN', 'price' => (string)(int)$p['price'],
                'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ]);
    }

    public static function course(array $c, array $rating): array
    {
        $o = [
            '@type' => 'Course', 'name' => $c['title'], 'description' => $c['subtitle'] ?: excerpt((string)$c['description'], 200), 'url' => abs_url('learn/' . $c['slug']),
            'provider' => ['@type' => 'Person', 'name' => setting('owner_name'), 'url' => abs_url('about')], 'educationalLevel' => $c['level'] ?? '', 'inLanguage' => 'en',
            'image' => self::img($c['cover_image'] ?? ''),
            'offers' => ['@type' => 'Offer', 'category' => (int)$c['price'] > 0 ? 'Paid' : 'Free', 'priceCurrency' => 'NGN', 'price' => (string)(int)$c['price'], 'url' => abs_url('learn/' . $c['slug'])],
            'hasCourseInstance' => ['@type' => 'CourseInstance', 'courseMode' => 'online', 'courseWorkload' => 'PT' . max(1, (int)($c['minutes'] ?? 0)) . 'M'],
        ];
        if (($rating['count'] ?? 0) > 0) {
            $o['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => (string)$rating['avg'], 'reviewCount' => (string)$rating['count'], 'bestRating' => '5'];
        }
        return self::clean($o);
    }

    public static function event(array $e): array
    {
        $online = stripos((string)$e['location'], 'online') !== false;
        return self::clean([
            '@type' => 'Event', 'name' => $e['title'], 'startDate' => $e['start_date'], 'endDate' => $e['end_date'] ?: $e['start_date'], 'description' => (string)$e['summary'],
            'eventAttendanceMode' => $online ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode', 'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => $online ? ['@type' => 'VirtualLocation', 'url' => $e['url'] ?: abs_url('events')] : self::clean(['@type' => 'Place', 'name' => $e['location'] ?: 'To be announced', 'address' => $e['location'] ?: 'To be announced']),
            'organizer' => ['@type' => 'Person', 'name' => setting('owner_name')], 'url' => $e['url'] ?: abs_url('events'),
        ]);
    }

    /** BreadcrumbList from the address: Home › Section › This page. */
    public static function breadcrumbs(string $title): ?array
    {
        $seg = array_values(array_filter(explode('/', request_path())));
        if (!$seg) {
            return null;
        }
        $names = ['blog' => 'Blog', 'shop' => 'Shop', 'learn' => 'Learn', 'news' => 'News', 'toolbox' => 'Free tools', 'resources' => 'Free resources', 'events' => 'Events', 'press' => 'In the media', 'services' => 'Services', 'research' => 'Research', 'about' => 'About', 'speaking' => 'Speaking', 'contact' => 'Contact'];
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => abs_url('')]];
        if (isset($names[$seg[0]])) {
            $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $names[$seg[0]], 'item' => abs_url($seg[0])];
        }
        if (count($seg) > 1 || !isset($names[$seg[0]])) {
            $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $title !== '' ? $title : ucfirst($seg[count($seg) - 1])];
        }
        return count($items) > 1 ? ['@type' => 'BreadcrumbList', 'itemListElement' => $items] : null;
    }

    /** Renders JSON-LD blocks. The data is JSON-encoded with HTML-safe flags so it cannot break out of the script element. */
    public static function jsonLdTags(array $graph): string
    {
        $out = '';
        foreach ($graph as $obj) {
            if (!$obj) {
                continue;
            }
            $json = json_encode(['@context' => 'https://schema.org'] + $obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            $out .= '<script type="application/ld+json">' . $json . "</script>\n";
        }
        return $out;
    }

    /* ------------------------------------------------------------------ sitemap */

    /** @return array<int, array{0: string, 1: string}> [absolute address, lastmod date or ''] */
    public static function sitemapEntries(): array
    {
        $e = [];
        $add = function (string $path, ?string $mod = null) use (&$e): void {
            $e[abs_url($path)] = $mod ? date('Y-m-d', strtotime($mod)) : '';
        };
        foreach (['', 'about', 'research', 'blog', 'contact', 'services', 'speaking', 'press', 'events', 'resources', 'toolbox'] as $p) {
            $add($p);
        }
        $try = function (callable $fn): void {
            try {
                $fn();
            } catch (Throwable) {
                // table from a later update not installed yet
            }
        };
        foreach (Db::all("SELECT slug, updated_at FROM pages") as $pg) {
            $add($pg['slug'], $pg['updated_at']);
        }
        foreach (Db::all("SELECT slug, updated_at, published_at FROM posts WHERE status = 'published' AND is_sample = 0 AND is_visible = 1 AND (published_at IS NULL OR published_at <= NOW()) ORDER BY id DESC LIMIT 5000") as $p) {
            $add('blog/' . $p['slug'], $p['updated_at']);
        }
        if (module_enabled('shop')) {
            $add('shop');
            $try(function () use ($add) {
                foreach (Db::all("SELECT slug, updated_at FROM products WHERE status = 'active' AND is_sample = 0") as $p) {
                    $add('shop/' . $p['slug'], $p['updated_at']);
                }
                foreach (Db::all("SELECT c.slug FROM shop_categories c WHERE c.is_visible = 1 AND EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id AND p.status = 'active' AND p.is_sample = 0)") as $c) {
                    $add('shop/category/' . $c['slug']);
                }
            });
        }
        if (module_enabled('learn')) {
            $add('learn');
            $try(function () use ($add) {
                foreach (Db::all("SELECT id, slug, updated_at FROM courses WHERE status = 'published' AND is_sample = 0") as $c) {
                    $add('learn/' . $c['slug'], $c['updated_at']);
                    foreach (Db::all("SELECT id, updated_at FROM lessons WHERE course_id = ? AND status = 'published' AND is_free_preview = 1", [$c['id']]) as $l) {
                        $add('learn/' . $c['slug'] . '/lesson/' . $l['id'], $l['updated_at'] ?? null);
                    }
                }
            });
        }
        if (NewsRepo::enabled()) {
            foreach (['news', 'news/nobel', 'news/sources'] as $p) {
                $add($p);
            }
        }
        $try(function () use ($add) {
            foreach (Db::all("SELECT id, created_at FROM toolbox_links WHERE is_visible = 1 AND link_status <> 'broken' AND notes IS NOT NULL AND notes <> ''") as $t) {
                $add('toolbox/' . $t['id'], $t['created_at']);
            }
        });
        $out = [];
        foreach ($e as $loc => $mod) {
            $out[] = [$loc, $mod];
        }
        return $out;
    }

    /* ------------------------------------------------------------------ IndexNow (Bing, Yandex, Naver, Seznam…) */

    public static function indexNowKey(): string
    {
        $k = Settings::get('indexnow_key');
        if (!preg_match('/^[a-f0-9]{32}$/', $k)) {
            $k = bin2hex(random_bytes(16));
            Settings::set('indexnow_key', $k);
        }
        return $k;
    }

    /**
     * Tells IndexNow-enabled search engines that these pages are new or changed.
     * @return array{ok: bool, code: int, message: string}
     */
    public static function indexNow(array $urls): array
    {
        if (!self::indexingAllowed()) {
            return ['ok' => false, 'code' => 0, 'message' => 'Indexing is off on this installation (it is not set to "live website" mode).'];
        }
        $host = (string)parse_url(abs_url(''), PHP_URL_HOST);
        if ($host === '' || $host === 'localhost' || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host) || !function_exists('curl_init')) {
            return ['ok' => false, 'code' => 0, 'message' => 'Set APP_URL in config/.env to your public https address first (and make sure PHP cURL is enabled).'];
        }
        $key = self::indexNowKey();
        $body = json_encode(['host' => $host, 'key' => $key, 'keyLocation' => abs_url($key . '.txt'), 'urlList' => array_slice(array_values($urls), 0, 10000)]);
        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8']]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        $ok = $code === 200 || $code === 202;
        if ($ok) {
            Settings::set('indexnow_last', date('Y-m-d H:i:s') . ' (' . count($urls) . ' addresses)');
        }
        return ['ok' => $ok, 'code' => $code, 'message' => $ok ? 'Sent.' : ($err !== '' ? $err . (stripos($err, 'certificate') !== false ? ' (on WAMP set curl.cainfo in php.ini; a live Ubuntu server normally works)' : '') : "The service answered HTTP $code.")];
    }
}

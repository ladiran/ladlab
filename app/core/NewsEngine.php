<?php
declare(strict_types=1);

/**
 * The science-news pipeline: fetch → clean → verify → filter → de-duplicate → cluster → decide "breaking" → publish,
 * then keep checking published stories. Every automatic decision is written to news_log.
 *
 * Nothing here rewrites or summarises publisher text: only the publisher's own headline and feed excerpt are stored.
 */
final class NewsEngine
{
    public const TOPICS = [
        'climate' => 'Climate & weather', 'space' => 'Space', 'physics' => 'Physics', 'earth' => 'Earth & oceans',
        'life' => 'Life & health', 'tech' => 'Technology', 'nobel' => 'Nobel Prize', 'other' => 'More science',
    ];
    public const REGIONS = ['nigeria' => 'Nigeria', 'africa' => 'Africa', 'world' => 'World'];
    public const TIER_LABELS = [1 => 'Tier 1 · Official source', 2 => 'Tier 2 · Reputable press', 3 => 'Tier 3 · Press release'];

    private const STOPWORDS = ['the', 'and', 'for', 'with', 'from', 'that', 'this', 'are', 'was', 'were', 'has', 'have', 'had', 'its', 'their', 'into', 'over', 'new', 'how', 'why', 'what', 'who', 'can', 'will', 'may', 'more', 'than', 'about', 'after', 'could', 'says', 'say', 'not', 'but', 'out', 'all', 'one', 'two', 'first', 'study', 'scientists', 'researchers'];

    private static array $kwCache = [];

    /* ------------------------------------------------------------------ settings & logging */

    public static function opt(string $key): int
    {
        return max(0, (int)Settings::get($key));
    }

    /** How often the news is refreshed automatically (Admin → News rules). Default every 2 hours. */
    public static function intervalSeconds(): int
    {
        return max(1, min(24, (int)Settings::get('news_interval_hours', '2'))) * 3600;
    }

    /** Unix time of the next automatic update (a failed or skipped attempt is retried after 10 minutes). */
    public static function nextDueAt(): int
    {
        $last = strtotime(Settings::get('news_last_run_at')) ?: 0;
        $attempt = strtotime(Settings::get('news_last_attempt_at')) ?: 0;
        return max($last + self::intervalSeconds(), $attempt + 600);
    }

    public static function due(): bool
    {
        return module_enabled('news') && !self::paused() && time() >= self::nextDueAt();
    }

    /** Runs the update only if it is due (or when forced). This is what the schedule and the visitor trigger call. */
    public static function runIfDue(bool $force = false): array
    {
        if (!$force && !self::due()) {
            return ['skipped' => 'Not due yet: next automatic update at ' . date('H:i', self::nextDueAt()) . '.'];
        }
        Settings::set('news_last_attempt_at', date('Y-m-d H:i:s'));
        return self::run();
    }

    public static function paused(): bool
    {
        return Settings::get('news_paused') === '1';
    }

    public static function log(string $event, string $detail = '', ?int $itemId = null, ?int $sourceId = null, string $actor = 'auto'): void
    {
        try {
            Db::insert('news_log', [
                'item_id' => $itemId, 'source_id' => $sourceId, 'event' => substr($event, 0, 24),
                'detail' => mb_substr($detail, 0, 500), 'actor' => $actor,
            ]);
        } catch (Throwable) {
            // logging must never break the pipeline
        }
    }

    /** Case-insensitive "word starts with" regex built from a settings list. */
    private static function kw(string $settingKey): ?string
    {
        if (!array_key_exists($settingKey, self::$kwCache)) {
            $words = nl_lines(Settings::get($settingKey));
            self::$kwCache[$settingKey] = $words
                ? '/(?<![\p{L}\p{N}])(?:' . implode('|', array_map(fn($w) => preg_quote(mb_strtolower($w), '/'), $words)) . ')/iu'
                : null;
        }
        return self::$kwCache[$settingKey];
    }

    /** Public access to a keyword regex (used by the source test screen). */
    public static function kw2(string $settingKey): ?string
    {
        return self::kw($settingKey);
    }

    public static function clearCaches(): void
    {
        self::$kwCache = [];
    }

    /* ------------------------------------------------------------------ the run */

    /** Fetch every active source, process stories, run checks. Returns a summary array. */
    public static function run(): array
    {
        if (self::paused()) {
            return ['skipped' => 'News is paused (kill-switch is on).'];
        }
        $lock = fopen(ROOT . '/storage/news.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            return ['skipped' => 'A news update is already running.'];
        }
        @set_time_limit(280);
        self::clearCaches();

        $stats = ['sources' => 0, 'failed' => 0, 'new' => 0, 'breaking' => 0, 'filtered' => 0, 'duplicates' => 0, 'rejected' => 0, 'hidden' => 0, 'updated' => 0, 'clusters' => []];
        $started = microtime(true);

        // End probation for sources that have behaved.
        foreach (Db::all("SELECT * FROM news_sources WHERE status = 'probation' AND probation_until IS NOT NULL AND probation_until <= NOW() AND consecutive_failures < 3") as $s) {
            Db::update('news_sources', ['status' => 'active'], 'id = ?', [$s['id']]);
            self::log('source_active', 'Probation finished; ' . $s['name'] . ' is now fully active.', null, (int)$s['id']);
        }

        foreach (Db::all("SELECT * FROM news_sources WHERE status IN ('active', 'probation') ORDER BY id") as $src) {
            $stats['sources']++;
            try {
                self::fetchSource($src, $stats);
            } catch (Throwable $e) {
                self::fail($src, 'Internal error: ' . $e->getMessage(), $stats);
            }
        }

        // Re-evaluate "breaking" for anything fetched recently (corroboration can arrive later).
        $recent = Db::all("SELECT DISTINCT cluster_id FROM news_items WHERE cluster_id IS NOT NULL AND status = 'published' AND fetched_at > (NOW() - INTERVAL 6 HOUR)");
        foreach ($recent as $r) {
            $stats['clusters'][(int)$r['cluster_id']] = true;
        }
        foreach (array_keys($stats['clusters']) as $cid) {
            if (self::evaluateBreaking((int)$cid)) {
                $stats['breaking']++;
            }
        }

        $checked = self::recheck($stats);
        self::refreshNobel(false);
        Db::run('DELETE FROM news_log WHERE created_at < (NOW() - INTERVAL 60 DAY)');

        $summary = sprintf(
            '%d sources checked (%d failed): %d new stories, %d breaking now, %d filtered out, %d already seen, %d rejected (unsafe link), %d re-checked, %d hidden, %d updated. %.1fs',
            $stats['sources'], $stats['failed'], $stats['new'], $stats['breaking'], $stats['filtered'], $stats['duplicates'], $stats['rejected'], $checked, $stats['hidden'], $stats['updated'], microtime(true) - $started
        );
        Settings::set('news_last_run_at', date('Y-m-d H:i:s'));
        Settings::set('news_last_run', $summary);
        self::log('run', $summary);
        self::digestIfDue();

        flock($lock, LOCK_UN);
        fclose($lock);
        unset($stats['clusters']);
        $stats['summary'] = $summary;
        return $stats;
    }

    /* ------------------------------------------------------------------ one source */

    private static function fail(array $src, string $message, array &$stats): void
    {
        $stats['failed']++;
        $n = (int)$src['consecutive_failures'] + 1;
        $data = ['consecutive_failures' => $n, 'last_fetch_at' => date('Y-m-d H:i:s'), 'last_error' => mb_substr($message, 0, 290)];
        if ($n >= 8) {
            $data['status'] = 'paused';
            $data['paused_reason'] = 'Auto-paused after ' . $n . ' failed attempts in a row. Last error: ' . mb_substr($message, 0, 120);
            self::log('source_paused', $src['name'] . ': ' . $data['paused_reason'], null, (int)$src['id']);
        }
        Db::update('news_sources', $data, 'id = ?', [$src['id']]);
    }

    private static function fetchSource(array $src, array &$stats): void
    {
        $resp = Http::get($src['feed_url'], ['etag' => $src['etag'], 'last_modified' => $src['last_modified'], 'timeout' => 15]);
        if ($resp['code'] === 304) {
            Db::update('news_sources', ['consecutive_failures' => 0, 'last_fetch_at' => date('Y-m-d H:i:s'), 'last_success_at' => date('Y-m-d H:i:s'), 'last_error' => ''], 'id = ?', [$src['id']]);
            return;
        }
        if ($resp['error'] !== '' && $resp['code'] < 200) {
            self::fail($src, $resp['error'], $stats);
            return;
        }
        if ($resp['code'] < 200 || $resp['code'] >= 300) {
            self::fail($src, 'The server answered HTTP ' . $resp['code'], $stats);
            return;
        }
        try {
            $items = FeedParser::parse($resp['body']);
        } catch (RuntimeException $e) {
            self::fail($src, $e->getMessage(), $stats);
            return;
        }

        $firstFetch = $src['last_success_at'] === null;
        $candidates = self::prepare($src, $items, $stats);

        // Flood guard: a sudden surge of unseen stories can mean a hijacked or broken feed.
        $new = array_filter($candidates, fn($c) => !Db::val('SELECT 1 FROM news_items WHERE url_hash = ?', [$c['url_hash']]));
        $limit = max(10, self::opt('news_flood_limit'));
        if (!$firstFetch && count($new) > $limit) {
            $reason = 'Auto-paused: ' . count($new) . ' new stories arrived at once (limit ' . $limit . '). This can mean a hijacked or broken feed. Nothing from this batch was published.';
            Db::update('news_sources', ['status' => 'paused', 'paused_reason' => $reason, 'last_fetch_at' => date('Y-m-d H:i:s'), 'last_error' => ''], 'id = ?', [$src['id']]);
            self::log('source_paused', $src['name'] . ': ' . $reason, null, (int)$src['id']);
            $stats['failed']++;
            return;
        }

        Db::update('news_sources', [
            'consecutive_failures' => 0, 'last_fetch_at' => date('Y-m-d H:i:s'), 'last_success_at' => date('Y-m-d H:i:s'), 'last_error' => '',
            'etag' => mb_substr($resp['headers']['etag'] ?? '', 0, 250), 'last_modified' => mb_substr($resp['headers']['last-modified'] ?? '', 0, 95),
        ], 'id = ?', [$src['id']]);

        if ($firstFetch) {
            usort($candidates, fn($a, $b) => $b['published'] <=> $a['published']);
            $candidates = array_slice($candidates, 0, 25);
        }
        // Oldest first so clustering sees stories in the order they happened.
        usort($candidates, fn($a, $b) => $a['published'] <=> $b['published']);
        foreach ($candidates as $c) {
            self::store($src, $c, $stats);
        }
    }

    /**
     * Cleans and validates parsed items. Rejections are counted, never silently published.
     * @return array<int, array> candidates ready to store
     */
    private static function prepare(array $src, array $items, array &$stats): array
    {
        $domains = array_filter(array_map('trim', explode(',', strtolower($src['domains']))));
        $maxAge = time() - max(1, self::opt('news_max_age_days')) * 86400;
        $blocked = array_column(Db::all('SELECT term FROM news_blocked_terms'), 'term');
        $science = $src['topic_filter'] ? self::kw('news_keywords_science') : null;
        $out = [];
        $rejectedSample = '';

        foreach ($items as $it) {
            $link = self::normaliseUrl($it['link']);
            if ($link === null) {
                $stats['rejected']++;
                continue;
            }
            $host = strtolower((string)parse_url($link, PHP_URL_HOST));
            $okDomain = false;
            foreach ($domains as $d) {
                if ($host === $d || str_ends_with($host, '.' . $d)) {
                    $okDomain = true;
                    break;
                }
            }
            if (!$okDomain) {
                $stats['rejected']++;
                $rejectedSample = $rejectedSample ?: $host;
                continue;
            }
            $title = trim(mb_substr($it['title'], 0, 300));
            if (mb_strlen($title) < 8) {
                $stats['filtered']++;
                continue;
            }
            $summary = $it['summary'] !== $title ? excerpt($it['summary'], 320) : '';
            $published = $it['published'] ?? time();
            if ($published > time() + 600) {
                $published = time();
            }
            if ($published < $maxAge) {
                continue; // too old: ignored quietly
            }
            $hay = mb_strtolower($title . ' ' . $summary);
            $isBlocked = false;
            foreach ($blocked as $term) {
                if ($term !== '' && str_contains($hay, mb_strtolower($term))) {
                    $isBlocked = true;
                    break;
                }
            }
            if ($isBlocked || self::isOpinion($title, $link)) {
                $stats['filtered']++;
                continue;
            }
            if ($science !== null && !preg_match($science, $title . ' ' . $summary)) {
                $stats['filtered']++;
                continue;
            }
            $image = '';
            if ($src['show_images'] && preg_match('#^https://#i', $it['image']) && strlen($it['image']) < 790) {
                $image = $it['image'];
            }
            $out[] = [
                'title' => $title, 'summary' => $summary, 'url' => $link, 'published' => $published, 'image' => $image,
                'url_hash' => sha1($link), 'title_hash' => sha1(self::normTitle($title)),
            ];
        }
        if ($rejectedSample !== '') {
            self::log('rejected', $src['name'] . ': some stories link to "' . $rejectedSample . '", which is not one of this source’s domains (' . $src['domains'] . '). They were not published.', null, (int)$src['id']);
        }
        return $out;
    }

    /** Only http(s) links; strips tracking parameters and fragments. */
    public static function normaliseUrl(string $url): ?string
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url) || strlen($url) > 790) {
            return null;
        }
        $p = parse_url($url);
        if (!$p || empty($p['host']) || isset($p['user']) || isset($p['pass'])) {
            return null;
        }
        $query = '';
        if (!empty($p['query'])) {
            parse_str($p['query'], $q);
            foreach (array_keys($q) as $k) {
                if (preg_match('/^(utm_|fbclid|gclid|mc_|ref$|cmpid|ocid|taid|at_)/i', (string)$k)) {
                    unset($q[$k]);
                }
            }
            $query = $q ? '?' . http_build_query($q) : '';
        }
        return strtolower($p['scheme']) . '://' . strtolower($p['host']) . (isset($p['port']) ? ':' . $p['port'] : '') . ($p['path'] ?? '/') . $query;
    }

    private static function isOpinion(string $title, string $url): bool
    {
        return (bool)preg_match('~/(opinions?|editorials?|comment|commentary|op-ed|viewpoints?|columns?|letters)/~i', (string)parse_url($url, PHP_URL_PATH))
            || (bool)preg_match('/^\s*(opinion|editorial|op-ed|viewpoint|column|letters?|podcast|video)\s*[:\-–|]/i', $title);
    }

    private static function normTitle(string $t): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($t))));
    }

    /** @return string[] significant lower-case word stems */
    public static function tokens(string $t): array
    {
        $words = preg_split('/\s+/', self::normTitle($t), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($words as $w) {
            if (mb_strlen($w) < 3 || in_array($w, self::STOPWORDS, true)) {
                continue;
            }
            $out[preg_replace('/(ing|ed|es|s)$/u', '', $w)] = true;
        }
        return array_keys($out);
    }

    public static function similarity(string $a, string $b): float
    {
        $x = self::tokens($a);
        $y = self::tokens($b);
        if (!$x || !$y) {
            return 0.0;
        }
        $inter = count(array_intersect($x, $y));
        return $inter / count(array_unique(array_merge($x, $y)));
    }

    /* ------------------------------------------------------------------ storing a story */

    private static function classify(string $title, string $summary, string $default, string $srcRegion): array
    {
        $topics = [
            'nobel' => '/nobel/i',
            'climate' => '/\b(climate|weather|rainfall|monsoon|harmattan|drought|flood|hurricane|cyclone|typhoon|atmospher|ozone|global warming|heatwave|meteorolog|el ni[nñ]o|sea level|carbon|emissions?)/i',
            'space' => '/\b(nasa|esa|spacecraft|space station|space agency|astronaut|rocket|satellite|telescope|galax|planet|moon|mars|comet|asteroid|cosmic|black hole|solar system|launch)/i',
            'physics' => '/\b(physic|quantum|particle|laser|superconduct|fusion|higgs|neutrino|photon|electron|magnet|relativity|dark matter|semiconductor)/i',
            'earth' => '/\b(earthquake|volcan|seismic|tsunami|geolog|ocean|glacier|ice sheet|fossil|mineral)/i',
            'life' => '/\b(health|disease|virus|vaccine|cancer|brain|gene|dna|cell|species|biolog|medic|malaria|bacteria|evolution|animal|plant)/i',
            'tech' => '/\b(technolog|robot|artificial intelligence|\bai\b|computer|software|battery|chip|engineer|innovation|drone)/i',
        ];
        $topic = null;
        foreach ($topics as $t => $re) {
            if (preg_match($re, $title)) {
                $topic = $t;
                break;
            }
        }
        if ($topic === null) {
            foreach ($topics as $t => $re) {
                if ($t !== 'nobel' && preg_match($re, $summary)) {
                    $topic = $t;
                    break;
                }
            }
        }
        $topic = $topic ?? ($default !== '' && isset(self::TOPICS[$default]) ? $default : 'other');
        $region = $srcRegion;
        if ($srcRegion !== 'nigeria' && preg_match('/\b(nigeria|nigerian|nigerians|lagos|abuja|nimet|nasrda)\b/i', $title)) {
            $region = 'nigeria';
        }
        return [$topic, $region];
    }

    private static function store(array $src, array $c, array &$stats): void
    {
        $existing = Db::one('SELECT id, title, status FROM news_items WHERE url_hash = ?', [$c['url_hash']]);
        if ($existing) {
            // Same story seen again: if the publisher changed the headline, follow it and mark "Updated".
            if ($existing['status'] === 'published' && $existing['title'] !== $c['title'] && self::normTitle($existing['title']) !== self::normTitle($c['title'])) {
                Db::update('news_items', ['title' => $c['title'], 'was_updated' => 1, 'title_hash' => $c['title_hash']], 'id = ?', [$existing['id']]);
                self::log('updated', 'Headline changed at the source: "' . mb_substr($existing['title'], 0, 120) . '" → "' . mb_substr($c['title'], 0, 120) . '"', (int)$existing['id'], (int)$src['id']);
                $stats['updated']++;
            } else {
                $stats['duplicates']++;
            }
            return;
        }
        if (Db::val('SELECT 1 FROM news_items WHERE title_hash = ? AND source_id = ?', [$c['title_hash'], $src['id']])) {
            $stats['duplicates']++;
            return;
        }

        [$topic, $region] = self::classify($c['title'], $c['summary'], (string)$src['default_topic'], (string)$src['region']);
        $id = Db::insert('news_items', [
            'source_id' => $src['id'], 'url_hash' => $c['url_hash'], 'title_hash' => $c['title_hash'], 'title' => $c['title'],
            'summary' => $c['summary'], 'url' => $c['url'], 'image_url' => $c['image'],
            'published_at' => date('Y-m-d H:i:s', $c['published']), 'region' => $region, 'topic' => $topic,
        ]);
        $stats['new']++;
        self::log('published', ($src['status'] === 'probation' ? '[probation] ' : '') . self::TIER_LABELS[(int)$src['tier']] . ' · ' . $src['name'] . ': ' . mb_substr($c['title'], 0, 200), $id, (int)$src['id']);

        // Cluster with the same story from other sources (similar headline in the last 3 days).
        $cluster = null;
        $best = 0.0;
        $mine = self::tokens($c['title']);
        $rows = Db::all("SELECT id, cluster_id, title, title_hash FROM news_items WHERE id <> ? AND source_id <> ? AND status = 'published' AND published_at > (NOW() - INTERVAL 3 DAY) ORDER BY id DESC LIMIT 800", [$id, $src['id']]);
        foreach ($rows as $r) {
            $sim = $r['title_hash'] === $c['title_hash'] ? 1.0 : self::similarity($c['title'], $r['title']);
            $shared = count(array_intersect($mine, self::tokens($r['title'])));
            if ($sim >= 0.5 && $shared >= 3 && $sim > $best) {
                $best = $sim;
                $cluster = (int)($r['cluster_id'] ?: $r['id']);
                if (!$r['cluster_id']) {
                    Db::update('news_items', ['cluster_id' => $cluster], 'id = ?', [$r['id']]);
                }
            }
        }
        $cluster = $cluster ?? $id;
        Db::update('news_items', ['cluster_id' => $cluster], 'id = ?', [$id]);
        self::recomputePrimary($cluster);
        $stats['clusters'][$cluster] = true;
    }

    /** One representative story per cluster: best tier first, then earliest. */
    public static function recomputePrimary(int $cluster): void
    {
        $rows = Db::all("SELECT i.id FROM news_items i JOIN news_sources s ON s.id = i.source_id WHERE i.cluster_id = ? AND i.status = 'published' ORDER BY s.tier ASC, i.published_at ASC, i.id ASC", [$cluster]);
        Db::update('news_items', ['is_primary' => 0], 'cluster_id = ?', [$cluster]);
        if ($rows) {
            Db::update('news_items', ['is_primary' => 1], 'id = ?', [$rows[0]['id']]);
        }
    }

    /* ------------------------------------------------------------------ "breaking" */

    /**
     * BREAKING requires BOTH a breaking signal in the headline (or an all-breaking source such as the USGS significant-quake feed)
     * AND either a Tier 1 authority that can break news, or enough independent Tier 1/2 sources within the window.
     * Tier 3 press releases and sources on probation never count. The story must also be fresh (inside the ticker window).
     * Returns true when the cluster is breaking after evaluation.
     */
    public static function evaluateBreaking(int $cluster): bool
    {
        $items = Db::all(
            "SELECT i.id, i.title, i.summary, i.published_at, i.is_breaking, i.is_primary, s.id AS sid, s.name, s.tier, s.can_break, s.all_breaking, s.status AS sstatus
             FROM news_items i JOIN news_sources s ON s.id = i.source_id
             WHERE i.cluster_id = ? AND i.status = 'published'", [$cluster]
        );
        if (!$items) {
            return false;
        }
        $primary = null;
        foreach ($items as $i) {
            if ((int)$i['is_primary'] === 1) {
                $primary = $i;
            }
        }
        $primary = $primary ?? $items[0];

        $signal = self::kw('news_keywords_breaking');
        $minPub = min(array_map(fn($i) => strtotime($i['published_at']), $items));
        $fresh = $minPub >= time() - max(1, self::opt('news_ticker_hours')) * 3600;

        $reason = '';
        if ($fresh) {
            // Tier 1 route
            foreach ($items as $i) {
                if ((int)$i['tier'] === 1 && (int)$i['can_break'] === 1 && $i['sstatus'] === 'active') {
                    $hit = (int)$i['all_breaking'] === 1 || ($signal && preg_match($signal, $i['title']));
                    if ($hit) {
                        $reason = 'Tier 1 authority: ' . $i['name'];
                        break;
                    }
                }
            }
            // Corroboration route
            if ($reason === '' && $signal && array_filter($items, fn($i) => preg_match($signal, $i['title']))) {
                $window = max(1, self::opt('news_breaking_window_hours')) * 3600;
                $sources = [];
                foreach ($items as $i) {
                    if ((int)$i['tier'] <= 2 && $i['sstatus'] === 'active' && strtotime($i['published_at']) - $minPub <= $window) {
                        $sources[(int)$i['sid']] = $i['name'];
                    }
                }
                if (count($sources) >= max(2, self::opt('news_breaking_min_sources'))) {
                    $reason = count($sources) . ' independent sources within ' . self::opt('news_breaking_window_hours') . ' h: ' . implode(', ', $sources);
                }
            }
        }

        $was = (int)$primary['is_breaking'] === 1;
        Db::update('news_items', ['is_breaking' => 0], 'cluster_id = ?', [$cluster]);
        if ($reason !== '') {
            Db::update('news_items', ['is_breaking' => 1, 'breaking_at' => date('Y-m-d H:i:s')], 'id = ? AND breaking_at IS NULL', [$primary['id']]);
            Db::update('news_items', ['is_breaking' => 1], 'id = ?', [$primary['id']]);
            if (!$was) {
                self::log('breaking', 'Marked BREAKING (' . $reason . '): ' . mb_substr($primary['title'], 0, 200), (int)$primary['id'], (int)$primary['sid']);
            }
            return true;
        }
        if ($was) {
            self::log('breaking_off', 'No longer breaking: ' . mb_substr($primary['title'], 0, 200), (int)$primary['id'], (int)$primary['sid']);
        }
        return false;
    }

    /* ------------------------------------------------------------------ hide / restore */

    public static function hide(int $id, string $reason, string $by = 'auto'): void
    {
        $item = Db::one('SELECT id, cluster_id, source_id, title FROM news_items WHERE id = ?', [$id]);
        if (!$item) {
            return;
        }
        Db::update('news_items', ['status' => 'hidden', 'hidden_reason' => mb_substr($reason, 0, 190), 'hidden_by' => $by, 'is_primary' => 0, 'is_breaking' => 0], 'id = ?', [$id]);
        self::log('hidden', $reason . ' · ' . mb_substr($item['title'], 0, 160), $id, (int)$item['source_id'], $by);
        if ($item['cluster_id']) {
            self::recomputePrimary((int)$item['cluster_id']);
            self::evaluateBreaking((int)$item['cluster_id']);
        }
    }

    public static function restore(int $id, string $by = 'admin'): void
    {
        $item = Db::one('SELECT id, cluster_id, source_id, title FROM news_items WHERE id = ?', [$id]);
        if (!$item) {
            return;
        }
        Db::update('news_items', ['status' => 'published', 'hidden_reason' => '', 'hidden_by' => '', 'last_checked_at' => null], 'id = ?', [$id]);
        self::log('restored', mb_substr($item['title'], 0, 200), $id, (int)$item['source_id'], $by);
        if ($item['cluster_id']) {
            self::recomputePrimary((int)$item['cluster_id']);
            self::evaluateBreaking((int)$item['cluster_id']);
        }
    }

    /* ------------------------------------------------------------------ re-checking published stories */

    private static function recheck(array &$stats): int
    {
        $hours = max(1, self::opt('news_recheck_hours'));
        $rows = Db::all(
            "SELECT i.*, s.domains FROM news_items i JOIN news_sources s ON s.id = i.source_id
             WHERE i.status = 'published' AND i.published_at > (NOW() - INTERVAL $hours HOUR)
               AND (i.last_checked_at IS NULL OR i.last_checked_at < (NOW() - INTERVAL 6 HOUR))
               AND i.fetched_at < (NOW() - INTERVAL 30 MINUTE)
             ORDER BY i.last_checked_at IS NULL DESC, i.last_checked_at ASC LIMIT 25"
        );
        foreach ($rows as $i) {
            Db::update('news_items', ['last_checked_at' => date('Y-m-d H:i:s')], 'id = ?', [$i['id']]);
            $r = Http::get($i['url'], ['accept' => 'text/html,application/xhtml+xml', 'timeout' => 10, 'max_bytes' => 600 * 1024]);
            if ($r['code'] === 404 || $r['code'] === 410) {
                self::hide((int)$i['id'], 'The original page was removed by the source (HTTP ' . $r['code'] . ').');
                $stats['hidden']++;
                continue;
            }
            if ($r['code'] !== 200) {
                continue; // blocked, rate-limited or temporarily down: cannot verify, leave as is
            }
            $host = strtolower((string)parse_url($r['url'], PHP_URL_HOST));
            $ok = false;
            foreach (array_filter(array_map('trim', explode(',', strtolower($i['domains'])))) as $d) {
                if ($host === $d || str_ends_with($host, '.' . $d)) {
                    $ok = true;
                }
            }
            if (!$ok) {
                self::hide((int)$i['id'], 'The link now redirects away from the source’s own domain (' . $host . ').');
                $stats['hidden']++;
                continue;
            }
            $page = self::pageTitle($r['body']);
            if ($page === '') {
                continue;
            }
            $best = self::similarity($i['title'], $page);
            foreach (preg_split('/\s+[|\-–—:]\s+/u', $page) ?: [] as $part) {
                $best = max($best, self::similarity($i['title'], $part));
            }
            if (count(self::tokens($page)) < 3 || $best >= 0.6) {
                continue;
            }
            if ($best < 0.25) {
                self::hide((int)$i['id'], 'The headline on the original page is now very different from what we published (possible correction or retraction).');
                $stats['hidden']++;
            } else {
                $new = trim(mb_substr((string)(preg_split('/\s+[|]\s+/u', $page)[0] ?? $page), 0, 300));
                if ($new !== '' && $new !== $i['title']) {
                    Db::update('news_items', ['title' => $new, 'was_updated' => 1], 'id = ?', [$i['id']]);
                    self::log('updated', 'Headline updated from the original page: "' . mb_substr($i['title'], 0, 100) . '" → "' . mb_substr($new, 0, 100) . '"', (int)$i['id'], (int)$i['source_id']);
                    $stats['updated']++;
                }
            }
        }
        return count($rows);
    }

    private static function pageTitle(string $html): string
    {
        $head = substr($html, 0, 200000);
        if (preg_match('~<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']~i', $head, $m)
            || preg_match('~<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']~i', $head, $m)
            || preg_match('~<title[^>]*>(.*?)</title>~is', $head, $m)) {
            return trim((string)preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }
        return '';
    }

    /* ------------------------------------------------------------------ Nobel Prize data */

    public static function nobelCategories(): array
    {
        return ['phy' => ['Physics', 'physics'], 'che' => ['Chemistry', 'chemistry'], 'med' => ['Physiology or Medicine', 'medicine'],
            'lit' => ['Literature', 'literature'], 'pea' => ['Peace', 'peace'], 'eco' => ['Economic Sciences', 'economic-sciences']];
    }

    /** Downloads one year of prizes from the official NobelPrize.org API (CC0) into a small local cache. */
    public static function fetchNobelYear(int $year): bool
    {
        $r = Http::get('https://api.nobelprize.org/2.1/nobelPrizes?nobelPrizeYear=' . $year . '&limit=30', ['accept' => 'application/json', 'timeout' => 12, 'max_bytes' => 1024 * 1024]);
        if ($r['code'] !== 200) {
            return false;
        }
        $data = json_decode($r['body'], true);
        if (!is_array($data) || !isset($data['nobelPrizes']) || !is_array($data['nobelPrizes'])) {
            return false;
        }
        $out = [];
        foreach ($data['nobelPrizes'] as $p) {
            $href = '';
            foreach ($p['links'] ?? [] as $l) {
                if (($l['rel'] ?? '') === 'nobelPrize') {
                    $href = (string)$l['href'];
                }
            }
            if (!preg_match('~/nobelPrize/(\w{3})/\d{4}~', $href, $m)) {
                continue;
            }
            $laureates = [];
            foreach ($p['laureates'] ?? [] as $l) {
                $name = trim((string)($l['knownName']['en'] ?? ($l['orgName']['en'] ?? ($l['fullName']['en'] ?? ''))));
                if ($name !== '') {
                    $laureates[] = ['name' => $name, 'motivation' => trim((string)($l['motivation']['en'] ?? '')), 'portion' => (string)($l['portion'] ?? '')];
                }
            }
            $out[$m[1]] = [
                'full' => (string)($p['categoryFullName']['en'] ?? ($p['category']['en'] ?? '')),
                'date' => (string)($p['dateAwarded'] ?? ''),
                'laureates' => $laureates,
            ];
        }
        Db::run('INSERT INTO nobel_cache (year, payload, fetched_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE payload = VALUES(payload), fetched_at = NOW()', [$year, json_encode($out, JSON_UNESCAPED_UNICODE)]);
        return true;
    }

    /** Cached prize data for a year. Refreshes when stale (hourly in Oct–Dec of the current year, otherwise monthly). */
    public static function nobel(int $year, bool $allowFetch = true): ?array
    {
        $row = Db::one('SELECT payload, fetched_at FROM nobel_cache WHERE year = ?', [$year]);
        $current = (int)date('Y');
        $ttl = ($year === $current && (int)date('n') >= 10) ? 3600 : ($year === $current ? 86400 : 30 * 86400);
        $stale = !$row || strtotime($row['fetched_at']) < time() - $ttl;
        if ($stale && $allowFetch) {
            if (self::fetchNobelYear($year)) {
                $row = Db::one('SELECT payload, fetched_at FROM nobel_cache WHERE year = ?', [$year]);
            }
        }
        return $row ? (json_decode($row['payload'], true) ?: []) : null;
    }

    private static function refreshNobel(bool $force): void
    {
        $year = (int)date('Y');
        if ($force || ((int)date('n') >= 10 && (int)date('n') <= 12)) {
            try {
                self::nobel($year, true);
            } catch (Throwable) {
                // optional extra: ignore failures
            }
        }
    }

    /* ------------------------------------------------------------------ daily digest */

    public static function digestIfDue(): void
    {
        if (Settings::get('news_digest') !== '1') {
            return;
        }
        $to = Settings::get('notify_email') ?: Settings::get('contact_email');
        if (!$to) {
            return;
        }
        $last = Settings::get('news_last_digest_at');
        if ($last !== '' && strtotime($last) > time() - 20 * 3600) {
            return;
        }
        $since = $last !== '' ? $last : date('Y-m-d H:i:s', time() - 86400);
        $count = fn(string $ev) => (int)Db::val('SELECT COUNT(*) FROM news_log WHERE event = ? AND created_at > ?', [$ev, $since]);
        $lines = [];
        $lines[] = 'News summary since ' . $since;
        $lines[] = '';
        $lines[] = 'Published: ' . $count('published') . ' · Breaking: ' . $count('breaking') . ' · Hidden automatically: ' . (int)Db::val("SELECT COUNT(*) FROM news_log WHERE event = 'hidden' AND actor = 'auto' AND created_at > ?", [$since]) . ' · Headline updates: ' . $count('updated');
        foreach (Db::all("SELECT detail FROM news_log WHERE event = 'breaking' AND created_at > ? ORDER BY id DESC LIMIT 10", [$since]) as $r) {
            $lines[] = '  BREAKING: ' . $r['detail'];
        }
        $hidden = Db::all("SELECT detail FROM news_log WHERE event = 'hidden' AND created_at > ? ORDER BY id DESC LIMIT 15", [$since]);
        if ($hidden) {
            $lines[] = '';
            $lines[] = 'Hidden stories and why:';
            foreach ($hidden as $r) {
                $lines[] = '  - ' . $r['detail'];
            }
        }
        $bad = Db::all("SELECT name, status, consecutive_failures, last_error, paused_reason FROM news_sources WHERE status = 'paused' OR consecutive_failures >= 3 ORDER BY name");
        if ($bad) {
            $lines[] = '';
            $lines[] = 'Sources needing attention:';
            foreach ($bad as $b) {
                $lines[] = '  - ' . $b['name'] . ' [' . $b['status'] . ']: ' . ($b['paused_reason'] ?: $b['last_error']);
            }
        }
        $open = (int)Db::val("SELECT COUNT(*) FROM news_reports WHERE status = 'open'");
        if ($open) {
            $lines[] = '';
            $lines[] = "Reader reports waiting: $open";
        }
        $lines[] = '';
        $lines[] = 'Review everything: ' . abs_url('admin/news');
        Mailer::send($to, '[' . Settings::get('site_name') . '] Daily news summary', implode("\n", $lines));
        Settings::set('news_last_digest_at', date('Y-m-d H:i:s'));
        self::log('digest', 'Daily summary emailed.');
    }
}

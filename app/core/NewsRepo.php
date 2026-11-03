<?php
declare(strict_types=1);

/** Public-side news queries. Everything shown to visitors goes through here. */
final class NewsRepo
{
    /** News section is live and not switched off by the kill-switch. */
    public static function enabled(): bool
    {
        return module_enabled('news') && !NewsEngine::paused();
    }

    private static function where(array $f, array &$params): string
    {
        $w = ["i.status = 'published'", 'i.is_primary = 1'];
        $region = (string)($f['region'] ?? '');
        if ($region === 'nigeria') {
            $w[] = "i.region = 'nigeria'";
        } elseif ($region === 'africa') {
            $w[] = "i.region IN ('nigeria', 'africa')";
        } elseif ($region === 'world') {
            $w[] = "i.region = 'world'";
        }
        if (!empty($f['topic']) && isset(NewsEngine::TOPICS[$f['topic']])) {
            $w[] = 'i.topic = ?';
            $params[] = $f['topic'];
        }
        if (($f['view'] ?? '') === 'breaking') {
            $w[] = 'i.is_breaking = 1';
            $w[] = 'i.published_at > (NOW() - INTERVAL 72 HOUR)';
        }
        if (!empty($f['q'])) {
            $w[] = '(i.title LIKE ? OR i.summary LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$f['q']) . '%';
            array_push($params, $like, $like);
        }
        return implode(' AND ', $w);
    }

    public static function list(array $f, int $limit, int $offset = 0): array
    {
        $params = [];
        $where = self::where($f, $params);
        $rows = Db::all(
            "SELECT i.*, s.name AS source_name, s.tier, s.kind, s.status AS source_status, s.show_images
             FROM news_items i JOIN news_sources s ON s.id = i.source_id
             WHERE $where ORDER BY i.published_at DESC, i.id DESC LIMIT " . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
        return self::withCorroboration($rows);
    }

    public static function count(array $f): int
    {
        $params = [];
        return (int)Db::val('SELECT COUNT(*) FROM news_items i WHERE ' . self::where($f, $params), $params);
    }

    /** Adds "reported by N sources" (names) to each row. */
    private static function withCorroboration(array $rows): array
    {
        $ids = array_values(array_unique(array_filter(array_map(fn($r) => (int)$r['cluster_id'], $rows))));
        $map = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            foreach (Db::all("SELECT i.cluster_id, s.name FROM news_items i JOIN news_sources s ON s.id = i.source_id WHERE i.cluster_id IN ($in) AND i.status = 'published' GROUP BY i.cluster_id, s.id, s.name", $ids) as $r) {
                $map[(int)$r['cluster_id']][] = $r['name'];
            }
        }
        foreach ($rows as &$r) {
            $r['reported_by'] = $map[(int)$r['cluster_id']] ?? [$r['source_name']];
        }
        return $rows;
    }

    /** Current breaking stories for the ticker (fresh, primary, published). */
    public static function ticker(): array
    {
        static $cache = null;
        if ($cache === null) {
            $hours = max(1, NewsEngine::opt('news_ticker_hours'));
            $cache = Db::all(
                "SELECT i.id, i.title, i.url, i.published_at, s.name AS source_name FROM news_items i JOIN news_sources s ON s.id = i.source_id
                 WHERE i.status = 'published' AND i.is_primary = 1 AND i.is_breaking = 1 AND i.published_at > (NOW() - INTERVAL $hours HOUR)
                 ORDER BY i.published_at DESC LIMIT 5"
            );
        }
        return $cache;
    }

    /**
     * What the scrolling bar under the header shows: breaking stories (red BREAKING bar) or, when there are none, the most
     * recent headlines (blue LATEST bar), unless switched off in News rules.
     * @return array{label: string, breaking: bool, items: array}
     */
    public static function bar(): array
    {
        $breaking = self::ticker();
        if ($breaking) {
            return ['label' => 'BREAKING', 'breaking' => true, 'items' => $breaking];
        }
        if (Settings::get('news_ticker_latest', '1') === '0') {
            return ['label' => '', 'breaking' => false, 'items' => []];
        }
        $rows = Db::all(
            "SELECT i.id, i.title, i.url, i.published_at, s.name AS source_name FROM news_items i JOIN news_sources s ON s.id = i.source_id
             WHERE i.status = 'published' AND i.is_primary = 1 AND i.published_at > (NOW() - INTERVAL 72 HOUR)
             ORDER BY i.published_at DESC, i.id DESC LIMIT 10"
        );
        return ['label' => 'LATEST', 'breaking' => false, 'items' => $rows];
    }

    public static function latest(int $n): array
    {
        return self::list([], $n);
    }

    /** Sources shown on the public policy page. */
    public static function publicSources(): array
    {
        return Db::all("SELECT * FROM news_sources WHERE status <> 'paused' ORDER BY tier, name");
    }
}

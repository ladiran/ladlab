<?php
declare(strict_types=1);

/**
 * Public-side content queries.
 *
 * Sample/placeholder rows (is_sample = 1) are shown only while a table has no real rows.
 * As soon as you add or edit a real item, the samples disappear by themselves.
 */
final class Content
{
    private const TABLES = ['publications', 'awards', 'talks', 'projects', 'testimonials', 'timeline', 'posts', 'services', 'speaking_topics', 'press_items', 'events', 'resources', 'toolbox_links'];

    public static function sampleFlag(string $table): int
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Unknown table');
        }
        return Db::val("SELECT 1 FROM `$table` WHERE is_sample = 0 LIMIT 1") ? 0 : 1;
    }

    /** Visible rows for a simple content table. */
    public static function visible(string $table, string $order = 'sort_order, id', int $limit = 0): array
    {
        $flag = self::sampleFlag($table);
        $sql = "SELECT * FROM `$table` WHERE is_visible = 1 AND is_sample = ? ORDER BY $order" . ($limit ? ' LIMIT ' . (int)$limit : '');
        return Db::all($sql, [$flag]);
    }

    public static function publications(array $f = []): array
    {
        $flag = self::sampleFlag('publications');
        $where = ['is_visible = 1', 'is_sample = ?'];
        $params = [$flag];
        if (!empty($f['year'])) {
            $where[] = 'year = ?';
            $params[] = (int)$f['year'];
        }
        if (!empty($f['type'])) {
            $where[] = 'type = ?';
            $params[] = (string)$f['type'];
        }
        if (!empty($f['q'])) {
            $where[] = '(title LIKE ? OR authors LIKE ? OR venue LIKE ? OR tags LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$f['q']) . '%';
            array_push($params, $like, $like, $like, $like);
        }
        return Db::all('SELECT * FROM publications WHERE ' . implode(' AND ', $where) . ' ORDER BY year DESC, sort_order, id DESC', $params);
    }

    public static function featuredPublications(int $limit = 3): array
    {
        $flag = self::sampleFlag('publications');
        $rows = Db::all('SELECT * FROM publications WHERE is_visible = 1 AND is_sample = ? AND is_featured = 1 ORDER BY year DESC, id DESC LIMIT ' . (int)$limit, [$flag]);
        return $rows ?: Db::all('SELECT * FROM publications WHERE is_visible = 1 AND is_sample = ? ORDER BY year DESC, id DESC LIMIT ' . (int)$limit, [$flag]);
    }

    public static function posts(int $limit, int $offset = 0, ?string $tag = null): array
    {
        $flag = self::sampleFlag('posts');
        $where = "status = 'published' AND published_at <= NOW() AND is_sample = ?";
        $params = [$flag];
        if ($tag) {
            $where .= ' AND FIND_IN_SET(?, REPLACE(tags, ", ", ",")) > 0';
            $params[] = $tag;
        }
        return Db::all("SELECT * FROM posts WHERE $where ORDER BY published_at DESC, id DESC LIMIT " . (int)$limit . ' OFFSET ' . (int)$offset, $params);
    }

    public static function countPosts(?string $tag = null): int
    {
        $flag = self::sampleFlag('posts');
        $where = "status = 'published' AND published_at <= NOW() AND is_sample = ?";
        $params = [$flag];
        if ($tag) {
            $where .= ' AND FIND_IN_SET(?, REPLACE(tags, ", ", ",")) > 0';
            $params[] = $tag;
        }
        return (int)Db::val("SELECT COUNT(*) FROM posts WHERE $where", $params);
    }

    /**
     * Name and contact shown with a blog post. Posts written by the site owner fall back to the owner's name and contact email;
     * guest articles always carry the registered name and email of the author (who agreed to it when submitting).
     * @return array{name: string, email: string, user: bool}
     */
    public static function author(array $p): array
    {
        $name = trim((string)($p['author_name'] ?? ''));
        $email = trim((string)($p['author_email'] ?? ''));
        $user = !empty($p['is_user_article']);
        if ($name === '' && !$user) {
            $name = setting('owner_name');
            $email = $email !== '' ? $email : setting('contact_email');
        }
        return ['name' => $name, 'email' => $email, 'user' => $user];
    }

    public static function post(string $slug): ?array
    {
        return Db::one("SELECT * FROM posts WHERE slug = ? AND status = 'published' AND published_at <= NOW()", [$slug]);
    }

    public static function allTags(): array
    {
        $flag = self::sampleFlag('posts');
        $tags = [];
        foreach (Db::all("SELECT tags FROM posts WHERE status = 'published' AND is_sample = ? AND tags <> ''", [$flag]) as $r) {
            foreach (explode(',', $r['tags']) as $t) {
                $t = trim($t);
                if ($t !== '') {
                    $tags[$t] = ($tags[$t] ?? 0) + 1;
                }
            }
        }
        arsort($tags);
        return $tags;
    }

    public static function activeCv(): ?array
    {
        return Db::one('SELECT * FROM cv_files WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    }

    public static function stats(): array
    {
        $out = [];
        for ($i = 1; $i <= 4; $i++) {
            $v = trim(Settings::get("stat{$i}_value"));
            if ($v !== '') {
                $out[] = ['value' => $v, 'label' => Settings::get("stat{$i}_label")];
            }
        }
        return $out;
    }

    public static function socials(): array
    {
        $labels = [
            'social_linkedin' => 'LinkedIn', 'social_x' => 'X', 'social_facebook' => 'Facebook', 'social_instagram' => 'Instagram',
            'social_youtube' => 'YouTube', 'social_scholar' => 'Google Scholar', 'social_orcid' => 'ORCID', 'social_researchgate' => 'ResearchGate',
        ];
        $out = [];
        foreach ($labels as $key => $label) {
            $u = trim(Settings::get($key));
            if ($u !== '' && preg_match('#^https?://#i', $u)) {
                $out[$label] = $u;
            }
        }
        return $out;
    }
}

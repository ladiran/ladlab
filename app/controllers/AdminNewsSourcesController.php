<?php
declare(strict_types=1);

/** Add, test, edit, pause/resume and remove news sources. */
final class AdminNewsSourcesController extends AdminBase
{
    public function index(): void
    {
        $rows = Db::all(
            "SELECT s.*, (SELECT COUNT(*) FROM news_items i WHERE i.source_id = s.id AND i.published_at > (NOW() - INTERVAL 24 HOUR)) AS items_24h,
                    (SELECT COUNT(*) FROM news_items i WHERE i.source_id = s.id) AS items_total
             FROM news_sources s ORDER BY s.status = 'paused', s.tier, s.name"
        );
        $this->page('admin/news_sources', ['title' => 'News sources', 'tab' => 'sources', 'rows' => $rows]);
    }

    public function form(?string $id = null): void
    {
        $row = null;
        if ($id !== null) {
            $row = Db::one('SELECT * FROM news_sources WHERE id = ?', [(int)$id]);
            if (!$row) {
                abort(404);
            }
        }
        $this->page('admin/news_source_form', ['title' => $row ? 'Edit source: ' . $row['name'] : 'Add a news source', 'tab' => 'sources', 'row' => $row, 'test' => null]);
    }

    /** Reads and validates the form. Returns [data, errors]. */
    private function collect(?array $existing): array
    {
        $in = fn(string $k) => trim((string)($_POST[$k] ?? ''));
        $errors = [];
        $d = [];
        $d['name'] = mb_substr($in('name'), 0, 120);
        $d['site_url'] = mb_substr($in('site_url'), 0, 300);
        $d['feed_url'] = mb_substr($in('feed_url'), 0, 590);
        $d['tier'] = in_array((int)$in('tier'), [1, 2, 3], true) ? (int)$in('tier') : 2;
        $d['kind'] = in_array($in('kind'), ['news', 'press_release', 'primary'], true) ? $in('kind') : 'news';
        $d['region'] = isset(NewsEngine::REGIONS[$in('region')]) ? $in('region') : 'world';
        $d['default_topic'] = isset(NewsEngine::TOPICS[$in('default_topic')]) ? $in('default_topic') : 'other';
        $d['is_official'] = isset($_POST['is_official']) ? 1 : 0;
        $d['can_break'] = isset($_POST['can_break']) ? 1 : 0;
        $d['all_breaking'] = isset($_POST['all_breaking']) ? 1 : 0;
        $d['topic_filter'] = isset($_POST['topic_filter']) ? 1 : 0;
        $d['show_images'] = isset($_POST['show_images']) ? 1 : 0;
        $d['notes'] = mb_substr($in('notes'), 0, 290);

        if ($d['name'] === '') {
            $errors[] = 'Please enter the source name.';
        }
        $local = Http::allowLocal();
        if (!preg_match($local ? '#^https?://\S+$#i' : '#^https://\S+$#i', $d['feed_url'])) {
            $errors[] = 'The feed address must start with https:// (secure feeds only).';
        }
        if ($d['site_url'] !== '' && !preg_match('#^https?://\S+$#i', $d['site_url'])) {
            $errors[] = 'The website address must start with https://';
        }
        // Allowed link domains: typed, else taken from the website or feed address.
        $domains = array_filter(array_map(fn($x) => strtolower(trim(preg_replace('#^(https?://)?(www\.)?#i', '', trim($x)), " /\t")), explode(',', $in('domains'))));
        if (!$domains) {
            foreach ([$d['site_url'], $d['feed_url']] as $u) {
                $h = strtolower((string)parse_url($u, PHP_URL_HOST));
                if ($h !== '') {
                    $domains[] = preg_replace('/^(www|feeds|rss)\./', '', $h);
                    break;
                }
            }
        }
        foreach ($domains as $dm) {
            if (!preg_match('/^[a-z0-9]([a-z0-9.\-]*[a-z0-9])?\.[a-z]{2,}$/', $dm) && !($local && $dm === 'localhost' || $local && preg_match('/^127\.|^localhost/', $dm))) {
                $errors[] = 'The domain "' . $dm . '" does not look valid. Use only the domain, e.g. nasa.gov';
            }
        }
        $d['domains'] = mb_substr(implode(',', array_unique($domains)), 0, 290);
        if ($d['domains'] === '') {
            $errors[] = 'Please enter the domain(s) this source’s story links must belong to (e.g. nasa.gov).';
        }
        if ($d['tier'] === 1 && !$d['is_official']) {
            $errors[] = 'Tier 1 is only for official/primary sources. Tick “This is an official authority” to confirm, or choose Tier 2.';
        }
        if ($d['tier'] !== 1) {
            $d['is_official'] = 0;
            $d['can_break'] = 0;
            $d['all_breaking'] = 0;
        }
        if ($d['tier'] === 3) {
            $d['kind'] = 'press_release';
        }
        if ($d['all_breaking'] && !$d['can_break']) {
            $d['can_break'] = 1;
        }
        return [$d, $errors];
    }

    public function save(?string $id = null): void
    {
        $this->verify();
        $row = null;
        if ($id !== null) {
            $row = Db::one('SELECT * FROM news_sources WHERE id = ?', [(int)$id]);
            if (!$row) {
                abort(404);
            }
        }
        [$d, $errors] = $this->collect($row);
        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect($row ? 'admin/news/sources/' . $row['id'] : 'admin/news/sources/new');
        }
        if ($row) {
            if ($row['feed_url'] !== $d['feed_url']) {
                $d += ['etag' => '', 'last_modified' => '', 'consecutive_failures' => 0, 'last_error' => ''];
            }
            Db::update('news_sources', $d, 'id = ?', [$row['id']]);
            NewsEngine::log('source_edited', $d['name'] . ' settings changed.', null, (int)$row['id'], 'admin');
            Audit::log('news_source_update', $d['name']);
            flash('success', 'Source saved.');
        } else {
            $hours = max(1, NewsEngine::opt('news_probation_hours'));
            $d += ['status' => 'probation', 'probation_until' => date('Y-m-d H:i:s', time() + $hours * 3600)];
            $newId = Db::insert('news_sources', $d);
            NewsEngine::log('source_added', $d['name'] . ' added; probation for ' . $hours . ' h (stories show as “New”, never breaking).', null, $newId, 'admin');
            Audit::log('news_source_add', $d['name']);
            flash('success', 'Source added. It starts on a ' . $hours . '-hour probation and is fetched on the next update.');
        }
        clear_old();
        redirect('admin/news/sources');
    }

    /** Fetches the feed once, without saving anything, and reports what would happen. */
    public function test(): void
    {
        $this->verify();
        $id = (int)($_POST['id'] ?? 0);
        $row = $id ? Db::one('SELECT * FROM news_sources WHERE id = ?', [$id]) : null;
        [$d, $errors] = $this->collect($row);
        $test = ['checks' => [], 'items' => [], 'ok' => false];
        $add = function (bool $ok, string $label, string $detail = '') use (&$test): void {
            $test['checks'][] = ['ok' => $ok, 'label' => $label, 'detail' => $detail];
        };
        foreach ($errors as $e) {
            $add(false, $e);
        }
        if (preg_match('#^https?://#i', $d['feed_url'])) {
            $r = Http::get($d['feed_url'], ['timeout' => 15]);
            $add($r['code'] >= 200 && $r['code'] < 300, 'Feed reachable', $r['error'] !== '' && $r['code'] < 200 ? $r['error'] : 'HTTP ' . $r['code'] . ', ' . number_format(strlen($r['body']) / 1024, 0) . ' KB');
            if ($r['code'] >= 200 && $r['code'] < 300) {
                try {
                    $items = FeedParser::parse($r['body']);
                    $add(true, 'Valid RSS/Atom feed', count($items) . ' stories found');
                    $domains = array_filter(array_map('trim', explode(',', $d['domains'])));
                    $okDom = $bad = 0;
                    $badHost = '';
                    $blocked = array_column(Db::all('SELECT term FROM news_blocked_terms'), 'term');
                    $science = NewsEngine::kw2('news_keywords_science');
                    $blockedHits = $offTopic = 0;
                    foreach ($items as $it) {
                        $link = NewsEngine::normaliseUrl($it['link']);
                        $host = $link ? strtolower((string)parse_url($link, PHP_URL_HOST)) : '';
                        $pass = false;
                        foreach ($domains as $dm) {
                            if ($host === $dm || str_ends_with($host, '.' . $dm)) {
                                $pass = true;
                            }
                        }
                        if ($pass) {
                            $okDom++;
                        } else {
                            $bad++;
                            $badHost = $badHost !== '' ? $badHost : $host;
                        }
                        $hay = mb_strtolower($it['title'] . ' ' . $it['summary']);
                        foreach ($blocked as $t) {
                            if ($t !== '' && str_contains($hay, mb_strtolower($t))) {
                                $blockedHits++;
                                break;
                            }
                        }
                        if ($d['topic_filter'] && $science && !preg_match($science, $it['title'] . ' ' . $it['summary'])) {
                            $offTopic++;
                        }
                    }
                    $add($bad === 0, 'Story links match the allowed domains (' . $d['domains'] . ')', $bad === 0 ? 'All ' . $okDom . ' links are on the allowed domains' : $bad . ' of ' . count($items) . ' links are on other domains (e.g. ' . $badHost . '); those would be rejected. Add the domain above if it is legitimate.');
                    $add(true, 'Block-list check', $blockedHits . ' stories contain a blocked term and would be filtered');
                    if ($d['topic_filter']) {
                        $add(true, 'Science-topic check', $offTopic . ' of ' . count($items) . ' stories are off-topic and would be filtered');
                    }
                    foreach (array_slice($items, 0, 3) as $it) {
                        $test['items'][] = ['title' => $it['title'], 'link' => $it['link'], 'date' => $it['published'] ? date('j M Y, H:i', $it['published']) : 'no date'];
                    }
                    $test['ok'] = !$errors && $bad === 0;
                } catch (RuntimeException $e) {
                    $add(false, 'Valid RSS/Atom feed', $e->getMessage());
                }
            }
        }
        $row2 = $row ?: null;
        remember_input($_POST);
        $this->page('admin/news_source_form', ['title' => $row ? 'Edit source: ' . $row['name'] : 'Add a news source', 'tab' => 'sources', 'row' => $row2, 'test' => $test]);
    }

    public function pause(string $id): void
    {
        $this->verify();
        $row = Db::one('SELECT * FROM news_sources WHERE id = ?', [(int)$id]);
        if ($row) {
            Db::update('news_sources', ['status' => 'paused', 'paused_reason' => 'Paused by administrator.'], 'id = ?', [$row['id']]);
            NewsEngine::log('source_paused', $row['name'] . ' paused by administrator.', null, (int)$row['id'], 'admin');
            flash('success', $row['name'] . ' paused. Its existing stories stay online; no new ones are fetched.');
        }
        redirect('admin/news/sources');
    }

    public function resume(string $id): void
    {
        $this->verify();
        $row = Db::one('SELECT * FROM news_sources WHERE id = ?', [(int)$id]);
        if ($row) {
            $probation = $row['probation_until'] && strtotime($row['probation_until']) > time();
            Db::update('news_sources', ['status' => $probation ? 'probation' : 'active', 'paused_reason' => '', 'consecutive_failures' => 0, 'last_error' => '', 'etag' => '', 'last_modified' => ''], 'id = ?', [$row['id']]);
            NewsEngine::log('source_resumed', $row['name'] . ' resumed by administrator.', null, (int)$row['id'], 'admin');
            flash('success', $row['name'] . ' resumed; it will be fetched on the next update.');
        }
        redirect('admin/news/sources');
    }

    public function delete(string $id): void
    {
        $this->verify();
        $row = Db::one('SELECT * FROM news_sources WHERE id = ?', [(int)$id]);
        if ($row) {
            Db::delete('news_sources', 'id = ?', [$row['id']]);
            NewsEngine::log('source_removed', $row['name'] . ' and all its stories removed.', null, null, 'admin');
            Audit::log('news_source_delete', $row['name']);
            flash('success', $row['name'] . ' and its stories were removed.');
        }
        redirect('admin/news/sources');
    }
}

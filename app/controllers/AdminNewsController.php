<?php
declare(strict_types=1);

/** News stories, reports, rules (block-list), activity log, fetch-now and the kill-switch. */
final class AdminNewsController extends AdminBase
{
    public function index(): void
    {
        $status = in_array($_GET['status'] ?? '', ['published', 'hidden'], true) ? $_GET['status'] : '';
        $flag = ($_GET['flag'] ?? '') === 'breaking' ? 'breaking' : '';
        $source = (int)($_GET['source'] ?? 0);
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $where = ['1 = 1'];
        $p = [];
        if ($status) {
            $where[] = 'i.status = ?';
            $p[] = $status;
        }
        if ($flag) {
            $where[] = 'i.is_breaking = 1';
        }
        if ($source) {
            $where[] = 'i.source_id = ?';
            $p[] = $source;
        }
        if ($q !== '') {
            $where[] = 'i.title LIKE ?';
            $p[] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        }
        $per = 40;
        $total = (int)Db::val('SELECT COUNT(*) FROM news_items i WHERE ' . implode(' AND ', $where), $p);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
        $rows = Db::all(
            'SELECT i.*, s.name AS source_name, s.tier FROM news_items i JOIN news_sources s ON s.id = i.source_id WHERE ' . implode(' AND ', $where)
            . ' ORDER BY i.published_at DESC, i.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per),
            $p
        );
        $this->page('admin/news_items', [
            'title' => 'News stories',
            'tab' => 'stories',
            'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages,
            'status' => $status, 'flag' => $flag, 'source' => $source, 'q' => $q,
            'sources' => Db::all('SELECT id, name FROM news_sources ORDER BY name'),
            'lastRun' => Settings::get('news_last_run'),
            'lastRunAt' => Settings::get('news_last_run_at'),
            'paused' => NewsEngine::paused(),
            'intervalH' => (int)(NewsEngine::intervalSeconds() / 3600),
            'nextAt' => NewsEngine::nextDueAt(),
            'stats' => [
                'published' => (int)Db::val("SELECT COUNT(*) FROM news_items WHERE status = 'published'"),
                'hidden' => (int)Db::val("SELECT COUNT(*) FROM news_items WHERE status = 'hidden'"),
                'breaking' => (int)Db::val("SELECT COUNT(*) FROM news_items WHERE is_breaking = 1 AND status = 'published' AND published_at > (NOW() - INTERVAL 12 HOUR)"),
            ],
        ]);
    }

    public function fetchNow(): void
    {
        $this->verify();
        $r = NewsEngine::run();
        flash(isset($r['skipped']) ? 'info' : 'success', $r['skipped'] ?? $r['summary']);
        Audit::log('news_fetch_now', $r['skipped'] ?? 'run');
        redirect('admin/news');
    }

    public function pause(): void
    {
        $this->verify();
        $now = !NewsEngine::paused();
        Settings::set('news_paused', $now ? '1' : '0');
        NewsEngine::log($now ? 'kill_switch_on' : 'kill_switch_off', $now ? 'All news paused by administrator.' : 'News resumed by administrator.', null, null, 'admin');
        Audit::log('news_kill_switch', $now ? 'on' : 'off');
        flash($now ? 'info' : 'success', $now ? 'All news is now paused: fetching is stopped and the News section and ticker are hidden.' : 'News is live again.');
        redirect('admin/news');
    }

    public function hide(string $id): void
    {
        $this->verify();
        $reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 150);
        NewsEngine::hide((int)$id, 'Hidden by administrator' . ($reason !== '' ? ': ' . $reason : '.'), 'admin');
        flash('success', 'Story hidden from the website.');
        redirect($this->back());
    }

    public function restore(string $id): void
    {
        $this->verify();
        NewsEngine::restore((int)$id);
        flash('success', 'Story restored.');
        redirect($this->back());
    }

    private function back(): string
    {
        $r = (string)($_POST['return'] ?? 'admin/news');
        return preg_match('#^admin/news[A-Za-z0-9/_.\-?=&%]*$#', $r) ? $r : 'admin/news';
    }

    /* ---- reports ---- */

    public function reports(): void
    {
        $this->page('admin/news_reports', [
            'title' => 'Reader reports',
            'tab' => 'reports',
            'rows' => Db::all(
                "SELECT r.*, i.title, i.url, i.status AS item_status, s.name AS source_name FROM news_reports r JOIN news_items i ON i.id = r.item_id JOIN news_sources s ON s.id = i.source_id
                 ORDER BY r.status = 'open' DESC, r.id DESC LIMIT 200"
            ),
        ]);
    }

    public function reportResolve(string $id): void
    {
        $this->verify();
        $r = Db::one('SELECT * FROM news_reports WHERE id = ?', [(int)$id]);
        if ($r) {
            NewsEngine::hide((int)$r['item_id'], 'Hidden after a reader report.', 'admin');
            Db::update('news_reports', ['status' => 'resolved'], 'item_id = ? AND status = ?', [$r['item_id'], 'open']);
            flash('success', 'Story hidden and the report resolved.');
        }
        redirect('admin/news/reports');
    }

    public function reportDismiss(string $id): void
    {
        $this->verify();
        Db::update('news_reports', ['status' => 'dismissed'], 'id = ?', [(int)$id]);
        flash('success', 'Report dismissed.');
        redirect('admin/news/reports');
    }

    /* ---- rules: block-list ---- */

    public function rules(): void
    {
        $this->page('admin/news_rules', [
            'title' => 'News rules',
            'tab' => 'rules',
            'terms' => Db::all('SELECT * FROM news_blocked_terms ORDER BY term'),
        ]);
    }

    public function termAdd(): void
    {
        $this->verify();
        $terms = array_filter(array_map(fn($t) => mb_substr(trim($t), 0, 100), preg_split('/[\r\n,]+/', (string)($_POST['term'] ?? '')) ?: []));
        $n = 0;
        foreach ($terms as $t) {
            if (mb_strlen($t) >= 3) {
                $n += Db::run('INSERT IGNORE INTO news_blocked_terms (term) VALUES (?)', [mb_strtolower($t)])->rowCount();
            }
        }
        flash($n ? 'success' : 'info', $n ? "$n blocked term" . ($n === 1 ? '' : 's') . ' added. Stories containing them will no longer be published.' : 'Nothing added (terms must be at least 3 characters and new).');
        redirect('admin/news/rules');
    }

    public function termDelete(string $id): void
    {
        $this->verify();
        Db::delete('news_blocked_terms', 'id = ?', [(int)$id]);
        flash('success', 'Term removed.');
        redirect('admin/news/rules');
    }

    /* ---- log ---- */

    public function log(): void
    {
        $event = preg_match('/^[a-z_]{2,24}$/', (string)($_GET['event'] ?? '')) ? $_GET['event'] : '';
        $rows = Db::all(
            'SELECT l.*, s.name AS source_name FROM news_log l LEFT JOIN news_sources s ON s.id = l.source_id' . ($event ? ' WHERE l.event = ?' : '') . ' ORDER BY l.id DESC LIMIT 300',
            $event ? [$event] : []
        );
        $this->page('admin/news_log', [
            'title' => 'News activity log',
            'tab' => 'log',
            'rows' => $rows,
            'event' => $event,
            'events' => array_column(Db::all('SELECT DISTINCT event FROM news_log ORDER BY event'), 'event'),
        ]);
    }
}

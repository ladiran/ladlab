<?php
declare(strict_types=1);

final class NewsController
{
    private function guard(): void
    {
        if (!module_enabled('news')) {
            (new SiteController())->soon();
            exit;
        }
    }

    /** Called quietly by the page script when an update is due, so the news refreshes itself without any scheduler. */
    public function tick(): void
    {
        session_write_close(); // do not hold the visitor's session while the update runs
        ignore_user_abort(true);
        header('Cache-Control: no-store');
        if (!NewsEngine::due()) {
            http_response_code(204);
            return;
        }
        http_response_code(202);
        header('Content-Length: 0');
        header('Connection: close');
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }
        NewsEngine::runIfDue();
    }

    public function hub(): void
    {
        $this->guard();
        if (NewsEngine::paused()) {
            view('public/message', ['title' => 'Science News', 'heading' => 'News is paused for a moment', 'text' => 'The science news desk is temporarily switched off. Please check back soon.']);
            return;
        }
        $f = [
            'region' => isset(NewsEngine::REGIONS[$_GET['region'] ?? '']) ? $_GET['region'] : '',
            'topic' => isset(NewsEngine::TOPICS[$_GET['topic'] ?? '']) ? $_GET['topic'] : '',
            'view' => ($_GET['view'] ?? '') === 'breaking' ? 'breaking' : '',
            'q' => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80),
        ];
        $per = 20;
        $total = NewsRepo::count($f);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
        form_started('newsreport');
        view('public/news', [
            'title' => 'Science News',
            'meta_desc' => 'Science news from Nigeria and the world, from verified sources, with breaking stories as they happen.',
            'items' => NewsRepo::list($f, $per, ($page - 1) * $per),
            'filters' => $f,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'breaking' => $f['view'] === '' && $page === 1 && !$f['q'] ? NewsRepo::ticker() : [],
        ]);
    }

    public function sources(): void
    {
        view('public/news_sources', [
            'title' => 'News sources & editorial policy',
            'meta_desc' => 'Where our science news comes from and the rules every story must pass.',
            'sources' => NewsRepo::publicSources(),
            'rules' => [
                'window' => NewsEngine::opt('news_breaking_window_hours'),
                'min' => max(2, NewsEngine::opt('news_breaking_min_sources')),
                'ticker' => NewsEngine::opt('news_ticker_hours'),
                'recheck' => NewsEngine::opt('news_recheck_hours'),
                'probation' => NewsEngine::opt('news_probation_hours'),
            ],
        ]);
    }

    public function nobel(): void
    {
        $this->guard();
        $now = (int)date('Y');
        $year = (int)($_GET['year'] ?? 0);
        if ($year < 1901 || $year > $now) {
            // Default: this year once any prize has been announced, otherwise last year.
            $current = NewsEngine::nobel($now);
            $year = $current ? $now : $now - 1;
        }
        $data = NewsEngine::nobel($year);
        $items = Db::all("SELECT i.*, s.name AS source_name, s.tier, s.kind, s.status AS source_status, s.show_images FROM news_items i JOIN news_sources s ON s.id = i.source_id WHERE i.status = 'published' AND i.is_primary = 1 AND i.topic = 'nobel' ORDER BY i.published_at DESC LIMIT 6");
        form_started('newsreport');
        view('public/nobel', [
            'title' => 'Nobel Prize ' . $year,
            'meta_desc' => 'The Nobel Prize laureates for ' . $year . ', with official citations, from NobelPrize.org.',
            'year' => $year,
            'now' => $now,
            'data' => $data,
            'categories' => NewsEngine::nobelCategories(),
            'items' => $items,
        ]);
    }

    public function report(): void
    {
        csrf_check();
        $return = (string)($_POST['return'] ?? '/news');
        $return = preg_match('#^/[A-Za-z0-9/_.\-?=&%]*$#', $return) ? $return : '/news';
        if (!form_human('newsreport')) {
            flash('error', 'Please wait a moment and try again.');
            redirect($return);
        }
        if (!rate_ok('newsreport', 10, 3600)) {
            abort(429, 'Too many reports. Please try again later.');
        }
        $id = (int)($_POST['item_id'] ?? 0);
        $msg = mb_substr(trim((string)($_POST['message'] ?? '')), 0, 500);
        if (!Db::val("SELECT 1 FROM news_items WHERE id = ? AND status = 'published'", [$id])) {
            flash('error', 'That story is no longer listed.');
            redirect($return);
        }
        Db::insert('news_reports', ['item_id' => $id, 'message' => $msg, 'ip' => client_ip()]);
        NewsEngine::log('reported', 'Reader report' . ($msg !== '' ? ': ' . mb_substr($msg, 0, 200) : ''), $id);
        flash('success', 'Thank you. Your report was sent for review.');
        redirect($return);
    }
}

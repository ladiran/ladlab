<?php
declare(strict_types=1);

final class AdminController extends AdminBase
{
    private const SAMPLE_TABLES = ['publications', 'awards', 'talks', 'projects', 'testimonials', 'timeline', 'posts', 'products', 'courses', 'services', 'speaking_topics', 'press_items', 'events'];

    public function dashboard(): void
    {
        $samples = 0;
        foreach (self::SAMPLE_TABLES as $t) {
            try {
                $samples += (int)Db::val("SELECT COUNT(*) FROM `$t` WHERE is_sample = 1");
            } catch (Throwable) {
                // table from a later phase not created yet (database update pending)
            }
        }
        $this->page('admin/dashboard', [
            'title' => 'Dashboard',
            'counts' => [
                'new_enquiries' => (int)Db::val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"),
                'subscribers' => (int)Db::val('SELECT COUNT(*) FROM subscribers WHERE confirmed = 1 AND unsubscribed_at IS NULL'),
                'pending_subs' => (int)Db::val('SELECT COUNT(*) FROM subscribers WHERE confirmed = 0 AND unsubscribed_at IS NULL'),
                'publications' => (int)Db::val('SELECT COUNT(*) FROM publications WHERE is_sample = 0'),
                'posts' => (int)Db::val("SELECT COUNT(*) FROM posts WHERE is_sample = 0 AND status = 'published'"),
                'pending_articles' => self::pendingArticles(),
            ],
            'samples' => $samples,
            'recent' => Db::all("SELECT id, name, type, subject, status, created_at FROM enquiries ORDER BY id DESC LIMIT 5"),
            'todo' => $this->checklist(),
            'cv' => Content::activeCv(),
            'pendingUpdates' => Migrator::pending(),
            'news' => self::newsStats(),
            'shop' => self::shopStats(),
            'learn' => self::learnStats(),
        ]);
    }

    private static function newsStats(): ?array
    {
        try {
            if (!module_enabled('news')) {
                return null;
            }
            return [
                'stories' => (int)Db::val("SELECT COUNT(*) FROM news_items WHERE status = 'published' AND published_at > (NOW() - INTERVAL 24 HOUR)"),
                'breaking' => count(NewsRepo::ticker()),
                'reports' => (int)Db::val("SELECT COUNT(*) FROM news_reports WHERE status = 'open'"),
                'bad' => (int)Db::val("SELECT COUNT(*) FROM news_sources WHERE status <> 'paused' AND consecutive_failures >= 3"),
                'paused' => (int)Db::val("SELECT COUNT(*) FROM news_sources WHERE status = 'paused'"),
                'lastRunAt' => Settings::get('news_last_run_at'),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private static function pendingArticles(): int
    {
        try {
            return (int)Db::val("SELECT COUNT(*) FROM posts WHERE is_user_article = 1 AND status = 'pending'");
        } catch (Throwable) {
            return 0; // articles feature not installed yet
        }
    }

    private static function learnStats(): ?array
    {
        try {
            if (!module_enabled('learn')) {
                return null;
            }
            return [
                'pending' => (int)Db::val("SELECT COUNT(*) FROM enrolments WHERE status = 'awaiting_payment'"),
                'receipts' => (int)Db::val("SELECT COUNT(*) FROM enrolments WHERE status = 'awaiting_payment' AND receipt_path <> ''"),
                'students' => (int)Db::val("SELECT COUNT(DISTINCT user_id) FROM enrolments WHERE status = 'active'"),
                'certs' => (int)Db::val('SELECT COUNT(*) FROM certificates'),
                'courses' => (int)Db::val("SELECT COUNT(*) FROM courses WHERE status = 'published' AND is_sample = 0"),
                'questions' => (int)Db::val("SELECT COUNT(*) FROM lesson_questions WHERE status = 'open'"),
                'reviews' => (int)Db::val("SELECT COUNT(*) FROM course_reviews WHERE status = 'pending'"),
            ];
        } catch (Throwable) {
            return null; // course tables not installed yet
        }
    }

    private static function shopStats(): ?array
    {
        try {
            if (!module_enabled('shop')) {
                return null;
            }
            Shop::expireUnpaid();
            return [
                'awaiting' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'awaiting_payment'"),
                'receipts' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'awaiting_payment' AND receipt_path <> ''"),
                'topack' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'paid'"),
                'transit' => (int)Db::val("SELECT COUNT(*) FROM orders WHERE status = 'shipped'"),
                'low' => count(Shop::lowStock()),
                'waiting' => (int)Db::val('SELECT COUNT(*) FROM stock_alerts WHERE notified_at IS NULL'),
                'month' => (int)Db::val("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ('paid','packed','shipped','delivered') AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"),
            ];
        } catch (Throwable) {
            return null; // shop tables not installed yet
        }
    }

    public function update(): void
    {
        $this->verify();
        try {
            $done = Migrator::run();
            Audit::log('db_update', implode(', ', $done));
            flash('success', $done ? 'Database updated: ' . implode(', ', $done) . '.' : 'Already up to date.');
        } catch (Throwable $e) {
            flash('error', 'The update failed: ' . $e->getMessage());
        }
        redirect('admin');
    }

    /** "Make it yours" checklist: shows what is still a placeholder. */
    private function checklist(): array
    {
        return [
            ['Add your name and headline', Settings::get('owner_name') !== 'Dr. [Your Name]', 'admin/settings/profile'],
            ['Upload your portrait photo', setting('portrait') !== '', 'admin/settings/profile'],
            ['Write your biography', trim(setting('bio_full')) !== trim((string)SettingsDef::defaults()['bio_full']), 'admin/settings/profile'],
            ['Upload your CV (PDF)', (bool)Content::activeCv(), 'admin/cv'],
            ['Add your publications', (int)Db::val('SELECT COUNT(*) FROM publications WHERE is_sample = 0') > 0, 'admin/publications'],
            ['Set your contact email', setting('contact_email') !== '', 'admin/settings/contact'],
            ['Upload your logo (optional)', setting('logo') !== '', 'admin/settings/brand'],
        ];
    }

    public function clearSamples(): void
    {
        $this->verify();
        $n = 0;
        foreach (self::SAMPLE_TABLES as $t) {
            try {
                $n += Db::delete($t, 'is_sample = 1');
            } catch (Throwable) {
                // table not created yet
            }
        }
        Audit::log('clear_samples', "$n rows");
        flash('success', "Removed $n sample items.");
        redirect('admin');
    }

    public function markdownPreview(): void
    {
        $this->verify();
        json_out(['html' => md((string)($_POST['text'] ?? ''))]);
    }

    public function account(): void
    {
        $this->page('admin/account', ['title' => 'My account', 'user' => Auth::user()]);
    }

    public function accountSave(): void
    {
        $this->verify();
        $user = Auth::user();
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['password'] ?? '');
        $new2 = (string)($_POST['password2'] ?? '');

        $row = Db::one('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        if (!password_verify($current, $row['password_hash'])) {
            flash('error', 'Your current password is not correct.');
            redirect('admin/account');
        }
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter your name and a valid email.');
            redirect('admin/account');
        }
        if (Db::val('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $user['id']])) {
            flash('error', 'That email is already used by another account.');
            redirect('admin/account');
        }
        $data = ['name' => $name, 'email' => $email];
        if ($new !== '') {
            if ($p = Auth::passwordProblem($new, $email, 'admin', $name)) {
                flash('error', $p);
                redirect('admin/account');
            }
            if ($new !== $new2) {
                flash('error', 'The two new passwords do not match.');
                redirect('admin/account');
            }
            $data['password_hash'] = Auth::hash($new);
            $data['pw_changed_at'] = date('Y-m-d H:i:s');
        }
        Db::update('users', $data, 'id = ?', [$user['id']]);
        if ($new !== '') {
            Auth::bumpEpoch((int)$user['id']);
            Auth::alert($user, 'Your administrator password was changed', "Hello {$user['name']},\n\nYour administrator password was just changed. If this was not you, reset it now: " . abs_url('forgot-password'));
        }
        Audit::log('account_update', $email . ($new !== '' ? ' (password changed)' : ''));
        flash('success', 'Your account was updated.');
        redirect('admin/account');
    }

    /** Sends a test email to the signed-in administrator and says plainly whether it worked. */
    public function mailTest(): void
    {
        $this->verify();
        $u = Auth::user();
        $ok = Mailer::send($u['email'], '[' . Settings::get('site_name') . '] Test email', "Hello {$u['name']},\n\nThis is a test email from your website. If you can read it, outgoing email works.\n\nSent " . date('j M Y, H:i') . '.');
        $mode = env('MAIL_MODE', 'log');
        if ($ok && $mode === 'log') {
            flash('info', 'Test mode: the message was only written to storage/logs/mail.log. To really send email, set MAIL_MODE=smtp in config/.env (see the Linux Installation guide, email step).');
        } elseif ($ok) {
            flash('success', 'Test email sent to ' . $u['email'] . '. Check your inbox (and spam folder).');
        } else {
            flash('error', 'The test email FAILED: ' . (Mailer::$lastError ?: 'unknown reason') . ' Check the SMTP lines in config/.env and storage/logs/mail.log.');
        }
        redirect('admin');
    }

    public function audit(): void
    {
        $this->page('admin/audit', [
            'title' => 'Activity log',
            'rows' => Db::all('SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 200'),
        ]);
    }
}

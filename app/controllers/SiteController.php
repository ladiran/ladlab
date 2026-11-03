<?php
declare(strict_types=1);

final class SiteController
{
    public const ENQUIRY_TYPES = [
        'general' => 'General question',
        'consulting' => 'Consulting',
        'speaking' => 'Speaking / keynote',
        'training' => 'Training / workshop',
        'supervision' => 'Supervision / mentoring',
    ];

    public function home(): void
    {
        view('public/home', [
            'title' => '',
            'meta_desc' => setting('meta_description'),
            'body_class' => 'home',
            'stats' => Content::stats(),
            'services' => Content::visible('services', 'sort_order, id', 4),
            'featured' => Content::featuredPublications(3),
            'posts' => Content::posts(3),
            'testimonials' => Content::visible('testimonials', 'sort_order, id', 3),
            'cv' => Content::activeCv(),
            'news' => self::homeNews(),
            'shop' => self::homeShop(),
        ]);
    }

    private static function homeShop(): array
    {
        try {
            return module_enabled('shop') ? Shop::products([], 3) : [];
        } catch (Throwable) {
            return []; // shop tables not installed yet
        }
    }

    private static function homeNews(): array
    {
        try {
            return NewsRepo::enabled() ? NewsRepo::latest(5) : [];
        } catch (Throwable) {
            return []; // news tables not installed yet
        }
    }

    public function about(): void
    {
        view('public/about', [
            'title' => 'About',
            'meta_desc' => excerpt(setting('bio_short'), 155),
            'timeline' => Content::visible('timeline'),
            'awards' => Content::visible('awards', 'sort_order, year DESC, id DESC'),
            'cv' => Content::activeCv(),
        ]);
    }

    public function research(): void
    {
        $filters = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'year' => preg_match('/^\d{4}$/', (string)($_GET['year'] ?? '')) ? $_GET['year'] : '',
            'type' => isset(Importer::TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : '',
        ];
        $flag = Content::sampleFlag('publications');
        $years = array_column(Db::all('SELECT DISTINCT year FROM publications WHERE is_visible = 1 AND is_sample = ? AND year IS NOT NULL ORDER BY year DESC', [$flag]), 'year');
        $types = array_column(Db::all('SELECT DISTINCT type FROM publications WHERE is_visible = 1 AND is_sample = ? ORDER BY type', [$flag]), 'type');
        view('public/research', [
            'title' => 'Research',
            'meta_desc' => 'Research areas, publications, talks and media.',
            'filters' => $filters,
            'publications' => Content::publications($filters),
            'years' => $years,
            'types' => $types,
            'projects' => Content::visible('projects'),
            'talks' => Content::visible('talks', 'talk_date DESC, id DESC'),
        ]);
    }

    public function blog(): void
    {
        $per = 6;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $tag = trim((string)($_GET['tag'] ?? '')) ?: null;
        $total = Content::countPosts($tag);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($page, $pages);
        view('public/blog', [
            'title' => 'Blog',
            'meta_desc' => 'Articles and explainers on physics, weather, climate and science education.',
            'posts' => Content::posts($per, ($page - 1) * $per, $tag),
            'tags' => Content::allTags(),
            'tag' => $tag,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public function post(string $slug): void
    {
        $post = Content::post($slug);
        if (!$post) {
            abort(404);
        }
        view('public/post', [
            'title' => $post['title'],
            'meta_desc' => $post['excerpt'] ?: excerpt($post['body'], 155),
            'og_image' => $post['cover_image'] ? abs_url($post['cover_image']) : '',
            'og_type' => 'article', 'noindex' => (bool)$post['is_sample'], 'jsonld' => [Seo::article($post)],
            'post' => $post,
        ]);
    }

    public function contact(): void
    {
        form_started('contact');
        $type = $_GET['type'] ?? 'general';
        view('public/contact', [
            'title' => 'Contact',
            'meta_desc' => 'Get in touch for consulting, speaking, training or supervision.',
            'types' => self::ENQUIRY_TYPES,
            'selectedType' => isset(self::ENQUIRY_TYPES[$type]) ? $type : 'general',
        ]);
    }

    public function contactSend(): void
    {
        csrf_check();
        $back = 'contact';
        if (!form_human('contact')) {
            flash('error', 'Please wait a moment and send the form again.');
            redirect($back);
        }
        if (!rate_ok('contact', 5, 3600)) {
            abort(429, 'You have sent several messages recently. Please try again later.');
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $type = (string)($_POST['type'] ?? 'general');
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) {
            $errors[] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (mb_strlen($phone) > 40 || ($phone !== '' && !preg_match('/^[0-9+()\-\s]{5,40}$/', $phone))) {
            $errors[] = 'Please check the phone number.';
        }
        if (!isset(self::ENQUIRY_TYPES[$type])) {
            $type = 'general';
        }
        if (mb_strlen($subject) > 200) {
            $errors[] = 'The subject is too long.';
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
            $errors[] = 'Please write a message between 10 and 5000 characters.';
        }
        if ($errors) {
            remember_input($_POST);
            foreach ($errors as $err) {
                flash('error', $err);
            }
            redirect($back);
        }

        $id = Db::insert('enquiries', [
            'name' => $name, 'email' => $email, 'phone' => $phone, 'type' => $type,
            'subject' => $subject, 'message' => $message, 'ip' => client_ip(),
        ]);
        $to = setting('notify_email') ?: setting('contact_email');
        $sent = true;
        if ($to) {
            $sent = Mailer::send(
                $to,
                '[' . setting('site_name') . '] New ' . strtolower(self::ENQUIRY_TYPES[$type]) . ' enquiry from ' . $name,
                "Name: $name\nEmail: $email\nPhone: $phone\nType: " . self::ENQUIRY_TYPES[$type] . "\nSubject: $subject\n\n$message\n\nView in admin: " . abs_url('admin/enquiries/' . $id),
                $email
            );
        }
        try {
            Db::update('enquiries', ['mail_status' => $sent ? 'sent' : 'failed'], 'id = ?', [$id]);
        } catch (Throwable) {
            // column not installed yet (database update pending)
        }
        if ($sent) {
            clear_old();
            $test = env('MAIL_MODE', 'log') === 'log' && env('APP_ENV') !== 'production' ? ' (Test mode: the email was written to storage/logs/mail.log, not sent.)' : '';
            $_SESSION['contact_result'] = ['ok' => true, 'title' => 'Message sent', 'text' => 'Thank you, ' . (preg_split('/\s+/', $name)[0] ?: $name) . '! Your message has been delivered and I will reply to ' . $email . ' soon. Please also look in your spam folder if you do not hear from me.' . $test];
        } else {
            remember_input($_POST); // keep what they typed, so they can try again or copy it
            $alt = array_filter([setting('contact_email') ? 'email ' . setting('contact_email') : '', setting('whatsapp') ? 'WhatsApp +' . setting('whatsapp') : '']);
            $_SESSION['contact_result'] = ['ok' => false, 'title' => 'The email could not be delivered', 'text' => 'Sorry: the email system had a problem, so your message may reach me late. It has been saved safely on the website and has not been lost, but if it is urgent please contact me directly' . ($alt ? ' by ' . implode(' or ', $alt) : '') . ', or try sending the form again in a few minutes.'];
        }
        redirect($back . '#result');
    }

    public function subscribe(): void
    {
        csrf_check();
        $return = (string)($_POST['return'] ?? '/');
        $return = preg_match('#^/[A-Za-z0-9/_.\-]*$#', $return) ? $return : '/';
        if (!form_human('newsletter')) {
            flash('error', 'Please wait a moment and try again.');
            redirect($return);
        }
        if (!rate_ok('subscribe', 6, 3600)) {
            abort(429, 'Too many sign-up attempts. Please try again later.');
        }
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            flash('error', 'Please enter a valid email address.');
            redirect($return);
        }
        $existing = Db::one('SELECT * FROM subscribers WHERE email = ?', [$email]);
        if ($existing && (int)$existing['confirmed'] === 1 && $existing['unsubscribed_at'] === null) {
            flash('success', 'You are already subscribed. Thank you!');
            redirect($return);
        }
        $token = bin2hex(random_bytes(20));
        if ($existing) {
            Db::update('subscribers', ['token' => $token, 'confirmed' => 0, 'unsubscribed_at' => null], 'id = ?', [$existing['id']]);
        } else {
            Db::insert('subscribers', ['email' => $email, 'name' => $name, 'token' => $token]);
        }
        $link = abs_url('newsletter/confirm/' . $token);
        $sent = Mailer::send(
            $email,
            'Please confirm your subscription to ' . setting('site_name'),
            "Hello" . ($name ? " $name" : '') . ",\n\nPlease confirm your subscription by opening this link:\n$link\n\nIf you did not ask for this, simply ignore this email.\n\n" . setting('site_name')
        );
        if (!$sent) {
            flash('error', 'Sorry, we could not send the confirmation email just now. Please try again in a few minutes.');
            redirect($return);
        }
        $note = env('MAIL_MODE', 'log') === 'log' && env('APP_ENV') !== 'production'
            ? ' (Test mode: the confirmation email was written to storage/logs/mail.log.)' : '';
        flash('success', 'Almost done! We have sent a confirmation email to ' . $email . '. Open it and click the link (look in your spam folder if it does not arrive).' . $note);
        redirect($return);
    }

    public function confirmSubscription(string $token): void
    {
        $row = preg_match('/^[a-f0-9]{40}$/', $token) ? Db::one('SELECT * FROM subscribers WHERE token = ?', [$token]) : null;
        if (!$row) {
            abort(404);
        }
        Db::update('subscribers', ['confirmed' => 1, 'confirmed_at' => date('Y-m-d H:i:s'), 'unsubscribed_at' => null], 'id = ?', [$row['id']]);
        view('public/message', ['title' => 'Subscription confirmed', 'heading' => 'You are subscribed', 'text' => 'Thank you for confirming. You will hear from me soon.']);
    }

    public function unsubscribe(string $token): void
    {
        $row = preg_match('/^[a-f0-9]{40}$/', $token) ? Db::one('SELECT * FROM subscribers WHERE token = ?', [$token]) : null;
        if (!$row) {
            abort(404);
        }
        Db::update('subscribers', ['confirmed' => 0, 'unsubscribed_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['id']]);
        view('public/message', ['title' => 'Unsubscribed', 'heading' => 'You have been unsubscribed', 'text' => 'You will not receive further newsletters. You are welcome back any time.']);
    }

    /* ------------------------------------------------------------------ self-promotion pages */

    public function services(): void
    {
        view('public/services', ['title' => 'Services', 'meta_desc' => 'Consulting, speaking, training and supervision with ' . setting('owner_name') . '.', 'services' => Content::visible('services')]);
    }

    public function speaking(): void
    {
        $today = date('Y-m-d');
        $events = array_values(array_filter(Content::visible('events', 'start_date'), fn($e) => ($e['end_date'] ?: $e['start_date']) >= $today));
        form_started('contact');
        view('public/speaking', [
            'title' => 'Speaking and media kit',
            'meta_desc' => 'Invite ' . setting('owner_name') . ' to speak: topics, media bio, headshot and booking.',
            'topics' => Content::visible('speaking_topics'),
            'events' => array_slice($events, 0, 3),
            'talks' => Content::visible('talks', 'talk_date DESC, id DESC', 6),
            'testimonials' => Content::visible('testimonials', 'sort_order, id', 3),
        ]);
    }

    public function press(): void
    {
        $items = Content::visible('press_items', 'pub_date DESC, id DESC');
        $byYear = [];
        foreach ($items as $i) {
            $byYear[$i['pub_date'] ? substr($i['pub_date'], 0, 4) : 'Earlier'][] = $i;
        }
        view('public/press', ['title' => 'In the media', 'meta_desc' => setting('owner_name') . ' in the news, on radio, TV and podcasts.', 'byYear' => $byYear]);
    }

    public function events(): void
    {
        $today = date('Y-m-d');
        $all = Content::visible('events', 'start_date');
        $upcoming = array_values(array_filter($all, fn($e) => ($e['end_date'] ?: $e['start_date']) >= $today));
        $past = array_reverse(array_values(array_filter($all, fn($e) => ($e['end_date'] ?: $e['start_date']) < $today)));
        view('public/events', ['title' => 'Events', 'meta_desc' => 'Upcoming talks, workshops and lectures.', 'upcoming' => $upcoming, 'past' => $past, 'jsonld' => array_map(fn($ev) => Seo::event($ev), array_values(array_filter($upcoming, fn($ev) => !$ev['is_sample'])))]);
    }

    public function links(): void
    {
        $rows = Db::all('SELECT * FROM link_items WHERE is_visible = 1 ORDER BY sort_order, id');
        if (!$rows) {
            $rows = [];
            foreach ([['Courses', '/learn', '🎓', 'learn'], ['Shop', '/shop', '👕', 'shop'], ['Science news', '/news', '📰', 'news'], ['Work with me', '/services', '💼', null], ['Speaking and media kit', '/speaking', '🎙', null], ['Contact', '/contact', '✉', null]] as [$label, $href, $icon, $mod]) {
                if ($mod === null || module_enabled($mod)) {
                    $rows[] = ['label' => $label, 'url' => $href, 'icon' => $icon];
                }
            }
            foreach (Content::socials() as $label => $href) {
                $rows[] = ['label' => $label, 'url' => $href, 'icon' => '🔗'];
            }
        }
        view('public/links', ['title' => 'Links', 'meta_desc' => 'All my links in one place.', 'rows' => $rows, 'body_class' => 'links-page']);
    }

    public function resources(): void
    {
        $byTopic = [];
        foreach (Content::visible('resources', 'sort_order, id DESC') as $r) {
            $byTopic[$r['topic'] !== '' ? $r['topic'] : 'General'][] = $r;
        }
        view('public/resources', ['title' => 'Free resources', 'meta_desc' => 'Free formula sheets, notes and problem sets for physics students and teachers.', 'byTopic' => $byTopic]);
    }

    /** "Report a problem" for a free resource (copyright concern, broken file, other): lands in Admin → Enquiries and by email. */
    public function resourceReportForm(string $id): void
    {
        $r = Db::one('SELECT id, title FROM resources WHERE id = ? AND is_visible = 1', [(int)$id]);
        if (!$r) {
            abort(404);
        }
        form_started('resreport');
        view('public/resource_report', ['title' => 'Report a problem', 'meta_desc' => '', 'r' => $r]);
    }

    public function resourceReport(string $id): void
    {
        csrf_check();
        $r = Db::one('SELECT id, title FROM resources WHERE id = ?', [(int)$id]);
        if (!$r) {
            abort(404);
        }
        $back = 'resources/' . $r['id'] . '/report';
        if (!form_human('resreport') || !rate_ok('resreport', 5, 3600)) {
            flash('error', 'Please wait a moment and try again.');
            redirect($back);
        }
        $reason = ['copyright' => 'Copyright concern', 'broken' => 'Broken file or link', 'wrong' => 'Wrong or misleading', 'other' => 'Other'][$_POST['reason'] ?? ''] ?? 'Other';
        $details = mb_substr(trim((string)($_POST['details'] ?? '')), 0, 3000);
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'That email address does not look right.');
            redirect($back);
        }
        if (mb_strlen($details) < 10) {
            flash('error', 'Please tell us a little more (at least 10 characters). For a copyright concern, say who the owner is and where the work is lawfully available.');
            redirect($back);
        }
        Db::insert('enquiries', [
            'name' => $name !== '' ? $name : 'Resource reporter', 'email' => $email !== '' ? $email : 'noreply@invalid.local', 'type' => 'general',
            'subject' => mb_substr('Resource report (' . $reason . '): ' . $r['title'], 0, 200),
            'message' => "$reason\n\nResource: {$r['title']} (#{$r['id']})\n\n$details", 'ip' => client_ip(),
        ]);
        Community::notifyOwner('Resource report: ' . strtolower($reason), "Resource: {$r['title']} (#{$r['id']})\n\n$details\n\nFrom: " . ($name ?: 'anonymous') . ' ' . $email);
        flash('success', 'Thank you. We take every report seriously and will look at it promptly.');
        redirect('resources');
    }

    public function resourceDownload(string $id): void
    {
        $r = Db::one('SELECT * FROM resources WHERE id = ? AND is_visible = 1', [(int)$id]);
        $target = $r ? ($r['pdf_file'] !== '' ? $r['pdf_file'] : $r['url']) : '';
        if (!$r || $target === '') {
            abort(404);
        }
        Db::run('UPDATE resources SET downloads = downloads + 1 WHERE id = ?', [$r['id']]);
        redirect($target);
    }

    public function securityTxt(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $mail = Settings::get('contact_email') ?: Settings::get('notify_email');
        echo "Contact: mailto:$mail\nExpires: " . gmdate('Y-m-d\\TH:i:s\\Z', strtotime('+1 year')) . "\nPreferred-Languages: en\nCanonical: " . abs_url('.well-known/security.txt') . "\n";
    }

    public function soon(): void
    {
        $key = trim(request_path(), '/');
        $info = [
            'learn' => ['Learn', '🎓', 'University-level Physics courses are being prepared, starting with University Physics I: Mechanics. Leave your email to be told the day the first course opens.'],
            'shop' => ['Shop', '👕', 'The ClearSky T-shirt range is on its way: original physics and atmosphere designs. Leave your email to hear first.'],
            'news' => ['Science News', '📰', 'A live science news desk, with Nigerian and global stories and breaking news, is coming soon.'],
        ][$key] ?? null;
        if (!$info) {
            abort(404);
        }
        form_started('newsletter');
        view('public/soon', ['title' => $info[0] . ' (coming soon)', 'name' => $info[0], 'icon' => $info[1], 'text' => $info[2]]);
    }

    public function legal(): void
    {
        $slug = trim(request_path(), '/');
        $page = Db::one('SELECT * FROM pages WHERE slug = ?', [$slug]);
        if (!$page) {
            abort(404);
        }
        $page['body'] = str_replace('{{contact_email}}', Settings::get('contact_email') ?: (Settings::get('notify_email') ?: '[your contact email]'), (string)$page['body']);
        view('public/legal', ['title' => $page['title'], 'page' => $page]);
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=900');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (Seo::sitemapEntries() as [$loc, $mod]) {
            echo '<url><loc>' . e($loc) . '</loc>' . ($mod !== '' ? '<lastmod>' . e($mod) . '</lastmod>' : '') . "</url>\n";
        }
        echo '</urlset>';
    }
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo Seo::robotsTxt();
    }

    /** RSS feed of the blog: helps readers and search engines discover new posts. */
    public function feed(): void
    {
        header('Content-Type: application/rss+xml; charset=utf-8');
        header('Cache-Control: public, max-age=900');
        $site = setting('site_name');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel>'
            . '<title>' . e($site) . '</title><link>' . e(abs_url('blog')) . '</link><description>' . e(setting('meta_description')) . '</description><language>en</language>'
            . '<atom:link href="' . e(abs_url('blog/feed.xml')) . '" rel="self" type="application/rss+xml"/>';
        foreach (array_filter(Content::posts(30), fn($p) => !$p['is_sample']) as $p) {
            echo '<item><title>' . e($p['title']) . '</title><link>' . e(abs_url('blog/' . $p['slug'])) . '</link><guid isPermaLink="true">' . e(abs_url('blog/' . $p['slug'])) . '</guid>'
                . '<dc:creator>' . e(Content::author($p)['name']) . '</dc:creator><pubDate>' . date(DATE_RSS, strtotime($p['published_at'] ?: $p['created_at'])) . '</pubDate><description>' . e($p['excerpt'] ?: excerpt((string)$p['body'], 300)) . '</description></item>';
        }
        echo '</channel></rss>';
    }

    /** IndexNow ownership proof: https://yoursite/<key>.txt must return the key. */
    public function indexNowKey(string $key): void
    {
        if (!hash_equals(Seo::indexNowKey(), $key)) {
            abort(404);
        }
        header('Content-Type: text/plain; charset=utf-8');
        echo $key;
    }
}

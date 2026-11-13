<?php
declare(strict_types=1);

/** Public learning area: catalogue, course pages, lesson player, quizzes, enrolment, certificates. */
final class LearnController
{
    private function guard(): void
    {
        if (!module_enabled('learn')) {
            (new SiteController())->soon();
            exit;
        }
    }

    private function courseOr404(string $slug): array
    {
        return Learn::accessibleCourse($slug);
    }

    /* ------------------------------------------------------------------ catalogue & course page */

    public function index(): void
    {
        $this->guard();
        $f = ['q' => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60), 'price' => ($_GET['price'] ?? '') === 'free' ? 'free' : ''];
        view('public/learn', [
            'title' => 'Learn',
            'meta_desc' => 'University-level physics courses taught clearly: lessons, worked equations, quizzes and certificates.',
            'courses' => Learn::catalogue($f),
            'filters' => $f,
        ]);
    }

    public function course(string $slug): void
    {
        $this->guard();
        $c = $this->courseOr404($slug);
        $u = Auth::check() ? Auth::user() : null;
        $enrol = $u ? Learn::enrolment((int)$u['id'], (int)$c['id']) : null;
        $cur = Learn::curriculum((int)$c['id']);
        $progress = $u && $enrol && $enrol['status'] === 'active' ? Learn::progress((int)$u['id'], (int)$c['id']) : null;
        $first = Learn::lessonOrder((int)$c['id'])[0]['id'] ?? null;
        $resume = $enrol && $enrol['last_lesson_id'] ? (int)$enrol['last_lesson_id'] : $first;
        $coupon = '';
        view('public/course', [
            'title' => $c['title'],
            'meta_desc' => $c['subtitle'] ?: excerpt((string)$c['description'], 155),
            'og_image' => $c['cover_image'] ? abs_url($c['cover_image']) : '',
            'noindex' => (bool)$c['is_sample'], 'jsonld' => $c['status'] === 'published' ? [Seo::course($c + ['minutes' => $cur['minutes'] ?? 0], Community::ratingStats((int)$c['id']))] : [],
            'c' => $c, 'cur' => $cur, 'u' => $u, 'enrol' => $enrol, 'progress' => $progress, 'first' => $first, 'resume' => $resume,
            'students' => (int)Db::val("SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND status = 'active'", [$c['id']]),
            'isAdmin' => Learn::manages($u, $c),
            'lockNote' => Learn::manages($u, $c) ? Learn::lockWindow($c) : null,
            'lecturer' => $c['lecturer_id'] ? Db::one('SELECT id, name, lecturer_bio FROM users WHERE id = ?', [$c['lecturer_id']]) : null,
            'rating' => Community::ratingStats((int)$c['id']),
            'reviews' => Community::reviews((int)$c['id']),
            'announcements' => ($enrol && $enrol['status'] === 'active') || Auth::isAdmin() ? Community::announcements((int)$c['id']) : [],
            'myReview' => $u ? Db::one('SELECT * FROM course_reviews WHERE course_id = ? AND user_id = ?', [$c['id'], $u['id']]) : null,
        ]);
    }

    public function review(string $slug): void
    {
        $this->guard();
        csrf_check();
        Auth::requireLogin();
        $c = $this->courseOr404($slug);
        $u = Auth::user();
        $e = Learn::enrolment((int)$u['id'], (int)$c['id']);
        if (!$e || $e['status'] !== 'active') {
            flash('error', 'Only enrolled students can review a course.');
            redirect('learn/' . $c['slug']);
        }
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = mb_substr(trim((string)($_POST['comment'] ?? '')), 0, 1500);
        if ($rating < 1 || $rating > 5) {
            flash('error', 'Please choose a rating from 1 to 5 stars.');
            redirect('learn/' . $c['slug'] . '#reviews');
        }
        if (!rate_ok('review', 10, 3600)) {
            abort(429, 'Too many attempts. Please try again later.');
        }
        Db::run(
            "INSERT INTO course_reviews (course_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = 'pending', created_at = NOW()",
            [$c['id'], $u['id'], $rating, $comment]
        );
        Community::notifyOwner('New course review to approve', "{$u['name']} rated \"{$c['title']}\" $rating/5.\n\n$comment\n\nApprove or hide it: " . abs_url('admin/reviews'));
        flash('success', 'Thank you! Your review will appear once it has been approved.');
        redirect('learn/' . $c['slug'] . '#reviews');
    }

    public function ask(string $slug, string $id): void
    {
        $this->guard();
        csrf_check();
        Auth::requireLogin();
        $c = $this->courseOr404($slug);
        $l = Learn::lesson((int)$id);
        $u = Auth::user();
        if (!$l || (int)$l['course_id'] !== (int)$c['id']) {
            abort(404);
        }
        $back = 'learn/' . $c['slug'] . '/lesson/' . $l['id'] . '#questions';
        if (!Learn::hasAccess($u, (int)$c['id'])) {
            flash('error', 'Only enrolled students can ask questions.');
            redirect($back);
        }
        $q = mb_substr(trim((string)($_POST['question'] ?? '')), 0, 1500);
        if (mb_strlen($q) < 10) {
            flash('error', 'Please write your question in a little more detail (at least 10 characters).');
            redirect($back);
        }
        if (!rate_ok('ask', 8, 3600)) {
            abort(429, 'Too many questions in a short time. Please try again later.');
        }
        Db::insert('lesson_questions', ['course_id' => $c['id'], 'lesson_id' => $l['id'], 'user_id' => $u['id'], 'question' => $q]);
        Community::notifyOwner('New student question', "{$u['name']} asked in \"{$l['title']}\" (\"{$c['title']}\"):\n\n$q\n\nAnswer it: " . abs_url('admin/qa'));
        flash('success', 'Question sent. You will get an email when it is answered.');
        redirect($back);
    }

    public function enrol(string $slug): void
    {
        $this->guard();
        csrf_check();
        $c = $this->courseOr404($slug);
        if (!Auth::check()) {
            $_SESSION['intended'] = '/learn/' . $c['slug'];
            flash('info', 'Please sign in or create an account to enrol.');
            redirect('login');
        }
        if (!rate_ok('enrol', 10, 3600)) {
            abort(429, 'Too many enrolment attempts. Please try again later.');
        }
        try {
            $e = Learn::enrol((int)$_SESSION['uid'], $c, strtoupper(mb_substr(trim((string)($_POST['coupon'] ?? '')), 0, 40)));
        } catch (RuntimeException $ex) {
            flash('error', $ex->getMessage());
            redirect('learn/' . $c['slug']);
        }
        if ($e['status'] === 'active') {
            flash('success', 'You are enrolled. Welcome to the course!');
            redirect('learn/' . $c['slug']);
        }
        redirect('enrolment/' . $e['enrol_no']);
    }

    /* ------------------------------------------------------------------ lesson player */

    public function lesson(string $slug, string $id): void
    {
        $this->guard();
        $c = $this->courseOr404($slug);
        $l = Learn::lesson((int)$id);
        if (!$l || (int)$l['course_id'] !== (int)$c['id'] || ($l['status'] !== 'published' && !Auth::isAdmin())) {
            abort(404);
        }
        $u = Auth::check() ? Auth::user() : null;
        $allowed = Learn::canViewLesson($u, $l);
        $enrol = $u ? Learn::enrolment((int)$u['id'], (int)$c['id']) : null;
        $active = $u && Learn::hasAccess($u, (int)$c['id']);
        $order = array_map(fn($r) => (int)$r['id'], Learn::lessonOrder((int)$c['id']));
        $pos = array_search((int)$l['id'], $order, true);
        $progress = $u && $active ? Learn::progress((int)$u['id'], (int)$c['id']) : null;
        if ($allowed && $u && $enrol && $enrol['status'] === 'active' && (int)$enrol['last_lesson_id'] !== (int)$l['id']) {
            Db::update('enrolments', ['last_lesson_id' => $l['id']], 'id = ?', [$enrol['id']]);
        }
        view('public/lesson', [
            'title' => $l['title'] . ' | ' . $c['title'],
            'meta_desc' => excerpt((string)$l['objectives'], 150),
            'noindex' => !$allowed || (bool)$c['is_sample'],
            'c' => $c, 'l' => $l, 'cur' => Learn::curriculum((int)$c['id']), 'u' => $u, 'allowed' => $allowed, 'active' => $active, 'enrol' => $enrol,
            'progress' => $progress,
            'prev' => $pos !== false && $pos > 0 ? $order[$pos - 1] : null,
            'next' => $pos !== false && $pos < count($order) - 1 ? $order[$pos + 1] : null,
            'files' => $allowed ? Db::all('SELECT * FROM lesson_files WHERE lesson_id = ?', [$l['id']]) : [],
            'done' => $progress ? isset($progress['lessons'][(int)$l['id']]) : false,
            'questions' => $allowed && $active && $u ? Community::lessonQuestions((int)$l['id'], (int)$u['id']) : [],
            'needsCheck' => $active && $u && !Learn::manages($u, $c) && Learn::needsCheck((int)$u['id'], $l),
            'moduleLast' => Learn::lastInModule($l), 'manager' => Learn::manages($u, $c),
        ], 'layout/public');
    }

    /** Where the student goes after finishing a lesson: the next lesson, or the module summary after the last one of a module. */
    private function afterLessonUrl(array $c, array $l): string
    {
        if (Learn::lastInModule($l)) {
            return 'learn/' . $c['slug'] . '/module/' . $l['module_id'];
        }
        $order = array_map(fn($r) => (int)$r['id'], Learn::lessonOrder((int)$c['id']));
        $pos = array_search((int)$l['id'], $order, true);
        $next = $pos !== false && isset($order[$pos + 1]) ? $order[$pos + 1] : null;
        return $next ? 'learn/' . $c['slug'] . '/lesson/' . $next : 'learn/' . $c['slug'];
    }

    /* ------------------------------------------------------------------ check-up question after a lesson */

    /** @return array{0:array,1:array,2:array} course, lesson, user (enrolled student or administrator) */
    private function checkContext(string $slug, string $id): array
    {
        $this->guard();
        Auth::requireLogin();
        $c = $this->courseOr404($slug);
        $l = Learn::lesson((int)$id);
        $u = Auth::user();
        if (!$l || (int)$l['course_id'] !== (int)$c['id'] || ($l['status'] !== 'published' && !Auth::isAdmin())) {
            abort(404);
        }
        if (!Learn::hasAccess($u, (int)$c['id'])) {
            flash('info', 'Enrol in the course to answer its check-up questions.');
            redirect('learn/' . $c['slug']);
        }
        return [$c, $l, $u];
    }

    public function check(string $slug, string $id): void
    {
        [$c, $l, $u] = $this->checkContext($slug, $id);
        $bank = Learn::activeBank((int)$l['id']);
        $base = 'learn/' . $c['slug'] . '/lesson/' . $l['id'];
        if (!$bank) {
            redirect($base);
        }
        $row = null;
        $questions = [];
        if (!Learn::manages($u, $c)) {
            $row = Learn::startCheck((int)$u['id'], $l, $bank);
            $questions = Learn::attemptQuestions($row, $bank);
        }
        view('public/lesson_check', [
            'title' => 'Check-up: ' . $l['title'], 'noindex' => true, 'c' => $c, 'l' => $l, 'row' => $row, 'questions' => $questions,
            'res' => $row && $row['answered_at'] !== null ? (json_decode((string)$row['results'], true) ?: []) : [],
            'isAdmin' => Learn::manages($u, $c), 'bankSize' => (int)Db::val('SELECT COUNT(*) FROM questions WHERE quiz_id = ?', [$bank['id']]),
            'nextUrl' => $this->afterLessonUrl($c, $l), 'moduleLast' => Learn::lastInModule($l),
            'meta_desc' => '',
        ]);
    }

    public function checkSubmit(string $slug, string $id): void
    {
        [$c, $l, $u] = $this->checkContext($slug, $id);
        csrf_check();
        $self = 'learn/' . $c['slug'] . '/lesson/' . $l['id'] . '/check';
        $bank = Learn::activeBank((int)$l['id']);
        if (!$bank || Learn::manages($u, $c)) {
            redirect($self);
        }
        $row = Learn::startCheck((int)$u['id'], $l, $bank);
        if ($row['answered_at'] === null) {
            $answers = [];
            foreach ((array)($_POST['answers'] ?? []) as $qid => $v) {
                $answers[(int)$qid] = is_array($v) ? array_map('intval', $v) : mb_substr((string)$v, 0, 200);
            }
            $answered = false;
            foreach ($answers as $v) {
                if ($v !== '' && $v !== [] && $v !== null) {
                    $answered = true;
                }
            }
            if (!$answered) {
                flash('error', 'Please answer the question before you continue.');
                redirect($self);
            }
            Learn::answerCheck($row, $bank, $answers);
            Learn::markComplete((int)$u['id'], $l);
        }
        redirect($self);
    }

    /* ------------------------------------------------------------------ module summary and download */

    /** @return array{0:array,1:array,2:array} course, module, user */
    private function moduleContext(string $slug, string $mid): array
    {
        $this->guard();
        Auth::requireLogin();
        $c = $this->courseOr404($slug);
        $m = Learn::moduleOf((int)$c['id'], (int)$mid);
        $u = Auth::user();
        if (!$m) {
            abort(404);
        }
        if (!Learn::hasAccess($u, (int)$c['id'])) {
            flash('info', 'Enrol in the course to open the module summary.');
            redirect('learn/' . $c['slug']);
        }
        if (!Learn::manages($u, $c) && !Learn::moduleDone((int)$u['id'], (int)$m['id'])) {
            $open = Learn::firstOpenLesson((int)$u['id'], (int)$m['id']);
            flash('info', 'Finish every lesson in this module to unlock the summary and the module PDF.');
            redirect($open ? 'learn/' . $c['slug'] . '/lesson/' . $open : 'learn/' . $c['slug']);
        }
        return [$c, $m, $u];
    }

    public function module(string $slug, string $mid): void
    {
        [$c, $m, $u] = $this->moduleContext($slug, $mid);
        $next = Learn::nextModule((int)$c['id'], (int)$m['id']);
        $cert = Db::one('SELECT code FROM certificates WHERE user_id = ? AND course_id = ?', [$u['id'], $c['id']]);
        view('public/module_done', [
            'title' => 'Module complete: ' . $m['title'], 'noindex' => true, 'meta_desc' => '', 'c' => $c, 'm' => $m, 'isAdmin' => Learn::manages($u, $c),
            'num' => Learn::moduleNumber((int)$c['id'], (int)$m['id']), 'score' => Learn::moduleScore((int)$u['id'], (int)$m['id']),
            'next' => $next, 'cert' => $cert, 'hasPdf' => $m['pdf_path'] !== '',
        ]);
    }

    /** The module PDF the administrator uploaded; without one, a printable page of the module notes ("Save as PDF"). */
    public function modulePdf(string $slug, string $mid): void
    {
        [$c, $m] = $this->moduleContext($slug, $mid);
        if ($m['pdf_path'] === '') {
            redirect('learn/' . $c['slug'] . '/module/' . $m['id'] . '/notes?autoprint=1');
        }
        $full = realpath(ROOT . '/' . $m['pdf_path']);
        $base = realpath(ROOT . '/storage/private/course-files');
        if (!$full || !$base || !str_starts_with($full, $base . DIRECTORY_SEPARATOR) || !is_file($full)) {
            abort(404);
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . (slugify($c['title'] . '-' . $m['title']) ?: 'module') . '.pdf"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    public function moduleNotes(string $slug, string $mid): void
    {
        [$c, $m] = $this->moduleContext($slug, $mid);
        $lessons = Db::all("SELECT title, objectives, content FROM lessons WHERE module_id = ? AND status = 'published' ORDER BY sort_order, id", [$m['id']]);
        view('public/module_notes', ['title' => $c['title'] . ': ' . $m['title'], 'c' => $c, 'm' => $m, 'lessons' => $lessons, 'num' => Learn::moduleNumber((int)$c['id'], (int)$m['id'])], 'layout/print');
    }

    public function complete(string $slug, string $id): void
    {
        $this->guard();
        csrf_check();
        Auth::requireLogin();
        $c = $this->courseOr404($slug);
        $l = Learn::lesson((int)$id);
        $u = Auth::user();
        if (!$l || (int)$l['course_id'] !== (int)$c['id'] || !Learn::hasAccess($u, (int)$c['id'])) {
            abort(403, 'Enrol in this course to track your progress.');
        }
        if (!Learn::manages($u, $c)) {
            if (Learn::needsCheck((int)$u['id'], $l)) { // the check-up question must be answered first
                flash('info', 'Answer the check-up question to finish this lesson.');
                redirect('learn/' . $c['slug'] . '/lesson/' . $l['id'] . '/check');
            }
            Learn::markComplete((int)$u['id'], $l);
        }
        if (Db::val('SELECT 1 FROM certificates WHERE user_id = ? AND course_id = ?', [$u['id'], $c['id']])) {
            flash('success', 'Course complete! Your certificate is ready under My courses.');
        }
        redirect($this->afterLessonUrl($c, $l));
    }

    /* ------------------------------------------------------------------ protected media */

    public function video(string $id): void
    {
        $l = Learn::lesson((int)$id);
        $u = Auth::check() ? Auth::user() : null;
        if (!$l || $l['video_type'] !== 'file' || !Learn::canViewLesson($u, $l)) {
            abort(404);
        }
        if ($course = Learn::courseById((int)$l['course_id'])) {
            Learn::lockGate($course);
        }
        $full = realpath(ROOT . '/' . $l['video_ref']);
        $base = realpath(ROOT . '/storage/private/videos');
        if (!$full || !$base || !str_starts_with($full, $base . DIRECTORY_SEPARATOR) || !is_file($full)) {
            abort(404);
        }
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $type = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'm4v' => 'video/mp4'][$ext] ?? 'application/octet-stream';
        $size = filesize($full);
        $start = 0;
        $end = $size - 1;
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $type);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] !== '') {
                $start = (int)$m[1];
                $end = $m[2] !== '' ? min((int)$m[2], $size - 1) : $end;
            } elseif ($m[2] !== '') {
                $start = max(0, $size - (int)$m[2]);
            }
            if ($start > $end || $start >= $size) {
                http_response_code(416);
                header("Content-Range: bytes */$size");
                exit;
            }
            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");
        }
        header('Content-Length: ' . ($end - $start + 1));
        session_write_close(); // do not block other requests while streaming
        $fh = fopen($full, 'rb');
        fseek($fh, $start);
        $left = $end - $start + 1;
        while ($left > 0 && !feof($fh)) {
            $chunk = fread($fh, min(262144, $left));
            if ($chunk === false) {
                break;
            }
            echo $chunk;
            $left -= strlen($chunk);
            flush();
        }
        fclose($fh);
        exit;
    }

    public function file(string $id): void
    {
        $f = Db::one('SELECT * FROM lesson_files WHERE id = ?', [(int)$id]);
        $l = $f ? Learn::lesson((int)$f['lesson_id']) : null;
        $u = Auth::check() ? Auth::user() : null;
        if (!$f || !$l || !Learn::canViewLesson($u, $l)) {
            abort(404);
        }
        if ($course = Learn::courseById((int)$l['course_id'])) {
            Learn::lockGate($course);
        }
        $full = realpath(ROOT . '/' . $f['path']);
        $base = realpath(ROOT . '/storage/private/course-files');
        if (!$full || !$base || !str_starts_with($full, $base . DIRECTORY_SEPARATOR) || !is_file($full)) {
            abort(404);
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '-', $f['title']) . '.pdf"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    /* ------------------------------------------------------------------ quizzes */

    private function quizContext(string $slug, string $id): array
    {
        $this->guard();
        Auth::requireLogin();
        $c = $this->courseOr404($slug);
        $q = Learn::quiz((int)$id);
        $u = Auth::user();
        if (!$q || $q['kind'] === 'check' || (int)$q['course_id'] !== (int)$c['id'] || ($q['status'] !== 'published' && !Auth::isAdmin())) {
            abort(404);
        }
        if (!Learn::hasAccess($u, (int)$c['id'])) {
            flash('info', 'Enrol in the course to take its quizzes.');
            redirect('learn/' . $c['slug']);
        }
        return [$c, $q, $u];
    }

    public function quiz(string $slug, string $id): void
    {
        [$c, $q, $u] = $this->quizContext($slug, $id);
        $attempts = Db::all('SELECT * FROM quiz_attempts WHERE user_id = ? AND quiz_id = ? AND submitted_at IS NOT NULL ORDER BY id DESC', [$u['id'], $q['id']]);
        $open = Db::one('SELECT * FROM quiz_attempts WHERE user_id = ? AND quiz_id = ? AND submitted_at IS NULL ORDER BY id DESC LIMIT 1', [$u['id'], $q['id']]);
        view('public/quiz_intro', [
            'title' => $q['title'], 'c' => $c, 'q' => $q, 'attempts' => $attempts, 'open' => $open,
            'qcount' => (int)Db::val('SELECT COUNT(*) FROM questions WHERE quiz_id = ?', [$q['id']]),
        ]);
    }

    public function quizStart(string $slug, string $id): void
    {
        [$c, $q, $u] = $this->quizContext($slug, $id);
        csrf_check();
        try {
            $a = Learn::startAttempt((int)$u['id'], $q);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('learn/' . $c['slug'] . '/quiz/' . $q['id']);
        }
        redirect('learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/attempt/' . $a['id']);
    }

    private function attemptOr404(array $q, array $u, string $aid): array
    {
        $a = Db::one('SELECT * FROM quiz_attempts WHERE id = ? AND user_id = ? AND quiz_id = ?', [(int)$aid, $u['id'], $q['id']]);
        if (!$a) {
            abort(404);
        }
        return $a;
    }

    public function attempt(string $slug, string $id, string $aid): void
    {
        [$c, $q, $u] = $this->quizContext($slug, $id);
        $a = $this->attemptOr404($q, $u, $aid);
        if ($a['submitted_at'] !== null) {
            redirect('learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/result/' . $a['id']);
        }
        if ($a['deadline_at'] && strtotime($a['deadline_at']) + 60 < time()) {
            Learn::submitAttempt($a, $q, []);
            flash('error', 'Time ran out before the quiz was submitted.');
            redirect('learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/result/' . $a['id']);
        }
        view('public/quiz', ['title' => $q['title'], 'c' => $c, 'q' => $q, 'a' => $a, 'questions' => Learn::attemptQuestions($a, $q)]);
    }

    public function submit(string $slug, string $id, string $aid): void
    {
        [$c, $q, $u] = $this->quizContext($slug, $id);
        csrf_check();
        $a = $this->attemptOr404($q, $u, $aid);
        $answers = [];
        foreach ((array)($_POST['answers'] ?? []) as $qid => $v) {
            $answers[(int)$qid] = is_array($v) ? array_map('intval', $v) : mb_substr((string)$v, 0, 200);
        }
        Learn::submitAttempt($a, $q, $answers);
        redirect('learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/result/' . $a['id']);
    }

    public function result(string $slug, string $id, string $aid): void
    {
        [$c, $q, $u] = $this->quizContext($slug, $id);
        $a = $this->attemptOr404($q, $u, $aid);
        if ($a['submitted_at'] === null) {
            redirect('learn/' . $c['slug'] . '/quiz/' . $q['id'] . '/attempt/' . $a['id']);
        }
        $res = json_decode((string)$a['results'], true) ?: [];
        $cert = Db::one('SELECT code FROM certificates WHERE user_id = ? AND course_id = ?', [$u['id'], $c['id']]);
        view('public/quiz_result', [
            'title' => 'Results: ' . $q['title'], 'c' => $c, 'q' => $q, 'a' => $a, 'res' => $res['q'] ?? [], 'late' => !empty($res['late']),
            'questions' => Learn::attemptQuestions($a, $q), 'cert' => $cert,
            'left' => (int)$q['max_attempts'] > 0 ? max(0, (int)$q['max_attempts'] - Learn::submittedAttempts((int)$u['id'], (int)$q['id'])) : null,
        ]);
    }

    /* ------------------------------------------------------------------ enrolment page (payment) */

    private function enrolmentOr404(string $no): array
    {
        Auth::requireLogin();
        $e = Db::one('SELECT * FROM enrolments WHERE enrol_no = ?', [$no]);
        if (!$e || ((int)$e['user_id'] !== (int)$_SESSION['uid'] && !Auth::isAdmin())) {
            abort(404);
        }
        return $e;
    }

    public function enrolment(string $no): void
    {
        $this->guard();
        $e = $this->enrolmentOr404($no);
        form_started('receipt');
        view('public/enrolment', ['title' => 'Enrolment ' . $e['enrol_no'], 'e' => $e, 'c' => Learn::courseById((int)$e['course_id'])]);
    }

    public function receipt(string $no): void
    {
        $e = $this->enrolmentOr404($no);
        csrf_check();
        $back = 'enrolment/' . $e['enrol_no'];
        if (!form_human('receipt')) {
            flash('error', 'Please wait a moment and try again.');
            redirect($back);
        }
        if (!rate_ok('receipt', 10, 3600)) {
            abort(429, 'Too many uploads. Please try again later.');
        }
        if ($e['status'] !== 'awaiting_payment') {
            redirect($back);
        }
        try {
            $path = Upload::receipt($_FILES['receipt'] ?? []);
        } catch (RuntimeException $ex) {
            flash('error', $ex->getMessage());
            redirect($back);
        }
        Upload::removePrivate($e['receipt_path']);
        Db::update('enrolments', ['receipt_path' => $path, 'receipt_at' => date('Y-m-d H:i:s')], 'id = ?', [$e['id']]);
        Shop::mailAdmin('Receipt uploaded for enrolment ' . $e['enrol_no'], "A student uploaded a payment receipt for {$e['enrol_no']} (" . Shop::naira($e['total']) . ").\nConfirm it here: " . abs_url('admin/enrolments'));
        flash('success', 'Thank you! We have your receipt and will confirm your payment as soon as we see it in our account.');
        redirect($back);
    }

    public function cancelEnrolment(string $no): void
    {
        $e = $this->enrolmentOr404($no);
        csrf_check();
        try {
            Learn::cancel((int)$e['id'], true);
            flash('success', 'Enrolment cancelled.');
        } catch (RuntimeException $ex) {
            flash('error', $ex->getMessage());
        }
        redirect('account/courses');
    }

    /* ------------------------------------------------------------------ my courses & certificates */

    public function myCourses(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        $rows = Db::all(
            "SELECT e.*, c.title, c.slug, c.subtitle, c.cover_image,
                    (SELECT code FROM certificates x WHERE x.user_id = e.user_id AND x.course_id = e.course_id LIMIT 1) AS cert_code
             FROM enrolments e JOIN courses c ON c.id = e.course_id WHERE e.user_id = ? AND e.status <> 'cancelled' ORDER BY e.id DESC",
            [$u['id']]
        );
        foreach ($rows as &$r) {
            $r['progress'] = $r['status'] === 'active' ? Learn::progress((int)$u['id'], (int)$r['course_id']) : null;
        }
        view('public/my_courses', ['title' => 'My courses', 'tab' => 'courses', 'user' => $u, 'rows' => $rows]);
    }

    public function certificate(string $code): void
    {
        $cert = Db::one('SELECT * FROM certificates WHERE code = ?', [strtoupper($code)]);
        if (!$cert) {
            abort(404);
        }
        view('public/certificate_verify', ['title' => 'Certificate ' . $cert['code'], 'cert' => $cert, 'meta_desc' => 'Verification of a course completion certificate.']);
    }

    public function certificatePrint(string $code): void
    {
        $cert = Db::one('SELECT * FROM certificates WHERE code = ?', [strtoupper($code)]);
        if (!$cert) {
            abort(404);
        }
        view('public/certificate_print', ['title' => 'Certificate ' . $cert['code'], 'cert' => $cert], 'layout/print');
    }
}

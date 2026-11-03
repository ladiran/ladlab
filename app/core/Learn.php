<?php
declare(strict_types=1);

/** Courses: catalogue, access rules, enrolment, progress, quiz grading, completion and certificates. */
final class Learn
{
    public const ENROL_STATUS = ['awaiting_payment' => 'Awaiting payment', 'active' => 'Enrolled', 'cancelled' => 'Cancelled'];
    public const Q_TYPES = ['mcq' => 'Multiple choice (one answer)', 'multi' => 'Multiple choice (select all that apply)', 'truefalse' => 'True / False', 'numeric' => 'Numeric answer', 'short' => 'Short text answer'];

    /* ------------------------------------------------------------------ catalogue */

    /** Sample courses show only while there is no real published course. */
    public static function sampleFlag(): int
    {
        return Db::val("SELECT 1 FROM courses WHERE is_sample = 0 AND status = 'published' LIMIT 1") ? 0 : 1;
    }

    /** Published courses plus "coming soon" teasers. */
    public static function catalogue(array $f = []): array
    {
        $where = ["((c.status = 'published' AND c.is_sample = ?) OR c.status = 'soon')"];
        $params = [self::sampleFlag()];
        if (!empty($f['q'])) {
            $where[] = '(c.title LIKE ? OR c.subtitle LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$f['q']) . '%';
            array_push($params, $like, $like);
        }
        if (($f['price'] ?? '') === 'free') {
            $where[] = 'c.price = 0';
        }
        $rows = Db::all(
            "SELECT c.*, (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id AND l.status = 'published') AS lesson_count,
                    (SELECT COALESCE(SUM(l.video_minutes), 0) FROM lessons l WHERE l.course_id = c.id AND l.status = 'published') AS minutes,
                    (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.id AND e.status = 'active') AS students,
                    (SELECT COALESCE(AVG(r.rating), 0) FROM course_reviews r WHERE r.course_id = c.id AND r.status = 'published') AS rating_avg,
                    (SELECT COUNT(*) FROM course_reviews r WHERE r.course_id = c.id AND r.status = 'published') AS rating_count
             FROM courses c WHERE " . implode(' AND ', $where) . " ORDER BY c.status = 'soon', c.is_featured DESC, c.sort_order, c.id",
            $params
        );
        return $rows;
    }

    public static function course(string $slug): ?array
    {
        $c = Db::one('SELECT * FROM courses WHERE slug = ?', [$slug]);
        return $c && in_array($c['status'], ['published', 'soon'], true) ? $c : null;
    }

    public static function courseById(int $id): ?array
    {
        return Db::one('SELECT * FROM courses WHERE id = ?', [$id]);
    }

    /* ------------------------------------------------------------------ lockdown: a course closed for a period */

    /** The lock that is in force right now (null when the course is open). Administrators are exempted by the callers. */
    public static function lock(array $course): ?array
    {
        $until = (string)($course['lock_until'] ?? '');
        if ($until === '') {
            return null;
        }
        $now = date('Y-m-d H:i:s');
        $from = (string)($course['lock_from'] ?? '');
        return ($from === '' || $now >= $from) && $now < $until ? ['from' => $from, 'until' => $until] : null;
    }

    /** The lock that is in force or still to come (null when none is set or it has ended). */
    public static function lockWindow(array $course): ?array
    {
        $until = (string)($course['lock_until'] ?? '');
        if ($until === '' || date('Y-m-d H:i:s') >= $until) {
            return null;
        }
        return ['from' => (string)($course['lock_from'] ?? ''), 'until' => $until, 'active' => self::lock($course) !== null];
    }

    /** "6:00 PM, 25 October 2026" */
    public static function lockText(string $datetime): string
    {
        $ts = strtotime($datetime);
        return $ts ? date('g:i A, j F Y', $ts) : $datetime;
    }

    /** The page students and visitors see while a course is locked. */
    public static function lockedPage(array $course, array $lock): never
    {
        http_response_code(503);
        header('Retry-After: ' . max(60, (int)(strtotime($lock['until']) - time())));
        header('Cache-Control: no-store');
        view('public/course_locked', ['title' => 'Course not available', 'noindex' => true, 'c' => $course, 'until' => self::lockText($lock['until'])]);
        exit;
    }

    /** Finds the course for a visitor: 404 for unknown or hidden sample courses, the locked page during a lockdown (not for administrators). */
    public static function accessibleCourse(string $slug): array
    {
        $c = self::course($slug);
        if (!$c) {
            abort(404);
        }
        // sample courses disappear once a real one is published
        if ($c['status'] === 'published' && (int)$c['is_sample'] !== self::sampleFlag() && !Auth::isAdmin()) {
            abort(404);
        }
        self::lockGate($c);
        return $c;
    }

    /** Shows the locked page instead of continuing, unless the course is open or the visitor is an administrator. */
    public static function lockGate(array $course): void
    {
        if (!Auth::isAdmin() && ($lock = self::lock($course))) {
            self::lockedPage($course, $lock);
        }
    }

    /**
     * Modules with their published lessons and quiz, plus course-level quizzes (final exam).
     * @return array{modules: array, final: array, lessons: int, quizzes: int, minutes: int}
     */
    public static function curriculum(int $courseId, bool $publishedOnly = true): array
    {
        $st = $publishedOnly ? " AND status = 'published'" : '';
        $modules = Db::all('SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order, id', [$courseId]);
        $lessons = Db::all("SELECT id, course_id, module_id, title, sort_order, video_type, video_minutes, is_free_preview, status, (SELECT COUNT(*) FROM questions x JOIN quizzes z ON z.id = x.quiz_id WHERE z.lesson_id = lessons.id AND z.kind = 'check') AS bank_count FROM lessons WHERE course_id = ? $st ORDER BY sort_order, id", [$courseId]);
        $quizzes = Db::all("SELECT q.*, (SELECT COUNT(*) FROM questions x WHERE x.quiz_id = q.id) AS question_count FROM quizzes q WHERE q.course_id = ? AND q.kind <> 'check' $st ORDER BY q.sort_order, q.id", [$courseId]);
        $byModule = [];
        foreach ($lessons as $l) {
            $byModule[(int)$l['module_id']]['lessons'][] = $l;
        }
        $out = [];
        $final = [];
        foreach ($modules as $m) {
            $out[] = ['module' => $m, 'lessons' => $byModule[(int)$m['id']]['lessons'] ?? [], 'quizzes' => array_values(array_filter($quizzes, fn($q) => (int)$q['module_id'] === (int)$m['id']))];
        }
        foreach ($quizzes as $q) {
            if (!$q['module_id']) {
                $final[] = $q;
            }
        }
        return ['modules' => $out, 'final' => $final, 'lessons' => count($lessons), 'quizzes' => count($quizzes), 'minutes' => array_sum(array_column($lessons, 'video_minutes'))];
    }

    /** Flat ordered list of lessons (for previous/next navigation). */
    public static function lessonOrder(int $courseId): array
    {
        return Db::all(
            "SELECT l.id FROM lessons l JOIN course_modules m ON m.id = l.module_id WHERE l.course_id = ? AND l.status = 'published' ORDER BY m.sort_order, m.id, l.sort_order, l.id",
            [$courseId]
        );
    }

    public static function lesson(int $id): ?array
    {
        return Db::one('SELECT * FROM lessons WHERE id = ?', [$id]);
    }

    /* ------------------------------------------------------------------ enrolment & access */

    public static function enrolment(int $userId, int $courseId): ?array
    {
        return Db::one('SELECT * FROM enrolments WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
    }

    public static function hasAccess(?array $user, int $courseId): bool
    {
        if (!$user) {
            return false;
        }
        if ($user['role'] === 'admin') {
            return true; // administrators can preview everything
        }
        $e = self::enrolment((int)$user['id'], $courseId);
        return $e !== null && $e['status'] === 'active';
    }

    public static function canViewLesson(?array $user, array $lesson): bool
    {
        return (int)$lesson['is_free_preview'] === 1 || self::hasAccess($user, (int)$lesson['course_id']);
    }

    /** Creates the enrolment. Free (or fully discounted) courses are activated immediately. */
    public static function enrol(int $userId, array $course, string $couponCode = ''): array
    {
        if ($course['status'] !== 'published') {
            throw new RuntimeException('This course is not open for enrolment yet.');
        }
        $existing = self::enrolment($userId, (int)$course['id']);
        if ($existing && $existing['status'] !== 'cancelled') {
            return $existing;
        }
        $price = (int)$course['price'];
        $coupon = null;
        if ($couponCode !== '') {
            [$coupon, $err] = Shop::coupon($couponCode, $price);
            if (!$coupon) {
                throw new RuntimeException($err ?: 'That coupon code is not valid.');
            }
        }
        $discount = $coupon ? Shop::discountFor($coupon, $price) : 0;
        $total = $price - $discount;
        $free = $total === 0;
        $data = [
            'status' => $free ? 'active' : 'awaiting_payment', 'price' => $price, 'discount' => $discount, 'total' => $total,
            'coupon_code' => $coupon ? $coupon['code'] : '', 'paid_at' => $free ? date('Y-m-d H:i:s') : null,
            'source' => $free ? 'free' : 'purchase', 'receipt_path' => '', 'receipt_at' => null,
        ];
        if ($existing) { // re-enrolling after a cancellation
            Db::update('enrolments', $data, 'id = ?', [$existing['id']]);
            $id = (int)$existing['id'];
        } else {
            $id = Db::insert('enrolments', $data + ['user_id' => $userId, 'course_id' => $course['id']]);
        }
        Db::update('enrolments', ['enrol_no' => 'ENR-' . (1000 + $id)], 'id = ?', [$id]);
        if ($coupon) {
            Db::run('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$coupon['id']]);
        }
        $e = Db::one('SELECT * FROM enrolments WHERE id = ?', [$id]);
        self::mail($e, $free ? 'welcome' : 'placed');
        if (!$free) {
            Shop::mailAdmin('New course enrolment ' . $e['enrol_no'], 'A student enrolled in "' . $course['title'] . '" (' . Shop::naira($total) . ", awaiting payment).\nOpen in admin: " . abs_url('admin/enrolments'));
        }
        return $e;
    }

    /** Admin: gives a registered student access without payment (scholarship, class group, testing). */
    public static function grant(int $userId, int $courseId): array
    {
        $existing = self::enrolment($userId, $courseId);
        if ($existing) {
            if ($existing['status'] !== 'active') {
                Db::update('enrolments', ['status' => 'active', 'paid_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
                self::mail(Db::one('SELECT * FROM enrolments WHERE id = ?', [$existing['id']]), 'welcome');
            }
            return Db::one('SELECT * FROM enrolments WHERE id = ?', [$existing['id']]);
        }
        $id = Db::insert('enrolments', ['user_id' => $userId, 'course_id' => $courseId, 'status' => 'active', 'source' => 'manual', 'paid_at' => date('Y-m-d H:i:s')]);
        Db::update('enrolments', ['enrol_no' => 'ENR-' . (1000 + $id)], 'id = ?', [$id]);
        $e = Db::one('SELECT * FROM enrolments WHERE id = ?', [$id]);
        self::mail($e, 'welcome');
        return $e;
    }

    public static function confirmPayment(int $enrolId): void
    {
        $e = Db::one('SELECT * FROM enrolments WHERE id = ?', [$enrolId]);
        if (!$e || $e['status'] !== 'awaiting_payment') {
            throw new RuntimeException('Only enrolments awaiting payment can be confirmed.');
        }
        Db::update('enrolments', ['status' => 'active', 'paid_at' => date('Y-m-d H:i:s')], 'id = ?', [$enrolId]);
        self::mail(Db::one('SELECT * FROM enrolments WHERE id = ?', [$enrolId]), 'welcome');
    }

    public static function cancel(int $enrolId, bool $byStudent = false): void
    {
        $e = Db::one('SELECT * FROM enrolments WHERE id = ?', [$enrolId]);
        if (!$e || $e['status'] === 'cancelled' || ($byStudent && $e['status'] !== 'awaiting_payment')) {
            throw new RuntimeException('This enrolment cannot be cancelled.');
        }
        Db::update('enrolments', ['status' => 'cancelled'], 'id = ?', [$enrolId]);
        if ($e['coupon_code'] !== '') {
            Db::run('UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE code = ?', [$e['coupon_code']]);
        }
    }

    /* ------------------------------------------------------------------ progress */

    /** @return array{done:int,total:int,pct:int,lessons:array<int,bool>,quizzes:array<int,array>} */
    public static function progress(int $userId, int $courseId): array
    {
        $lessonIds = array_column(Db::all("SELECT id FROM lessons WHERE course_id = ? AND status = 'published'", [$courseId]), 'id');
        $quizIds = array_column(Db::all("SELECT id FROM quizzes WHERE course_id = ? AND kind <> 'check' AND status = 'published'", [$courseId]), 'id');
        $done = array_column(Db::all('SELECT p.lesson_id FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = ? AND l.course_id = ?', [$userId, $courseId]), 'lesson_id');
        $quizzes = [];
        foreach (Db::all('SELECT quiz_id, MAX(score) AS best, MAX(passed) AS passed, COUNT(*) AS attempts FROM quiz_attempts WHERE user_id = ? AND submitted_at IS NOT NULL AND quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?) GROUP BY quiz_id', [$userId, $courseId]) as $r) {
            $quizzes[(int)$r['quiz_id']] = ['best' => (int)$r['best'], 'passed' => (bool)$r['passed'], 'attempts' => (int)$r['attempts']];
        }
        $lessonsDone = array_intersect(array_map('intval', $done), array_map('intval', $lessonIds));
        $quizzesPassed = count(array_filter($quizzes, fn($q, $id) => $q['passed'] && in_array($id, array_map('intval', $quizIds), true), ARRAY_FILTER_USE_BOTH));
        $total = count($lessonIds) + count($quizIds);
        $doneCount = count($lessonsDone) + $quizzesPassed;
        return [
            'done' => $doneCount, 'total' => $total, 'pct' => $total ? (int)floor($doneCount * 100 / $total) : 0,
            'lessons' => array_fill_keys(array_map('intval', $lessonsDone), true), 'quizzes' => $quizzes,
            'complete' => $total > 0 && $doneCount >= $total,
        ];
    }

    public static function markComplete(int $userId, array $lesson): void
    {
        Db::run('INSERT IGNORE INTO lesson_progress (user_id, lesson_id) VALUES (?, ?)', [$userId, $lesson['id']]);
        Db::run('UPDATE enrolments SET last_lesson_id = ? WHERE user_id = ? AND course_id = ?', [$lesson['id'], $userId, $lesson['course_id']]);
        self::refreshCompletion($userId, (int)$lesson['course_id']);
    }

    /** When every lesson is done and every quiz passed: mark the course complete and issue the certificate. */
    public static function refreshCompletion(int $userId, int $courseId): ?array
    {
        $e = self::enrolment($userId, $courseId);
        if (!$e || $e['status'] !== 'active' || $e['completed_at'] !== null) {
            return null;
        }
        if (!self::progress($userId, $courseId)['complete']) {
            return null;
        }
        Db::update('enrolments', ['completed_at' => date('Y-m-d H:i:s')], 'id = ?', [$e['id']]);
        $course = self::courseById($courseId);
        if (!$course || !(int)$course['certificate_enabled']) {
            return null;
        }
        return self::issueCertificate($userId, $course);
    }

    public static function issueCertificate(int $userId, array $course): array
    {
        $have = Db::one('SELECT * FROM certificates WHERE user_id = ? AND course_id = ?', [$userId, $course['id']]);
        if ($have) {
            return $have;
        }
        $user = Db::one('SELECT name, email FROM users WHERE id = ?', [$userId]);
        do {
            $h = strtoupper(bin2hex(random_bytes(6)));
            $code = 'CSA-' . substr($h, 0, 4) . '-' . substr($h, 4, 4) . '-' . substr($h, 8, 4);
        } while (Db::val('SELECT 1 FROM certificates WHERE code = ?', [$code]));
        $id = Db::insert('certificates', ['code' => $code, 'user_id' => $userId, 'course_id' => $course['id'], 'student_name' => $user['name'], 'course_title' => $course['title']]);
        $cert = Db::one('SELECT * FROM certificates WHERE id = ?', [$id]);
        Mailer::send($user['email'], '[' . Settings::get('site_name') . '] Congratulations: your certificate for ' . $course['title'], "Hello {$user['name']},\n\nYou have completed \"{$course['title']}\". Well done!\n\nYour certificate (verification code {$code}):\n" . abs_url('certificate/' . $code) . "\n\nAnyone can verify it at that address.");
        return $cert;
    }

    /* ------------------------------------------------------------------ quizzes */

    public static function quiz(int $id): ?array
    {
        return Db::one('SELECT * FROM quizzes WHERE id = ?', [$id]);
    }

    public static function questions(int $quizId): array
    {
        $qs = Db::all('SELECT * FROM questions WHERE quiz_id = ? ORDER BY sort_order, id', [$quizId]);
        foreach ($qs as &$q) {
            $q['options'] = Db::all('SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order, id', [$q['id']]);
        }
        return $qs;
    }

    public static function submittedAttempts(int $userId, int $quizId): int
    {
        return (int)Db::val('SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ? AND quiz_id = ? AND submitted_at IS NOT NULL', [$userId, $quizId]);
    }

    /** Starts (or resumes) an attempt: draws and orders the questions and sets the deadline. */
    public static function startAttempt(int $userId, array $quiz): array
    {
        $open = Db::one('SELECT * FROM quiz_attempts WHERE user_id = ? AND quiz_id = ? AND submitted_at IS NULL ORDER BY id DESC LIMIT 1', [$userId, $quiz['id']]);
        if ($open && (!$open['deadline_at'] || strtotime($open['deadline_at']) + 60 > time())) {
            return $open;
        }
        if ($open) { // an expired, never-submitted attempt counts as used
            Db::update('quiz_attempts', ['submitted_at' => date('Y-m-d H:i:s'), 'score' => 0, 'passed' => 0, 'results' => json_encode(['expired' => true])], 'id = ?', [$open['id']]);
        }
        $max = (int)$quiz['max_attempts'];
        if ($max > 0 && self::submittedAttempts($userId, (int)$quiz['id']) >= $max) {
            throw new RuntimeException('You have used all ' . $max . ' attempts for this quiz.');
        }
        $ids = array_column(Db::all('SELECT id FROM questions WHERE quiz_id = ? ORDER BY sort_order, id', [$quiz['id']]), 'id');
        if (!$ids) {
            throw new RuntimeException('This quiz has no questions yet.');
        }
        $draw = (int)$quiz['questions_to_draw'];
        if ($draw > 0 && $draw < count($ids)) {
            shuffle($ids);
            $ids = array_slice($ids, 0, $draw);
            sort($ids);
        }
        if ((int)$quiz['shuffle']) {
            shuffle($ids);
        }
        $deadline = (int)$quiz['time_limit_minutes'] > 0 ? date('Y-m-d H:i:s', time() + (int)$quiz['time_limit_minutes'] * 60) : null;
        $id = Db::insert('quiz_attempts', ['user_id' => $userId, 'quiz_id' => $quiz['id'], 'question_ids' => json_encode(array_map('intval', $ids)), 'deadline_at' => $deadline]);
        return Db::one('SELECT * FROM quiz_attempts WHERE id = ?', [$id]);
    }

    /** Questions of an attempt, in attempt order, with options in a stable per-attempt order. */
    public static function attemptQuestions(array $attempt, array $quiz): array
    {
        $ids = json_decode((string)$attempt['question_ids'], true) ?: [];
        $out = [];
        foreach (self::questions((int)$quiz['id']) as $q) {
            $out[(int)$q['id']] = $q;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($out[(int)$id])) {
                $q = $out[(int)$id];
                if ((int)$quiz['shuffle'] && in_array($q['type'], ['mcq', 'multi'], true)) {
                    mt_srand((int)$attempt['id'] * 7919 + (int)$q['id']);
                    shuffle($q['options']);
                    mt_srand();
                }
                $ordered[] = $q;
            }
        }
        return $ordered;
    }

    /** Parses "58.9", "5.89e1", "1,200", "58.9 J" → [float, unit text] or null. */
    public static function parseNumber(string $s): ?array
    {
        $s = trim(str_replace(["\u{2212}", "\u{2013}", ' '], ['-', '-', ' '], $s));
        $s = preg_replace('/(?<=\d),(?=\d{3}\b)/', '', $s);
        if (!preg_match('/^([+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)\s*(.*)$/u', (string)$s, $m)) {
            return null;
        }
        return [(float)$m[1], trim($m[2])];
    }

    private static function normUnit(string $u): string
    {
        return strtolower(str_replace([' ', '^', '*', "\u{00B7}", "\u{22C5}", '·', "\u{00B2}", "\u{00B3}", "\u{03BC}", "\u{00B5}"], ['', '', '', '', '', '', '2', '3', 'u', 'u'], $u));
    }

    /** @return array{0:float,1:bool,2:string} [points earned, fully correct, correct answer text for display] */
    public static function gradeQuestion(array $q, mixed $resp): array
    {
        $pts = (float)$q['points'];
        switch ($q['type']) {
            case 'mcq':
            case 'truefalse':
                $correct = array_values(array_filter($q['options'], fn($o) => (int)$o['is_correct'] === 1));
                $ok = $resp !== null && $resp !== '' && $correct && (int)$correct[0]['id'] === (int)$resp;
                return [$ok ? $pts : 0.0, $ok, implode(' / ', array_column($correct, 'label'))];
            case 'multi':
                $want = array_map('intval', array_column(array_filter($q['options'], fn($o) => (int)$o['is_correct'] === 1), 'id'));
                $got = array_map('intval', is_array($resp) ? $resp : []);
                sort($want);
                sort($got);
                $ok = $want === $got;
                return [$ok ? $pts : 0.0, $ok, implode('; ', array_column(array_filter($q['options'], fn($o) => (int)$o['is_correct'] === 1), 'label'))];
            case 'numeric':
                $expected = (float)$q['num_answer'];
                $show = rtrim(rtrim(number_format($expected, 6, '.', ''), '0'), '.') . ($q['num_unit'] !== '' ? ' ' . $q['num_unit'] : '');
                $parsed = is_string($resp) ? self::parseNumber($resp) : null;
                if ($parsed === null) {
                    return [0.0, false, $show];
                }
                [$val, $unit] = $parsed;
                if ($unit !== '' && $q['num_unit'] !== '' && self::normUnit($unit) !== self::normUnit($q['num_unit'])) {
                    return [0.0, false, $show];
                }
                $tol = max(0.0, (float)$q['num_tolerance']) / 100;
                $ok = $expected == 0.0 ? abs($val) <= 1e-6 : abs($val - $expected) <= abs($expected) * $tol + 1e-12;
                return [$ok ? $pts : 0.0, $ok, $show];
            case 'short':
                $norm = fn(string $s) => trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', mb_strtolower($s))));
                $accepted = array_map($norm, nl_lines((string)$q['accepted']));
                $given = is_string($resp) ? $norm($resp) : '';
                $ok = $given !== '' && in_array($given, $accepted, true);
                return [$ok ? $pts : 0.0, $ok, nl_lines((string)$q['accepted'])[0] ?? ''];
        }
        return [0.0, false, ''];
    }

    /** Grades and stores an attempt. Returns the updated attempt row. */
    public static function submitAttempt(array $attempt, array $quiz, array $answers): array
    {
        if ($attempt['submitted_at'] !== null) {
            return $attempt;
        }
        $late = $attempt['deadline_at'] && strtotime($attempt['deadline_at']) + 60 < time();
        $results = [];
        $earned = $total = 0.0;
        foreach (self::attemptQuestions($attempt, $quiz) as $q) {
            $resp = $answers[$q['id']] ?? null;
            [$pts, $ok, $expected] = self::gradeQuestion($q, $late ? null : $resp);
            $earned += $pts;
            $total += (float)$q['points'];
            $results[(int)$q['id']] = ['ok' => $ok, 'earned' => $pts, 'max' => (int)$q['points'], 'given' => $late ? null : $resp, 'expected' => $expected];
        }
        $score = $total > 0 ? (int)round($earned * 100 / $total) : 0;
        $passed = $score >= (int)$quiz['pass_mark'];
        Db::update('quiz_attempts', [
            'answers' => json_encode($answers), 'results' => json_encode(['late' => $late, 'q' => $results]), 'score' => $score,
            'passed' => $passed ? 1 : 0, 'submitted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$attempt['id']]);
        if ($passed) {
            self::refreshCompletion((int)$attempt['user_id'], (int)$quiz['course_id']);
        }
        return Db::one('SELECT * FROM quiz_attempts WHERE id = ?', [$attempt['id']]);
    }

    /* ------------------------------------------------------------------ check-up questions (a bank per lesson) */

    /** The question bank of a lesson (a hidden quiz of kind "check"), or null. */
    public static function bank(int $lessonId): ?array
    {
        return Db::one("SELECT * FROM quizzes WHERE lesson_id = ? AND kind = 'check'", [$lessonId]);
    }

    /** The bank, only when it is published and holds at least one question. */
    public static function activeBank(int $lessonId): ?array
    {
        $b = self::bank($lessonId);
        if (!$b || $b['status'] !== 'published') {
            return null;
        }
        return Db::val('SELECT 1 FROM questions WHERE quiz_id = ? LIMIT 1', [$b['id']]) ? $b : null;
    }

    public static function ensureBank(array $lesson): array
    {
        $b = self::bank((int)$lesson['id']);
        if ($b) {
            return $b;
        }
        $id = Db::insert('quizzes', [
            'course_id' => $lesson['course_id'], 'module_id' => $lesson['module_id'], 'lesson_id' => $lesson['id'], 'kind' => 'check',
            'title' => mb_substr('Check-up: ' . $lesson['title'], 0, 200), 'pass_mark' => 1, 'questions_to_draw' => 1, 'shuffle' => 1, 'status' => 'published', 'sort_order' => 0,
        ]);
        return Db::one('SELECT * FROM quizzes WHERE id = ?', [$id]);
    }

    public static function checkRow(int $userId, int $lessonId): ?array
    {
        return Db::one('SELECT * FROM lesson_checks WHERE user_id = ? AND lesson_id = ?', [$userId, $lessonId]);
    }

    /** True while a student still has to answer this lesson's check-up (lessons already completed are never re-opened). */
    public static function needsCheck(int $userId, array $lesson): bool
    {
        if (!self::activeBank((int)$lesson['id'])) {
            return false;
        }
        if (Db::val('SELECT 1 FROM lesson_progress WHERE user_id = ? AND lesson_id = ?', [$userId, $lesson['id']])) {
            return false;
        }
        $r = self::checkRow($userId, (int)$lesson['id']);
        return !$r || $r['answered_at'] === null;
    }

    /** Draws (once) the question(s) this student must answer for the lesson. */
    public static function startCheck(int $userId, array $lesson, array $bank): array
    {
        $row = self::checkRow($userId, (int)$lesson['id']);
        $all = array_map('intval', array_column(Db::all('SELECT id FROM questions WHERE quiz_id = ? ORDER BY sort_order, id', [$bank['id']]), 'id'));
        if ($row) {
            $still = array_intersect(array_map('intval', json_decode((string)$row['question_ids'], true) ?: []), $all);
            if ($row['answered_at'] !== null || $still) {
                return $row;
            }
            Db::delete('lesson_checks', 'id = ?', [$row['id']]); // every drawn question was deleted meanwhile: draw again
        }
        $draw = max(1, (int)$bank['questions_to_draw']);
        shuffle($all);
        $ids = array_slice($all, 0, $draw);
        sort($ids);
        Db::run('INSERT IGNORE INTO lesson_checks (user_id, lesson_id, question_ids) VALUES (?, ?, ?)', [$userId, $lesson['id'], json_encode($ids)]);
        return self::checkRow($userId, (int)$lesson['id']);
    }

    /** Grades and stores the answer(s). The result is for the student's information only. */
    public static function answerCheck(array $row, array $bank, array $answers): array
    {
        if ($row['answered_at'] !== null) {
            return $row;
        }
        $results = [];
        $earned = $total = 0.0;
        foreach (self::attemptQuestions($row, $bank) as $q) {
            $resp = $answers[$q['id']] ?? null;
            [$pts, $ok, $expected] = self::gradeQuestion($q, $resp);
            $earned += $pts;
            $total += (float)$q['points'];
            $results[(int)$q['id']] = ['ok' => $ok, 'earned' => $pts, 'max' => (int)$q['points'], 'given' => $resp, 'expected' => $expected];
        }
        Db::update('lesson_checks', ['answers' => json_encode($answers), 'results' => json_encode($results), 'earned' => $earned, 'max_points' => (int)$total, 'answered_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['id']]);
        return Db::one('SELECT * FROM lesson_checks WHERE id = ?', [$row['id']]);
    }

    public static function lastInModule(array $lesson): bool
    {
        return !Db::val(
            "SELECT 1 FROM lessons WHERE module_id = ? AND status = 'published' AND (sort_order > ? OR (sort_order = ? AND id > ?)) LIMIT 1",
            [$lesson['module_id'], $lesson['sort_order'], $lesson['sort_order'], $lesson['id']]
        );
    }

    public static function moduleOf(int $courseId, int $moduleId): ?array
    {
        return Db::one('SELECT * FROM course_modules WHERE id = ? AND course_id = ?', [$moduleId, $courseId]);
    }

    /** First published lesson of the module the student has not completed yet (null when the module is finished). */
    public static function firstOpenLesson(int $userId, int $moduleId): ?int
    {
        $id = Db::val(
            "SELECT l.id FROM lessons l WHERE l.module_id = ? AND l.status = 'published' AND NOT EXISTS (SELECT 1 FROM lesson_progress p WHERE p.lesson_id = l.id AND p.user_id = ?) ORDER BY l.sort_order, l.id LIMIT 1",
            [$moduleId, $userId]
        );
        return $id ? (int)$id : null;
    }

    public static function moduleDone(int $userId, int $moduleId): bool
    {
        return (bool)Db::val("SELECT 1 FROM lessons WHERE module_id = ? AND status = 'published' LIMIT 1", [$moduleId]) && self::firstOpenLesson($userId, $moduleId) === null;
    }

    /** Total of the check-up answers in a module (for the student's information only). */
    public static function moduleScore(int $userId, int $moduleId): array
    {
        $lessons = Db::all("SELECT id, title FROM lessons WHERE module_id = ? AND status = 'published' ORDER BY sort_order, id", [$moduleId]);
        $rows = [];
        foreach (Db::all('SELECT lesson_id, earned, max_points FROM lesson_checks WHERE user_id = ? AND answered_at IS NOT NULL AND lesson_id IN (SELECT id FROM lessons WHERE module_id = ?)', [$userId, $moduleId]) as $r) {
            $rows[(int)$r['lesson_id']] = $r;
        }
        $earned = $max = 0.0;
        $out = [];
        foreach ($lessons as $l) {
            $r = $rows[(int)$l['id']] ?? null;
            if ($r) {
                $earned += (float)$r['earned'];
                $max += (float)$r['max_points'];
            }
            $out[] = ['id' => (int)$l['id'], 'title' => $l['title'], 'earned' => $r ? (float)$r['earned'] : null, 'max' => $r ? (int)$r['max_points'] : null];
        }
        return ['lessons' => $out, 'earned' => $earned, 'max' => $max, 'pct' => $max > 0 ? (int)round($earned * 100 / $max) : null];
    }

    /** The next module that has lessons, with the id of its first lesson; null after the last module. */
    public static function nextModule(int $courseId, int $moduleId): ?array
    {
        $found = false;
        foreach (Db::all('SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order, id', [$courseId]) as $m) {
            if ($found) {
                $first = Db::val("SELECT id FROM lessons WHERE module_id = ? AND status = 'published' ORDER BY sort_order, id LIMIT 1", [$m['id']]);
                if ($first) {
                    return ['module' => $m, 'lesson' => (int)$first];
                }
            }
            if ((int)$m['id'] === $moduleId) {
                $found = true;
            }
        }
        return null;
    }

    /** Position (1-based) of a module in its course. */
    public static function moduleNumber(int $courseId, int $moduleId): int
    {
        $ids = array_map('intval', array_column(Db::all('SELECT id FROM course_modules WHERE course_id = ? ORDER BY sort_order, id', [$courseId]), 'id'));
        $i = array_search($moduleId, $ids, true);
        return $i === false ? 1 : $i + 1;
    }

    /* ------------------------------------------------------------------ video helpers */

    /** @return array{0:string,1:string}|null [type, id] for a YouTube or Vimeo link. */
    public static function parseVideoUrl(string $url): ?array
    {
        $url = trim($url);
        if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return ['youtube', $m[1]];
        }
        if (preg_match('~^https?://(?:www\.)?(?:player\.)?vimeo\.com/(?:video/)?(\d{6,12})~', $url, $m)) {
            return ['vimeo', $m[1]];
        }
        return null;
    }

    /* ------------------------------------------------------------------ emails */

    public static function mail(array $e, string $kind): void
    {
        $user = Db::one('SELECT name, email FROM users WHERE id = ?', [$e['user_id']]);
        $course = self::courseById((int)$e['course_id']);
        if (!$user || !$course) {
            return;
        }
        $site = Settings::get('site_name');
        $link = abs_url('learn/' . $course['slug']);
        if ($kind === 'placed') {
            $body = "Hello {$user['name']},\n\nThank you for enrolling in \"{$course['title']}\" (reference {$e['enrol_no']}).\n\nPLEASE PAY BY BANK TRANSFER:\nBank: " . Settings::get('bank_name') . "\nAccount name: " . Settings::get('bank_account_name') . "\nAccount number: " . Settings::get('bank_account_number') . "\nAmount: " . Shop::naira($e['total']) . "\nReference / narration: {$e['enrol_no']}\n\nAfter you pay you can upload your receipt here:\n" . abs_url('enrolment/' . $e['enrol_no']) . "\n\nYour access starts as soon as we confirm the payment.";
            Mailer::send($user['email'], "[$site] Enrolment {$e['enrol_no']}: payment details", $body);
        } elseif ($kind === 'welcome') {
            Mailer::send($user['email'], "[$site] You are enrolled: {$course['title']}", "Hello {$user['name']},\n\nYou now have full access to \"{$course['title']}\". Start learning:\n$link\n\nGood luck!\n$site");
        }
    }
}

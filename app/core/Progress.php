<?php
declare(strict_types=1);

/**
 * Student progress for one course, for the administrator and for the course's lecturer:
 * lessons completed, check-up scores per module (for information only), quiz and final-exam scores, and overall performance.
 * Only students with an active enrolment in the course are included.
 */
final class Progress
{
    /** Published quizzes of the course (module quizzes and the final exam; the per-lesson check-up banks are not quizzes). */
    private static function quizzes(int $courseId): array
    {
        return Db::all("SELECT id, module_id, title, kind, pass_mark FROM quizzes WHERE course_id = ? AND kind <> 'check' AND status = 'published' ORDER BY (kind = 'final'), sort_order, id", [$courseId]);
    }

    private static function lessonTotal(int $courseId): int
    {
        return (int)Db::val("SELECT COUNT(*) FROM lessons WHERE course_id = ? AND status = 'published'", [$courseId]);
    }

    /** @return array<int, array<int, array{best:int, passed:bool, attempts:int, last:?string}>> user id → quiz id → best result */
    private static function quizResults(int $courseId): array
    {
        $out = [];
        foreach (Db::all(
            "SELECT a.user_id, a.quiz_id, MAX(a.score) AS best, MAX(a.passed) AS passed, COUNT(*) AS attempts, MAX(a.submitted_at) AS last
             FROM quiz_attempts a JOIN quizzes q ON q.id = a.quiz_id
             WHERE q.course_id = ? AND q.kind <> 'check' AND a.submitted_at IS NOT NULL GROUP BY a.user_id, a.quiz_id",
            [$courseId]
        ) as $r) {
            $out[(int)$r['user_id']][(int)$r['quiz_id']] = ['best' => (int)$r['best'], 'passed' => (bool)$r['passed'], 'attempts' => (int)$r['attempts'], 'last' => $r['last']];
        }
        return $out;
    }

    public static function statusOf(array $row): string
    {
        if (!empty($row['completed_at'])) {
            return 'Completed';
        }
        return (int)$row['lessons_done'] > 0 || (int)$row['quizzes_attempted'] > 0 ? 'In progress' : 'Not started';
    }

    /** One row per enrolled student. */
    public static function cohort(int $courseId): array
    {
        $total = self::lessonTotal($courseId);
        $quizzes = self::quizzes($courseId);
        $finalIds = array_map('intval', array_column(array_filter($quizzes, fn($q) => $q['kind'] === 'final'), 'id'));
        $results = self::quizResults($courseId);
        $rows = Db::all(
            "SELECT e.id AS enrol_id, e.user_id, e.created_at, e.paid_at, e.completed_at, u.name, u.email,
                    (SELECT COUNT(*) FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = e.user_id AND l.course_id = e.course_id AND l.status = 'published') AS lessons_done,
                    (SELECT MAX(p.completed_at) FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = e.user_id AND l.course_id = e.course_id) AS last_lesson_at,
                    (SELECT COALESCE(SUM(k.earned), 0) FROM lesson_checks k JOIN lessons l ON l.id = k.lesson_id WHERE k.user_id = e.user_id AND l.course_id = e.course_id AND k.answered_at IS NOT NULL) AS check_earned,
                    (SELECT COALESCE(SUM(k.max_points), 0) FROM lesson_checks k JOIN lessons l ON l.id = k.lesson_id WHERE k.user_id = e.user_id AND l.course_id = e.course_id AND k.answered_at IS NOT NULL) AS check_max,
                    (SELECT code FROM certificates x WHERE x.user_id = e.user_id AND x.course_id = e.course_id LIMIT 1) AS cert_code
             FROM enrolments e JOIN users u ON u.id = e.user_id
             WHERE e.course_id = ? AND e.status = 'active' ORDER BY u.name, u.id",
            [$courseId]
        );
        foreach ($rows as &$r) {
            $uid = (int)$r['user_id'];
            $mine = $results[$uid] ?? [];
            $bests = [];
            $passed = 0;
            $finalBest = null;
            $lastQuiz = null;
            foreach ($quizzes as $q) {
                $res = $mine[(int)$q['id']] ?? null;
                if (!$res) {
                    continue;
                }
                $bests[] = $res['best'];
                $passed += $res['passed'] ? 1 : 0;
                if (in_array((int)$q['id'], $finalIds, true)) {
                    $finalBest = max($finalBest ?? 0, $res['best']);
                }
                if ($res['last'] && ($lastQuiz === null || $res['last'] > $lastQuiz)) {
                    $lastQuiz = $res['last'];
                }
            }
            $r['lessons_total'] = $total;
            $r['pct'] = $total > 0 ? (int)floor((int)$r['lessons_done'] * 100 / $total) : 0;
            $r['check_earned'] = (float)$r['check_earned'];
            $r['check_max'] = (float)$r['check_max'];
            $r['check_pct'] = $r['check_max'] > 0 ? (int)round($r['check_earned'] * 100 / $r['check_max']) : null;
            $r['quizzes_total'] = count($quizzes);
            $r['quizzes_attempted'] = count($bests);
            $r['quizzes_passed'] = $passed;
            $r['quiz_avg'] = $bests ? (int)round(array_sum($bests) / count($bests)) : null;
            $r['final_best'] = $finalBest;
            $r['enrolled_at'] = $r['paid_at'] ?: $r['created_at'];
            $act = array_filter([$r['last_lesson_at'], $lastQuiz]);
            $r['last_activity'] = $act ? max($act) : null;
            $r['status'] = self::statusOf($r);
        }
        return $rows;
    }

    /** Headline numbers for a course, from its cohort rows. */
    public static function summary(array $cohort): array
    {
        $n = count($cohort);
        $avg = function (array $vals): ?int {
            $vals = array_values(array_filter($vals, fn($v) => $v !== null));
            return $vals ? (int)round(array_sum($vals) / count($vals)) : null;
        };
        return [
            'students' => $n,
            'completed' => count(array_filter($cohort, fn($r) => !empty($r['completed_at']))),
            'started' => count(array_filter($cohort, fn($r) => $r['status'] !== 'Not started')),
            'avg_progress' => $avg(array_column($cohort, 'pct')),
            'avg_check' => $avg(array_column($cohort, 'check_pct')),
            'avg_quiz' => $avg(array_column($cohort, 'quiz_avg')),
        ];
    }

    /** For every lesson: how many students finished it and how they did in its check-up. */
    public static function lessonFunnel(int $courseId): array
    {
        $done = [];
        foreach (Db::all(
            "SELECT p.lesson_id, COUNT(*) AS n FROM lesson_progress p JOIN enrolments e ON e.user_id = p.user_id AND e.course_id = ? AND e.status = 'active' GROUP BY p.lesson_id",
            [$courseId]
        ) as $r) {
            $done[(int)$r['lesson_id']] = (int)$r['n'];
        }
        $checks = [];
        foreach (Db::all(
            "SELECT k.lesson_id, SUM(k.earned) AS earned, SUM(k.max_points) AS max_points, COUNT(*) AS n FROM lesson_checks k JOIN enrolments e ON e.user_id = k.user_id AND e.course_id = ? AND e.status = 'active'
             WHERE k.answered_at IS NOT NULL GROUP BY k.lesson_id",
            [$courseId]
        ) as $r) {
            $checks[(int)$r['lesson_id']] = ['pct' => (float)$r['max_points'] > 0 ? (int)round((float)$r['earned'] * 100 / (float)$r['max_points']) : null, 'n' => (int)$r['n']];
        }
        $out = [];
        foreach (Db::all(
            "SELECT l.id, l.title, m.title AS module_title, m.id AS module_id FROM lessons l JOIN course_modules m ON m.id = l.module_id
             WHERE l.course_id = ? AND l.status = 'published' ORDER BY m.sort_order, m.id, l.sort_order, l.id",
            [$courseId]
        ) as $l) {
            $out[] = $l + ['done' => $done[(int)$l['id']] ?? 0, 'check_pct' => $checks[(int)$l['id']]['pct'] ?? null, 'check_n' => $checks[(int)$l['id']]['n'] ?? 0];
        }
        return $out;
    }

    /** Everything about one student in one course: modules, lessons, check-up scores, quiz attempts, totals. Null when not an active student of the course. */
    public static function student(int $courseId, int $userId): ?array
    {
        $enrol = Db::one(
            "SELECT e.*, u.name, u.email FROM enrolments e JOIN users u ON u.id = e.user_id WHERE e.course_id = ? AND e.user_id = ? AND e.status = 'active'",
            [$courseId, $userId]
        );
        if (!$enrol) {
            return null;
        }
        $done = [];
        foreach (Db::all('SELECT p.lesson_id, p.completed_at FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = ? AND l.course_id = ?', [$userId, $courseId]) as $r) {
            $done[(int)$r['lesson_id']] = $r['completed_at'];
        }
        $checks = [];
        foreach (Db::all('SELECT k.lesson_id, k.earned, k.max_points, k.answered_at FROM lesson_checks k JOIN lessons l ON l.id = k.lesson_id WHERE k.user_id = ? AND l.course_id = ? AND k.answered_at IS NOT NULL', [$userId, $courseId]) as $r) {
            $checks[(int)$r['lesson_id']] = $r;
        }
        $attempts = [];
        foreach (Db::all(
            "SELECT a.quiz_id, a.score, a.passed, a.submitted_at FROM quiz_attempts a JOIN quizzes q ON q.id = a.quiz_id
             WHERE a.user_id = ? AND q.course_id = ? AND q.kind <> 'check' AND a.submitted_at IS NOT NULL ORDER BY a.id",
            [$userId, $courseId]
        ) as $r) {
            $attempts[(int)$r['quiz_id']][] = $r;
        }
        $quizzes = self::quizzes($courseId);
        $quizRow = function (array $q) use ($attempts): array {
            $list = $attempts[(int)$q['id']] ?? [];
            $best = $list ? max(array_map(fn($a) => (int)$a['score'], $list)) : null;
            return ['quiz' => $q, 'attempts' => $list, 'best' => $best, 'passed' => (bool)array_filter($list, fn($a) => (int)$a['passed'] === 1)];
        };
        $modules = [];
        $totalLessons = $doneLessons = 0;
        $checkEarned = $checkMax = 0.0;
        foreach (Db::all('SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order, id', [$courseId]) as $m) {
            $lessons = [];
            $mDone = 0;
            $mEarned = $mMax = 0.0;
            foreach (Db::all("SELECT id, title FROM lessons WHERE module_id = ? AND status = 'published' ORDER BY sort_order, id", [$m['id']]) as $l) {
                $lid = (int)$l['id'];
                $ck = $checks[$lid] ?? null;
                $lessons[] = [
                    'id' => $lid, 'title' => $l['title'], 'completed_at' => $done[$lid] ?? null,
                    'earned' => $ck ? (float)$ck['earned'] : null, 'max' => $ck ? (int)$ck['max_points'] : null,
                ];
                $mDone += isset($done[$lid]) ? 1 : 0;
                if ($ck) {
                    $mEarned += (float)$ck['earned'];
                    $mMax += (float)$ck['max_points'];
                }
            }
            $totalLessons += count($lessons);
            $doneLessons += $mDone;
            $checkEarned += $mEarned;
            $checkMax += $mMax;
            $modules[] = [
                'module' => $m, 'lessons' => $lessons, 'done' => $mDone, 'total' => count($lessons),
                'check_earned' => $mEarned, 'check_max' => $mMax, 'check_pct' => $mMax > 0 ? (int)round($mEarned * 100 / $mMax) : null,
                'quizzes' => array_map($quizRow, array_values(array_filter($quizzes, fn($q) => (int)$q['module_id'] === (int)$m['id'] && $q['kind'] !== 'final'))),
            ];
        }
        $final = array_map($quizRow, array_values(array_filter($quizzes, fn($q) => $q['kind'] === 'final')));
        $all = array_merge($final, ...array_map(fn($m) => $m['quizzes'], $modules));
        $bests = array_values(array_filter(array_column($all, 'best'), fn($b) => $b !== null));
        return [
            'enrol' => $enrol, 'modules' => $modules, 'final' => $final,
            'lessons_done' => $doneLessons, 'lessons_total' => $totalLessons, 'pct' => $totalLessons > 0 ? (int)floor($doneLessons * 100 / $totalLessons) : 0,
            'check_earned' => $checkEarned, 'check_max' => $checkMax, 'check_pct' => $checkMax > 0 ? (int)round($checkEarned * 100 / $checkMax) : null,
            'quiz_avg' => $bests ? (int)round(array_sum($bests) / count($bests)) : null,
            'quizzes_passed' => count(array_filter($all, fn($q) => $q['passed'])), 'quizzes_total' => count($all),
            'final_best' => $final ? max(array_map(fn($f) => $f['best'] ?? 0, $final)) : null,
            'cert' => Db::one('SELECT code, issued_at FROM certificates WHERE user_id = ? AND course_id = ? LIMIT 1', [$userId, $courseId]),
        ];
    }

    /** A cell for a CSV file: text starting with = + - @ is made harmless so spreadsheets never run it as a formula. */
    public static function csvCell(mixed $v): string
    {
        $s = (string)$v;
        if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($s)) {
            $s = "'" . $s;
        }
        return $s;
    }
}

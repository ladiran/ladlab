<?php
declare(strict_types=1);

/**
 * Student progress. The administrator sees every course; a lecturer sees only the students of the courses assigned to them
 * (every request re-checks that the course belongs to the signed-in person).
 */
final class AdminProgressController extends AdminBase
{
    protected function staffAllowed(): bool
    {
        return true;
    }

    private function own(string $id): array
    {
        $c = Learn::courseById((int)$id);
        if (!$c || !Lecturers::canManage(Auth::user(), $c)) {
            abort(404);
        }
        return $c;
    }

    public function index(): void
    {
        [$scope, $params] = Lecturers::scope(Auth::user(), 'c');
        $courses = Db::all(
            "SELECT c.id, c.title, c.status, c.is_sample, (SELECT u.name FROM users u WHERE u.id = c.lecturer_id) AS lecturer_name FROM courses c WHERE $scope ORDER BY c.status = 'published' DESC, c.sort_order, c.id",
            $params
        );
        foreach ($courses as &$c) {
            $c['sum'] = Progress::summary(Progress::cohort((int)$c['id']));
        }
        $this->page('admin/progress_index', ['title' => 'Student progress', 'tab' => 'progress', 'courses' => $courses]);
    }

    public function course(string $id): void
    {
        $c = $this->own($id);
        $cohort = Progress::cohort((int)$c['id']);
        $sort = in_array($_GET['sort'] ?? '', ['name', 'progress', 'check', 'quiz', 'last'], true) ? $_GET['sort'] : 'name';
        $cmp = [
            'progress' => fn($a, $b) => $b['pct'] <=> $a['pct'],
            'check' => fn($a, $b) => ($b['check_pct'] ?? -1) <=> ($a['check_pct'] ?? -1),
            'quiz' => fn($a, $b) => ($b['quiz_avg'] ?? -1) <=> ($a['quiz_avg'] ?? -1),
            'last' => fn($a, $b) => strcmp((string)($b['last_activity'] ?? ''), (string)($a['last_activity'] ?? '')),
        ];
        if (isset($cmp[$sort])) {
            usort($cohort, $cmp[$sort]);
        }
        $this->page('admin/progress_course', [
            'title' => 'Progress: ' . $c['title'], 'tab' => 'progress', 'c' => $c, 'rows' => $cohort, 'sum' => Progress::summary($cohort),
            'funnel' => Progress::lessonFunnel((int)$c['id']), 'sort' => $sort,
            'lecturer' => $c['lecturer_id'] ? Db::val('SELECT name FROM users WHERE id = ?', [$c['lecturer_id']]) : null,
        ]);
    }

    public function student(string $id, string $uid): void
    {
        $c = $this->own($id);
        $s = Progress::student((int)$c['id'], (int)$uid);
        if (!$s) {
            abort(404); // not an active student of this course (also hides other courses' students from a lecturer)
        }
        $this->page('admin/progress_student', ['title' => $s['enrol']['name'] . ': ' . $c['title'], 'tab' => 'progress', 'c' => $c, 's' => $s]);
    }

    public function csv(string $id): void
    {
        $c = $this->own($id);
        $rows = Progress::cohort((int)$c['id']);
        Audit::log('progress_export', $c['title']);
        $name = (slugify($c['title']) ?: 'course') . '-progress-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the letters correctly
        fputcsv($out, ['Name', 'Email', 'Enrolled', 'Status', 'Lessons done', 'Lessons total', 'Progress %', 'Check-up points', 'Check-up possible', 'Check-up %', 'Quizzes passed', 'Quizzes total', 'Quiz average %', 'Final exam best %', 'Completed on', 'Last activity'], ',', '"', '');
        foreach ($rows as $r) {
            fputcsv($out, array_map([Progress::class, 'csvCell'], [
                $r['name'], $r['email'], fmt_date($r['enrolled_at'], 'Y-m-d'), $r['status'], $r['lessons_done'], $r['lessons_total'], $r['pct'],
                $r['check_earned'], $r['check_max'], $r['check_pct'] ?? '', $r['quizzes_passed'], $r['quizzes_total'], $r['quiz_avg'] ?? '', $r['final_best'] ?? '',
                fmt_date($r['completed_at'], 'Y-m-d'), fmt_date($r['last_activity'], 'Y-m-d H:i'),
            ]), ',', '"', '');
        }
        fclose($out);
        exit;
    }
}

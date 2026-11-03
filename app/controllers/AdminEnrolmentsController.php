<?php
declare(strict_types=1);

/** Students: payments to confirm, access, progress and certificates. */
final class AdminEnrolmentsController extends AdminBase
{
    public function index(): void
    {
        $status = isset(Learn::ENROL_STATUS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
        $course = (int)($_GET['course'] ?? 0);
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);
        $where = ['1 = 1'];
        $p = [];
        if ($status !== '') {
            $where[] = 'e.status = ?';
            $p[] = $status;
        }
        if ($course) {
            $where[] = 'e.course_id = ?';
            $p[] = $course;
        }
        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR e.enrol_no LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            array_push($p, $like, $like, $like);
        }
        $rows = Db::all(
            'SELECT e.*, u.name, u.email, c.title AS course_title FROM enrolments e JOIN users u ON u.id = e.user_id JOIN courses c ON c.id = e.course_id WHERE '
            . implode(' AND ', $where) . ' ORDER BY e.status = \'awaiting_payment\' DESC, e.id DESC LIMIT 300',
            $p
        );
        $this->page('admin/enrolments', [
            'title' => 'Course enrolments', 'tab' => 'enrolments', 'rows' => $rows, 'status' => $status, 'course' => $course, 'q' => $q,
            'courses' => Db::all('SELECT id, title FROM courses WHERE status <> \'soon\' ORDER BY title'),
            'pending' => (int)Db::val("SELECT COUNT(*) FROM enrolments WHERE status = 'awaiting_payment'"),
            'revenue' => (int)Db::val("SELECT COALESCE(SUM(total), 0) FROM enrolments WHERE status = 'active' AND source = 'purchase'"),
        ]);
    }

    public function show(string $id): void
    {
        $e = Db::one('SELECT e.*, u.name, u.email, u.phone FROM enrolments e JOIN users u ON u.id = e.user_id WHERE e.id = ?', [(int)$id]);
        if (!$e) {
            abort(404);
        }
        $c = Learn::courseById((int)$e['course_id']);
        $this->page('admin/enrolment', [
            'title' => 'Enrolment ' . $e['enrol_no'], 'tab' => 'enrolments', 'e' => $e, 'c' => $c,
            'cur' => Learn::curriculum((int)$c['id']), 'progress' => Learn::progress((int)$e['user_id'], (int)$c['id']),
            'cert' => Db::one('SELECT * FROM certificates WHERE user_id = ? AND course_id = ?', [$e['user_id'], $c['id']]),
            'attempts' => Db::all('SELECT a.*, q.title FROM quiz_attempts a JOIN quizzes q ON q.id = a.quiz_id WHERE a.user_id = ? AND q.course_id = ? AND a.submitted_at IS NOT NULL ORDER BY a.id DESC LIMIT 50', [$e['user_id'], $c['id']]),
        ]);
    }

    public function action(string $id): void
    {
        $this->verify();
        $act = (string)($_POST['action'] ?? '');
        try {
            if ($act === 'confirm') {
                Learn::confirmPayment((int)$id);
                flash('success', 'Payment confirmed. The student now has full access and has been emailed.');
            } elseif ($act === 'cancel') {
                Learn::cancel((int)$id);
                flash('success', 'Enrolment cancelled.');
            } else {
                throw new RuntimeException('Unknown action.');
            }
            Audit::log('enrolment_' . $act, (string)(int)$id);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect(($_POST['return'] ?? '') === 'list' ? 'admin/enrolments' : 'admin/enrolments/' . (int)$id);
    }

    public function grant(): void
    {
        $this->verify();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $u = Db::one('SELECT id, name FROM users WHERE email = ?', [$email]);
        $c = Learn::courseById((int)($_POST['course_id'] ?? 0));
        if (!$u || !$c || $c['status'] === 'soon') {
            flash('error', !$u ? 'No registered account with that email. Ask the student to register first.' : 'Choose a course.');
        } else {
            $e = Learn::grant((int)$u['id'], (int)$c['id']);
            Audit::log('enrolment_grant', $email . ' → ' . $c['title']);
            flash('success', $u['name'] . ' now has full access to “' . $c['title'] . '” (' . $e['enrol_no'] . ').');
        }
        redirect('admin/enrolments');
    }

    public function receipt(string $id): void
    {
        $e = Db::one('SELECT * FROM enrolments WHERE id = ?', [(int)$id]);
        $rel = $e['receipt_path'] ?? '';
        $full = $rel ? realpath(ROOT . '/' . $rel) : false;
        $base = realpath(ROOT . '/storage/private/receipts');
        if (!$full || !$base || !str_starts_with($full, $base . DIRECTORY_SEPARATOR) || !is_file($full)) {
            abort(404);
        }
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        header('Content-Type: ' . (['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="receipt-' . $e['enrol_no'] . '.' . $ext . '"');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
        header('Cache-Control: private, no-store');
        readfile($full);
        exit;
    }
}

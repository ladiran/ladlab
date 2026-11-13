<?php
declare(strict_types=1);

/** Course announcements, student questions and reviews. */
final class AdminCommunityController extends AdminBase
{
    /** Lecturers use announcements and student questions, only for their own courses. Reviews stay with the administrator. */
    protected function staffAllowed(): bool
    {
        return true;
    }

    private function ownCourse(int $courseId): array
    {
        $c = Learn::courseById($courseId);
        if (!$c || !Lecturers::canManage(Auth::user(), $c)) {
            abort(404);
        }
        return $c;
    }

    /* ------------------------------------------------------------------ announcements */

    public function announcements(string $id): void
    {
        $c = $this->ownCourse((int)$id);
        $this->page('admin/announcements', [
            'title' => 'Announcements: ' . $c['title'], 'tab' => 'courses', 'c' => $c,
            'rows' => Db::all('SELECT * FROM course_announcements WHERE course_id = ? ORDER BY id DESC', [$c['id']]),
            'students' => (int)Db::val("SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND status = 'active'", [$c['id']]),
        ]);
    }

    public function announcementSave(string $id): void
    {
        $this->verify();
        $c = $this->ownCourse((int)$id);
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 200);
        $body = mb_substr(trim((string)($_POST['body'] ?? '')), 0, 10000);
        if ($title === '' || $body === '') {
            flash('error', 'Please write both a title and the message.');
            remember_input($_POST);
            redirect('admin/courses/' . $c['id'] . '/announcements');
        }
        $sent = 0;
        if (isset($_POST['email_students'])) {
            $sent = Community::emailAnnouncement($c, $title, $body);
        }
        Db::insert('course_announcements', ['course_id' => $c['id'], 'title' => $title, 'body' => $body, 'emailed' => $sent]);
        Audit::log('announcement', $c['title'] . ': ' . $title);
        clear_old();
        flash('success', 'Announcement posted' . ($sent ? " and emailed to $sent student" . ($sent === 1 ? '' : 's') : '') . '.');
        redirect('admin/courses/' . $c['id'] . '/announcements');
    }

    public function announcementDelete(string $aid): void
    {
        $this->verify();
        $a = Db::one('SELECT * FROM course_announcements WHERE id = ?', [(int)$aid]);
        if ($a) {
            $this->ownCourse((int)$a['course_id']);
            Db::delete('course_announcements', 'id = ?', [$a['id']]);
            flash('success', 'Announcement deleted.');
        }
        redirect('admin/courses/' . ($a['course_id'] ?? 0) . '/announcements');
    }

    /* ------------------------------------------------------------------ questions */

    public function questions(): void
    {
        $st = in_array($_GET['status'] ?? '', ['open', 'answered', 'hidden'], true) ? $_GET['status'] : 'open';
        [$scope, $scopeParams] = Lecturers::scope(Auth::user(), 'c');
        $rows = Db::all(
            "SELECT q.*, u.name, u.email, l.title AS lesson_title, c.title AS course_title, c.slug AS course_slug
             FROM lesson_questions q JOIN users u ON u.id = q.user_id JOIN lessons l ON l.id = q.lesson_id JOIN courses c ON c.id = q.course_id
             WHERE q.status = ? AND $scope ORDER BY q.id DESC LIMIT 200",
            array_merge([$st], $scopeParams)
        );
        $this->page('admin/qa', ['title' => 'Student questions', 'tab' => 'qa', 'rows' => $rows, 'status' => $st]);
    }

    public function questionAnswer(string $qid): void
    {
        $this->verify();
        $q = Db::one('SELECT q.*, u.name, u.email, l.title AS lesson_title, c.title AS course_title, c.slug AS course_slug FROM lesson_questions q JOIN users u ON u.id = q.user_id JOIN lessons l ON l.id = q.lesson_id JOIN courses c ON c.id = q.course_id WHERE q.id = ?', [(int)$qid]);
        if (!$q) {
            abort(404);
        }
        $this->ownCourse((int)$q['course_id']);
        $act = (string)($_POST['action'] ?? 'answer');
        if ($act === 'hide') {
            Db::update('lesson_questions', ['status' => 'hidden'], 'id = ?', [$q['id']]);
            flash('success', 'Question hidden.');
        } elseif ($act === 'delete') {
            Db::delete('lesson_questions', 'id = ?', [$q['id']]);
            flash('success', 'Question deleted.');
        } else {
            $answer = mb_substr(trim((string)($_POST['answer'] ?? '')), 0, 8000);
            if ($answer === '') {
                flash('error', 'Please write an answer.');
                redirect('admin/qa');
            }
            Db::update('lesson_questions', ['answer' => $answer, 'status' => 'answered', 'answered_at' => date('Y-m-d H:i:s')], 'id = ?', [$q['id']]);
            $site = Settings::get('site_name');
            Mailer::send($q['email'], "[$site] Your question was answered", "Hello {$q['name']},\n\nYou asked in \"{$q['lesson_title']}\" ({$q['course_title']}):\n\n{$q['question']}\n\nAnswer:\n\n$answer\n\nSee it in the lesson: " . abs_url('learn/' . $q['course_slug'] . '/lesson/' . $q['lesson_id'] . '#questions') . "\n\n$site");
            flash('success', 'Answer posted and the student has been emailed.');
        }
        redirect('admin/qa' . (($_POST['return'] ?? '') === 'answered' ? '?status=answered' : ''));
    }

    /* ------------------------------------------------------------------ reviews */

    public function reviews(): void
    {
        Auth::requireAdmin();
        $st = in_array($_GET['status'] ?? '', ['pending', 'published', 'hidden'], true) ? $_GET['status'] : 'pending';
        $rows = Db::all(
            'SELECT r.*, u.name, c.title AS course_title FROM course_reviews r JOIN users u ON u.id = r.user_id JOIN courses c ON c.id = r.course_id WHERE r.status = ? ORDER BY r.id DESC LIMIT 200',
            [$st]
        );
        $this->page('admin/reviews', ['title' => 'Course reviews', 'tab' => 'reviews', 'rows' => $rows, 'status' => $st]);
    }

    public function reviewAction(string $rid): void
    {
        Auth::requireAdmin();
        $this->verify();
        $r = Db::one('SELECT * FROM course_reviews WHERE id = ?', [(int)$rid]);
        if ($r) {
            $act = (string)($_POST['action'] ?? '');
            if ($act === 'delete') {
                Db::delete('course_reviews', 'id = ?', [$r['id']]);
            } elseif (in_array($act, ['published', 'hidden'], true)) {
                Db::update('course_reviews', ['status' => $act], 'id = ?', [$r['id']]);
            }
            flash('success', 'Done.');
        }
        redirect('admin/reviews' . (isset($_POST['back']) ? '?status=' . rawurlencode((string)$_POST['back']) : ''));
    }
}

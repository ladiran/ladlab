<?php
declare(strict_types=1);

/** Administrator only: make registered members lecturers, edit their short bio, take the role away. Assigning courses is done on each course. */
final class AdminLecturersController extends AdminBase
{
    public function index(): void
    {
        $rows = Lecturers::all();
        $courses = [];
        foreach (Db::all('SELECT id, title, lecturer_id FROM courses WHERE lecturer_id IS NOT NULL ORDER BY title') as $c) {
            $courses[(int)$c['lecturer_id']][] = $c;
        }
        $label = Lecturers::label();
        $this->page('admin/lecturers', ['title' => $label . 's', 'tab' => 'courses', 'rows' => $rows, 'courses' => $courses, 'label' => $label]);
    }

    public function add(): void
    {
        $this->verify();
        $err = Lecturers::promote((string)($_POST['email'] ?? ''), (string)($_POST['bio'] ?? ''));
        if ($err !== '') {
            flash('error', $err);
            remember_input($_POST);
        } else {
            clear_old();
            flash('success', 'Done. They have been emailed. Their ' . mb_strtolower(Lecturers::label()) . ' area opens as soon as they turn on two-step verification (compulsory). To give them an existing course, open the course and choose them as its ' . mb_strtolower(Lecturers::label()) . '.');
        }
        redirect('admin/lecturers');
    }

    public function bio(string $id): void
    {
        $this->verify();
        if (Db::val("SELECT 1 FROM users WHERE id = ? AND role = 'lecturer'", [(int)$id])) {
            Db::update('users', ['lecturer_bio' => mb_substr(trim((string)($_POST['bio'] ?? '')), 0, 600)], 'id = ?', [(int)$id]);
            flash('success', 'Saved.');
        }
        redirect('admin/lecturers');
    }

    public function remove(string $id): void
    {
        $this->verify();
        $u = Db::one("SELECT email FROM users WHERE id = ? AND role = 'lecturer'", [(int)$id]);
        if ($u) {
            $n = Lecturers::demote((int)$id);
            flash('success', $u['email'] . ' is an ordinary member again.' . ($n ? " $n course" . ($n === 1 ? ' was' : 's were') . ' left without a ' . mb_strtolower(Lecturers::label()) . ': assign ' . ($n === 1 ? 'it' : 'them') . ' to someone else if needed.' : ''));
        }
        redirect('admin/lecturers');
    }
}

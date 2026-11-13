<?php
declare(strict_types=1);

/** Courses, curriculum (modules, lessons), quizzes and questions. */
final class AdminCoursesController extends AdminBase
{
    /** Lecturers may use this controller, but only for the courses assigned to them. */
    protected function staffAllowed(): bool
    {
        return true;
    }

    /** The course, or 404 when it does not exist or is not this person's to manage (administrators manage every course). */
    private function own(int $courseId): array
    {
        $c = Learn::courseById($courseId);
        if (!$c || !Lecturers::canManage(Auth::user(), $c)) {
            abort(404);
        }
        return $c;
    }

    private function course(string $id): array
    {
        return $this->own((int)$id);
    }

    /** Live preview of Markdown in the editors (shared by administrators and lecturers). */
    public function markdownPreview(): void
    {
        $this->verify();
        json_out(['html' => md((string)($_POST['text'] ?? ''))]);
    }

    private function in(string $k): string
    {
        return trim((string)($_POST[$k] ?? ''));
    }

    /** Moves a row up or down among its siblings (renumbers sort_order 10, 20, 30…). */
    private function move(string $table, string $scopeCol, int $scopeVal, int $id, string $dir): void
    {
        $rows = Db::all("SELECT id FROM `$table` WHERE `$scopeCol` " . ($scopeVal === 0 ? 'IS NULL' : '= ?') . ' ORDER BY sort_order, id', $scopeVal === 0 ? [] : [$scopeVal]);
        $ids = array_map('intval', array_column($rows, 'id'));
        $i = array_search($id, $ids, true);
        if ($i === false) {
            return;
        }
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if (!isset($ids[$j])) {
            return;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $k => $rid) {
            Db::update($table, ['sort_order' => ($k + 1) * 10], 'id = ?', [$rid]);
        }
    }

    private function dir(): string
    {
        return ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down';
    }

    /* ------------------------------------------------------------------ courses */

    public function index(): void
    {
        [$scope, $scopeParams] = Lecturers::scope(Auth::user(), 'c');
        $this->page('admin/courses', ['title' => Auth::isAdmin() ? 'Courses' : 'My courses', 'tab' => 'courses', 'rows' => Db::all(
            "SELECT c.*, (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lessons, (SELECT COUNT(*) FROM quizzes q WHERE q.course_id = c.id AND q.kind <> 'check') AS quizzes,
                    (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.id AND e.status = 'active') AS students,
                    (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.id AND e.status = 'awaiting_payment') AS pending,
                    (SELECT u.name FROM users u WHERE u.id = c.lecturer_id) AS lecturer_name
             FROM courses c WHERE $scope ORDER BY c.status = 'published' DESC, c.sort_order, c.id",
            $scopeParams
        )]);
    }

    public function form(?string $id = null): void
    {
        $c = $id !== null ? $this->course($id) : null;
        $this->page('admin/course_form', ['title' => $c ? 'Edit course: ' . $c['title'] : 'Add a course', 'tab' => 'courses', 'c' => $c, 'lecturers' => Auth::isAdmin() ? Lecturers::all() : []]);
    }

    public function save(?string $id = null): void
    {
        $this->verify();
        $staff = !Auth::isAdmin(); // a lecturer
        $existing = $id !== null ? $this->course($id) : null;
        $d = [
            'title' => mb_substr($this->in('title'), 0, 200), 'subtitle' => mb_substr($this->in('subtitle'), 0, 250),
            'subject' => mb_substr($this->in('subject'), 0, 40) ?: 'Physics', 'level' => mb_substr($this->in('level'), 0, 40) ?: 'University Year 1',
            'description' => mb_substr($this->in('description'), 0, 30000), 'outcomes' => mb_substr($this->in('outcomes'), 0, 10000), 'requirements' => mb_substr($this->in('requirements'), 0, 10000),
            'status' => in_array($this->in('status'), ['draft', 'published', 'soon'], true) ? $this->in('status') : 'draft',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0, 'sort_order' => (int)$this->in('sort_order'),
            'pass_mark' => max(1, min(100, (int)($this->in('pass_mark') ?: 60))), 'certificate_enabled' => isset($_POST['certificate_enabled']) ? 1 : 0,
            'chat_enabled' => isset($_POST['chat_enabled']) ? 1 : 0,
        ];
        $errors = [];
        if (!$staff) {
            [$d['lock_from'], $d['lock_until']] = $this->lockFromPost($existing, $errors);
        }
        if ($d['title'] === '') {
            $errors[] = 'Please enter the course title.';
        }
        if (!preg_match('/^\d{1,7}$/', $this->in('price') ?: '0')) {
            $errors[] = 'The price must be a whole number of Naira (0 for a free course).';
        }
        $d['price'] = (int)$this->in('price');
        if ($this->in('compare_price') !== '' && !preg_match('/^\d{1,7}$/', $this->in('compare_price'))) {
            $errors[] = 'The “was” price must be a whole number, or empty.';
        }
        $d['compare_price'] = $this->in('compare_price') !== '' ? (int)$this->in('compare_price') : null;
        $slug = slugify(!$staff && $this->in('slug') !== '' ? $this->in('slug') : $d['title']); // a lecturer's course address always comes from its title
        $base = $slug;
        for ($n = 2; Db::val('SELECT 1 FROM courses WHERE slug = ? AND id <> ?', [$slug, $existing['id'] ?? 0]); $n++) {
            $slug = $base . '-' . $n;
        }
        $d['slug'] = $slug;
        $cover = null;
        if (Upload::has($_FILES['cover_image'] ?? null)) {
            try {
                $cover = Upload::image($_FILES['cover_image'], 1400);
            } catch (RuntimeException $e) {
                $errors[] = 'Cover image: ' . $e->getMessage();
            }
        }
        $lecturerId = null;
        if (!$staff && $this->in('lecturer_id') !== '') {
            $lecturerId = (int)$this->in('lecturer_id');
            if (!Db::val("SELECT 1 FROM users WHERE id = ? AND role = 'lecturer'", [$lecturerId])) {
                $errors[] = 'Please choose one of the existing ' . mb_strtolower(Lecturers::label()) . 's.';
            }
        }
        if ($errors) {
            if ($cover) {
                Upload::remove($cover);
            }
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect($existing ? 'admin/courses/' . $existing['id'] : 'admin/courses/new');
        }
        if ($staff) {
            // content only: price, publishing, featuring, order, address, chat switch, lockdown and assignment belong to the administrator
            foreach (['status', 'is_featured', 'sort_order', 'price', 'compare_price', 'chat_enabled'] as $k) {
                unset($d[$k]);
            }
            if ($existing) {
                unset($d['slug']);
            } else {
                $d['status'] = 'draft';
                $d['price'] = 0;
                $d['lecturer_id'] = (int)Auth::user()['id'];
            }
        } else {
            $d['lecturer_id'] = $lecturerId;
        }
        if ($existing) {
            if ($cover) {
                $d['cover_image'] = $cover;
                Upload::remove($existing['cover_image']);
            } elseif (!empty($_POST['remove_cover'])) {
                Upload::remove($existing['cover_image']);
                $d['cover_image'] = '';
            }
            $d['is_sample'] = 0; // editing a sample makes it yours
            Db::update('courses', $d, 'id = ?', [$existing['id']]);
            $cid = (int)$existing['id'];
        } else {
            $cid = Db::insert('courses', $d + ['cover_image' => $cover ?? '', 'is_sample' => 0]);
        }
        Audit::log($existing ? 'course_update' : 'course_create', $d['title']);
        if ($staff && !$existing) {
            Community::notifyOwner('New course draft by ' . Auth::user()['name'], Auth::user()['name'] . ' started a new course "' . $d['title'] . "\".\n\nIt is a draft with price 0. Review it, set the price and publish it here: " . abs_url('admin/courses/' . $cid));
        }
        if (!$staff && $d['lock_until'] !== null && (!$existing || $existing['lock_until'] !== $d['lock_until'] || $existing['lock_from'] !== $d['lock_from'])) {
            Audit::log('course_lock', $d['title'] . ' until ' . $d['lock_until']);
        } elseif (!$staff && $existing && $existing['lock_until'] !== null && $d['lock_until'] === null) {
            Audit::log('course_unlock', $d['title']);
        }
        clear_old();
        flash('success', $existing ? 'Course saved.' : 'Course created' . ($staff ? ' as a draft; the administrator will review it, set the price and publish it' : '') . '. Now build the curriculum: add modules and lessons.');
        redirect($existing ? 'admin/courses/' . $cid : 'admin/courses/' . $cid . '/curriculum');
    }

    /**
     * Reads the lock period from the form. The lock must end in the future and may not start in the past
     * (an unchanged start of a lock that is already running is accepted). An empty start means "lock now".
     * @return array{0:?string,1:?string} lock_from, lock_until as 'Y-m-d H:i:s' (both null = no lock)
     */
    private function lockFromPost(?array $existing, array &$errors): array
    {
        if (!empty($_POST['lock_clear'])) {
            return [null, null];
        }
        $fromIn = $this->in('lock_from');
        $untilIn = $this->in('lock_until');
        if ($fromIn === '' && $untilIn === '') {
            return [null, null];
        }
        $parse = function (string $v): ?int {
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $v, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1]) || (int)$m[4] > 23 || (int)$m[5] > 59) {
                return null;
            }
            return mktime((int)$m[4], (int)$m[5], 0, (int)$m[2], (int)$m[3], (int)$m[1]);
        };
        $before = count($errors);
        $now = time();
        $thisMinute = $now - ($now % 60);
        $until = null;
        if ($untilIn === '') {
            $errors[] = 'Choose when the lock ends.';
        } elseif (($until = $parse($untilIn)) === null) {
            $errors[] = 'The lock end is not a valid date and time.';
        } elseif ($until <= $now) {
            $errors[] = 'The lock must end at a future date and time.';
        }
        $from = null;
        if ($fromIn !== '') {
            $from = $parse($fromIn);
            if ($from === null) {
                $errors[] = 'The lock start is not a valid date and time.';
            } else {
                $unchanged = $existing && $existing['lock_from'] && date('Y-m-d H:i', (int)strtotime((string)$existing['lock_from'])) === date('Y-m-d H:i', $from);
                if (!$unchanged && $from < $thisMinute) {
                    $errors[] = 'The lock must start at a future date and time (or leave the start empty to lock immediately).';
                }
                if ($until !== null && $from >= $until) {
                    $errors[] = 'The lock must end after it starts.';
                }
            }
        }
        if (count($errors) > $before || $until === null) {
            return [null, null];
        }
        return [$from !== null ? date('Y-m-d H:i:00', $from) : date('Y-m-d H:i:s', $now), date('Y-m-d H:i:00', $until)];
    }

    public function delete(string $id): void
    {
        $this->verify();
        if (!Auth::isAdmin()) {
            abort(403, 'Only the administrator can delete a course.');
        }
        $c = $this->course($id);
        foreach (Db::all('SELECT video_ref FROM lessons WHERE course_id = ? AND video_type = ?', [$c['id'], 'file']) as $l) {
            Upload::removePrivate($l['video_ref']);
        }
        foreach (Db::all('SELECT f.path FROM lesson_files f JOIN lessons l ON l.id = f.lesson_id WHERE l.course_id = ?', [$c['id']]) as $f) {
            Upload::removePrivate($f['path']);
        }
        foreach (Db::all("SELECT pdf_path FROM course_modules WHERE course_id = ? AND pdf_path <> ''", [$c['id']]) as $mp) {
            Upload::removePrivate($mp['pdf_path']);
        }
        Upload::remove($c['cover_image']);
        Db::delete('courses', 'id = ?', [$c['id']]);
        Audit::log('course_delete', $c['title']);
        flash('success', '“' . $c['title'] . '” and all its lessons, quizzes and enrolments were deleted.');
        redirect('admin/courses');
    }

    /* ------------------------------------------------------------------ curriculum */

    public function curriculum(string $id): void
    {
        $c = $this->course($id);
        $this->page('admin/curriculum', ['title' => 'Curriculum: ' . $c['title'], 'tab' => 'courses', 'c' => $c, 'cur' => Learn::curriculum((int)$c['id'], false)]);
    }

    public function moduleAdd(string $id): void
    {
        $this->verify();
        $c = $this->course($id);
        $title = mb_substr($this->in('title'), 0, 200);
        if ($title === '') {
            flash('error', 'Please enter a module title.');
        } else {
            $max = (int)Db::val('SELECT COALESCE(MAX(sort_order), 0) FROM course_modules WHERE course_id = ?', [$c['id']]);
            Db::insert('course_modules', ['course_id' => $c['id'], 'title' => $title, 'summary' => mb_substr($this->in('summary'), 0, 400), 'sort_order' => $max + 10]);
            flash('success', 'Module added.');
        }
        redirect('admin/courses/' . $c['id'] . '/curriculum');
    }

    public function moduleSave(string $mid): void
    {
        $this->verify();
        $m = Db::one('SELECT * FROM course_modules WHERE id = ?', [(int)$mid]);
        if (!$m) {
            abort(404);
        }
        $this->own((int)$m['course_id']);
        $title = mb_substr($this->in('title'), 0, 200);
        if ($title !== '') {
            Db::update('course_modules', ['title' => $title, 'summary' => mb_substr($this->in('summary'), 0, 400)], 'id = ?', [$m['id']]);
            flash('success', 'Module saved.');
        }
        redirect('admin/courses/' . $m['course_id'] . '/curriculum');
    }

    public function moduleMove(string $mid): void
    {
        $this->verify();
        $m = Db::one('SELECT * FROM course_modules WHERE id = ?', [(int)$mid]);
        if ($m) {
            $this->own((int)$m['course_id']);
            $this->move('course_modules', 'course_id', (int)$m['course_id'], (int)$m['id'], $this->dir());
        }
        redirect('admin/courses/' . ($m['course_id'] ?? 0) . '/curriculum');
    }

    public function moduleDelete(string $mid): void
    {
        $this->verify();
        $m = Db::one('SELECT * FROM course_modules WHERE id = ?', [(int)$mid]);
        if ($m) {
            $this->own((int)$m['course_id']);
            Upload::removePrivate($m['pdf_path']);
            foreach (Db::all('SELECT id, video_type, video_ref FROM lessons WHERE module_id = ?', [$m['id']]) as $l) {
                $this->cleanLessonFiles($l);
            }
            Db::delete('course_modules', 'id = ?', [$m['id']]);
            flash('success', 'Module deleted with its lessons and quiz.');
        }
        redirect('admin/courses/' . ($m['course_id'] ?? 0) . '/curriculum');
    }

    private function cleanLessonFiles(array $l): void
    {
        if (($l['video_type'] ?? '') === 'file') {
            Upload::removePrivate($l['video_ref']);
        }
        foreach (Db::all('SELECT path FROM lesson_files WHERE lesson_id = ?', [$l['id']]) as $f) {
            Upload::removePrivate($f['path']);
        }
    }

    /* ------------------------------------------------------------------ lessons */

    public function lessonForm(string $id, ?string $lid = null): void
    {
        $l = null;
        if ($lid !== null) {
            $l = Learn::lesson((int)$lid);
            if (!$l) {
                abort(404);
            }
            $c = $this->course((string)$l['course_id']);
        } else {
            $c = $this->course($id);
        }
        $this->page('admin/lesson_form', [
            'title' => $l ? 'Edit lesson: ' . $l['title'] : 'Add a lesson', 'tab' => 'courses', 'c' => $c, 'l' => $l,
            'modules' => Db::all('SELECT id, title FROM course_modules WHERE course_id = ? ORDER BY sort_order, id', [$c['id']]),
            'files' => $l ? Db::all('SELECT * FROM lesson_files WHERE lesson_id = ?', [$l['id']]) : [],
            'bank' => $l ? Learn::bank((int)$l['id']) : null,
            'bankCount' => $l && ($bk = Learn::bank((int)$l['id'])) ? (int)Db::val('SELECT COUNT(*) FROM questions WHERE quiz_id = ?', [$bk['id']]) : 0,
            'preselect' => (int)($_GET['module'] ?? 0),
        ]);
    }

    public function lessonSave(string $id, ?string $lid = null): void
    {
        $this->verify();
        $existing = $lid !== null ? Learn::lesson((int)$lid) : null;
        if ($lid !== null && !$existing) {
            abort(404);
        }
        $c = $this->course($existing ? (string)$existing['course_id'] : $id);
        $errors = [];
        $moduleId = (int)$this->in('module_id');
        if (!Db::val('SELECT 1 FROM course_modules WHERE id = ? AND course_id = ?', [$moduleId, $c['id']])) {
            $errors[] = 'Please choose a module (add one on the curriculum page first).';
        }
        $title = mb_substr($this->in('title'), 0, 200);
        if ($title === '') {
            $errors[] = 'Please enter the lesson title.';
        }
        $vtype = in_array($this->in('video_type'), ['none', 'file', 'youtube', 'vimeo'], true) ? $this->in('video_type') : 'none';
        $vref = '';
        $newVideo = null;
        if ($vtype === 'youtube' || $vtype === 'vimeo') {
            $p = Learn::parseVideoUrl($this->in('video_url'));
            if (!$p || $p[0] !== $vtype) {
                $errors[] = 'Paste a valid ' . ($vtype === 'youtube' ? 'YouTube' : 'Vimeo') . ' link.';
            } else {
                $vref = $p[1];
            }
        } elseif ($vtype === 'file') {
            if (Upload::has($_FILES['video_file'] ?? null)) {
                try {
                    $vref = $newVideo = Upload::video($_FILES['video_file']);
                } catch (RuntimeException $e) {
                    $errors[] = $e->getMessage();
                }
            } elseif ($this->in('video_name') !== '') {
                // a file already copied into storage/private/videos (e.g. too large for the browser upload)
                $name = basename($this->in('video_name'));
                if (!preg_match('/^[A-Za-z0-9._-]+\.(mp4|webm)$/i', $name) || !is_file(ROOT . '/storage/private/videos/' . $name)) {
                    $errors[] = 'No such file in storage/private/videos. Copy the MP4/WebM there first, then type its exact file name.';
                } else {
                    $vref = 'storage/private/videos/' . $name;
                }
            } elseif ($existing && $existing['video_type'] === 'file') {
                $vref = $existing['video_ref'];
            } else {
                $errors[] = 'Choose a video file, type the name of a file in storage/private/videos, or pick another video option.';
            }
        }
        $pdfs = [];
        foreach (Upload::has($_FILES['new_files'] ?? null) && is_array($_FILES['new_files']['name']) ? array_keys($_FILES['new_files']['name']) : [] as $i) {
            if (($_FILES['new_files']['error'][$i] ?? 4) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $f = ['name' => $_FILES['new_files']['name'][$i], 'type' => $_FILES['new_files']['type'][$i], 'tmp_name' => $_FILES['new_files']['tmp_name'][$i], 'error' => $_FILES['new_files']['error'][$i], 'size' => $_FILES['new_files']['size'][$i]];
            try {
                $pdfs[] = [Upload::coursePdf($f), pathinfo($f['name'], PATHINFO_FILENAME), (int)$f['size']];
            } catch (RuntimeException $e) {
                $errors[] = $f['name'] . ': ' . $e->getMessage();
            }
        }
        if ($errors) {
            if ($newVideo) {
                Upload::removePrivate($newVideo);
            }
            foreach ($pdfs as $p) {
                Upload::removePrivate($p[0]);
            }
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect($existing ? 'admin/lessons/' . $existing['id'] : 'admin/courses/' . $c['id'] . '/lessons/new');
        }
        $d = [
            'module_id' => $moduleId, 'title' => $title, 'objectives' => mb_substr($this->in('objectives'), 0, 2000), 'content' => mb_substr($this->in('content'), 0, 200000),
            'video_type' => $vtype, 'video_ref' => $vref, 'video_minutes' => max(0, min(1000, (int)$this->in('video_minutes'))),
            'is_free_preview' => isset($_POST['is_free_preview']) ? 1 : 0, 'status' => $this->in('status') === 'draft' ? 'draft' : 'published',
        ];
        if ($existing) {
            $d['sort_order'] = $this->in('sort_order') !== '' ? (int)$this->in('sort_order') : (int)$existing['sort_order'];
            Db::update('lessons', $d, 'id = ?', [$existing['id']]);
            if ($existing['video_type'] === 'file' && $existing['video_ref'] !== $vref) {
                Upload::removePrivate($existing['video_ref']);
            }
            $lessonId = (int)$existing['id'];
        } else {
            $max = (int)Db::val('SELECT COALESCE(MAX(sort_order), 0) FROM lessons WHERE module_id = ?', [$moduleId]);
            $lessonId = Db::insert('lessons', $d + ['course_id' => $c['id'], 'sort_order' => $max + 10]);
        }
        foreach ((array)($_POST['remove_file'] ?? []) as $fid) {
            $f = Db::one('SELECT * FROM lesson_files WHERE id = ? AND lesson_id = ?', [(int)$fid, $lessonId]);
            if ($f) {
                Upload::removePrivate($f['path']);
                Db::delete('lesson_files', 'id = ?', [$f['id']]);
            }
        }
        $label = $this->in('new_file_title');
        foreach ($pdfs as [$path, $name, $size]) {
            Db::insert('lesson_files', ['lesson_id' => $lessonId, 'title' => mb_substr(($label !== '' && count($pdfs) === 1) ? $label : $name, 0, 190), 'path' => $path, 'size' => $size]);
        }
        Audit::log($existing ? 'lesson_update' : 'lesson_create', $title);
        clear_old();
        flash('success', 'Lesson saved.');
        redirect($existing ? 'admin/lessons/' . $lessonId : 'admin/courses/' . $c['id'] . '/curriculum');
    }

    public function lessonMove(string $lid): void
    {
        $this->verify();
        $l = Learn::lesson((int)$lid);
        if ($l) {
            $this->own((int)$l['course_id']);
            $this->move('lessons', 'module_id', (int)$l['module_id'], (int)$l['id'], $this->dir());
        }
        redirect('admin/courses/' . ($l['course_id'] ?? 0) . '/curriculum');
    }

    public function lessonDelete(string $lid): void
    {
        $this->verify();
        $l = Learn::lesson((int)$lid);
        if ($l) {
            $this->own((int)$l['course_id']);
            $this->cleanLessonFiles($l);
            Db::delete('lessons', 'id = ?', [$l['id']]);
            flash('success', 'Lesson deleted.');
        }
        redirect('admin/courses/' . ($l['course_id'] ?? 0) . '/curriculum');
    }

    /** Opens (creating it if needed) the question bank of a lesson. */
    public function bankOpen(string $lid): void
    {
        $this->verify();
        $l = Learn::lesson((int)$lid);
        if (!$l) {
            abort(404);
        }
        $this->own((int)$l['course_id']);
        $b = Learn::ensureBank($l);
        redirect('admin/quizzes/' . $b['id']);
    }

    /** Upload, replace or remove the complete-module PDF that students download after finishing a module. */
    public function modulePdf(string $mid): void
    {
        $this->verify();
        $m = Db::one('SELECT * FROM course_modules WHERE id = ?', [(int)$mid]);
        if (!$m) {
            abort(404);
        }
        $this->own((int)$m['course_id']);
        $back = 'admin/courses/' . $m['course_id'] . '/curriculum';
        if (!empty($_POST['remove_pdf'])) {
            Upload::removePrivate($m['pdf_path']);
            Db::update('course_modules', ['pdf_path' => '', 'pdf_title' => '', 'pdf_size' => 0], 'id = ?', [$m['id']]);
            flash('success', 'Module PDF removed. Students will get a printable page of the module notes instead.');
            redirect($back);
        }
        $f = $_FILES['module_pdf'] ?? null;
        if (!Upload::has($f)) {
            flash('error', 'Choose a PDF file to upload.');
            redirect($back);
        }
        try {
            $path = Upload::coursePdf($f);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect($back);
        }
        Upload::removePrivate($m['pdf_path']);
        Db::update('course_modules', ['pdf_path' => $path, 'pdf_title' => mb_substr((string)pathinfo($f['name'], PATHINFO_FILENAME), 0, 190), 'pdf_size' => (int)$f['size']], 'id = ?', [$m['id']]);
        Audit::log('module_pdf', $m['title']);
        flash('success', 'Module PDF saved. Students can download it after finishing the module.');
        redirect($back);
    }

    /* ------------------------------------------------------------------ quizzes & questions */

    public function quizForm(string $id, ?string $qid = null): void
    {
        $q = null;
        if ($qid !== null) {
            $q = Learn::quiz((int)$qid);
            if (!$q) {
                abort(404);
            }
            $c = $this->course((string)$q['course_id']);
        } else {
            $c = $this->course($id);
        }
        $this->page('admin/quiz_form', [
            'title' => $q ? 'Quiz: ' . $q['title'] : 'Add a quiz', 'tab' => 'courses', 'c' => $c, 'q' => $q,
            'modules' => Db::all('SELECT id, title FROM course_modules WHERE course_id = ? ORDER BY sort_order, id', [$c['id']]),
            'questions' => $q ? Learn::questions((int)$q['id']) : [],
            'lesson' => $q && $q['lesson_id'] ? Learn::lesson((int)$q['lesson_id']) : null,
            'preselect' => ($_GET['module'] ?? '') === 'final' ? 0 : (int)($_GET['module'] ?? 0),
            'final' => ($_GET['module'] ?? '') === 'final',
        ]);
    }

    public function quizSave(string $id, ?string $qid = null): void
    {
        $this->verify();
        $existing = $qid !== null ? Learn::quiz((int)$qid) : null;
        if ($qid !== null && !$existing) {
            abort(404);
        }
        $c = $this->course($existing ? (string)$existing['course_id'] : $id);
        if ($existing && $existing['kind'] === 'check') {
            Db::update('quizzes', ['questions_to_draw' => max(1, min(10, (int)$this->in('questions_to_draw'))), 'status' => $this->in('status') === 'draft' ? 'draft' : 'published'], 'id = ?', [$existing['id']]);
            Audit::log('bank_update', $existing['title']);
            flash('success', 'Question bank settings saved.');
            redirect('admin/quizzes/' . $existing['id']);
        }
        $kind = $this->in('kind') === 'final' ? 'final' : 'quiz';
        $moduleId = $kind === 'final' ? null : (int)$this->in('module_id');
        $errors = [];
        if ($kind === 'quiz' && !Db::val('SELECT 1 FROM course_modules WHERE id = ? AND course_id = ?', [$moduleId, $c['id']])) {
            $errors[] = 'Choose the module this quiz belongs to, or make it a final exam.';
        }
        $title = mb_substr($this->in('title'), 0, 200);
        if ($title === '') {
            $errors[] = 'Please enter the quiz title.';
        }
        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect($existing ? 'admin/quizzes/' . $existing['id'] : 'admin/courses/' . $c['id'] . '/quizzes/new');
        }
        $d = [
            'module_id' => $moduleId, 'title' => $title, 'kind' => $kind, 'instructions' => mb_substr($this->in('instructions'), 0, 5000),
            'pass_mark' => max(1, min(100, (int)($this->in('pass_mark') ?: 60))), 'time_limit_minutes' => max(0, min(600, (int)$this->in('time_limit_minutes'))),
            'max_attempts' => max(0, min(100, (int)$this->in('max_attempts'))), 'questions_to_draw' => max(0, min(500, (int)$this->in('questions_to_draw'))),
            'shuffle' => isset($_POST['shuffle']) ? 1 : 0, 'status' => $this->in('status') === 'draft' ? 'draft' : 'published',
        ];
        if ($existing) {
            Db::update('quizzes', $d, 'id = ?', [$existing['id']]);
            $quizId = (int)$existing['id'];
        } else {
            $max = (int)Db::val('SELECT COALESCE(MAX(sort_order), 0) FROM quizzes WHERE course_id = ?', [$c['id']]);
            $quizId = Db::insert('quizzes', $d + ['course_id' => $c['id'], 'sort_order' => $kind === 'final' ? 900 : $max + 10]);
        }
        Audit::log($existing ? 'quiz_update' : 'quiz_create', $title);
        clear_old();
        flash('success', $existing ? 'Quiz saved.' : 'Quiz created. Now add its questions.');
        redirect('admin/quizzes/' . $quizId);
    }

    public function quizDelete(string $qid): void
    {
        $this->verify();
        $q = Learn::quiz((int)$qid);
        if ($q) {
            $this->own((int)$q['course_id']);
            Db::delete('quizzes', 'id = ?', [$q['id']]);
            flash('success', $q['kind'] === 'check' ? 'Question bank deleted.' : 'Quiz deleted.');
            if ($q['kind'] === 'check' && $q['lesson_id']) {
                redirect('admin/lessons/' . $q['lesson_id']);
            }
        }
        redirect('admin/courses/' . ($q['course_id'] ?? 0) . '/curriculum');
    }

    public function questionForm(string $qid, ?string $id = null): void
    {
        $question = null;
        if ($id !== null) {
            $question = Db::one('SELECT * FROM questions WHERE id = ?', [(int)$id]);
            if (!$question) {
                abort(404);
            }
            $question['options'] = Db::all('SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order, id', [$question['id']]);
            $qid = (string)$question['quiz_id'];
        }
        $quiz = Learn::quiz((int)$qid);
        if (!$quiz) {
            abort(404);
        }
        $this->own((int)$quiz['course_id']);
        $this->page('admin/question_form', ['title' => $question ? 'Edit question' : 'Add a question', 'tab' => 'courses', 'quiz' => $quiz, 'qn' => $question]);
    }

    public function questionSave(string $qid, ?string $id = null): void
    {
        $this->verify();
        $existing = $id !== null ? Db::one('SELECT * FROM questions WHERE id = ?', [(int)$id]) : null;
        if ($id !== null && !$existing) {
            abort(404);
        }
        $quiz = Learn::quiz((int)($existing['quiz_id'] ?? $qid));
        if (!$quiz) {
            abort(404);
        }
        $this->own((int)$quiz['course_id']);
        $type = isset(Learn::Q_TYPES[$this->in('type')]) ? $this->in('type') : 'mcq';
        $prompt = mb_substr($this->in('prompt'), 0, 5000);
        $errors = [];
        if ($prompt === '') {
            $errors[] = 'Please write the question.';
        }
        $options = [];
        $num = null;
        $accepted = null;
        if ($type === 'mcq' || $type === 'multi') {
            foreach (nl_lines($this->in('options')) as $line) {
                $correct = str_starts_with($line, '*');
                $label = trim($correct ? substr($line, 1) : $line);
                if ($label !== '') {
                    $options[] = [mb_substr($label, 0, 490), $correct];
                }
            }
            $nc = count(array_filter($options, fn($o) => $o[1]));
            if (count($options) < 2) {
                $errors[] = 'Give at least two options (one per line).';
            } elseif ($type === 'mcq' && $nc !== 1) {
                $errors[] = 'Mark exactly ONE correct option by starting its line with *.';
            } elseif ($type === 'multi' && $nc < 1) {
                $errors[] = 'Mark at least one correct option by starting its line with *.';
            }
        } elseif ($type === 'truefalse') {
            $options = [['True', $this->in('tf_correct') !== 'false'], ['False', $this->in('tf_correct') === 'false']];
        } elseif ($type === 'numeric') {
            $raw = str_replace(',', '', $this->in('num_answer'));
            if (!is_numeric($raw)) {
                $errors[] = 'Enter the correct numeric answer.';
            } else {
                $num = (float)$raw;
            }
        } else {
            $accepted = implode("\n", nl_lines($this->in('accepted')));
            if ($accepted === '') {
                $errors[] = 'Enter at least one accepted answer (one per line).';
            }
        }
        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect($existing ? 'admin/questions/' . $existing['id'] : 'admin/quizzes/' . $quiz['id'] . '/questions/new');
        }
        $d = [
            'type' => $type, 'prompt' => $prompt, 'explanation' => mb_substr($this->in('explanation'), 0, 5000), 'points' => max(1, min(100, (int)($this->in('points') ?: 1))),
            'num_answer' => $num, 'num_tolerance' => max(0.0, min(100.0, (float)($this->in('num_tolerance') !== '' ? $this->in('num_tolerance') : 1))),
            'num_unit' => mb_substr($this->in('num_unit'), 0, 28), 'accepted' => $accepted,
        ];
        if ($existing) {
            Db::update('questions', $d, 'id = ?', [$existing['id']]);
            $questionId = (int)$existing['id'];
            Db::delete('question_options', 'question_id = ?', [$questionId]);
        } else {
            $max = (int)Db::val('SELECT COALESCE(MAX(sort_order), 0) FROM questions WHERE quiz_id = ?', [$quiz['id']]);
            $questionId = Db::insert('questions', $d + ['quiz_id' => $quiz['id'], 'sort_order' => $max + 10]);
        }
        foreach ($options as $i => [$label, $correct]) {
            Db::insert('question_options', ['question_id' => $questionId, 'label' => $label, 'is_correct' => $correct ? 1 : 0, 'sort_order' => ($i + 1) * 10]);
        }
        clear_old();
        flash('success', 'Question saved.');
        redirect(($_POST['again'] ?? '') === '1' ? 'admin/quizzes/' . $quiz['id'] . '/questions/new' : 'admin/quizzes/' . $quiz['id']);
    }

    /* Single-parameter route entry points (edit by row id). */
    public function lessonEdit(string $lid): void { $this->lessonForm('0', $lid); }
    public function lessonUpdate(string $lid): void { $this->lessonSave('0', $lid); }
    public function quizEdit(string $qid): void { $this->quizForm('0', $qid); }
    public function quizUpdate(string $qid): void { $this->quizSave('0', $qid); }
    public function questionEdit(string $id): void { $this->questionForm('0', $id); }
    public function questionUpdate(string $id): void { $this->questionSave('0', $id); }

    private function ownQuestion(array $q): void
    {
        $quiz = Learn::quiz((int)$q['quiz_id']);
        if (!$quiz) {
            abort(404);
        }
        $this->own((int)$quiz['course_id']);
    }

    public function questionMove(string $id): void
    {
        $this->verify();
        $q = Db::one('SELECT * FROM questions WHERE id = ?', [(int)$id]);
        if ($q) {
            $this->ownQuestion($q);
            $this->move('questions', 'quiz_id', (int)$q['quiz_id'], (int)$q['id'], $this->dir());
        }
        redirect('admin/quizzes/' . ($q['quiz_id'] ?? 0));
    }

    public function questionDelete(string $id): void
    {
        $this->verify();
        $q = Db::one('SELECT * FROM questions WHERE id = ?', [(int)$id]);
        if ($q) {
            $this->ownQuestion($q);
            Db::delete('questions', 'id = ?', [$q['id']]);
            flash('success', 'Question deleted.');
        }
        redirect('admin/quizzes/' . ($q['quiz_id'] ?? 0));
    }
}

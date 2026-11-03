<?php
declare(strict_types=1);

/** Course chat room: students enrolled in the course and the lecturer. Messages are plain text; the lecturer can remove messages and mute people. */
final class CourseChatController
{
    /** @return array{0:array,1:array} course, user */
    private function context(string $slug, bool $json = false): array
    {
        if (!module_enabled('learn')) {
            (new SiteController())->soon();
            exit;
        }
        if (!Auth::check()) {
            if ($json) {
                json_out(['ok' => false, 'error' => 'Please sign in again.'], 403);
            }
            $_SESSION['intended'] = '/learn/' . $slug . '/chat';
            flash('info', 'Please sign in to use the course chat.');
            redirect('login');
        }
        $c = Learn::accessibleCourse($slug); // 404, or the locked page during a lockdown (not for administrators)
        $u = Auth::user();
        if (!CourseChat::canUse($u, $c)) {
            if ($json) {
                json_out(['ok' => false, 'error' => 'The chat room is for students enrolled in this course.'], 403);
            }
            flash('info', CourseChat::enabled($c) ? 'The chat room is for students enrolled in this course.' : 'The chat room of this course is switched off.');
            redirect('learn/' . $c['slug']);
        }
        return [$c, $u];
    }

    private function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    public function show(string $slug): void
    {
        [$c, $u] = $this->context($slug);
        $messages = CourseChat::messages((int)$c['id']);
        view('public/course_chat', [
            'title' => 'Chat: ' . $c['title'], 'noindex' => true, 'meta_desc' => '', 'c' => $c, 'u' => $u,
            'list' => CourseChat::listHtml($c, $u, $messages), 'sig' => CourseChat::signature($messages),
            'isAdmin' => $u['role'] === 'admin', 'muted' => $u['role'] !== 'admin' && CourseChat::muted((int)$u['id'], (int)$c['id']),
            'people' => (int)Db::val("SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND status = 'active'", [$c['id']]),
        ]);
    }

    public function poll(string $slug): void
    {
        [$c, $u] = $this->context($slug, true);
        $messages = CourseChat::messages((int)$c['id']);
        json_out(['ok' => true, 'sig' => CourseChat::signature($messages), 'html' => CourseChat::listHtml($c, $u, $messages)]);
    }

    public function post(string $slug): void
    {
        $json = $this->wantsJson();
        [$c, $u] = $this->context($slug, $json);
        csrf_check();
        $fail = function (string $msg) use ($json, $c): never {
            if ($json) {
                json_out(['ok' => false, 'error' => $msg]);
            }
            flash('error', $msg);
            redirect('learn/' . $c['slug'] . '/chat');
        };
        if ($u['role'] !== 'admin' && CourseChat::muted((int)$u['id'], (int)$c['id'])) {
            $fail('The lecturer has switched off your messages in this chat.');
        }
        $body = trim(preg_replace("/\r\n?/", "\n", (string)($_POST['message'] ?? '')));
        $body = preg_replace("/\n{3,}/", "\n\n", $body);
        if ($body === '') {
            $fail('Please type a message first.');
        }
        if (mb_strlen($body) > CourseChat::MAX_LEN) {
            $fail('That message is too long (maximum ' . CourseChat::MAX_LEN . ' characters).');
        }
        if (!rate_ok('chat', 40, 60)) {
            $fail('You are sending messages very quickly. Please wait a moment.');
        }
        if (Db::val("SELECT 1 FROM course_chat WHERE course_id = ? AND user_id = ? AND body = ? AND created_at > (NOW() - INTERVAL 20 SECOND)", [$c['id'], $u['id'], $body])) {
            $fail('You just sent that same message.');
        }
        CourseChat::post((int)$c['id'], (int)$u['id'], $body);
        if ($json) {
            $messages = CourseChat::messages((int)$c['id']);
            json_out(['ok' => true, 'sig' => CourseChat::signature($messages), 'html' => CourseChat::listHtml($c, $u, $messages)]);
        }
        redirect('learn/' . $c['slug'] . '/chat#end');
    }

    public function delete(string $slug, string $id): void
    {
        [$c, $u] = $this->context($slug);
        csrf_check();
        $m = Db::one('SELECT * FROM course_chat WHERE id = ? AND course_id = ?', [(int)$id, $c['id']]);
        if ($m && ($u['role'] === 'admin' || (int)$m['user_id'] === (int)$u['id'])) {
            Db::delete('course_chat', 'id = ?', [$m['id']]);
            flash('success', 'Message deleted.');
        }
        redirect('learn/' . $c['slug'] . '/chat');
    }

    /** Lecturer only: stop (or allow again) a student from posting in this course's chat. */
    public function mute(string $slug, string $uid): void
    {
        [$c, $u] = $this->context($slug);
        csrf_check();
        if ($u['role'] !== 'admin') {
            abort(403);
        }
        $on = ($_POST['mute'] ?? '1') === '1' ? 1 : 0;
        if (Db::val('SELECT 1 FROM enrolments WHERE user_id = ? AND course_id = ?', [(int)$uid, $c['id']])) {
            Db::run('UPDATE enrolments SET chat_muted = ? WHERE user_id = ? AND course_id = ?', [$on, (int)$uid, $c['id']]);
            Audit::log($on ? 'chat_mute' : 'chat_unmute', $c['title'] . ' / user ' . (int)$uid);
            flash('success', $on ? 'That student can no longer post in this chat.' : 'That student can post again.');
        }
        redirect('learn/' . $c['slug'] . '/chat');
    }
}

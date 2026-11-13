<?php
declare(strict_types=1);

/**
 * Lecturers: registered members the administrator has given a lecturer account (users.role = 'lecturer').
 * A lecturer manages only the courses assigned to them (courses.lecturer_id) and sees the progress of only their own students.
 * The administrator manages every course and every lecturer.
 */
final class Lecturers
{
    /** What the role is called on the site (the administrator can rename it: Lecturer, Instructor, Tutor…). */
    public static function label(): string
    {
        $l = trim(Settings::get('lecturer_label', 'Lecturer'));
        return $l !== '' ? mb_substr($l, 0, 30) : 'Lecturer';
    }

    public static function isLecturer(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? '') === 'lecturer';
    }

    /** May this person manage this course? The administrator: every course. A lecturer: only courses assigned to them, and only while two-step verification is on. */
    public static function canManage(?array $user, array|int $course): bool
    {
        if ($user === null) {
            return false;
        }
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }
        if (!self::isLecturer($user)) {
            return false;
        }
        if (empty($user['totp_enabled_at'])) {
            return false; // two-step verification is compulsory for lecturers: without it every lecturer right is suspended
        }
        $owner = is_array($course) ? ($course['lecturer_id'] ?? null) : Db::val('SELECT lecturer_id FROM courses WHERE id = ?', [(int)$course]);
        return $owner !== null && (int)$owner === (int)$user['id'];
    }

    /** SQL condition limiting course rows to those this person manages, for example ['c.lecturer_id = ?', [7]]. */
    public static function scope(array $user, string $alias = 'c'): array
    {
        if (($user['role'] ?? '') === 'admin') {
            return ['1 = 1', []];
        }
        return ["$alias.lecturer_id = ?", [(int)$user['id']]];
    }

    /** All lecturers with their course and student counts. */
    public static function all(): array
    {
        return Db::all(
            "SELECT u.id, u.name, u.email, u.lecturer_since, u.lecturer_bio, u.last_login, u.totp_enabled_at,
                    (SELECT COUNT(*) FROM courses c WHERE c.lecturer_id = u.id) AS courses,
                    (SELECT COUNT(*) FROM enrolments e JOIN courses c ON c.id = e.course_id WHERE c.lecturer_id = u.id AND e.status = 'active') AS students
             FROM users u WHERE u.role = 'lecturer' ORDER BY u.name"
        );
    }

    /** Makes an existing member a lecturer. Returns an error message, or '' on success. */
    public static function promote(string $email, string $bio = ''): string
    {
        $u = Db::one('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
        if (!$u) {
            return 'No registered member has that email address. Ask the person to register first, then try again.';
        }
        if ($u['role'] === 'admin') {
            return 'That is an administrator account. Administrators already manage everything.';
        }
        if ($u['role'] === 'lecturer') {
            return 'That member is already a ' . mb_strtolower(self::label()) . '.';
        }
        if (empty($u['email_verified_at'])) {
            return 'That member has not verified their email address yet, so they cannot be made a ' . mb_strtolower(self::label()) . '.';
        }
        Db::update('users', ['role' => 'lecturer', 'lecturer_since' => date('Y-m-d H:i:s'), 'lecturer_bio' => mb_substr(trim($bio), 0, 600)], 'id = ?', [$u['id']]);
        Auth::bumpEpoch((int)$u['id'], false); // start a fresh sign-in so the new rights and idle rules apply
        Audit::log('lecturer_added', $u['email']);
        $site = Settings::get('site_name');
        $label = mb_strtolower(self::label());
        Mailer::send($u['email'], "[$site] You are now a $label", "Hello {$u['name']},\n\nYou have been made a $label on $site. You can create and manage your own courses, answer your students' questions and see how your students are progressing.\n\nSign in here:\n" . abs_url('admin/login') . "\n\nTwo-step verification is COMPULSORY for a $label: your area opens as soon as you turn it on (Account → Security, it takes two minutes with Google or Microsoft Authenticator), and it cannot be turned off while you are a $label. It protects your students' details.\n\n$site");
        return '';
    }

    /** Returns a lecturer to an ordinary member. Their courses become unassigned. Returns how many courses were unassigned. */
    public static function demote(int $userId): int
    {
        $u = Db::one("SELECT * FROM users WHERE id = ? AND role = 'lecturer'", [$userId]);
        if (!$u) {
            return 0;
        }
        $n = (int)Db::val('SELECT COUNT(*) FROM courses WHERE lecturer_id = ?', [$userId]);
        Db::run('UPDATE courses SET lecturer_id = NULL WHERE lecturer_id = ?', [$userId]);
        Db::update('users', ['role' => 'customer', 'lecturer_since' => null], 'id = ?', [$userId]);
        Auth::bumpEpoch($userId, false); // ends any open sessions at once
        Audit::log('lecturer_removed', $u['email']);
        return $n;
    }
}

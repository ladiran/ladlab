<?php
declare(strict_types=1);

/** The chat room of a course: enrolled students and the lecturer (administrator) talk to each other. */
final class CourseChat
{
    public const MAX_LEN = 1000;
    public const SHOW = 80;

    public static function enabled(array $course): bool
    {
        return (int)($course['chat_enabled'] ?? 1) === 1;
    }

    /** Enrolled students (and administrators) of a course whose chat is switched on. */
    public static function canUse(?array $user, array $course): bool
    {
        if (!$user) {
            return false;
        }
        if (Lecturers::canManage($user, $course)) {
            return true; // administrators and the course's lecturer
        }
        return self::enabled($course) && Learn::hasAccess($user, (int)$course['id']);
    }

    /** May delete any message and mute students: administrators and the course's lecturer. */
    public static function isModerator(?array $user, array $course): bool
    {
        return Lecturers::canManage($user, $course);
    }

    public static function muted(int $userId, int $courseId): bool
    {
        return (bool)Db::val('SELECT chat_muted FROM enrolments WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
    }

    /** @return int[] ids of students the lecturer has muted in this course */
    public static function mutedIds(int $courseId): array
    {
        return array_map('intval', array_column(Db::all('SELECT user_id FROM enrolments WHERE course_id = ? AND chat_muted = 1', [$courseId]), 'user_id'));
    }

    /** The latest messages, oldest first. */
    public static function messages(int $courseId, int $limit = self::SHOW): array
    {
        $rows = Db::all(
            'SELECT m.id, m.user_id, m.body, m.created_at, u.name, u.role FROM course_chat m LEFT JOIN users u ON u.id = m.user_id WHERE m.course_id = ? ORDER BY m.id DESC LIMIT ' . max(1, $limit),
            [$courseId]
        );
        return array_reverse($rows);
    }

    public static function post(int $courseId, int $userId, string $body): int
    {
        return Db::insert('course_chat', ['course_id' => $courseId, 'user_id' => $userId, 'body' => mb_substr($body, 0, self::MAX_LEN)]);
    }

    /** Changes whenever a message is added or removed, so the page knows when to redraw. */
    public static function signature(array $messages): string
    {
        return ($messages ? (int)end($messages)['id'] : 0) . '-' . count($messages);
    }

    /** What is shown as the author: the lecturer by name, students as "Ada O." (never full names). */
    public static function label(array $m, ?array $course = null): string
    {
        if (($m['role'] ?? '') === 'admin') {
            return (string)(setting('owner_name') ?: $m['name'] ?: Lecturers::label());
        }
        if ($course !== null && ($m['role'] ?? '') === 'lecturer' && (int)($course['lecturer_id'] ?? 0) === (int)$m['user_id']) {
            return (string)$m['name'];
        }
        return Community::shortName((string)($m['name'] ?? 'Student'));
    }

    /** The message list as HTML (used by the page and by the live updates). */
    public static function listHtml(array $course, array $viewer, array $messages): string
    {
        ob_start();
        partial('course_chat_list', ['c' => $course, 'viewer' => $viewer, 'messages' => $messages, 'mutedIds' => self::isModerator($viewer, $course) ? self::mutedIds((int)$course['id']) : []]);
        return (string)ob_get_clean();
    }
}

<?php
declare(strict_types=1);

/** Course community features: ratings and reviews, lesson questions and answers, announcements. */
final class Community
{
    /** @return array{avg: float, count: int} */
    public static function ratingStats(int $courseId): array
    {
        $r = Db::one("SELECT COUNT(*) AS n, COALESCE(AVG(rating), 0) AS a FROM course_reviews WHERE course_id = ? AND status = 'published'", [$courseId]);
        return ['avg' => round((float)($r['a'] ?? 0), 1), 'count' => (int)($r['n'] ?? 0)];
    }

    public static function reviews(int $courseId, int $limit = 20): array
    {
        return Db::all(
            "SELECT r.rating, r.comment, r.created_at, u.name FROM course_reviews r JOIN users u ON u.id = r.user_id
             WHERE r.course_id = ? AND r.status = 'published' ORDER BY r.id DESC LIMIT " . (int)$limit,
            [$courseId]
        );
    }

    public static function announcements(int $courseId, int $limit = 5): array
    {
        return Db::all('SELECT * FROM course_announcements WHERE course_id = ? ORDER BY id DESC LIMIT ' . (int)$limit, [$courseId]);
    }

    /** Answered questions for everyone enrolled, plus the student's own unanswered ones. */
    public static function lessonQuestions(int $lessonId, int $userId): array
    {
        return Db::all(
            "SELECT q.*, u.name FROM lesson_questions q JOIN users u ON u.id = q.user_id
             WHERE q.lesson_id = ? AND ((q.status = 'answered') OR (q.user_id = ? AND q.status = 'open')) ORDER BY q.id DESC LIMIT 30",
            [$lessonId, $userId]
        );
    }

    /** "Ada O." style: never show a student's full name or email to other students. */
    public static function shortName(string $name): string
    {
        $p = preg_split('/\s+/', trim($name)) ?: [];
        $first = $p[0] ?? 'Student';
        $last = count($p) > 1 ? ' ' . mb_strtoupper(mb_substr(end($p), 0, 1)) . '.' : '';
        return mb_substr($first, 0, 20) . $last;
    }

    public static function stars(float $avg): string
    {
        $full = (int)round($avg);
        return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
    }

    public static function notifyOwner(string $subject, string $body): void
    {
        $to = Settings::get('notify_email') ?: Settings::get('contact_email');
        if ($to !== '') {
            Mailer::send($to, '[' . Settings::get('site_name') . '] ' . $subject, $body);
        }
    }

    /** Emails an announcement to every active student of the course. Returns how many were sent. */
    public static function emailAnnouncement(array $course, string $title, string $body): int
    {
        $site = Settings::get('site_name');
        $n = 0;
        foreach (Db::all("SELECT u.name, u.email FROM enrolments e JOIN users u ON u.id = e.user_id WHERE e.course_id = ? AND e.status = 'active'", [$course['id']]) as $s) {
            $text = "Hello {$s['name']},\n\nNews from \"{$course['title']}\":\n\n$title\n\n$body\n\nOpen the course: " . abs_url('learn/' . $course['slug']) . "\n\n$site";
            if (Mailer::send($s['email'], "[$site] {$course['title']}: $title", $text)) {
                $n++;
            }
        }
        return $n;
    }
}

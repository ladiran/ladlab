<?php
declare(strict_types=1);

/** Admin → Article submissions: review articles written by registered users, then approve or send back with a note. */
final class AdminArticlesController extends AdminBase
{
    private const STATUSES = ['pending' => 'Waiting for approval', 'rejected' => 'Sent back', 'published' => 'Published', 'draft' => 'Drafts'];

    public function index(): void
    {
        $st = isset(self::STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : 'pending';
        $rows = Db::all(
            'SELECT p.id, p.title, p.status, p.author_name, p.author_email, p.submitted_at, p.reviewed_at, p.published_at, p.updated_at, p.slug, u.created_at AS user_since
             FROM posts p LEFT JOIN users u ON u.id = p.author_id WHERE p.is_user_article = 1 AND p.status = ? ORDER BY COALESCE(p.submitted_at, p.updated_at) DESC LIMIT 200',
            [$st]
        );
        $counts = [];
        foreach (array_keys(self::STATUSES) as $k) {
            $counts[$k] = (int)Db::val('SELECT COUNT(*) FROM posts WHERE is_user_article = 1 AND status = ?', [$k]);
        }
        $this->page('admin/articles', ['title' => 'Article submissions', 'rows' => $rows, 'status' => $st, 'statuses' => self::STATUSES, 'counts' => $counts]);
    }

    private function find(string $id): array
    {
        $a = Db::one('SELECT p.*, u.created_at AS user_since, u.last_login, u.email_verified_at FROM posts p LEFT JOIN users u ON u.id = p.author_id WHERE p.id = ? AND p.is_user_article = 1', [(int)$id]);
        if (!$a) {
            abort(404);
        }
        return $a;
    }

    public function show(string $id): void
    {
        $a = $this->find($id);
        $this->page('admin/article_review', ['title' => 'Review: ' . $a['title'], 'a' => $a]);
    }

    private function uniqueSlug(string $title, int $id): string
    {
        $slug = slugify($title) ?: 'article';
        $base = $slug;
        $n = 2;
        while (Db::val('SELECT 1 FROM posts WHERE slug = ? AND id <> ?', [$slug, $id])) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    public function approve(string $id): void
    {
        $this->verify();
        $a = $this->find($id);
        $slug = $this->uniqueSlug($a['title'], (int)$a['id']);
        Db::update('posts', ['status' => 'published', 'slug' => $slug, 'published_at' => date('Y-m-d H:i:s'), 'reviewed_at' => date('Y-m-d H:i:s'), 'review_note' => ''], 'id = ?', [$a['id']]);
        Audit::log('article_approved', $a['title']);
        $link = abs_url('blog/' . $slug);
        $mailed = true;
        if ($a['author_email'] !== '') {
            $mailed = Mailer::send($a['author_email'], '[' . Settings::get('site_name') . '] Your article is published', "Hello {$a['author_name']},\n\nGood news: your article \"{$a['title']}\" has been approved and is now on the blog:\n$link\n\nYour name and email address are shown with it, as you agreed. Thank you for contributing!\n\n" . Settings::get('site_name'));
        }
        try {
            Seo::indexNow([$link, abs_url('blog')]);
        } catch (Throwable) {
            // search-engine notice is optional
        }
        flash('success', 'Published: ' . $a['title'] . '. ' . ($a['author_email'] === '' ? 'The author has no email on record.' : ($mailed ? 'The author was emailed.' : 'The author could NOT be emailed (see the warning above).')));
        redirect('admin/articles');
    }

    public function reject(string $id): void
    {
        $this->verify();
        $a = $this->find($id);
        $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 500);
        if ($note === '') {
            flash('error', 'Please write a short note telling the author what to change.');
            redirect('admin/articles/' . $a['id']);
        }
        Db::update('posts', ['status' => 'rejected', 'review_note' => $note, 'reviewed_at' => date('Y-m-d H:i:s')], 'id = ?', [$a['id']]);
        Audit::log('article_returned', $a['title']);
        if ($a['author_email'] !== '') {
            Mailer::send($a['author_email'], '[' . Settings::get('site_name') . '] About your article', "Hello {$a['author_name']},\n\nThank you for submitting \"{$a['title']}\". We cannot publish it yet. Here is what we would like you to change:\n\n$note\n\nYou can edit it and submit it again under My articles: " . abs_url('account/articles') . "\n\n" . Settings::get('site_name'));
        }
        flash('success', 'Sent back to the author with your note.');
        redirect('admin/articles');
    }

    public function delete(string $id): void
    {
        $this->verify();
        $a = $this->find($id);
        if ($a['cover_image'] !== '') {
            Upload::remove($a['cover_image']);
        }
        Db::delete('posts', 'id = ?', [$a['id']]);
        Audit::log('article_deleted', $a['title']);
        flash('success', 'Article deleted.');
        redirect('admin/articles');
    }
}

<?php
declare(strict_types=1);

/** "My articles": registered users write articles for the blog. Nothing is published until the administrator approves it. */
final class ArticlesController
{
    private const MAX_PENDING = 3;
    private const MAX_TOTAL = 50;

    private function mine(string $id): array
    {
        Auth::requireLogin();
        $a = Db::one('SELECT * FROM posts WHERE id = ? AND author_id = ? AND is_user_article = 1', [(int)$id, (int)$_SESSION['uid']]);
        if (!$a) {
            abort(404);
        }
        return $a;
    }

    public static function statusLabel(string $s): string
    {
        return ['draft' => 'Draft (only you can see it)', 'pending' => 'Waiting for approval', 'published' => 'Published', 'rejected' => 'Needs changes'][$s] ?? $s;
    }

    public function index(): void
    {
        Auth::requireLogin();
        $rows = Db::all('SELECT id, title, slug, status, review_note, created_at, updated_at, published_at FROM posts WHERE author_id = ? AND is_user_article = 1 ORDER BY id DESC', [(int)$_SESSION['uid']]);
        view('public/my_articles', ['title' => 'My articles', 'meta_desc' => '', 'tab' => 'articles', 'rows' => $rows, 'user' => Auth::user()]);
    }

    public function form(?string $id = null): void
    {
        Auth::requireLogin();
        $a = $id !== null ? $this->mine($id) : null;
        if ($a && !in_array($a['status'], ['draft', 'rejected'], true)) {
            flash('info', $a['status'] === 'pending' ? 'This article is waiting for approval. Withdraw it first if you want to change it.' : 'A published article can no longer be edited here. Contact the site owner for corrections.');
            redirect('account/articles');
        }
        view('public/article_form', ['title' => $a ? 'Edit article' : 'Write an article', 'meta_desc' => '', 'tab' => 'articles', 'a' => $a, 'user' => Auth::user()]);
    }

    public function save(?string $id = null): void
    {
        Auth::requireLogin();
        csrf_check();
        $u = Auth::user();
        $existing = $id !== null ? $this->mine($id) : null;
        $self = $existing ? 'account/articles/' . $existing['id'] : 'account/articles/new';
        if ($existing && !in_array($existing['status'], ['draft', 'rejected'], true)) {
            flash('error', 'This article can no longer be edited.');
            redirect('account/articles');
        }
        if (!rate_ok('article', 20, 3600)) {
            abort(429, 'Too many saves in a short time. Please wait a little and try again.');
        }
        $submit = ($_POST['action'] ?? '') === 'submit';
        $in = fn(string $k) => trim((string)($_POST[$k] ?? ''));
        $title = mb_substr($in('title'), 0, 250);
        $excerpt = mb_substr($in('excerpt'), 0, 400);
        $body = (string)($_POST['body'] ?? '');
        $tags = Entities::normaliseTags(mb_substr($in('tags'), 0, 300));
        $errors = [];
        if (mb_strlen($title) < 5) {
            $errors[] = 'Please give the article a title (at least 5 characters).';
        }
        if (mb_strlen($body) > 40000) {
            $errors[] = 'The article is too long (maximum 40,000 characters).';
        }
        if ($submit) {
            if (!EmailVerify::verified($u)) {
                $errors[] = 'Please verify your email address before submitting: it is published with your article. Open My account and press "Verify my email".';
            }
            if (mb_strlen(trim($body)) < 300) {
                $errors[] = 'An article needs at least 300 characters before it can be submitted.';
            }
            if (empty($_POST['consent'])) {
                $errors[] = 'Please tick the box agreeing that your name and email address are shown with the article.';
            }
            if ((int)Db::val("SELECT COUNT(*) FROM posts WHERE author_id = ? AND is_user_article = 1 AND status = 'pending' AND id <> ?", [$u['id'], $existing['id'] ?? 0]) >= self::MAX_PENDING) {
                $errors[] = 'You already have ' . self::MAX_PENDING . ' articles waiting for approval. Please wait until they are reviewed.';
            }
        }
        if (!$existing && (int)Db::val('SELECT COUNT(*) FROM posts WHERE author_id = ? AND is_user_article = 1', [$u['id']]) >= self::MAX_TOTAL) {
            $errors[] = 'You have reached the limit of articles on this account.';
        }
        $cover = $existing['cover_image'] ?? '';
        $newCover = '';
        if (Upload::has($_FILES['cover'] ?? null)) {
            try {
                $newCover = Upload::image($_FILES['cover'], 1600);
            } catch (RuntimeException $e) {
                $errors[] = 'Cover image: ' . $e->getMessage();
            }
        }
        if ($errors) {
            if ($newCover !== '') {
                Upload::remove($newCover);
            }
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect($self);
        }
        if ($newCover !== '') {
            if ($cover !== '') {
                Upload::remove($cover);
            }
            $cover = $newCover;
        } elseif (!empty($_POST['remove_cover']) && $cover !== '') {
            Upload::remove($cover);
            $cover = '';
        }
        $data = [
            'title' => $title, 'excerpt' => $excerpt, 'body' => $body, 'tags' => $tags, 'cover_image' => $cover,
            'status' => $submit ? 'pending' : 'draft', 'author_id' => (int)$u['id'], 'author_name' => mb_substr((string)$u['name'], 0, 150),
            'author_email' => (string)$u['email'], 'is_user_article' => 1, 'review_note' => '', 'is_visible' => 1, 'is_sample' => 0,
            'consent_at' => $submit ? date('Y-m-d H:i:s') : ($existing['consent_at'] ?? null), 'submitted_at' => $submit ? date('Y-m-d H:i:s') : null,
        ];
        if ($existing) {
            Db::update('posts', $data, 'id = ?', [$existing['id']]);
            $pid = (int)$existing['id'];
        } else {
            $pid = Db::insert('posts', $data + ['slug' => 'draft-' . bin2hex(random_bytes(6)), 'published_at' => null]);
        }
        clear_old();
        if ($submit) {
            Audit::log('article_submitted', $title);
            Community::notifyOwner('New article to review', "{$u['name']} ({$u['email']}) submitted an article for the blog:\n\n$title\n\nReview it: " . abs_url('admin/articles/' . $pid));
            Mailer::send($u['email'], '[' . Settings::get('site_name') . '] We received your article', "Hello {$u['name']},\n\nThank you for submitting \"$title\". It is now waiting for review. We will email you when it is published, or if we need changes.\n\nYou can follow its progress under My articles: " . abs_url('account/articles') . "\n\n" . Settings::get('site_name'));
            flash('success', 'Thank you! Your article was submitted. It will appear on the blog after the administrator approves it, and you will be told by email.');
            redirect('account/articles');
        }
        flash('success', 'Draft saved. Submit it for approval when it is ready.');
        redirect('account/articles/' . $pid);
    }

    public function withdraw(string $id): void
    {
        $a = $this->mine($id);
        csrf_check();
        if ($a['status'] === 'pending') {
            Db::update('posts', ['status' => 'draft', 'submitted_at' => null], 'id = ?', [$a['id']]);
            flash('success', 'The article was taken back. It is a draft again, and you can edit and resubmit it.');
        }
        redirect('account/articles');
    }

    public function delete(string $id): void
    {
        $a = $this->mine($id);
        csrf_check();
        if ($a['status'] !== 'published') {
            if ($a['cover_image'] !== '') {
                Upload::remove($a['cover_image']);
            }
            Db::delete('posts', 'id = ?', [$a['id']]);
            flash('success', 'The article was deleted.');
        } else {
            flash('error', 'A published article can only be removed by the site owner. Please contact us.');
        }
        redirect('account/articles');
    }

    /** Preview for the editor: the same safe rendering the public page uses. */
    public function preview(): void
    {
        Auth::requireLogin();
        csrf_check();
        json_out(['html' => md_ugc((string)($_POST['text'] ?? ''))]);
    }
}

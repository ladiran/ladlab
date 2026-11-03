<?php
declare(strict_types=1);

final class AdminMediaController extends AdminBase
{
    public function index(): void
    {
        $this->page('admin/media', ['title' => 'Media library', 'files' => Db::all('SELECT * FROM media ORDER BY id DESC LIMIT 500')]);
    }

    public function upload(): void
    {
        $this->verify();
        $f = $_FILES['file'] ?? null;
        if (!Upload::has($f)) {
            flash('error', 'Choose a file first.');
            redirect('admin/media');
        }
        try {
            $mime = ($f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name'])) ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
            $path = $mime === 'application/pdf' ? Upload::pdf($f) : Upload::image($f);
            flash('success', 'Uploaded. Use this in a blog post: ' . ($mime === 'application/pdf' ? "[Download]({$path})" : "![description]({$path})"));
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect('admin/media');
    }

    public function delete(string $id): void
    {
        $this->verify();
        $row = Db::one('SELECT path FROM media WHERE id = ?', [(int)$id]);
        if ($row) {
            Upload::remove($row['path']);
            Audit::log('media_delete', $row['path']);
            flash('success', 'File deleted. If it was used in a post or setting, that item will now show a broken image.');
        }
        redirect('admin/media');
    }
}

<?php
declare(strict_types=1);

final class AdminCvController extends AdminBase
{
    public function index(): void
    {
        $this->page('admin/cv', [
            'title' => 'CV',
            'files' => Db::all('SELECT * FROM cv_files ORDER BY id DESC'),
        ]);
    }

    public function upload(): void
    {
        $this->verify();
        try {
            $path = Upload::pdf($_FILES['cv'] ?? []);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('admin/cv');
        }
        Db::update('cv_files', ['is_active' => 0], '1 = 1');
        Db::insert('cv_files', ['path' => $path, 'original_name' => mb_substr(basename((string)$_FILES['cv']['name']), 0, 190), 'is_active' => 1]);
        Audit::log('cv_upload', $_FILES['cv']['name']);
        flash('success', 'Your new CV is now live. The old version is kept in the history below.');
        redirect('admin/cv');
    }

    public function activate(string $id): void
    {
        $this->verify();
        $row = Db::one('SELECT id FROM cv_files WHERE id = ?', [(int)$id]);
        if ($row) {
            Db::update('cv_files', ['is_active' => 0], '1 = 1');
            Db::update('cv_files', ['is_active' => 1], 'id = ?', [$row['id']]);
            flash('success', 'That version is now the public CV.');
        }
        redirect('admin/cv');
    }

    public function delete(string $id): void
    {
        $this->verify();
        $row = Db::one('SELECT * FROM cv_files WHERE id = ?', [(int)$id]);
        if ($row) {
            Upload::remove($row['path']);
            Db::delete('cv_files', 'id = ?', [$row['id']]);
            if ((int)$row['is_active'] === 1) {
                $latest = Db::one('SELECT id FROM cv_files ORDER BY id DESC LIMIT 1');
                if ($latest) {
                    Db::update('cv_files', ['is_active' => 1], 'id = ?', [$latest['id']]);
                }
            }
            flash('success', 'CV file deleted.');
        }
        redirect('admin/cv');
    }
}

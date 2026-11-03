<?php
declare(strict_types=1);

/** Validated file uploads. Files get random names; images are re-encoded (strips metadata and any hidden payload). */
final class Upload
{
    private const IMAGES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    private const MAX_IMAGE = 8 * 1024 * 1024;
    private const MAX_PDF = 15 * 1024 * 1024;

    public static function has(?array $file): bool
    {
        return $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private static function check(array $file, int $max): void
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('That file is too large for the server. Limit is ' . ini_get('upload_max_filesize') . '.');
        }
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('The upload failed. Please try again.');
        }
        if ($file['size'] > $max) {
            throw new RuntimeException('That file is larger than ' . round($max / 1048576) . ' MB.');
        }
    }

    private static function dir(string $sub): string
    {
        $dir = ROOT . '/public/uploads/' . $sub;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create the uploads folder.');
        }
        return $dir;
    }

    /** @return string relative path such as uploads/images/ab12.jpg */
    public static function image(array $file, int $maxWidth = 1600): string
    {
        self::check($file, self::MAX_IMAGE);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::IMAGES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Please upload a JPG, PNG, WebP or GIF image.');
        }
        $ext = self::IMAGES[$mime];
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = self::dir('images') . '/' . $name;

        $done = false;
        if (extension_loaded('gd') && $mime !== 'image/gif') {
            $img = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
            if ($img !== false) {
                $w = imagesx($img);
                $h = imagesy($img);
                if ($w > $maxWidth) {
                    $img = imagescale($img, $maxWidth, (int)round($h * $maxWidth / $w)) ?: $img;
                }
                if ($ext === 'png' || $ext === 'webp') {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                }
                $done = match ($ext) {
                    'jpg' => imagejpeg($img, $dest, 85),
                    'png' => imagepng($img, $dest, 6),
                    'webp' => function_exists('imagewebp') ? imagewebp($img, $dest, 85) : false,
                };
            }
        }
        if (!$done && !move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not save the image.');
        }
        $rel = 'uploads/images/' . $name;
        self::record($rel, (string)$file['name'], $mime, (int)filesize($dest), 'image');
        return $rel;
    }

    public static function pdf(array $file): string
    {
        self::check($file, self::MAX_PDF);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $head = (string)file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($mime !== 'application/pdf' || $head !== '%PDF-') {
            throw new RuntimeException('Please upload a PDF file.');
        }
        $name = bin2hex(random_bytes(8)) . '.pdf';
        $dest = self::dir('docs') . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not save the PDF.');
        }
        $rel = 'uploads/docs/' . $name;
        self::record($rel, (string)$file['name'], $mime, (int)filesize($dest), 'pdf');
        return $rel;
    }

    private static function record(string $rel, string $original, string $mime, int $size, string $kind): void
    {
        Db::insert('media', [
            'path' => $rel,
            'original_name' => mb_substr(basename($original), 0, 190),
            'mime' => $mime,
            'size' => $size,
            'kind' => $kind,
        ]);
    }

    /**
     * Private files (payment receipts): stored outside the public web root and served only through an admin-checked route.
     * Accepts JPG/PNG/WebP (re-encoded) or PDF up to 6 MB. Returns a path relative to the project root.
     */
    public static function receipt(array $file): string
    {
        self::check($file, 6 * 1024 * 1024);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $dir = ROOT . '/storage/private/receipts';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create the receipts folder.');
        }
        $base = bin2hex(random_bytes(10));
        if ($mime === 'application/pdf' && (string)file_get_contents($file['tmp_name'], false, null, 0, 5) === '%PDF-') {
            $name = $base . '.pdf';
            if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
                throw new RuntimeException('Could not save the receipt.');
            }
            return 'storage/private/receipts/' . $name;
        }
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) && @getimagesize($file['tmp_name']) !== false && extension_loaded('gd')) {
            $img = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
            if ($img !== false) {
                $ext = $mime === 'image/png' ? 'png' : 'jpg';
                $ok = $ext === 'png' ? imagepng($img, "$dir/$base.png", 6) : imagejpeg($img, "$dir/$base.jpg", 88);
                if ($ok) {
                    return "storage/private/receipts/$base.$ext";
                }
            }
        }
        throw new RuntimeException('Please upload a photo (JPG, PNG, WebP) or a PDF of your payment receipt.');
    }

    public static function removePrivate(?string $rel): void
    {
        if (!$rel || !str_starts_with($rel, 'storage/private/') || str_contains($rel, '..')) {
            return;
        }
        $full = realpath(ROOT . '/' . $rel);
        $base = realpath(ROOT . '/storage/private');
        if ($full && $base && str_starts_with($full, $base . DIRECTORY_SEPARATOR) && is_file($full)) {
            @unlink($full);
        }
    }

    /** Lesson video (MP4 or WebM) stored privately; only streamed to enrolled students. Returns a path relative to the project root. */
    public static function video(array $file): string
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('That video is larger than the server allows (' . ini_get('upload_max_filesize') . '). Upload it to YouTube/Vimeo and paste the link, or copy the file into storage/private/videos and type its name.');
        }
        self::check($file, 2048 * 1024 * 1024);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = ['video/mp4' => 'mp4', 'video/webm' => 'webm'][$mime] ?? null;
        if ($ext === null) {
            throw new RuntimeException('Please upload an MP4 or WebM video.');
        }
        $dir = ROOT . '/storage/private/videos';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create the videos folder.');
        }
        $name = bin2hex(random_bytes(10)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            throw new RuntimeException('Could not save the video.');
        }
        return 'storage/private/videos/' . $name;
    }

    /** Downloadable lesson PDF (private). */
    public static function coursePdf(array $file): string
    {
        self::check($file, self::MAX_PDF);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($mime !== 'application/pdf' || (string)file_get_contents($file['tmp_name'], false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('Please upload a PDF file.');
        }
        $dir = ROOT . '/storage/private/course-files';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create the folder.');
        }
        $name = bin2hex(random_bytes(10)) . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            throw new RuntimeException('Could not save the PDF.');
        }
        return 'storage/private/course-files/' . $name;
    }
    /** Deletes the file and its media record. Only touches files inside public/uploads. */
    public static function remove(?string $rel): void
    {
        if (!$rel || !str_starts_with($rel, 'uploads/') || str_contains($rel, '..')) {
            return;
        }
        $full = realpath(ROOT . '/public/' . $rel);
        $base = realpath(ROOT . '/public/uploads');
        if ($full && $base && str_starts_with($full, $base . DIRECTORY_SEPARATOR) && is_file($full)) {
            @unlink($full);
        }
        Db::delete('media', 'path = ?', [$rel]);
    }
}

<?php
declare(strict_types=1);

/** One controller drives list / add / edit / delete for every simple content type described in Entities. */
final class AdminCrudController extends AdminBase
{
    public function index(string $entity): void
    {
        $def = Entities::get($entity);
        $rows = Db::all('SELECT * FROM `' . $def['table'] . '` ORDER BY ' . $def['order'] . ' LIMIT 500');
        $this->page('admin/crud_list', ['title' => $def['plural'], 'def' => $def, 'rows' => $rows]);
    }

    public function form(string $entity, ?string $id = null): void
    {
        $def = Entities::get($entity);
        $row = null;
        if ($id !== null) {
            $row = Db::one('SELECT * FROM `' . $def['table'] . '` WHERE id = ?', [(int)$id]);
            if (!$row) {
                abort(404);
            }
        } elseif (($def['can_create'] ?? true) === false) {
            abort(404);
        }
        $this->page('admin/crud_form', [
            'title' => ($row ? 'Edit ' : 'Add ') . strtolower($def['label']),
            'def' => $def,
            'row' => $row,
        ]);
    }

    public function store(string $entity): void
    {
        $this->verify();
        $def = Entities::get($entity);
        if (($def['can_create'] ?? true) === false) {
            abort(404);
        }
        [$data, $files, $removals] = $this->collect($def, null);
        if (empty($def['no_sample'])) {
            $data['is_sample'] = 0;
        }
        $id = Db::insert($def['table'], array_merge($data, $files));
        Audit::log($entity . '_create', (string)$id);
        clear_old();
        flash('success', $def['label'] . ' added.');
        if ($entity === 'posts' && ($data['status'] ?? '') === 'published') {
            try {
                $slug = Db::val('SELECT slug FROM posts WHERE id = ?', [$id]);
                Seo::indexNow([abs_url('blog/' . $slug), abs_url('blog')]); // tell Bing and others (only on a live site)
            } catch (Throwable) {
                // never block saving
            }
        }
        redirect('admin/' . $entity);
    }

    public function update(string $entity, string $id): void
    {
        $this->verify();
        $def = Entities::get($entity);
        $row = Db::one('SELECT * FROM `' . $def['table'] . '` WHERE id = ?', [(int)$id]);
        if (!$row) {
            abort(404);
        }
        [$data, $files, $removals] = $this->collect($def, $row);
        if (!empty($row['is_sample'])) {
            $data['is_sample'] = 0; // editing a placeholder turns it into your real content
        }
        // Remove replaced or deleted files after the new data is validated.
        foreach ($removals as $field) {
            if (!isset($files[$field])) {
                $data[$field] = '';
            }
        }
        Db::update($def['table'], array_merge($data, $files), 'id = ?', [$row['id']]);
        foreach (array_merge(array_keys($files), $removals) as $field) {
            if (!empty($row[$field]) && ($files[$field] ?? '') !== $row[$field]) {
                Upload::remove($row[$field]);
            }
        }
        Audit::log($entity . '_update', (string)$row['id']);
        clear_old();
        flash('success', 'Saved.');
        redirect('admin/' . $entity);
    }

    public function destroy(string $entity, string $id): void
    {
        $this->verify();
        $def = Entities::get($entity);
        if (($def['can_delete'] ?? true) === false) {
            abort(404);
        }
        $row = Db::one('SELECT * FROM `' . $def['table'] . '` WHERE id = ?', [(int)$id]);
        if ($row) {
            foreach ($def['fields'] as [$name, $type]) {
                if (in_array($type, ['image', 'pdf'], true) && !empty($row[$name])) {
                    Upload::remove($row[$name]);
                }
            }
            Db::delete($def['table'], 'id = ?', [$row['id']]);
            Audit::log($entity . '_delete', (string)$row['id']);
            flash('success', $def['label'] . ' deleted.');
        }
        redirect('admin/' . $entity);
    }

    /**
     * Validates the POST data against the entity definition.
     * Returns [column values, newly uploaded files (field => path), fields flagged for removal].
     * On any error it flashes the messages and redirects back, keeping what was typed.
     */
    private function collect(array $def, ?array $existing): array
    {
        $data = [];
        $files = [];
        $removals = [];
        $errors = [];

        foreach ($def['fields'] as [$name, $type, $label, $o]) {
            $o = $o ?? [];
            $raw = $_POST[$name] ?? '';
            $raw = is_string($raw) ? trim($raw) : '';

            switch ($type) {
                case 'checkbox':
                    $data[$name] = isset($_POST[$name]) ? 1 : 0;
                    break;

                case 'image':
                case 'pdf':
                    if (!empty($_POST['remove_' . $name])) {
                        $removals[] = $name;
                    }
                    if (Upload::has($_FILES[$name] ?? null)) {
                        try {
                            $files[$name] = $type === 'image' ? Upload::image($_FILES[$name]) : Upload::pdf($_FILES[$name]);
                        } catch (RuntimeException $e) {
                            $errors[] = "$label: " . $e->getMessage();
                        }
                    }
                    break;

                case 'number':
                    if ($raw === '') {
                        if (!empty($o['required'])) {
                            $errors[] = "$label is required.";
                        }
                        $data[$name] = isset($o['default']) ? (int)$o['default'] : null;
                    } elseif (!preg_match('/^-?\d+$/', $raw)) {
                        $errors[] = "$label must be a whole number.";
                    } else {
                        $n = (int)$raw;
                        if ((isset($o['min']) && $n < $o['min']) || (isset($o['max']) && $n > $o['max'])) {
                            $errors[] = "$label is out of range.";
                        }
                        $data[$name] = $n;
                    }
                    break;

                case 'select':
                    $data[$name] = array_key_exists($raw, $o['options'] ?? []) ? $raw : ($o['default'] ?? array_key_first($o['options']));
                    break;

                case 'date':
                    if ($raw === '') {
                        $data[$name] = null;
                    } elseif (!($d = DateTime::createFromFormat('Y-m-d', $raw)) || $d->format('Y-m-d') !== $raw) {
                        $errors[] = "$label is not a valid date.";
                    } else {
                        $data[$name] = $raw;
                    }
                    break;

                case 'datetime':
                    if ($raw === '') {
                        $data[$name] = null;
                    } else {
                        $ts = strtotime(str_replace('T', ' ', $raw));
                        if ($ts === false) {
                            $errors[] = "$label is not a valid date and time.";
                        } else {
                            $data[$name] = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    break;

                case 'url':
                    if ($raw !== '' && !preg_match('#^https?://\S+$#i', $raw)) {
                        $errors[] = "$label must start with http:// or https://";
                    }
                    $data[$name] = $raw;
                    break;

                default: // text, textarea, markdown
                    $max = $o['max'] ?? (in_array($type, ['textarea', 'markdown'], true) ? 60000 : 255);
                    if (mb_strlen($raw) > $max) {
                        $errors[] = "$label is too long (maximum $max characters).";
                    }
                    $data[$name] = $raw;
            }

            if (!empty($o['required']) && ($data[$name] ?? '') === '') {
                $errors[] = "$label is required.";
            }
        }

        $this->prepare($def['slug'], $data, $existing, $errors);

        if ($errors) {
            foreach ($files as $path) {
                Upload::remove($path);
            }
            foreach (array_unique($errors) as $err) {
                flash('error', $err);
            }
            remember_input($_POST);
            redirect('admin/' . $def['slug'] . ($existing ? '/' . $existing['id'] : '/new'));
        }
        return [$data, $files, $removals];
    }

    /** Entity-specific tidy-ups and rules. */
    private function prepare(string $slug, array &$data, ?array $existing, array &$errors): void
    {
        if ($slug === 'publications') {
            $data['doi'] = Importer::normaliseDoi($data['doi'] ?? '');
            $data['tags'] = Entities::normaliseTags($data['tags'] ?? '');
        }
        if ($slug === 'coupons') {
            $data['code'] = strtoupper($data['code']);
            if (!preg_match('/^[A-Z0-9_-]{3,40}$/', $data['code'])) {
                $errors[] = 'The code must be 3–40 letters, numbers, dashes or underscores.';
            } elseif (Db::val('SELECT 1 FROM coupons WHERE code = ? AND id <> ?', [$data['code'], $existing['id'] ?? 0])) {
                $errors[] = 'That code already exists.';
            }
            if (($data['type'] ?? '') === 'percent' && (int)($data['value'] ?? 0) > 100) {
                $errors[] = 'A percentage discount cannot be more than 100.';
            }
        }
        if ($slug === 'toolbox' && (!$existing || $existing['url'] !== ($data['url'] ?? ''))) {
            $data['link_status'] = 'unknown';
            $data['link_checked_at'] = null;
        }
        if ($slug === 'resources') {
            if (empty($data['rights_confirmed'])) {
                $errors[] = 'Please tick the box confirming you have the right to share this item. If you are not sure, do not publish it.';
            }
            if (in_array($data['license'] ?? '', ['cc-by', 'cc-by-sa', 'cc-by-nc', 'cc-by-nc-sa', 'cc-by-nd', 'permission'], true) && trim(($data['author'] ?? '')) === '') {
                $errors[] = 'Please enter the author or rights holder: this licence requires credit.';
            }
            // a visible entry needs a file or a link (a hidden draft may have neither yet)
            $keepsPdf = !empty($existing['pdf_file']) && empty($_POST['remove_pdf_file']);
            if (!empty($data['is_visible']) && !Upload::has($_FILES['pdf_file'] ?? null) && !$keepsPdf && ($data['url'] ?? '') === '') {
                $errors[] = 'Upload a PDF or give a link, or untick "Show on the website".';
            }
        }
        if ($slug === 'categories') {
            $cs = slugify($data['slug'] !== '' ? $data['slug'] : ($data['name'] ?? ''));
            $base = $cs;
            $i = 2;
            while (Db::val('SELECT 1 FROM shop_categories WHERE slug = ? AND id <> ?', [$cs, $existing['id'] ?? 0])) {
                $cs = $base . '-' . $i++;
            }
            $data['slug'] = $cs;
        }
        if ($slug === 'links' && !preg_match('~^(https?://\S+|/\S*)$~i', $data['url'] ?? '')) {
            $errors[] = 'The address must start with http://, https:// or / (a page of this site).';
        }
        if ($slug === 'posts') {
            if (($data['author_email'] ?? '') === '-') {
                $data['author_email'] = '';
                $data['author_name'] = $data['author_name'] !== '' ? $data['author_name'] : setting('owner_name');
            }
            if (!$existing) {
                $data['author_id'] = (int)($_SESSION['uid'] ?? 0) ?: null;
            }
            $data['tags'] = Entities::normaliseTags($data['tags'] ?? '');
            $slug = slugify($data['slug'] !== '' ? $data['slug'] : ($data['title'] ?? ''));
            $base = $slug;
            $i = 2;
            while (Db::val('SELECT 1 FROM posts WHERE slug = ? AND id <> ?', [$slug, $existing['id'] ?? 0])) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
            if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
        }
    }
}

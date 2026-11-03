<?php
declare(strict_types=1);

final class AdminSettingsController extends AdminBase
{
    private function group(string $key): array
    {
        $groups = SettingsDef::groups();
        if (!isset($groups[$key])) {
            abort(404);
        }
        return $groups[$key];
    }

    public function edit(string $group): void
    {
        $def = $this->group($group);
        $this->page('admin/settings', ['title' => $def['label'], 'group' => $group, 'def' => $def]);
    }

    public function save(string $group): void
    {
        $this->verify();
        $def = $this->group($group);
        $errors = [];
        $values = [];
        $uploads = [];
        $removals = [];

        foreach ($def['fields'] as [$key, $type, $label]) {
            if ($type === 'checkbox') {
                $values[$key] = isset($_POST[$key]) ? '1' : '0';
                continue;
            }
            if ($type === 'image') {
                if (!empty($_POST['remove_' . $key])) {
                    $removals[] = $key;
                }
                if (Upload::has($_FILES[$key] ?? null)) {
                    try {
                        $uploads[$key] = Upload::image($_FILES[$key], $key === 'hero_image' || $key === 'og_image' ? 2000 : 1200);
                    } catch (RuntimeException $e) {
                        $errors[] = "$label: " . $e->getMessage();
                    }
                }
                continue;
            }
            $v = trim((string)($_POST[$key] ?? ''));
            $limit = in_array($type, ['textarea', 'markdown'], true) ? 30000 : 500;
            if (mb_strlen($v) > $limit) {
                $errors[] = "$label is too long.";
                continue;
            }
            if ($type === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "$label is not a valid email address.";
                continue;
            }
            if (str_starts_with($key, 'social_') && $v !== '' && !preg_match('#^https?://\S+$#i', $v)) {
                $errors[] = "$label must start with https://";
                continue;
            }
            if ($key === 'whatsapp' && $v !== '' && !preg_match('/^\d{8,16}$/', $v)) {
                $errors[] = 'WhatsApp number must be digits only, in international format (e.g. 2348012345678).';
                continue;
            }
            if ($key === 'order_hold_hours' && $v !== '' && !ctype_digit($v)) {
                $errors[] = 'Hold time must be a whole number of hours.';
                continue;
            }
            $values[$key] = $v;
        }

        if ($errors) {
            foreach ($uploads as $path) {
                Upload::remove($path); // do not keep files from a rejected submission
            }
            foreach ($errors as $e) {
                flash('error', $e);
            }
            remember_input($_POST);
            redirect('admin/settings/' . $group);
        }

        foreach ($values as $k => $v) {
            Settings::set($k, $v);
        }
        foreach ($removals as $k) {
            if (!isset($uploads[$k])) {
                Upload::remove(Settings::get($k));
                Settings::set($k, '');
            }
        }
        foreach ($uploads as $k => $path) {
            $old = Settings::get($k);
            Settings::set($k, $path);
            if ($old && $old !== $path) {
                Upload::remove($old);
            }
        }
        clear_old();
        Audit::log('settings_saved', $group);
        flash('success', 'Saved.');
        redirect('admin/settings/' . $group);
    }
}

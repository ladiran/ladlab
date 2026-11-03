<?php
declare(strict_types=1);

/** Public "Free tools" pages. */
final class ToolboxController
{
    public function index(): void
    {
        $f = [
            'q' => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60),
            'kind' => isset(Toolbox::KINDS[$_GET['kind'] ?? '']) ? $_GET['kind'] : '',
            'audience' => isset(Toolbox::AUDIENCES[$_GET['audience'] ?? '']) && ($_GET['audience'] ?? '') !== 'all' ? $_GET['audience'] : '',
            'subject' => mb_substr(trim((string)($_GET['subject'] ?? '')), 0, 60),
        ];
        $rows = Toolbox::visible($f);
        $filtered = $f['q'] !== '' || $f['kind'] !== '' || $f['audience'] !== '' || $f['subject'] !== '';
        $groups = [];
        if (!$filtered) {
            foreach ($rows as $r) {
                $groups[$r['kind']][] = $r;
            }
            $groups = array_replace(array_intersect_key(Toolbox::KINDS, $groups), $groups);
        }
        form_started('toolbox');
        view('public/toolbox', [
            'title' => 'Free tools for students and researchers',
            'meta_desc' => 'Free video lessons, open textbooks, software, data and tutorials for physics, mathematics and meteorology: WRF, Quantum ESPRESSO, Python and more.',
            'f' => $f, 'rows' => $rows, 'groups' => $groups, 'filtered' => $filtered, 'subjects' => Toolbox::subjects(),
        ]);
    }

    public function show(string $id): void
    {
        $t = Db::one("SELECT * FROM toolbox_links WHERE id = ? AND is_visible = 1 AND link_status <> 'broken'", [(int)$id]);
        if (!$t) {
            abort(404);
        }
        form_started('toolbox');
        view('public/toolbox_item', ['title' => $t['title'], 'meta_desc' => $t['description'], 't' => $t]);
    }

    /** Counts the click, then sends the visitor to the resource. Only addresses stored by the administrator can be reached. */
    public function go(string $id): void
    {
        $t = Db::one('SELECT id, url FROM toolbox_links WHERE id = ? AND is_visible = 1', [(int)$id]);
        if (!$t || !Toolbox::validUrl($t['url'])) {
            abort(404);
        }
        Db::run('UPDATE toolbox_links SET clicks = clicks + 1 WHERE id = ?', [$t['id']]);
        header('Referrer-Policy: no-referrer');
        redirect($t['url']);
    }

    /** "Suggest a resource or report a broken link": lands in Admin → Enquiries. */
    public function suggest(): void
    {
        csrf_check();
        $back = (string)($_POST['back'] ?? '');
        $back = preg_match('#^toolbox(/\d+)?$#', $back) ? $back : 'toolbox';
        if (!form_human('toolbox') || !rate_ok('toolbox', 5, 3600)) {
            flash('error', 'Please wait a moment and try again.');
            redirect($back);
        }
        $url = trim((string)($_POST['url'] ?? ''));
        $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 1500);
        $email = trim((string)($_POST['email'] ?? ''));
        $kind = ($_POST['kind'] ?? '') === 'broken' ? 'Broken or unsuitable link' : 'Suggested resource';
        if ($url !== '' && !Toolbox::validUrl($url)) {
            flash('error', 'Please enter the link starting with https://');
            redirect($back);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'That email address does not look right (you can also leave it empty).');
            redirect($back);
        }
        if ($url === '' && mb_strlen($note) < 10) {
            flash('error', 'Please add the link or a short description.');
            redirect($back);
        }
        Db::insert('enquiries', [
            'name' => 'Free tools visitor', 'email' => $email !== '' ? $email : 'noreply@invalid.local', 'type' => 'general', 'subject' => mb_substr('Free tools: ' . $kind, 0, 200),
            'message' => "$kind\n\nLink: $url\n\n$note", 'ip' => client_ip(),
        ]);
        Community::notifyOwner('Free tools: ' . strtolower($kind), "$kind\nLink: $url\n\n$note");
        flash('success', 'Thank you! We will check it before it appears.');
        redirect($back);
    }
}

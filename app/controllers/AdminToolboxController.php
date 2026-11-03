<?php
declare(strict_types=1);

/** Admin → Free tools: adding many links at once and checking that the links still work. */
final class AdminToolboxController extends AdminBase
{
    public function bulkForm(): void
    {
        $this->page('admin/toolbox_bulk', ['title' => 'Add many links at once']);
    }

    public function bulkSave(): void
    {
        $this->verify();
        $kind = isset(Toolbox::KINDS[$_POST['kind'] ?? '']) ? $_POST['kind'] : 'tutorial';
        $aud = isset(Toolbox::AUDIENCES[$_POST['audience'] ?? '']) ? $_POST['audience'] : 'all';
        $lic = isset(Toolbox::LICENSES[$_POST['license'] ?? '']) ? $_POST['license'] : 'free';
        $subject = mb_substr(trim((string)($_POST['subject'] ?? '')), 0, 60) ?: 'General';
        $added = 0;
        $skipped = [];
        $order = (int)Db::val('SELECT COALESCE(MAX(sort_order), 0) FROM toolbox_links');
        foreach (nl_lines((string)($_POST['lines'] ?? '')) as $line) {
            $p = array_map('trim', explode('|', $line));
            $url = $p[0] ?? '';
            if (!Toolbox::validUrl($url)) {
                $skipped[] = mb_substr($line, 0, 60) . ' (not a link)';
                continue;
            }
            if (Db::val('SELECT 1 FROM toolbox_links WHERE url = ?', [$url])) {
                $skipped[] = mb_substr($url, 0, 60) . ' (already listed)';
                continue;
            }
            $k = isset($p[3]) && isset(Toolbox::KINDS[$p[3]]) ? $p[3] : $kind;
            $title = ($p[1] ?? '') !== '' ? $p[1] : Toolbox::host($url);
            Db::insert('toolbox_links', [
                'title' => mb_substr($title, 0, 200), 'url' => $url, 'kind' => $k, 'subject' => mb_substr(($p[2] ?? '') !== '' ? $p[2] : $subject, 0, 60),
                'audience' => $aud, 'provider' => Toolbox::host($url), 'license' => $lic, 'sort_order' => ($order += 10),
            ]);
            $added++;
        }
        Audit::log('toolbox_bulk', "$added added");
        flash($added ? 'success' : 'error', "$added link" . ($added === 1 ? '' : 's') . ' added.' . ($skipped ? ' Skipped: ' . implode('; ', array_slice($skipped, 0, 5)) . (count($skipped) > 5 ? '…' : '') : ''));
        redirect('admin/toolbox');
    }

    /** Checks up to 40 links per press (oldest check first), so a slow site cannot time the page out. Press again to continue. */
    public function check(): void
    {
        $this->verify();
        @set_time_limit(280);
        $rows = Db::all('SELECT id, url FROM toolbox_links ORDER BY link_checked_at IS NOT NULL, link_checked_at, id LIMIT 40');
        $n = ['ok' => 0, 'blocked' => 0, 'broken' => 0, 'unknown' => 0];
        foreach ($rows as $r) {
            $s = Toolbox::check($r['url']);
            $n[$s]++;
            Db::update('toolbox_links', ['link_status' => $s, 'link_checked_at' => date('Y-m-d H:i:s')], 'id = ?', [$r['id']]);
        }
        $left = (int)Db::val('SELECT COUNT(*) FROM toolbox_links WHERE link_checked_at IS NULL OR link_checked_at < (NOW() - INTERVAL 1 HOUR)');
        flash($n['broken'] ? 'info' : 'success', sprintf('Checked %d links: %d working, %d broken (hidden from visitors), %d refuse automatic checks (open them by hand), %d not checkable.%s', count($rows), $n['ok'], $n['broken'], $n['blocked'], $n['unknown'], $left > 0 ? " $left still to check: press the button again." : ''));
        redirect('admin/toolbox');
    }
}

<?php
declare(strict_types=1);

final class AdminInboxController extends AdminBase
{
    private const STATUSES = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'];

    public function enquiries(): void
    {
        $status = isset(self::STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
        $rows = $status
            ? Db::all('SELECT * FROM enquiries WHERE status = ? ORDER BY id DESC LIMIT 300', [$status])
            : Db::all("SELECT * FROM enquiries WHERE status <> 'archived' ORDER BY id DESC LIMIT 300");
        $this->page('admin/enquiries', ['title' => 'Enquiries', 'rows' => $rows, 'status' => $status, 'statuses' => self::STATUSES]);
    }

    public function enquiry(string $id): void
    {
        $row = Db::one('SELECT * FROM enquiries WHERE id = ?', [(int)$id]);
        if (!$row) {
            abort(404);
        }
        if ($row['status'] === 'new') {
            Db::update('enquiries', ['status' => 'read'], 'id = ?', [$row['id']]);
            $row['status'] = 'read';
        }
        $this->page('admin/enquiry', ['title' => 'Enquiry from ' . $row['name'], 'row' => $row, 'statuses' => self::STATUSES, 'types' => SiteController::ENQUIRY_TYPES]);
    }

    public function enquiryStatus(string $id): void
    {
        $this->verify();
        $status = (string)($_POST['status'] ?? '');
        if (isset(self::STATUSES[$status])) {
            Db::update('enquiries', ['status' => $status], 'id = ?', [(int)$id]);
            flash('success', 'Marked as ' . strtolower(self::STATUSES[$status]) . '.');
        }
        redirect('admin/enquiries/' . (int)$id);
    }

    public function enquiryDelete(string $id): void
    {
        $this->verify();
        Db::delete('enquiries', 'id = ?', [(int)$id]);
        Audit::log('enquiry_delete', (string)(int)$id);
        flash('success', 'Enquiry deleted.');
        redirect('admin/enquiries');
    }

    public function subscribers(): void
    {
        $this->page('admin/subscribers', [
            'title' => 'Subscribers',
            'rows' => Db::all('SELECT * FROM subscribers ORDER BY id DESC LIMIT 1000'),
            'confirmed' => (int)Db::val('SELECT COUNT(*) FROM subscribers WHERE confirmed = 1 AND unsubscribed_at IS NULL'),
        ]);
    }

    public function subscribersExport(): void
    {
        $rows = Db::all('SELECT email, name, source, confirmed_at FROM subscribers WHERE confirmed = 1 AND unsubscribed_at IS NULL ORDER BY id');
        Audit::log('subscribers_export', count($rows) . ' rows');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['email', 'name', 'source', 'confirmed_at']);
        foreach ($rows as $r) {
            // Guard against spreadsheet formula injection from user-supplied text.
            foreach ($r as $k => $v) {
                if (is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) {
                    $r[$k] = "'" . $v;
                }
            }
            fputcsv($out, $r);
        }
        fclose($out);
    }

    public function subscriberDelete(string $id): void
    {
        $this->verify();
        Db::delete('subscribers', 'id = ?', [(int)$id]);
        flash('success', 'Subscriber removed.');
        redirect('admin/subscribers');
    }
}

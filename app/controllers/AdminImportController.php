<?php
declare(strict_types=1);

final class AdminImportController extends AdminBase
{
    public function form(): void
    {
        $this->page('admin/import', ['title' => 'Import publications']);
    }

    public function run(): void
    {
        $this->verify();
        $mode = (string)($_POST['mode'] ?? '');
        try {
            switch ($mode) {
                case 'doi':
                    // Look up one DOI and open the normal form, pre-filled, so you can check it before saving.
                    $row = Importer::fromDoi((string)($_POST['doi'] ?? ''));
                    if ($row['doi'] && Db::val('SELECT 1 FROM publications WHERE is_sample = 0 AND doi = ? LIMIT 1', [$row['doi']])) {
                        flash('info', 'This DOI is already in your list.');
                        redirect('admin/publications');
                    }
                    $_SESSION['old'] = array_map(fn($v) => $v === null ? '' : (string)$v, $row) + ['is_visible' => '1'];
                    flash('success', 'Details found. Please check them and press Save.');
                    redirect('admin/publications/new');

                case 'orcid':
                    $rows = Importer::fromOrcid((string)($_POST['orcid'] ?? ''));
                    break;

                case 'bibtex':
                    $text = trim((string)($_POST['bibtex'] ?? ''));
                    if (Upload::has($_FILES['bibfile'] ?? null) && ($_FILES['bibfile']['error'] ?? 1) === UPLOAD_ERR_OK && $_FILES['bibfile']['size'] < 2_000_000) {
                        $text = (string)file_get_contents($_FILES['bibfile']['tmp_name']);
                    }
                    if ($text === '') {
                        throw new RuntimeException('Paste your BibTeX entries or choose a .bib file.');
                    }
                    $rows = Importer::fromBibtex($text);
                    break;

                case 'csv':
                    $f = $_FILES['csvfile'] ?? null;
                    if (!Upload::has($f) || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || $f['size'] > 2_000_000) {
                        throw new RuntimeException('Please choose a CSV file (under 2 MB).');
                    }
                    $rows = Importer::fromCsv($f['tmp_name']);
                    break;

                default:
                    throw new RuntimeException('Choose an import method.');
            }
            if (!$rows) {
                throw new RuntimeException('No publications were found in that source.');
            }
            [$added, $skipped] = Importer::save($rows);
            Audit::log('publications_import', "$mode: $added added, $skipped skipped");
            flash('success', "Imported $added publication" . ($added === 1 ? '' : 's') . ($skipped ? " ($skipped skipped as duplicates)" : '') . '. Review them below and tick “Feature on the home page” for your best ones.');
            redirect('admin/publications');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('admin/publications/import');
        }
    }

    public function template(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="publications-template.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['title', 'authors', 'venue', 'year', 'type', 'doi', 'url', 'abstract']);
        fputcsv($out, ['Example paper title', 'A. Author, B. Author', 'Journal of Examples', '2025', 'journal', '10.1000/example', '', 'Optional abstract']);
        fclose($out);
    }
}

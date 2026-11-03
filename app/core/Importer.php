<?php
declare(strict_types=1);

/** Publication importers: DOI (Crossref), ORCID, BibTeX and CSV. All return arrays of publication field sets. */
final class Importer
{
    public const TYPES = [
        'journal' => 'Journal article', 'conference' => 'Conference paper', 'book' => 'Book', 'chapter' => 'Book chapter',
        'thesis' => 'Thesis', 'report' => 'Report', 'preprint' => 'Preprint', 'other' => 'Other',
    ];

    /* ---------- HTTP ---------- */

    private static function getJson(string $url, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL is not enabled, so online lookups are unavailable. BibTeX and CSV import still work.');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'ClearSkyAcademy/1.0 (mailto:' . (Settings::get('contact_email') ?: 'webmaster@example.com') . ')',
            CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        ]);
        // WAMP ships without a CA bundle; on Windows, trust the system certificate store instead.
        if (PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && defined('CURLSSLOPT_NATIVE_CA')) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        }
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        if ($body === false) {
            $hint = stripos($err, 'certificate') !== false ? ' (WAMP may need a CA bundle: set curl.cainfo in php.ini)' : '';
            throw new RuntimeException('Could not reach the server: ' . $err . $hint);
        }
        if ($code === 404) {
            throw new RuntimeException('Not found. Please check the identifier.');
        }
        $data = json_decode((string)$body, true);
        if ($code >= 400 || !is_array($data)) {
            throw new RuntimeException('The lookup service returned an error (HTTP ' . $code . ').');
        }
        return $data;
    }

    /* ---------- DOI via Crossref ---------- */

    public static function normaliseDoi(string $doi): string
    {
        $doi = trim($doi);
        $doi = preg_replace('#^(https?://)?(dx\.)?doi\.org/#i', '', $doi);
        $doi = preg_replace('#^doi:\s*#i', '', $doi);
        return trim((string)$doi);
    }

    public static function fromDoi(string $doi): array
    {
        $doi = self::normaliseDoi($doi);
        if (!preg_match('#^10\.\d{4,9}/\S+$#', $doi)) {
            throw new RuntimeException('That does not look like a DOI (it should start with 10.).');
        }
        $data = self::getJson('https://api.crossref.org/works/' . rawurlencode($doi));
        $m = $data['message'] ?? [];
        $authors = [];
        foreach ($m['author'] ?? [] as $a) {
            $name = trim(($a['given'] ?? '') . ' ' . ($a['family'] ?? ($a['name'] ?? '')));
            if ($name !== '') {
                $authors[] = $name;
            }
        }
        $year = $m['issued']['date-parts'][0][0] ?? ($m['published']['date-parts'][0][0] ?? ($m['created']['date-parts'][0][0] ?? null));
        $abstract = isset($m['abstract']) ? trim(html_entity_decode(strip_tags((string)$m['abstract']))) : '';
        $abstract = preg_replace('/^Abstract\s*/i', '', $abstract);
        return [
            'title' => trim(html_entity_decode(strip_tags((string)($m['title'][0] ?? '')))),
            'authors' => implode(', ', $authors),
            'venue' => trim(html_entity_decode((string)($m['container-title'][0] ?? ($m['publisher'] ?? '')))),
            'year' => $year ? (int)$year : null,
            'type' => self::mapCrossrefType((string)($m['type'] ?? '')),
            'doi' => (string)($m['DOI'] ?? $doi),
            'url' => (string)($m['URL'] ?? ''),
            'abstract' => (string)$abstract,
        ];
    }

    private static function mapCrossrefType(string $t): string
    {
        return match ($t) {
            'journal-article' => 'journal',
            'proceedings-article' => 'conference',
            'book', 'monograph', 'edited-book' => 'book',
            'book-chapter' => 'chapter',
            'dissertation' => 'thesis',
            'report' => 'report',
            'posted-content' => 'preprint',
            default => 'other',
        };
    }

    /* ---------- ORCID ---------- */

    public static function fromOrcid(string $orcid): array
    {
        $orcid = trim($orcid);
        $orcid = preg_replace('#^https?://orcid\.org/#i', '', $orcid);
        if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', (string)$orcid)) {
            throw new RuntimeException('An ORCID looks like 0000-0002-1825-0097.');
        }
        $data = self::getJson('https://pub.orcid.org/v3.0/' . $orcid . '/works');
        $out = [];
        foreach ($data['group'] ?? [] as $g) {
            $w = $g['work-summary'][0] ?? null;
            if (!$w) {
                continue;
            }
            $doi = '';
            foreach ($g['external-ids']['external-id'] ?? [] as $id) {
                if (($id['external-id-type'] ?? '') === 'doi') {
                    $doi = self::normaliseDoi((string)($id['external-id-value'] ?? ''));
                    break;
                }
            }
            $title = trim((string)($w['title']['title']['value'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'authors' => '',
                'venue' => (string)($w['journal-title']['value'] ?? ''),
                'year' => isset($w['publication-date']['year']['value']) ? (int)$w['publication-date']['year']['value'] : null,
                'type' => self::mapOrcidType((string)($w['type'] ?? '')),
                'doi' => $doi,
                'url' => (string)($w['url']['value'] ?? ''),
                'abstract' => '',
            ];
        }
        return $out;
    }

    private static function mapOrcidType(string $t): string
    {
        return match ($t) {
            'JOURNAL_ARTICLE' => 'journal',
            'CONFERENCE_PAPER', 'CONFERENCE_ABSTRACT', 'CONFERENCE_POSTER' => 'conference',
            'BOOK' => 'book',
            'BOOK_CHAPTER' => 'chapter',
            'DISSERTATION_THESIS' => 'thesis',
            'REPORT' => 'report',
            'PREPRINT' => 'preprint',
            default => 'other',
        };
    }

    /* ---------- BibTeX ---------- */

    public static function fromBibtex(string $src): array
    {
        $out = [];
        $len = strlen($src);
        $pos = 0;
        while (($at = strpos($src, '@', $pos)) !== false) {
            if (!preg_match('/\G@(\w+)\s*([{(])/A', $src, $m, 0, $at)) {
                $pos = $at + 1;
                continue;
            }
            $type = strtolower($m[1]);
            $i = $at + strlen($m[0]);
            // Find the matching close of the entry, respecting nested braces.
            $depth = 1;
            $start = $i;
            for (; $i < $len && $depth > 0; $i++) {
                if ($src[$i] === '{') {
                    $depth++;
                } elseif ($src[$i] === '}') {
                    $depth--;
                }
            }
            $pos = $i;
            if (in_array($type, ['comment', 'string', 'preamble'], true)) {
                continue;
            }
            $entry = substr($src, $start, $i - $start - 1);
            $fields = self::bibFields($entry);
            if (empty($fields['title'])) {
                continue;
            }
            $authors = isset($fields['author']) ? implode(', ', array_map([self::class, 'bibAuthor'], preg_split('/\s+and\s+/i', $fields['author']))) : '';
            $out[] = [
                'title' => $fields['title'],
                'authors' => $authors,
                'venue' => $fields['journal'] ?? ($fields['booktitle'] ?? ($fields['publisher'] ?? ($fields['school'] ?? ($fields['institution'] ?? '')))),
                'year' => isset($fields['year']) ? (int)$fields['year'] : null,
                'type' => self::mapBibType($type),
                'doi' => self::normaliseDoi($fields['doi'] ?? ''),
                'url' => $fields['url'] ?? '',
                'abstract' => $fields['abstract'] ?? '',
            ];
        }
        return $out;
    }

    private static function bibFields(string $entry): array
    {
        $fields = [];
        $i = strpos($entry, ',');
        $len = strlen($entry);
        if ($i === false) {
            return $fields;
        }
        $i++;
        while ($i < $len) {
            if (!preg_match('/\G\s*,?\s*(\w[\w-]*)\s*=\s*/A', $entry, $m, 0, $i)) {
                break;
            }
            $name = strtolower($m[1]);
            $i += strlen($m[0]);
            $val = '';
            if ($i < $len && $entry[$i] === '{') {
                $depth = 0;
                for ($j = $i; $j < $len; $j++) {
                    if ($entry[$j] === '{') {
                        $depth++;
                    } elseif ($entry[$j] === '}') {
                        $depth--;
                        if ($depth === 0) {
                            break;
                        }
                    }
                }
                $val = substr($entry, $i + 1, $j - $i - 1);
                $i = $j + 1;
            } elseif ($i < $len && $entry[$i] === '"') {
                $j = $i + 1;
                while ($j < $len && ($entry[$j] !== '"' || $entry[$j - 1] === '\\')) {
                    $j++;
                }
                $val = substr($entry, $i + 1, $j - $i - 1);
                $i = $j + 1;
            } else {
                preg_match('/\G[^,\s}]+/A', $entry, $mm, 0, $i);
                $val = $mm[0] ?? '';
                $i += strlen($val);
            }
            $val = trim(preg_replace('/\s+/', ' ', str_replace(['{', '}', '\\&', '\\_', '~'], ['', '', '&', '_', ' '], $val)));
            $fields[$name] = $val;
        }
        return $fields;
    }

    private static function bibAuthor(string $a): string
    {
        $a = trim($a);
        if (str_contains($a, ',')) {
            [$last, $first] = array_map('trim', explode(',', $a, 2));
            return trim($first . ' ' . $last);
        }
        return $a;
    }

    private static function mapBibType(string $t): string
    {
        return match ($t) {
            'article' => 'journal',
            'inproceedings', 'conference', 'proceedings' => 'conference',
            'book' => 'book',
            'incollection', 'inbook' => 'chapter',
            'phdthesis', 'mastersthesis' => 'thesis',
            'techreport' => 'report',
            'unpublished' => 'preprint',
            default => 'other',
        };
    }

    /* ---------- CSV ---------- */

    public static function fromCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) {
            throw new RuntimeException('Could not read the CSV file.');
        }
        $header = fgetcsv($fh);
        if (!$header) {
            throw new RuntimeException('The CSV file is empty.');
        }
        $header = array_map(fn($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$h))), $header);
        if (!in_array('title', $header, true)) {
            throw new RuntimeException('The CSV needs a header row with at least a "title" column. Download the template for an example.');
        }
        $out = [];
        while (($row = fgetcsv($fh)) !== false) {
            if (count(array_filter($row, fn($c) => trim((string)$c) !== '')) === 0) {
                continue;
            }
            $r = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
            $title = trim((string)($r['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $type = strtolower(trim((string)($r['type'] ?? '')));
            $out[] = [
                'title' => $title,
                'authors' => trim((string)($r['authors'] ?? '')),
                'venue' => trim((string)($r['venue'] ?? ($r['journal'] ?? ''))),
                'year' => ($r['year'] ?? '') !== '' ? (int)$r['year'] : null,
                'type' => isset(self::TYPES[$type]) ? $type : 'other',
                'doi' => self::normaliseDoi((string)($r['doi'] ?? '')),
                'url' => trim((string)($r['url'] ?? '')),
                'abstract' => trim((string)($r['abstract'] ?? '')),
            ];
        }
        fclose($fh);
        return $out;
    }

    /** Inserts rows, skipping any that already exist (same DOI, or same title and year). Returns [added, skipped]. */
    public static function save(array $rows): array
    {
        $added = $skipped = 0;
        foreach ($rows as $r) {
            $dupe = false;
            if (!empty($r['doi'])) {
                $dupe = (bool)Db::val('SELECT 1 FROM publications WHERE is_sample = 0 AND doi = ? LIMIT 1', [$r['doi']]);
            }
            if (!$dupe) {
                $dupe = (bool)Db::val('SELECT 1 FROM publications WHERE is_sample = 0 AND LOWER(title) = LOWER(?) AND (year <=> ?) LIMIT 1', [$r['title'], $r['year']]);
            }
            if ($dupe) {
                $skipped++;
                continue;
            }
            $url = (string)($r['url'] ?? '');
            Db::insert('publications', [
                'title' => mb_substr($r['title'], 0, 500),
                'authors' => mb_substr((string)$r['authors'], 0, 1000),
                'venue' => mb_substr((string)$r['venue'], 0, 300),
                'year' => $r['year'] ?: null,
                'type' => isset(self::TYPES[$r['type']]) ? $r['type'] : 'other',
                'doi' => mb_substr((string)$r['doi'], 0, 200),
                'url' => preg_match('#^https?://#i', $url) ? mb_substr($url, 0, 500) : '',
                'abstract' => (string)($r['abstract'] ?? ''),
                'is_visible' => 1,
                'is_sample' => 0,
            ]);
            $added++;
        }
        return [$added, $skipped];
    }
}

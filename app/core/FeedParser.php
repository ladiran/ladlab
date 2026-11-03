<?php
declare(strict_types=1);

/** Parses RSS 2.0, RSS 1.0 (RDF) and Atom feeds into plain, text-only items. Never trusts feed markup. */
final class FeedParser
{
    /** @return array<int, array{guid:string,title:string,link:string,summary:string,published:?int,image:string}> */
    public static function parse(string $xml): array
    {
        $xml = ltrim($xml, "\xEF\xBB\xBF \t\r\n");
        if ($xml === '' || $xml[0] !== '<') {
            throw new RuntimeException('The address did not return a feed (it returned a web page or plain text).');
        }
        // Refuse entity declarations outright (blocks XXE and "billion laughs" attacks).
        if (stripos($xml, '<!ENTITY') !== false) {
            throw new RuntimeException('The feed contains unsafe XML entity declarations and was rejected.');
        }
        $prev = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $ok = $doc->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
        if (!$ok) {
            // Some publishers ship a slightly broken root tag (e.g. attributes glued together). Repair only that tag and retry once.
            $fixed = preg_replace_callback('/<(rss|feed|rdf:RDF)(?=[\s>])[^>]*>/i', fn($m) => preg_replace('/(="[^"]*")(?=[A-Za-z_][\w:.\-]*=)/', '$1 ', $m[0]), $xml, 1);
            if ($fixed !== null && $fixed !== $xml) {
                $doc = new DOMDocument();
                $ok = $doc->loadXML($fixed, LIBXML_NONET | LIBXML_NOCDATA);
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok || !$doc->documentElement) {
            throw new RuntimeException('The feed is not valid XML.');
        }
        $xp = new DOMXPath($doc);
        $root = strtolower($doc->documentElement->localName);

        if ($root === 'feed') {
            $nodes = $xp->query("//*[local-name()='entry']");
            $kind = 'atom';
        } elseif ($root === 'rss' || $root === 'rdf') {
            $nodes = $xp->query("//*[local-name()='item']");
            $kind = 'rss';
        } else {
            throw new RuntimeException('Unrecognised feed format (expected RSS or Atom).');
        }

        $items = [];
        foreach ($nodes as $n) {
            $title = self::text(self::first($xp, $n, 'title'));
            if ($title === '') {
                continue;
            }
            if ($kind === 'atom') {
                $link = '';
                foreach ($xp->query("*[local-name()='link']", $n) as $l) {
                    $rel = $l->getAttribute('rel');
                    if ($rel === '' || $rel === 'alternate') {
                        $link = trim($l->getAttribute('href'));
                        break;
                    }
                }
                $summary = self::text(self::first($xp, $n, 'summary') ?: self::first($xp, $n, 'content'));
                $date = self::first($xp, $n, 'published') ?: self::first($xp, $n, 'updated');
                $guid = trim(self::first($xp, $n, 'id'));
            } else {
                $link = trim(self::first($xp, $n, 'link'));
                if ($link === '') {
                    $link = trim(self::first($xp, $n, 'guid'));
                }
                $summary = self::text(self::first($xp, $n, 'description') ?: self::first($xp, $n, 'encoded'));
                $date = self::first($xp, $n, 'pubDate') ?: (self::first($xp, $n, 'date') ?: self::first($xp, $n, 'updated'));
                $guid = trim(self::first($xp, $n, 'guid'));
            }
            $image = '';
            foreach (['thumbnail', 'content'] as $tag) {
                foreach ($xp->query("*[local-name()='$tag']", $n) as $m) {
                    $u = trim($m->getAttribute('url'));
                    $type = $m->getAttribute('type');
                    if ($u !== '' && ($type === '' || str_starts_with($type, 'image')) && $m->getAttribute('medium') !== 'video') {
                        $image = $u;
                        break 2;
                    }
                }
            }
            if ($image === '') {
                foreach ($xp->query("*[local-name()='enclosure']", $n) as $m) {
                    if (str_starts_with($m->getAttribute('type'), 'image')) {
                        $image = trim($m->getAttribute('url'));
                        break;
                    }
                }
            }
            $ts = $date !== '' ? strtotime($date) : false;
            $items[] = [
                'guid' => $guid,
                'title' => $title,
                'link' => $link,
                'summary' => $summary,
                'published' => $ts ?: null,
                'image' => $image,
            ];
        }
        return $items;
    }

    private static function first(DOMXPath $xp, DOMNode $ctx, string $local): string
    {
        $r = $xp->query("*[local-name()='$local']", $ctx);
        return $r->length ? (string)$r->item(0)->textContent : '';
    }

    /** Strip markup, decode entities, drop common "appeared first on" boilerplate, collapse whitespace. */
    public static function text(string $html): string
    {
        $html = preg_replace('~<(script|style)\b.*?</\1>~is', ' ', $html);
        $html = preg_replace('~<br\s*/?>|</p>|</div>|</li>~i', ' ', (string)$html);
        $t = html_entity_decode(strip_tags((string)$html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('/\s*The post .{1,200} appeared first on .{1,100}\.?\s*$/su', '', $t);
        $t = preg_replace('~\s*(read more|continue reading|click here to read more)\s*:?\s*https?://\S+\s*$~iu', '', (string)$t);
        $t = preg_replace('/\s*(Read more|Continue reading|Click here to read more)\.{0,3}\s*(»|›)?\s*$/iu', '', (string)$t);
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x{200B}-\x{200F}\x{FEFF}]/u', '', (string)$t);
        return trim((string)preg_replace('/\s+/u', ' ', (string)$t));
    }
}

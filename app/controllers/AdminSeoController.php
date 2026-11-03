<?php
declare(strict_types=1);

/** Admin → Search engines: a checklist, how to register with Google and Bing, verification codes and IndexNow. */
final class AdminSeoController extends AdminBase
{
    public function index(): void
    {
        $prod = Seo::indexingAllowed();
        $appUrl = (string)env('APP_URL', '');
        $host = (string)parse_url($appUrl, PHP_URL_HOST);
        $c = [];
        $add = function (string $s, string $t, string $d) use (&$c): void {
            $c[] = ['status' => $s, 'title' => $t, 'detail' => $d];
        };
        $add($prod ? 'ok' : 'bad', $prod ? 'Search engines are allowed to index this site' : 'Indexing is switched OFF (this is a test installation)', $prod ? 'robots.txt invites crawlers and pages carry "index, follow".' : 'The site tells every search engine to stay away. When you go live set APP_ENV=production in config/.env (and APP_DEBUG=0).');
        if ($appUrl === '' || $host === '' || $host === 'localhost' || str_starts_with($appUrl, 'http://')) {
            $add($prod ? 'bad' : 'warn', 'Set your public web address (APP_URL)', 'Add APP_URL=https://yourdomain.com to config/.env. Without it, the sitemap and canonical links use whatever address the visitor typed, which can confuse Google.');
        } else {
            $add('ok', "Public web address: $appUrl", 'Canonical links, the sitemap and structured data use this address.');
        }
        $add(is_https() ? 'ok' : ($prod ? 'warn' : 'ok'), is_https() ? 'This page was served over HTTPS' : 'HTTPS not detected from here', 'Google prefers HTTPS. The Cloudflare tunnel provides it; also turn on "Always Use HTTPS" in Cloudflare.');
        $reqHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($appUrl !== '' && $reqHost !== '' && $host !== '' && $reqHost !== strtolower($host) && !str_starts_with($reqHost, 'localhost')) {
            $add('bad', "Your address (APP_URL) does not match the address people use: $reqHost", "APP_URL says $host but visitors reach the site at $reqHost. Google would be told to prefer a different address from the one that works, and the sitemap would list redirected addresses. Edit APP_URL in config/.env to https://$reqHost");
        }
        if (BASE_PATH !== '') {
            $add('warn', 'The site lives in a sub-folder (' . BASE_PATH . ')', 'Crawlers read only the robots.txt at the domain root, so the private-page rules in this site\'s own robots.txt are ignored (the pages are still protected by noindex tags). For the cleanest result give the site its own domain or sub-domain with the web root pointing to the public folder. If you keep the sub-folder, ask the owner of the domain to add the Disallow and Sitemap lines shown below to the root robots.txt.');
        }
        if (is_file(ROOT . '/public/install.php')) {
            $add('warn', 'The installer file is still on the server', 'Delete it from Admin → Security (one button).');
        }
        $desc = Settings::get('meta_description');
        $add(mb_strlen($desc) >= 70 && mb_strlen($desc) <= 170 ? 'ok' : 'warn', 'Site description (' . mb_strlen($desc) . ' characters)', 'Shown in Google results for the home page. Aim for 120 to 160 characters. Edit under Search & sharing.');
        $add(setting('og_image') !== '' ? 'ok' : 'warn', setting('og_image') !== '' ? 'A sharing image is set' : 'No sharing image yet', 'The picture shown when your pages are shared on WhatsApp, Facebook and X. Upload one (1200 × 630 px) under Search & sharing.');
        $g = setting('seo_google_verification') !== '';
        $b = setting('seo_bing_verification') !== '';
        $add($g ? 'ok' : 'warn', $g ? 'Google verification code saved' : 'Google Search Console: not verified yet', 'Follow step 2 below. It is free and lets you submit the sitemap and see how Google sees the site.');
        $add($b ? 'ok' : 'warn', $b ? 'Bing verification code saved' : 'Bing Webmaster Tools: not verified yet', 'Bing also feeds Yahoo, DuckDuckGo and Ecosia, so registering there reaches all of them.');
        $samples = (int)Db::val('SELECT COUNT(*) FROM posts WHERE is_sample = 1') + (int)Db::val('SELECT COUNT(*) FROM products WHERE is_sample = 1') + (int)Db::val('SELECT COUNT(*) FROM courses WHERE is_sample = 1');
        $add($samples ? 'warn' : 'ok', $samples ? "$samples sample items still exist" : 'No placeholder content is left', $samples ? 'Sample posts, products and courses are kept out of the sitemap and marked "noindex", so they never reach Google. Replace them with your real content.' : '');
        $entries = Seo::sitemapEntries();
        $this->page('admin/seo', [
            'title' => 'Search engines (SEO)', 'checks' => $c, 'count' => count($entries), 'prod' => $prod, 'appUrl' => $appUrl,
            'google' => setting('seo_google_verification'), 'bing' => setting('seo_bing_verification'), 'yandex' => setting('seo_yandex_verification'),
            'sub' => BASE_PATH !== '', 'robotsText' => Seo::robotsTxt(), 'indexnowLast' => Settings::get('indexnow_last'), 'keyUrl' => abs_url(Seo::indexNowKey() . '.txt'),
        ]);
    }

    public function save(): void
    {
        $this->verify();
        foreach (['seo_google_verification', 'seo_bing_verification', 'seo_yandex_verification'] as $k) {
            $v = trim((string)($_POST[$k] ?? ''));
            // accept a pasted whole meta tag too: pull out just the content value
            if (preg_match('/content=["\']([^"\']+)["\']/', $v, $m)) {
                $v = $m[1];
            }
            Settings::set($k, preg_match('/^[A-Za-z0-9_\-]{10,120}$/', $v) ? $v : '');
        }
        Audit::log('seo_settings', '');
        flash('success', 'Saved. The codes appear in every page head within a minute.');
        redirect('admin/seo');
    }

    public function notify(): void
    {
        $this->verify();
        $r = Seo::indexNow(array_column(Seo::sitemapEntries(), 0));
        flash($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Sent every page address to the IndexNow search engines (Bing, Yandex, Naver, Seznam…).' : 'Not sent: ' . $r['message']);
        redirect('admin/seo');
    }
}

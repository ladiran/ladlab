<?php $icons = ['ok' => '✅', 'warn' => '⚠️', 'bad' => '⛔']; ?>
<p class="muted">Everything search engines need is built in: a sitemap of <strong><?= (int)$count ?></strong> pages, a robots file, a canonical address and sharing tags on every page, structured data (Google-ready information about products, courses, articles, events and you), a blog feed, and automatic "noindex" for private pages and placeholder content.</p>

<section class="card sec-card">
  <h2>Checklist</h2>
  <ul class="checklist">
    <?php foreach ($checks as $c): ?>
      <li class="chk chk-<?= e($c['status']) ?>"><span class="chk-ico" aria-hidden="true"><?= $icons[$c['status']] ?></span><div><strong><?= e($c['title']) ?></strong><?php if ($c['detail'] !== ''): ?><br><span class="muted"><?= e($c['detail']) ?></span><?php endif; ?></div></li>
    <?php endforeach; ?>
  </ul>
  <?php if ($sub): ?><details class="sec-details"><summary>Lines for the domain's root robots.txt (sub-folder install)</summary><pre class="code-block"><?= e($robotsText) ?></pre></details><?php endif; ?>
  <p>Look at them yourself: <a href="<?= e(url('/sitemap.xml')) ?>" target="_blank">sitemap.xml</a> · <a href="<?= e(url('/robots.txt')) ?>" target="_blank">robots.txt</a> · <a href="<?= e(url('/blog/feed.xml')) ?>" target="_blank">blog feed</a></p>
</section>

<section class="card sec-card">
  <h2>Get listed: three steps (about 15 minutes, once)</h2>
  <ol class="steps">
    <li><strong>Go live properly.</strong> In <code>config/.env</code> set <code>APP_ENV=production</code>, <code>APP_DEBUG=0</code> and <code>APP_URL=https://yourdomain.com</code> (your real address). In Cloudflare turn on <em>Always Use HTTPS</em> and pick one version of your address (with or without <code>www</code>) and redirect the other to it. Do not put the site under "I'm Under Attack" mode or block bots: Google and Bing are verified bots and are allowed by default.</li>
    <li><strong>Google:</strong> sign in at <a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">Google Search Console</a> → Add property → <em>URL prefix</em> → your address → choose <em>HTML tag</em>, and paste the code below. Press Verify there, then open <em>Sitemaps</em> and submit <code>sitemap.xml</code>. Later, use <em>URL inspection → Request indexing</em> for new important pages.</li>
    <li><strong>Bing</strong> (this also covers Yahoo, DuckDuckGo and Ecosia): sign in at <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener noreferrer">Bing Webmaster Tools</a> → Add site (you can import it from Google Search Console in one click) → or choose <em>HTML meta tag</em> and paste the code below → submit <code>sitemap.xml</code>.</li>
  </ol>
  <form method="post" action="<?= e(url('/admin/seo/save')) ?>" class="form admin-form"><?= csrf_field() ?>
    <div class="field"><label for="sg">Google verification code</label><input type="text" id="sg" name="seo_google_verification" value="<?= e($google) ?>" maxlength="200" placeholder="paste the content value, or the whole meta tag"></div>
    <div class="field"><label for="sb">Bing verification code</label><input type="text" id="sb" name="seo_bing_verification" value="<?= e($bing) ?>" maxlength="200"></div>
    <div class="field"><label for="sy">Yandex verification code (optional)</label><input type="text" id="sy" name="seo_yandex_verification" value="<?= e($yandex) ?>" maxlength="200"></div>
    <div class="form-actions"><button class="btn btn-gold" type="submit">Save codes</button></div>
  </form>
</section>

<section class="card sec-card">
  <h2>Tell search engines about changes (IndexNow)</h2>
  <p>IndexNow lets Bing, Yandex, Naver and Seznam learn about new or changed pages within minutes instead of days. New blog posts are announced automatically; this button sends every page. Google does not use IndexNow: it finds changes from the sitemap and Search Console.</p>
  <p class="muted small">Ownership proof file: <?= e($keyUrl) ?> · Last sent: <?= $indexnowLast !== '' ? e($indexnowLast) : 'never' ?></p>
  <form method="post" action="<?= e(url('/admin/seo/notify')) ?>"><?= csrf_field() ?><button class="btn btn-navy" type="submit" <?= $prod ? '' : 'disabled' ?>>Send all pages now</button></form>
</section>

<section class="card sec-card">
  <h2>Habits that help you rank</h2>
  <ul>
    <li>Publish useful pages regularly: blog posts, lesson previews, free tools notes. Each one has its own address and is added to the sitemap by itself.</li>
    <li>Give every product, course and post a clear title and a one-sentence summary: they become the headline and description in search results.</li>
    <li>Add a sharing image and your real name, photo and institution (Profile &amp; photos): Google uses them to understand who you are.</li>
    <li>Link your Google Scholar, ORCID, LinkedIn and ResearchGate profiles under Contact &amp; social, and link back to this site from them.</li>
    <li>Ranking takes weeks to months: a new site is normally indexed within days to a few weeks. Check Search Console → Pages to see what is indexed.</li>
  </ul>
</section>

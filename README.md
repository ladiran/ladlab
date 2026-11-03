# ClearSky Academy: Phases 1 to 9

Plain PHP 8 + MySQL/MariaDB. No frameworks, no Composer, no build step.

* **Phase 1 (Foundation):** public site (Home, About, Research, Blog, Contact, newsletter, legal pages) and an Admin area.
* **Phase 2 (News):** automatic science-news desk with source tiers, strict "BREAKING" rules, ticker, Nobel Prize centre.
* **Phase 3 (Accounts & Shop):** customer login / registration / **forgot password**, and the T-shirt shop: catalogue, cart, checkout, bank-transfer payment, receipts, stock, delivery fees, coupons, order board, printable pick lists and labels.
* **Phase 4 (Learn):** University Physics courses: catalogue, course pages, lesson player with video and maths, quizzes, progress, enrolment by bank transfer, certificates with a public verification code, and an Admin course builder.
* **Phase 5 (Shop sections, teaching and self-promotion extras):** shop categories you create yourself, one-size products (books, mugs, teaching aids) next to the T-shirts, product copy, back-in-stock alerts; course announcements, lesson questions and answers, and moderated reviews; Services, Speaking and media kit, Events, In the media, Free resources and a link-in-bio page, all editable from Admin.
* **Phase 7 (Free tools):** a curated directory at `/toolbox` of free learning, teaching and research resources (YouTube lessons, open textbooks, software such as WRF and Quantum ESPRESSO with install notes, data, papers), managed in Admin → Free tools (links).
* **Phase 8 (Copyright safeguards):** every Free Resource carries a licence, an author credit and a rights confirmation, each has a "Report a problem" link, and there is a Copyright and takedown policy page.
* **Phase 9 (Search engines):** sitemap, robots.txt, canonical links, structured data, sharing tags, blog feed, verification codes for Google/Bing/Yandex and IndexNow, plus Admin → Search engines (SEO).
* **News refresh and scrolling bar:** the news updates itself every 2 hours (adjustable) and the bar under the header scrolls (BREAKING in red; the latest headlines in blue when nothing is breaking), with a pause button.
* **Phase 6 (Security hardening):** encrypted database and email passwords, a dedicated limited database user, optional two-step verification (Google/Microsoft Authenticator and similar) with recovery codes, an Admin → Security page with a plain-language checklist, sign-in history and alerts, "sign out everywhere", stronger passwords and sessions.
* On hold: online payment gateway (bank transfer remains the payment method).

## 1. Install it

### A. Fresh installation (new site, recommended for testing)
1. Copy this whole folder to `C:\wamp64\www\clearsky_academy` (replace any older copy of the folder completely; if an old `config\.env` exists, delete it so the installer appears).
2. Start WAMP (icon **green**; PHP 8.1 or newer, 8.3 recommended).
3. Open **http://localhost/clearsky_academy/**. You are sent to the installer, pre-filled for WAMP (host `127.0.0.1`, port `3306`, user `root`, empty password). Choose a **new database name**, tick "Load sample content", create your administrator account, press **Install**. One step creates everything: all tables for Phases 1-5, sample content, the approved news sources, six sample T-shirts, a sample course (University Physics I: Mechanics, 30 lessons, 4 quizzes), placeholder delivery fees and legal templates.
4. Sign in at **http://localhost/clearsky_academy/admin**. Delete `public/install.php` when done.

Other ways to do the same thing:
* Command line: `php tools/install_cli.php --admin-email=you@example.com --admin-password="a long passphrase" --admin-name="Your Name"`.
* By hand in phpMyAdmin: create an empty database (utf8mb4_unicode_ci), **Import** `database/fresh_install.sql`, copy `config/.env.example` to `config/.env` and set the `DB_` lines, then run `php tools/create_admin.php --email=you@example.com --password="a long passphrase"`.

### B. Updating an existing site from an earlier phase
1. Copy the new files over the old ones, **keeping** your `config/.env` and `public/uploads/` (and `storage/`).
2. Visitors now see a friendly "We are updating the site" page until the database is updated. Sign in at `/admin` and press **Apply update now** on the dashboard (or run `php tools/migrate.php`). Your content is untouched; the new tables are added, and your existing product categories become real shop categories automatically.
3. Take a database backup first if the site is live.

**If you see `Unknown column 'phone'` (or `Table ... doesn't exist`)** the new files are in place but the database update has not been applied yet. Open `/admin` and press **Apply update now** (the admin area keeps working in this state), or run `php tools/migrate.php`. If the admin area itself will not open, check that `config/.env` points at the right database and that all files were copied.

## 2. Customer accounts

* Header (top strip): **Login · Register** for visitors; **👤 name · Logout** (plus **Admin** for administrators) when signed in; **🛒 Cart** with item count.
* **/login**, **/register**, **/forgot-password** (also linked from the admin sign-in page), **/account** (overview, orders, details & default address, change password, delete account).
* Forgot password: a one-hour, single-use link is emailed (in test mode it is written to `storage/logs/mail.log`). The answer is the same whether or not the address has an account; only a hash of the token is stored; max 3 live requests per account per hour; old links die when a new password is set. Administrators need 10+ characters, customers 8+.
* Customers can never open the admin area; the admin sign-in refuses customer accounts. Guests can check out without an account (optionally creating one at checkout).

## 3. The shop: how it works

**Customer journey:** shop → product (choose colour and size; unavailable sizes are greyed out) → cart (coupon) → checkout (delivery or pickup, state fee shown live) → order page with the **bank details and order number as the transfer reference** → optional **receipt upload** → emails at each step → tracking on the order page. Orders are reachable by a private link (also emailed) or from the account.

**What the system guarantees**
* Stock is **reserved when the order is placed** and re-checked under a database lock, so the last unit cannot be sold twice (tested with six simultaneous buyers).
* Prices and fees are always recalculated on the server; the browser cannot change them.
* Unpaid orders are released after the hold time (default 24 h, edit under Bank details). Orders where the customer uploaded a receipt are **never auto-expired**; you decide. Cancelling returns stock and coupon use.
* Receipts are stored **outside the public web folder** and only an administrator can open them.

### Your daily routine (Admin → Shop orders)
1. **Awaiting payment:** check your bank. If the money is in, open the order → **Confirm payment received**. (Receipt photo is one click away.)
2. **To pack:** print **Pick list for all paid orders** (grouped by shelf), pack, then **Mark packed**. Print the **packing slip** and **address label** per order.
3. **Ship:** choose the courier, paste the tracking number → **Mark shipped** (the customer is emailed). Pickup orders: **Ready for pickup**, then **Collected**.
4. Use the **Board** view for a kanban overview, **Export** for a CSV of orders for your accounts.

### Setting up the shop (Admin)
* **Products & stock:** name, price, photos (up to 8), description, colours (`Name|#hex` per line), sizes, **stock grid per colour × size**. Add a product with another category and it appears alongside the tees. Sample products vanish as soon as you put a real product **On sale**.
* **Stock:** all levels at once with **re-order level** (amber when low) and **shelf location** (printed on pick lists). Every change is logged with a reason.
* **Delivery fees:** a fee per state (placeholders loaded; set your courier prices). Default fee, free-delivery threshold, pickup address and dispatch promise are in **Shop settings**.
* **Coupons:** percentage or fixed amount, minimum order, maximum uses, expiry.
* **Bank details:** your real account (placeholder until you change it) and order hold time.
* **Terms of Sale:** a starting template under Legal pages. Please have it reviewed.

### Scheduling (optional but recommended)
**News updates itself every 2 hours** (change it in Admin → Science news → Thresholds & keywords → "Update the news automatically every"). Out of the box it runs whenever a visitor opens a page after the time is up, so nothing needs installing. To make it run exactly on time even when nobody visits, schedule `cron/fetch_news.php` every 15 minutes: it only does work when an update is due. The exact commands, with your folder filled in, are on Admin → Science news ("Automatic updates"). Also run the stock-release job hourly. PowerShell on WAMP:

```
schtasks /Create /SC MINUTE /MO 15 /TN "ClearSkyNews" /TR "\"C:\wamp64\bin\php\php8.3.14\php.exe\" \"C:\wamp64\www\clearsky_academy\cron\fetch_news.php\"" /F
schtasks /Create /SC HOURLY /TN "ClearSkyShop" /TR "\"C:\wamp64\bin\php\php8.3.14\php.exe\" \"C:\wamp64\www\clearsky_academy\cron\shop_cleanup.php\"" /F
```
Without the shop job, expiry still happens whenever anyone opens the shop, checkout or Admin → Orders.

### Payments
Phase 3 uses **bank transfer** with manual confirmation (no CAC registration needed). The payment step is isolated in `Shop::bankBlock()` and the "Confirm payment" action, so a gateway such as Paystack can later confirm orders automatically without changing the shop.

## 3b. Phase 4: Learn (courses)

**Students** open **Learn**, choose a course, press **Enrol**. Free course: access at once. Paid course: they see your bank details and an enrolment number (ENR-1001); they transfer, optionally upload the receipt, and you press **Confirm payment** in Admin → **Students & payments**. Coupons from the shop work on courses too. Confirmed students get an email, see **My courses** under their account, and can watch lessons, take quizzes and track progress. A **free preview** lesson in each module is open to everyone.

**A course is complete** when every published lesson is marked done and every published quiz (and the final exam) is passed. The student then receives a **certificate** with a code such as `CSA-XXXX-XXXX-XXXX`; anyone can verify it at `/certificate/<code>`; it prints to PDF from the browser.

### Building a course (Admin → Courses)
1. **Add a course** (title, level, price, pass mark, description, outcomes, cover image). Status `Coming soon` shows it in the catalogue without enrolment; `Draft` hides it.
2. **Curriculum**: add modules, then lessons and quizzes inside them; use the arrows to reorder. A module quiz sits at the end of its module; the **final exam** covers the whole course.
3. **Lessons**: notes in Markdown with maths (`$F = ma$` inline, `$$ ... $$` for displayed equations; the Preview button shows the result), learning objective, video, PDF downloads, free-preview switch, draft/published.
   * Video: a **YouTube** link (use *unlisted*), a **Vimeo** link, or an **MP4/WebM file** uploaded here (stored privately; streamed only to enrolled students, with seeking). The upload limit is your PHP `upload_max_filesize` (shown on the form). For big files copy them into `storage/private/videos` and type the file name.
4. **Quizzes**: pass mark, time limit, number of attempts, shuffle, and **random draw** (build a bank of 30 questions, give each student 10). Five question types: multiple choice, select-all, true/false, numeric (answer, ± % tolerance, optional unit) and short answer (accepted answers, case and punctuation ignored). Each question can have an explanation shown after submission.
5. **Students & payments**: pending payments (with receipts), all enrolments with progress and quiz attempts, give free access (scholarships, your own classes), cancel an enrolment, see certificates.

Maths is rendered on the server as MathML (no external library, works under the strict security policy). It covers the usual physics LaTeX (fractions, roots, sub/superscripts, Greek letters, vectors, integrals, sums, matrices, cases). A construct it does not know is shown as plain LaTeX text rather than breaking the page.
## 3c. Phase 5: what is new and where to find it in Admin

### Shop: products and sections (Admin → Shop orders / Products & stock / Shop categories)
* **Categories (sections).** Admin → **Shop categories** → *Add shop category*: name, short description, optional banner image, order, show/hide. You can also type a new category name directly on a product's form. Each category gets its own page (`/shop/category/books`) and a button on the shop page. A category appears to customers once it has at least one product on sale. Deleting a category never deletes products; they stay in the shop without a category.
* **Adding products.** Admin → **Products & stock** → *Add a product*. Choose the kind:
  * *Clothing* (colours and sizes, with a stock count for each combination, as before).
  * *One-size item* for books, mugs, pens, posters and teaching aids: just one stock count, or several "choices" (for example Paperback and Hardback), each with its own stock.
  Then price, "was" price, description, up to 8 photos, category, "Popular" flag and display order. **Copy** on the product list duplicates a product as a draft, so a second similar item takes a minute.
* **Back-in-stock alerts.** When a product is sold out, shoppers can leave an email address; they get one email the first time you add stock. The product list shows how many people are waiting.
* The cart, checkout, order pages, pick lists and labels all handle one-size products (they simply do not show a size).

### Teaching extras (Admin → Courses)
* **Announce** (button on each course): post a message to enrolled students, optionally emailed to all of them. It shows on the course page for enrolled students.
* **Questions**: students ask under any lesson; you answer in Admin → *Student questions*; the answer is emailed to the student and shown to everyone enrolled. Other students see only a first name and last initial.
* **Reviews**: enrolled students can rate (1–5 stars) and comment. Nothing is published until you approve it (Admin → *Course reviews*). Stars show on the course card and page.
* **Free resources** (Admin → Free resources, public page `/resources`): formula sheets, notes and problem sets as PDFs or links; downloads are counted.

### Self-promotion pages (Admin menu)
* **Services** (`/services`): what you offer, with an Enquire button that opens the contact form on the right topic. The first four also appear on the home page.
* **Speaking topics** and **Speaking & media kit** (`/speaking`): topics, an "organisers" box, short and long media bios with Copy buttons, headshot download, upcoming events, recent talks, testimonials and a booking button. The bios, notice period and fee note are in Admin → *Speaking & media kit*.
* **Events** (`/events`): upcoming and past, sorted automatically by date.
* **In the media** (`/press`): articles, interviews, radio, TV, podcasts, grouped by year.
* **Link page** (`/links`): a single page for your social-media profiles ("link in bio"). Without any entries it builds itself from your main pages and social links.
* Placeholder samples are marked "sample" and disappear section by section as soon as you add your own.
## 3d. Phase 6: security (read this before going live)

### Where the secrets are
* **Database password and email (SMTP) password are never stored as plain text.** `config/.env` holds only `DB_PASS_ENC=enc1:…` (AES-256-GCM, authenticated). The key that unlocks it is a separate file, `storage/keys/app.key` (or the `APP_KEY` environment variable, or any path you set with `APP_KEY_FILE`). Someone who steals only `.env`, or only the database, learns nothing usable.
* **The installer no longer saves the administrator login you type.** It uses it once to create a separate database user (named `csa_…`) that can reach only this site's database, gives it a long random password, tests it, and stores that password encrypted. (Untick the box on the install page, or pass `--no-app-user` on the command line, to skip this.)
* **Keep a backup of `storage/keys/app.key`**, stored separately from your database backups. Without it the encrypted values (and enrolled 2-step secrets) cannot be read. On a live server the safest place is outside the website folder: `php tools/secure_config.php --key-file=/home/you/keys/clearsky.key`.
* **Upgrading from an earlier phase?** Open Admin → **Security**. The checklist shows what is still plain text and has an **Encrypt it now** button. Or run `php tools/secure_config.php` (encrypt), `php tools/secure_config.php --dedicated-user` (switch from root to a limited user; add `--root-user=… --root-pass=…` if root has a password), `php tools/secure_config.php --status`, `php tools/secure_config.php --rotate-key`.

### Two-step verification (optional, strongly advised)
* **Customers:** My account → **Security**. **Administrators:** Admin → **Security**. Scan the picture with Google Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden or any "TOTP" app (or type the setup key), enter the 6-digit code, and save the ten **recovery codes** shown once.
* Signing in then asks for the password **and** a code. A code works only once. Five wrong codes lock the sign-in for 15 minutes.
* The site nags until it is on: a red banner on every admin page and on the account pages, and a reminder after sign-in. Administrators can also tick **Require two-step verification for every administrator**; anyone without it can then reach only the Security page.
* **Lost phone?** Use a recovery code at the sign-in. No codes either? Another administrator can reset it (Admin → Customers → Reset, or the Security page), or from the server: `php tools/reset_2fa.php --email=you@example.com`. The secret is stored encrypted, never in plain text.

### Other protections added
* Passwords: at least 10 characters, a blocklist of common ones, no name or email inside; stored with Argon2id (bcrypt cost 12 where Argon2 is missing) and upgraded automatically at the next sign-in.
* Sessions: administrators are signed out after 30 minutes idle (customers 2 hours), everyone after 12 hours in total, or if the browser identity changes. **Sign out everywhere** on the Security page, and a password or 2-step change, end all other sessions.
* Sign-in history for every account, a "new browser" email alert, and emails when a password or 2-step setting changes (switch off under Security → Settings).
* Real visitor addresses behind a Cloudflare tunnel or proxy on the same machine are read from the proxy's header, so rate limits and history work per visitor (add `TRUSTED_PROXIES=` for a proxy on another address). HTTPS is detected behind the proxy too, so cookies are marked Secure and HSTS is sent.
* Extra browser protections (cross-origin isolation headers, no caching of signed-in pages), `/.well-known/security.txt`, and an **Admin → Security** checklist covering HTTPS, root database user, installer file left behind, debug mode, key location and more.
## 3e. Phase 7: Free tools for students and researchers (`/toolbox`)

A public directory of **links to free, legitimate resources**: video lessons (YouTube), free courses and open textbooks, tutorials, software to download, online tools, data and maps, and papers. It starts with about 70 well-known entries (MIT OpenCourseWare, Khan Academy, OpenStax, COMET MetEd, WRF and its online tutorial, Quantum ESPRESSO, Python/Jupyter, ERA5/Copernicus, arXiv, Unpaywall and more). All of them are yours to edit, hide or delete.

* **Admin → Free tools (links):** add one link (title, address, type, subject, who it is for, licence, optional "notes" in Markdown for install tips), or **Add many at once** (paste one link per line, optionally `link | title | subject | type`). A new subject you type becomes a new section automatically. YouTube video links show a thumbnail; channel and playlist links show an icon.
* **Check links:** press it now and then. Dead links (404, gone, unknown domain) are marked **broken and hidden from visitors** until you fix them; sites that refuse automatic checks are marked "check by hand"; slow or unreachable ones stay "not checked". It does 40 per press.
* Visitors can search and filter by type, subject and audience, open a resource (clicks are counted), and **suggest a link or report a broken one** (it arrives in Admin → Enquiries, and by email).
* **Keep it legitimate.** List only the owner's own site, open-access or openly licensed material and software its authors give away. Never link to pirated books, cracked software or unauthorised copies: it exposes you, your university name and your hosting to legal and reputational risk. The page tells visitors the same.
## 3f. Phase 8: licences and takedown policy

* **Admin → Free resources:** each item now has a **Licence** (my own work, Creative Commons variants, CC0, public domain, or shared with the owner's written permission), an **Author / rights holder** (the credit line; required for Creative Commons and permission items), an optional **source link** where the licence can be checked, and a **tick box confirming you have the right to share it**. You cannot publish without ticking it. The public page shows the licence badge (linked to the Creative Commons summary), the author, and a **Report a problem** link.
* **Report a problem** (copyright concern, broken file, wrong, other) lands in Admin → Enquiries and in your email. Handle copyright reports promptly: disable the item first (untick "Show on the website"), then review.
* **Copyright and takedown policy** at `/copyright` (footer link). It explains what the site publishes, how to report an infringement, what to include, and what you do next (acknowledge within 2 working days, disable promptly, tell the uploader, repeat-infringer rule). It is a **starting template, not legal advice**: edit it in Admin → Legal pages (the contact email fills in automatically from Contact & social) and have it reviewed, especially the response times you promise.
* Existing resources become "My own work" with the rights box already ticked: open each one and correct the licence if it is not yours.
## 3g. Phase 9: being found by Google, Bing, Yahoo and the rest

Built in, nothing to install:
* **`/sitemap.xml`** lists every public page (home, about, research, blog posts, shop products and categories, courses and their free-preview lessons, tools notes, legal pages, events…) with real "last changed" dates. **`/robots.txt`** points to it and keeps crawlers out of the admin area, accounts, cart, checkout and similar private pages.
* **Every page** carries a title, description, canonical address, Open Graph and Twitter sharing tags and a robots tag. Searches and filters (`?q=…`, `?tag=…`) are kept out of results automatically while crawlers can still follow their links.
* **Structured data (JSON-LD)** tells Google what things are: you (Person) and the site on the home page, Article (blog posts), Product with price in NGN and stock status (shop), Course with rating (courses), Event (upcoming events) and breadcrumbs on inner pages.
* **Placeholder content is never indexed:** sample posts, products and courses are left out of the sitemap and marked `noindex`. So are paid lessons, quizzes, certificates and downloads.
* **Blog feed** at `/blog/feed.xml`. **IndexNow** pings Bing/Yandex/Naver/Seznam when you publish a post (and the button on Admin → Search engines sends every page). Yahoo, DuckDuckGo and Ecosia use Bing's index.
* **A test site is never indexed.** Indexing turns on only when `APP_ENV=production` (or `ALLOW_INDEXING=1`); otherwise robots.txt says `Disallow: /`.

**What you must do once, on your live Ubuntu + Cloudflare site:**
1. In `config/.env`: `APP_ENV=production`, `APP_DEBUG=0`, `APP_URL=https://yourdomain.com` (the installer asks for the address if you choose "Live website").
2. Cloudflare: turn on *Always Use HTTPS*, redirect `www` to the bare domain (or the reverse), and do not enable "I'm Under Attack" mode or block bots (verified bots such as Googlebot are allowed by default).
3. Open **Admin → Search engines (SEO)** and follow the three steps there: verify in **Google Search Console** and **Bing Webmaster Tools** (paste the codes into the boxes), and submit `sitemap.xml` in both.

Be realistic: nobody can guarantee ranking. A new site is normally crawled within days and fully indexed within a few weeks; ranking depends on useful, original content, links from other sites (link to it from Google Scholar, ORCID, LinkedIn, ResearchGate and your university page) and time.
## 4. Phase 2: the news desk (unchanged)
See sections in the admin: Admin → **Science news** (stories, sources, rules, reader reports, log, kill-switch). Fetch with **Fetch news now** or schedule `cron/fetch_news.php`. Safeguards: tiered whitelist, HTTPS feeds on the source's own domains, no rewriting, strict BREAKING (needs a breaking word in the headline **and** a Tier 1 authority or enough independent Tier 1/2 sources; stale, probation and press-release stories never qualify), quality filters, 48-hour re-checks with auto-hide/update, source health auto-pause, public policy page, daily digest email. Source list status at install is shown in Admin; sources that had no reachable feed on 8 Oct 2026 are added paused with notes.

## 5. Phase 1 reference
Admin screens: Profile & photos, CV, Publications (DOI/ORCID/BibTeX/CSV import), Awards, Talks, Projects, Timeline, Testimonials, Blog posts (Markdown), Home page text, Brand, Contact & social, Site sections, Search & sharing, Bank details, Enquiries, Subscribers, Media library, Legal pages, My account, Activity log. Sample items are marked "sample" and disappear per section once you add real ones.

## 6. Email in test mode
`config/.env` has `MAIL_MODE=log`: nothing is sent; every email (order confirmations, receipts alerts, password resets, news digest…) is written to `storage/logs/mail.log`. For real sending set `MAIL_MODE=smtp` and fill the `SMTP_*` lines (not yet tested against a live mail server). On a live site also set `APP_URL`.

## 7. Folder map
```
public/         web root: index.php, assets/, uploads/ (public images), install.php
app/core/       Db, Router, Auth, Shop, Cart, Learn, Community, Latex, NewsEngine, Markdown, Upload, Mailer, Importer, Settings ...
app/controllers/  SiteController, NewsController, ShopController, LearnController, AccountController, Admin*Controller
app/views/      layout/, public/, admin/, partials/
config/         config.php and .env (created by the installer; never commit it)
cron/           fetch_news.php (every 10 min), shop_cleanup.php (hourly)
database/       schema.sql, seed.sql, migrations/ (applied once each, in order), fresh_install.sql (everything in one file)
storage/        logs, locks, keys/app.key (master key, back it up), private/receipts, private/videos, private/course-files (never web-accessible)
tools/          install_cli.php, migrate.php, create_admin.php, secure_config.php, reset_2fa.php, devrouter.php
```

## 8. Security notes
* Passwords hashed; sign-in throttled (5 failures per account / 10 per IP per 15 minutes); sessions regenerated on sign-in; idle sessions end after 2 hours; admin role required for every admin screen.
* CSRF token on every form; prepared statements everywhere; output escaped; Markdown escaped before formatting.
* Orders: unguessable 128-bit link per order; server-side pricing; stock locking in a transaction; rate limits and honeypot/timer on register, checkout, receipt upload, contact, newsletter, reports and password reset.
* Uploads: type checked by content, random names, images re-encoded, PHP execution blocked in `public/uploads`; receipts, lesson videos and lesson PDFs are private and served only after an access check.
* Courses: lessons, quizzes, files and videos are checked against an active enrolment on every request; quiz answers are marked on the server only, attempts and time limits are enforced on the server; the only third-party content allowed is YouTube (privacy mode) and Vimeo embeds.
* `app/`, `config/`, `cron/`, `database/`, `storage/`, `tools/` are denied by `.htaccess`.
* Before going live: `APP_ENV=production`, `APP_DEBUG=0`, HTTPS, delete `install.php`, real bank details, real delivery fees, reviewed Terms of Sale and Privacy Policy.

## 9. Verified on
PHP 8.3 on Apache 2.4 (WAMP build, sub-folder install) and PHP's built-in server; MariaDB 11.5 and MySQL 9.1 (Phases 1–2). Phase 3: 74 automated shop-engine checks (pricing, coupons, delivery fees, order placement, no-oversell including 6 simultaneous buyers, every status change and email, cancel/expiry stock return, variant edits), full web walk-throughs (register, login, lockout, forgot/reset password including expired and reused links, cart, checkout as guest and with account creation, pickup, receipt upload, admin confirm/pack/ship/deliver, product/stock/fee/coupon screens, printouts, CSV export, account deletion), the upgrade path and a fresh install. Phase 2: 45 news-engine checks and live feeds.


Phase 4: 14 grading cases (numeric tolerance and units, short answers, select-all), enrolment (paid, coupon, free, cancel, receipt upload), access control (anonymous, other student, cancelled), lesson player, quiz attempts (wrong then right, double submit, attempt limit, time-out), whole-course completion with certificate and verification, the Admin course builder (course, module, lesson with YouTube and PDF, quiz with every question type, deletes), MariaDB 11.5 and MySQL 9.1, upgrade from a Phase 3 database and fresh installs (installer and `fresh_install.sql`).

Phase 5: shop categories (create, auto-slug, duplicate names, delete), one-size products with options through cart, checkout, order, admin order, slip and pick list, product copy, back-in-stock alerts (email sent once on restock), course questions (validation, enrolled-only, answer email, privacy of names), reviews (moderation, edit resets to pending, enrolled-only, stars in catalogue), announcements (email and visibility), services, topics, events (upcoming/past), press, links (address validation), resources (PDF upload, counted download), promo settings, sitemap, upgrade from a Phase 4 database, fresh installs, MariaDB 11.5 and MySQL 9.1.

Phase 6: QR generator checked with an independent reader (format bits, Reed-Solomon, 10 sizes), encryption round trip, installer with a dedicated database user and encrypted password, encrypt / dedicated-user / rotate-key / key-file tools, 2-step set-up, sign-in with code, replay refusal, recovery codes (single use), lockout, customer and administrator flows, admin reset, enforcement for administrators, sign out everywhere, browser binding, new-browser alert, password rules, proxy address header, upgrade and fresh installs on MariaDB 11.5 and MySQL 9.1. Not tested: scanning the QR with a physical phone (the setup key is always shown as a fallback) and real SMTP delivery.

Phase 7: directory pages, filters, search, notes pages, click counting, suggestion form, bulk add (duplicates, bad lines), URL change resets link status, address validation, live link check of the starter list (broken links hidden, bot-blocking sites flagged), fresh install and upgrade. Links marked unverified in the check list could not be reached from the build machine; run Check links from your own connection.

Phase 8: licence/author/rights rules (blocked without the tick, credit required for CC), file-or-link rule, licence badges and report form (saves to Enquiries and emails), the policy page and its contact-email token, footer link and sitemap, upgrade and fresh install.

Phase 9: robots.txt (live and test modes), sitemap validity and contents (samples and private pages excluded, real posts and products included), canonical and robots meta on home, private, filtered and paginated pages, X-Robots-Tag header, JSON-LD validity (WebSite, Person, BlogPosting, Product, BreadcrumbList), blog feed validity, verification tags, IndexNow key file. Not tested: real Google/Bing registration and the IndexNow call (needs your live domain; the local certificate bundle blocked it).

**Sub-folder installs and the right address:** crawlers read only the `robots.txt` at the **domain root**, so on a site in a sub-folder (for example `https://yourdomain.org/clearsky_academy/`) the private-page rules in this site's own robots.txt are ignored; the pages stay protected by `noindex` tags, and Admin → Search engines shows the lines to give the domain owner. A separate domain or sub-domain with the web root set to the `public` folder is cleanest. `APP_URL` must be exactly the address visitors use (including or excluding `www`, whichever your site redirects to): a mismatch makes Google see redirected addresses in the sitemap and canonical links. Visits to `/public/…` are redirected to the clean address (Apache `.htaccess`).

**Messages and email results (Phase 9c):** after a visitor sends the contact form or subscribes to the newsletter, the page now tells them plainly whether the email went out. A clear green "Message sent" box appears above the form on success; on failure a red box explains that the message is saved safely and gives direct contact details, and what they typed stays in the form. A failed enquiry email is flagged in Admin → Enquiries ("email alert failed"), signed-in administrators see a warning whenever any email fails, and the dashboard has a **Send a test email to me** button. Technical error details are never shown to visitors.

**Authors and guest articles (Phase 9e):** every blog post now shows who wrote it: "By NAME" under the title, an author box with the name and a contact email link, the author in the blog list, the Google structured data and the RSS feed. Posts you write yourself use your name and contact email from the site settings (or the **Author name / Author contact email** boxes on the post form, if you want something else). **Registered users can write articles** under *My account → My articles* (drafts, a preview, an optional cover picture) and **submit them for approval**. Nothing is published until you open **Admin → Article submissions**, read it, and either **Approve and publish** (the author is emailed) or **Send back for changes** with a note (also emailed; the author edits and resubmits). A published guest article carries the author's **registered name and email address**; the author ticks a consent box when submitting. Guest text is shown with no outside pictures and with nofollow links. The review page shows whether the author's email was verified by a code. If an author deletes their account, their published articles stay under their name but the email is removed, and their unpublished ones are deleted.

**Email verification at sign-up (Phase 9f):** a new account is created only after the person proves the email address is theirs. After the sign-up form (or the optional "create an account" box at checkout) the site emails a **6-digit code**, and the person types it on the *Verify your email* page; only then are they signed in. Codes last 15 minutes, allow 5 wrong tries (then a new code is needed), can be re-sent once a minute (5 per hour), and are stored only as salted hashes. Signing in before verifying sends the person back to the code page. "Wrong address? Start again" removes the unverified sign-up, and signing up again with an address that was never verified replaces the old attempt, so nobody can block someone else's address. Password reset by email link also counts as verification. **Existing accounts are not locked out:** they can still sign in, but must press *Verify my email* (My account) before they can submit a guest article. The administrator accounts are treated as verified. Admin → Customers and the article review page show who is verified. **This needs working email** (Admin → dashboard → *Send a test email to me*); in test mode the code is written to `storage/logs/mail.log`. Upgrading: run "Apply update now" (or `php tools/migrate.php`).

Phase 9f tests: sign-up, wrong/right/expired/locked codes, resend cooldown, replace-unverified, verified address protected, cancel, checkout-created accounts, legacy accounts, article submission gate, password-reset verification, admin views, upgrade of a Phase 9e database and fresh install.

**Check-up questions and module PDFs (Phase 9g):** each lesson can have a **question bank** (Admin → Courses → Curriculum → open a lesson → *Check-up questions for this lesson*). Use **multiple choice** (options A to D) or **short text answer** as a fill-in-the-gap (write the blank as `____` in the question and list the accepted words). When a student finishes a lesson the site asks **one question drawn at random** from the bank (change the number under *Manage the question bank*, up to 10); they must answer it to move on, and see right or wrong with your explanation. A lesson with no questions behaves as before, and lessons a student had already completed are never re-opened. After the **last lesson of a module** the student sees the **module summary**: their total score (for information only: it is not a grade, does not affect progress or the certificate), a lesson-by-lesson list, a **Download full module PDF** button and a **Next module** button. The PDF button appears only once every lesson in the module is complete. Upload the module PDF on the curriculum page (*Complete module PDF*); if you have not uploaded one, the button opens all the module's notes on one printable page ("Save as PDF"). Question banks are hidden from the quiz list and do not count towards course completion. Upgrading: run "Apply update now" (or `php tools/migrate.php`).

Phase 9g tests: bank creation and settings, skip attempt through the Complete button, empty/wrong/right answers, no re-answering, lessons without a bank, locked summary and PDF, score total, next-module link, uploaded and fallback PDFs, access control (unfinished student, anonymous, bank opened as a quiz), upgrade from a Phase 9f database and a fresh install.

**Course chat and course lockdown (Phase 9h):** every course has a **chat room** (Course page → *Course chat*, also in the lesson sidebar). Enrolled students and the lecturer (you) can talk; new messages appear by themselves every few seconds and Enter sends. Messages are plain text; students appear as "Ada O." (first name and initial), the lecturer by name with a *Lecturer* badge. Students can delete their own messages; you can delete any message and **mute** a student (🔇 beside the message) so they can still read but not post. Switch the chat off per course under *Admin → Courses → Course details → Chat room*. To **lock a course for a period**, open *Course details* → *Lock this course for a period*: choose when the lock ends (and optionally when it starts; empty means "lock now"). The lock must end in the future and may not start in the past, and it lifts by itself at the end time (Nigeria time). While locked, students and visitors who open any page of the course (lessons, quizzes, chat, downloads) see: "This course is currently not available, please check back by 6:00 PM, 25 October 2026." (the page is sent as a temporary 503 with a *Retry-After* header, so search engines treat it as temporary). **Administrators are never locked out** and see a banner on the course page; the catalogue card shows when a locked course is back. To lift a lock early, tick *Remove the lock now* and save. Upgrading: run "Apply update now" (or `php tools/migrate.php`).

Phase 9h tests: chat access (anonymous, not enrolled, enrolled), posting (live and plain), escaping, short names, empty/long/duplicate messages, lecturer badge, delete and mute rules, chat switched off, lock validation (past end, past start, end before start, start without end), visitors during a lock (every course page, other courses unaffected), administrator exemption, editing a running lock, clearing, scheduled and expired locks, upgrade from a Phase 9g database and a fresh install.

<?php if (!empty($pendingUpdates)): ?>
<section class="card note">
  <h2>Database update available</h2>
  <p>New features need a quick database update (<?= e(implode(', ', $pendingUpdates)) ?>). Your content is not touched. <strong>Back up your database first if this is a live site.</strong></p>
  <form method="post" action="<?= e(url('/admin/update')) ?>"><?= csrf_field() ?><button class="btn btn-gold" type="submit">Apply update now</button></form>
</section>
<?php endif; ?>

<div class="cards-row">
  <a class="stat-card" href="<?= e(url('/admin/enquiries')) ?>"><strong><?= (int)$counts['new_enquiries'] ?></strong><span>new enquiries</span></a>
  <a class="stat-card" href="<?= e(url('/admin/subscribers')) ?>"><strong><?= (int)$counts['subscribers'] ?></strong><span>subscribers<?= $counts['pending_subs'] ? ' (+' . (int)$counts['pending_subs'] . ' unconfirmed)' : '' ?></span></a>
  <a class="stat-card" href="<?= e(url('/admin/publications')) ?>"><strong><?= (int)$counts['publications'] ?></strong><span>publications</span></a>
  <a class="stat-card" href="<?= e(url('/admin/posts')) ?>"><strong><?= (int)$counts['posts'] ?></strong><span>published posts</span></a>
  <a class="stat-card" href="<?= e(url('/admin/articles')) ?>"><strong><?= (int)$counts['pending_articles'] ?></strong><span>articles to review</span></a>
</div>

<section class="card">
  <h2>Email</h2>
  <p class="muted">Mode: <strong><?= e(env('MAIL_MODE', 'log') === 'smtp' ? 'sending through SMTP' : (env('MAIL_MODE', 'log') === 'mail' ? 'PHP mail()' : 'test mode (messages are only logged)')) ?></strong>. Visitors are told clearly whether their contact message was delivered.</p>
  <form method="post" action="<?= e(url('/admin/mail-test')) ?>"><?= csrf_field() ?><button class="btn btn-navy" type="submit">Send a test email to me</button></form>
</section>

<div class="two-col">
  <section class="card">
    <h2>Make it yours</h2>
    <p class="muted">Your site shows neat placeholders until you fill these in. Each takes about a minute.</p>
    <ul class="checklist">
      <?php foreach ($todo as [$label, $done, $href]): ?>
        <li class="<?= $done ? 'done' : '' ?>"><span class="tick"><?= $done ? '✔' : '○' ?></span> <a href="<?= e(url($href)) ?>"><?= e($label) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="card">
    <h2>Recent enquiries</h2>
    <?php if ($recent): ?>
      <table class="table"><tbody>
      <?php foreach ($recent as $r): ?>
        <tr><td><a href="<?= e(url('/admin/enquiries/' . $r['id'])) ?>"><?= e($r['name']) ?></a><br><small class="muted"><?= e($r['subject'] ?: ucfirst($r['type'])) ?></small></td><td><?= e(fmt_date($r['created_at'], 'j M, H:i')) ?></td><td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    <?php else: ?><p class="empty">No enquiries yet.</p><?php endif; ?>
  </section>
</div>

<?php if (!empty($learn)): ?>
<section class="card">
  <h2>Courses</h2>
  <p>
    <a href="<?= e(url('/admin/enrolments?status=awaiting_payment')) ?>"><strong><?= (int)$learn['pending'] ?></strong> payments to confirm<?= $learn['receipts'] ? ' (' . (int)$learn['receipts'] . ' with a receipt)' : '' ?></a> ·
    <strong><?= (int)$learn['students'] ?></strong> active students ·
    <strong><?= (int)$learn['certs'] ?></strong> certificates issued ·
    <a href="<?= e(url('/admin/qa')) ?>"><strong><?= (int)$learn['questions'] ?></strong> student questions to answer</a> ·
    <a href="<?= e(url('/admin/reviews')) ?>"><strong><?= (int)$learn['reviews'] ?></strong> reviews to approve</a>
  </p>
  <p class="muted"><a href="<?= e(url('/admin/courses')) ?>">Manage courses and lessons →</a></p>
</section>
<?php endif; ?>

<?php if (!empty($shop)): ?>
<section class="card">
  <h2>Shop</h2>
  <p>
    <a href="<?= e(url('/admin/orders?status=awaiting_payment')) ?>"><strong><?= (int)$shop['awaiting'] ?></strong> awaiting payment<?= $shop['receipts'] ? ' (' . (int)$shop['receipts'] . ' with a receipt to check)' : '' ?></a> ·
    <a href="<?= e(url('/admin/orders?status=paid')) ?>"><strong><?= (int)$shop['topack'] ?></strong> to pack</a> ·
    <strong><?= (int)$shop['transit'] ?></strong> in transit ·
    <a href="<?= e(url('/admin/shop/stock?low=1')) ?>" <?= $shop['low'] ? 'style="color:#a05a00"' : '' ?>><strong><?= (int)$shop['low'] ?></strong> low-stock items</a>
  </p>
  <?php if (!empty($shop['waiting'])): ?><p class="muted"><?= (int)$shop['waiting'] ?> shopper<?= $shop['waiting'] === 1 ? ' is' : 's are' ?> waiting to be told when a sold-out product is back. <a href="<?= e(url('/admin/shop/products')) ?>">See products</a></p><?php endif; ?>
  <p class="muted">Paid this month: <strong><?= e(Shop::naira($shop['month'])) ?></strong>. <a href="<?= e(url('/admin/orders?view=board')) ?>">Open the order board →</a></p>
</section>
<?php endif; ?>

<?php if (!empty($news)): ?>
<section class="card">
  <h2>Science news</h2>
  <p>
    <strong><?= (int)$news['stories'] ?></strong> new stories in the last 24 h ·
    <strong><?= (int)$news['breaking'] ?></strong> breaking now ·
    <a href="<?= e(url('/admin/news/reports')) ?>"><strong><?= (int)$news['reports'] ?></strong> reader reports waiting</a>
    <?php if ($news['bad']): ?> · <a href="<?= e(url('/admin/news/sources')) ?>" style="color:#a02727"><strong><?= (int)$news['bad'] ?></strong> sources failing</a><?php endif; ?>
    <?php if ($news['paused']): ?> · <?= (int)$news['paused'] ?> paused<?php endif; ?>
  </p>
  <p class="muted">Last automatic update: <?= $news['lastRunAt'] ? e(time_ago($news['lastRunAt'])) : '<strong>never</strong>: press “Fetch news now”; after that it updates itself every ' . (int)(NewsEngine::intervalSeconds() / 3600) . ' h' ?>.<?= $news['lastRunAt'] ? ' Next: about ' . e(date('H:i', NewsEngine::nextDueAt())) . '.' : '' ?>
  <a href="<?= e(url('/admin/news')) ?>">Manage news →</a></p>
</section>
<?php endif; ?>

<?php if ($samples > 0): ?>
<section class="card note">
  <h2>Sample content</h2>
  <p>There are <strong><?= (int)$samples ?></strong> sample items (marked “sample” on the website). They disappear by themselves for each section as soon as you add your own. To remove them all right now:</p>
  <form method="post" action="<?= e(url('/admin/samples/clear')) ?>" data-confirm="Delete all sample items? Your own content is not touched."><?= csrf_field() ?><button class="btn btn-danger" type="submit">Delete all sample content</button></form>
</section>
<?php endif; ?>

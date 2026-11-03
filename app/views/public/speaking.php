<?php
$shortBio = setting('media_bio_short') ?: setting('bio_short');
$longBio = setting('media_bio_long') ?: strip_tags(md(setting('bio_full')));
$portrait = setting('portrait');
$reqs = nl_lines((string)setting('speaker_requirements'));
?>
<section class="page-hero">
  <div class="wrap">
    <h1>Speaking and media kit</h1>
    <p class="lead"><?= e(setting('speaker_intro')) ?></p>
    <p><a class="btn btn-gold" href="<?= e(url('/contact?type=speaking')) ?>">Invite me to speak</a> <a class="btn btn-outline" href="#media-kit">Media kit</a></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <h2 class="section-title">Talks I give</h2>
    <?php if ($topics): ?>
      <div class="grid g3">
        <?php foreach ($topics as $t): ?>
          <article class="card topic">
            <h3><?= e($t['title']) ?><?php if ($t['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></h3>
            <?php if ($t['summary']): ?><p><?= nl2br(e($t['summary'])) ?></p><?php endif; ?>
            <p class="muted small"><?= $t['audience'] !== '' ? 'For: ' . e($t['audience']) : '' ?><?= $t['audience'] !== '' && $t['duration'] !== '' ? ' · ' : '' ?><?= $t['duration'] !== '' ? e($t['duration']) : '' ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?><p class="empty">Topics will be listed here soon.</p><?php endif; ?>
    <?php if ($reqs || setting('speaker_fee_note') || setting('speaker_lead_time')): ?>
    <div class="card" style="margin-top:24px">
      <h3>For organisers</h3>
      <?php if (setting('speaker_lead_time')): ?><p><?= e(setting('speaker_lead_time')) ?></p><?php endif; ?>
      <?php if (setting('speaker_fee_note')): ?><p><?= e(setting('speaker_fee_note')) ?></p><?php endif; ?>
      <?php if ($reqs): ?><ul><?php foreach ($reqs as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($events): ?>
<section class="section alt">
  <div class="wrap">
    <h2 class="section-title">Where to hear me next</h2>
    <ul class="event-list"><?php foreach ($events as $ev): partial('event_row', ['ev' => $ev]); endforeach; ?></ul>
    <p><a href="<?= e(url('/events')) ?>">All events →</a></p>
  </div>
</section>
<?php endif; ?>

<section class="section" id="media-kit">
  <div class="wrap">
    <h2 class="section-title">Media kit</h2>
    <p class="muted">For event programmes, press and introductions. Copy the text or download the photo.</p>
    <div class="grid g2">
      <div class="card">
        <h3>Short bio</h3>
        <p id="bio-short"><?= nl2br(e($shortBio)) ?></p>
        <button class="btn btn-sm btn-ghost" type="button" data-copy="<?= e($shortBio) ?>">Copy</button>
      </div>
      <div class="card">
        <h3>Long bio</h3>
        <p id="bio-long"><?= nl2br(e($longBio)) ?></p>
        <button class="btn btn-sm btn-ghost" type="button" data-copy="<?= e($longBio) ?>">Copy</button>
      </div>
    </div>
    <div class="card" style="margin-top:20px">
      <h3>Name and title</h3>
      <p><strong><?= e(setting('owner_name')) ?></strong><br><?= e(setting('owner_title')) ?><br><?= e(setting('owner_institution')) ?></p>
      <?php if ($portrait): ?><p><img src="<?= e(upload_url($portrait)) ?>" alt="Portrait of <?= e(setting('owner_name')) ?>" class="media-portrait"><br><a class="btn btn-sm btn-navy" href="<?= e(upload_url($portrait)) ?>" download>Download photo</a></p>
      <?php else: ?><p class="muted small">A downloadable photo will be added here.</p><?php endif; ?>
    </div>
  </div>
</section>

<?php if ($talks): ?>
<section class="section alt">
  <div class="wrap">
    <h2 class="section-title">Recent talks and media</h2>
    <ul class="plain-links">
      <?php foreach ($talks as $t): ?><li><strong><?= $t['url'] ? '<a href="' . e($t['url']) . '" target="_blank" rel="noopener noreferrer">' . e($t['title']) . '</a>' : e($t['title']) ?></strong> <span class="muted">· <?= e($t['event']) ?><?= $t['talk_date'] ? ', ' . e(fmt_date($t['talk_date'], 'M Y')) : '' ?></span></li><?php endforeach; ?>
    </ul>
    <p><a href="<?= e(url('/press')) ?>">In the media →</a></p>
  </div>
</section>
<?php endif; ?>

<?php if ($testimonials): ?>
<section class="section">
  <div class="wrap">
    <h2 class="section-title">What people say</h2>
    <div class="grid g3"><?php foreach ($testimonials as $t): ?><blockquote class="card quote"><p><?= e($t['quote']) ?></p><footer><strong><?= e($t['name']) ?></strong><?= $t['role'] !== '' ? ' · ' . e($t['role']) : '' ?></footer></blockquote><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>

<section class="band">
  <div class="wrap band-inner"><div><h2>Planning an event?</h2><p>Tell me the date, audience and topic, and I will reply with availability and a quote.</p></div><a class="btn btn-gold btn-lg" href="<?= e(url('/contact?type=speaking')) ?>">Send an invitation</a></div>
</section>

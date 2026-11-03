<section class="page-hero">
  <div class="wrap">
    <h1>Work with me</h1>
    <p class="lead"><?= e(setting('work_intro')) ?></p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <?php if ($services): ?>
    <div class="services">
      <?php foreach ($services as $s): ?>
        <article class="card service" id="s<?= (int)$s['id'] ?>">
          <h2><?= $s['icon'] !== '' ? '<span aria-hidden="true">' . e($s['icon']) . '</span> ' : '' ?><?= e($s['title']) ?><?php if ($s['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></h2>
          <?php if ($s['summary'] !== ''): ?><p class="lead-sm"><?= e($s['summary']) ?></p><?php endif; ?>
          <?php if (trim((string)$s['details']) !== ''): ?><div class="prose"><?= md($s['details']) ?></div><?php endif; ?>
          <p class="service-foot"><?php if ($s['price_note'] !== ''): ?><span class="tag"><?= e($s['price_note']) ?></span> <?php endif; ?><a class="btn btn-gold" href="<?= e(url('/contact?type=' . $s['enquiry_type'])) ?>">Enquire</a></p>
        </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?><p class="empty">Services will be listed here soon. In the meantime, <a href="<?= e(url('/contact')) ?>">get in touch</a>.</p><?php endif; ?>
    <p class="center" style="margin-top:2rem"><a href="<?= e(url('/speaking')) ?>">Looking for a speaker? See the speaking page and media kit →</a></p>
  </div>
</section>

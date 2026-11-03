<section class="page-hero"><div class="wrap"><h1><?= e($c['title']) ?></h1></div></section>
<section class="section">
  <div class="wrap narrow">
    <div class="card lock-box" style="text-align:center;padding:36px 24px">
      <p style="font-size:44px;margin:0">🔒</p>
      <h2>This course is currently not available</h2>
      <p class="lead-sm">This course is currently not available, please check back by <strong><?= e($until) ?></strong>.</p>
      <p><a class="btn btn-gold" href="<?= e(url('/learn')) ?>">See other courses</a> <a class="btn btn-ghost-dark" href="<?= e(url('/')) ?>">Home</a></p>
    </div>
  </div>
</section>

<section class="page-hero"><div class="wrap"><h1><?= e($name) ?></h1></div></section>
<section class="section">
  <div class="wrap narrow center">
    <div class="soon-icon"><?= $icon ?></div>
    <h2>Coming soon</h2>
    <p class="lead"><?= e($text) ?></p>
    <div class="card inline-form"><?php partial('newsletter_form'); ?></div>
    <p><a href="<?= e(url('/')) ?>">← Back to the home page</a></p>
  </div>
</section>

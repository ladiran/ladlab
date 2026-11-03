<section class="section"><div class="wrap narrow center">
  <p class="eyebrow dark"><?= (int)$code ?></p>
  <h1><?= e($heading) ?></h1>
  <p class="lead"><?= e($message !== '' ? $message : ($code === 404 ? 'We could not find that page. It may have moved.' : 'Please go back and try again.')) ?></p>
  <p><a class="btn btn-navy" href="<?= e(url('/')) ?>">Home page</a></p>
</div></section>

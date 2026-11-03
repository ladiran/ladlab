<section class="page-hero"><div class="wrap"><h1>Certificate verified ✔</h1><p class="lead">This certificate was issued by <?= e(setting('site_name')) ?>.</p></div></section>
<section class="section">
  <div class="wrap narrow">
    <div class="card cert-card">
      <p class="muted small">Certificate of completion</p>
      <h2><?= e($cert['student_name']) ?></h2>
      <p>successfully completed the course</p>
      <h3><?= e($cert['course_title']) ?></h3>
      <p class="muted">Issued <?= e(fmt_date($cert['issued_at'], 'j F Y')) ?><br>Verification code <strong><?= e($cert['code']) ?></strong></p>
      <p><a class="btn btn-gold" href="<?= e(url('/certificate/' . $cert['code'] . '/print')) ?>" target="_blank">🖨 View / print the certificate</a></p>
    </div>
    <p class="small muted">Anyone can check a certificate by opening this page address or by entering the code in the address <code><?= e(abs_url('certificate/CODE')) ?></code>.</p>
  </div>
</section>

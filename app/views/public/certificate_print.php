<div class="certificate" style="border:10px double #0b1f3a;padding:48px 40px;text-align:center;max-width:900px;margin:0 auto;font-family:Georgia,serif;background:#fff">
  <div style="font-size:15px;letter-spacing:.3em;color:#d98a0c;font-weight:700">☼ <?= e(strtoupper(setting('site_name'))) ?></div>
  <h1 style="font-size:44px;margin:28px 0 8px;color:#0b1f3a">Certificate of Completion</h1>
  <p style="color:#555;margin:0 0 26px">This is to certify that</p>
  <div style="font-size:38px;font-style:italic;border-bottom:2px solid #0b1f3a;display:inline-block;padding:0 40px 4px;color:#0b1f3a"><?= e($cert['student_name']) ?></div>
  <p style="color:#555;margin:26px 0 8px">has successfully completed the course</p>
  <div style="font-size:30px;font-weight:700;color:#0b1f3a"><?= e($cert['course_title']) ?></div>
  <p style="color:#555;margin:34px 0 0">Issued on <?= e(fmt_date($cert['issued_at'], 'j F Y')) ?></p>
  <div style="display:flex;justify-content:space-around;margin-top:46px;font-size:14px">
    <div><div style="border-top:1px solid #0b1f3a;padding-top:6px;min-width:220px"><?= e(setting('owner_name')) ?><br><span style="color:#555"><?= e(setting('owner_title')) ?></span></div></div>
    <div><div style="border-top:1px solid #0b1f3a;padding-top:6px;min-width:220px">Verification code<br><strong><?= e($cert['code']) ?></strong></div></div>
  </div>
  <p style="font-size:12px;color:#777;margin-top:26px">Verify at <?= e(abs_url('certificate/' . $cert['code'])) ?></p>
</div>

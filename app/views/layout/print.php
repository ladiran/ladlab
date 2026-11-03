<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?></title>
<style>
  body{font-family:"Segoe UI",Arial,sans-serif;color:#111;margin:0;padding:24px;font-size:14px;line-height:1.45}
  h1{font-size:20px;margin:0 0 4px}h2{font-size:15px;margin:16px 0 6px}
  table{width:100%;border-collapse:collapse;margin-top:8px}
  th,td{border:1px solid #999;padding:6px 8px;text-align:left;vertical-align:top}th{background:#eee}
  .bar{display:flex;gap:12px;align-items:center;margin-bottom:16px}
  .bar button{padding:8px 18px;font-size:14px;cursor:pointer}
  .muted{color:#555}.big{font-size:22px;font-weight:700}
  .label{border:3px solid #000;padding:18px 22px;max-width:520px}
  .label .to{font-size:24px;line-height:1.3;margin:6px 0 12px}
  .box{display:inline-block;width:16px;height:16px;border:2px solid #000;vertical-align:middle}
  .sheet{page-break-after:always;margin-bottom:28px}
  @media print{.bar{display:none}body{padding:0}}
</style>
</head>
<body>
<div class="bar"><button type="button" data-print>🖨 Print</button><a href="#" data-back>← Back</a></div>
<?= $content ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>

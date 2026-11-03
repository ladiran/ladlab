<style>
  .notes{max-width:820px;margin:0 auto;font-family:Georgia,"Times New Roman",serif;font-size:15px;line-height:1.55}
  .notes h1{font-size:26px;margin:0 0 4px}.notes h2{font-size:21px;margin:30px 0 6px;border-bottom:1px solid #999;padding-bottom:3px}
  .notes .obj{background:#f3f3f3;padding:8px 12px;margin:8px 0 12px;font-family:"Segoe UI",Arial,sans-serif;font-size:13px}
  .notes img{max-width:100%}.notes pre,.notes code{font-family:Consolas,monospace;font-size:13px}.notes pre{background:#f3f3f3;padding:8px;overflow:auto}
  .notes table{width:100%}.notes blockquote{border-left:3px solid #999;margin-left:0;padding-left:12px;color:#444}
  .notes .lesson{page-break-inside:auto}.notes .lesson+.lesson{page-break-before:always}
</style>
<div class="notes">
  <p class="muted"><?= e(setting('site_name')) ?> · <?= e($c['title']) ?></p>
  <h1>Module <?= (int)$num ?>: <?= e($m['title']) ?></h1>
  <?php if ($m['summary'] !== ''): ?><p class="muted"><?= e($m['summary']) ?></p><?php endif; ?>
  <?php foreach ($lessons as $i => $l): ?>
    <section class="lesson">
      <h2><?= $i + 1 ?>. <?= e($l['title']) ?></h2>
      <?php if ($l['objectives']): ?><div class="obj"><strong>By the end of this lesson you can:</strong> <?= e($l['objectives']) ?></div><?php endif; ?>
      <div class="prose"><?= md((string)$l['content']) ?></div>
    </section>
  <?php endforeach; ?>
  <p class="muted" style="margin-top:30px;font-size:12px">© <?= e(setting('owner_name')) ?> · <?= e(abs_url('learn/' . $c['slug'])) ?>. For personal study by enrolled students.</p>
</div>

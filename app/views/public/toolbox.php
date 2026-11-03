<section class="page-hero">
  <div class="wrap">
    <h1>Free tools for students and researchers</h1>
    <p class="lead">Video lessons, open textbooks, software, data and step-by-step tutorials for physics, mathematics and meteorology, including how to get started with models such as WRF and Quantum ESPRESSO. Every link goes to the owner's own site.</p>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <form method="get" action="<?= e(url('/toolbox')) ?>" class="filters">
      <label class="sr-only" for="tq">Search</label>
      <input type="search" id="tq" name="q" value="<?= e($f['q']) ?>" placeholder="Search, e.g. WRF, calculus, Python…" maxlength="60">
      <label class="sr-only" for="tk">Type</label>
      <select id="tk" name="kind"><option value="">All types</option><?php foreach (Toolbox::KINDS as $k => $l): ?><option value="<?= e($k) ?>" <?= $f['kind'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <label class="sr-only" for="ts">Subject</label>
      <select id="ts" name="subject"><option value="">All subjects</option><?php foreach ($subjects as $s): ?><option <?= $f['subject'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
      <label class="sr-only" for="ta">For</label>
      <select id="ta" name="audience"><option value="">Everyone</option><?php foreach (Toolbox::AUDIENCES as $k => $l): if ($k === 'all') { continue; } ?><option value="<?= e($k) ?>" <?= $f['audience'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <button class="btn btn-navy" type="submit">Find</button>
      <?php if ($filtered): ?><a class="btn btn-ghost-dark" href="<?= e(url('/toolbox')) ?>">Clear</a><?php endif; ?>
    </form>

    <?php if (!$filtered): ?>
      <nav class="chips" aria-label="Jump to a type"><?php foreach ($groups as $k => $items): ?><a class="chip" href="#k-<?= e($k) ?>"><?= e(Toolbox::ICONS[$k] ?? '') ?> <?= e(Toolbox::KINDS[$k] ?? $k) ?> <small>(<?= count($items) ?>)</small></a><?php endforeach; ?></nav>
      <?php foreach ($groups as $k => $items): ?>
        <h2 class="section-title" id="k-<?= e($k) ?>"><?= e(Toolbox::ICONS[$k] ?? '') ?> <?= e(Toolbox::KINDS[$k] ?? $k) ?></h2>
        <div class="grid g3 tools"><?php foreach ($items as $t): partial('toolbox_card', ['t' => $t]); endforeach; ?></div>
      <?php endforeach; ?>
      <?php if (!$groups): ?><p class="empty">Resources will be listed here soon.</p><?php endif; ?>
    <?php else: ?>
      <p class="muted small"><?= count($rows) ?> result<?= count($rows) === 1 ? '' : 's' ?></p>
      <?php if ($rows): ?><div class="grid g3 tools"><?php foreach ($rows as $t): partial('toolbox_card', ['t' => $t]); endforeach; ?></div>
      <?php else: ?><p class="empty">Nothing matches. Try fewer words, or suggest the resource below.</p><?php endif; ?>
    <?php endif; ?>

    <p class="muted small" style="margin-top:2rem">These are links to other websites, which we do not control; always follow each owner's licence and terms. Concerned about a link? See our <a href="<?= e(url('/copyright')) ?>">copyright and takedown policy</a>. Looking for notes written by me? See <a href="<?= e(url('/resources')) ?>">my free downloads</a>.</p>
    <?php partial('toolbox_suggest', ['back' => 'toolbox']); ?>
  </div>
</section>

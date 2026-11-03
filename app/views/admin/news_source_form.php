<?php
partial('news_tabs', ['tab' => $tab]);
$old = $_SESSION['old'] ?? null;
$v = function (string $k, $default = '') use ($row, $old) {
    if ($old !== null) {
        return $old[$k] ?? $default;
    }
    return $row[$k] ?? $default;
};
$chk = function (string $k, int $default = 0) use ($row, $old) {
    if ($old !== null) {
        return isset($old[$k]);
    }
    return (int)($row[$k] ?? $default) === 1;
};
$action = $row ? '/admin/news/sources/' . $row['id'] : '/admin/news/sources/new';
?>
<p><a href="<?= e(url('/admin/news/sources')) ?>">← All sources</a></p>

<?php if (!empty($test)): ?>
<section class="card test-result <?= $test['ok'] ? 'test-ok' : 'test-bad' ?>">
  <h2><?= $test['ok'] ? '✔ This feed looks good' : '⚠ Please review these results' ?></h2>
  <ul class="checks">
    <?php foreach ($test['checks'] as $c): ?><li class="<?= $c['ok'] ? 'ok' : 'bad' ?>"><span><?= $c['ok'] ? '✔' : '✘' ?></span> <strong><?= e($c['label']) ?></strong><?= $c['detail'] ? ' <span class="muted">· ' . e($c['detail']) . '</span>' : '' ?></li><?php endforeach; ?>
  </ul>
  <?php if ($test['items']): ?>
    <p><strong>Latest stories in this feed:</strong></p>
    <ul><?php foreach ($test['items'] as $it): ?><li><?= e($it['title']) ?> <small class="muted">· <?= e($it['date']) ?></small></li><?php endforeach; ?></ul>
  <?php endif; ?>
  <p class="muted small">Nothing was saved by this test.</p>
</section>
<?php endif; ?>

<form method="post" action="<?= e(url($action)) ?>" class="form admin-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>">

  <?php
  $F = function (string $name, string $label, string $help = '', string $type = 'text') use ($v) {
      partial('field', ['name' => $name, 'type' => $type, 'label' => $label, 'o' => ['help' => $help], 'value' => $v($name)]);
  };
  $F('name', 'Source name', 'As readers should see it, e.g. “NASA”.');
  $F('site_url', 'Website', 'The publisher’s home page, e.g. https://www.nasa.gov', 'url');
  $F('feed_url', 'Feed address (RSS or Atom)', 'Must start with https://. Look for an RSS icon or a link such as /feed or /rss.', 'url');
  $F('domains', 'Allowed story-link domains', 'Only stories whose links belong to these domains are published (comma-separated, e.g. bbc.co.uk, bbc.com). Leave blank to use the website’s domain.');
  ?>
  <div class="form-row">
    <?php
    partial('field', ['name' => 'tier', 'type' => 'select', 'label' => 'Tier', 'o' => ['options' => [1 => 'Tier 1: official / primary source', 2 => 'Tier 2: reputable press', 3 => 'Tier 3: press-release wire'], 'help' => 'Press releases (Tier 3) are labelled and never count towards “breaking”.'], 'value' => $v('tier', 2)]);
    partial('field', ['name' => 'region', 'type' => 'select', 'label' => 'Region', 'o' => ['options' => NewsEngine::REGIONS], 'value' => $v('region', 'world')]);
    ?>
  </div>
  <div class="form-row">
    <?php
    partial('field', ['name' => 'kind', 'type' => 'select', 'label' => 'Type', 'o' => ['options' => ['news' => 'News', 'primary' => 'Official / primary', 'press_release' => 'Press releases']], 'value' => $v('kind', 'news')]);
    partial('field', ['name' => 'default_topic', 'type' => 'select', 'label' => 'Default topic', 'o' => ['options' => NewsEngine::TOPICS, 'help' => 'Used when a story’s headline does not reveal its topic.'], 'value' => $v('default_topic', 'other')]);
    ?>
  </div>
  <fieldset class="options">
    <legend>Rules for this source</legend>
    <label class="check"><input type="checkbox" name="topic_filter" value="1" <?= $chk('topic_filter') ? 'checked' : '' ?>> General news feed: keep only stories that pass the science-topic check</label>
    <label class="check"><input type="checkbox" name="show_images" value="1" <?= $chk('show_images') ? 'checked' : '' ?>> Show the publisher’s thumbnail images (only if their terms allow it)</label>
    <p class="muted small" style="margin:.8rem 0 .2rem"><strong>Tier 1 only:</strong></p>
    <label class="check"><input type="checkbox" name="is_official" value="1" <?= $chk('is_official') ? 'checked' : '' ?>> This is an official authority that produces the news itself (required for Tier 1)</label>
    <label class="check"><input type="checkbox" name="can_break" value="1" <?= $chk('can_break') ? 'checked' : '' ?>> Its stories may be marked BREAKING (when the headline carries a breaking signal)</label>
    <label class="check"><input type="checkbox" name="all_breaking" value="1" <?= $chk('all_breaking') ? 'checked' : '' ?>> Every story in this feed is breaking by nature (e.g. a feed of significant earthquakes)</label>
  </fieldset>
  <?php partial('field', ['name' => 'notes', 'type' => 'text', 'label' => 'Private notes', 'o' => ['help' => 'Only you see this.', 'max' => 290], 'value' => $v('notes')]); ?>

  <?php if (!$row): ?><p class="flash flash-info">New sources start on a probation period: their stories show as “New source” and are never marked breaking until it ends.</p><?php endif; ?>
  <div class="form-actions">
    <button class="btn btn-gold" type="submit">Save source</button>
    <button class="btn btn-navy" type="submit" formaction="<?= e(url('/admin/news/sources/test')) ?>">Test this feed first</button>
    <a class="btn btn-ghost-dark" href="<?= e(url('/admin/news/sources')) ?>">Cancel</a>
  </div>
</form>
<?php clear_old(); ?>

<?php
/** One event in a list. Expects $ev. */
$d1 = strtotime($ev['start_date']);
$d2 = $ev['end_date'] ? strtotime($ev['end_date']) : null;
?>
<li class="event">
  <div class="event-date" aria-hidden="true"><span class="d"><?= e(date('j', $d1)) ?></span><span class="m"><?= e(date('M', $d1)) ?></span><span class="y"><?= e(date('Y', $d1)) ?></span></div>
  <div class="event-body">
    <h3><?= $ev['url'] ? '<a href="' . e($ev['url']) . '" target="_blank" rel="noopener noreferrer">' . e($ev['title']) . '</a>' : e($ev['title']) ?><?php if ($ev['is_sample']): ?> <span class="badge-sample">sample</span><?php endif; ?></h3>
    <p class="muted small"><?= e(date('j F Y', $d1)) ?><?= $d2 && $d2 > $d1 ? ' to ' . e(date('j F Y', $d2)) : '' ?><?= $ev['time_text'] !== '' ? ' · ' . e($ev['time_text']) : '' ?><?= $ev['location'] !== '' ? ' · ' . e($ev['location']) : '' ?> · <span class="tag"><?= e(ucfirst($ev['kind'])) ?></span></p>
    <?php if ($ev['summary']): ?><p><?= nl2br(e($ev['summary'])) ?></p><?php endif; ?>
  </div>
</li>

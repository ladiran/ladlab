<section class="page-hero"><div class="wrap"><h1>Events</h1><p class="lead">Public lectures, workshops, conferences and webinars.</p></div></section>
<section class="section">
  <div class="wrap">
    <h2 class="section-title">Upcoming</h2>
    <?php if ($upcoming): ?><ul class="event-list"><?php foreach ($upcoming as $ev): partial('event_row', ['ev' => $ev]); endforeach; ?></ul>
    <?php else: ?><p class="empty">No events are scheduled right now. <a href="#newsletter">Join the weekly digest</a> to hear about the next one, or <a href="<?= e(url('/speaking')) ?>">invite me to speak</a>.</p><?php endif; ?>
    <?php if ($past): ?>
      <h2 class="section-title" style="margin-top:2.5rem">Past events</h2>
      <ul class="event-list past"><?php foreach ($past as $ev): partial('event_row', ['ev' => $ev]); endforeach; ?></ul>
    <?php endif; ?>
  </div>
</section>
<section class="band" id="newsletter"><div class="wrap band-inner"><div><h2>Hear about new events</h2><p>A short weekly email, with event news when there is some.</p></div><?php partial('newsletter_form'); ?></div></section>

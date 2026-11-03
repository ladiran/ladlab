<?php
/** Announcements (enrolled students), reviews and the review form. */
$active = !empty($enrol) && $enrol['status'] === 'active';
?>
<?php if ($announcements): ?>
<section class="section alt" id="announcements">
  <div class="wrap narrow-wide">
    <h2>Announcements</h2>
    <?php foreach ($announcements as $a): ?>
      <article class="card announce"><h3><?= e($a['title']) ?></h3><p class="muted small"><?= e(fmt_date($a['created_at'], 'j M Y')) ?></p><div class="prose"><?= md($a['body']) ?></div></article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<section class="section" id="reviews">
  <div class="wrap narrow-wide">
    <h2>Student reviews</h2>
    <?php if ($rating['count'] > 0): ?>
      <p class="rating-line big"><span class="stars" aria-hidden="true"><?= e(Community::stars($rating['avg'])) ?></span> <strong><?= e(number_format($rating['avg'], 1)) ?> out of 5</strong> <span class="muted">(<?= (int)$rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?>)</span></p>
      <?php foreach ($reviews as $r): ?>
        <article class="card review"><p class="rating-line"><span class="stars" aria-hidden="true"><?= e(Community::stars((float)$r['rating'])) ?></span> <strong><?= e(Community::shortName($r['name'])) ?></strong> <span class="muted small"><?= e(fmt_date($r['created_at'], 'j M Y')) ?></span></p><?php if ($r['comment'] !== null && $r['comment'] !== ''): ?><p><?= nl2br(e($r['comment'])) ?></p><?php endif; ?></article>
      <?php endforeach; ?>
    <?php else: ?><p class="muted">No reviews yet.</p><?php endif; ?>

    <?php if ($active): ?>
      <form method="post" action="<?= e(url('/learn/' . $c['slug'] . '/review')) ?>" class="card form review-form">
        <?= csrf_field() ?>
        <h3><?= $myReview ? 'Your review' . ($myReview['status'] === 'pending' ? ' (waiting for approval)' : '') : 'Review this course' ?></h3>
        <fieldset class="star-pick"><legend class="sr-only">Rating</legend>
          <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="st<?= $i ?>" name="rating" value="<?= $i ?>" <?= (int)($myReview['rating'] ?? 0) === $i ? 'checked' : '' ?> required><label for="st<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label><?php endfor; ?>
        </fieldset>
        <label for="rv_c">Your comments (optional)</label>
        <textarea id="rv_c" name="comment" rows="3" maxlength="1500"><?= e((string)($myReview['comment'] ?? '')) ?></textarea>
        <button class="btn btn-gold" type="submit">Send review</button>
        <p class="small muted">Reviews are checked before they appear. Only your first name and last initial are shown.</p>
      </form>
    <?php endif; ?>
  </div>
</section>
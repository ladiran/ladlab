<p><a href="<?= e(url('/admin/articles')) ?>">← Article submissions</a></p>
<div class="two-col">
  <section class="card">
    <h2><?= e($a['title']) ?></h2>
    <p><span class="status st-<?= $a['status'] === 'published' ? 'paid' : ($a['status'] === 'pending' ? 'awaiting_payment' : 'cancelled') ?>"><?= e(ArticlesController::statusLabel($a['status'])) ?></span></p>
    <?php if ($a['excerpt'] !== ''): ?><p class="lead-sm"><?= e($a['excerpt']) ?></p><?php endif; ?>
    <?php if ($a['cover_image']): ?><img class="post-cover" src="<?= e(upload_url($a['cover_image'])) ?>" alt="" style="max-width:100%"><?php endif; ?>
    <div class="prose"><?= md_ugc($a['body']) ?></div>
    <?php if ($a['tags']): ?><p class="small muted">Tags: <?= e($a['tags']) ?></p><?php endif; ?>
  </section>
  <aside>
    <section class="card">
      <h3>The author</h3>
      <p><strong><?= e($a['author_name']) ?></strong><br><a href="mailto:<?= e($a['author_email']) ?>"><?= e($a['author_email']) ?></a></p>
      <p class="small muted">Account created <?= $a['user_since'] ? e(fmt_date($a['user_since'], 'j M Y')) : 'n/a (account deleted)' ?>. <?php if (!empty($a['email_verified_at'])): ?><strong style="color:#14603f">✔ The email address was verified</strong> by a code sent to it on <?= e(fmt_date($a['email_verified_at'], 'j M Y')) ?>.<?php else: ?><strong style="color:#a02727">⚠ The email address is NOT verified</strong> (an older account). Check that the name and email look genuine before you publish: they appear with the article.<?php endif; ?></p>
      <p class="small muted">Consent to show name and email: <?= $a['consent_at'] ? e(fmt_date($a['consent_at'], 'j M Y, H:i')) : 'not recorded' ?></p>
    </section>
    <?php if ($a['status'] !== 'published'): ?>
    <section class="card" style="margin-top:16px">
      <h3>Decision</h3>
      <form method="post" action="<?= e(url('/admin/articles/' . $a['id'] . '/approve')) ?>" data-confirm="Publish this article on the blog now?"><?= csrf_field() ?><button class="btn btn-gold btn-block" type="submit">✔ Approve and publish</button></form>
      <p class="small muted">Want to fix a typo first? <a href="<?= e(url('/admin/posts/' . $a['id'])) ?>">Edit the article</a>, then approve.</p>
      <hr>
      <form method="post" action="<?= e(url('/admin/articles/' . $a['id'] . '/reject')) ?>" class="form"><?= csrf_field() ?>
        <label for="rj">Send back to the author, with a note (emailed to them)</label>
        <textarea id="rj" name="note" rows="4" maxlength="500" required placeholder="What should they change?"><?= e($a['review_note']) ?></textarea>
        <button class="btn btn-navy btn-block" type="submit">Send back for changes</button>
      </form>
    </section>
    <?php else: ?>
      <section class="card" style="margin-top:16px"><p><a class="btn btn-ghost" href="<?= e(url('/blog/' . $a['slug'])) ?>" target="_blank">View on the blog ↗</a></p><p class="small muted">Published <?= e(fmt_date($a['published_at'], 'j M Y')) ?>. To unpublish, set its status to Draft in <a href="<?= e(url('/admin/posts/' . $a['id'])) ?>">Blog posts</a>.</p></section>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/admin/articles/' . $a['id'] . '/delete')) ?>" data-confirm="Delete this article permanently?" style="margin-top:16px"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete article</button></form>
  </aside>
</div>

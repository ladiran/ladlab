<section class="page-hero"><div class="wrap"><h1>My articles</h1><p class="lead">Write for the blog. An administrator reads every article before it is published.</p></div></section>
<section class="section">
  <div class="wrap">
    <?php partial('account_tabs', ['tab' => $tab]); ?>
    <p><a class="btn btn-gold" href="<?= e(url('/account/articles/new')) ?>">＋ Write an article</a></p>
    <?php if ($rows): ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Article</th><th>Status</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= e($r['title']) ?></strong><?php if ($r['status'] === 'rejected' && $r['review_note'] !== ''): ?><br><small class="low"><strong>From the editor:</strong> <?= e($r['review_note']) ?></small><?php endif; ?></td>
            <td><span class="status st-<?= $r['status'] === 'published' ? 'paid' : ($r['status'] === 'pending' ? 'awaiting_payment' : 'cancelled') ?>"><?= e(ArticlesController::statusLabel($r['status'])) ?></span></td>
            <td><?= e(time_ago($r['updated_at'])) ?></td>
            <td class="row-actions">
              <?php if (in_array($r['status'], ['draft', 'rejected'], true)): ?><a class="btn btn-sm btn-gold" href="<?= e(url('/account/articles/' . $r['id'])) ?>"><?= $r['status'] === 'rejected' ? 'Edit and resubmit' : 'Edit' ?></a><?php endif; ?>
              <?php if ($r['status'] === 'published'): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('/blog/' . $r['slug'])) ?>">View ↗</a><?php endif; ?>
              <?php if ($r['status'] === 'pending'): ?><form method="post" action="<?= e(url('/account/articles/' . $r['id'] . '/withdraw')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit">Take back</button></form><?php endif; ?>
              <?php if ($r['status'] !== 'published'): ?><form method="post" action="<?= e(url('/account/articles/' . $r['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this article?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php else: ?>
      <p class="empty">You have not written anything yet. Articles on physics, weather and climate, science teaching or research are welcome.</p>
    <?php endif; ?>
    <div class="card" style="margin-top:20px">
      <h3>How it works</h3>
      <ol class="steps">
        <li>Write your article (you can save drafts and come back).</li>
        <li>Submit it for approval. An administrator reads it and either publishes it or sends it back with a note.</li>
        <li>You get an email either way. When it is published, <strong>your registered name and email address are shown</strong> with it, so readers can contact you.</li>
      </ol>
      <p class="small muted">Please send only your own original work, or text you have the right to share. See our <a href="<?= e(url('/copyright')) ?>">copyright policy</a>.</p>
    </div>
  </div>
</section>

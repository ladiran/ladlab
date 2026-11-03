<?php
$v = fn(string $k, string $d = '') => old($k) !== null && old($k) !== '' ? (string)old($k) : (string)($a[$k] ?? $d);
$action = $a ? '/account/articles/' . $a['id'] : '/account/articles/new';
?>
<section class="page-hero"><div class="wrap"><h1><?= $a ? 'Edit article' : 'Write an article' ?></h1></div></section>
<section class="section">
  <div class="wrap">
    <?php partial('account_tabs', ['tab' => $tab]); ?>
    <?php if (!EmailVerify::verified($user)): ?><div class="flash flash-info">Your email address is not verified yet. You can write and save drafts, but to submit an article you must <a href="<?= e(url('/account/verify')) ?>"><strong>verify your email</strong></a> (we send you a 6-digit code).</div><?php endif; ?>
    <?php if ($a && $a['status'] === 'rejected' && $a['review_note'] !== ''): ?><div class="flash flash-info"><strong>From the editor:</strong> <?= e($a['review_note']) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="form card admin-form" data-preview-url="<?= e(url('/account/articles/preview')) ?>">
      <?= csrf_field() ?>
      <div class="field"><label for="a_title">Title <span class="req">*</span></label><input type="text" id="a_title" name="title" value="<?= e($v('title')) ?>" maxlength="250" required></div>
      <div class="field"><label for="a_ex">Short summary (one or two sentences, shown in lists and in Google)</label><textarea id="a_ex" name="excerpt" rows="2" maxlength="400"><?= e($v('excerpt')) ?></textarea></div>
      <div class="field field-markdown"><label for="a_body">Article <span class="req">*</span></label>
        <div class="md-editor" data-md-editor>
          <div class="md-toolbar" role="toolbar" aria-label="Formatting">
            <button type="button" data-md="bold" title="Bold"><b>B</b></button>
            <button type="button" data-md="italic" title="Italic"><i>I</i></button>
            <button type="button" data-md="h2" title="Heading">H</button>
            <button type="button" data-md="ul" title="Bullet list">• List</button>
            <button type="button" data-md="ol" title="Numbered list">1. List</button>
            <button type="button" data-md="quote" title="Quote">❝</button>
            <button type="button" data-md="link" title="Link">Link</button>
            <button type="button" data-md="preview" class="md-preview-btn">Preview</button>
          </div>
          <textarea id="a_body" name="body" rows="18"><?= e($v('body')) ?></textarea>
          <div class="md-preview prose" hidden></div>
        </div>
        <small class="hint">Use the buttons above for headings, bold and lists. Equations work too: write $E = mc^2$. At least 300 characters to submit. Pictures cannot be placed inside the text; add one cover picture below.</small></div>
      <div class="form-row">
        <div class="field"><label for="a_tags">Tags (comma-separated)</label><input type="text" id="a_tags" name="tags" value="<?= e($v('tags')) ?>" maxlength="300" placeholder="weather, physics education"></div>
        <div class="field"><label for="a_cover">Cover picture (optional)</label><input type="file" id="a_cover" name="cover" accept="image/jpeg,image/png,image/webp">
          <?php if ($a && $a['cover_image']): ?><label class="check small"><input type="checkbox" name="remove_cover" value="1"> Remove the current picture</label><?php endif; ?></div>
      </div>
      <fieldset class="options">
        <legend>Before you submit</legend>
        <label class="check"><input type="checkbox" name="consent" value="1" <?= old('consent') ? 'checked' : '' ?>> I agree that my <strong>registered name (<?= e($user['name']) ?>)</strong> and <strong>email address (<?= e($user['email']) ?>)</strong> are published with the article, and that the article is my own work or I have the right to share it.</label>
        <p class="small muted">To change the name or email shown, update them first under <a href="<?= e(url('/account/profile')) ?>">My details &amp; password</a>.</p>
      </fieldset>
      <div class="form-actions">
        <button class="btn btn-gold" type="submit" name="action" value="submit">Submit for approval</button>
        <button class="btn btn-navy" type="submit" name="action" value="draft">Save as draft</button>
        <a class="btn btn-ghost-dark" href="<?= e(url('/account/articles')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</section>
<?php clear_old(); ?>

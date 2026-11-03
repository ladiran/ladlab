<?php /** Suggest / report form. Expects $back ('toolbox' or 'toolbox/ID'). */ ?>
<section class="card" id="suggest">
  <h2>Know a good free resource? Found a broken link?</h2>
  <p class="muted">Tell us. We check every suggestion first, and we list only <strong>legitimate</strong> sources: official sites, open-access and openly licensed material, and software that its authors give away.</p>
  <form method="post" action="<?= e(url('/toolbox/suggest')) ?>" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e($back) ?>">
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div class="form-row">
      <div class="field"><label for="ts_kind">This is a</label><select id="ts_kind" name="kind"><option value="suggest">suggestion for a new resource</option><option value="broken">broken or unsuitable link</option></select></div>
      <div class="field"><label for="ts_url">Link</label><input type="url" id="ts_url" name="url" maxlength="600" placeholder="https://"></div>
    </div>
    <div class="field"><label for="ts_note">Why is it useful? (or what is wrong with the link)</label><textarea id="ts_note" name="note" rows="3" maxlength="1500"></textarea></div>
    <div class="field"><label for="ts_email">Your email (optional, if you would like a reply)</label><input type="email" id="ts_email" name="email" maxlength="190"></div>
    <button class="btn btn-navy" type="submit">Send</button>
  </form>
</section>

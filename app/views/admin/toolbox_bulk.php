<p><a href="<?= e(url('/admin/toolbox')) ?>">← Free tools</a></p>
<form method="post" action="<?= e(url('/admin/toolbox/bulk')) ?>" class="card form admin-form">
  <?= csrf_field() ?>
  <p class="muted">Paste one link per line. Optional extras after the link, separated by <strong>|</strong>: <code>https://… | Title | Subject | type</code>. Whatever you leave out takes the defaults below. Duplicates are skipped.</p>
  <div class="flash flash-info">List only <strong>legitimate</strong> sources: the owner's own site, open-access and openly licensed material, and software its authors give away. Never link to pirated books, cracked software or unauthorised copies.</div>
  <div class="field"><label for="b_lines">Links</label><textarea id="b_lines" name="lines" rows="10" required placeholder="https://www.example.org/course | A good course | Physics | course"></textarea></div>
  <div class="form-row">
    <div class="field"><label for="b_kind">Default type</label><select id="b_kind" name="kind"><?php foreach (Toolbox::KINDS as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="b_subj">Default subject</label><input type="text" id="b_subj" name="subject" value="Physics" maxlength="60" list="subjects"><datalist id="subjects"><?php foreach (Toolbox::subjects() as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist></div>
    <div class="field"><label for="b_aud">For</label><select id="b_aud" name="audience"><?php foreach (Toolbox::AUDIENCES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="b_lic">Cost / licence</label><select id="b_lic" name="license"><?php foreach (Toolbox::LICENSES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
  </div>
  <p class="small muted">Types you can write after the second | : <?= e(implode(', ', array_keys(Toolbox::KINDS))) ?>.</p>
  <div class="form-actions"><button class="btn btn-gold" type="submit">Add the links</button></div>
</form>

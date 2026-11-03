<div class="two-col">
  <section class="card">
    <h2>Upload a new CV</h2>
    <p class="muted">Upload a PDF. It becomes the “Download CV” file on the About page and home page straight away. Older versions are kept below.</p>
    <form method="post" action="<?= e(url('/admin/cv')) ?>" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?>
      <label>PDF file <input type="file" name="cv" accept="application/pdf" required></label>
      <button class="btn btn-gold" type="submit">Upload and publish</button>
    </form>
  </section>
  <section class="card">
    <h2>Version history</h2>
    <?php if ($files): ?>
      <table class="table"><tbody>
      <?php foreach ($files as $f): ?>
        <tr>
          <td><a href="<?= e(upload_url($f['path'])) ?>" target="_blank" rel="noopener"><?= e($f['original_name']) ?></a><br><small class="muted"><?= e(fmt_date($f['uploaded_at'], 'j M Y, H:i')) ?></small></td>
          <td><?php if ($f['is_active']): ?><span class="status status-replied">Public</span><?php else: ?>
            <form method="post" action="<?= e(url('/admin/cv/' . $f['id'] . '/activate')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit">Make public</button></form><?php endif; ?></td>
          <td><form method="post" action="<?= e(url('/admin/cv/' . $f['id'] . '/delete')) ?>" class="inline" data-confirm="Delete this CV file?"><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    <?php else: ?><p class="empty">No CV uploaded yet. The website shows “CV coming soon” until you add one.</p><?php endif; ?>
  </section>
</div>

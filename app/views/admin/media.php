<section class="card">
  <h2>Upload a file</h2>
  <form method="post" action="<?= e(url('/admin/media')) ?>" enctype="multipart/form-data" class="form inline-form">
    <?= csrf_field() ?>
    <input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" required>
    <button class="btn btn-gold" type="submit">Upload</button>
  </form>
  <p class="muted small">Images (JPG, PNG, WebP, GIF) up to 8 MB and PDFs up to 15 MB. After uploading, copy the path into a blog post, e.g. <code>![description](uploads/images/name.jpg)</code>.</p>
</section>
<?php if ($files): ?>
<div class="media-grid">
  <?php foreach ($files as $f): ?>
    <figure class="card media-item">
      <?php if ($f['kind'] === 'image'): ?><a href="<?= e(upload_url($f['path'])) ?>" target="_blank" rel="noopener"><img src="<?= e(upload_url($f['path'])) ?>" alt="" loading="lazy"></a><?php else: ?><a class="pdf-ico" href="<?= e(upload_url($f['path'])) ?>" target="_blank" rel="noopener">📄 PDF</a><?php endif; ?>
      <figcaption>
        <code data-copy="<?= e($f['path']) ?>" title="Click to copy"><?= e($f['path']) ?></code>
        <small class="muted"><?= e($f['original_name']) ?> · <?= e(number_format($f['size'] / 1024, 0)) ?> KB</small>
        <form method="post" action="<?= e(url('/admin/media/' . $f['id'] . '/delete')) ?>" data-confirm="Delete this file? Anything using it will show a broken image."><?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
      </figcaption>
    </figure>
  <?php endforeach; ?>
</div>
<?php else: ?><p class="empty">No uploads yet.</p><?php endif; ?>

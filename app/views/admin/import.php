<p><a href="<?= e(url('/admin/publications')) ?>">← Back to publications</a></p>
<div class="two-col">
  <section class="card">
    <h2>From a DOI</h2>
    <p class="muted">Fills the form for you from Crossref. You check it before saving. Needs internet.</p>
    <form method="post" action="<?= e(url('/admin/publications/import')) ?>" class="form"><?= csrf_field() ?><input type="hidden" name="mode" value="doi">
      <label>DOI <input type="text" name="doi" placeholder="10.1029/2020JD033411" required></label>
      <button class="btn btn-gold" type="submit">Look up</button>
    </form>
  </section>
  <section class="card">
    <h2>From ORCID</h2>
    <p class="muted">Imports every public work on your ORCID record (title, year, journal, DOI). Authors are not included by ORCID; add them later if you wish. Needs internet.</p>
    <form method="post" action="<?= e(url('/admin/publications/import')) ?>" class="form"><?= csrf_field() ?><input type="hidden" name="mode" value="orcid">
      <label>ORCID iD <input type="text" name="orcid" placeholder="0000-0002-1825-0097" required></label>
      <button class="btn btn-gold" type="submit">Import all</button>
    </form>
  </section>
  <section class="card">
    <h2>From BibTeX</h2>
    <p class="muted">Paste entries, or choose a .bib file (Google Scholar, Zotero and Mendeley can all export BibTeX).</p>
    <form method="post" action="<?= e(url('/admin/publications/import')) ?>" enctype="multipart/form-data" class="form"><?= csrf_field() ?><input type="hidden" name="mode" value="bibtex">
      <label>Paste BibTeX <textarea name="bibtex" rows="6" placeholder="@article{key, title={…}, author={…}, journal={…}, year={2025}}"></textarea></label>
      <label>…or a .bib file <input type="file" name="bibfile" accept=".bib,.txt"></label>
      <button class="btn btn-gold" type="submit">Import</button>
    </form>
  </section>
  <section class="card">
    <h2>From a CSV spreadsheet</h2>
    <p class="muted">Columns: title, authors, venue, year, type, doi, url, abstract. <a href="<?= e(url('/admin/publications/template.csv')) ?>">Download a template</a>. Save from Excel as “CSV UTF-8”.</p>
    <form method="post" action="<?= e(url('/admin/publications/import')) ?>" enctype="multipart/form-data" class="form"><?= csrf_field() ?><input type="hidden" name="mode" value="csv">
      <label>CSV file <input type="file" name="csvfile" accept=".csv,text/csv" required></label>
      <button class="btn btn-gold" type="submit">Import</button>
    </form>
  </section>
</div>
<p class="muted">Duplicates (same DOI, or same title and year) are skipped automatically.</p>

<section class="page-hero"><div class="wrap"><h1>News sources &amp; editorial policy</h1><p class="lead">What appears in the Science News section, where it comes from, and how we keep it accurate.</p></div></section>
<section class="section">
  <div class="wrap narrow prose">
    <h2>How it works</h2>
    <p>Stories are gathered automatically from the official feeds of the sources listed below and published without manual selection. We show only the publisher’s own headline and short excerpt, never a rewritten or machine-generated summary, and always link to the original article.</p>

    <h2>Source tiers</h2>
    <ul>
      <li><strong>Tier 1 · Official source:</strong> the organisation that produces the news itself (space agencies, weather and geological services, the Nobel Foundation, journal research).</li>
      <li><strong>Tier 2 · Reputable press:</strong> established science and news outlets.</li>
      <li><strong>Tier 3 · Press release:</strong> wire services that mostly republish university and institution press releases. These are labelled and never count towards “breaking”.</li>
    </ul>

    <h2>Checks every story must pass</h2>
    <ul>
      <li>It must come from an approved source over a secure (HTTPS) feed, and its link must lead to that source’s own website.</li>
      <li>Sponsored, clickbait, miracle-cure, pseudoscience and opinion pieces are filtered out. General news feeds are kept only for stories that pass a science-topic check.</li>
      <li>Duplicate and near-duplicate stories are grouped, and shown once with “Reported by N sources”.</li>
      <li><strong>BREAKING</strong> is used sparingly: the headline must carry a breaking signal <em>and</em> either an official Tier 1 source has reported it, or at least <?= (int)$rules['min'] ?> independent Tier 1/2 sources reported it within <?= (int)$rules['window'] ?> hours. Press releases and brand-new sources never qualify. Breaking stories stay on the ticker for <?= (int)$rules['ticker'] ?> hours.</li>
      <li>For <?= (int)$rules['recheck'] ?> hours after publication every story is re-checked against the original page. If the page is removed, redirected away, or its headline changes substantially, the story is hidden or updated and marked “Updated”.</li>
      <li>New sources spend <?= (int)$rules['probation'] ?> hours on probation, and a source that starts failing, or suddenly floods the system, is paused automatically.</li>
    </ul>

    <h2>Limits</h2>
    <p>Automation can confirm that a story exists at a trusted source and is consistent across sources. It cannot judge scientific quality. The tier labels, source names and links tell you exactly what you are reading. If something looks wrong, use “Report a problem” on the story.</p>

    <h2>Current sources</h2>
  </div>
  <div class="wrap">
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Source</th><th>Tier</th><th>Type</th><th>Region</th></tr></thead>
      <tbody>
      <?php foreach ($sources as $s): ?>
        <tr>
          <td><?= $s['site_url'] ? '<a href="' . e($s['site_url']) . '" target="_blank" rel="noopener noreferrer">' . e($s['name']) . '</a>' : e($s['name']) ?><?= $s['status'] === 'probation' ? ' <span class="tag tag-new">new</span>' : '' ?></td>
          <td><span class="tag tier tier-<?= (int)$s['tier'] ?>"><?= e(NewsEngine::TIER_LABELS[(int)$s['tier']] ?? '') ?></span></td>
          <td><?= e(['primary' => 'Official / primary', 'news' => 'News', 'press_release' => 'Press releases'][$s['kind']] ?? $s['kind']) ?></td>
          <td><?= e(NewsEngine::REGIONS[$s['region']] ?? $s['region']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <p class="muted small">Sources that are temporarily unavailable are not listed.</p>
  </div>
</section>

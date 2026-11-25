<section class="page-hero">
  <div class="wrap">
    <h1>Fake news checker</h1>
    <p class="lead">Paste a headline, a link or a social media handle. You get one of three answers, with the evidence behind it, so you can decide for yourself before you share.</p>
  </div>
</section>

<section class="section">
  <div class="wrap narrow">
    <?php if (!$online): ?>
      <div class="card note" role="status"><strong>The checker is resting.</strong> It is not responding at the moment, so checks will fail until it is switched back on. Please try again later.</div>
    <?php endif; ?>

    <form id="fn-form" class="card fn-form" novalidate data-endpoint="<?= e(url('/fakenews/check')) ?>" data-csrf="<?= e(csrf_token()) ?>">
      <label for="fn-title">Headline or claim</label>
      <textarea id="fn-title" rows="2" maxlength="500" placeholder="Paste the headline or the claim you saw"></textarea>
      <label for="fn-url">Link (URL)</label>
      <input id="fn-url" type="text" inputmode="url" maxlength="2000" placeholder="https://example.com/story" autocomplete="off">
      <label for="fn-handle">Social media handle or profile link</label>
      <input id="fn-handle" type="text" maxlength="300" placeholder="@name or https://bsky.app/profile/name" autocomplete="off">
      <p class="small muted">Fill in whatever you have. One field is enough; more fields give a better answer.</p>
      <button id="fn-go" class="btn btn-navy" type="submit">Check</button>
    </form>

    <div id="fn-result" class="fn-result" aria-live="polite"></div>

    <div class="grid g3 fn-legend">
      <div class="card"><span class="fn-badge fn-verified_fake">Verified fake</span><p class="small">Fact-checkers rate it false, a safety list flags the link, or the site is on our unreliable list.</p></div>
      <div class="card"><span class="fn-badge fn-unverified">Unverified</span><p class="small">We could not confirm it either way. That is not the same as false: look for the original source.</p></div>
      <div class="card"><span class="fn-badge fn-authenticated">Authenticated</span><p class="small">Fact-checkers rate it true, or a trusted outlet and independent coverage support it with no red flags.</p></div>
    </div>

    <h2>How it works</h2>
    <p>Each check looks at published fact-checks, safety lists, whether independent trusted outlets report the same story, the age of the website, and the account behind a social media handle where that can be checked for free. A writing-style model adds a small hint, but it can never decide an answer on its own.</p>
    <h2>What to keep in mind</h2>
    <ul>
      <li>This is evidence, not certainty. Very new stories often have no fact-checks yet, and will show as unverified.</li>
      <li>Some platforms (X, Facebook, Instagram, TikTok) offer no free way to look up an account, so the handle cannot be checked there.</li>
      <li>What you type is sent to outside fact-checking and safety services to run the check. Do not enter private information. We do not keep what you enter.</li>
      <li>To keep the free services available to everyone, each visitor can run 20 checks per hour.</li>
    </ul>
  </div>
</section>
<script src="<?= e(asset('js/fakenews.js')) ?>?v=<?= (int)@filemtime(ROOT . '/public/assets/js/fakenews.js') ?>" defer></script>

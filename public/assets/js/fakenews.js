/* Fake news checker page. Builds the result with DOM calls and textContent only, so nothing the checker returns can run as markup. */
(function () {
  'use strict';
  var form = document.getElementById('fn-form');
  if (!form) { return; }
  var out = document.getElementById('fn-result');
  var go = document.getElementById('fn-go');
  var LABEL = { verified_fake: 'Verified fake', unverified: 'Unverified', authenticated: 'Authenticated' };

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined) { n.textContent = text; }
    return n;
  }
  function show(node) { out.textContent = ''; out.appendChild(node); }
  function message(text) { show(el('p', 'fn-msg', text)); }

  function render(d) {
    var card = el('div', 'card');
    var head = el('div', 'fn-head');
    head.appendChild(el('span', 'fn-badge fn-' + d.verdict, LABEL[d.verdict] || 'Unverified'));
    head.appendChild(el('span', 'fn-score', 'credibility ' + d.score + '/100'));
    card.appendChild(head);
    card.appendChild(el('p', 'fn-summary', d.summary));
    var list = el('ul', 'fn-evidence');
    (d.evidence || []).forEach(function (ev) {
      var li = el('li', 'fn-dir-' + ev.direction);
      li.appendChild(el('span', 'fn-source', ev.source));
      li.appendChild(el('span', 'fn-detail', ev.detail));
      if (/^https?:\/\//i.test(ev.link || '')) {
        var a = el('a', '', 'source');
        a.href = ev.link; a.target = '_blank'; a.rel = 'noopener noreferrer';
        li.appendChild(document.createTextNode(' ')); li.appendChild(a);
      }
      list.appendChild(li);
    });
    if (!list.children.length) { list.appendChild(el('li', '', 'No evidence was collected.')); }
    card.appendChild(list);
    if (d.skipped && d.skipped.length) {
      card.appendChild(el('p', 'small muted', 'Not checked: ' + d.skipped.join('; ')));
    }
    show(card);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var body = {
      title: document.getElementById('fn-title').value.trim(),
      url: document.getElementById('fn-url').value.trim(),
      handle: document.getElementById('fn-handle').value.trim()
    };
    if (!body.title && !body.url && !body.handle) { message('Please fill in at least one field.'); return; }
    go.disabled = true; message('Checking… this can take up to half a minute.');
    fetch(form.getAttribute('data-endpoint'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': form.getAttribute('data-csrf') },
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, j: j }; });
    }).then(function (res) {
      if (res.ok && res.j.verdict) { render(res.j); } else { message(res.j.error || 'Something went wrong. Please try again.'); }
    }).catch(function () {
      message('Could not reach the site. Check your connection and try again.');
    }).then(function () { go.disabled = false; });
  });
})();

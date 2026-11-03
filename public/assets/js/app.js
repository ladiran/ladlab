/* ClearSky Academy: small progressive enhancements. No external libraries. */
(function () {
  'use strict';

  // Mobile menus (public header and admin sidebar)
  document.querySelectorAll('.nav-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.getElementById(btn.getAttribute('aria-controls'));
      if (!target) { return; }
      var open = target.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  // Confirm before destructive actions: <form data-confirm="Are you sure?">
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); }
  });

  // Copy to clipboard: <button data-copy="text">
  function copy(text, el) {
    function done() {
      if (!el) { return; }
      var old = el.textContent;
      el.textContent = 'Copied ✓';
      setTimeout(function () { el.textContent = old; }, 1500);
    }
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, done);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); } catch (err) { /* ignore */ }
      document.body.removeChild(ta); done();
    }
  }
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-copy]');
    if (el) { copy(el.getAttribute('data-copy'), el.tagName === 'BUTTON' ? el : null); }
  });

  // Markdown editor toolbar + preview (admin)
  document.querySelectorAll('[data-md-editor]').forEach(function (box) {
    var ta = box.querySelector('textarea');
    var preview = box.querySelector('.md-preview');
    var previewBtn = box.querySelector('.md-preview-btn');
    var csrf = document.querySelector('input[name=_csrf]');

    function wrap(before, after, placeholder) {
      var s = ta.selectionStart, e = ta.selectionEnd;
      var sel = ta.value.substring(s, e) || placeholder;
      ta.value = ta.value.substring(0, s) + before + sel + after + ta.value.substring(e);
      ta.focus();
      ta.setSelectionRange(s + before.length, s + before.length + sel.length);
    }
    function linePrefix(prefix) {
      var s = ta.selectionStart, e = ta.selectionEnd;
      var start = ta.value.lastIndexOf('\n', s - 1) + 1;
      var chunk = ta.value.substring(start, e) || 'Item';
      var out = chunk.split('\n').map(function (l, i) {
        return (prefix === '1. ' ? (i + 1) + '. ' : prefix) + l;
      }).join('\n');
      ta.value = ta.value.substring(0, start) + out + ta.value.substring(e);
      ta.focus();
    }
    box.querySelectorAll('[data-md]').forEach(function (b) {
      b.addEventListener('click', function () {
        switch (b.getAttribute('data-md')) {
          case 'bold': wrap('**', '**', 'bold text'); break;
          case 'italic': wrap('*', '*', 'italic text'); break;
          case 'h2': linePrefix('# '); break;
          case 'ul': linePrefix('- '); break;
          case 'ol': linePrefix('1. '); break;
          case 'quote': linePrefix('> '); break;
          case 'link':
            var url = window.prompt('Link address (https://…)');
            if (url) { wrap('[', '](' + url + ')', 'link text'); }
            break;
          case 'preview':
            if (!preview.hidden) { preview.hidden = true; ta.hidden = false; previewBtn.textContent = 'Preview'; return; }
            var body = new URLSearchParams();
            body.set('text', ta.value);
            body.set('_csrf', csrf ? csrf.value : '');
            fetch(box.closest('form').getAttribute('data-preview-url') || (window.CSA_BASE || '') + '/admin/markdown-preview', {
              method: 'POST', body: body, credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(function (j) {
              preview.innerHTML = j.html; preview.hidden = false; ta.hidden = true; previewBtn.textContent = 'Edit';
            }).catch(function () { alert('Preview is not available right now.'); });
        }
      });
    });
  });
  // Print / back buttons on printable admin pages
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-print]')) { window.print(); }
    var back = e.target.closest('[data-back]');
    if (back) { e.preventDefault(); window.history.back(); }
  });
  if (location.search.indexOf('autoprint=1') > -1) { window.addEventListener('load', function () { window.print(); }); }

  // ---------- Shop: product page ----------
  function inkFor(hex) {
    var n = parseInt(hex.slice(1), 16), r = n >> 16 & 255, g = n >> 8 & 255, b = n & 255;
    return (0.299 * r + 0.587 * g + 0.114 * b) < 140 ? '#ffffff' : '#0b1f3a';
  }
  document.querySelectorAll('[data-buy]').forEach(function (form) {
    var stock = {};
    try { stock = JSON.parse(form.getAttribute('data-stock') || '{}'); } catch (err) { stock = {}; }
    var colours = form.querySelectorAll('input[name=colour]');
    var sizes = form.querySelectorAll('input[name=size]');
    var note = form.querySelector('[data-stock-note]');
    var nameOut = form.querySelector('[data-colour-name]');
    var tee = document.querySelector('[data-tee-preview] .tee');
    var qty = form.querySelector('select[name=qty]');

    function currentColour() { var c = form.querySelector('input[name=colour]:checked'); return c ? c : null; }
    function refresh() {
      var c = currentColour();
      if (!c) { return; }
      if (nameOut) { nameOut.textContent = c.value; }
      var hex = c.getAttribute('data-hex');
      if (tee && hex) { tee.style.setProperty('--g', hex); tee.style.setProperty('--ink', inkFor(hex)); }
      sizes.forEach(function (s) {
        var q = stock[c.value + '|' + s.value];
        var out = !q || q <= 0;
        s.disabled = out;
        s.parentElement.classList.toggle('out', out);
        if (out && s.checked) { s.checked = false; }
      });
      showNote();
    }
    function showNote() {
      var c = currentColour(), s = form.querySelector('input[name=size]:checked');
      if (!note) { return; }
      if (!c || !s) { note.textContent = ''; return; }
      var q = stock[c.value + '|' + s.value] || 0;
      note.textContent = q <= 0 ? 'Sold out' : (q <= 5 ? 'Only ' + q + ' left' : 'In stock');
      note.classList.toggle('low', q > 0 && q <= 5);
      if (qty) {
        for (var i = 0; i < qty.options.length; i++) { qty.options[i].disabled = parseInt(qty.options[i].value, 10) > q; }
        if (qty.selectedOptions[0] && qty.selectedOptions[0].disabled) { qty.value = '1'; }
      }
    }
    colours.forEach(function (c) { c.addEventListener('change', refresh); });
    sizes.forEach(function (s) { s.addEventListener('change', showNote); });
    refresh();
  });
  document.querySelectorAll('[data-gallery]').forEach(function (g) {
    var main = g.querySelector('[data-main-img]');
    g.querySelectorAll('.thumb-btn').forEach(function (b) {
      b.addEventListener('click', function () {
        if (main) { main.src = b.getAttribute('data-src'); }
        g.querySelectorAll('.thumb-btn').forEach(function (x) { x.classList.remove('on'); });
        b.classList.add('on');
      });
    });
  });

  // ---------- Shop: checkout ----------
  document.querySelectorAll('[data-checkout]').forEach(function (form) {
    var fees = {};
    try { fees = JSON.parse(form.getAttribute('data-fees') || '{}'); } catch (err) { fees = {}; }
    var subtotal = +form.getAttribute('data-subtotal'), discount = +form.getAttribute('data-discount');
    var freeOver = +form.getAttribute('data-free-over'), defFee = +form.getAttribute('data-default-fee');
    var feeOut = document.querySelector('[data-fee-out]'), totalOut = document.querySelector('[data-total-out]');
    var stateSel = form.querySelector('[data-state]');
    var shipBox = form.querySelector('[data-ship-box]'), pickBox = form.querySelector('[data-pickup-box]');
    function money(n) { return '₦' + Math.round(n).toLocaleString('en-NG'); }
    function method() { var m = form.querySelector('input[name=delivery_method]:checked'); return m ? m.value : 'ship'; }
    function update() {
      var pick = method() === 'pickup', net = subtotal - discount, fee = 0, text;
      if (shipBox) { shipBox.hidden = pick; }
      if (pickBox) { pickBox.hidden = !pick; }
      if (pick) { text = 'Free (pickup)'; }
      else if (!stateSel || !stateSel.value) { text = 'choose your state'; fee = null; }
      else {
        fee = (freeOver > 0 && net >= freeOver) ? 0 : (fees[stateSel.value] !== undefined ? fees[stateSel.value] : defFee);
        text = fee === 0 ? 'Free' : money(fee);
      }
      if (feeOut) { feeOut.textContent = text; }
      if (totalOut) { totalOut.textContent = money(net + (fee || 0)); }
    }
    form.querySelectorAll('[data-method]').forEach(function (r) { r.addEventListener('change', update); });
    if (stateSel) { stateSel.addEventListener('change', update); }
    var create = form.querySelector('[data-create]'), accBox = form.querySelector('[data-account-box]');
    if (create && accBox) { create.addEventListener('change', function () { accBox.hidden = !create.checked; }); }
    update();
  });

  // ---------- Learn: quiz countdown, unanswered check, admin question-type toggler ----------
  document.querySelectorAll('[data-countdown]').forEach(function (el) {
    var left = parseInt(el.getAttribute('data-countdown'), 10) || 0;
    var form = document.querySelector('[data-quiz-form]');
    var t;
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
      var h = Math.floor(left / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
      el.textContent = (h ? h + ':' + pad(m) : m) + ':' + pad(s);
      var wrap = document.getElementById('timer-wrap');
      if (wrap && left <= 60) { wrap.classList.add('low'); }
      if (left <= 0) { clearInterval(t); if (form) { form.setAttribute('data-timeup', '1'); form.submit(); } return; }
      left--;
    }
    t = setInterval(tick, 1000);
    tick();
  });
  document.querySelectorAll('[data-quiz-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.getAttribute('data-timeup')) { return; }
      var open = 0;
      form.querySelectorAll('fieldset.question').forEach(function (q) {
        var any = false;
        q.querySelectorAll('input').forEach(function (i) {
          if ((i.type === 'radio' || i.type === 'checkbox') ? i.checked : i.value.trim() !== '') { any = true; }
        });
        if (!any) { open++; }
      });
      if (open && !window.confirm(open + ' question' + (open > 1 ? 's are' : ' is') + ' unanswered. Submit anyway?')) { e.preventDefault(); }
    });
  });
  document.querySelectorAll('[data-qform]').forEach(function (form) {
    var sel = form.querySelector('[data-qtype]');
    function show() {
      form.querySelectorAll('.qbox').forEach(function (b) {
        b.classList.toggle('show', b.getAttribute('data-for').split(' ').indexOf(sel.value) > -1);
      });
    }
    sel.addEventListener('change', show);
    show();
  });

  // ---------- Phase 5: admin product type switch (clothing / one-size) ----------
  document.querySelectorAll('[data-ptype]').forEach(function (sel) {
    var form = sel.closest('form');
    function show() {
      form.querySelectorAll('.ptype-box').forEach(function (b) {
        b.classList.toggle('show', b.getAttribute('data-for') === sel.value);
      });
    }
    sel.addEventListener('change', show);
    show();
  });

  // ---------- News: scrolling bar and self-updating ----------
  document.querySelectorAll('[data-ticker]').forEach(function (bar) {
    var view = bar.querySelector('.ticker-viewport'), track = bar.querySelector('.ticker-track');
    var first = track && track.querySelector('.ticker-set');
    var btn = bar.querySelector('[data-ticker-toggle]');
    // The headlines are repeated until one half of the track is wider than the bar, so the text keeps scrolling on any screen width
    // (a wide laptop screen used to hold all the headlines at once, so nothing moved). Constant speed: about 60 pixels a second.
    function build() {
      if (!view || !first) { return; }
      var sets = track.querySelectorAll('.ticker-set');
      for (var i = sets.length - 1; i > 0; i--) { sets[i].parentNode.removeChild(sets[i]); }
      var w = first.getBoundingClientRect().width;
      if (!w) { return; }
      var k = Math.max(1, Math.ceil(view.clientWidth / w));
      for (var j = 1; j < 2 * k; j++) {
        var c = first.cloneNode(true);
        c.setAttribute('aria-hidden', 'true');
        c.querySelectorAll('a').forEach(function (a) { a.setAttribute('tabindex', '-1'); });
        track.appendChild(c);
      }
      track.style.setProperty('--ticker-speed', Math.max(20, Math.round((w * k) / 60)) + 's');
    }
    build();
    window.addEventListener('load', build);
    var rt;
    window.addEventListener('resize', function () { window.clearTimeout(rt); rt = window.setTimeout(build, 200); });
    if (btn) {
      btn.addEventListener('click', function () {
        var paused = bar.classList.toggle('paused');
        btn.textContent = paused ? '▶' : '⏸';
        btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
        btn.setAttribute('aria-label', paused ? 'Resume scrolling' : 'Pause scrolling');
      });
    }
  });
  // Course chat: live updates every 5 seconds, sending without reloading the page
  document.querySelectorAll('[data-chat]').forEach(function (box) {
    var list = box.querySelector('[data-chat-list]');
    var form = box.querySelector('[data-chat-form]');
    var status = box.querySelector('[data-chat-status]');
    var pollUrl = box.getAttribute('data-chat-poll');
    if (!list || !pollUrl || !window.fetch) { return; }
    var sig = list.getAttribute('data-sig');
    var busy = false;
    function say(t) { if (status) { status.textContent = t || ''; } }
    function nearBottom() { return list.scrollHeight - list.scrollTop - list.clientHeight < 80; }
    function apply(d) {
      if (!d || !d.ok || d.sig === sig) { return; }
      var stick = nearBottom();
      list.innerHTML = d.html;
      sig = d.sig;
      if (stick) { list.scrollTop = list.scrollHeight; }
    }
    function poll() {
      if (busy || document.hidden) { return; }
      busy = true;
      fetch(pollUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) {
          if (r.status === 503 || r.status === 403) { window.location.reload(); throw new Error('stop'); }
          return r.json();
        })
        .then(apply)
        .catch(function () {})
        .then(function () { busy = false; });
    }
    list.scrollTop = list.scrollHeight;
    window.setInterval(poll, 5000);
    document.addEventListener('visibilitychange', poll);
    if (!form || !window.FormData) { return; }
    var ta = form.querySelector('textarea');
    var btn = form.querySelector('button[type=submit]');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!ta.value.trim()) { return; }
      btn.disabled = true;
      say('Sending…');
      fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.ok) { ta.value = ''; list.innerHTML = d.html; sig = d.sig; list.scrollTop = list.scrollHeight; say(''); } else { say(d.error || 'Could not send.'); }
        })
        .catch(function () { say('Could not send. Please check your connection.'); })
        .then(function () { btn.disabled = false; ta.focus(); });
    });
    ta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
        e.preventDefault();
        if (form.requestSubmit) { form.requestSubmit(); } else { form.dispatchEvent(new Event('submit', { cancelable: true })); }
      }
    });
  });
  var tickUrl = document.body.getAttribute('data-news-tick');
  if (tickUrl && window.fetch) {
    window.setTimeout(function () { fetch(tickUrl, { credentials: 'omit', keepalive: true }).catch(function () {}); }, 1500);
  }
})();

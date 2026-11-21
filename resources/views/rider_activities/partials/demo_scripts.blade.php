<script>
(function () {
  function toast(msg) {
    var el = document.getElementById('raToast');
    if (!el) return;
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(function () { el.classList.remove('show'); }, 2200);
  }

  // Operations menu
  var opsBtn = document.getElementById('raOpsBtn');
  var opsMenu = document.getElementById('raOpsMenu');
  if (opsBtn && opsMenu) {
    opsBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      opsMenu.classList.toggle('show');
      opsBtn.setAttribute('aria-expanded', opsMenu.classList.contains('show') ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.ra-dd')) opsMenu.classList.remove('show');
    });
  }

  // Quick search submit on Enter / debounce
  var q = document.getElementById('raQ');
  var qForm = document.getElementById('raQuickSearchForm');
  if (q && qForm) {
    q.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        qForm.submit();
      }
    });
  }

  // Live clock — update every second
  var liveClockTime = document.getElementById('raLiveClockTime');
  if (liveClockTime) {
    function pad2(n) { return n < 10 ? '0' + n : String(n); }
    function tickLiveClock() {
      var now = new Date();
      liveClockTime.textContent = pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());
    }
    tickLiveClock();
    setInterval(tickLiveClock, 1000);
  }

  // Shared auto-scroll + drag for insight + project tickers
  function bindTicker(tkId, trackId, speed) {
    var track = document.getElementById(trackId);
    var tk = document.getElementById(tkId);
    if (!track || !tk || !track.children.length) return;
    var tx = 0, paused = false, drag = null, moved = false, last = performance.now();
    var SPEED = speed || 20;
    var half = Math.floor(track.children.length / 2);
    function setW() {
      if (half <= 0 || !track.children[half]) return 0;
      return track.children[half].offsetLeft - track.children[0].offsetLeft;
    }
    (function step(t) {
      var dt = Math.min(64, t - last);
      last = t;
      if (!paused && !drag) tx -= SPEED * dt / 1000;
      var w = setW();
      if (w > 0) {
        while (tx <= -w) tx += w;
        while (tx > 0) tx -= w;
      }
      track.style.transform = 'translateX(' + tx + 'px)';
      requestAnimationFrame(step);
    })(performance.now());
    tk.addEventListener('mouseenter', function () { paused = true; });
    tk.addEventListener('mouseleave', function () { paused = false; });
    track.addEventListener('pointerdown', function (e) {
      if (e.button !== 0) return;
      drag = { sx: e.clientX, x0: tx };
      moved = false;
    });
    window.addEventListener('pointermove', function (e) {
      if (!drag) return;
      var dx = e.clientX - drag.sx;
      if (Math.abs(dx) > 4) {
        moved = true;
        track.classList.add('drag');
      }
      tx = drag.x0 + dx;
    });
    window.addEventListener('pointerup', function () {
      if (!drag) return;
      drag = null;
      setTimeout(function () { track.classList.remove('drag'); }, 0);
    });
    tk.addEventListener('wheel', function (e) {
      var d = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : (e.shiftKey ? e.deltaY : 0);
      if (!d) return;
      e.preventDefault();
      tx -= d;
    }, { passive: false });
    track.addEventListener('click', function (e) {
      if (moved) {
        e.preventDefault();
        moved = false;
      }
    });
  }

  bindTicker('raTicker', 'raTkTrack', 20);

  // WhatsApp alerts drawer — per-tab filters + Filter + Send
  var waBtn = document.getElementById('raWaBtn');
  var waOv = document.getElementById('raWaOv');
  var waDr = document.getElementById('raWaDrawer');
  var waX = document.getElementById('raWaX');
  var waPayload = { types: {}, bindings: {}, mode: (waDr && waDr.getAttribute('data-wa-mode')) || 'activities' };
  var waTypeBtn = document.querySelector('#raWaTypes button.on');
  var waType = waTypeBtn ? (waTypeBtn.getAttribute('data-type') || 'abs') : 'abs';
  var waSelected = null;
  var waLoadedType = null;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function currentList() {
    if (waLoadedType !== waType) return [];
    return (waPayload.types && waPayload.types[waType]) ? waPayload.types[waType].slice() : [];
  }

  function currentBinding() {
    return (waPayload.bindings && waPayload.bindings[waType]) ? waPayload.bindings[waType] : null;
  }

  function setAvatar(el, r) {
    if (!el) return;
    var ini = (r && r.ini) || 'R';
    el.setAttribute('data-ini', ini);
    el.classList.remove('has-img');
    el.innerHTML = '<span class="ra-av-ini">' + esc(ini) + '</span>';
  }

  var UNBOUND_MSG = 'No Message template found for this alert type. Contact FleetValue Support team to resolve this issue.';

  function missingLabels(r) {
    if (!r) return [];
    if (Array.isArray(r.missing_inject_labels) && r.missing_inject_labels.length) {
      return r.missing_inject_labels.map(String);
    }
    if (Array.isArray(r.missing_injects) && r.missing_injects.length) {
      return r.missing_injects.map(String);
    }
    return [];
  }

  function hasPreviewError(r) {
    var b = currentBinding();
    if (!b || !b.bound || !r) return false;
    if (r.preview_ok === true || r.preview_text) return false;
    return true;
  }

  function previewText(r) {
    var b = currentBinding();
    if (!b || !b.bound) return UNBOUND_MSG;
    if (r && r.preview_text) return String(r.preview_text);
    var missing = missingLabels(r);
    if (missing.length) {
      return 'Cannot send — missing: ' + missing.join(', ') + '.';
    }
    if (r && r.preview_error) return String(r.preview_error);
    return 'Template bound, but preview could not be built for this rider (missing inject values).';
  }

  function selectedFromId() {
    var el = document.getElementById('raWaFrom');
    var v = el ? String(el.value || '').trim() : '';
    return v !== '' ? parseInt(v, 10) : 0;
  }

  function syncFilterFields() {
    var btn = document.querySelector('#raWaTypes button.on');
    var fields = ((btn && btn.getAttribute('data-fields')) || 'project,supervisor').split(',');
    var allowed = {};
    fields.forEach(function (f) { allowed[String(f).trim()] = true; });
    document.querySelectorAll('#raWaSet [data-wa-field]').forEach(function (el) {
      var key = el.getAttribute('data-wa-field');
      el.hidden = !allowed[key];
    });
    var dateEl = document.getElementById('raWaDate');
    if (dateEl && allowed.date) {
      var mode = (waDr && waDr.getAttribute('data-wa-mode')) || 'activities';
      var lockToday = mode === 'live' && (waType === 'lhrs' || waType === 'lord');
      var isOff = waType === 'off' || waType === 'off2' || waType === 'off4' || waType === 'off6';
      var today = dateEl.getAttribute('data-default-today') || dateEl.value;
      var def = lockToday || isOff
        ? today
        : (dateEl.getAttribute('data-default-yesterday') || dateEl.value);
      if (!dateEl._waType || dateEl._waType !== waType) {
        dateEl.value = def;
        dateEl._waType = waType;
      }
      if (lockToday) {
        dateEl.value = today;
        dateEl.readOnly = true;
        dateEl.disabled = true;
        dateEl.title = 'Live hours/orders are always for today';
      } else {
        dateEl.readOnly = false;
        dateEl.disabled = false;
        dateEl.removeAttribute('title');
      }
    }
  }

  function selectableCount() {
    return document.querySelectorAll('#raWaRows .ra-wa-row:not([data-blocked]) input[type=checkbox]:checked').length;
  }

  function syncListHeader() {
    var lh = document.getElementById('raWaLh');
    if (!lh) return;
    var loaded = waLoadedType === waType;
    if (!loaded) {
      lh.textContent = 'Hit Filter to load riders';
      return;
    }
    var total = currentList().length;
    var selected = selectableCount();
    lh.textContent = 'Riders in this alert (' + total + ') · Selected ' + selected;
  }

  function syncSendUi() {
    var b = currentBinding();
    var sendBtn = document.getElementById('raWaSend');
    var fromOk = selectedFromId() > 0;
    var loaded = waLoadedType === waType;
    syncListHeader();
    if (sendBtn) {
      sendBtn.disabled = !(b && b.bound && loaded && selectableCount() > 0 && fromOk);
    }
  }

  function fillPreview(r) {
    waSelected = r || null;
    var nameEl = document.getElementById('raWaPName');
    var subEl = document.getElementById('raWaPSub');
    var av = document.getElementById('raWaPAv');
    var textEl = document.getElementById('raWaMsgText');
    var msgEl = document.getElementById('raWaMsgPreview');
    var loaded = waLoadedType === waType;
    var broken = hasPreviewError(r);
    if (msgEl) msgEl.classList.toggle('is-err', broken);
    if (r) {
      if (nameEl) nameEl.textContent = r.name || '—';
      if (subEl) subEl.textContent = (r.phone ? ('+' + r.phone) : 'No phone') + ' · WhatsApp';
      setAvatar(av, r);
      if (textEl) textEl.textContent = previewText(r);
    } else {
      if (nameEl) nameEl.textContent = '—';
      if (subEl) subEl.textContent = 'Rider · WhatsApp';
      if (textEl) textEl.textContent = loaded
        ? 'Select a rider to preview the message.'
        : 'Hit Filter to load riders for this alert.';
    }
    syncSendUi();
  }

  function renderWaRows() {
    var rows = document.getElementById('raWaRows');
    var lh = document.getElementById('raWaLh');
    if (!rows) return;
    var loaded = waLoadedType === waType;
    var list = currentList();
    document.querySelectorAll('#raWaTypes [data-cnt]').forEach(function (el) {
      var k = el.getAttribute('data-cnt');
      if (waLoadedType === k && waPayload.types && waPayload.types[k]) {
        el.textContent = String(waPayload.types[k].length);
      } else if (waLoadedType !== k) {
        // keep previous count for other tabs until they are re-filtered
      }
    });
    if (!loaded) {
      if (lh) lh.textContent = 'Hit Filter to load riders';
      rows.innerHTML = '<div class="ra-wa-empty">Set parameters and press Filter.</div>';
      fillPreview(null);
      return;
    }
    if (!list.length) {
      if (lh) lh.textContent = 'Riders in this alert (0) · Selected 0';
      rows.innerHTML = '<div class="ra-wa-empty">No riders match these filters.</div>';
      fillPreview(null);
      return;
    }
    rows.innerHTML = list.map(function (r, i) {
      var details = [esc(r.code)];
      details.push(r.phone ? ('+' + esc(r.phone)) : 'no phone');
      if (r.sup) details.push(esc(r.sup));
      if (r.days_absent != null && r.days_absent !== '' && (waType === 'off' || waType === 'off2' || waType === 'off4' || waType === 'off6' || waType === 'abs')) {
        details.push(esc(r.days_absent) + 'd off');
      }
      if (r.hours != null && r.hours !== '') details.push(esc(r.hours) + 'h');
      if (r.orders != null && r.orders !== '') details.push(esc(r.orders) + ' orders');
      var broken = hasPreviewError(r);
      var tip = broken
        ? ('Cannot send — missing: ' + (missingLabels(r).join(', ') || 'required values'))
        : '';
      var errIcon = broken
        ? '<span class="ra-wa-err" title="' + esc(tip) + '" aria-label="' + esc(tip) + '">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
          '</span>'
        : '';
      return '<div class="ra-wa-row' + (i === 0 ? ' sel' : '') + (broken ? ' is-err' : '') + '" data-id="' + r.id + '"' + (broken ? ' data-blocked="1"' : '') + '>' +
        '<input type="checkbox"' + (broken ? ' disabled title="' + esc(tip) + '"' : ' checked') + '>' +
        '<div class="av" data-ini="' + esc(r.ini || 'R') + '"><span class="ra-av-ini">' + esc(r.ini || 'R') + '</span></div>' +
        '<div><b>' + esc(r.name) + '</b><small>' + details.join(' · ') + '</small></div>' +
        '<div class="rt">' + errIcon + '</div></div>';
    }).join('');
    rows.querySelectorAll('.ra-wa-row').forEach(function (row) {
      row.addEventListener('click', function (e) {
        if (e.target && e.target.tagName === 'INPUT') return;
        rows.querySelectorAll('.ra-wa-row').forEach(function (x) { x.classList.remove('sel'); });
        row.classList.add('sel');
        var id = +row.getAttribute('data-id');
        var found = list.find(function (x) { return x.id === id; });
        fillPreview(found || null);
      });
      var cb = row.querySelector('input[type=checkbox]');
      if (cb && !cb.disabled) {
        cb.addEventListener('change', function () {
          if (waAll) {
            var selectable = rows.querySelectorAll('.ra-wa-row:not([data-blocked]) input[type=checkbox]');
            waAll.checked = selectable.length > 0 && Array.prototype.every.call(selectable, function (c) { return c.checked; });
          }
          syncSendUi();
        });
      }
    });
    if (waAll) {
      var selectable = rows.querySelectorAll('.ra-wa-row:not([data-blocked]) input[type=checkbox]');
      waAll.checked = selectable.length > 0 && Array.prototype.every.call(selectable, function (c) { return c.checked; });
      waAll.disabled = selectable.length === 0;
    }
    syncListHeader();
    fillPreview(list[0] || null);
  }

  function appendFilterParams(body) {
    body.append('alert_type', waType);
    body.append('project_id', (document.getElementById('raWaProj') || {}).value || '');
    body.append('fleet_supervisor', (document.getElementById('raWaSup') || {}).value || '');
    if (waType === 'abs' || waType === 'off' || waType === 'off2' || waType === 'off4' || waType === 'off6'
      || waType === 'hrs' || waType === 'ord' || waType === 'lhrs' || waType === 'lord') {
      var dateEl = document.getElementById('raWaDate');
      var mode = (waDr && waDr.getAttribute('data-wa-mode')) || 'activities';
      var alertDate = (dateEl && dateEl.value) || '';
      if (mode === 'live' && (waType === 'lhrs' || waType === 'lord') && dateEl) {
        alertDate = dateEl.getAttribute('data-default-today') || alertDate;
      }
      body.append('alert_date', alertDate);
    }
    body.append('days_threshold', (document.getElementById('raWaDays') || {}).value || '2');
    body.append('hours_target', (document.getElementById('raWaHrs') || {}).value || '10');
    body.append('orders_target', (document.getElementById('raWaOrd') || {}).value || '15');
  }

  function openWa(o) {
    if (!waDr || !waOv) return;
    waDr.hidden = !o;
    waOv.hidden = !o;
    if (o) {
      syncFilterFields();
      renderWaRows();
    }
  }
  if (waBtn) waBtn.addEventListener('click', function () { openWa(true); });
  var waMenuBtn = document.getElementById('raWaMenuBtn');
  if (waMenuBtn) {
    waMenuBtn.addEventListener('click', function () {
      if (opsMenu) opsMenu.classList.remove('show');
      openWa(true);
    });
  }
  if (waX) waX.addEventListener('click', function () { openWa(false); });
  if (waOv) waOv.addEventListener('click', function () { openWa(false); });

  document.querySelectorAll('#raWaTypes button').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('#raWaTypes button').forEach(function (x) { x.classList.toggle('on', x === b); });
      waType = b.getAttribute('data-type') || waType;
      syncFilterFields();
      renderWaRows();
    });
  });
  syncFilterFields();

  var waAll = document.getElementById('raWaAll');
  if (waAll) {
    waAll.addEventListener('change', function () {
      document.querySelectorAll('#raWaRows .ra-wa-row:not([data-blocked]) input[type=checkbox]').forEach(function (c) {
        c.checked = waAll.checked;
      });
      syncSendUi();
    });
  }

  var filterBtn = document.getElementById('raWaFilter');
  if (filterBtn && waDr) {
    filterBtn.addEventListener('click', function () {
      var url = waDr.getAttribute('data-wa-filter-url');
      var csrf = waDr.getAttribute('data-wa-csrf');
      var body = new FormData();
      body.append('_token', csrf || '');
      body.append('mode', waDr.getAttribute('data-wa-mode') || 'activities');
      appendFilterParams(body);
      filterBtn.disabled = true;
      var rows = document.getElementById('raWaRows');
      var lh = document.getElementById('raWaLh');
      if (lh) lh.textContent = 'Loading riders…';
      if (rows) {
        rows.innerHTML = '<div class="ra-wa-empty ra-wa-loading" role="status" aria-live="polite">' +
          '<span class="ra-wa-spin" aria-hidden="true"></span>' +
          '<span>Loading riders…</span>' +
          '</div>';
      }
      fetch(url, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          waPayload = data || { types: {}, bindings: {} };
          waLoadedType = waType;
          var cnt = document.querySelector('#raWaTypes [data-cnt="' + waType + '"]');
          if (cnt) cnt.textContent = String((waPayload.types && waPayload.types[waType]) ? waPayload.types[waType].length : 0);
          renderWaRows();
          toast('Filter applied');
        })
        .catch(function () {
          if (rows) rows.innerHTML = '<div class="ra-wa-empty">Filter failed. Try again.</div>';
          if (lh) lh.textContent = 'Hit Filter to load riders';
          toast('Filter failed');
        })
        .finally(function () { filterBtn.disabled = false; });
    });
  }

  var fromSel = document.getElementById('raWaFrom');
  if (fromSel) fromSel.addEventListener('change', syncSendUi);

  var sendBtn = document.getElementById('raWaSend');
  if (sendBtn && waDr) {
    sendBtn.addEventListener('click', function () {
      var b = currentBinding();
      if (!b || !b.bound) { toast(UNBOUND_MSG); return; }
      var fromId = selectedFromId();
      if (!fromId) { toast('Select a WhatsApp number to send from'); return; }
      if (waLoadedType !== waType) { toast('Hit Filter for this alert type first'); return; }
      var ids = [];
      document.querySelectorAll('#raWaRows .ra-wa-row:not([data-blocked])').forEach(function (row) {
        var cb = row.querySelector('input[type=checkbox]');
        if (cb && !cb.disabled && cb.checked) ids.push(+row.getAttribute('data-id'));
      });
      if (!ids.length) { toast('Select at least one sendable rider'); return; }
      var url = waDr.getAttribute('data-wa-send-url');
      var csrf = waDr.getAttribute('data-wa-csrf');
      var body = new FormData();
      body.append('_token', csrf || '');
      body.append('mode', waDr.getAttribute('data-wa-mode') || 'activities');
      body.append('whatsapp_connection_id', String(fromId));
      appendFilterParams(body);
      ids.forEach(function (id) { body.append('rider_ids[]', String(id)); });
      sendBtn.disabled = true;
      fetch(url, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          toast((res.j && res.j.message) ? res.j.message : (res.ok ? 'Queued' : 'Send failed'));
        })
        .catch(function () { toast('Send failed'); })
        .finally(function () { syncSendUi(); });
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      openWa(false);
      if (opsMenu) opsMenu.classList.remove('show');
    }
  });
})();
</script>

<script>
(function () {
  function toast(msg, ms) {
    var el = document.getElementById('raToast');
    if (!el) return;
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(function () { el.classList.remove('show'); }, ms || 2200);
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

  var WA_AV_COLORS = [['#d9fdd3', '#008069'], ['#e7f0ff', '#1d4ed8'], ['#fff4e5', '#b45309'], ['#fde8e8', '#c81e1e'], ['#f3e8ff', '#7e22ce'], ['#e0f7fa', '#00796b']];

  function avColor(r) {
    var n = Math.abs(parseInt(r && r.id, 10) || 0);
    return WA_AV_COLORS[n % WA_AV_COLORS.length];
  }

  function avatarHtml(r, className) {
    var ini = (r && r.ini) || 'R';
    var c = avColor(r);
    var photo = r && r.photo ? String(r.photo) : '';
    var cls = className || 'wa-av';
    var html = '<div class="' + cls + '" data-ini="' + esc(ini) + '" style="background:' + c[0] + ';color:' + c[1] + '">' +
      '<span class="ra-av-ini">' + esc(ini) + '</span>';
    if (photo) {
      html += '<img src="' + esc(photo) + '" alt="" loading="lazy"' +
        ' onload="this.parentElement.classList.add(\'has-img\')"' +
        ' onerror="this.remove()">';
    }
    html += '</div>';
    return html;
  }

  function setAvatar(el, r) {
    if (!el) return;
    var ini = (r && r.ini) || 'R';
    var c = avColor(r);
    var photo = r && r.photo ? String(r.photo) : '';
    el.setAttribute('data-ini', ini);
    el.classList.remove('has-img');
    el.style.background = c[0];
    el.style.color = c[1];
    el.innerHTML = '<span class="ra-av-ini">' + esc(ini) + '</span>';
    if (photo) {
      var img = document.createElement('img');
      img.src = photo;
      img.alt = '';
      img.loading = 'lazy';
      img.onload = function () { el.classList.add('has-img'); };
      img.onerror = function () { img.remove(); };
      el.appendChild(img);
    }
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

  function hasNoPhone(r) {
    return !r || !String(r.phone || '').trim();
  }

  function hasMissingData(r) {
    return !hasNoPhone(r) && missingLabels(r).length > 0;
  }

  function hasPreviewError(r) {
    // Only hard-fail when unbound or phone missing / preview truly failed.
    var b = currentBinding();
    if (!b || !b.bound || !r) return false;
    if (hasNoPhone(r)) return true;
    if (r.preview_ok === true || r.preview_text) return false;
    return true;
  }

  function previewText(r) {
    var b = currentBinding();
    if (!b || !b.bound) return UNBOUND_MSG;
    if (r && hasNoPhone(r)) return 'Cannot send — this rider has no phone number.';
    if (r && r.preview_text) return String(r.preview_text);
    if (r && r.preview_error) return String(r.preview_error);
    return 'Template bound, but preview could not be built for this rider.';
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
    return document.querySelectorAll('#raWaRows .wa-row:not([data-blocked]) input[type=checkbox]:checked').length;
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
    var warnEl = document.getElementById('raWaMsgWarn');
    var loaded = waLoadedType === waType;
    var broken = hasPreviewError(r);
    var missing = hasMissingData(r);
    if (msgEl) {
      msgEl.classList.toggle('is-err', broken);
      msgEl.classList.toggle('is-warn', !broken && missing);
    }
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
    if (warnEl) {
      if (!broken && missing) {
        var labels = missingLabels(r);
        warnEl.hidden = false;
        warnEl.textContent = 'Missing data will send as "-": ' + labels.join(', ') + '.';
      } else {
        warnEl.hidden = true;
        warnEl.textContent = '';
      }
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
      var rowsForType = (waPayload.types && waPayload.types[k]) ? waPayload.types[k] : null;
      // Filtered type shows its count; other tabs reset to 0 until they are filtered.
      el.textContent = String(rowsForType ? rowsForType.length : 0);
    });
    if (!loaded) {
      if (lh) lh.textContent = 'Hit Filter to load riders';
      rows.innerHTML = '<div class="wa-empty">Set parameters and press Filter.</div>';
      fillPreview(null);
      return;
    }
    if (!list.length) {
      if (lh) lh.textContent = 'Riders in this alert (0) · Selected 0';
      rows.innerHTML = '<div class="wa-empty">No riders match these filters.</div>';
      fillPreview(null);
      return;
    }
    rows.innerHTML = list.map(function (r, i) {
      var sub = esc(r.code);
      var tag = r.project_name || r.project || '';
      if (tag) sub += ' · <em>' + esc(tag) + '</em>';
      var noPhone = hasNoPhone(r);
      var missing = hasMissingData(r);
      if (noPhone) sub += ' · no phone';
      var chips = '';
      var showDays = r.days_absent != null && r.days_absent !== '' &&
        (waType === 'off' || waType === 'off2' || waType === 'off4' || waType === 'off6' || waType === 'abs' || waType === 'labs');
      if (showDays) chips += '<span class="wa-chip d">' + esc(r.days_absent) + 'd off</span>';
      if (r.hours != null && r.hours !== '') chips += '<span class="wa-chip h">' + esc(r.hours) + 'h</span>';
      if (r.orders != null && r.orders !== '') chips += '<span class="wa-chip o">' + esc(r.orders) + ' orders</span>';
      var tip = noPhone
        ? 'Cannot send — no phone number'
        : (missing ? ('Missing data will send as "-": ' + missingLabels(r).join(', ')) : '');
      if (noPhone || missing) {
        chips += '<span class="wa-err" title="' + esc(tip) + '" aria-label="' + esc(tip) + '">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
          '</span>';
      }
      // No phone: blocked. Missing data: selectable but unchecked by default.
      var checked = !noPhone && !missing;
      var profileLink = r.activities_url
        ? ('<a class="wa-profile-link" href="' + esc(r.activities_url) + '" target="_blank" rel="noopener" title="Open rider activities" aria-label="Open rider activities" onclick="event.stopPropagation()">' +
          '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>' +
          '</a>')
        : '';
      return '<div class="wa-row' + (noPhone ? ' is-err' : '') + (missing ? ' is-warn' : '') + '" data-id="' + r.id + '"' + (noPhone ? ' data-blocked="1"' : '') + '>' +
        '<input type="checkbox"' + (noPhone ? ' disabled title="' + esc(tip) + '"' : (checked ? ' checked' : '')) + (missing && tip ? ' title="' + esc(tip) + '"' : '') + '>' +
        avatarHtml(r) +
        '<div class="wa-tx"><b title="' + esc(r.phone ? '+' + r.phone : 'No phone') + '">' + esc(r.name) + '</b><small>' + sub + '</small>' +
        '<span class="wa-chips">' + chips + '</span></div>' + profileLink + '</div>';
    }).join('');
    rows.querySelectorAll('.wa-row').forEach(function (row) {
      row.addEventListener('click', function (e) {
        if (e.target && (e.target.tagName === 'INPUT' || e.target.closest('.wa-profile-link'))) return;
        rows.querySelectorAll('.wa-row').forEach(function (x) { x.classList.remove('sel'); });
        row.classList.add('sel');
        var id = +row.getAttribute('data-id');
        var found = list.find(function (x) { return x.id === id; });
        fillPreview(found || null);
      });
      var cb = row.querySelector('input[type=checkbox]');
      if (cb && !cb.disabled) {
        cb.addEventListener('change', function () {
          if (waAll) {
            var selectable = rows.querySelectorAll('.wa-row:not([data-blocked]) input[type=checkbox]');
            waAll.checked = selectable.length > 0 && Array.prototype.every.call(selectable, function (c) { return c.checked; });
          }
          syncSendUi();
        });
      }
    });
    if (waAll) {
      var selectable = rows.querySelectorAll('.wa-row:not([data-blocked]) input[type=checkbox]');
      waAll.checked = selectable.length > 0 && Array.prototype.every.call(selectable, function (c) { return c.checked; });
      waAll.disabled = selectable.length === 0;
    }
    syncListHeader();
    // Prefer first rider with a phone for the initial preview selection.
    var firstPreview = list.find(function (x) { return !hasNoPhone(x); }) || list[0] || null;
    if (firstPreview) {
      var firstRow = rows.querySelector('.wa-row[data-id="' + firstPreview.id + '"]');
      if (firstRow) firstRow.classList.add('sel');
    }
    fillPreview(firstPreview);
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
      waPlaceRsz();
    } else {
      var cp = document.getElementById('raWaCust');
      if (cp) cp.hidden = true;
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
      document.querySelectorAll('#raWaRows .wa-row:not([data-blocked]) input[type=checkbox]').forEach(function (c) {
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
        rows.innerHTML = '<div class="wa-empty wa-loading" role="status" aria-live="polite">' +
          '<span class="wa-spin" aria-hidden="true"></span>' +
          '<span>Loading riders…</span>' +
          '</div>';
      }
      fetch(url, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          waPayload = data || { types: {}, bindings: {} };
          waLoadedType = waType;
          renderWaRows();
          var excluded = parseInt((data && data.excluded_already_sent) || 0, 10) || 0;
          toast(excluded > 0
            ? ('Filter applied · hid ' + excluded + ' already messaged')
            : 'Filter applied');
          var note = document.getElementById('raWaNote');
          if (note) {
            note.textContent = excluded > 0
              ? ('Each selected rider gets the message template. Already messaged for these params are hidden (' + excluded + ').')
              : 'Each selected rider gets the message template on their phone number.';
          }
        })
        .catch(function () {
          if (rows) rows.innerHTML = '<div class="wa-empty">Filter failed. Try again.</div>';
          if (lh) lh.textContent = 'Hit Filter to load riders';
          toast('Filter failed');
        })
        .finally(function () { filterBtn.disabled = false; });
    });
  }

  var fromSel = document.getElementById('raWaFrom');
  if (fromSel) fromSel.addEventListener('change', syncSendUi);

  function clearFilteredList() {
    if (!waPayload.types) waPayload.types = {};
    waPayload.types[waType] = [];
    waLoadedType = null;
    waSelected = null;
    if (waAll) {
      waAll.checked = false;
      waAll.disabled = true;
    }
    renderWaRows();
    var note = document.getElementById('raWaNote');
    if (note) note.textContent = 'Each selected rider gets the message template on their phone number.';
  }

  var sendBtn = document.getElementById('raWaSend');
  if (sendBtn && waDr) {
    sendBtn.addEventListener('click', function () {
      var b = currentBinding();
      if (!b || !b.bound) { toast(UNBOUND_MSG); return; }
      var fromId = selectedFromId();
      if (!fromId) { toast('Select a WhatsApp number to send from'); return; }
      if (waLoadedType !== waType) { toast('Hit Filter for this alert type first'); return; }
      var ids = [];
      document.querySelectorAll('#raWaRows .wa-row:not([data-blocked])').forEach(function (row) {
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

      var rows = document.getElementById('raWaRows');
      var lh = document.getElementById('raWaLh');
      var filterBtn = document.getElementById('raWaFilter');
      sendBtn.disabled = true;
      sendBtn.classList.add('is-sending');
      if (filterBtn) filterBtn.disabled = true;
      if (lh) lh.textContent = 'Sending…';
      if (rows) {
        rows.innerHTML = '<div class="wa-empty wa-loading" role="status" aria-live="polite">' +
          '<span class="wa-spin" aria-hidden="true"></span>' +
          '<span>Sending messages…</span>' +
          '</div>';
      }

      fetch(url, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
          var msg = (res.j && res.j.message) ? res.j.message : (res.ok ? 'Messages queued.' : 'Send failed');
          if (res.ok) {
            clearFilteredList();
            toast(msg, 3600);
          } else {
            renderWaRows();
            toast(msg, 3200);
          }
        })
        .catch(function () {
          renderWaRows();
          toast('Send failed', 3200);
        })
        .finally(function () {
          sendBtn.classList.remove('is-sending');
          if (filterBtn) filterBtn.disabled = false;
          syncSendUi();
        });
    });
  }

  /* ---- Column resizer (list | chat) ---- */
  var waRsz = document.getElementById('raWaRsz');
  var waList = document.getElementById('raWaList');
  var waDrag = false;

  function waPlaceRsz() {
    if (!waRsz || !waDr || !waList || waDr.hidden) return;
    requestAnimationFrame(function () {
      var a = waDr.getBoundingClientRect();
      var l = waList.getBoundingClientRect();
      waRsz.style.left = (l.right - a.left) + 'px';
      waRsz.style.top = (l.top - a.top) + 'px';
      waRsz.style.bottom = '0';
    });
  }
  window.addEventListener('resize', waPlaceRsz);
  // Sidebar collapse/expand changes the drawer width.
  if (window.MutationObserver) {
    new MutationObserver(function () { setTimeout(waPlaceRsz, 320); })
      .observe(document.body, { attributes: true, attributeFilter: ['class'] });
  }

  function waSetListWidth(p) {
    p = Math.round(Math.max(18, Math.min(65, p)));
    waC.lw = p;
    waApplyPrefs(true);
    var v = document.getElementById('v_lw');
    if (v) v.textContent = p + '%';
    var i = document.querySelector('#raWaCustRows [data-k=lw]');
    if (i) i.value = p;
  }
  function waStopDrag() {
    if (!waDrag) return;
    waDrag = false;
    if (waRsz) waRsz.classList.remove('drag');
    document.body.classList.remove('wa-dragging');
  }
  if (waRsz && waDr) {
    waRsz.addEventListener('pointerdown', function (e) {
      waDrag = true;
      waRsz.classList.add('drag');
      document.body.classList.add('wa-dragging');
      waRsz.setPointerCapture(e.pointerId);
    });
    waRsz.addEventListener('pointermove', function (e) {
      if (!waDrag) return;
      var a = waDr.getBoundingClientRect();
      waSetListWidth((e.clientX - a.left) / a.width * 100);
    });
    waRsz.addEventListener('pointerup', waStopDrag);
    waRsz.addEventListener('pointercancel', waStopDrag);
    waRsz.addEventListener('dblclick', function () { waSetListWidth(WA_DEF.lw); });
  }

  /* ---- Customize panel (CSS variables; optional debounced POST to data-wa-prefs-url) ---- */
  var WA_DEF = { accent: '#00a884', bubble: '#d9fdd3', chat: '#efeae2', doodle: true, panel: '#ffffff', head: '#ffffff', lw: 34, ns: 17, av: 50, rh: 74, mf: 15, mw: 62 };
  var WA_FIELDS = [
    ['accent', 'Main color (buttons)', 'c'], ['bubble', 'Message bubble', 'c'], ['chat', 'Chat background', 'c'],
    ['doodle', 'Doodle pattern', 'b'], ['panel', 'Left panel background', 'c'], ['head', 'Chat header', 'c'],
    ['lw', 'List width', 's', 18, 65, '%'], ['ns', 'Name size', 's', 12, 24, 'px'], ['av', 'Photo size', 's', 30, 72, 'px'],
    ['rh', 'Row height', 's', 50, 110, 'px'], ['mf', 'Message text', 's', 11, 22, 'px'], ['mw', 'Message width', 's', 35, 95, '%']
  ];
  var WA_LS_KEY = 'waAlertsUiPrefs';
  var waC = Object.assign({}, WA_DEF);
  // Prefer server prefs (cross-device); fall back to local cache only if server absent.
  try {
    var serverPrefs = waDr && waDr.getAttribute('data-wa-prefs');
    if (serverPrefs) {
      Object.assign(waC, JSON.parse(serverPrefs));
    } else {
      Object.assign(waC, JSON.parse(localStorage.getItem(WA_LS_KEY) || '{}'));
    }
  } catch (e) {
    try { Object.assign(waC, JSON.parse(localStorage.getItem(WA_LS_KEY) || '{}')); } catch (e2) {}
  }
  var waPrefsTimer = null;

  function waApplyPrefs(skipSave) {
    if (!waDr) return;
    var s = waDr.style;
    s.setProperty('--accent', waC.accent);
    s.setProperty('--bubble', waC.bubble);
    s.setProperty('--chat', waC.chat);
    s.setProperty('--panel', waC.panel);
    s.setProperty('--head', waC.head);
    s.setProperty('--lw', waC.lw + '%');
    s.setProperty('--ns', waC.ns + 'px');
    s.setProperty('--av', waC.av + 'px');
    s.setProperty('--rh', waC.rh + 'px');
    s.setProperty('--mf', waC.mf + 'px');
    s.setProperty('--mw', waC.mw + '%');
    waDr.classList.toggle('nodoodle', !waC.doodle);
    waPlaceRsz();
    if (skipSave === 'nosave') return;
    try { localStorage.setItem(WA_LS_KEY, JSON.stringify(waC)); } catch (e) {}
    waQueuePrefsSave();
  }

  function waQueuePrefsSave() {
    var url = waDr && waDr.getAttribute('data-wa-prefs-url');
    if (!url) return;
    clearTimeout(waPrefsTimer);
    waPrefsTimer = setTimeout(function () {
      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': waDr.getAttribute('data-wa-csrf') || ''
        },
        body: JSON.stringify({ prefs: waC })
      }).catch(function () {});
    }, 600);
  }

  function waBuildCust() {
    var box = document.getElementById('raWaCustRows');
    if (!box) return;
    box.innerHTML = WA_FIELDS.map(function (f) {
      var k = f[0], l = f[1], t = f[2];
      if (t === 'c') return '<div class="r"><span>' + l + '</span><input type="color" data-k="' + k + '" value="' + waC[k] + '"></div>';
      if (t === 'b') return '<div class="r"><span>' + l + '</span><input type="checkbox" data-k="' + k + '"' + (waC[k] ? ' checked' : '') + '></div>';
      return '<div class="r"><span>' + l + '</span><input type="range" data-k="' + k + '" min="' + f[3] + '" max="' + f[4] + '" value="' + waC[k] + '"><span class="v" id="v_' + k + '">' + waC[k] + f[5] + '</span></div>';
    }).join('');
  }

  var waCustRows = document.getElementById('raWaCustRows');
  var waCust = document.getElementById('raWaCust');
  if (waCustRows) {
    waCustRows.addEventListener('input', function (e) {
      var k = e.target.getAttribute('data-k');
      if (!k) return;
      var f = WA_FIELDS.filter(function (x) { return x[0] === k; })[0];
      if (!f) return;
      waC[k] = e.target.type === 'checkbox' ? e.target.checked : (e.target.type === 'range' ? +e.target.value : e.target.value);
      if (f[2] === 's') {
        var v = document.getElementById('v_' + k);
        if (v) v.textContent = waC[k] + f[5];
      }
      waApplyPrefs();
    });
  }
  var waGear = document.getElementById('raWaGear');
  if (waGear && waCust) {
    waGear.addEventListener('click', function () {
      waBuildCust();
      waCust.hidden = !waCust.hidden;
    });
  }
  var waCustX = document.getElementById('raWaCustX');
  var waCustDone = document.getElementById('raWaCustDone');
  var waCustReset = document.getElementById('raWaCustReset');
  if (waCustX && waCust) waCustX.addEventListener('click', function () { waCust.hidden = true; });
  if (waCustDone && waCust) waCustDone.addEventListener('click', function () { waCust.hidden = true; });
  if (waCustReset) {
    waCustReset.addEventListener('click', function () {
      waC = Object.assign({}, WA_DEF);
      waApplyPrefs();
      waBuildCust();
    });
  }
  waApplyPrefs('nosave');

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (waCust && !waCust.hidden) { waCust.hidden = true; return; }
      openWa(false);
      if (opsMenu) opsMenu.classList.remove('show');
    }
  });
})();
</script>

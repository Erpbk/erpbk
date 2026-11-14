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
    var t;
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

  // WhatsApp drawer (UI only)
  var waBtn = document.getElementById('raWaBtn');
  var waOv = document.getElementById('raWaOv');
  var waDr = document.getElementById('raWaDrawer');
  var waX = document.getElementById('raWaX');
  var waPayload = { types: {}, all: [], date: '' };
  try {
    waPayload = JSON.parse(waDr && waDr.getAttribute('data-wa-payload') || '{}');
  } catch (e) {}
  var waType = (document.querySelector('#raWaTypes button.on') || {}).getAttribute
    ? document.querySelector('#raWaTypes button.on').getAttribute('data-type')
    : 'abs';
  var waSelected = null;
  var waUsingAll = false;
  var waChannel = 'wa';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function fmtDateLabel(ymd) {
    if (!ymd) return '—';
    var p = ymd.split('-');
    if (p.length !== 3) return ymd;
    var d = new Date(+p[0], +p[1] - 1, +p[2]);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function waMode() {
    var el = document.getElementById('raWaMode');
    return (el && el.value) || 'rider';
  }

  function isGroupMode() {
    return waMode() === 'group';
  }

  function currentList() {
    var list = (waPayload.types && waPayload.types[waType]) ? waPayload.types[waType].slice() : [];
    waUsingAll = false;
    if (!list.length) {
      list = (waPayload.all || []).slice();
      waUsingAll = true;
    }
    return list;
  }

  function setAvatar(el, r, fallbackIni) {
    if (!el) return;
    var ini = (r && r.ini) || fallbackIni || 'R';
    var photo = r && r.photo ? String(r.photo) : '';
    el.setAttribute('data-ini', ini);
    el.classList.remove('has-img');
    if (photo) {
      el.innerHTML = '<span class="ra-av-ini">' + esc(ini) + '</span>' +
        '<img src="' + esc(photo) + '" alt="" onload="this.parentElement.classList.add(\'has-img\')" onerror="this.remove()">';
    } else {
      el.innerHTML = '<span class="ra-av-ini">' + esc(ini) + '</span>';
    }
  }

  function photoEnabled() {
    var photoChk = document.getElementById('raWaPhoto');
    return !!(photoChk && photoChk.checked);
  }

  function personalMessage(r) {
    var tpl = document.getElementById('raWaTpl');
    var dateEl = document.getElementById('raWaDate');
    var daysEl = document.getElementById('raWaDays');
    var hrsEl = document.getElementById('raWaHrs');
    var ordEl = document.getElementById('raWaOrd');
    var projEl = document.getElementById('raWaProj');
    var dateVal = (dateEl && dateEl.value) || waPayload.date || '';
    var projectLabel = '';
    if (projEl && projEl.selectedIndex > 0) {
      projectLabel = projEl.options[projEl.selectedIndex].text || '';
    }
    var raw = (tpl && tpl.value) || '';
    return raw
      .replace(/\{name\}/g, (r && r.name) || '')
      .replace(/\{id\}/g, (r && r.code) || '')
      .replace(/\{mobile\}/g, '')
      .replace(/\{days\}/g, (daysEl && daysEl.value) || '2')
      .replace(/\{last_date\}/g, fmtDateLabel(dateVal))
      .replace(/\{supervisor\}/g, (r && r.sup) || 'your supervisor')
      .replace(/\{project\}/g, projectLabel)
      .replace(/\{hours\}/g, (hrsEl && hrsEl.value) || '10')
      .replace(/\{orders\}/g, '0')
      .replace(/\{target\}/g, (ordEl && ordEl.value) || '15')
      .replace(/\{date\}/g, fmtDateLabel(dateVal));
  }

  function messageBubbleHtml(r, opts) {
    opts = opts || {};
    var showPhoto = !!(photoEnabled() && r && r.photo);
    var highlight = opts.highlight ? ' sel-msg' : '';
    var photo = showPhoto
      ? '<div class="ra-wa-photo"><img src="' + esc(r.photo) + '" alt="' + esc((r.name || 'Rider') + ' photo') + '"><span>' + esc(r.name || 'Rider') + ' photo</span></div>'
      : '';
    var who = opts.showName
      ? '<div class="who">' + esc(r.name || 'Rider') + ' · ' + esc(r.code || '') + '</div>'
      : '';
    return '<div class="ra-wa-msg' + highlight + '" data-id="' + (r && r.id ? r.id : '') + '">' +
      who + photo +
      '<div class="txt">' + esc(personalMessage(r)) + '</div>' +
      '<div class="meta"><span>Preview</span></div>' +
      '</div>';
  }

  function syncModeUi() {
    var group = isGroupMode();
    var gWrap = document.getElementById('raWaGWrap');
    var note = document.getElementById('raWaNote');
    if (gWrap) gWrap.hidden = !(waChannel === 'wa' && group);
    if (note && waChannel === 'wa') {
      note.textContent = group
        ? 'Each rider message is posted separately into the WhatsApp group.'
        : 'Each rider gets a separate message on their own WhatsApp number.';
    }
  }

  function fillPreview(r) {
    var nameEl = document.getElementById('raWaPName');
    var subEl = document.getElementById('raWaPSub');
    var av = document.getElementById('raWaPAv');
    var stack = document.getElementById('raWaMsgStack');
    var chat = document.getElementById('raWaChat');
    var groupEl = document.getElementById('raWaGroup');
    var list = currentList();
    var group = isGroupMode();
    if (!stack) return;

    if (group) {
      var gName = (groupEl && groupEl.value.trim()) || 'Express – Fleet Supervisors';
      if (nameEl) nameEl.textContent = gName;
      if (subEl) subEl.textContent = 'Group · separate messages';
      if (av) {
        av.textContent = 'GR';
        av.classList.remove('has-img');
      }
      var checked = [];
      document.querySelectorAll('#raWaRows .ra-wa-row').forEach(function (row) {
        var cb = row.querySelector('input[type=checkbox]');
        if (cb && !cb.checked) return;
        var id = +row.getAttribute('data-id');
        var found = list.find(function (x) { return x.id === id; });
        if (found) checked.push(found);
      });
      if (!checked.length) checked = list.slice();
      var focusId = r && r.id ? r.id : (waSelected && waSelected.id);
      var previewList = checked.slice(0, 12);
      var more = checked.length > previewList.length
        ? '<div class="ra-wa-more">+' + (checked.length - previewList.length) + ' more rider messages in group…</div>'
        : '';
      stack.innerHTML = previewList.map(function (item) {
        return messageBubbleHtml(item, { showName: true, highlight: item.id === focusId });
      }).join('') + more;
      if (chat) {
        var selMsg = stack.querySelector('.ra-wa-msg.sel-msg') || stack.querySelector('.ra-wa-msg');
        if (selMsg) selMsg.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
      return;
    }

    if (!r) {
      stack.innerHTML = '<div class="ra-wa-empty">Select a rider to preview.</div>';
      return;
    }
    if (nameEl) nameEl.textContent = r.name;
    if (subEl) subEl.textContent = 'Rider · WhatsApp';
    setAvatar(av, r, 'R');
    stack.innerHTML = messageBubbleHtml(r, { showName: false, highlight: false });
  }

  function renderWaRows() {
    var rows = document.getElementById('raWaRows');
    var lh = document.getElementById('raWaLh');
    if (!rows) return;
    syncModeUi();
    var list = currentList();
    var dateEl = document.getElementById('raWaDate');
    var dateLabel = fmtDateLabel((dateEl && dateEl.value) || waPayload.date || '');
    if (lh) {
      lh.textContent = waUsingAll
        ? ('No matches for this alert on ' + dateLabel + ' — showing all riders (' + list.length + ')')
        : ('Preview list · ' + list.length + ' riders · ' + dateLabel);
    }
    if (!list.length) {
      rows.innerHTML = '<div class="ra-wa-empty">No riders available to preview.</div>';
      waSelected = null;
      fillPreview(null);
      return;
    }
    rows.innerHTML = list.map(function (r, i) {
      var avHtml = r.photo
        ? '<div class="av" data-ini="' + esc(r.ini) + '"><span class="ra-av-ini">' + esc(r.ini) + '</span><img src="' + esc(r.photo) + '" alt="" onload="this.parentElement.classList.add(\'has-img\')" onerror="this.remove()"></div>'
        : '<div class="av" data-ini="' + esc(r.ini) + '"><span class="ra-av-ini">' + esc(r.ini) + '</span></div>';
      return '<div class="ra-wa-row' + (i === 0 ? ' sel' : '') + '" data-id="' + r.id + '">' +
        '<input type="checkbox" checked aria-label="Select">' +
        avHtml +
        '<div><b>' + esc(r.name) + '</b><small>' + esc(r.code) + (r.sup ? ' · ' + esc(r.sup) : '') + '</small></div>' +
        '<div class="rt"><span class="off' + (waUsingAll ? ' is-all' : '') + '">' + (waUsingAll ? 'All' : 'Alert') + '</span></div>' +
        '</div>';
    }).join('');
    waSelected = list[0];
    fillPreview(waSelected);
    rows.querySelectorAll('.ra-wa-row').forEach(function (row) {
      row.addEventListener('click', function (e) {
        if (e.target && e.target.matches('input[type=checkbox]')) return;
        rows.querySelectorAll('.ra-wa-row').forEach(function (x) { x.classList.remove('sel'); });
        row.classList.add('sel');
        var id = +row.getAttribute('data-id');
        waSelected = list.find(function (r) { return r.id === id; }) || list[0];
        fillPreview(waSelected);
      });
      var cb = row.querySelector('input[type=checkbox]');
      if (cb) {
        cb.addEventListener('change', function () {
          if (isGroupMode()) fillPreview(waSelected);
        });
      }
    });
  }

  function openWa(o) {
    if (!waDr || !waOv) return;
    waDr.hidden = !o;
    waOv.hidden = !o;
    if (o) renderWaRows();
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
      renderWaRows();
    });
  });

  var waDate = document.getElementById('raWaDate');
  if (waDate) {
    waDate.addEventListener('change', function () {
      // UI preview only: when insight list is empty for yesterday, keep showing all riders
      // and refresh template tokens with the selected date.
      renderWaRows();
      toast(waUsingAll
        ? 'No alert matches for this date — showing all riders'
        : 'Alert date updated to ' + fmtDateLabel(waDate.value));
    });
  }

  var waModeEl = document.getElementById('raWaMode');
  if (waModeEl) {
    waModeEl.addEventListener('change', function () {
      syncModeUi();
      fillPreview(waSelected);
      toast(isGroupMode()
        ? 'Group mode: each rider message posts separately'
        : 'Preview switched to each rider (1-to-1)');
    });
  }

  var waGroupEl = document.getElementById('raWaGroup');
  if (waGroupEl) {
    waGroupEl.addEventListener('input', function () {
      if (isGroupMode()) fillPreview(waSelected);
    });
  }

  var waPhotoChk = document.getElementById('raWaPhoto');
  if (waPhotoChk) {
    waPhotoChk.addEventListener('change', function () {
      fillPreview(waSelected);
      toast(waPhotoChk.checked
        ? 'Rider photo attached on each message preview'
        : 'Rider photo hidden from preview');
    });
  }

  ['raWaDays', 'raWaHrs', 'raWaOrd', 'raWaProj'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('change', function () {
      fillPreview(waSelected);
    });
  });

  var waAll = document.getElementById('raWaAll');
  if (waAll) {
    waAll.addEventListener('change', function () {
      document.querySelectorAll('#raWaRows input[type=checkbox]').forEach(function (c) {
        c.checked = waAll.checked;
      });
      if (isGroupMode()) fillPreview(waSelected);
    });
  }

  document.querySelectorAll('#raChSw button').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('#raChSw button').forEach(function (x) { x.classList.toggle('on', x === b); });
      var em = b.getAttribute('data-ch') === 'em';
      waChannel = em ? 'em' : 'wa';
      if (waDr) waDr.classList.toggle('em', em);
      var title = document.getElementById('raChTitle');
      if (title) title.textContent = em ? 'Email Alerts' : 'WhatsApp Alerts';
      var phone = document.getElementById('raWaPhone');
      var emPrev = document.getElementById('raEmPrev');
      if (phone) phone.hidden = em;
      if (emPrev) emPrev.hidden = !em;
      var note = document.getElementById('raWaNote');
      if (note) {
        note.textContent = em
          ? 'One summary email to a list (preview only).'
          : (isGroupMode()
            ? 'Each rider message is posted separately into the WhatsApp group.'
            : 'Each rider gets a separate message on their own WhatsApp number.');
      }
      var emTo = document.getElementById('raEmToWrap');
      if (emTo) emTo.hidden = !em;
      syncModeUi();
      if (!em) fillPreview(waSelected);
    });
  });

  var tpl = document.getElementById('raWaTpl');
  if (tpl) {
    tpl.addEventListener('input', function () {
      fillPreview(waSelected);
    });
  }

  function previewOnly(e) {
    if (e) e.preventDefault();
    toast('Preview only – no messages are sent');
  }
  var send = document.getElementById('raWaSend');
  var runAuto = document.getElementById('raWaRunAuto');
  if (send) send.addEventListener('click', previewOnly);
  if (runAuto) runAuto.addEventListener('click', previewOnly);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      openWa(false);
      if (opsMenu) opsMenu.classList.remove('show');
    }
  });
})();
</script>

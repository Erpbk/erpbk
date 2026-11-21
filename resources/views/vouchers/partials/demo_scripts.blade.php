<script>
(function () {
  function ready(fn) {
    if (typeof jQuery === 'undefined') {
      setTimeout(function () { ready(fn); }, 50);
      return;
    }
    jQuery(fn);
  }

  function toast(msg) {
    var t = document.getElementById('vcToast');
    if (!t) return;
    t.hidden = false;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._t);
    t._t = setTimeout(function () {
      t.classList.remove('show');
    }, 2600);
  }
  window.vcToast = toast;

  function openModal(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.hidden = false;
    el.classList.add('open');
  }

  function closeModal(el) {
    if (!el) return;
    el.classList.remove('open');
    el.hidden = true;
  }

  ready(function ($) {
    var $actBtn = $('#vcActBtn');
    var $actMenu = $('#vcActMenu');

    $actBtn.on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $actMenu.toggleClass('open');
      $actBtn.attr('aria-expanded', $actMenu.hasClass('open') ? 'true' : 'false');
    });

    $(document).on('click', function (e) {
      if (!$(e.target).closest('#vcActBtn, #vcActMenu').length) {
        $actMenu.removeClass('open');
        $actBtn.attr('aria-expanded', 'false');
      }
      if (!$(e.target).closest('.vc-dots, #vcRowMenu').length) {
        var rm = document.getElementById('vcRowMenu');
        if (rm) {
          rm.hidden = true;
          rm.classList.remove('open');
        }
      }
    });

    $('#vcExportBtn').on('click', function () {
      $actMenu.removeClass('open');
      $actBtn.attr('aria-expanded', 'false');
      // Open Column Control so the user can pick Excel / CSV / PDF with visible columns.
      $('.openColumnControlSidebar').first().trigger('click');
      toast('Choose Excel, CSV, or PDF in Column Control');
    });

    function vcImpInitSelect2() {
      var $sel = $('#vc_payment_from');
      if (!$sel.length || typeof $.fn.select2 === 'undefined') return;
      if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.select2('destroy');
      }
      $sel.select2({
        width: '100%',
        allowClear: true,
        placeholder: $sel.data('placeholder') || 'Select credit account',
        dropdownParent: $('#vcImpM')
      });
    }

    function vcImpReadEmbeddedDraft() {
      var el = document.getElementById('vcImpDraftJson');
      if (!el) return null;
      try {
        var raw = (el.textContent || '').trim();
        if (!raw || raw === 'null') return null;
        var data = JSON.parse(raw);
        return data && data.rows && data.rows.length ? data : null;
      } catch (e) {
        return null;
      }
    }

    function vcImpUpdateResumeUi(draft) {
      var has = !!(draft && draft.rows && draft.rows.length);
      var root = vcImpRoot();
      if (root) root.setAttribute('data-has-draft', has ? '1' : '0');
      $('#vcImportBadge').prop('hidden', !has);
      $('#vcImportHint').text(has ? 'Resume saved sheet from this session' : 'Add new vouchers from Excel or CSV');
      if (has) vcImpSetRestored(draft.saved_at || null);
      else vcImpSetRestored(null);
    }

    $('#vcImportBtn').on('click', function () {
      $actMenu.removeClass('open');
      openModal('vcImpM');
      vcImpInitSelect2();
      var draft = vcImp.draft || vcImpReadEmbeddedDraft();
      if (draft && draft.rows && draft.rows.length) {
        vcImpRestoreDraft(draft, false);
      } else {
        vcImpReset(true);
        vcImpUpdateResumeUi(null);
      }
      vcImpRefreshBalance();
    });

    $(document).on('click', '[data-vc-mclose]', function () {
      closeModal($(this).closest('.vc-modal-ov')[0]);
    });

    $(document).on('click', '.vc-modal-ov', function (e) {
      if (e.target === this) closeModal(this);
    });

    // ── Import: Excel-like editable grid ─────────────────────────────
    var vcImp = {
      rows: [],
      filter: 'all',
      q: '',
      busy: false,
      validateTimer: null,
      saveTimer: null,
      filename: '',
      draft: null,
      keys: ['d', 'm', 'id', 'p', 'a']
    };

    function vcImpRoot() {
      return document.getElementById('vcImpM');
    }

    function vcImpCfg() {
      var el = vcImpRoot();
      return {
        preview: el && el.getAttribute('data-preview-url'),
        validate: el && el.getAttribute('data-validate-url'),
        commit: el && el.getAttribute('data-commit-url'),
        draft: el && el.getAttribute('data-draft-url'),
        draftSave: el && el.getAttribute('data-draft-save-url'),
        draftClear: el && el.getAttribute('data-draft-clear-url'),
        balance: el && el.getAttribute('data-balance-url'),
        csrf: el && el.getAttribute('data-csrf')
      };
    }

    function vcImpFmtAmt(n) {
      return (parseFloat(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function vcImpAccountLabel() {
      var $sel = $('#vc_payment_from');
      var t = ($sel.find('option:selected').text() || '').trim();
      if (!t || /^select/i.test(t)) return 'selected account';
      return t;
    }

    function vcImpRefreshBalance() {
      var cfg = vcImpCfg();
      var id = String($('#vc_payment_from').val() || '').trim();
      var $bal = $('#vcImpBal');
      if (!id || !cfg.balance) {
        $bal.attr('hidden', true).text('').removeClass('low ok');
        return;
      }
      $.ajax({
        url: cfg.balance,
        method: 'GET',
        data: { account_id: id },
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        success: function (res) {
          if (!res || !res.success) {
            $bal.attr('hidden', true);
            return;
          }
          var readyAmt = 0;
          vcImp.rows.forEach(function (r) {
            if (!(r.errors && Object.keys(r.errors).length)) readyAmt += parseFloat(r.a) || 0;
          });
          var bal = parseFloat(res.balance) || 0;
          var txt = 'Balance: ' + vcImpFmtAmt(bal);
          $bal.removeClass('low ok');
          if (readyAmt > 0 && bal < readyAmt) {
            txt += ' — not enough for ' + vcImpFmtAmt(readyAmt);
            $bal.addClass('low');
          } else if (readyAmt > 0) {
            txt += ' — covers ' + vcImpFmtAmt(readyAmt);
            $bal.addClass('ok');
          }
          $bal.removeAttr('hidden').text(txt);
        }
      });
    }

    function vcImpSetRestored(savedAt) {
      var $el = $('#vcImpRestored');
      if (!savedAt) {
        $el.attr('hidden', true).text('');
        return;
      }
      try {
        var d = new Date(savedAt);
        var label = isNaN(d.getTime()) ? String(savedAt) : d.toLocaleString(undefined, {
          day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
        $el.removeAttr('hidden').text('Restored ' + label);
      } catch (e) {
        $el.removeAttr('hidden').text('Restored');
      }
    }

    function vcImpEsc(s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
      });
    }

    function vcImpSetStep(n) {
      $('#vcImpSteps .s').each(function () {
        var s = parseInt($(this).data('s'), 10);
        $(this).toggleClass('on', s === n);
        $(this).toggleClass('done', s < n);
      });
    }

    function vcImpShowErr(msg) {
      var $e = $('#vcImpFerr');
      if (!msg) {
        $e.attr('hidden', true).text('');
        return;
      }
      $e.removeAttr('hidden').text(msg);
    }

    function vcImpReset(clearFile) {
      vcImp.rows = [];
      vcImp.filter = 'all';
      vcImp.q = '';
      vcImp.filename = '';
      clearTimeout(vcImp.validateTimer);
      clearTimeout(vcImp.saveTimer);
      $('#vcImpEmpty').show();
      $('#vcImpPrev').attr('hidden', true);
      $('#vcImpSum').attr('hidden', true);
      $('#vcImpGo').prop('disabled', true).text('Start Import');
      $('#vcImpQ').val('');
      $('.vc-ifilts button').removeClass('on').filter('[data-f="all"]').addClass('on');
      vcImpShowErr('');
      vcImpSetStep(1);
      vcImpSetRestored(null);
      $('#vcImpBal').attr('hidden', true).text('').removeClass('low ok');
      if (clearFile) {
        var f = document.getElementById('vcImpFile');
        if (f) f.value = '';
        $('#vcImpFn').text('No file chosen');
      }
      $('#vcImpTb').empty();
      $('#vcImpTf').empty();
      vcImpUpdateCounts();
    }

    function vcImpSetPaymentFrom(id) {
      var $sel = $('#vc_payment_from');
      if (!$sel.length) return;
      $sel.val(id == null || id === '' ? '' : String(id)).trigger('change');
    }

    function vcImpRestoreDraft(draft, silent) {
      if (!draft || !draft.rows || !draft.rows.length) return false;
      vcImp.draft = draft;
      vcImp.filename = draft.filename || '';
      if (vcImp.filename) $('#vcImpFn').text(vcImp.filename + ' (resumed)');
      if (draft.payment_from) vcImpSetPaymentFrom(draft.payment_from);
      vcImpApplyServer({ rows: draft.rows, counts: draft.counts || {} }, 'replace');
      vcImpUpdateResumeUi(draft);
      if (!silent) toast('Resumed previous import — fix errors, then Import');
      return true;
    }

    function vcImpRememberDraft(draft) {
      if (draft && draft.rows && draft.rows.length) {
        vcImp.draft = draft;
        if (draft.filename) vcImp.filename = draft.filename;
        var el = document.getElementById('vcImpDraftJson');
        if (el) el.textContent = JSON.stringify(draft);
      } else {
        vcImp.draft = null;
        var el2 = document.getElementById('vcImpDraftJson');
        if (el2) el2.textContent = 'null';
      }
      vcImpUpdateResumeUi(vcImp.draft);
    }

    function vcImpSaveDraftSoon() {
      clearTimeout(vcImp.saveTimer);
      vcImp.saveTimer = setTimeout(vcImpSaveDraftNow, 700);
    }

    function vcImpSaveDraftNow() {
      var cfg = vcImpCfg();
      if (!cfg.draftSave || !vcImp.rows.length) return;
      $.ajax({
        url: cfg.draftSave,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          rows: vcImpPayloadRows(),
          payment_from: $('#vc_payment_from').val() || null,
          filename: vcImp.filename || null,
          _token: cfg.csrf
        }),
        headers: {
          'X-CSRF-TOKEN': cfg.csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (res) {
          if (res && res.success) {
            vcImpRememberDraft(res.draft || {
              filename: vcImp.filename,
              payment_from: $('#vc_payment_from').val() || null,
              rows: res.rows || vcImp.rows,
              counts: res.counts
            });
            if (res.rows) vcImpApplyServer(res, 'annotate');
          }
        }
      });
    }

    function vcImpClearDraft(cb) {
      var cfg = vcImpCfg();
      if (!cfg.draftClear) {
        vcImpRememberDraft(null);
        if (cb) cb();
        return;
      }
      $.ajax({
        url: cfg.draftClear,
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': cfg.csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        data: { _token: cfg.csrf },
        complete: function () {
          vcImpRememberDraft(null);
          if (cb) cb();
        }
      });
    }

    function vcImpUpdateCounts() {
      var all = vcImp.rows.length;
      var err = 0;
      var ok = 0;
      var warn = 0;
      var readyAmt = 0;
      vcImp.rows.forEach(function (r) {
        var hasErr = r.errors && Object.keys(r.errors).length;
        if (hasErr) {
          err++;
        } else {
          ok++;
          readyAmt += parseFloat(r.a) || 0;
          if (r.warnings && r.warnings.length) warn++;
        }
      });
      $('#vcImpNOk').text(ok);
      $('#vcImpNErr').text(err);
      $('#vcImpSumAll').text(all);
      $('#vcImpSumOk').text(ok);
      $('#vcImpSumErr').text(err);
      $('#vcImpSumWarn').text(warn);
      $('#vcImpSumAmt').text(vcImpFmtAmt(readyAmt));
      if (all) $('#vcImpSum').removeAttr('hidden');
      else $('#vcImpSum').attr('hidden', true);
      $('#vcImpGo').prop('disabled', ok < 1 || vcImp.busy)
        .text(ok ? ('Start Import (' + ok + ' row' + (ok === 1 ? '' : 's') + ')') : 'Start Import');
      return { all: all, ok: ok, err: err, warn: warn, total: readyAmt };
    }

    function vcImpRowVisible(r, idx) {
      var hasErr = r.errors && Object.keys(r.errors).length;
      if (vcImp.filter === 'ok' && hasErr) return false;
      if (vcImp.filter === 'err' && !hasErr) return false;
      var q = String(vcImp.q || '').trim().toLowerCase();
      if (!q) return true;
      var hay = [r.d, r.m, r.id, r.name, r.p, r.a, String(idx + 1)].join(' ').toLowerCase();
      return hay.indexOf(q) !== -1;
    }

    function vcImpCheckHtml(r) {
      var errs = r.errors || {};
      var warns = r.warnings || [];
      var keys = Object.keys(errs);
      if (keys.length) {
        return '<div class="chk"><span class="bad">✕ ' + vcImpEsc(Object.values(errs).join(' · ')) + '</span></div>';
      }
      if (warns.length) {
        return '<div class="chk"><span class="warn">⚠ ' + vcImpEsc(warns.join(' · ')) + '</span></div>';
      }
      return '<div class="chk"><span class="ok">✓ Ready</span></div>';
    }

    function vcImpRender(opts) {
      opts = opts || {};
      var focusI = opts.focusI;
      var focusK = opts.focusK;
      var focusSel = opts.focusSel;
      if (focusI == null) {
        var ae = document.activeElement;
        if (ae && ae.classList && ae.classList.contains('cell') && ae.closest('#vcImpTb')) {
          focusI = parseInt($(ae).closest('tr').data('i'), 10);
          focusK = $(ae).data('k');
          focusSel = { start: ae.selectionStart, end: ae.selectionEnd };
        }
      }
      var $tb = $('#vcImpTb');
      var html = '';
      var visibleAmt = 0;
      var visibleN = 0;
      vcImp.rows.forEach(function (r, i) {
        var hasErr = r.errors && Object.keys(r.errors).length;
        var hasWarn = !hasErr && r.warnings && r.warnings.length;
        var cls = hasErr ? 'is-err' : (hasWarn ? 'is-warn' : 'is-ok');
        var hide = !vcImpRowVisible(r, i);
        if (!hide) {
          visibleN++;
          visibleAmt += parseFloat(r.a) || 0;
        }
        var e = r.errors || {};
        html += '<tr data-i="' + i + '" class="' + cls + (hide ? ' hidden' : '') + '">' +
          '<td class="cn">' + (i + 1) + '</td>' +
          '<td><input class="cell' + (e.d ? ' has-err' : '') + '" data-k="d" value="' + vcImpEsc(r.d) + '" placeholder="dd-mm-yyyy"></td>' +
          '<td><input class="cell' + (e.m ? ' has-err' : '') + '" data-k="m" value="' + vcImpEsc(r.m) + '" placeholder="Nov-2026"></td>' +
          '<td><input class="cell' + (e.id ? ' has-err' : '') + '" data-k="id" value="' + vcImpEsc(r.id) + '" placeholder="Rider ID"></td>' +
          '<td class="ro"><span class="ro-txt" title="' + vcImpEsc(r.name || '') + '">' + vcImpEsc(r.name || '—') + '</span></td>' +
          '<td><input class="cell" data-k="p" value="' + vcImpEsc(r.p) + '" placeholder="Particulars"></td>' +
          '<td><input class="cell num' + (e.a ? ' has-err' : '') + '" data-k="a" value="' + vcImpEsc(r.a) + '" placeholder="0.00"></td>' +
          '<td class="ro">' + vcImpCheckHtml(r) + '</td>' +
          '<td class="cn"><button type="button" class="rm" title="Remove row" aria-label="Remove">×</button></td>' +
          '</tr>';
      });
      $tb.html(html);
      var counts = vcImpUpdateCounts();
      $('#vcImpTf').html(
        '<tr><td colspan="6">Total to be paid from ' + vcImpEsc(vcImpAccountLabel()) + '</td>' +
        '<td class="amt">' + vcImpFmtAmt(counts.total) + '</td>' +
        '<td colspan="2"></td></tr>'
      );
      if (vcImp.rows.length) {
        $('#vcImpEmpty').hide();
        $('#vcImpPrev').removeAttr('hidden');
        vcImpSetStep(2);
      }
      vcImpRefreshBalance();
      if (focusI != null && focusK) {
        var el = document.querySelector('#vcImpTb tr[data-i="' + focusI + '"] input.cell[data-k="' + focusK + '"]');
        if (el) {
          el.focus();
          try {
            if (focusSel && typeof focusSel.start === 'number') {
              el.setSelectionRange(focusSel.start, focusSel.end);
            }
          } catch (err) {}
        }
      }
    }

    function vcImpApplyServer(data, mode) {
      // mode: 'replace' (file preview / paste) | 'annotate' (keep local edits, refresh checks)
      var incoming = data.rows || [];
      if (mode === 'annotate' && vcImp.rows.length === incoming.length) {
        vcImp.rows = vcImp.rows.map(function (local, i) {
          var r = incoming[i] || {};
          return {
            d: local.d,
            m: local.m,
            id: local.id,
            p: local.p,
            a: local.a,
            name: r.name || '',
            errors: r.errors || {},
            warnings: r.warnings || []
          };
        });
      } else {
        vcImp.rows = incoming.map(function (r) {
          return {
            d: r.d || '',
            m: r.m || '',
            id: r.id || '',
            name: r.name || '',
            p: r.p || '',
            a: r.a != null ? r.a : '',
            errors: r.errors || {},
            warnings: r.warnings || []
          };
        });
      }
      vcImpRender();
    }

    function vcImpPayloadRows() {
      return vcImp.rows.map(function (r) {
        return { d: r.d, m: r.m, id: r.id, p: r.p, a: r.a };
      });
    }

    function vcImpValidateSoon() {
      clearTimeout(vcImp.validateTimer);
      vcImp.validateTimer = setTimeout(vcImpValidateNow, 450);
    }

    function vcImpValidateNow() {
      var cfg = vcImpCfg();
      if (!cfg.validate || !vcImp.rows.length) return;
      $.ajax({
        url: cfg.validate,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          rows: vcImpPayloadRows(),
          payment_from: $('#vc_payment_from').val() || null,
          filename: vcImp.filename || null,
          _token: cfg.csrf
        }),
        headers: {
          'X-CSRF-TOKEN': cfg.csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (res) {
          if (res && res.success) {
            vcImpApplyServer(res, 'annotate');
            if (res.draft) vcImpRememberDraft(res.draft);
          }
        }
      });
    }

    function vcImpPreviewFile(file) {
      var cfg = vcImpCfg();
      if (!file || !cfg.preview) return;
      vcImp.filename = file.name;
      $('#vcImpFn').text(file.name);
      vcImpShowErr('');
      vcImp.busy = true;
      $('#vcImpGo').prop('disabled', true).text('Reading…');
      toast('Reading file…');
      var fd = new FormData();
      fd.append('file', file);
      fd.append('_token', cfg.csrf);
      var pf = $('#vc_payment_from').val();
      if (pf) fd.append('payment_from', pf);
      $.ajax({
        url: cfg.preview,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: {
          'X-CSRF-TOKEN': cfg.csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (res) {
          if (!res || !res.success) {
            vcImpShowErr((res && res.message) || 'Could not read this file');
            toast((res && res.message) || 'Could not read this file');
            return;
          }
          if (!(res.rows || []).length) {
            vcImpShowErr('No data rows found (headers are skipped)');
            toast('No data rows found');
            vcImpReset(false);
            return;
          }
          vcImpApplyServer(res, 'replace');
          if (res.draft) vcImpRememberDraft(res.draft);
          else {
            vcImpRememberDraft({
              filename: vcImp.filename,
              payment_from: $('#vc_payment_from').val() || null,
              rows: res.rows,
              counts: res.counts
            });
          }
          vcImpSetRestored(null);
          toast((res.rows.length) + ' row' + (res.rows.length === 1 ? '' : 's') + ' loaded — edit like Excel, then Import');
        },
        error: function (xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not read this file';
          vcImpShowErr(msg);
          toast(msg);
        },
        complete: function () {
          vcImp.busy = false;
          vcImpUpdateCounts();
        }
      });
    }

    $('#vcImpFile').on('change', function () {
      var f = this.files && this.files[0];
      if (f) vcImpPreviewFile(f);
      else $('#vcImpFn').text('No file chosen');
    });

    var dropBody = document.getElementById('vcImpBody');
    var dropOv = document.getElementById('vcImpDropOv');
    var dropZone = document.getElementById('vcImpDrop');
    var dragDepth = 0;
    function vcImpShowDrop(on) {
      if (!dropOv) return;
      if (on) dropOv.removeAttribute('hidden');
      else dropOv.setAttribute('hidden', 'hidden');
      if (dropZone) dropZone.classList.toggle('over', !!on);
    }
    if (dropBody) {
      dropBody.addEventListener('dragenter', function (e) {
        e.preventDefault();
        dragDepth++;
        vcImpShowDrop(true);
      });
      dropBody.addEventListener('dragover', function (e) {
        e.preventDefault();
      });
      dropBody.addEventListener('dragleave', function (e) {
        e.preventDefault();
        dragDepth = Math.max(0, dragDepth - 1);
        if (dragDepth === 0) vcImpShowDrop(false);
      });
      dropBody.addEventListener('drop', function (e) {
        e.preventDefault();
        dragDepth = 0;
        vcImpShowDrop(false);
        var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (!f) return;
        var input = document.getElementById('vcImpFile');
        if (input) {
          try {
            var dt = new DataTransfer();
            dt.items.add(f);
            input.files = dt.files;
          } catch (err) {}
        }
        vcImpPreviewFile(f);
      });
    }

    $('.vc-ifilts').on('click', 'button[data-f]', function () {
      vcImp.filter = $(this).data('f') || 'all';
      $('.vc-ifilts button').removeClass('on');
      $(this).addClass('on');
      vcImpRender();
    });

    $('#vcImpQ').on('input', function () {
      vcImp.q = this.value || '';
      vcImpRender();
    });

    $('#vcImpAdd').on('click', function () {
      vcImp.rows.push({ d: '', m: '', id: '', name: '', p: '', a: '', errors: { d: 'Date missing or wrong (dd-mm-yyyy)' }, warnings: [] });
      vcImpRender();
      var $last = $('#vcImpTb tr:not(.hidden)').last().find('input.cell').first();
      if ($last.length) $last.focus();
    });

    $('#vcImpTb').on('input', 'input.cell', function () {
      var i = parseInt($(this).closest('tr').data('i'), 10);
      var k = $(this).data('k');
      if (!vcImp.rows[i] || !k) return;
      vcImp.rows[i][k] = this.value;
      if (k === 'a') {
        var n = parseFloat(String(this.value).replace(/[^\d.\-]/g, ''));
        if (!isNaN(n)) vcImp.rows[i].a = n;
      }
      vcImpValidateSoon();
    });

    $('#vcImpTb').on('keydown', 'input.cell', function (e) {
      var $inputs = $('#vcImpTb tr:not(.hidden) input.cell');
      var idx = $inputs.index(this);
      if (e.key === 'Enter' || e.key === 'Tab') {
        e.preventDefault();
        var next = e.shiftKey ? idx - 1 : idx + 1;
        if (next >= 0 && next < $inputs.length) $inputs.eq(next).focus().select();
      }
    });

    $('#vcImpTb').on('click', '.rm', function () {
      var i = parseInt($(this).closest('tr').data('i'), 10);
      if (isNaN(i)) return;
      vcImp.rows.splice(i, 1);
      if (!vcImp.rows.length) {
        vcImpReset(false);
        return;
      }
      vcImpRender();
      vcImpValidateNow();
    });

    // Paste from Excel into the grid
    $('#vcImpTable').on('paste', 'input.cell', function (e) {
      var clip = (e.originalEvent || e).clipboardData;
      if (!clip) return;
      var text = clip.getData('text/plain');
      if (!text || text.indexOf('\t') === -1 && text.indexOf('\n') === -1) return;
      e.preventDefault();
      var startI = parseInt($(this).closest('tr').data('i'), 10);
      var startK = $(this).data('k');
      var keyIdx = vcImp.keys.indexOf(startK);
      if (isNaN(startI) || keyIdx < 0) return;
      var lines = text.replace(/\r/g, '').split('\n').filter(function (l, n, arr) {
        return l.length || n < arr.length - 1;
      });
      lines.forEach(function (line, li) {
        if (!line && li === lines.length - 1) return;
        var cols = line.split('\t');
        var rowI = startI + li;
        while (vcImp.rows.length <= rowI) {
          vcImp.rows.push({ d: '', m: '', id: '', name: '', p: '', a: '', errors: {}, warnings: [] });
        }
        cols.forEach(function (val, ci) {
          var k = vcImp.keys[keyIdx + ci];
          if (!k) return;
          vcImp.rows[rowI][k] = String(val == null ? '' : val).trim();
          if (k === 'a') {
            var n = parseFloat(String(val).replace(/[^\d.\-]/g, ''));
            vcImp.rows[rowI].a = isNaN(n) ? 0 : n;
          }
        });
      });
      vcImpRender();
      vcImpValidateNow();
    });

    $('#vcImpDiscard').on('click', function () {
      if (!window.confirm('Discard the saved import draft?')) return;
      vcImpClearDraft(function () {
        vcImpReset(true);
        vcImpSetPaymentFrom('');
        vcImpUpdateResumeUi(null);
        toast('Import draft discarded');
      });
    });

    $('#vcImpResetBtn').on('click', function () {
      if (!window.confirm('Reset import? This clears the saved session cache and the grid.')) return;
      var $btn = $(this).prop('disabled', true);
      vcImpClearDraft(function () {
        vcImpReset(true);
        vcImpSetPaymentFrom('');
        vcImpUpdateResumeUi(null);
        toast('Import cache cleared');
        $btn.prop('disabled', false);
      });
    });

    $('#vcImpErrXls').on('click', function () {
      var lines = [['#', 'Date', 'Billing Month', 'Rider ID', 'Rider', 'Particulars', 'Paid', 'Errors']];
      vcImp.rows.forEach(function (r, i) {
        if (!(r.errors && Object.keys(r.errors).length)) return;
        lines.push([
          i + 1, r.d || '', r.m || '', r.id || '', r.name || '', r.p || '', r.a || '',
          Object.values(r.errors).join('; ')
        ]);
      });
      if (lines.length < 2) {
        toast('No error rows to export');
        return;
      }
      var csv = lines.map(function (row) {
        return row.map(function (c) {
          var s = String(c == null ? '' : c);
          return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
        }).join(',');
      }).join('\n');
      var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'voucher-import-errors.csv';
      a.click();
      URL.revokeObjectURL(a.href);
      toast('Error rows downloaded');
    });

    $(document).on('change', '#vc_payment_from', function () {
      vcImpRefreshBalance();
      if (vcImp.rows.length) {
        vcImpRender();
        vcImpSaveDraftSoon();
      }
    });

    // Page load: show Resume badge if session draft exists
    (function () {
      var draft = vcImpReadEmbeddedDraft();
      if (draft) {
        vcImp.draft = draft;
        vcImpUpdateResumeUi(draft);
      }
    })();

    $('#vcImpGo').on('click', function () {
      var cfg = vcImpCfg();
      var paymentFrom = String($('#vc_payment_from').val() || '').trim();
      if (!paymentFrom) {
        toast('Select a credit account first');
        if ($('#vc_payment_from').hasClass('select2-hidden-accessible')) {
          $('#vc_payment_from').select2('open');
        } else {
          $('#vc_payment_from').focus();
        }
        return;
      }
      var counts = vcImpUpdateCounts();
      if (!counts.ok) {
        toast('No ready rows to import');
        return;
      }
      if (counts.err) {
        if (!window.confirm('Import ' + counts.ok + ' ready row(s) and skip ' + counts.err + ' with errors?')) return;
      }
      vcImp.busy = true;
      vcImpSetStep(3);
      $('#vcImpGo').prop('disabled', true).text('Importing…');
      $.ajax({
        url: cfg.commit,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          payment_from: parseInt(paymentFrom, 10),
          rows: vcImpPayloadRows(),
          filename: vcImp.filename || null,
          _token: cfg.csrf
        }),
        headers: {
          'X-CSRF-TOKEN': cfg.csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (res) {
          toast((res && res.message) || 'Import finished');
          if (res && res.draft && res.draft.rows && res.draft.rows.length) {
            vcImpRememberDraft(res.draft);
            vcImpApplyServer(res.draft, 'replace');
            vcImpSetStep(2);
            toast((res.message || 'Partial import') + ' — remaining rows kept for resume');
          } else {
            vcImpRememberDraft(null);
            closeModal(vcImpRoot());
            setTimeout(function () { window.location.reload(); }, 700);
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON || {};
          var msg = res.message || 'Import failed';
          toast(msg);
          vcImpSetStep(2);
          if (res.draft && res.draft.rows && res.draft.rows.length) {
            vcImpRememberDraft(res.draft);
            vcImpApplyServer(res.draft, 'replace');
          } else {
            vcImpSaveDraftNow();
          }
        },
        complete: function () {
          vcImp.busy = false;
          vcImpUpdateCounts();
        }
      });
    });

    // Status tabs — full-page filter via approval_tab (default landing tab is pending)
    $('#vcTabs').on('click', 'button[data-tab]', function () {
      var tab = $(this).data('tab') || 'pending';
      var url = new URL(window.location.href);
      url.searchParams.set('approval_tab', tab);
      url.searchParams.delete('page');
      window.location.href = url.toString();
    });

    $(document).on('click', '[data-phase2="reject"]', function () {
      toast('Reject will be wired in a later step');
    });

    // Bulk selection UI
    function updateBulk() {
      var n = $('.vc-rck:checked').length;
      $('#vcSelN').text(n);
      $('#vcBulk').toggleClass('show', n > 0);
      $('.vc-rck').each(function () {
        $(this).closest('tr').toggleClass('sel', this.checked);
      });
    }

    $(document).on('change', '#vcCkAll', function () {
      var on = this.checked;
      $('.vc-rck').prop('checked', on);
      updateBulk();
    });

    $(document).on('change', '.vc-rck', updateBulk);

    $('#vcBulk .vc-chips span').on('click', function () {
      var n = $(this).data('n') === 'all' ? 1e9 : parseInt($(this).data('n'), 10);
      $('.vc-rck').each(function (i) {
        this.checked = i < n;
      });
      updateBulk();
    });

    $('#vcClr').on('click', function () {
      $('.vc-rck, #vcCkAll').prop('checked', false);
      updateBulk();
    });

    window.__vcDelIds = [];

    function vcEscHtml(s) {
      return String(s == null ? '' : s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function vcCollectCheckedDeleteItems() {
      return $('.vc-rck:checked').map(function () {
        var row = $(this).closest('tr');
        var id = row.data('id');
        var vno = row.find('.vc-lnk').first().text().trim() || ('#' + id);
        var amt = row.find('.vc-amt').first().text().replace(/\s+/g, ' ').trim() || '';
        return { id: id, vno: vno, amount: amt };
      }).get();
    }

    window.vcOpenDeleteModal = function (items) {
      items = Array.isArray(items) ? items.filter(function (it) { return it && it.id; }) : [];
      if (!items.length) return;
      window.__vcDelIds = items.map(function (it) { return it.id; });
      var n = items.length;
      $('#vcDelT').text('Delete ' + n + ' voucher' + (n === 1 ? '' : 's') + '?');
      $('#vcDelSub').text(
        n === 1
          ? 'This voucher and its posted transactions will be removed.'
          : 'These vouchers and their posted transactions will be removed.'
      );
      $('#vcDelGo').text(n === 1 ? 'Delete voucher' : 'Delete vouchers');
      $('#vcDelL').html(items.map(function (it) {
        return '<div class="vc-del-row">'
          + '<b class="vc-del-id">' + vcEscHtml(it.vno || ('#' + it.id)) + '</b>'
          + '<span class="vc-del-amt">' + vcEscHtml(it.amount || '') + '</span>'
          + '</div>';
      }).join(''));
      $('#vcDelR').val('');
      openModal('vcDelM');
      setTimeout(function () { $('#vcDelR').trigger('focus'); }, 40);
    };

    $('#vcBdel').on('click', function () {
      var items = vcCollectCheckedDeleteItems();
      if (!items.length) return;
      window.vcOpenDeleteModal(items);
    });

    // Row ⋯ Delete — open reason modal (not Swal without reason)
    $(document).on('click', '.mi.del[data-vc-del-id]', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $btn = $(this);
      window.vcOpenDeleteModal([{
        id: $btn.data('vc-del-id'),
        vno: $btn.data('vc-del-vno') || ('#' + $btn.data('vc-del-id')),
        amount: $btn.data('vc-del-amt') || ''
      }]);
    });

    $('#vcDelGo').on('click', function () {
      var reason = String($('#vcDelR').val() || '').trim();
      if (!reason) {
        toast('Please enter a delete reason');
        $('#vcDelR').focus();
        return;
      }
      var ids = (window.__vcDelIds && window.__vcDelIds.length)
        ? window.__vcDelIds.slice()
        : vcCollectCheckedDeleteItems().map(function (it) { return it.id; });
      if (!ids.length) {
        closeModal(document.getElementById('vcDelM'));
        return;
      }

      var $btn = $(this).prop('disabled', true);
      $.ajax({
        url: @json(route('vouchers.bulk-destroy')),
        method: 'POST',
        data: {
          _token: @json(csrf_token()),
          ids: ids,
          delete_reason: reason,
          reason: reason
        },
        success: function (res) {
          window.__vcDelIds = [];
          closeModal(document.getElementById('vcDelM'));
          toast(res.message || 'Delete requests submitted');
          setTimeout(function () { window.location.reload(); }, 700);
        },
        error: function (xhr) {
          var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && xhr.responseJSON.errors.error))) || 'Bulk delete failed';
          if (Array.isArray(msg)) msg = msg.join(' ');
          toast(msg);
        },
        complete: function () {
          $btn.prop('disabled', false);
        }
      });
    });

    // View panel / legacy confirmDelete → prefer reason modal when row is on the list
    (function wrapConfirmDeleteWithReasonModal() {
      var tries = 0;
      function wrap() {
        tries++;
        if (typeof window.confirmDelete !== 'function') {
          if (tries < 40) setTimeout(wrap, 100);
          return;
        }
        if (window.confirmDelete.__vcReasonWrapped) return;
        var orig = window.confirmDelete;
        window.confirmDelete = function (url) {
          var row = null;
          var id = null;
          var m = String(url || '').match(/(?:\/|=)(\d+)(?:\/?$|\?)/);
          if (m) {
            id = parseInt(m[1], 10);
            row = document.querySelector('#dataTableBuilder tr[data-id="' + id + '"]');
          }
          if (!row) {
            // trans_code style URLs — match by voucher link text / data
            var code = String(url || '').split('/').filter(Boolean).pop();
            if (code) {
              $('#dataTableBuilder tbody tr[data-id]').each(function () {
                var vno = $(this).find('.vc-lnk').first().text().trim();
                if (vno && (url.indexOf(vno) !== -1 || String($(this).data('id')) === code)) {
                  row = this;
                  id = $(this).data('id');
                  return false;
                }
              });
            }
          }
          if (row && id && typeof window.vcOpenDeleteModal === 'function') {
            var $row = $(row);
            var vno = $row.find('.vc-lnk').first().text().trim() || ('#' + id);
            var amt = $row.find('.vc-amt').first().text().replace(/\s+/g, ' ').trim() || '';
            window.vcOpenDeleteModal([{ id: id, vno: vno, amount: amt }]);
            return;
          }
          return orig.apply(this, arguments);
        };
        window.confirmDelete.__vcReasonWrapped = true;
        window.confirmDelete.__erpbkDeleteConfirm = true;
      }
      wrap();
    })();

    // Attachment document viewer (header + iframe preview + note)
    var vcDocNoteTimer = null;
    function vcDocExt(name) {
      var m = String(name || '').toLowerCase().match(/\.([a-z0-9]+)(?:\?|$)/);
      return m ? m[1] : '';
    }
    function vcCloseDocViewer() {
      var ov = document.getElementById('vcDocViewer');
      if (!ov) return;
      ov.classList.remove('open');
      ov.setAttribute('hidden', 'hidden');
      ov.setAttribute('aria-hidden', 'true');
      ov.style.display = 'none';
      var body = document.getElementById('vcDocVBody');
      if (body) body.innerHTML = '';
      var note = document.getElementById('vcDocVNote');
      if (note) {
        note.setAttribute('hidden', 'hidden');
        note.textContent = '';
      }
      if (vcDocNoteTimer) {
        clearTimeout(vcDocNoteTimer);
        vcDocNoteTimer = null;
      }
      document.body.classList.remove('vc-docv-open');
    }
    function vcOpenDocViewer(opts) {
      var ov = document.getElementById('vcDocViewer');
      if (!ov) return;
      // Escape layout overflow/transform parents (common on live servers)
      if (ov.parentElement !== document.body) {
        document.body.appendChild(ov);
      }
      var url = String(opts.url || '');
      if (!url) return;
      var name = String(opts.name || 'Document');
      var meta = String(opts.meta || '');
      var context = String(opts.context || 'this voucher');
      var ext = vcDocExt(name) || vcDocExt(url);
      var isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].indexOf(ext) !== -1;
      var isPdf = ext === 'pdf';

      $('#vcDocVTitle').text(name);
      $('#vcDocVMeta').text(meta);
      $('#vcDocVDownload').attr({ href: url, download: name });

      var body = document.getElementById('vcDocVBody');
      if (!body) return;
      body.innerHTML = '';
      if (isImg) {
        body.innerHTML = '<div class="vc-docv-img-wrap"><img src="'+url.replace(/"/g, '&quot;')+'" alt="'+String(name).replace(/"/g, '&quot;')+'"></div>';
      } else if (isPdf) {
        // view=FitH helps long PDFs; browser PDF UI scrolls inside the iframe
        var src = url + (url.indexOf('#') === -1 ? '#toolbar=1&navpanes=1&scrollbar=1&view=FitH' : '');
        body.innerHTML = '<iframe title="Document preview" src="'+src.replace(/"/g, '&quot;')+'"></iframe>';
      } else {
        body.innerHTML = '<div class="vc-docv-fallback"><div>Preview is not available for this file type.</div><a href="'+url.replace(/"/g, '&quot;')+'" target="_blank" rel="noopener">Open / Download</a></div>';
      }

      var note = document.getElementById('vcDocVNote');
      if (note) {
        note.removeAttribute('hidden');
        note.textContent = '1 document(s) attached to ' + context;
        if (vcDocNoteTimer) clearTimeout(vcDocNoteTimer);
        vcDocNoteTimer = setTimeout(function () {
          note.setAttribute('hidden', 'hidden');
        }, 4200);
      }

      ov.removeAttribute('hidden');
      ov.classList.add('open');
      ov.setAttribute('aria-hidden', 'false');
      // Inline fallback if cached CSS on server is missing .vc-docv rules
      ov.style.cssText = 'position:fixed;inset:0;z-index:200000;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,0.55);box-sizing:border-box;';
      document.body.classList.add('vc-docv-open');
    }
    window.vcOpenDocViewer = vcOpenDocViewer;
    window.vcCloseDocViewer = vcCloseDocViewer;

    $(document).on('click', '.js-vc-doc-view, a.vc-fbtn', function (e) {
      var $a = $(this);
      var url = $a.attr('data-file-url') || $a.attr('href');
      if (!url || url === '#' || url.indexOf('javascript:') === 0) return;
      e.preventDefault();
      e.stopPropagation();
      vcOpenDocViewer({
        url: url,
        name: $a.attr('data-file-name') || ($a.attr('title') || 'Document'),
        meta: $a.attr('data-file-meta') || '',
        context: $a.attr('data-file-context') || 'this voucher'
      });
    });

    $(document).on('click', '#vcDocVClose', function (e) {
      e.preventDefault();
      vcCloseDocViewer();
    });
    $(document).on('click', '#vcDocViewer', function (e) {
      if (e.target === this) vcCloseDocViewer();
    });
    $(document).on('keydown', function (e) {
      var ov = document.getElementById('vcDocViewer');
      if (e.key === 'Escape' && ov && ov.classList.contains('open')) {
        vcCloseDocViewer();
      }
    });

    // Row ⋯ menu (#vcRowMenu lives outside .vc-demo with modals; CSS targets #vcRowMenu)
    $(document).on('click', '.vc-dots', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $btn = $(this);
      var menuHtml = $btn.attr('data-menu-html') || '';
      var rm = document.getElementById('vcRowMenu');
      if (!rm) return;
      if (!menuHtml) {
        toast('No actions available for this voucher');
        return;
      }
      var rowId = String($btn.closest('tr').attr('data-id') || '');
      // Close if re-clicking the same open trigger
      if (rm.classList.contains('open') && rm.getAttribute('data-for-id') === rowId) {
        rm.hidden = true;
        rm.classList.remove('open');
        rm.removeAttribute('data-for-id');
        return;
      }
      rm.innerHTML = menuHtml;
      rm.hidden = false;
      rm.removeAttribute('hidden');
      rm.classList.add('open');
      rm.setAttribute('data-for-id', rowId);
      // Measure after visible so width/height are correct
      var r = this.getBoundingClientRect();
      var w = rm.offsetWidth || 300;
      var h = rm.offsetHeight || 200;
      rm.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
      rm.style.top = (r.bottom + h + 8 > window.innerHeight ? Math.max(8, r.top - h - 6) : r.bottom + 6) + 'px';
    });

    function vcEscHtml(s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
      });
    }

    function vcStripEmoji(s) {
      return String(s == null ? '' : s)
        .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '')
        .replace(/\uFE0F/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function vcFileNameFromUrl(url) {
      try {
        var path = decodeURIComponent(String(url || '').split('?')[0]);
        var base = path.split('/').filter(Boolean).pop() || '';
        return vcStripEmoji(base);
      } catch (e) {
        return '';
      }
    }

    function vcCellCopyValue(td, thLabel) {
      if (!td) return { text: '-', href: null };
      var isFileCol = /file/i.test(thLabel || '') || td.classList.contains('vc-file-cell');
      var fileA = td.querySelector('a.vc-fbtn[href], a[data-file-url], a[data-file-name]');

      // File column: NEVER copy the 📄 icon — use filename.ext + file URL
      if (isFileCol || fileA) {
        if (!fileA) return { text: '-', href: null };
        var fileUrl = fileA.getAttribute('data-file-url') || fileA.getAttribute('href') || fileA.href || '';
        var fileName = vcStripEmoji(fileA.getAttribute('data-file-name') || '')
          || vcFileNameFromUrl(fileUrl)
          || 'file';
        // Prefer absolute URL for paste/open
        try {
          fileUrl = new URL(fileUrl, window.location.origin).href;
        } catch (e2) {}
        return { text: fileName, href: fileUrl || null };
      }

      // Voucher ID link — text only (panel is JS, not a file URL)
      var idA = td.querySelector('a.vc-lnk, a.show-voucher-panel');
      if (idA) {
        return { text: vcStripEmoji(idA.textContent) || '-', href: null };
      }
      var clone = td.cloneNode(true);
      $(clone).find('input,button,.vc-dots,.dropdown,.vc-fbtn').remove();
      var text = vcStripEmoji(clone.innerText) || '-';
      return { text: text, href: null };
    }

    // Copy table (File column pastes as clickable link with file URL + extension)
    $('#vcCopyBtn').on('click', async function () {
      var table = document.getElementById('dataTableBuilder');
      if (!table) return;

      var colMeta = [];
      $(table).find('thead th').each(function (i) {
        if ($(this).is('[aria-hidden]') || $(this).css('display') === 'none') return;
        if (this.classList.contains('column-hidden')) return;
        var label = (this.textContent || '').replace(/[⇅↑↓]/g, '').replace(/\s+/g, ' ').trim();
        if (!label || /^actions$/i.test(label) || label === '') return;
        // Skip checkbox / select column
        if (this.querySelector('input[type="checkbox"]') || /^select$/i.test(label)) return;
        colMeta.push({ index: i, label: label });
      });

      if (!colMeta.length) {
        toast('No visible columns to copy');
        return;
      }

      var matrix = [colMeta.map(function (c) { return { text: c.label, href: null }; })];
      $(table).find('tbody tr').each(function () {
        if ($(this).hasClass('vc-empty') || $(this).find('.vc-empty').length) return;
        var cells = this.children;
        matrix.push(colMeta.map(function (c) {
          return vcCellCopyValue(cells[c.index], c.label);
        }));
      });

      // Plain text: files paste as "filename.ext https://.../file.ext" (never the 📄 icon)
      var txt = matrix.map(function (r) {
        return r.map(function (c) {
          if (!c.href) return c.text;
          return (c.text || 'file') + ' ' + c.href;
        }).join('\t');
      }).join('\n');

      var htmlTable = '<table><thead><tr>' +
        colMeta.map(function (c) { return '<th>' + vcEscHtml(c.label) + '</th>'; }).join('') +
        '</tr></thead><tbody>' +
        matrix.slice(1).map(function (r) {
          return '<tr>' + r.map(function (c) {
            if (c.href) {
              // Link text = filename with extension only (no emoji)
              return '<td><a href="' + vcEscHtml(c.href) + '">' + vcEscHtml(c.text || 'file') + '</a></td>';
            }
            return '<td>' + vcEscHtml(c.text) + '</td>';
          }).join('') + '</tr>';
        }).join('') +
        '</tbody></table>';

      var copied = false;
      try {
        if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
          await navigator.clipboard.write([
            new ClipboardItem({
              'text/plain': new Blob([txt], { type: 'text/plain' }),
              'text/html': new Blob([htmlTable], { type: 'text/html' }),
            }),
          ]);
          copied = true;
        } else {
          await navigator.clipboard.writeText(txt);
          copied = true;
        }
      } catch (err) {
        try {
          var ta = document.createElement('textarea');
          ta.value = txt;
          ta.style.cssText = 'position:fixed;left:-9999px';
          document.body.appendChild(ta);
          ta.select();
          copied = document.execCommand('copy');
          ta.remove();
        } catch (e2) {}
      }

      if (!copied) {
        toast('Could not copy to clipboard');
        return;
      }
      toast((matrix.length - 1) + ' vouchers copied — File column includes link to open the file');
    });

    // Ticker drag + auto-scroll
    var strip = document.getElementById('vcStrip');
    if (strip) {
      var paused = false, drag = false, dragged = false, sx = 0, sl = 0, acc = 0;
      strip.addEventListener('mouseenter', function () { paused = true; });
      strip.addEventListener('mouseleave', function () { paused = false; drag = false; strip.classList.remove('dragging'); });
      strip.addEventListener('mousedown', function (e) {
        drag = true; dragged = false; sx = e.pageX; sl = strip.scrollLeft; strip.classList.add('dragging');
      });
      window.addEventListener('mouseup', function () {
        drag = false; strip.classList.remove('dragging');
        setTimeout(function () { dragged = false; }, 0);
      });
      strip.addEventListener('mousemove', function (e) {
        if (!drag) return;
        if (Math.abs(e.pageX - sx) > 4) dragged = true;
        strip.scrollLeft = sl - (e.pageX - sx);
      });
      strip.addEventListener('click', function (e) {
        if (dragged) {
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);
      (function step() {
        if (!paused) {
          acc += 0.4;
          if (acc >= 1) {
            strip.scrollLeft += Math.floor(acc);
            acc %= 1;
          }
        }
        var track = document.getElementById('vcTrack');
        var h = track ? track.scrollWidth / 2 : 0;
        if (h && strip.scrollLeft >= h) strip.scrollLeft -= h;
        requestAnimationFrame(step);
      })();
    }
  });
})();
</script>

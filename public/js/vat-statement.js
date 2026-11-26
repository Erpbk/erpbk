/**
 * VAT Ledger / Return printable statement (design demo layout).
 * Expects window.VatStatementCompany = { name, address, trn, phone, email, vat_rate, logo }.
 */
(function (window) {
  'use strict';

  var MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var MONTHS_UP = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /** Allow only <b>/</b> in narration HTML. */
  function safeNarration(html) {
    var raw = String(html == null ? '' : html);
    var ta = document.createElement('textarea');
    ta.innerHTML = raw;
    var decoded = ta.value;
    var escaped = esc(decoded);
    return escaped.replace(/&lt;(\/?b)&gt;/gi, '<$1>');
  }

  function fmt(n) {
    return Number(n || 0).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function todayNice() {
    var d = new Date();
    return String(d.getDate()).padStart(2, '0') + '-' + MONTHS_SHORT[d.getMonth()] + '-' + d.getFullYear();
  }

  function formatDatePrint(isoOrDisplay) {
    var s = String(isoOrDisplay || '').trim();
    if (!s) return '';
    // YYYY-MM-DD
    var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) {
      return m[3] + '-' + MONTHS_UP[parseInt(m[2], 10) - 1] + '-' + m[1];
    }
    // dd-mm-yyyy
    m = s.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (m) {
      return m[1] + '-' + MONTHS_UP[parseInt(m[2], 10) - 1] + '-' + m[3];
    }
    // d M Y / dd MMM YYYY
    m = s.match(/^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{4})/);
    if (m) {
      var idx = MONTHS_SHORT.findIndex(function (x) {
        return x.toLowerCase() === m[2].toLowerCase();
      });
      return String(m[1]).padStart(2, '0') + '-' + (idx >= 0 ? MONTHS_UP[idx] : m[2].toUpperCase()) + '-' + m[3];
    }
    return s;
  }

  function formatMonthPrint(month) {
    var s = String(month || '').trim();
    if (!s) return '';
    // YYYY-MM
    var m = s.match(/^(\d{4})-(\d{2})/);
    if (m) {
      return MONTHS_SHORT[parseInt(m[2], 10) - 1] + '-' + m[1];
    }
    // "Jun 2026"
    m = s.match(/^([A-Za-z]{3})\s+(\d{4})$/);
    if (m) return m[1] + '-' + m[2];
    return s.replace(/\s+/, '-');
  }

  function balLabel(balance) {
    var n = Number(balance || 0);
    var abs = fmt(Math.abs(n));
    if (n > 0) return abs + 'Dr';
    if (n < 0) return abs + 'Cr';
    return abs;
  }

  function companyBlock(company) {
    var c = company || window.VatStatementCompany || {};
    var name = c.name || 'Company';
    var logo = c.logo || '';
    var meta = [];
    if (c.trn) meta.push('TRN: ' + c.trn);
    if (c.phone) meta.push('Tel: ' + c.phone);
    if (c.email) meta.push(c.email);
    var logoHtml = logo
      ? '<img src="' + esc(logo) + '" alt="' + esc(name) + '">'
      : '<div class="vs-logo-fallback" aria-hidden="true"></div>';
    return (
      '<div class="vs-top">' +
      '<div class="vs-logo">' +
      logoHtml +
      '</div>' +
      '<div class="vs-co">' +
      '<h1>' +
      esc(name) +
      '</h1>' +
      (meta.length ? '<p>' + esc(meta.join(' · ')) + '</p>' : '') +
      (c.address ? '<p>' + esc(c.address) + '</p>' : '') +
      '</div></div>'
    );
  }

  function leftTable(rows) {
    var html = '<table class="vs-l">';
    (rows || []).forEach(function (r) {
      html +=
        '<tr' +
        (r.net ? ' class="vs-net"' : '') +
        '><td>' +
        esc(r.label) +
        '</td><td>' +
        esc(r.value) +
        '</td></tr>';
    });
    return html + '</table>';
  }

  function rightTable(rows) {
    var html = '<table class="vs-r">';
    (rows || []).forEach(function (r) {
      html += '<tr><td>' + esc(r.label) + '</td><td>' + esc(r.value) + '</td></tr>';
    });
    return html + '</table>';
  }

  function buildSheet(payload) {
    var p = payload || {};
    var kind = p.kind === 'return' ? 'return' : 'ledger';
    var company = p.company || window.VatStatementCompany || {};
    var rows = p.rows || [];
    var opening = Number(p.opening_balance || 0);
    var totalDebit = 0;
    var totalCredit = 0;
    var body = '';

    body +=
      '<tr><td></td><td></td><td></td><td class="b ds">BALANCE BROUGHT FORWARD</td><td></td><td></td><td></td><td class="r b">' +
      esc(balLabel(opening)) +
      '</td></tr>';

    rows.forEach(function (r) {
      var debit = Number(r.debit || 0);
      var credit = Number(r.credit || 0);
      totalDebit += debit;
      totalCredit += credit;
      body +=
        '<tr>' +
        '<td>' +
        esc(formatDatePrint(r.date_iso || r.date)) +
        '</td>' +
        '<td>' +
        esc(formatMonthPrint(r.billing_month_key || r.month || r.billing_month)) +
        '</td>' +
        '<td>' +
        esc(r.account || '') +
        '</td>' +
        '<td class="ds">' +
        safeNarration(r.narration || '') +
        '</td>' +
        '<td>' +
        esc(r.voucher || '') +
        '</td>' +
        '<td class="r">' +
        (debit ? esc(fmt(debit)) : '') +
        '</td>' +
        '<td class="r">' +
        (credit ? esc(fmt(credit)) : '') +
        '</td>' +
        '<td class="r b">' +
        esc(r.balance_label || balLabel(r.balance)) +
        '</td>' +
        '</tr>';
    });

    var closing =
      p.closing_balance != null
        ? Number(p.closing_balance)
        : opening + totalDebit - totalCredit;
    if (p.totals) {
      if (p.totals.debit != null) totalDebit = Number(p.totals.debit);
      if (p.totals.credit != null) totalCredit = Number(p.totals.credit);
      if (p.totals.balance != null) closing = Number(p.totals.balance);
    }

    var netLabel = balLabel(closing);
    body +=
      '<tr class="vs-tot"><td></td><td></td><td></td><td class="b ds">TOTAL</td><td></td>' +
      '<td class="r b">' +
      esc(fmt(totalDebit)) +
      '</td><td class="r b">' +
      esc(fmt(totalCredit)) +
      '</td><td class="r b">' +
      esc(netLabel) +
      '</td></tr>';

    var heading = kind === 'return' ? 'Your VAT Return Transactions' : 'Your VAT Ledger Transactions';
    var phone = company.phone || '';
    var coName = company.name || '';

    return (
      companyBlock(company) +
      '<div class="vs-sum">' +
      leftTable(p.summary_left || []) +
      rightTable(p.summary_right || []) +
      '</div>' +
      '<h2 class="vs-h"><span></span>' +
      esc(heading) +
      '<span></span></h2>' +
      '<table class="vs-t"><thead><tr>' +
      '<th>Date</th><th>Billing Month</th><th>Account</th><th>Description</th><th>Voucher</th>' +
      '<th class="r">Debit</th><th class="r">Credit</th><th class="r">Balance</th>' +
      '</tr></thead><tbody>' +
      body +
      '</tbody></table>' +
      '<div class="vs-ft">This is a computer generated document and does not require a signature. Printed on ' +
      esc(todayNice()) +
      (coName ? ' · ' + esc(coName) : '') +
      (phone ? ' · ' + esc(phone) : '') +
      '</div>'
    );
  }

  function ensureModal() {
    return document.getElementById('vatStmtOv');
  }

  function openStatement(payload) {
    var ov = ensureModal();
    var sheet = document.getElementById('vatStmtSheet');
    if (!ov || !sheet) {
      console.warn('VAT statement modal missing');
      return;
    }
    sheet.innerHTML = buildSheet(payload);
    ov.classList.add('open');
    ov.setAttribute('aria-hidden', 'false');
    ov.scrollTop = 0;
  }

  function closeStatement() {
    var ov = ensureModal();
    if (!ov) return;
    ov.classList.remove('open');
    ov.setAttribute('aria-hidden', 'true');
  }

  function printStatement() {
    window.print();
  }

  function bindModalOnce() {
    if (window.__vatStmtBound) return;
    window.__vatStmtBound = true;
    var ov = ensureModal();
    if (!ov) return;
    var closeBtn = document.getElementById('vatStmtClose');
    var printBtn = document.getElementById('vatStmtPrint');
    if (closeBtn) closeBtn.addEventListener('click', closeStatement);
    if (printBtn) printBtn.addEventListener('click', printStatement);
    ov.addEventListener('click', function (e) {
      if (e.target === ov) closeStatement();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && ov.classList.contains('open')) closeStatement();
    });
  }

  function bindTriggers(root) {
    bindModalOnce();
    var scope = root || document;
    scope.querySelectorAll('[data-vat-stmt]').forEach(function (btn) {
      if (btn.__vatStmtBound) return;
      btn.__vatStmtBound = true;
      btn.addEventListener('click', function () {
        var raw = btn.getAttribute('data-vat-stmt');
        var target = btn.getAttribute('data-vat-stmt-target');
        var payload = null;
        if (target) {
          var el = document.getElementById(target);
          if (el) {
            try {
              payload = JSON.parse(el.textContent || '{}');
            } catch (err) {
              payload = null;
            }
          }
        } else if (raw) {
          try {
            payload = JSON.parse(raw);
          } catch (err) {
            payload = null;
          }
        }
        if (!payload && window.VatLedgerStatement) {
          payload = window.VatLedgerStatement;
        }
        if (!payload) {
          if (window.toastr) toastr.warning('No statement data available.');
          return;
        }
        openStatement(payload);
        if (btn.getAttribute('data-vat-stmt-print') === '1') {
          setTimeout(printStatement, 120);
        }
      });
    });
  }

  window.VatStatement = {
    open: openStatement,
    close: closeStatement,
    print: printStatement,
    bind: bindTriggers,
    buildSheet: buildSheet,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      bindTriggers(document);
    });
  } else {
    bindTriggers(document);
  }
})(window);

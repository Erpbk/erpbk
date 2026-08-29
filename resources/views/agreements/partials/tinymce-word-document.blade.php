@once
@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('vendor/tinymce/skins/ui/oxide/skin.min.css') }}">
<style>
  .agreement-word-editor .tox-tinymce {
    border: 1px solid #8a8a8a;
    border-radius: 0;
    display: flex;
    flex-direction: column;
    font-family: 'Segoe UI', Calibri, Arial, sans-serif;
  }

  .agreement-word-editor .tox-editor-container {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .agreement-word-editor .tox-sidebar-wrap {
    flex: 1 1 auto;
    min-height: 0;
  }

  .agreement-word-editor .tox .tox-editor-header {
    display: none !important;
  }

  .agreement-word-editor .tox .tox-edit-area,
  .agreement-word-editor .tox .tox-edit-area__iframe {
    background: #5c5c5c;
  }

  .agreement-word-editor .tox .tox-statusbar {
    background: #f3f2f1;
    border-top: 1px solid #d2d0ce;
    color: #605e5c;
    font-family: 'Segoe UI', sans-serif;
    font-size: 12px;
  }

  .agreement-word-editor textarea.form-control {
    min-height: calc(100vh - 72px);
  }

  .agreement-word-editor .tox-tinymce .word-ribbon,
  .agreement-word-editor .tox-tinymce .word-ribbon *:not(svg):not(path):not(rect):not(circle):not(line) {
    box-sizing: border-box !important;
    color: #323130;
    cursor: default;
    font-family: 'Segoe UI', Calibri, Arial, sans-serif !important;
    font-size: 12px !important;
    font-style: normal !important;
    font-weight: 400 !important;
    line-height: 1.2 !important;
    text-align: left;
    text-decoration: none !important;
    text-transform: none !important;
    white-space: nowrap !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon {
    flex: 0 0 auto;
    background: #ffffff;
    border-bottom: 1px solid #c8c6c4;
    box-shadow: 0 1px 0 rgba(0, 0, 0, .06);
    user-select: none;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-tabs {
    display: flex !important;
    align-items: stretch !important;
    gap: 4px;
    background: #ffffff;
    border-bottom: 1px solid #e1dfdd;
    padding: 4px 10px 0;
    min-height: 34px;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-tab {
    appearance: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-bottom: 0 !important;
    border-radius: 4px 4px 0 0 !important;
    color: #323130 !important;
    cursor: pointer !important;
    font-size: 13px !important;
    font-weight: 400 !important;
    height: 30px !important;
    margin: 0 !important;
    min-width: 64px;
    padding: 0 16px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-tab:hover {
    background: #f3f2f1 !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-tab.is-active {
    background: #f3f2f1 !important;
    border-color: #e1dfdd !important;
    border-bottom-color: #f3f2f1 !important;
    color: #185abd !important;
    font-weight: 600 !important;
    position: relative;
    z-index: 1;
    margin-bottom: -1px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-body {
    background: #f3f2f1;
    min-height: 102px;
    padding: 8px 6px 6px;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-panel {
    display: none !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-panel.is-active {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: stretch !important;
    overflow-x: auto;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-group {
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    flex: 0 0 auto !important;
    border-right: 1px solid #c8c6c4 !important;
    padding: 0 12px 2px !important;
    min-width: 88px;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-group:last-child {
    border-right: 0 !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-group-body {
    display: flex !important;
    align-items: center !important;
    gap: 4px !important;
    min-height: 66px;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-col {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    gap: 4px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-row {
    display: flex !important;
    align-items: center !important;
    gap: 3px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-group-label {
    color: #605e5c !important;
    font-size: 11px !important;
    font-weight: 400 !important;
    line-height: 1 !important;
    margin-top: 6px !important;
    text-align: center !important;
    white-space: nowrap !important;
    width: 100%;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-btn {
    appearance: none !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 3px !important;
    color: #323130 !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    height: 28px !important;
    min-width: 28px !important;
    padding: 0 5px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-btn:hover {
    background: #e1dfdd !important;
    border-color: #d2d0ce !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-btn.is-active {
    background: #c5e0f7 !important;
    border-color: #a3d0f5 !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-btn.is-large {
    flex-direction: column !important;
    font-size: 11px !important;
    font-weight: 400 !important;
    gap: 4px !important;
    height: 64px !important;
    min-width: 52px !important;
    padding: 6px 8px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-btn.is-large .word-ribbon-icon {
    width: 26px !important;
    height: 26px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-icon {
    width: 16px !important;
    height: 16px !important;
    display: block !important;
    flex: 0 0 auto;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-select {
    appearance: auto !important;
    background: #ffffff !important;
    border: 1px solid #8a8886 !important;
    border-radius: 2px !important;
    color: #323130 !important;
    font-size: 12px !important;
    height: 24px !important;
    min-width: 128px !important;
    max-width: 160px;
    padding: 0 6px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-select.is-size {
    min-width: 58px !important;
    max-width: 64px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-color {
    width: 28px !important;
    height: 28px !important;
    padding: 2px !important;
    border: 1px solid #c8c6c4 !important;
    border-radius: 3px !important;
    background: #ffffff !important;
    cursor: pointer !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-style {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    justify-content: center !important;
    background: #ffffff !important;
    border: 1px solid #c8c6c4 !important;
    border-radius: 2px !important;
    height: 64px !important;
    min-width: 72px !important;
    padding: 4px 8px !important;
    font-weight: 400 !important;
    cursor: pointer !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-style:hover,
  .agreement-word-editor .tox-tinymce .word-ribbon-style.is-active {
    border-color: #185abd !important;
    background: #deecf9 !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-style-preview {
    color: #201f1e !important;
    font-size: 16px !important;
    line-height: 1.15 !important;
    white-space: nowrap !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-style.is-h1 .word-ribbon-style-preview {
    font-size: 18px !important;
    font-weight: 700 !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-style.is-h2 .word-ribbon-style-preview {
    font-size: 15px !important;
    font-weight: 700 !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-style.is-h3 .word-ribbon-style-preview {
    font-size: 13px !important;
    font-weight: 700 !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-style-name {
    color: #605e5c !important;
    font-size: 10px !important;
    margin-top: 4px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-field-label {
    color: #605e5c !important;
    font-size: 11px !important;
    min-width: 32px;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-table-wrap {
    position: relative;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-table-picker {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 30;
    background: #ffffff;
    border: 1px solid #8a8886;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .18);
    padding: 8px;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-table-wrap.is-open .word-ribbon-table-picker {
    display: block;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-table-grid {
    display: grid;
    grid-template-columns: repeat(5, 16px);
    gap: 2px;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-table-cell {
    width: 16px !important;
    height: 16px !important;
    border: 1px solid #8a8886 !important;
    background: #ffffff !important;
    cursor: pointer !important;
    padding: 0 !important;
    min-width: 16px !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-table-cell.is-on {
    background: #c5e0f7 !important;
    border-color: #185abd !important;
  }

  .agreement-word-editor .tox-tinymce .word-ribbon-table-caption {
    color: #605e5c !important;
    font-size: 11px !important;
    margin-top: 6px !important;
    text-align: center !important;
  }
</style>
@endpush

@push('third_party_scripts')
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
  window.erpbkAgreementWordEditor = {
    height: function () {
      var viewport = window.innerHeight || 900;
      return Math.max(viewport - 56, 920);
    },
    pageCanvas: '#5c5c5c',
    pageGapPx: 22,
    pageHeightMm: 297,
    contentStyle: function (margins) {
      margins = margins || this.pageMargins();
      var pad = [margins.top, margins.right, margins.bottom, margins.left].map(function (v) {
        return v + 'mm';
      }).join(' ');
      var canvas = this.pageCanvas;
      return [
        'html{background:' + canvas + ';height:100%;}',
        'body{--word-margin-top:' + margins.top + 'mm;--word-margin-right:' + margins.right + 'mm;',
        '--word-margin-bottom:' + margins.bottom + 'mm;--word-margin-left:' + margins.left + 'mm;',
        'font-family:Calibri,\'Segoe UI\',Arial,sans-serif;font-size:11pt;line-height:1.5;color:#1e293b;',
        'background:#ffffff;width:210mm;max-width:calc(100% - 24px);min-height:297mm;',
        'margin:20px auto 48px auto !important;padding:' + pad + ';box-sizing:border-box;',
        'box-shadow:0 1px 4px rgba(0,0,0,.28),0 0 0 1px #cfcfcf;}',
        '.word-page-gap,[data-word-page-gap]{display:block!important;box-sizing:border-box!important;',
        'height:calc(var(--word-margin-bottom) + 22px + var(--word-margin-top))!important;',
        'min-height:calc(var(--word-margin-bottom) + 22px + var(--word-margin-top))!important;',
        'line-height:0!important;font-size:1px!important;overflow:hidden!important;',
        'padding:0!important;border:0!important;',
        'margin:0 calc(-1 * var(--word-margin-right)) 0 calc(-1 * var(--word-margin-left)) !important;',
        'width:auto!important;max-width:none!important;background:#ffffff!important;',
        'background-image:linear-gradient(' + canvas + ',' + canvas + ')!important;',
        'background-repeat:no-repeat!important;background-size:100% 22px!important;',
        'background-position:0 var(--word-margin-bottom)!important;',
        'box-shadow:none!important;user-select:none!important;pointer-events:none!important;clear:both!important;}',
        'img.mce-pagebreak{display:block!important;border:0!important;outline:0!important;',
        'height:calc(var(--word-margin-bottom) + 22px + var(--word-margin-top))!important;',
        'width:210mm!important;max-width:none!important;background:#ffffff!important;',
        'background-image:linear-gradient(' + canvas + ',' + canvas + ')!important;',
        'background-repeat:no-repeat!important;background-size:100% 22px!important;',
        'background-position:0 var(--word-margin-bottom)!important;',
        'margin:0 0 0 calc(-1 * var(--word-margin-left)) !important;}',
        'table{border-collapse:collapse;width:100%;}',
        'table td,table th{border:1px solid #94a3b8;padding:4px 8px;}',
        'p{margin:0 0 .5em;}',
        'h1,h2,h3,h4{margin:0 0 .55em;line-height:1.25;}',
        'img:not(.mce-pagebreak){max-width:100%;height:auto;}'
      ].join('');
    },
    pageMargins: function () {
      var wrap = document.querySelector('.agreement-word-editor');
      var defaults = { top: 18, right: 12, bottom: 0, left: 12 };
      if (!wrap) {
        return defaults;
      }
      ['top', 'right', 'bottom', 'left'].forEach(function (side) {
        var raw = wrap.getAttribute('data-margin-' + side);
        if (raw === null || raw === '') {
          return;
        }
        var n = parseFloat(raw);
        if (!isNaN(n)) {
          defaults[side] = n;
        }
      });
      return defaults;
    },
    applyPageMargins: function (editor, margins) {
      if (!editor || !editor.getBody()) {
        return;
      }
      var body = editor.getBody();
      body.style.padding = margins.top + 'mm ' + margins.right + 'mm ' + margins.bottom + 'mm ' + margins.left + 'mm';
      body.style.setProperty('--word-margin-top', margins.top + 'mm');
      body.style.setProperty('--word-margin-right', margins.right + 'mm');
      body.style.setProperty('--word-margin-bottom', margins.bottom + 'mm');
      body.style.setProperty('--word-margin-left', margins.left + 'mm');
      var wrap = document.querySelector('.agreement-word-editor');
      if (wrap) {
        wrap.setAttribute('data-margin-left', String(margins.left));
        wrap.setAttribute('data-margin-right', String(margins.right));
        wrap.setAttribute('data-margin-top', String(margins.top));
        wrap.setAttribute('data-margin-bottom', String(margins.bottom));
      }
      var leftField = document.querySelector('input[name="letterhead_margins[left]"]');
      var rightField = document.querySelector('input[name="letterhead_margins[right]"]');
      var bottomField = document.querySelector('input[name="letterhead_margins[bottom]"]');
      if (leftField) leftField.value = margins.left;
      if (rightField) rightField.value = margins.right;
      if (bottomField) bottomField.value = margins.bottom;
      this.paginate(editor);
    },
    debounce: function (fn, wait) {
      var timer;
      return function () {
        var ctx = this;
        var args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
          fn.apply(ctx, args);
        }, wait);
      };
    },
    pageHeightPx: function (doc) {
      if (!doc || !doc.body) {
        return 1123;
      }
      var probe = doc.createElement('div');
      probe.style.cssText = 'position:absolute;left:-9999px;top:0;height:' + this.pageHeightMm + 'mm;width:1px;visibility:hidden;';
      doc.body.appendChild(probe);
      var px = probe.offsetHeight;
      probe.parentNode.removeChild(probe);
      return px > 50 ? px : 1123;
    },
    _createPageGap: function (doc) {
      var gap = doc.createElement('div');
      gap.className = 'word-page-gap';
      gap.setAttribute('data-word-page-gap', '1');
      gap.setAttribute('data-mce-bogus', 'all');
      gap.setAttribute('contenteditable', 'false');
      gap.appendChild(doc.createTextNode('\u00a0'));
      return gap;
    },
    _removePageGaps: function (body) {
      Array.prototype.slice.call(body.querySelectorAll('[data-word-page-gap], .word-page-gap')).forEach(function (el) {
        if (el.parentNode) {
          el.parentNode.removeChild(el);
        }
      });
    },
    _gapNearY: function (body, yFromBodyTop, threshold) {
      var bodyRect = body.getBoundingClientRect();
      var gaps = body.querySelectorAll('[data-word-page-gap], img.mce-pagebreak');
      var i;
      for (i = 0; i < gaps.length; i++) {
        var top = gaps[i].getBoundingClientRect().top - bodyRect.top;
        if (Math.abs(top - yFromBodyTop) < threshold) {
          return true;
        }
      }
      return false;
    },
    _insertPageGap: function (editor, yFromBodyTop) {
      var doc = editor.getDoc();
      var win = editor.getWin();
      var body = editor.getBody();
      var html = doc.documentElement;
      var prevScroll = html.scrollTop || (win && win.scrollY) || 0;
      var iframeH = (win && win.innerHeight) || 800;
      var bodyRect = body.getBoundingClientRect();
      var clientY = bodyRect.top + yFromBodyTop;
      var scrolled = false;

      if (clientY < 12 || clientY > iframeH - 12) {
        html.scrollTop = Math.max(0, prevScroll + clientY - Math.round(iframeH / 2));
        scrolled = true;
        bodyRect = body.getBoundingClientRect();
      }

      try {
        if (this._gapNearY(body, yFromBodyTop, 28)) {
          return;
        }

        var gap = this._createPageGap(doc);
        var x = bodyRect.left + Math.max(24, Math.min(bodyRect.width / 2, 120));
        var y = bodyRect.top + yFromBodyTop - 1;
        var range = null;

        try {
          if (typeof doc.caretRangeFromPoint === 'function') {
            range = doc.caretRangeFromPoint(x, y);
          } else if (typeof doc.caretPositionFromPoint === 'function') {
            var pos = doc.caretPositionFromPoint(x, y);
            if (pos && pos.offsetNode) {
              range = doc.createRange();
              range.setStart(pos.offsetNode, pos.offset);
              range.collapse(true);
            }
          }
        } catch (err) {
          range = null;
        }

        if (range && range.startContainer && body.contains(range.startContainer)) {
          var node = range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentNode;
          if (node && node.closest && node.closest('[data-word-page-gap], .word-page-gap')) {
            return;
          }
          if (node && node.closest) {
            var table = node.closest('table');
            if (table && body.contains(table)) {
              table.parentNode.insertBefore(gap, table);
              return;
            }
          }
          try {
            range.insertNode(gap);
            return;
          } catch (err2) {}
        }

        var child = body.firstChild;
        while (child) {
          var next = child.nextSibling;
          if (child.nodeType === 1 && !child.hasAttribute('data-word-page-gap') && !(child.classList && child.classList.contains('word-page-gap'))) {
            var top = child.getBoundingClientRect().top - bodyRect.top;
            var bottom = top + child.offsetHeight;
            if (top >= yFromBodyTop - 1 || (top < yFromBodyTop && bottom > yFromBodyTop)) {
              body.insertBefore(gap, child);
              return;
            }
          }
          child = next;
        }
      } finally {
        if (scrolled) {
          html.scrollTop = prevScroll;
        }
      }
    },
    paginate: function (editor) {
      if (!editor || this._paging || typeof editor.getBody !== 'function') {
        return;
      }
      var body = editor.getBody();
      if (!body || !body.isConnected) {
        return;
      }
      this._paging = true;
      var self = this;
      var run = function () {
        self._removePageGaps(body);
        var pagePx = self.pageHeightPx(editor.getDoc());
        var gapPx = self.pageGapPx;
        var computed = editor.getWin().getComputedStyle(body);
        var padBottom = parseFloat(computed.paddingBottom) || 0;
        body.style.minHeight = pagePx + 'px';

        if (body.scrollHeight <= pagePx + 2) {
          return;
        }

        var bookmark = null;
        try {
          bookmark = editor.selection.getBookmark(2, true);
        } catch (err) {}

        var pageIndex = 1;
        var maxPages = 40;
        while (pageIndex < maxPages) {
          var boundary = pageIndex * pagePx + (pageIndex - 1) * gapPx - padBottom;
          if (boundary < pagePx * 0.4) {
            break;
          }
          if (body.scrollHeight <= boundary + 6) {
            break;
          }
          self._insertPageGap(editor, boundary);
          pageIndex += 1;
        }

        var gaps = body.querySelectorAll('[data-word-page-gap], .word-page-gap').length;
        var pages = Math.max(1, gaps + 1);
        body.style.minHeight = (pages * pagePx + gaps * gapPx) + 'px';

        if (bookmark) {
          try {
            editor.selection.moveToBookmark(bookmark);
          } catch (err2) {}
        }
      };

      try {
        if (editor.undoManager && typeof editor.undoManager.ignore === 'function') {
          editor.undoManager.ignore(run);
        } else {
          run();
        }
      } finally {
        this._paging = false;
      }
    },
    attachPagination: function (editor) {
      var self = this;
      var soon = this.debounce(function () {
        self.paginate(editor);
      }, 200);
      editor.on('init', function () {
        self.applyPageMargins(editor, self.pageMargins());
        setTimeout(function () {
          self.paginate(editor);
        }, 30);
      });
      editor.on('input SetContent change Undo Redo KeyUp', soon);
      editor.on('ResizeEditor', function () {
        self.paginate(editor);
      });
      editor.on('GetContent', function (e) {
        if (typeof e.content !== 'string' || e.content === '') {
          return;
        }
        e.content = e.content
          .replace(/<div[^>]*data-word-page-gap[^>]*>[\s\S]*?<\/div>/gi, '')
          .replace(/<div[^>]*class="[^"]*word-page-gap[^"]*"[^>]*>[\s\S]*?<\/div>/gi, '');
      });
    },
    icon: function (name) {
      var paths = {
        undo: '<path d="M3 7v6h6"/><path d="M3.5 13A9 9 0 1 0 7 5.1"/>',
        redo: '<path d="M21 7v6h-6"/><path d="M20.5 13A9 9 0 1 1 17 5.1"/>',
        paste: '<rect x="8" y="8" width="12" height="13" rx="1"/><path d="M8 12H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v2"/><rect x="10" y="4" width="5" height="3" rx="0.5"/>',
        cut: '<circle cx="6" cy="18" r="3"/><circle cx="18" cy="18" r="3"/><path d="M8.5 16.5 20 4M14 12l-5.5 4.5"/>',
        copy: '<rect x="9" y="9" width="11" height="11" rx="1"/><path d="M5 15V5h10"/>',
        bold: '<path d="M7 5h6.5a3.5 3.5 0 0 1 0 7H7z"/><path d="M7 12h7.5a3.5 3.5 0 0 1 0 7H7z"/>',
        italic: '<path d="M15 5H9"/><path d="M13 19H7"/><path d="M14 5 10 19"/>',
        underline: '<path d="M7 5v7a5 5 0 0 0 10 0V5"/><path d="M5 21h14"/>',
        strike: '<path d="M5 12h14"/><path d="M16 7.5A4 4 0 0 0 8.5 8"/><path d="M8 16a4 4 0 0 0 8 0"/>',
        sub: '<path d="M6 6h8l-6 12"/><path d="M15 18h5l-5 3h5"/>',
        sup: '<path d="M6 8h8l-6 12"/><path d="M15 5h5l-5 3h5"/>',
        alignL: '<path d="M4 6h16M4 12h10M4 18h14"/>',
        alignC: '<path d="M4 6h16M7 12h10M5 18h14"/>',
        alignR: '<path d="M4 6h16M10 12h10M6 18h14"/>',
        alignJ: '<path d="M4 6h16M4 12h16M4 18h16"/>',
        bullet: '<path d="M9 6h12M9 12h12M9 18h12"/><circle cx="5" cy="6" r="1.2" fill="currentColor"/><circle cx="5" cy="12" r="1.2" fill="currentColor"/><circle cx="5" cy="18" r="1.2" fill="currentColor"/>',
        number: '<path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 5h2v4M4 17h3M4 15h2v2"/>',
        outdent: '<path d="M9 6h12M13 12h8M9 18h12M4 9l3 3-3 3"/>',
        indent: '<path d="M3 6h12M3 12h8M3 18h12M21 9l-3 3 3 3"/>',
        table: '<rect x="3" y="4" width="18" height="16" rx="1"/><path d="M3 10h18M3 16h18M9 4v16M15 4v16"/>',
        link: '<path d="M10 13a5 5 0 0 0 7.5.1l1.4-1.4a5 5 0 0 0-7.1-7.1L10.5 6"/><path d="M14 11a5 5 0 0 0-7.5-.1L5.1 12.3a5 5 0 0 0 7.1 7.1L13.5 18"/>',
        hr: '<path d="M5 12h14"/><path d="M8 8v8M16 8v8"/>',
        page: '<rect x="6" y="3" width="12" height="18" rx="1"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        find: '<circle cx="11" cy="11" r="6"/><path d="m20 20-4-4"/>',
        code: '<path d="m8 8-5 4 5 4M16 8l5 4-5 4"/>',
        preview: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        full: '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
        fontUp: '<path d="M4 18 10 6l6 12M6.5 14h7"/><path d="M17 11v8M14 14l3-3 3 3"/>',
        fontDn: '<path d="M4 18 10 6l6 12M6.5 14h7"/><path d="M17 11v8M14 16l3 3 3-3"/>',
        spacing: '<path d="M6 5h12M6 12h12M6 19h12M4 8l2-3 2 3M4 16l2 3 2-3"/>'
      };
      return '<svg class="word-ribbon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + (paths[name] || '') + '</svg>';
    },
    ribbonHtml: function () {
      var i = this.icon.bind(this);
      return [
        '<div class="word-ribbon-tabs">',
        '  <button type="button" class="word-ribbon-tab is-active" data-word-tab="home">Home</button>',
        '  <button type="button" class="word-ribbon-tab" data-word-tab="insert">Insert</button>',
        '  <button type="button" class="word-ribbon-tab" data-word-tab="layout">Layout</button>',
        '  <button type="button" class="word-ribbon-tab" data-word-tab="view">View</button>',
        '</div>',
        '<div class="word-ribbon-body">',
        '  <div class="word-ribbon-panel is-active" data-word-panel="home">',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body">',
        '      <button type="button" class="word-ribbon-btn is-large" data-cmd="Paste" title="Paste">' + i('paste') + 'Paste</button>',
        '      <div class="word-ribbon-col">',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Cut" title="Cut">' + i('cut') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Copy" title="Copy">' + i('copy') + '</button>',
        '      </div></div><div class="word-ribbon-group-label">Clipboard</div></div>',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body"><div class="word-ribbon-col">',
        '      <div class="word-ribbon-row">',
        '        <select class="word-ribbon-select" data-font-family title="Font">',
        '          <option value="Calibri,sans-serif">Calibri</option>',
        '          <option value="Cambria,serif">Cambria</option>',
        '          <option value="Arial,Helvetica,sans-serif">Arial</option>',
        '          <option value="Times New Roman,Times,serif">Times New Roman</option>',
        '          <option value="Georgia,serif">Georgia</option>',
        '          <option value="Verdana,sans-serif">Verdana</option>',
        '          <option value="Courier New,Courier,monospace">Courier New</option>',
        '        </select>',
        '        <select class="word-ribbon-select is-size" data-font-size title="Font size">',
        '          <option>8pt</option><option>9pt</option><option>10pt</option><option selected>11pt</option>',
        '          <option>12pt</option><option>14pt</option><option>16pt</option><option>18pt</option>',
        '          <option>20pt</option><option>24pt</option><option>28pt</option><option>36pt</option><option>48pt</option>',
        '        </select>',
        '        <button type="button" class="word-ribbon-btn" data-font-step="1" title="Increase font size">' + i('fontUp') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-font-step="-1" title="Decrease font size">' + i('fontDn') + '</button>',
        '      </div>',
        '      <div class="word-ribbon-row">',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Bold" data-state="Bold" title="Bold">' + i('bold') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Italic" data-state="Italic" title="Italic">' + i('italic') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Underline" data-state="Underline" title="Underline">' + i('underline') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Strikethrough" data-state="Strikethrough" title="Strikethrough">' + i('strike') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Subscript" data-state="Subscript" title="Subscript">' + i('sub') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Superscript" data-state="Superscript" title="Superscript">' + i('sup') + '</button>',
        '        <input type="color" class="word-ribbon-color" data-color-cmd="ForeColor" value="#000000" title="Font color">',
        '        <input type="color" class="word-ribbon-color" data-color-cmd="HiliteColor" value="#ffff00" title="Text highlight color">',
        '      </div></div></div><div class="word-ribbon-group-label">Font</div></div>',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body"><div class="word-ribbon-col">',
        '      <div class="word-ribbon-row">',
        '        <button type="button" class="word-ribbon-btn" data-cmd="InsertUnorderedList" data-state="InsertUnorderedList" title="Bullets">' + i('bullet') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="InsertOrderedList" data-state="InsertOrderedList" title="Numbering">' + i('number') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Outdent" title="Decrease indent">' + i('outdent') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Indent" title="Increase indent">' + i('indent') + '</button>',
        '      </div>',
        '      <div class="word-ribbon-row">',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyLeft" data-state="JustifyLeft" title="Align left">' + i('alignL') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyCenter" data-state="JustifyCenter" title="Center">' + i('alignC') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyRight" data-state="JustifyRight" title="Align right">' + i('alignR') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyFull" data-state="JustifyFull" title="Justify">' + i('alignJ') + '</button>',
        '      </div></div></div><div class="word-ribbon-group-label">Paragraph</div></div>',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body">',
        '      <button type="button" class="word-ribbon-style" data-format="p" title="Normal"><span class="word-ribbon-style-preview">AaBbCc</span><span class="word-ribbon-style-name">Normal</span></button>',
        '      <button type="button" class="word-ribbon-style is-h1" data-format="h1" title="Heading 1"><span class="word-ribbon-style-preview">AaBbCc</span><span class="word-ribbon-style-name">Heading 1</span></button>',
        '      <button type="button" class="word-ribbon-style is-h2" data-format="h2" title="Heading 2"><span class="word-ribbon-style-preview">AaBbCc</span><span class="word-ribbon-style-name">Heading 2</span></button>',
        '      <button type="button" class="word-ribbon-style is-h3" data-format="h3" title="Heading 3"><span class="word-ribbon-style-preview">AaBbCc</span><span class="word-ribbon-style-name">Heading 3</span></button>',
        '    </div><div class="word-ribbon-group-label">Styles</div></div>',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body">',
        '      <button type="button" class="word-ribbon-btn is-large" data-ui="searchreplace" title="Find">' + i('find') + 'Find</button>',
        '    </div><div class="word-ribbon-group-label">Editing</div></div>',
        '  </div>',
        '  <div class="word-ribbon-panel" data-word-panel="insert">',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body">',
        '      <div class="word-ribbon-table-wrap">',
        '        <button type="button" class="word-ribbon-btn is-large" data-table-toggle title="Table">' + i('table') + 'Table</button>',
        '        <div class="word-ribbon-table-picker"><div class="word-ribbon-table-grid"></div><div class="word-ribbon-table-caption">Insert table</div></div>',
        '      </div>',
        '      <button type="button" class="word-ribbon-btn is-large" data-ui="link" title="Link">' + i('link') + 'Link</button>',
        '      <button type="button" class="word-ribbon-btn is-large" data-cmd="InsertHorizontalRule" title="Horizontal line">' + i('hr') + 'Line</button>',
        '      <button type="button" class="word-ribbon-btn is-large" data-cmd="mcePageBreak" title="Page break">' + i('page') + 'Page</button>',
        '    </div><div class="word-ribbon-group-label">Insert</div></div>',
        '  </div>',
        '  <div class="word-ribbon-panel" data-word-panel="layout">',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body"><div class="word-ribbon-col">',
        '      <div class="word-ribbon-row">',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyLeft" title="Align left">' + i('alignL') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyCenter" title="Center">' + i('alignC') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyRight" title="Align right">' + i('alignR') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="JustifyFull" title="Justify">' + i('alignJ') + '</button>',
        '      </div>',
        '      <div class="word-ribbon-row">',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Outdent" title="Decrease indent">' + i('outdent') + '</button>',
        '        <button type="button" class="word-ribbon-btn" data-cmd="Indent" title="Increase indent">' + i('indent') + '</button>',
        '        <select class="word-ribbon-select is-size" data-line-height title="Line spacing">',
        '          <option value="1">1.0</option><option value="1.15">1.15</option>',
        '          <option value="1.5" selected>1.5</option><option value="2">2.0</option>',
        '        </select>',
        '      </div></div></div><div class="word-ribbon-group-label">Paragraph</div></div>',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body"><div class="word-ribbon-col">',
        '      <div class="word-ribbon-row">',
        '        <span class="word-ribbon-field-label">Left</span>',
        '        <input type="number" class="word-ribbon-select is-size" data-page-margin="left" min="5" max="40" step="0.5" title="Left margin (mm)">',
        '        <span class="word-ribbon-field-label">mm</span>',
        '      </div>',
        '      <div class="word-ribbon-row">',
        '        <span class="word-ribbon-field-label">Right</span>',
        '        <input type="number" class="word-ribbon-select is-size" data-page-margin="right" min="5" max="40" step="0.5" title="Right margin (mm)">',
        '        <span class="word-ribbon-field-label">mm</span>',
        '      </div>',
        '      <div class="word-ribbon-row">',
        '        <span class="word-ribbon-field-label">Bottom</span>',
        '        <input type="number" class="word-ribbon-select is-size" data-page-margin="bottom" min="0" max="40" step="0.5" title="Bottom margin (mm)">',
        '        <span class="word-ribbon-field-label">mm</span>',
        '      </div></div></div><div class="word-ribbon-group-label">Margins</div></div>',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body">',
        '      <button type="button" class="word-ribbon-btn is-large" data-cmd="mcePageBreak" title="Page break">' + i('page') + 'Breaks</button>',
        '    </div><div class="word-ribbon-group-label">Page Setup</div></div>',
        '  </div>',
        '  <div class="word-ribbon-panel" data-word-panel="view">',
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body">',
        '      <button type="button" class="word-ribbon-btn is-large" data-ui="preview" title="Preview">' + i('preview') + 'Preview</button>',
        '      <button type="button" class="word-ribbon-btn is-large" data-ui="fullscreen" title="Fullscreen">' + i('full') + 'Full screen</button>',
        '      <button type="button" class="word-ribbon-btn is-large" data-ui="code" title="HTML source">' + i('code') + 'Source</button>',
        '    </div><div class="word-ribbon-group-label">Views</div></div>',
        '  </div>',
        '</div>'
      ].join('');
    },
    attachRibbon: function (editor) {
      var container = editor.getContainer();
      var host = container.querySelector('.tox-editor-container') || container;
      if (host.querySelector('.word-ribbon')) {
        return;
      }
      var ribbon = document.createElement('div');
      ribbon.className = 'word-ribbon';
      ribbon.innerHTML = this.ribbonHtml();
      host.insertBefore(ribbon, host.firstChild);
      this.bindRibbon(editor, ribbon);
    },
    bindRibbon: function (editor, ribbon) {
      var self = this;
      var sizes = ['8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '16pt', '18pt', '20pt', '24pt', '28pt', '36pt', '48pt'];

      function run(name, value) {
        editor.focus();
        editor.execCommand(name, false, value);
      }

      ribbon.querySelectorAll('[data-word-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
          ribbon.querySelectorAll('[data-word-tab]').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
          var id = tab.getAttribute('data-word-tab');
          ribbon.querySelectorAll('[data-word-panel]').forEach(function (panel) {
            panel.classList.toggle('is-active', panel.getAttribute('data-word-panel') === id);
          });
        });
      });

      ribbon.querySelectorAll('[data-cmd]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          run(btn.getAttribute('data-cmd'));
        });
      });

      var uiCommands = {
        searchreplace: 'SearchReplace',
        link: 'mceLink',
        preview: 'mcePreview',
        fullscreen: 'mceFullScreen',
        code: 'mceCodeEditor'
      };

      ribbon.querySelectorAll('[data-ui]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var name = uiCommands[btn.getAttribute('data-ui')] || btn.getAttribute('data-ui');
          editor.focus();
          editor.execCommand(name);
        });
      });

      ribbon.querySelectorAll('[data-format]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var format = btn.getAttribute('data-format');
          editor.focus();
          if (editor.formatter && editor.formatter.apply) {
            editor.formatter.apply(format);
          } else {
            run('FormatBlock', format);
          }
        });
      });

      ribbon.querySelectorAll('[data-color-cmd]').forEach(function (input) {
        input.addEventListener('input', function () {
          run(input.getAttribute('data-color-cmd'), input.value);
        });
      });

      var fontFamily = ribbon.querySelector('[data-font-family]');
      if (fontFamily) {
        fontFamily.addEventListener('change', function () {
          run('FontName', fontFamily.value);
        });
      }

      var fontSize = ribbon.querySelector('[data-font-size]');
      if (fontSize) {
        fontSize.addEventListener('change', function () {
          run('FontSize', fontSize.value);
        });
      }

      ribbon.querySelectorAll('[data-font-step]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var current = fontSize ? fontSize.value : '11pt';
          var idx = sizes.indexOf(current);
          if (idx < 0) idx = 3;
          idx = Math.max(0, Math.min(sizes.length - 1, idx + parseInt(btn.getAttribute('data-font-step'), 10)));
          if (fontSize) fontSize.value = sizes[idx];
          run('FontSize', sizes[idx]);
        });
      });

      var lineHeight = ribbon.querySelector('[data-line-height]');
      if (lineHeight) {
        lineHeight.addEventListener('change', function () {
          editor.formatter.register('wordlineheight', {
            selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,li',
            styles: { 'line-height': '%value' }
          });
          editor.formatter.apply('wordlineheight', { value: lineHeight.value });
        });
      }

      var margins = self.pageMargins();
      ribbon.querySelectorAll('[data-page-margin]').forEach(function (input) {
        var side = input.getAttribute('data-page-margin');
        if (side && margins[side] != null) {
          input.value = margins[side];
        }
        input.addEventListener('change', function () {
          var next = self.pageMargins();
          var value = parseFloat(input.value);
          if (isNaN(value)) {
            return;
          }
          var min = side === 'bottom' ? 0 : 5;
          value = Math.max(min, Math.min(40, value));
          input.value = value;
          next[side] = value;
          self.applyPageMargins(editor, next);
        });
      });

      ['left', 'right', 'bottom'].forEach(function (side) {
        var field = document.querySelector('input[name="letterhead_margins[' + side + ']"]');
        if (!field) {
          return;
        }
        field.addEventListener('input', function () {
          var next = self.pageMargins();
          var value = parseFloat(field.value);
          if (isNaN(value)) {
            return;
          }
          next[side] = value;
          var ribbonInput = ribbon.querySelector('[data-page-margin="' + side + '"]');
          if (ribbonInput) {
            ribbonInput.value = value;
          }
          self.applyPageMargins(editor, next);
        });
      });

      var tableWrap = ribbon.querySelector('.word-ribbon-table-wrap');
      var tableGrid = ribbon.querySelector('.word-ribbon-table-grid');
      var tableCaption = ribbon.querySelector('.word-ribbon-table-caption');
      if (tableWrap && tableGrid) {
        var r;
        var c;
        for (r = 1; r <= 5; r++) {
          for (c = 1; c <= 5; c++) {
            var cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'word-ribbon-table-cell';
            cell.setAttribute('data-rows', String(r));
            cell.setAttribute('data-cols', String(c));
            tableGrid.appendChild(cell);
          }
        }

        tableWrap.querySelector('[data-table-toggle]').addEventListener('click', function (e) {
          e.stopPropagation();
          tableWrap.classList.toggle('is-open');
        });

        tableGrid.addEventListener('mouseover', function (e) {
          var target = e.target.closest('.word-ribbon-table-cell');
          if (!target) return;
          var rows = parseInt(target.getAttribute('data-rows'), 10);
          var cols = parseInt(target.getAttribute('data-cols'), 10);
          tableGrid.querySelectorAll('.word-ribbon-table-cell').forEach(function (el) {
            var on = parseInt(el.getAttribute('data-rows'), 10) <= rows && parseInt(el.getAttribute('data-cols'), 10) <= cols;
            el.classList.toggle('is-on', on);
          });
          if (tableCaption) tableCaption.textContent = rows + ' x ' + cols + ' Table';
        });

        tableGrid.addEventListener('click', function (e) {
          var target = e.target.closest('.word-ribbon-table-cell');
          if (!target) return;
          editor.execCommand('mceInsertTable', false, {
            rows: parseInt(target.getAttribute('data-rows'), 10),
            columns: parseInt(target.getAttribute('data-cols'), 10)
          });
          tableWrap.classList.remove('is-open');
        });

        document.addEventListener('click', function (e) {
          if (!tableWrap.contains(e.target)) {
            tableWrap.classList.remove('is-open');
          }
        });
      }

      editor.on('NodeChange', function () {
        ribbon.querySelectorAll('[data-state]').forEach(function (btn) {
          var state = false;
          try {
            state = !!editor.queryCommandState(btn.getAttribute('data-state'));
          } catch (err) {}
          btn.classList.toggle('is-active', state);
        });
      });
    },
    config: function (overrides) {
      var extra = overrides || {};
      var userSetup = extra.setup;
      var self = this;
      var base = {
        height: this.height(),
        menubar: false,
        toolbar: false,
        plugins: 'lists link table code fullscreen preview pagebreak searchreplace wordcount',
        base_url: @json(asset('vendor/tinymce')),
        suffix: '.min',
        branding: false,
        promotion: false,
        resize: true,
        statusbar: true,
        font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 28pt 36pt 48pt',
        font_family_formats: 'Calibri=Calibri,sans-serif;Cambria=Cambria,serif;Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times,serif;Georgia=georgia,serif;Verdana=verdana,sans-serif;Courier New=courier new,courier,monospace',
        pagebreak_split_block: true,
        content_style: this.contentStyle(),
        setup: function (editor) {
          self.attachPagination(editor);
          editor.on('init', function () {
            self.attachRibbon(editor);
          });
          if (typeof userSetup === 'function') {
            userSetup(editor);
          }
        }
      };
      Object.keys(extra).forEach(function (key) {
        if (key !== 'setup') {
          base[key] = extra[key];
        }
      });
      return base;
    }
  };
</script>
@endpush
@endonce

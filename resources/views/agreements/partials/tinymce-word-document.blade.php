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

  .agreement-word-editor .tox-tinymce .word-ribbon-select.is-page {
    min-width: 118px !important;
    max-width: 140px;
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
@php
  $agreementFonts = app(\App\Services\Agreements\AgreementFontSettings::class);
  $letterheadLayout = app(\App\Services\Agreements\AgreementLetterheadLayout::class);
  $editorCategory = $category ?? (isset($template) ? $template->category : null);
  if ($editorCategory && ! $editorCategory instanceof \App\Models\AgreementCategory && isset($template) && method_exists($template, 'loadMissing')) {
      $template->loadMissing('category');
      $editorCategory = $template->category;
  }
  $editorMargins = $letterheadMargins
      ?? ($editorCategory instanceof \App\Models\AgreementCategory
          ? $editorCategory->resolvedLetterheadMarginsMm()
          : $letterheadLayout->defaultMarginsMm());
  $editorPageSize = $letterheadLayout->resolvedPageSize($editorCategory instanceof \App\Models\AgreementCategory ? $editorCategory : null);
  $editorPageW = $editorPageSize['width_mm'];
  $editorPageH = $editorPageSize['height_mm'];
  $editorPageSizeKey = $editorPageSize['key'];
  $editorPageSizes = [];
  foreach ($letterheadLayout->pageSizeCatalog() as $sizeKey => $size) {
      $editorPageSizes[$sizeKey] = [
          'label' => $size['label'],
          'width' => (float) $size['width_mm'],
          'height' => (float) $size['height_mm'],
          'paper' => $size['dompdf'] ?? $sizeKey,
      ];
  }
  $editorFontFaceCss = $agreementFonts->browserFontFaceCss();
  $editorLayoutSaveUrl = '';
  if ($editorCategory instanceof \App\Models\AgreementCategory && request()->route('company_slug')) {
      $editorLayoutSaveUrl = route('agreements.letterhead-layout', [
          'company_slug' => request()->route('company_slug'),
          'category' => $editorCategory->id,
      ]);
  }
@endphp
<script>
  window.erpbkAgreementWordEditor = {
    fonts: {
      family: @json($agreementFonts->familyStackCss()),
      sizePt: {{ $agreementFonts->sizePt() }},
      lineHeight: {{ $agreementFonts->lineHeight() }},
      color: @json($agreementFonts->color()),
      headings: @json($agreementFonts->headingSizesPt()),
      familyFormats: @json($agreementFonts->tinymceFamilyFormats()),
      sizeFormats: @json($agreementFonts->tinymceSizeFormats()),
      ribbonFamilies: @json($agreementFonts->ribbonFamilyOptions()),
      ribbonSizes: @json($agreementFonts->allowedSizesPt()),
    },
    letterhead: {
      fontFacesCss: @json($editorFontFaceCss),
      margins: {
        top: {{ (float) $editorMargins['top'] }},
        right: {{ (float) $editorMargins['right'] }},
        bottom: {{ (float) $editorMargins['bottom'] }},
        left: {{ (float) $editorMargins['left'] }}
      },
      pageWidthMm: {{ $editorPageW }},
      pageHeightMm: {{ $editorPageH }}
    },
    pageSizes: @json($editorPageSizes),
    pageSizeKey: @json($editorPageSizeKey),
    categoryId: {{ $editorCategory instanceof \App\Models\AgreementCategory ? (int) $editorCategory->id : 'null' }},
    layoutSaveUrl: @json($editorLayoutSaveUrl),
    _ready: false,
    height: function () {
      var viewport = window.innerHeight || 900;
      return Math.max(viewport - 56, 920);
    },
    currentPageSize: function () {
      var sizes = this.pageSizes || {};
      var key = this.pageSizeKey || 'a4';
      return sizes[key] || sizes.a4 || { label: 'A4', width: 210, height: 297, paper: 'a4' };
    },
    applyPageSize: function (key, editor) {
      var sizes = this.pageSizes || {};
      if (!sizes[key]) {
        return;
      }
      this.pageSizeKey = key;
      this.applyPageMetrics(editor);
      document.querySelectorAll('.agreement-word-editor').forEach(function (el) {
        el.setAttribute('data-page-size', key);
      });
      if (editor) {
        this.applyPageMargins(editor, this.pageMargins());
      }
      this.persistPageSize(key);
    },
    persistPageSize: function (key) {
      var field = document.querySelector('[name="letterhead_margins[page_size]"]');
      if (field) {
        field.value = key;
      }
      var csrf = document.querySelector('meta[name="csrf-token"]');
      if (!this.layoutSaveUrl || !csrf) {
        return;
      }
      fetch(this.layoutSaveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf.getAttribute('content'),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ letterhead_margins: { page_size: key } })
      }).catch(function () {});
    },
    applyPageMetrics: function (editor) {
      var size = this.currentPageSize();
      this.pageHeightMm = size.height;
      this.letterhead.pageWidthMm = size.width;
      this.letterhead.pageHeightMm = size.height;
      if (!editor || !editor.getBody()) {
        return;
      }
      var doc = editor.getDoc();
      var body = editor.getBody();
      var html = doc ? doc.documentElement : null;
      var width = size.width + 'mm';
      var height = size.height + 'mm';
      if (html) {
        html.style.setProperty('--word-page-width', width);
        html.style.setProperty('--word-page-height', height);
        html.style.background = this.pageCanvas;
      }
      body.style.setProperty('--word-page-width', width);
      body.style.setProperty('--word-page-height', height);
      body.style.width = width;
      body.style.maxWidth = 'none';
      body.style.minWidth = width;
      body.style.minHeight = height;
      body.style.height = 'auto';
    },
    pageCanvas: '#5c5c5c',
    pageHeightMm: {{ $editorPageH }},
    editorPadMm: function (margins) {
      margins = margins || this.pageMargins();
      return {
        top: margins.top,
        right: margins.right,
        bottom: margins.bottom,
        left: margins.left
      };
    },
    contentStyle: function (margins) {
      margins = margins || this.pageMargins();
      var size = this.currentPageSize();
      var pad = this.editorPadMm(margins);
      var padCss = [pad.top, pad.right, pad.bottom, pad.left].map(function (v) {
        return v + 'mm';
      }).join(' ');
      var canvas = this.pageCanvas;
      return [
        this.letterhead.fontFacesCss,
        'html{background:' + canvas + ';min-height:100%;overflow-x:auto;',
        '--word-page-width:' + size.width + 'mm;--word-page-height:' + size.height + 'mm;}',
        'body{position:relative;--word-margin-right:' + pad.right + 'mm;--word-margin-left:' + pad.left + 'mm;',
        '--word-page-width:' + size.width + 'mm;--word-page-height:' + size.height + 'mm;',
        'font-family:' + this.fonts.family + ';font-size:' + this.fonts.sizePt + 'pt;line-height:' + this.fonts.lineHeight + ';color:' + this.fonts.color + ';',
        'background:#ffffff;width:var(--word-page-width);min-width:var(--word-page-width);max-width:none;min-height:var(--word-page-height);height:auto;',
        'margin:20px auto 48px auto !important;padding:' + padCss + ';box-sizing:border-box;overflow-x:hidden;',
        'box-shadow:0 1px 4px rgba(0,0,0,.28),0 0 0 1px #cfcfcf;}',
        'img.mce-pagebreak{max-width:none;}',
        'table{border-collapse:collapse;width:100%;max-width:100%;margin:4pt 0;}',
        'table td,table th{border:1px solid #94a3b8;padding:4px 8px;vertical-align:top;word-break:break-word;overflow-wrap:anywhere;}',
        'p,h1,h2,h3,h4,li,div{max-width:100%;box-sizing:border-box;}',
        'p{margin:0 0 .5em;}',
        'h1,h2,h3,h4{margin:0 0 .55em;line-height:1.25;}',
        'h1{font-size:' + this.fonts.headings.h1 + 'pt;}',
        'h2{font-size:' + this.fonts.headings.h2 + 'pt;}',
        'h3{font-size:' + this.fonts.headings.h3 + 'pt;}',
        'h4{font-size:' + this.fonts.headings.h4 + 'pt;}',
        'ul,ol{margin:2pt 0 4pt 16pt;padding:0;max-width:100%;}',
        'li{margin:0 0 2pt;}',
        'hr{border:0;border-top:1px solid #94a3b8;margin:8pt 0;}',
        '.agreement-page-break,[data-agreement-page-break],img.mce-pagebreak{display:block!important;box-sizing:border-box!important;',
        'width:calc(100% + var(--word-margin-left,12mm) + var(--word-margin-right,12mm))!important;',
        'margin:12pt calc(-1 * var(--word-margin-right,12mm)) 12pt calc(-1 * var(--word-margin-left,12mm))!important;',
        'height:22px!important;padding:0!important;overflow:hidden!important;cursor:default!important;',
        'border:0!important;border-top:2px dashed #185abd!important;background:transparent!important;',
        'font-size:0!important;line-height:0!important;color:transparent!important;user-select:none!important;}',
        '.word-page-gap,[data-word-page-gap]{display:none!important;height:0!important;width:0!important;',
        'margin:0!important;padding:0!important;overflow:hidden!important;border:0!important;}',
        'strong,b{font-weight:700;}',
        'em,i{font-style:italic;}',
        'img:not(.mce-pagebreak){max-width:100%;height:auto;}'
      ].join('');
    },
    pageMargins: function () {
      var wrap = document.querySelector('.agreement-word-editor');
      var defaults = {
        top: this.letterhead.margins.top,
        right: this.letterhead.margins.right,
        bottom: this.letterhead.margins.bottom,
        left: this.letterhead.margins.left
      };
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
      var pad = this.editorPadMm(margins);
      var body = editor.getBody();
      body.style.padding = pad.top + 'mm ' + pad.right + 'mm ' + pad.bottom + 'mm ' + pad.left + 'mm';
      body.style.setProperty('--word-margin-right', pad.right + 'mm');
      body.style.setProperty('--word-margin-left', pad.left + 'mm');
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
      var topField = document.querySelector('input[name="letterhead_margins[top]"]');
      if (leftField) leftField.value = margins.left;
      if (rightField) rightField.value = margins.right;
      if (bottomField) bottomField.value = margins.bottom;
      if (topField) topField.value = margins.top;
      this.applyPageMetrics(editor);
    },
    insertPageBreak: function (editor) {
      if (!editor) {
        return;
      }
      editor.focus();
      editor.insertContent('<p class="agreement-page-break" data-agreement-page-break="1" contenteditable="false">&nbsp;</p>');
    },
    _removePageGaps: function (body) {
      if (!body) {
        return;
      }
      Array.prototype.slice.call(body.querySelectorAll(
        '[data-word-page-gap], .word-page-gap, .word-letterhead-stack, .word-letterhead-page'
      )).forEach(function (el) {
        if (el.parentNode) {
          el.parentNode.removeChild(el);
        }
      });
    },
    stripPaginationChrome: function (html) {
      if (typeof html !== 'string' || html === '') {
        return html;
      }
      return html
        .replace(/<div[^>]*class="[^"]*word-letterhead-(?:stack|page|chrome)[^"]*"[^>]*>[\s\S]*?<\/div>/gi, '')
        .replace(/<div[^>]*data-word-page-gap[^>]*>[\s\S]*?<\/div>/gi, '')
        .replace(/<div[^>]*class="[^"]*word-page-gap[^"]*"[^>]*>[\s\S]*?<\/div>/gi, '');
    },
    attachPageLayout: function (editor) {
      var self = this;
      var clean = function () {
        if (editor && typeof editor.getBody === 'function') {
          self._removePageGaps(editor.getBody());
        }
      };
      editor.on('init', function () {
        var wrap = document.querySelector('.agreement-word-editor');
        var margins = self.pageMargins();
        if (wrap) {
          wrap.setAttribute('data-page-size', self.pageSizeKey || 'a4');
          ['top', 'right', 'bottom', 'left'].forEach(function (side) {
            if (wrap.getAttribute('data-margin-' + side) === null || wrap.getAttribute('data-margin-' + side) === '') {
              wrap.setAttribute('data-margin-' + side, String(margins[side]));
            }
          });
        }
        self.applyPageMargins(editor, self.pageMargins());
        clean();
        self._ready = true;
      });
      editor.on('SetContent', function () {
        clean();
      });
      editor.on('GetContent', function (e) {
        if (e.selection || e.format === 'tree' || typeof e.content !== 'string' || e.content === '') {
          return;
        }
        e.content = self.stripPaginationChrome(e.content);
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
        image: '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5" fill="currentColor"/><path d="m21 15-5-5-9 9"/>',
        link: '<path d="M10 13a5 5 0 0 0 7.5.1l1.4-1.4a5 5 0 0 0-7.1-7.1L10.5 6"/><path d="M14 11a5 5 0 0 0-7.5-.1L5.1 12.3a5 5 0 0 0 7.1 7.1L13.5 18"/>',
        hr: '<path d="M5 12h14"/><path d="M8 8v8M16 8v8"/>',
        pageBreak: '<path d="M6 4h12v5M6 20h12v-5"/><path stroke-dasharray="2 2" d="M4 12h16"/>',
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
      var defaultSize = String(this.fonts.sizePt) + 'pt';
      var familyOptions = this.fonts.ribbonFamilies.map(function (item) {
        var selected = item.label === 'Calibri' ? ' selected' : '';
        return '<option value="' + item.value + '"' + selected + '>' + item.label + '</option>';
      }).join('');
      var sizeOptions = this.fonts.ribbonSizes.map(function (size) {
        var label = String(size).replace(/\.0$/, '') + 'pt';
        var selected = label === defaultSize ? ' selected' : '';
        return '<option value="' + label + '"' + selected + '>' + label + '</option>';
      }).join('');
      var pageSizeKey = this.pageSizeKey || 'a4';
      var pageSizes = this.pageSizes || {};
      var pageSizeOptions = Object.keys(pageSizes).map(function (key) {
        var size = pageSizes[key];
        var selected = key === pageSizeKey ? ' selected' : '';
        return '<option value="' + key + '"' + selected + '>' + size.label + '</option>';
      }).join('');
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
        familyOptions,
        '        </select>',
        '        <select class="word-ribbon-select is-size" data-font-size title="Font size">',
        sizeOptions,
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
        '      <button type="button" class="word-ribbon-btn is-large" data-insert-image title="Picture">' + i('image') + 'Picture</button>',
        '      <button type="button" class="word-ribbon-btn is-large" data-insert-page-break title="Page break — start the next PDF page">' + i('pageBreak') + 'Page break</button>',
        '      <button type="button" class="word-ribbon-btn is-large" data-cmd="InsertHorizontalRule" title="Horizontal line">' + i('hr') + 'Line</button>',
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
        '        <span class="word-ribbon-field-label">Top</span>',
        '        <input type="number" class="word-ribbon-select is-size" data-page-margin="top" min="30" max="100" step="0.5" title="Top margin includes the letterhead header (mm)">',
        '        <span class="word-ribbon-field-label">mm</span>',
        '      </div>',
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
        '    <div class="word-ribbon-group"><div class="word-ribbon-group-body"><div class="word-ribbon-col">',
        '      <div class="word-ribbon-row">',
        '        <span class="word-ribbon-field-label">Size</span>',
        '        <select class="word-ribbon-select is-page" data-page-size title="Page size">',
        pageSizeOptions,
        '        </select>',
        '      </div>',
        '    </div></div><div class="word-ribbon-group-label">Page</div></div>',
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
          var min = side === 'bottom' ? 0 : (side === 'top' ? 30 : 5);
          var max = side === 'top' ? 100 : 40;
          value = Math.max(min, Math.min(max, value));
          input.value = value;
          next[side] = value;
          self.applyPageMargins(editor, next);
        });
      });

      ['left', 'right', 'bottom', 'top'].forEach(function (side) {
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

      var pageSize = ribbon.querySelector('[data-page-size]');
      if (pageSize) {
        pageSize.value = self.pageSizeKey || 'a4';
        pageSize.addEventListener('change', function () {
          self.applyPageSize(pageSize.value, editor);
        });
      }

      var formPageSize = document.querySelector('[name="letterhead_margins[page_size]"]');
      if (formPageSize) {
        formPageSize.addEventListener('change', function () {
          self.applyPageSize(formPageSize.value, editor);
          if (pageSize) {
            pageSize.value = formPageSize.value;
          }
        });
      }

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

      var pageBreakBtn = ribbon.querySelector('[data-insert-page-break]');
      if (pageBreakBtn) {
        pageBreakBtn.addEventListener('click', function () {
          self.insertPageBreak(editor);
        });
      }

      var imageBtn = ribbon.querySelector('[data-insert-image]');
      if (imageBtn) {
        var imageInput = document.createElement('input');
        imageInput.type = 'file';
        imageInput.accept = 'image/jpeg,image/png,image/gif,image/webp';
        imageInput.hidden = true;
        ribbon.appendChild(imageInput);
        imageBtn.addEventListener('click', function () {
          imageInput.click();
        });
        imageInput.addEventListener('change', function () {
          var file = imageInput.files && imageInput.files[0];
          imageInput.value = '';
          if (!file) {
            return;
          }
          var reader = new FileReader();
          reader.onload = function () {
            var src = String(reader.result || '');
            if (src.indexOf('data:image/') !== 0) {
              return;
            }
            var probe = new Image();
            probe.onload = function () {
              var w = probe.naturalWidth || 400;
              var h = probe.naturalHeight || 300;
              var max = 680;
              if (w > max) {
                h = Math.round(h * max / w);
                w = max;
              }
              editor.focus();
              editor.insertContent(
                '<p><img src="' + src.replace(/"/g, '') + '" alt="" width="' + w + '" height="' + h +
                '" style="width:' + w + 'px;height:' + h + 'px;max-width:100%;"></p>'
              );
            };
            probe.src = src;
          };
          reader.readAsDataURL(file);
        });
      }

      function selectionElement() {
        var node = editor.selection ? editor.selection.getNode() : null;
        if (!node) {
          return null;
        }
        if (node.nodeType === 3) {
          return node.parentElement;
        }
        return node.nodeType === 1 ? node : null;
      }

      function rgbToHex(value) {
        if (!value) {
          return null;
        }
        value = String(value).trim();
        if (/^#[0-9a-f]{6}$/i.test(value)) {
          return value.toLowerCase();
        }
        var match = value.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
        if (!match) {
          return null;
        }
        return '#' + [match[1], match[2], match[3]].map(function (part) {
          return ('0' + parseInt(part, 10).toString(16)).slice(-2);
        }).join('');
      }

      function normalizeFamilyToken(value) {
        return String(value || '')
          .replace(/^["']+|["']+$/g, '')
          .split(',')[0]
          .trim()
          .toLowerCase()
          .replace(/\s+/g, ' ');
      }

      function matchFontFamily(raw) {
        if (!fontFamily) {
          return;
        }
        var token = normalizeFamilyToken(raw);
        if (!token) {
          return;
        }
        var options = Array.prototype.slice.call(fontFamily.options || []);
        var match = options.find(function (opt) {
          return normalizeFamilyToken(opt.value) === token || normalizeFamilyToken(opt.text) === token;
        }) || options.find(function (opt) {
          var label = normalizeFamilyToken(opt.text);
          var value = normalizeFamilyToken(opt.value);
          return token.indexOf(label) === 0 || value.indexOf(token) === 0 || token.indexOf(value) === 0;
        });
        if (match) {
          fontFamily.value = match.value;
        }
      }

      function sizeToPt(raw) {
        var value = String(raw || '').trim().toLowerCase();
        if (!value) {
          return null;
        }
        var match = value.match(/^([\d.]+)\s*(pt|px|em|rem|%)$/);
        if (!match) {
          var bare = parseFloat(value);
          return isNaN(bare) ? null : bare;
        }
        var num = parseFloat(match[1]);
        if (isNaN(num)) {
          return null;
        }
        if (match[2] === 'px') {
          return num * 72 / 96;
        }
        if (match[2] === 'em' || match[2] === 'rem') {
          return num * (self.fonts.sizePt || 11);
        }
        return num;
      }

      function matchFontSize(raw) {
        if (!fontSize) {
          return;
        }
        var pt = sizeToPt(raw);
        if (pt == null) {
          return;
        }
        var options = Array.prototype.slice.call(fontSize.options || []);
        var best = null;
        var bestDelta = Infinity;
        options.forEach(function (opt) {
          var optPt = sizeToPt(opt.value || opt.text);
          if (optPt == null) {
            return;
          }
          var delta = Math.abs(optPt - pt);
          if (delta < bestDelta) {
            bestDelta = delta;
            best = opt;
          }
        });
        if (best && bestDelta <= 0.75) {
          fontSize.value = best.value || best.text;
        }
      }

      function matchBlockFormat() {
        var block = 'p';
        try {
          var value = String(editor.queryCommandValue('FormatBlock') || '').toLowerCase();
          value = value.replace(/[<>]/g, '');
          if (value) {
            block = value;
          }
        } catch (err) {}
        var el = selectionElement();
        if (el && el.closest) {
          var heading = el.closest('h1,h2,h3,h4,h5,h6,p');
          if (heading) {
            block = heading.tagName.toLowerCase();
          }
        }
        if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].indexOf(block) === -1) {
          block = 'p';
        }
        ribbon.querySelectorAll('[data-format]').forEach(function (btn) {
          btn.classList.toggle('is-active', btn.getAttribute('data-format') === block);
        });
      }

      function syncRibbonFromSelection() {
        ribbon.querySelectorAll('[data-state]').forEach(function (btn) {
          var state = false;
          try {
            state = !!editor.queryCommandState(btn.getAttribute('data-state'));
          } catch (err) {}
          btn.classList.toggle('is-active', state);
        });

        var familyRaw = '';
        var sizeRaw = '';
        var foreRaw = '';
        var backRaw = '';
        try {
          familyRaw = editor.queryCommandValue('FontName') || '';
        } catch (err) {}
        try {
          sizeRaw = editor.queryCommandValue('FontSize') || '';
        } catch (err) {}
        try {
          foreRaw = editor.queryCommandValue('ForeColor') || '';
        } catch (err) {}
        try {
          backRaw = editor.queryCommandValue('HiliteColor') || editor.queryCommandValue('BackColor') || '';
        } catch (err) {}

        var el = selectionElement();
        if (el && el.ownerDocument && el.ownerDocument.defaultView) {
          var cs = el.ownerDocument.defaultView.getComputedStyle(el);
          if (!familyRaw) {
            familyRaw = cs.fontFamily || '';
          }
          if (!sizeRaw) {
            sizeRaw = cs.fontSize || '';
          }
          if (!foreRaw) {
            foreRaw = cs.color || '';
          }
          if (lineHeight && cs.lineHeight && cs.fontSize) {
            var lhPx = parseFloat(cs.lineHeight);
            var fsPx = parseFloat(cs.fontSize);
            if (!isNaN(lhPx) && !isNaN(fsPx) && fsPx > 0) {
              var ratio = (Math.round((lhPx / fsPx) * 100) / 100).toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
              var lhOptions = Array.prototype.slice.call(lineHeight.options || []);
              var lhMatch = lhOptions.find(function (opt) {
                return Math.abs(parseFloat(opt.value) - parseFloat(ratio)) < 0.06;
              });
              if (lhMatch) {
                lineHeight.value = lhMatch.value;
              }
            }
          }
        }

        matchFontFamily(familyRaw);
        matchFontSize(sizeRaw);
        matchBlockFormat();

        var fore = rgbToHex(foreRaw);
        var foreInput = ribbon.querySelector('[data-color-cmd="ForeColor"]');
        if (fore && foreInput && fore !== '#00000000') {
          foreInput.value = fore;
        }
        var back = rgbToHex(backRaw);
        var backInput = ribbon.querySelector('[data-color-cmd="HiliteColor"]');
        if (back && backInput && back !== '#00000000' && back !== '#ffffff') {
          backInput.value = back;
        }
      }

      editor.on('NodeChange', syncRibbonFromSelection);
      editor.on('keyup', syncRibbonFromSelection);
      editor.on('mouseup', syncRibbonFromSelection);
      editor.on('init', function () {
        setTimeout(syncRibbonFromSelection, 0);
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
        plugins: 'lists link table code fullscreen preview searchreplace wordcount',
        base_url: @json(asset('vendor/tinymce')),
        suffix: '.min',
        branding: false,
        promotion: false,
        resize: true,
        statusbar: true,
        content_css: false,
        allow_html_data_urls: true,
        convert_urls: false,
        paste_data_images: true,
        automatic_uploads: false,
        convert_unsafe_embeds: false,
        font_size_formats: this.fonts.sizeFormats,
        font_family_formats: this.fonts.familyFormats,
        extended_valid_elements: 'p[data-agreement-page-break|class|style|contenteditable|aria-hidden],div[data-agreement-page-break|class|style|contenteditable|aria-hidden],span[*],h1[*],h2[*],h3[*],h4[*],td[*],th[*],li[*],table[*],img[class|src|alt|title|width|height|style|data-mce-pagebreak]',
        pagebreak_separator: '<p class="agreement-page-break" data-agreement-page-break="1" contenteditable="false">&nbsp;</p>',
        pagebreak_split_block: true,
        remove_trailing_brs: false,
        content_style: this.contentStyle(),
        setup: function (editor) {
          self.attachPageLayout(editor);
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

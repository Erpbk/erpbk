@once
@push('third_party_stylesheets')
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
    background: #e8e8e8;
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
<script>
  window.erpbkAgreementWordEditor = {
    height: function () {
      var viewport = window.innerHeight || 900;
      return Math.max(viewport - 56, 920);
    },
    contentStyle: function () {
      return [
        'html{background:#e8e8e8;height:100%;}',
        'body{font-family:Calibri,\'Segoe UI\',Arial,sans-serif;font-size:11pt;line-height:1.5;color:#1e293b;',
        'background:#ffffff;width:210mm;max-width:calc(100% - 40px);min-height:297mm;',
        'margin:20px auto 48px auto !important;padding:25.4mm;box-sizing:border-box;',
        'box-shadow:0 1px 3px rgba(0,0,0,.16),0 0 0 1px #d0d0d0;}',
        'table{border-collapse:collapse;width:100%;}',
        'table td,table th{border:1px solid #94a3b8;padding:4px 8px;}',
        'p{margin:0 0 .5em;}',
        'h1,h2,h3,h4{margin:0 0 .55em;line-height:1.25;}',
        'img{max-width:100%;height:auto;}'
      ].join('');
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
        plugins: 'lists link table code fullscreen preview pagebreak searchreplace wordcount hr',
        branding: false,
        promotion: false,
        resize: true,
        statusbar: true,
        font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 28pt 36pt 48pt',
        font_family_formats: 'Calibri=Calibri,sans-serif;Cambria=Cambria,serif;Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times,serif;Georgia=georgia,serif;Verdana=verdana,sans-serif;Courier New=courier new,courier,monospace',
        content_style: this.contentStyle(),
        setup: function (editor) {
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

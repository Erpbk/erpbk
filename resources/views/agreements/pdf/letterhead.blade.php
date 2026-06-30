<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $template->template_name ?? 'Agreement' }}</title>
  @php
  $pageW = $pageWidthMm ?? 210;
  $pageH = $pageHeightMm ?? 297;
  $m = $letterheadMargins ?? ['top' => 44, 'bottom' => 15, 'left' => 12, 'right' => 12];
  $mt = $m['top'];
  $mb = $m['bottom'];
  $ml = $m['left'];
  $mr = $m['right'];
  $forPdf = ! empty($forPdf);
  $contentW = max(1, $pageW - $ml - $mr);
  $contentH = max(40, $pageH - $mt - $mb);
  $contentTopMm = $forPdf
    ? $mt + (float) config('agreement_letterhead.pdf_content_top_extra_mm', 6)
    : $mt;
  $contentTopCss = $forPdf
    ? round($contentTopMm * 2.834645669, 2) . 'pt'
    : $contentTopMm . 'mm';
  $p = $branding['primary_color'] ?? '#1e3a8a';
  $s = $branding['secondary_color'] ?? '#2563eb';
  @endphp
  <style>
    @page {
      size: {{ $pageW }}mm {{ $pageH }}mm;
      margin: 0;
    }

<<<<<<< Updated upstream
    .pdf-pages {
      margin: 0;
      padding: 0;
    }

    .pdf-page {
      position: relative;
      display: block;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      min-height: {{ $pageH }}mm;
      page-break-inside: avoid;
      overflow: hidden;
    }

    .pdf-page + .pdf-page {
      page-break-before: always;
    }

    .pdf-letterhead {
      position: absolute;
      top: 0;
      left: 0;
      z-index: 0;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      margin: 0;
      padding: 0;
      pointer-events: none;
    }

    .pdf-letterhead img {
      display: block;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      margin: 0;
      padding: 0;
      border: 0;
    }

    .pdf-page-flow {
      position: absolute;
      top: {{ $mt }}mm;
      left: {{ $ml }}mm;
      z-index: 1;
      width: {{ $contentW }}mm;
      height: {{ $contentH }}mm;
      margin: 0;
      padding: 0;
      overflow: hidden;
      box-sizing: border-box;
    }

    .pdf-page-content {
      width: 100%;
      max-width: 100%;
      height: 100%;
      max-height: 100%;
      margin: 0;
      overflow: hidden;
      box-sizing: border-box;
    }

    .content {
      width: 100%;
      max-width: 100%;
    }
    @else
    @page {
      size: {{ $pageW }}mm {{ $pageH }}mm;
      margin: 0;
    }
    @endif

=======
>>>>>>> Stashed changes
    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'DejaVu Sans', Calibri, sans-serif;
      font-size: 9.5pt;
      color: #1e293b;
      line-height: 1.4;
    }

    .agreement-page {
      position: relative;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      overflow: hidden;
      background: #fff;
    }

    @if (! $forPdf)
    .agreement-page + .agreement-page {
      page-break-before: always;
    }
    @endif

    .page-decor {
      position: absolute;
      top: 0;
      left: 0;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }

    .corner-shapes {
      position: absolute;
      overflow: hidden;
      pointer-events: none;
    }

    .corner-shapes--tr {
      top: 0;
      right: 0;
      width: 58mm;
      height: 48mm;
    }

    .corner-shapes--bl {
      bottom: 0;
      left: 0;
      width: 58mm;
      height: 48mm;
    }

    .corner-blob {
      position: absolute;
      border-radius: 50%;
    }

    .corner-shapes--tr .corner-blob--1 {
      top: -16mm;
      right: -14mm;
      width: 52mm;
      height: 52mm;
      opacity: 0.16;
    }

    .corner-shapes--tr .corner-blob--2 {
      top: 4mm;
      right: -6mm;
      width: 34mm;
      height: 34mm;
      opacity: 0.22;
    }

    .corner-shapes--tr .corner-blob--3 {
      top: 18mm;
      right: 12mm;
      width: 20mm;
      height: 20mm;
      opacity: 0.35;
    }

    .corner-shapes--bl .corner-blob--1 {
      bottom: -16mm;
      left: -14mm;
      width: 52mm;
      height: 52mm;
      opacity: 0.14;
    }

    .corner-shapes--bl .corner-blob--2 {
      bottom: 4mm;
      left: -6mm;
      width: 34mm;
      height: 34mm;
      opacity: 0.2;
    }

    .corner-shapes--bl .corner-blob--3 {
      bottom: 18mm;
      left: 12mm;
      width: 20mm;
      height: 20mm;
      opacity: 0.3;
    }

    .page-watermark {
      position: absolute;
      width: 90mm;
      height: 90mm;
      z-index: 0;
      opacity: 0.07;
      text-align: center;
    }

    .page-watermark img {
      display: block;
      width: 90mm;
      max-width: 90mm;
      height: auto;
      margin: 0 auto;
    }

    .page-header {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 2;
      padding-top: 8mm;
      pointer-events: none;
    }

    .page-header-inner {
      padding: 0 12mm;
    }

    .page-header-table {
      width: 100%;
      border-collapse: collapse;
    }

    .page-header-table td {
      border: none;
      padding: 0;
      vertical-align: top;
    }

    .page-header-logo {
      width: 55%;
      padding-right: 6mm;
    }

    .page-header-logo .company-logo-img {
      max-height: 30mm;
      max-width: 65mm;
      display: block;
    }

    .page-header-logo .company-logo-fallback {
      width: 22mm;
      height: 22mm;
      line-height: 22mm;
      text-align: center;
      font-size: 12pt;
      font-weight: bold;
      border-radius: 3mm;
    }

    .page-header-info {
      width: 45%;
      text-align: right;
      padding-top: 1mm;
    }

    .page-header-meta {
      margin: 0 0 2pt;
      font-size: 12pt;
      color: #1e293b;
      line-height: 1.45;
      text-align: right;
      font-weight: bolder;
    }

    .page-header-rule {
      height: 0.5mm;
      margin-top: 4mm;
      width: 100%;
    }

    .page-content-flow {
      position: absolute;
      top: {{ $contentTopCss }};
      left: {{ $ml }}mm;
      right: {{ $mr }}mm;
      bottom: {{ $mb }}mm;
      z-index: 1;
      overflow: hidden;
      box-sizing: border-box;
    }

    @if ($forPdf)
    .page-content-flow .content {
      padding-top: 1mm;
    }
    @endif

    .content {
      width: 100%;
      max-width: 100%;
      margin: 0;
      padding: 0;
      font-size: 8.5pt;
      line-height: 1.4;
      overflow-wrap: break-word;
      word-wrap: break-word;
      word-break: break-word;
      box-sizing: border-box;
    }

    .content p { margin: 0 0 6pt; max-width: 100%; }
    .content h1, .content h2, .content h3, .content h4 {
      font-size: 10.5pt;
      margin: 10pt 0 5pt;
      color: {{ $p }};
      page-break-after: avoid;
      max-width: 100%;
    }
    .content table {
      width: 100% !important;
      max-width: 100% !important;
      table-layout: fixed;
      border-collapse: collapse;
      margin: 6pt 0;
      page-break-inside: auto;
    }
    .content table th, .content table td {
      padding: 4pt 6pt;
      font-size: 8.5pt;
      border: 1px solid #cbd5e1;
      vertical-align: top;
      overflow-wrap: anywhere;
      word-wrap: break-word;
      word-break: break-word;
    }
    .content thead { display: table-header-group; }
    .content tbody tr { page-break-inside: auto; }
    .content ul, .content ol { margin: 4pt 0 6pt 16pt; padding: 0; max-width: 100%; }
    .content img { max-width: 100% !important; height: auto !important; }
    .content div, .content span, .content li, .content blockquote {
      max-width: 100%;
      overflow-wrap: break-word;
      word-wrap: break-word;
    }
    .content pre, .content code {
      white-space: pre-wrap;
      overflow-wrap: break-word;
      word-break: break-word;
      max-width: 100%;
    }

    @if (! $forPdf)
    .preview-pages {
      width: {{ $pageW }}mm;
      margin: 0 auto;
    }

    .preview-pages .agreement-page {
      margin: 0 auto 16px;
      box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12);
    }

    @media screen {
      body { background: #e2e8f0; padding: 16px 0 24px; }
    }

    @media print {
      html, body {
        width: {{ $pageW }}mm !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      .preview-pages {
        display: block !important;
        width: {{ $pageW }}mm !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      .preview-pages .agreement-page {
        margin: 0 !important;
        box-shadow: none !important;
<<<<<<< Updated upstream
        page-break-inside: avoid;
        break-inside: avoid;
        background-color: #fff;
        background-image: url('{{ $letterheadSrc }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center center;
=======
        page-break-after: avoid !important;
>>>>>>> Stashed changes
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

<<<<<<< Updated upstream
      .preview-page + .preview-page {
        page-break-before: always;
        break-before: page;
      }

      .preview-page:last-child {
        page-break-after: avoid;
        break-after: avoid;
=======
      .preview-pages .agreement-page + .agreement-page {
        page-break-before: always !important;
>>>>>>> Stashed changes
      }

      .corner-blob,
      .page-header-rule {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
    }
    @endif
  </style>
</head>
<body>
  @php $renderPages = $pages ?? [$body]; @endphp
  <div class="{{ $forPdf ? 'pdf-pages' : 'preview-pages' }}" id="agreement-preview-pages" aria-live="polite">
    @foreach ($renderPages as $pageBody)
    <div class="agreement-page {{ $forPdf ? 'pdf-page' : 'preview-page' }}">
      @include('agreements.pdf.partials.page-chrome', [
        'pageWidthMm' => $pageW,
        'pageHeightMm' => $pageH,
      ])
      <main class="page-content-flow {{ $forPdf ? 'pdf-page-flow' : 'document-flow' }}">
        <div class="content {{ $forPdf ? 'pdf-page-content' : '' }}">
          {!! $pageBody !!}
        </div>
      </main>
    </div>
    @endforeach
  </div>
  @if (! $forPdf)
  <script>
  (function () {
<<<<<<< Updated upstream
    var pageWmm = {{ $pageW }};
    var pageHmm = {{ $pageH }};
    var contentWmm = {{ $contentW }};
    var mt = {{ $mt }};
    var mb = {{ $mb }};
    var letterheadSrc = @json($letterheadSrc);

    function mmToPx(mm) {
      var probe = document.createElement('div');
      probe.style.width = '1mm';
      probe.style.position = 'absolute';
      probe.style.visibility = 'hidden';
      document.body.appendChild(probe);
      var pxPerMm = probe.offsetWidth || (96 / 25.4);
      document.body.removeChild(probe);
      return mm * pxPerMm;
    }

    function isEmptyNode(node) {
      if (!node) {
        return true;
      }
      if (node.nodeType === 3) {
        return !(node.textContent || '').trim();
      }
      if (node.nodeType !== 1) {
        return true;
      }
      if (/^BR$/i.test(node.tagName)) {
        return true;
      }
      if (/^(IMG|TABLE|HR|SVG)$/i.test(node.tagName)) {
        return false;
      }
      return !(node.textContent || '').replace(/\s+/g, '').length;
    }

    function splitParagraphByBr(paragraph) {
      if (!paragraph || !paragraph.getElementsByTagName || paragraph.getElementsByTagName('br').length === 0) {
        return [paragraph];
      }

      var parts = [];
      var current = document.createElement('p');
      if (paragraph.className) {
        current.className = paragraph.className;
      }
      if (paragraph.getAttribute('style')) {
        current.setAttribute('style', paragraph.getAttribute('style'));
      }

      Array.prototype.forEach.call(paragraph.childNodes, function (child) {
        if (child.nodeType === 1 && /^BR$/i.test(child.tagName)) {
          if (!isEmptyNode(current)) {
            parts.push(current);
          }
          current = document.createElement('p');
          if (paragraph.className) {
            current.className = paragraph.className;
          }
          if (paragraph.getAttribute('style')) {
            current.setAttribute('style', paragraph.getAttribute('style'));
          }
          return;
        }
        current.appendChild(child.cloneNode(true));
      });

      if (!isEmptyNode(current)) {
        parts.push(current);
      }

      return parts.length ? parts : [paragraph];
    }

    function collectPaginatableNodes(root) {
      var blocks = [];

      function walk(parent) {
        Array.prototype.forEach.call(parent.childNodes, function (node) {
          if (isEmptyNode(node)) {
            return;
          }

          if (node.nodeType === 3) {
            var textNode = document.createElement('p');
            textNode.textContent = node.textContent;
            blocks.push(textNode);
            return;
          }

          if (node.nodeType !== 1) {
            return;
          }

          var tag = node.tagName.toUpperCase();

          if (tag === 'TABLE') {
            blocks.push(node);
            return;
          }

          if (tag === 'UL' || tag === 'OL') {
            var start = tag === 'OL' ? parseInt(node.getAttribute('start') || '1', 10) : 1;
            Array.prototype.forEach.call(node.children, function (li, index) {
              if (isEmptyNode(li)) {
                return;
              }
              var list = document.createElement(tag.toLowerCase());
              if (tag === 'OL' && start + index > 1) {
                list.setAttribute('start', String(start + index));
              }
              list.appendChild(li.cloneNode(true));
              blocks.push(list);
            });
            return;
          }

          if (/^(P|H[1-6]|HR|BLOCKQUOTE|PRE|IMG)$/i.test(tag)) {
            if (tag === 'P' && node.getElementsByTagName('br').length > 0) {
              splitParagraphByBr(node).forEach(function (part) {
                blocks.push(part);
              });
              return;
            }
            blocks.push(node);
            return;
          }

          if (/^(DIV|SECTION|ARTICLE)$/i.test(tag)) {
            if (!node.children.length && (node.innerHTML || '').trim()) {
              var paragraph = document.createElement('p');
              paragraph.innerHTML = node.innerHTML;
              splitParagraphByBr(paragraph).forEach(function (part) {
                blocks.push(part);
              });
            } else {
              walk(node);
            }
            return;
          }

          blocks.push(node);
        });
      }

      walk(root);

      if (blocks.length === 0 && (root.innerHTML || '').trim()) {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = root.innerHTML;
        return collectPaginatableNodes(wrapper);
      }

      return blocks;
    }

    function hasMeaningfulContent(container) {
      if (!container) {
        return false;
      }

      if ((container.textContent || '').replace(/\s+/g, '').length > 0) {
        return true;
      }

      return Array.prototype.some.call(container.children, function (el) {
        return /^(IMG|TABLE|HR|SVG)$/i.test(el.tagName);
      });
    }

    function removeEmptyPages(target) {
      Array.prototype.slice.call(target.querySelectorAll('.preview-page')).forEach(function (page) {
        if (!hasMeaningfulContent(page.querySelector('.content'))) {
          page.remove();
        }
      });
    }

    function resizePreviewFrame() {
      var target = document.getElementById('agreement-preview-pages');
      if (!target) {
        return;
      }

      var docHeight = Math.max(
        document.body.scrollHeight,
        document.documentElement.scrollHeight,
        target.scrollHeight + mmToPx(48)
      );

      if (window.parent && window.parent !== window) {
        try {
          var frame = window.frameElement;
          if (frame) {
            frame.style.height = docHeight + 'px';
          }
        } catch (e) {}
      }
    }

    function paginatePreview() {
      var source = document.getElementById('agreement-content-source');
=======
    function resizePreviewFrame() {
>>>>>>> Stashed changes
      var target = document.getElementById('agreement-preview-pages');
      if (!target) {
        return;
      }

<<<<<<< Updated upstream
      var contentHeightPx = mmToPx(pageHmm - mt - mb);
      var contentWidthPx = mmToPx(contentWmm);
      var nodes = collectPaginatableNodes(source);
      target.innerHTML = '';

      function createPage() {
        var page = document.createElement('div');
        page.className = 'preview-page';

        var backdrop = document.createElement('div');
        backdrop.className = 'letterhead-backdrop';
        backdrop.setAttribute('aria-hidden', 'true');
        var img = document.createElement('img');
        img.src = letterheadSrc;
        img.alt = '';
        img.addEventListener('load', resizePreviewFrame);
        backdrop.appendChild(img);

        var flow = document.createElement('main');
        flow.className = 'document-flow';

        var content = document.createElement('div');
        content.className = 'content';
        content.style.width = contentWidthPx + 'px';
        content.style.maxWidth = contentWidthPx + 'px';
        content.style.maxHeight = contentHeightPx + 'px';
        content.style.overflow = 'hidden';

        flow.appendChild(content);
        page.appendChild(backdrop);
        page.appendChild(flow);
        target.appendChild(page);

        return content;
      }

      function fits(container) {
        return container.scrollHeight <= contentHeightPx + 2;
      }

      function appendBlock(node, pageContent) {
        if (node.nodeType === 1 && node.tagName === 'TABLE') {
          return appendTable(node, pageContent);
        }

        var clone = node.cloneNode(true);
        pageContent.appendChild(clone);

        if (!fits(pageContent)) {
          pageContent.removeChild(clone);

          if (!pageContent.childNodes.length) {
            pageContent.appendChild(clone);
            return createPage();
          }

          pageContent = createPage();
          pageContent.appendChild(clone);
        }

        return pageContent;
      }

      function appendTable(table, pageContent) {
        var thead = table.querySelector('thead');
        var body = table.querySelector('tbody') || table;
        var rows = Array.prototype.slice.call(body.querySelectorAll(':scope > tr'));

        if (rows.length === 0) {
          var clone = table.cloneNode(true);
          pageContent.appendChild(clone);
          if (!fits(pageContent) && pageContent.childNodes.length === 1) {
            return pageContent;
          }
          if (!fits(pageContent)) {
            pageContent.removeChild(clone);
            pageContent = createPage();
            pageContent.appendChild(clone);
          }
          return pageContent;
        }

        function buildTable(chunkRows) {
          var clone = table.cloneNode(false);
          if (thead) {
            clone.appendChild(thead.cloneNode(true));
          }
          var tbody = document.createElement('tbody');
          chunkRows.forEach(function (row) {
            tbody.appendChild(row.cloneNode(true));
          });
          clone.appendChild(tbody);
          return clone;
        }

        var chunk = [];

        rows.forEach(function (row) {
          var trial = buildTable(chunk.concat([row]));
          pageContent.appendChild(trial);

          if (!fits(pageContent)) {
            pageContent.removeChild(trial);

            if (chunk.length > 0) {
              pageContent.appendChild(buildTable(chunk));
              pageContent = createPage();
              chunk = [row];
            } else {
              chunk = [row];
            }
          } else {
            pageContent.removeChild(trial);
            chunk.push(row);
=======
      if (window.parent && window.parent !== window) {
        try {
          var frame = window.frameElement;
          if (frame) {
            var probe = document.createElement('div');
            probe.style.width = '1mm';
            probe.style.position = 'absolute';
            probe.style.visibility = 'hidden';
            document.body.appendChild(probe);
            var pxPerMm = probe.offsetWidth || (96 / 25.4);
            document.body.removeChild(probe);
            frame.style.height = (target.scrollHeight + (32 * pxPerMm / (96 / 25.4))) + 'px';
>>>>>>> Stashed changes
          }
        });

        if (chunk.length > 0) {
          pageContent.appendChild(buildTable(chunk));
        }

        return pageContent;
      }

      if (nodes.length === 0) {
        var fallback = createPage();
        fallback.innerHTML = source.innerHTML;
      } else {
        var pageContent = createPage();
        nodes.forEach(function (node) {
          pageContent = appendBlock(node, pageContent);
        });
      }

      removeEmptyPages(target);
      resizePreviewFrame();
    }

    window.__agreementRepaginate = resizePreviewFrame;

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', resizePreviewFrame);
    } else {
      resizePreviewFrame();
    }

<<<<<<< Updated upstream
    window.addEventListener('load', paginatePreview);
    window.addEventListener('beforeprint', paginatePreview);
=======
    window.addEventListener('beforeprint', resizePreviewFrame);
>>>>>>> Stashed changes
  })();
  </script>
  @endif
</body>
</html>

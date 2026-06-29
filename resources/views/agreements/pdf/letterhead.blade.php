<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $template->template_name ?? 'Agreement' }}</title>
  @php
  $pageW = $pageWidthMm ?? 210;
  $pageH = $pageHeightMm ?? 297;
  $m = $letterheadMargins ?? ['top' => 48, 'bottom' => 52, 'left' => 18, 'right' => 18];
  $mt = $m['top'];
  $mb = $m['bottom'];
  $ml = $m['left'];
  $mr = $m['right'];
  $forPdf = ! empty($forPdf);
  $contentW = max(1, $pageW - $ml - $mr);
  $contentH = max(40, $pageH - $mt - $mb);
  $mtPct = round($mt / $pageH * 100, 4);
  $mbPct = round($mb / $pageH * 100, 4);
  $mlPct = round($ml / $pageW * 100, 4);
  $mrPct = round($mr / $pageW * 100, 4);
  @endphp
  <style>
    @if ($forPdf)
    @page {
      size: {{ $pageW }}mm {{ $pageH }}mm;
      margin: 0;
    }

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
      page-break-after: always;
      page-break-inside: avoid;
      overflow: hidden;
    }

    .pdf-page:last-child {
      page-break-after: auto;
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

    .letterhead-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      z-index: -1;
      margin: 0;
      padding: 0;
      pointer-events: none;
      overflow: hidden;
    }

    .letterhead-backdrop img {
      display: block;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      margin: 0;
      padding: 0;
      border: 0;
    }

    .document-flow,
    .content {
      width: 100%;
      max-width: 100%;
      margin: 0;
      padding: 0;
    }

    .content {
      font-size: 9.5pt;
      line-height: 1.4;
      overflow-wrap: break-word;
      word-wrap: break-word;
      word-break: break-word;
    }

    .content p { margin: 0 0 6pt; max-width: 100%; }
    .content h1, .content h2, .content h3, .content h4 {
      font-size: 10.5pt;
      margin: 10pt 0 5pt;
      color: #1e293b;
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
    .preview-pages-source {
      display: none !important;
    }

    .preview-pages {
      width: {{ $pageW }}mm;
      margin: 0 auto;
    }

    .preview-page {
      position: relative;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      overflow: hidden;
      background: #fff;
      box-sizing: border-box;
    }

    .preview-page .letterhead-backdrop {
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      z-index: 0;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
    }

    .preview-page .letterhead-backdrop img {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .preview-page .document-flow {
      position: relative;
      z-index: 1;
      width: {{ $pageW }}mm;
      height: {{ $pageH }}mm;
      padding: {{ $mt }}mm {{ $mr }}mm {{ $mb }}mm {{ $ml }}mm;
      overflow: hidden;
      box-sizing: border-box;
    }

    .preview-page .content {
      width: {{ $contentW }}mm;
      max-width: {{ $contentW }}mm;
      max-height: {{ $contentH }}mm;
      margin: 0 auto;
      overflow: hidden;
      box-sizing: border-box;
    }
    @endif

    @media screen {
      body { background: #e2e8f0; padding: 16px 0 24px; }

      .preview-page {
        margin: 0 auto 16px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12);
      }
    }

    @media print {
      @page {
        size: {{ $pageW }}mm {{ $pageH }}mm;
        margin: 0 !important;
      }

      html, body {
        width: {{ $pageW }}mm !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      .preview-pages-source {
        display: none !important;
      }

      .preview-pages {
        display: block !important;
        width: {{ $pageW }}mm !important;
        margin: 0 !important;
        padding: 0 !important;
      }

      .preview-page {
        position: relative;
        width: {{ $pageW }}mm !important;
        height: {{ $pageH }}mm !important;
        min-height: {{ $pageH }}mm !important;
        max-height: {{ $pageH }}mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
        box-shadow: none !important;
        page-break-after: always;
        page-break-inside: avoid;
        break-after: page;
        break-inside: avoid;
        background-color: #fff;
        background-image: url('{{ $letterheadSrc }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center center;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      .preview-page:last-child {
        page-break-after: auto;
        break-after: auto;
      }

      .preview-page .letterhead-backdrop {
        display: none !important;
      }

      .preview-page .document-flow {
        position: absolute;
        top: 0;
        left: 0;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: {{ $mtPct }}% {{ $mrPct }}% {{ $mbPct }}% {{ $mlPct }}% !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
      }

      .preview-page .content {
        width: 100% !important;
        max-width: 100% !important;
        height: 100% !important;
        max-height: 100% !important;
        margin: 0 !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
      }
    }
  </style>
</head>
<body>
  @if ($forPdf)
  @php
    $pdfPages = $pages ?? [$body];
    $pdfLetterheadSrc = $letterheadSrc ?? $letterheadFileSrc;
  @endphp
  <div class="pdf-pages">
    @foreach ($pdfPages as $pageBody)
    <div class="pdf-page">
      <div class="pdf-letterhead" aria-hidden="true">
        <img src="{{ $pdfLetterheadSrc }}" alt="">
      </div>
      <main class="pdf-page-flow">
        <div class="pdf-page-content content">
          {!! $pageBody !!}
        </div>
      </main>
    </div>
    @endforeach
  </div>
  @else
  <div class="preview-pages-source">
    <div class="letterhead-backdrop" aria-hidden="true">
      <img src="{{ $letterheadSrc }}" alt="">
    </div>
    <main class="document-flow">
      <div class="content" id="agreement-content-source">
        {!! $body !!}
      </div>
    </main>
  </div>

  <div class="preview-pages" id="agreement-preview-pages" aria-live="polite"></div>

  <script>
  (function () {
    var pageWmm = {{ $pageW }};
    var pageHmm = {{ $pageH }};
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

    function paginatePreview() {
      var source = document.getElementById('agreement-content-source');
      var target = document.getElementById('agreement-preview-pages');
      if (!source || !target) {
        return;
      }

      var contentHeightPx = mmToPx(pageHmm - mt - mb);
      var nodes = Array.prototype.slice.call(source.children);
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
        backdrop.appendChild(img);

        var flow = document.createElement('main');
        flow.className = 'document-flow';

        var content = document.createElement('div');
        content.className = 'content';

        flow.appendChild(content);
        page.appendChild(backdrop);
        page.appendChild(flow);
        target.appendChild(page);

        return content;
      }

      function fits(container) {
        return container.scrollHeight <= contentHeightPx + 1;
      }

      var pageContent = createPage();

      nodes.forEach(function (node) {
        var clone = node.cloneNode(true);
        pageContent.appendChild(clone);

        if (!fits(pageContent)) {
          pageContent.removeChild(clone);

          if (!pageContent.childNodes.length) {
            pageContent.appendChild(clone);
            pageContent = createPage();
            return;
          }

          pageContent = createPage();
          pageContent.appendChild(clone);

          if (!fits(pageContent)) {
            pageContent = createPage();
          }
        }
      });

      if (window.parent && window.parent !== window) {
        try {
          var frame = window.frameElement;
          if (frame) {
            var totalHeight = target.scrollHeight + mmToPx(32);
            frame.style.height = totalHeight + 'px';
          }
        } catch (e) {}
      }
    }

    window.__agreementRepaginate = paginatePreview;

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', paginatePreview);
    } else {
      paginatePreview();
    }

    window.addEventListener('beforeprint', paginatePreview);
  })();
  </script>
  @endif
</body>
</html>

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
  $pdfTopExtra = $forPdf ? (float) config('agreement_letterhead.pdf_content_top_extra_mm', 5) : 0.0;
  $contentTopMm = $mt + $pdfTopExtra;
  $contentTopCss = $forPdf
    ? round($contentTopMm * (72 / 25.4), 2) . 'pt'
    : $mt . 'mm';
  $p = $branding['primary_color'] ?? '#1e3a8a';
  $s = $branding['secondary_color'] ?? '#2563eb';
  @endphp
  <style>
    @page {
      size: {{ $pageW }}mm {{ $pageH }}mm;
      margin: 0;
    }

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
      text-align: left;
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
      z-index: 3;
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
      body { background: #e2e8f0; padding: 0px; }
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
        page-break-after: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      .preview-pages .agreement-page + .agreement-page {
        page-break-before: always !important;
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
    function resizePreviewFrame() {
      var target = document.getElementById('agreement-preview-pages');
      if (!target) {
        return;
      }

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
          }
        } catch (e) {}
      }
    }

    window.__agreementRepaginate = resizePreviewFrame;

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', resizePreviewFrame);
    } else {
      resizePreviewFrame();
    }

    window.addEventListener('beforeprint', resizePreviewFrame);
  })();
  </script>
  @endif
</body>
</html>

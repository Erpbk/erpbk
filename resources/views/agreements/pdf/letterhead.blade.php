<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $template->template_name ?? 'Agreement' }}</title>
  @php
  $pageW = $pageWidthMm ?? 210;
  $pageH = $pageHeightMm ?? 297;
  $withLetterhead = $withLetterhead ?? true;
  $m = $letterheadMargins ?? ['top' => 44, 'bottom' => 15, 'left' => 12, 'right' => 12];
  $ml = $m['left'];
  $mr = $m['right'];
  $forPdf = ! empty($forPdf);
  $pad = $contentPadding ?? app(\App\Services\Agreements\AgreementLetterheadLayout::class)->contentPaddingMm($category ?? null, $withLetterhead);
  $contentPadTopMm = $pad['top'];
  $contentPadBottomMm = $pad['bottom'];
  $headerTopMarginMm = (float) config('agreement_letterhead.header_top_margin_mm', 8);
  $headerChromeMm = (float) config('agreement_letterhead.header_chrome_height_mm', 33);
  $p = $branding['primary_color'] ?? '#1e3a8a';
  $s = $branding['secondary_color'] ?? '#2563eb';
  $renderPages = $pages ?? [$body];
  $pdfFontFaces = $pdfFontFaces ?? [];
  @endphp
  <style>
    @page {
      size: A4 portrait;
      margin: 0;
    }

    @if ($forPdf)
    @foreach ($pdfFontFaces as $face)
    @font-face {
      font-family: '{{ $face['family'] }}';
      font-weight: {{ $face['weight'] }};
      font-style: {{ $face['style'] }};
      src: url('{{ $face['uri'] }}') format('truetype');
    }
    @endforeach
    @endif

    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      padding: 0;
      width: {{ $pageW }}mm;
      background: #fff;
    }

    body {
      font-family: Calibri, 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;
      font-size: 11pt;
      color: #1e293b;
      line-height: 1.5;
    }

    .agreement-page {
      position: relative;
      width: {{ $pageW }}mm;
      min-height: 0;
      height: auto;
      overflow: hidden;
      background: #fff;
      page-break-before: auto;
      break-before: auto;
      page-break-after: always;
      break-after: page;
    }

    .agreement-page:first-child {
      page-break-before: auto;
      break-before: auto;
    }

    .agreement-page:last-child {
      page-break-after: auto;
      break-after: auto;
    }

    .agreement-page-header {
      position: relative;
      z-index: 2;
    }

    .agreement-page-body {
      position: relative;
      z-index: 3;
      padding: {{ $contentPadTopMm }}mm {{ $mr }}mm {{ $contentPadBottomMm }}mm {{ $ml }}mm;
    }

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
      width: 50mm;
      height: 32mm;
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
      bottom: -12mm;
      left: -10mm;
      width: 40mm;
      height: 40mm;
      opacity: 0.14;
    }

    .corner-shapes--bl .corner-blob--2 {
      bottom: 2mm;
      left: -4mm;
      width: 26mm;
      height: 26mm;
      opacity: 0.2;
    }

    .corner-shapes--bl .corner-blob--3 {
      bottom: 12mm;
      left: 8mm;
      width: 16mm;
      height: 16mm;
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
      position: relative;
      z-index: 2;
      padding-top: {{ $headerTopMarginMm }}mm;
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
      max-height: 22mm;
      max-width: 58mm;
      display: block;
    }

    .page-header-logo .company-logo-fallback {
      width: 18mm;
      height: 18mm;
      line-height: 18mm;
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
      margin: 0 0 1pt;
      font-size: 9.5pt;
      color: #1e293b;
      line-height: 1.35;
      text-align: left;
      font-weight: bolder;
    }

    .page-header-rule {
      height: 0.5mm;
      margin-top: 2mm;
      width: 100%;
    }

    .content {
      position: relative;
      z-index: 3;
      width: 100%;
      margin: 0;
      padding: 0;
      font-family: Calibri, 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;
      font-size: 11pt;
      line-height: 1.5;
    }

    .content p { margin: 0 0 0.5em; }
    .content h1, .content h2, .content h3, .content h4 {
      margin: 0 0 0.55em;
      line-height: 1.25;
      color: {{ $p }};
      page-break-before: auto;
      break-before: auto;
      page-break-after: auto;
      break-after: auto;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .content table {
      width: 100%;
      border-collapse: collapse;
      margin: 4pt 0;
    }
    .content table th, .content table td {
      padding: 4px 8px;
      border: 1px solid #94a3b8;
      vertical-align: top;
      word-break: normal;
      overflow-wrap: break-word;
    }
    .content thead { display: table-header-group; }
    .content ul, .content ol { margin: 2pt 0 4pt 16pt; padding: 0; }
    .content img { max-width: 100%; height: auto; page-break-inside: avoid; break-inside: avoid; }

    .preview-pages {
      width: {{ $pageW }}mm;
      margin: 0 auto;
    }

    @if (! $forPdf)
    .preview-pages .agreement-page {
      margin: 0 auto 16px;
      box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12);
    }

    @media screen {
      body { background: #e2e8f0; }
    }

    @media print {
      html, body {
        background: #fff !important;
        width: {{ $pageW }}mm;
        margin: 0;
        padding: 0;
      }

      .preview-pages,
      .preview-pages .agreement-page {
        margin: 0;
        box-shadow: none;
      }
    }
    @endif
  </style>
</head>
<body>
  <div class="preview-pages" id="agreement-preview-pages" @if(! $forPdf) aria-live="polite" @endif>
    @foreach ($renderPages as $pageBody)
    <div class="agreement-page preview-page">
      @if($withLetterhead)
      @include('agreements.pdf.partials.page-decor', [
        'pageWidthMm' => $pageW,
        'pageHeightMm' => $pageH,
      ])
      <div class="agreement-page-header">
        @include('agreements.pdf.partials.page-header')
      </div>
      @endif
      <div class="agreement-page-body">
        <div class="content">
          {!! $pageBody !!}
        </div>
      </div>
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

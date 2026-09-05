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
  $renderPages = $pages ?? [$body];
  $pdfFontFaces = $pdfFontFaces ?? [];
  $fonts = app(\App\Services\Agreements\AgreementFontSettings::class);
  $agreementFontFamily = $agreementFontFamily ?? $fonts->familyStackCss();
  $agreementFontSizePt = $agreementFontSizePt ?? $fonts->sizePt();
  $agreementLineHeight = $agreementLineHeight ?? $fonts->lineHeight();
  $agreementFontColor = $agreementFontColor ?? $fonts->color();
  $agreementHeadingSizesPt = $agreementHeadingSizesPt ?? $fonts->headingSizesPt();
  // Dompdf/A4 rounding: a full-height box can overflow by a fraction of a mm and split a blank page.
  $pageBoxH = $forPdf ? round($pageH - 0.4, 1) : $pageH;
  @endphp
  <style>
    @page {
      size: {{ $pageW }}mm {{ $pageH }}mm;
      margin: 0;
    }

    @if (! $forPdf)
    @foreach ($pdfFontFaces as $face)
    @font-face {
      font-family: '{{ $face['family'] }}';
      font-weight: {{ $face['weight'] }};
      font-style: {{ $face['style'] }};
      font-display: swap;
      src: url('{{ $face['url'] }}') format('truetype');
    }
    @endforeach
    @endif

    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      padding: 0;
      width: {{ $pageW }}mm;
      /* Transparent in PDF so canvas-painted letterhead shows through; white for on-screen HTML preview. */
      background: {{ $forPdf ? 'transparent' : '#fff' }};
      --word-page-width: {{ $pageW }}mm;
      --word-page-height: {{ $pageBoxH }}mm;
    }

    body {
      font-family: {{ $agreementFontFamily }};
      font-size: {{ $agreementFontSizePt }}pt;
      color: {{ $agreementFontColor }};
      line-height: {{ $agreementLineHeight }};
    }

    .agreement-page {
      position: relative;
      width: {{ $pageW }}mm;
      height: {{ $pageBoxH }}mm;
      min-height: {{ $pageBoxH }}mm;
      max-height: {{ $pageBoxH }}mm;
      background: {{ $forPdf ? 'transparent' : '#fff' }};
      overflow: hidden;
      page-break-before: auto;
      break-before: auto;
      page-break-after: always;
      break-after: page;
      page-break-inside: auto;
      break-inside: auto;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .agreement-page:first-child {
      page-break-before: auto;
      break-before: auto;
    }

    .agreement-page:last-child {
      page-break-after: auto;
      break-after: auto;
    }

    .letterhead-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: {{ $pageW }}mm;
      height: 0;
      overflow: visible;
      pointer-events: none;
      z-index: 2;
    }

    .letterhead-overlay--design {
      z-index: 0;
    }

    @if ($forPdf)
    .letterhead-overlay--fixed {
      position: fixed;
      top: 0;
      left: 0;
      width: {{ $pageW }}mm;
      height: 0;
      overflow: visible;
      pointer-events: none;
      z-index: 10;
    }
    @endif

    .agreement-page-header {
      position: relative;
      z-index: 2;
      pointer-events: none;
    }

    .agreement-page-body {
      position: relative;
      z-index: 3;
      padding: {{ $contentPadTopMm }}mm {{ $mr }}mm {{ $contentPadBottomMm }}mm {{ $ml }}mm;
      overflow: visible;
      box-sizing: border-box;
    }

    @include('agreements.pdf.partials.letterhead-chrome-styles', [
      'pageWidthMm' => $pageW,
      'pageHeightMm' => $pageBoxH,
      'paperHeightMm' => $pageH,
      'branding' => $branding,
      'headerTopMarginMm' => $headerTopMarginMm,
    ])

    .content {
      position: relative;
      z-index: 3;
      width: 100%;
      margin: 0;
      padding: 0;
      font-family: {{ $agreementFontFamily }};
      font-size: {{ $agreementFontSizePt }}pt;
      line-height: {{ $agreementLineHeight }};
      color: {{ $agreementFontColor }};
    }

    .content p { margin: 0 0 0.5em; }
    .content h1, .content h2, .content h3, .content h4 {
      margin: 0 0 0.55em;
      line-height: 1.25;
      page-break-before: auto;
      break-before: auto;
      page-break-after: auto;
      break-after: auto;
      page-break-inside: auto;
      break-inside: auto;
    }
    .content h1 { font-size: {{ $agreementHeadingSizesPt['h1'] ?? 16 }}pt; }
    .content h2 { font-size: {{ $agreementHeadingSizesPt['h2'] ?? 14 }}pt; }
    .content h3 { font-size: {{ $agreementHeadingSizesPt['h3'] ?? 12 }}pt; }
    .content h4 { font-size: {{ $agreementHeadingSizesPt['h4'] ?? 11 }}pt; }
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
    .content thead { display: table-row-group; }
    .content ul, .content ol { margin: 2pt 0 4pt 16pt; padding: 0; }
    .content li { margin: 0 0 2pt; }
    .content strong, .content b { font-weight: 700; }
    .content em, .content i { font-style: italic; }
    .content u { text-decoration: underline; }
    .content s, .content strike, .content del { text-decoration: line-through; }
    .content sub { vertical-align: sub; font-size: smaller; }
    .content sup { vertical-align: super; font-size: smaller; }
    .content hr { border: 0; border-top: 1px solid #94a3b8; margin: 8pt 0; }
    .content img { max-width: 100%; page-break-inside: auto; break-inside: auto; }
    .content [data-agreement-page-break] { display: none; height: 0; margin: 0; padding: 0; overflow: hidden; }

    .preview-pages {
      width: {{ $pageW }}mm;
      margin: 0;
      padding: 0;
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
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .preview-pages {
        margin: 0;
        padding: 0;
      }

      .preview-pages .agreement-page {
        margin: 0 !important;
        box-shadow: none !important;
        height: {{ $pageBoxH }}mm !important;
        min-height: {{ $pageBoxH }}mm !important;
        max-height: {{ $pageBoxH }}mm !important;
        overflow: hidden !important;
        page-break-after: always !important;
        page-break-inside: auto !important;
        break-after: page !important;
        break-inside: auto !important;
      }

      .preview-pages .agreement-page:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
      }
    }
    @endif
  </style>
</head>
<body>
  @php
    $letterheadMode = $branding['letterhead_mode'] ?? 'default';
    $hasDesign = ! empty($branding['letterhead_src']);
    $hasWatermark = ! empty($branding['watermark_src']);
    $showCompanyHeader = $withLetterhead && $letterheadMode !== 'none' && ! $hasDesign;
    // Dompdf treats full-page letterhead <img> as layout height and splits every
    // sheet. PDF mode paints the design via AgreementLetterheadPdfPainter instead.
    $showPerPageChrome = $withLetterhead && ! $forPdf;
    $showPdfDesignWatermark = $forPdf && $withLetterhead && $hasDesign && $hasWatermark;
    $showFixedChrome = $forPdf && $withLetterhead && ! $hasDesign && ($showCompanyHeader || $hasWatermark);
  @endphp
  @if ($showFixedChrome)
  <div class="letterhead-overlay letterhead-overlay--fixed">
      @include('agreements.pdf.partials.page-chrome', [
        'pageWidthMm' => $pageW,
        'pageHeightMm' => $pageH,
        'branding' => $branding,
      ])
  </div>
  @endif
  <div class="preview-pages" id="agreement-preview-pages" @if(! $forPdf) aria-live="polite" @endif>
    @foreach ($renderPages as $pageBody)
    <div class="agreement-page preview-page">
      @if($showPerPageChrome)
      <div class="letterhead-overlay{{ $hasDesign ? ' letterhead-overlay--design' : '' }}">
        @include('agreements.pdf.partials.page-chrome', [
          'pageWidthMm' => $pageW,
          'pageHeightMm' => $forPdf ? $pageH : $pageBoxH,
          'branding' => $branding,
        ])
      </div>
      @elseif($showPdfDesignWatermark)
      <div class="letterhead-overlay letterhead-overlay--design">
        @include('agreements.pdf.partials.page-chrome', [
          'pageWidthMm' => $pageW,
          'pageHeightMm' => $pageH,
          'branding' => array_merge($branding, ['letterhead_src' => null, 'letterhead_mode' => 'none']),
        ])
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

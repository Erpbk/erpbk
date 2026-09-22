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
  $agreementRtl = false; // never whole-document RTL; use per-segment classes
  $agreementHasArabic = ! empty($agreementHasArabic);
  $agreementRtlFontFamily = $agreementRtlFontFamily ?? $fonts->rtlFamilyStackCss();
  $pdfEngine = $pdfEngine ?? 'html';
  $isMpdf = $pdfEngine === 'mpdf';
  // Continuous native flow for mPDF. Preview still paginates.
  $mpdfNativeFlow = $forPdf && $isMpdf && ! empty($mpdfNativeFlow);
  // Dompdf/A4 rounding: a full-height box can overflow by a fraction of a mm and split a blank page.
  // mPDF fixed-box path needs a tiny shrink to avoid blank trailing pages.
  // Native-flow PDF uses @page margins instead of fixed .agreement-page boxes.
  $pageBoxH = ($forPdf && $isMpdf && ! $mpdfNativeFlow) ? round($pageH - 0.4, 1) : $pageH;
  $letterheadModeEarly = $branding['letterhead_mode'] ?? 'default';
  $hasDesignEarly = ! empty($branding['letterhead_src']);
  $showMpdfHtmlHeader = $mpdfNativeFlow && $withLetterhead && $letterheadModeEarly === 'default' && ! $hasDesignEarly;
  @endphp
  <style>
    @page {
      size: {{ $pageW }}mm {{ $pageH }}mm;
      /* Native flow (mPDF): @page margins = content zone every printed page. */
      margin: {{ $mpdfNativeFlow ? ($contentPadTopMm . 'mm ' . $mr . 'mm ' . $contentPadBottomMm . 'mm ' . $ml . 'mm') : '0' }};
      @if ($showMpdfHtmlHeader)
      odd-header-name: html_agreementDefaultHeader;
      even-header-name: html_agreementDefaultHeader;
      header: html_agreementDefaultHeader;
      @endif
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
      /* Native flow: 100% inside engine content box (@page margins). Preview/fixed boxes: full paper width. */
      width: {{ $mpdfNativeFlow ? '100%' : ($pageW . 'mm') }};
      /* Transparent for mPDF so the letterhead watermark shows through. */
      background: {{ ($forPdf && $isMpdf) ? 'transparent' : '#fff' }};
      --word-page-width: {{ $pageW }}mm;
      --word-page-height: {{ $pageBoxH }}mm;
    }

    body {
      font-family: {{ $agreementFontFamily }};
      font-size: {{ $agreementFontSizePt }}pt;
      color: {{ $agreementFontColor }};
      line-height: {{ $agreementLineHeight }};
    }

    @if ($mpdfNativeFlow)
    /* Native flow (mPDF): page margins reserve letterhead/footer; content fills the zone. */
    .agreement-flow {
      position: relative;
      width: 100%;
      background: transparent;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .agreement-flow .content [data-agreement-page-break],
    .agreement-flow .content .agreement-page-break,
    .agreement-flow .content .mce-pagebreak {
      display: block !important;
      height: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
      border: 0 !important;
      overflow: hidden !important;
      page-break-before: always;
      break-before: page;
    }
    @else
    .agreement-page {
      position: relative;
      width: {{ $pageW }}mm;
      height: {{ $pageBoxH }}mm;
      min-height: {{ $pageBoxH }}mm;
      max-height: {{ $pageBoxH }}mm;
      background: {{ ($forPdf && $isMpdf) ? 'transparent' : '#fff' }};
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
    @endif

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

    @if ($isMpdf)
    /*
     * mPDF: design letterhead is painted behind via applyToMpdf (SetWatermarkImage).
     * Default company header is SetHTMLHeader (not a body overlay) so it cannot
     * consume page 1. Constrain logos so mPDF cannot blow them up.
     */
    .page-header-logo .company-logo-img {
      width: auto;
      height: 24mm;
      max-width: 40mm;
      max-height: 24mm;
      object-fit: contain;
      object-position: left center;
    }
    /* Tighten body spacing so Noto + OTL more often fits on one page. */
    body, .content {
      line-height: {{ $agreementLineHeight }};
    }
    .content p { margin: 0 0 0.22em; }
    .content h1, .content h2, .content h3, .content h4,
    .content p.agreement-h1, .content p.agreement-h2, .content p.agreement-h3, .content p.agreement-h4 { margin: 0 0 0.28em; }
    .content table { margin: 2pt 0; }
    .content table th, .content table td { padding: 3px 6px; }
    .content ul, .content ol { margin: 1pt 0 3pt 16pt; }
    .content li { margin: 0 0 1pt; }
    .content hr { margin: 5pt 0; }
    @endif

    .agreement-page-header {
      position: relative;
      z-index: 2;
      pointer-events: none;
    }

    .agreement-page-body {
      position: relative;
      z-index: 3;
      padding: {{ $mpdfNativeFlow ? 0 : $contentPadTopMm }}mm {{ $mpdfNativeFlow ? 0 : $mr }}mm {{ $mpdfNativeFlow ? 0 : $contentPadBottomMm }}mm {{ $mpdfNativeFlow ? 0 : $ml }}mm;
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
      text-align: left;
    }

    /*
     * mPDF cannot join Arabic OpenType fonts. Text is already presentation
     * forms in logical order, so direction:rtl places and wraps them.
     * bdo.agreement-ar is a visual-order word inside an LTR sentence.
     */
    @if ($isMpdf || $forPdf)
    .agreement-ar-block,
    [dir="rtl"] {
      font-family: {{ $agreementRtlFontFamily }} !important;
      direction: rtl;
      unicode-bidi: isolate;
    }
    bdo.agreement-ar {
      font-family: {{ $agreementRtlFontFamily }} !important;
      direction: ltr;
      unicode-bidi: bidi-override;
    }
    .agreement-ar-block ul,
    .agreement-ar-block ol {
      padding-right: 1.4em;
      padding-left: 0;
    }
    .agreement-ltr-block,
    [dir="ltr"] {
      direction: ltr;
      unicode-bidi: isolate;
    }
    bdi[dir="ltr"] {
      direction: ltr;
      unicode-bidi: isolate;
    }
    @else
    /* On-screen HTML preview / print: keep logical Unicode; browser shapes Arabic. */
    .agreement-ar,
    .agreement-ar-block {
      font-family: {{ $agreementRtlFontFamily }} !important;
      direction: rtl;
    }
    .agreement-ar-block ul,
    .agreement-ar-block ol {
      padding-right: 1.4em;
      padding-left: 0;
    }
    .agreement-ltr-block {
      direction: ltr;
    }
    @endif

    .content p { margin: 0 0 0.5em; }
    .content h1, .content h2, .content h3, .content h4,
    .content p.agreement-h1, .content p.agreement-h2, .content p.agreement-h3, .content p.agreement-h4,
    .content p.agreement-h5, .content p.agreement-h6 {
      margin: 0 0 0.55em;
      line-height: 1.25;
      font-weight: bold;
    }
    .content h1, .content p.agreement-h1 { font-size: {{ $agreementHeadingSizesPt['h1'] ?? 16 }}pt; }
    .content h2, .content p.agreement-h2 { font-size: {{ $agreementHeadingSizesPt['h2'] ?? 14 }}pt; }
    .content h3, .content p.agreement-h3 { font-size: {{ $agreementHeadingSizesPt['h3'] ?? 12 }}pt; }
    .content h4, .content p.agreement-h4 { font-size: {{ $agreementHeadingSizesPt['h4'] ?? 11 }}pt; }
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
    .content strong, .content b { font-weight: bold; }
    .content em, .content i { font-style: italic; }
    .agreement-ar-block em,
    .agreement-ar-block i,
    [dir="rtl"] em,
    [dir="rtl"] i {
      font-family: amiri !important;
      font-style: italic;
    }
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
    // Preview: per-page HTML letterhead.
    // mPDF + design: applyToMpdf paints letterhead behind; no HTML design img.
    // mPDF default company header: htmlpageheader in this document (not a body overlay).
    $showPerPageChrome = $withLetterhead && ! $forPdf;
    $showMpdfFlowWatermark = $forPdf && $isMpdf && $withLetterhead && $hasWatermark;
  @endphp
  @if ($showMpdfHtmlHeader)
  <htmlpageheader name="agreementDefaultHeader">
    {!! app(\App\Services\Agreements\AgreementLetterheadPdfPainter::class)->defaultHtmlHeader($category ?? null) !!}
  </htmlpageheader>
  @endif
  <div class="preview-pages" id="agreement-preview-pages" @if(! $forPdf) aria-live="polite" @endif>
    @if ($mpdfNativeFlow)
    <div class="agreement-flow">
      @if($showMpdfFlowWatermark)
      <div class="letterhead-overlay letterhead-overlay--design">
        @include('agreements.pdf.partials.page-chrome', [
          'pageWidthMm' => $pageW,
          'pageHeightMm' => $pageH,
          'paperHeightMm' => $pageH,
          'branding' => array_merge($branding, ['letterhead_src' => null, 'letterhead_mode' => 'none']),
          'pdfEngine' => $pdfEngine,
        ])
      </div>
      @endif
      <div class="content">
        {!! $body !!}
      </div>
    </div>
    @else
    @foreach ($renderPages as $pageBody)
    <div class="agreement-page preview-page">
      @if($showPerPageChrome)
      <div class="letterhead-overlay{{ $hasDesign ? ' letterhead-overlay--design' : '' }}">
        @include('agreements.pdf.partials.page-chrome', [
          'pageWidthMm' => $pageW,
          'pageHeightMm' => $forPdf ? $pageH : $pageBoxH,
          'paperHeightMm' => $pageH,
          'branding' => $branding,
          'pdfEngine' => $pdfEngine,
        ])
      </div>
      @elseif($showMpdfFlowWatermark)
      <div class="letterhead-overlay letterhead-overlay--design">
        @include('agreements.pdf.partials.page-chrome', [
          'pageWidthMm' => $pageW,
          'pageHeightMm' => $pageH,
          'paperHeightMm' => $pageH,
          'branding' => array_merge($branding, ['letterhead_src' => null, 'letterhead_mode' => 'none']),
          'pdfEngine' => $pdfEngine,
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
    @endif
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

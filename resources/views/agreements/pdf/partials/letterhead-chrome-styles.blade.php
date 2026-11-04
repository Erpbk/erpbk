{{-- Shared header + watermark chrome. Used by PDF/preview pages and the TinyMCE editor overlay. --}}
@php
  $chromeW = $pageWidthMm ?? 210;
  // Prefer full paper height for letterhead artwork (avoid mPDF pageBoxH shrink clipping design).
  $chromeH = $paperHeightMm ?? $pageHeightMm ?? 297;
  $chromeHeaderTop = $headerTopMarginMm ?? 8;
  $chromePrimary = ($branding['primary_color'] ?? null) ?: '#004aad';
@endphp
.page-decor {
  position: absolute;
  top: 0;
  left: 0;
  width: var(--word-page-width, {{ $chromeW }}mm);
  /* Height 0: Dompdf treats a full-page absolute box as in-flow and inserts extra pages. */
  height: 0;
  z-index: 0;
  pointer-events: none;
  overflow: visible;
}

.page-watermark {
  position: absolute;
  width: 90mm;
  height: 90mm;
  z-index: 1;
  opacity: 0.12;
  text-align: center;
  pointer-events: none;
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
  padding-top: {{ $chromeHeaderTop }}mm;
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
  padding: 0 0 4.5mm 0;
  vertical-align: middle;
}

.page-header-logo {
  width: 22%;
  white-space: nowrap;
  vertical-align: middle;
}

.page-header-logo .company-logo-img {
  width: auto;
  height: auto;
  max-width: 48mm;
  max-height: 22mm;
  object-fit: contain;
  object-position: left center;
  display: block;
}

.page-header-logo .company-logo-fallback {
  width: 22mm;
  height: 22mm;
  line-height: 22mm;
  text-align: center;
  font-size: 11pt;
  font-weight: bold;
  border-radius: 2mm;
}

.page-header-brand {
  width: 56%;
  text-align: center;
  vertical-align: middle;
  padding-left: 2.5mm;
  padding-right: 2.5mm;
}

.page-header-spacer {
  width: 22%;
}

.page-header-name {
  margin: 0 0 3.5pt;
  font-size: 14pt;
  color: {{ $chromePrimary }};
  line-height: 1.3;
  text-align: center;
  font-weight: bold;
  letter-spacing: 0.2px;
}

.page-header-meta {
  margin: 0;
  font-size: 9pt;
  color: #64748b;
  line-height: 1.7;
  text-align: center;
  font-weight: normal;
}

.page-header-rule {
  height: 2.5px;
  margin: 0;
  width: 100%;
}

.page-letterhead-design {
  position: absolute;
  top: 0;
  left: 0;
  width: {{ $chromeW }}mm;
  height: 0;
  z-index: 0;
  pointer-events: none;
  overflow: visible;
}

.page-letterhead-design img {
  display: block;
  position: absolute;
  top: 0;
  left: 0;
  width: {{ $chromeW }}mm;
  height: {{ $chromeH }}mm;
  border: 0;
  max-width: none;
  object-fit: fill;
}


{{-- Shared header + watermark chrome. Used by PDF/preview pages and the TinyMCE editor overlay. --}}
@php
  $chromeW = $pageWidthMm ?? 210;
  // Prefer full paper height for letterhead artwork (avoid mPDF pageBoxH shrink clipping design).
  $chromeH = $paperHeightMm ?? $pageHeightMm ?? 297;
  $chromeHeaderTop = $headerTopMarginMm ?? 8;
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
  padding: 0;
  vertical-align: top;
}

.page-header-logo {
  width: 1%;
  white-space: nowrap;
  padding-right: 1.5mm;
  vertical-align: middle;
}

.page-header-logo .company-logo-img {
  width: auto;
  height: 24mm;
  max-width: 40mm;
  max-height: 24mm;
  object-fit: contain;
  object-position: left center;
  display: block;
}

.page-header-logo .company-logo-fallback {
  width: 24mm;
  height: 24mm;
  line-height: 24mm;
  text-align: center;
  font-size: 12pt;
  font-weight: bold;
  border-radius: 3mm;
}

.page-header-info {
  text-align: left;
  vertical-align: middle;
  padding-top: 0;
}

.page-header-name {
  margin: 0 0 1pt;
  font-size: 11pt;
  color: #0f172a;
  line-height: 1.3;
  text-align: left;
  font-weight: bold;
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


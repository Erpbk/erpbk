@php
$p = $branding['primary_color'] ?? '#1e3a8a';
$s = $branding['secondary_color'] ?? '#2563eb';
$pSoft = $branding['primary_soft'] ?? '#e0e7ff';
$logoSrc = $branding['logo_src'] ?? null;
$wmTop = round(($pageHeightMm ?? 297) * 0.36, 1);
$wmLeft = round((($pageWidthMm ?? 210) - 90) / 2, 1);
@endphp
<div class="page-decor" aria-hidden="true">
  <div class="corner-shapes corner-shapes--tr">
    <div class="corner-blob corner-blob--1" style="background-color: {{ $s }};">&nbsp;</div>
    <div class="corner-blob corner-blob--2" style="background-color: {{ $p }};">&nbsp;</div>
    <div class="corner-blob corner-blob--3" style="background-color: {{ $pSoft }};">&nbsp;</div>
  </div>
  <div class="corner-shapes corner-shapes--bl">
    <div class="corner-blob corner-blob--1" style="background-color: {{ $p }};">&nbsp;</div>
    <div class="corner-blob corner-blob--2" style="background-color: {{ $s }};">&nbsp;</div>
    <div class="corner-blob corner-blob--3" style="background-color: {{ $pSoft }};">&nbsp;</div>
  </div>

  @if(!empty($logoSrc))
  <div class="page-watermark" style="top: {{ $wmTop }}mm; left: {{ $wmLeft }}mm;">
    <img src="{{ $logoSrc }}" alt="">
  </div>
  @endif
</div>

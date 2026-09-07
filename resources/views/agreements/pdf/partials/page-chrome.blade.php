{{-- Shared letterhead chrome: uploaded full-page design, generated company header, optional watermark. --}}
@php
  $letterheadSrc = $letterheadSrc ?? ($branding['letterhead_src'] ?? null);
  $watermarkSrc = $branding['watermark_src'] ?? null;
  $letterheadMode = $branding['letterhead_mode'] ?? 'default';
  $chromeW = $pageWidthMm ?? 210;
  $chromeH = $pageHeightMm ?? 297;
  $wmSize = 90;
  $wmTop = round(($chromeH - $wmSize) / 2, 1);
  $wmLeft = round(($chromeW - $wmSize) / 2, 1);
@endphp
@if(!empty($letterheadSrc))
<div class="page-letterhead-design" aria-hidden="true">
  <img src="{{ $letterheadSrc }}" alt=""
    style="width: {{ $chromeW }}mm; height: {{ $chromeH }}mm; max-width: none; border: 0; display: block; position: absolute; top: 0; left: 0;">
</div>
@elseif($letterheadMode !== 'none')
@include('agreements.pdf.partials.page-header')
@endif
@if(!empty($watermarkSrc))
<div class="page-watermark" aria-hidden="true" style="top: {{ $wmTop }}mm; left: {{ $wmLeft }}mm;">
  <img src="{{ $watermarkSrc }}" alt="">
</div>
@endif

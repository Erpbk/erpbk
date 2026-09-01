{{-- Shared letterhead chrome: uploaded full-page design, or generated company header. --}}
@php
  $letterheadSrc = $letterheadSrc ?? ($branding['letterhead_src'] ?? null);
  $chromeW = $pageWidthMm ?? 210;
  $chromeH = $pageHeightMm ?? 297;
@endphp
@if(!empty($letterheadSrc))
<div class="page-letterhead-design" aria-hidden="true">
  <img src="{{ $letterheadSrc }}" alt=""
    style="width: {{ $chromeW }}mm; height: {{ $chromeH }}mm; max-width: none; border: 0; display: block; position: absolute; top: 0; left: 0;">
</div>
@else
@include('agreements.pdf.partials.page-decor', [
  'pageWidthMm' => $chromeW,
  'pageHeightMm' => $chromeH,
])
@include('agreements.pdf.partials.page-header')
@endif

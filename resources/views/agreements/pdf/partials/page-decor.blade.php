@php
$logoSrc = $branding['logo_src'] ?? null;
$wmTop = round(($pageHeightMm ?? 297) * 0.36, 1);
$wmLeft = round((($pageWidthMm ?? 210) - 90) / 2, 1);
@endphp
<div class="page-decor" aria-hidden="true">
  @if(!empty($logoSrc))
  <div class="page-watermark" style="top: {{ $wmTop }}mm; left: {{ $wmLeft }}mm;">
    <img src="{{ $logoSrc }}" alt="">
  </div>
  @endif
</div>

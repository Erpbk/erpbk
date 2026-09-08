@php
$logoSrc = $branding['logo_src'] ?? null;
$primary = $branding['primary_color'] ?? '#1e3a8a';
$initial = strtoupper(\Illuminate\Support\Str::substr($branding['name'] ?? 'C', 0, 1));
$pdfEngine = $pdfEngine ?? ($branding['pdf_engine'] ?? 'html');
$isMpdfLogo = $pdfEngine === 'mpdf';
@endphp
@if(!empty($logoSrc))
@if($isMpdfLogo)
<img src="{{ $logoSrc }}" alt="{{ $branding['name'] ?? '' }}" class="company-logo-img" width="170" height="68" style="width: 45mm; height: 18mm; max-width: 58mm; max-height: 22mm;">
@else
<img src="{{ $logoSrc }}" alt="{{ $branding['name'] ?? '' }}" class="company-logo-img">
@endif
@else
<div class="company-logo-fallback" style="background-color: {{ $primary }}; color: {{ $branding['text_on_primary'] ?? '#fff' }};">
  {{ $initial }}
</div>
@endif

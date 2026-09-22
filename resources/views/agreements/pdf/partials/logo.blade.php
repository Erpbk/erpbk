@php
$logoSrc = $branding['logo_src'] ?? null;
$primary = $branding['primary_color'] ?? '#1e3a8a';
$initial = strtoupper(\Illuminate\Support\Str::substr($branding['name'] ?? 'C', 0, 1));
$pdfEngine = $pdfEngine ?? ($branding['pdf_engine'] ?? 'html');
$isMpdfLogo = $pdfEngine === 'mpdf';
$logoMm = app(\App\Services\Agreements\AgreementPdfBranding::class)->letterheadLogoBoxMm();
$logoW = (float) ($logoWidthMm ?? $logoMm['width']);
$logoH = (float) ($logoHeightMm ?? $logoMm['height']);
$logoPxW = (int) ($logoWidthPx ?? round($logoW * 96 / 25.4));
$logoPxH = (int) ($logoHeightPx ?? round($logoH * 96 / 25.4));
@endphp
@if(!empty($logoSrc))
@if($isMpdfLogo)
<img src="{{ $logoSrc }}" alt="{{ $branding['name'] ?? '' }}" class="company-logo-img" width="{{ $logoPxW }}" height="{{ $logoPxH }}" style="width: {{ $logoW }}mm; height: {{ $logoH }}mm; max-width: {{ $logoW }}mm; max-height: {{ $logoH }}mm; display: block;">
@else
<img src="{{ $logoSrc }}" alt="{{ $branding['name'] ?? '' }}" class="company-logo-img" style="width: auto; height: {{ $logoH }}mm; max-width: {{ $logoMm['width'] }}mm; max-height: {{ $logoH }}mm; object-fit: contain; object-position: left center; display: block;">
@endif
@else
<div class="company-logo-fallback" style="background-color: {{ $primary }}; color: {{ $branding['text_on_primary'] ?? '#fff' }};">
  {{ $initial }}
</div>
@endif

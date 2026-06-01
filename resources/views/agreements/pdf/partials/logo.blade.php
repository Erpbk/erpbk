@php
$logoSrc = $branding['logo_src'] ?? null;
$primary = $branding['primary_color'] ?? '#1e3a8a';
$initial = strtoupper(\Illuminate\Support\Str::substr($branding['name'] ?? 'C', 0, 1));
@endphp
@if(!empty($logoSrc))
<img src="{{ $logoSrc }}" alt="{{ $branding['name'] ?? '' }}" class="company-logo-img">
@else
<div class="company-logo-fallback" style="background-color: {{ $primary }}; color: {{ $branding['text_on_primary'] ?? '#fff' }};">
  {{ $initial }}
</div>
@endif
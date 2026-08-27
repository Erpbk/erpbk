@props([
    'label',
    'value' => null,
    'expiry' => false,
    'expiryName' => null,
    'badge' => null,
])

@php
    $isEmpty = $value === null || $value === '';
    $expiryBadge = $badge
        ?? (($expiry && ! $isEmpty)
            ? \App\Support\EntityExpiry::badgeForDate($value, $expiryName ?? $label)
            : null);
@endphp
<div {{ $attributes->class(['col-md-3 col-sm-6 entity-info-field mb-3']) }}>
    <label>{{ $label }}</label>
    <p class="mb-0">
        @if ($expiryBadge)
            @include('riders._document_expiry_badge', ['badge' => $expiryBadge])
        @elseif ($isEmpty)
            <span class="text-muted">—</span>
        @else
            {{ $value }}
        @endif
    </p>
</div>

@php
  $fieldKey = $fieldKey ?? '';
  $docRider = $rider ?? $riders ?? null;
  $dateValue = $dateValue
    ?? ($docRider instanceof \App\Models\Riders ? ($docRider->{$fieldKey} ?? null) : null)
    ?? ($result[$fieldKey] ?? null);
  $expiryBadge = $fieldKey !== ''
    ? \App\Support\RiderDocumentReplacement::expiryBadgeForField(
        $docRider instanceof \App\Models\Riders ? $docRider : null,
        $fieldKey,
        $dateValue
      )
    : null;
@endphp
@if ($expiryBadge)
  @include('riders._document_expiry_badge', ['badge' => $expiryBadge])
@endif

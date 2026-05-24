@php
$status = strtolower(trim((string) ($status ?? '')));
$badge = match ($status) {
    'active' => 'bg-label-success',
    'inactive' => 'bg-label-danger',
    'on_leave', 'on leave' => 'bg-label-warning',
    default => 'bg-label-secondary',
};
$label = $status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Unknown';
@endphp
<span class="badge {{ $badge }}">{{ $label }}</span>

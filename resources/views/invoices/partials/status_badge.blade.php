{{-- Shared blinking Paid / Unpaid / Partially Paid badge --}}
@php
$status = $status ?? 'unpaid'; // paid|unpaid|partial|partially_paid
if ($status === 'partially_paid') {
    $status = 'partial';
}
$label = $label ?? match ($status) {
    'paid' => 'Paid',
    'partial' => 'Partially Paid',
    default => 'Unpaid',
};
$class = match ($status) {
    'paid' => 'invoice-status-paid',
    'partial' => 'invoice-status-partial',
    default => 'invoice-status-unpaid',
};
@endphp
<span class="invoice-status-badge {{ $class }}">{{ $label }}</span>

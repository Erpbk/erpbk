@props(['voucher'])

@php
    $status = $voucher->approval_status ?? 'pending_approval';
    $map = [
        'pending_approval' => ['label' => 'Pending Approval', 'class' => 'bg-label-warning'],
        'approved' => ['label' => 'Approved', 'class' => 'bg-label-success'],
        'rejected' => ['label' => 'Rejected', 'class' => 'bg-label-danger'],
    ];
    $meta = $map[$status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $status)), 'class' => 'bg-label-secondary'];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$meta['class']]) }}>{{ $meta['label'] }}</span>

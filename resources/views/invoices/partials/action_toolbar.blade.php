{{--
  Shared invoice action toolbar (matches rider invoice UI).

  $isPaid (bool), $isPartial (bool optional)
  Optional action configs: editUrl/editTitle/editCan, clone*, email*, downloadUrl,
  paymentUrl/paymentTitle/paymentCan, showPayment, markSettled*,
  deleteUrl/deleteCan OR deleteFormRoute/deleteCan
--}}
@php
$isPaid = (bool) ($isPaid ?? false);
$isPartial = (bool) ($isPartial ?? false);
$showPayment = isset($showPayment) ? (bool) $showPayment : ! $isPaid;
$editTitle = $editTitle ?? 'Edit Invoice';
$cloneTitle = $cloneTitle ?? 'Clone Invoice';
$emailTitle = $emailTitle ?? 'Send Email';
$paymentTitle = $paymentTitle ?? 'Record Payment';
$markSettledTitle = $markSettledTitle ?? 'Mark Settled (no payment)';
$deleteConfirm = $deleteConfirm ?? 'Are you sure you want to delete this invoice?';
$payStatusClass = $isPaid ? 'is-paid' : ($isPartial ? 'is-partial' : 'is-unpaid');
$payStatusLabel = $isPaid ? 'Paid' : ($isPartial ? 'Partially Paid' : 'Unpaid');

$canList = static function ($can) {
    if ($can === null || $can === '' || $can === true) {
        return null;
    }
    return is_array($can) ? $can : [$can];
};
@endphp
<span class="invoice-pay-status {{ $payStatusClass }}">{{ $payStatusLabel }}</span>

@if(!empty($editUrl))
    @php $perms = $canList($editCan ?? null); @endphp
    @if($perms === null)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="{{ $editTitle }}" data-close-right-modal="1" data-action="{{ $editUrl }}">
            <i class="ti ti-edit"></i><span>Edit</span>
        </a>
    @else
        @canany($perms)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="{{ $editTitle }}" data-close-right-modal="1" data-action="{{ $editUrl }}">
            <i class="ti ti-edit"></i><span>Edit</span>
        </a>
        @endcanany
    @endif
@endif

@if(!empty($cloneUrl))
    @php $perms = $canList($cloneCan ?? null); @endphp
    @if($perms === null)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="{{ $cloneTitle }}" data-close-right-modal="1" data-action="{{ $cloneUrl }}">
            <i class="ti ti-copy"></i><span>Clone</span>
        </a>
    @else
        @canany($perms)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="{{ $cloneTitle }}" data-close-right-modal="1" data-action="{{ $cloneUrl }}">
            <i class="ti ti-copy"></i><span>Clone</span>
        </a>
        @endcanany
    @endif
@endif

@if(!empty($emailUrl))
    @can($emailCan ?? 'email_create')
    <a href="javascript:void(0);" class="action-btn show-modal" data-size="md" data-title="{{ $emailTitle }}" data-action="{{ $emailUrl }}">
        <i class="ti ti-mail"></i><span>Send Email</span>
    </a>
    @endcan
@endif

@if(!empty($downloadUrl))
<a href="{{ $downloadUrl }}" class="action-btn" target="_blank" rel="noopener">
    <i class="ti ti-download"></i><span>Download</span>
</a>
@endif

<button type="button" class="action-btn js-print-modal-content">
    <i class="ti ti-printer"></i><span>Print</span>
</button>

@if($showPayment && !empty($paymentUrl))
    @php $perms = $canList($paymentCan ?? null); @endphp
    @if($perms === null)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="{{ $paymentTitle }}" data-close-right-modal="1" data-action="{{ $paymentUrl }}">
            <i class="ti ti-cash"></i><span>Payment</span>
        </a>
    @else
        @canany($perms)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="xl" data-title="{{ $paymentTitle }}" data-close-right-modal="1" data-action="{{ $paymentUrl }}">
            <i class="ti ti-cash"></i><span>Payment</span>
        </a>
        @endcanany
    @endif
@endif

@if($showPayment && !empty($markSettledUrl))
    @php $perms = $canList($markSettledCan ?? null); @endphp
    @if($perms === null)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="lg" data-title="{{ $markSettledTitle }}" data-action="{{ $markSettledUrl }}">
            <i class="ti ti-checks"></i><span>Mark Settled</span>
        </a>
    @else
        @canany($perms)
        <a href="javascript:void(0);" class="action-btn show-modal" data-size="lg" data-title="{{ $markSettledTitle }}" data-action="{{ $markSettledUrl }}">
            <i class="ti ti-checks"></i><span>Mark Settled</span>
        </a>
        @endcanany
    @endif
@endif

@if(!empty($deleteFormRoute))
    @php $perms = $canList($deleteCan ?? null); @endphp
    @if($perms === null)
        {!! Form::open(['route' => $deleteFormRoute, 'method' => 'DELETE', 'id' => 'formajax', 'style' => 'display:inline;']) !!}
        <button type="submit" class="action-btn" onclick="return confirm(@json($deleteConfirm));">
            <i class="ti ti-trash"></i><span>Delete</span>
        </button>
        {!! Form::close() !!}
    @else
        @canany($perms)
        {!! Form::open(['route' => $deleteFormRoute, 'method' => 'DELETE', 'id' => 'formajax', 'style' => 'display:inline;']) !!}
        <button type="submit" class="action-btn" onclick="return confirm(@json($deleteConfirm));">
            <i class="ti ti-trash"></i><span>Delete</span>
        </button>
        {!! Form::close() !!}
        @endcanany
    @endif
@elseif(!empty($deleteUrl))
    @php $perms = $canList($deleteCan ?? null); @endphp
    @if($perms === null)
        <a href="javascript:void(0);" class="action-btn" onclick="typeof confirmDelete === 'function' ? confirmDelete(@json($deleteUrl)) : (confirm(@json($deleteConfirm)) ? window.location.href = @json($deleteUrl) : null);">
            <i class="ti ti-trash"></i><span>Delete</span>
        </a>
    @else
        @canany($perms)
        <a href="javascript:void(0);" class="action-btn" onclick="typeof confirmDelete === 'function' ? confirmDelete(@json($deleteUrl)) : (confirm(@json($deleteConfirm)) ? window.location.href = @json($deleteUrl) : null);">
            <i class="ti ti-trash"></i><span>Delete</span>
        </a>
        @endcanany
    @endif
@endif

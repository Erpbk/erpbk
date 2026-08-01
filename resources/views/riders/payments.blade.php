@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Rider Payments')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 280px);
    }
</style>
@endpush
@section('content')
@php
    $__companySlug = \App\Support\CompanyRouteContext::slug();
    $voucherTypes = $voucherTypes ?? [];
    $voucherCreateParams = static function (string $type) use ($__companySlug): array {
        $params = ['type' => $type];
        if (!empty($__companySlug)) {
            $params['company_slug'] = $__companySlug;
        }
        return $params;
    };
@endphp
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@canany(['riders_payments_view', 'riders_payments_create', 'riders_voucher_create'])
<section class="content-header">
    @include('flash::message')
    <div class="row my-3">
        <div class="col-sm-12 col-lg-12">
            <div class="action-buttons d-flex justify-content-end">
                @can('riders_voucher_create')
                <div class="action-dropdown-container">
                    <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Add New</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @forelse($voucherTypes as $code => $label)
                        @php
                            $isPaymentVoucher = $code === 'PAY';
                            $itemAction = $isPaymentVoucher
                                ? route('payments.create', array_filter([
                                    'invoice_type' => 'rider',
                                    'company_slug' => $__companySlug,
                                ]))
                                : route('riders.voucher.create', $voucherCreateParams($code));
                            $itemTitle = $isPaymentVoucher ? 'Add Payment Voucher' : $label;
                        @endphp
                        @if($isPaymentVoucher)
                        @canany(['riders_payments_create', 'riders_voucher_create'])
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);"
                            data-size="xl"
                            data-title="{{ $itemTitle }}"
                            data-action="{{ $itemAction }}">
                            <i class="ti ti-file-invoice"></i>
                            <div>
                                <div class="action-dropdown-item-text">{{ $label }}</div>
                                <div class="action-dropdown-item-desc">Record payment against an unpaid rider invoice</div>
                            </div>
                        </a>
                        @endcanany
                        @else
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);"
                            data-size="xl"
                            data-title="{{ $itemTitle }}"
                            data-action="{{ $itemAction }}">
                            <i class="ti ti-file-invoice"></i>
                            <div>
                                <div class="action-dropdown-item-text">{{ $label }}</div>
                                <div class="action-dropdown-item-desc">Open {{ $label }} voucher form</div>
                            </div>
                        </a>
                        @endif
                        @empty
                        <div class="action-dropdown-item text-muted">
                            <div>
                                <div class="action-dropdown-item-text">No voucher types</div>
                                <div class="action-dropdown-item-desc">Assign rider voucher types in Voucher Settings</div>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>
</section>
<div class="content">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('payments.table')
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body">
        <h5 class="card-title">You are not authorized to access this page</h5>
    </div>
</div>
@endcanany
@endsection

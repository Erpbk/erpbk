@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Rider Payments')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 280px);
    }

    .filter-sidebar {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100vh;
        background: white;
        box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
        transition: right 0.3s ease;
        z-index: 1050;
        overflow-y: auto;
    }

    .filter-sidebar.open {
        right: 0;
    }

    .filter-header {
        padding: 15px 20px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
    }

    .filter-body {
        padding: 20px;
    }

    .filter-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease;
    }

    .filter-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .card-search {
        max-width: 300px;
    }

    .btn-close {
        border: none;
        background: none;
        font-size: 1.2rem;
        cursor: pointer;
    }
</style>
@endpush
@section('content')
@php
$__companySlug = \App\Support\CompanyRouteContext::slug();
$banks = $banks ?? \App\Models\Banks::active()->get();
@endphp
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@canany(['riders_payments_view', 'riders_payments_create', 'riders_voucher_create'])
<section class="content-header">
    @include('flash::message')
</section>
<div class="content">
    @include('flash::message')
    <div class="clearfix"></div>

    <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
        <div class="filter-header">
            <h5>Filter Payments</h5>
            <button type="button" class="btn-close" id="closeSidebar">&times;</button>
        </div>
        <div class="filter-body" id="searchTopbody">
            <form id="filterForm" action="{{ route('rider.payments') }}" method="GET">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="voucher_id">Voucher ID</label>
                        <input type="text" name="voucher_id" class="form-control" placeholder="e.g. PV-0001" value="{{ request('voucher_id') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="date_of_payment">Date of Payment</label>
                        <input type="date" name="date_of_payment" class="form-control" value="{{ request('date_of_payment') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="billing_month">Billing Month</label>
                        <input type="month" name="billing_month" class="form-control" value="{{ request('billing_month') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="amount_type">Payment Mode</label>
                        <select class="form-control" id="amount_type" name="amount_type">
                            <option value="">Select</option>
                            @foreach(['Cash', 'Online', 'Cheque', 'Credit'] as $mode)
                            <option value="{{ $mode }}" {{ request('amount_type') == $mode ? 'selected' : '' }}>{{ $mode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="bank_id">Bank / Cash</label>
                        <select class="form-control" id="bank_id" name="bank_id">
                            <option value="">Select</option>
                            @foreach($banks as $bank)
                            <option value="{{ $bank->id }}" {{ (string) request('bank_id') === (string) $bank->id ? 'selected' : '' }}>
                                {{ optional($bank->account)->account_code }} - {{ optional($bank->account)->name ?? $bank->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="reference">Reference</label>
                        <input type="text" name="reference" class="form-control" placeholder="Filter by reference" value="{{ request('reference') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="quick_search">Quick Search</label>
                        <input type="text" name="quick_search" id="quickSearchSidebar" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                    </div>
                    <div class="col-md-12 form-group text-center">
                        <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                        <a href="{{ route('rider.payments') }}" class="btn btn-secondary pull-right mt-3 mr-2"><i class="fa fa-refresh mx-2"></i> Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="filter-overlay" id="filterOverlay"></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <div class="d-flex justify-content-end gap-2 align-items-center">
                @can('riders_payments_create')
                <a href="javascript:void(0);"
                    class="btn btn-primary btn-sm show-modal"
                    data-size="xl"
                    data-title="Record Rider Payment"
                    data-action="{{ route('payments.create', array_filter([
                        'invoice_type' => 'rider',
                        'company_slug' => $__companySlug,
                    ])) }}">
                    <i class="ti ti-file-invoice"></i> Payment Voucher
                </a>
                @endcan
                @can('riders_voucher_create')
                <a href="javascript:void(0);"
                    class="btn btn-outline-primary btn-sm show-modal"
                    data-size="sm"
                    data-title="Import Rider Vouchers"
                    data-action="{{ route('riders.import_rider_vouchers', ['modal' => 1]) }}">
                    <i class="ti ti-upload"></i> Import Vouchers
                </a>
                @endcan
                <button type="button" class="btn btn-primary btn-sm openFilterSidebar"><i class="fa fa-search"></i> Filter</button>
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

@section('page-script')
<script type="text/javascript">
    (function checkJQuery() {
        if (typeof jQuery === 'undefined') {
            setTimeout(checkJQuery, 50);
            return;
        }

        $(document).ready(function() {
            if (typeof $.fn.select2 !== 'undefined') {
                $('#amount_type, #bank_id').select2({
                    dropdownParent: $('#searchTopbody'),
                    allowClear: true,
                    placeholder: 'Select'
                });
            }

            $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
                e.preventDefault();
                $('#filterSidebar').addClass('open');
                $('#filterOverlay').addClass('show');
            });

            $(document).on('click', '#closeSidebar, #filterOverlay', function(e) {
                e.preventDefault();
                $('#filterSidebar').removeClass('open');
                $('#filterOverlay').removeClass('show');
            });

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                $('#loading-overlay').css('display', 'flex');
                $('#filterSidebar').removeClass('open');
                $('#filterOverlay').removeClass('show');

                let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && String(field.value).trim() !== '');
                let formData = $.param(filteredFields);

                $.ajax({
                    url: "{{ route('rider.payments') }}",
                    type: "GET",
                    data: formData,
                    success: function(data) {
                        $('#table-data').html(data.tableData);
                        let newUrl = "{{ route('rider.payments') }}" + (formData ? '?' + formData : '');
                        history.pushState(null, '', newUrl);
                        $('#quickSearch').val($('#quickSearchSidebar').val() || '');
                        $('#loading-overlay').hide();
                    },
                    error: function() {
                        $('#loading-overlay').hide();
                    }
                });
            });

            $('#quickSearch').on('keyup', function(e) {
                if (e.keyCode === 13 || $(this).val().length === 0) {
                    $('#quickSearchSidebar').val($(this).val());
                    $('#filterForm').submit();
                }
            });

            $('#quickSearchSidebar').on('keyup', function(e) {
                if (e.keyCode === 13 || $(this).val().length === 0) {
                    $('#quickSearch').val($(this).val());
                    $('#filterForm').submit();
                }
            });
        });
    })();
</script>
@endsection
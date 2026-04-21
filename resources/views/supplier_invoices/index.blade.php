@extends('layouts.app')

@section('title','Supplier Invoices')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 210px);
    }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="">
        <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6 text-right">
                @can('customer_create')
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add New</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Invoice" data-action="{{ route('supplier_invoices.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Invoice</div>
                                    <div class="action-dropdown-item-desc">Add a new Spplier Invoice</div>
                                </div>
                            </a>
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Invoice" data-action="{{ route('supplier_invoices.import') }}">
                                <i class="ti ti-arrow-up"></i>
                                <div class="action-dropdown-item-text">Import Invoice</div>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>
</section>
<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1100">
    <div class="filter-header">
        <h5>Filter Invoices</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('supplier_invoices.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="company_name">Filter by Supplier</label>
                    <select class="form-control" id="supplier_id" name="supplier_id">
                        @php
                        $customers = \App\Models\Supplier::all();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($customers as $company)
                        <option value="{{ $company->id }}" {{ request('name') == $company->name ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="billing month">Filter by Billing Moth</label>
                    <input type="month" name="billing_month" class="form-control">
                </div>
                <div class="form-group col-md-12">
                    <label for="inv_id">Invoice ID</label>
                    <input type="text" name="inv_id" class="form-control" placeholder="Filter By Invoice ID" value="{{ request('inv_id') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="inv_date_from">Invoice Date From</label>
                    <input type="date" name="inv_date_from" class="form-control" placeholder="Filter By Invoice Date From" value="{{ request('inv_date_from') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="inv_date_to">Invoice Date To</label>
                    <input type="date" name="inv_date_to" class="form-control" placeholder="Filter By Invoice Date To" value="{{ request('inv_date_to') }}">
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>

<div class="content mt-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter</button>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('supplier_invoices.table')
        </div>
    </div>
</div>
@endsection

@section('page-script')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        })
    }
    $(document).ready(function() {
        $('#supplier_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Supplier",
            allowClear: true
        });
    });
</script>

<script>
    $(document).ready(function() {
        if (typeof initSupplierInvoiceForm === 'function') {
            initSupplierInvoiceForm(document);
            return;
        }

        // Fallback: bind supplier invoice behavior if shared JS is unavailable
        const $doc = $(document);

        function tenantApiBaseForFallback() {
            if (typeof resolveTenantAppBase === 'function') {
                return resolveTenantAppBase();
            }
            var base = ($('#base_url').val() || window.location.origin || '').replace(/\/$/, '');
            var m = (window.location.pathname || '').match(/\/app\/([^/]+)/);
            return (m && m[1]) ? base + '/app/' + m[1] : base;
        }

        function calculateSupplierRow(rowEl, skipTotal) {
            const $row = $(rowEl).closest('.item-row');
            let qty = $row.find('.quantity').val();
            const rate = parseFloat($row.find('.rate').val()) || 0;
            const vat = parseFloat($row.find('.vat').val()) || 0;

            if (qty === '') {
                qty = 1;
                $row.find('.quantity').val(qty);
            }
            qty = parseFloat(qty) || 0;

            const subtotal = Math.round((qty * rate) * 100) / 100;
            const vatAmount = Math.round((subtotal * (vat / 100)) * 100) / 100;
            const total = Math.round((subtotal + vatAmount) * 100) / 100;

            $row.find('.vatAmount').val(vatAmount.toFixed(2));
            $row.find('.item-total').val(total.toFixed(2));

            if (!skipTotal) {
                calculateSupplierTotals();
            }
            return {
                subtotal,
                vatAmount,
                total
            };
        }

        function calculateSupplierTotals() {
            let subtotal = 0;
            let vatTotal = 0;
            let grandTotal = 0;

            $('#row-container .item-row').each(function() {
                const r = calculateSupplierRow(this, true);
                subtotal += r.subtotal;
                vatTotal += r.vatAmount;
                grandTotal += r.total;
            });

            $('#subtotal').val(subtotal.toFixed(2));
            $('#vat_total').val(vatTotal.toFixed(2));
            $('#total_cost').val(grandTotal.toFixed(2));
        }

        if ($.fn.select2) {
            $('.select2').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        width: '100%'
                    });
                }
            });
        }

        calculateSupplierTotals();
        if ($('#row-container .item-row').length <= 1) {
            $('.remove-row').hide();
        }

        $doc.off('change.supplier-fallback select2:select.supplier-fallback', '.item-select')
            .on('change.supplier-fallback select2:select.supplier-fallback', '.item-select', function() {
                const $row = $(this).closest('.item-row');
                const itemId = $(this).val();
                const selected = $(this).find('option:selected');
                const fallbackRate = parseFloat(selected.data('price')) || 0;
                const fallbackVat = parseFloat(selected.data('vat')) || 0;

                if (!itemId) {
                    $row.find('.rate').val('0');
                    $row.find('.vat').val('0');
                    calculateSupplierRow($row);
                    return;
                }

                $row.find('.vat').val(fallbackVat);

                var supplierId = $('#customer_id').val();
                if (!supplierId || supplierId === '0') {
                    $row.find('.rate').val(fallbackRate.toFixed(2));
                    calculateSupplierRow($row);
                    return;
                }

                $.ajax({
                    url: tenantApiBaseForFallback() + '/search_item_price/' + supplierId + '/' + itemId,
                    type: 'GET',
                    dataType: 'JSON',
                    success: function(data) {
                        const serverPrice = data && data.price !== undefined ? data.price : (data && data.pirce !== undefined ? data.pirce : fallbackRate);
                        $row.find('.rate').val(parseFloat(serverPrice || 0).toFixed(2));
                        calculateSupplierRow($row);
                    },
                    error: function() {
                        $row.find('.rate').val(fallbackRate.toFixed(2));
                        calculateSupplierRow($row);
                    }
                });
            });

        $doc.off('keyup.supplier-fallback change.supplier-fallback', '.quantity, .rate, .vat')
            .on('keyup.supplier-fallback change.supplier-fallback', '.quantity, .rate, .vat', function() {
                calculateSupplierRow(this);
            });

        $doc.off('click.supplier-fallback-remove', '.remove-row')
            .on('click.supplier-fallback-remove', '.remove-row', function() {
                if ($('#row-container .item-row').length <= 1) {
                    alert('At least one item is required');
                    return;
                }
                $(this).closest('.item-row').remove();
                if ($('#row-container .item-row').length <= 1) {
                    $('.remove-row').hide();
                }
                calculateSupplierTotals();
            });

        $('#add-row').off('click.supplier-fallback-add').on('click.supplier-fallback-add', function() {
            const $first = $('#row-container .item-row:first');
            if (!$first.length) {
                return;
            }
            const $row = $first.clone();
            $row.find('.item-select').val('');
            $row.find('.quantity').val(1);
            $row.find('.rate').val(0);
            $row.find('.vat').val(0);
            $row.find('.vatAmount').val(0);
            $row.find('.item-total').val(0);
            $row.find('.remove-row').show();
            $row.find('.select2').removeClass('select2-hidden-accessible').next('.select2').remove();
            $('#row-container').append($row);
            if ($.fn.select2) {
                $row.find('.select2').select2({
                    width: '100%'
                });
            }
            calculateSupplierTotals();
        });
    });
</script>
@endsection
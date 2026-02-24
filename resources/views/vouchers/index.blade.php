@extends('layouts.app')
@section('title', 'Vouchers')

@push('page-styles')
<style>
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

    /* Fix dropdown z-index issue in table-responsive */
    .table-responsive .dropdown-menu {
        z-index: 9999 !important;
        position: absolute !important;
    }

    /* Ensure dropdown appears above overflow content */
    .dropdown-menu {
        z-index: 9999 !important;
    }

    /* Override Bootstrap's dropdown positioning for table context */
    .table .dropdown-menu {
        transform: none !important;
        will-change: auto !important;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Vouchers</h3>
                </div>
                <div class="col-sm-6">
                    @can('voucher_create')
                    <a class="btn btn-info action-btn show-modal"
                        href="javascript:void(0);" data-size="sm" data-title="Import Voucher" data-action="{{ route('voucher.import') }}">
                        Import Voucher
                    </a>
                    @foreach(App\Helpers\General::ActiveVoucherTypesForCreate() as $key => $value)
                    @if($key !== 'RFV' && $key !== 'LV' && $key !== 'VL' && $key !== 'SV' && $key !== 'AL' && $key !== 'INC' && $key !== 'PN' && $key !== 'PAY' && $key !== 'COD' && $key !== 'VC' && $key !== 'RI' && $key !== 'GV' && $key !== 'RV' && $key !== 'PV')
                    <a class="show-modal action-btn btn btn-primary" style="margin-right:5px;"
                        href="javascript:void(0);"
                        data-size="xl"
                        data-title="Create {{$value}}"
                        data-action="{{ route('vouchers.create', ['vt' => $key]) }}">
                        <i class="fa fa-plus"></i>&nbsp;{{$value}}
                    </a>
                    @endif
                    @endforeach
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-0">
        <div class="clearfix"></div>

        <!-- Filter Sidebar -->
        <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
            <div class="filter-header">
                <h5>Filter Vouchers</h5>
                <button type="button" class="btn-close" id="closeSidebar">&times;</button>
            </div>
            <div class="filter-body" id="searchTopbody">
                <form id="filterForm" action="{{ route('vouchers.index') }}" method="GET">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="voucher_id">Voucher ID</label>
                            <input type="text" name="voucher_id" class="form-control" placeholder="Filter By Voucher ID (e.g., JV-0001)" value="{{ request('voucher_id') }}">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="trans_date">Transaction Date</label>
                            <input type="date" name="trans_date" class="form-control" value="{{ request('trans_date') }}">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="billing_month">Billing Month</label>
                            <input type="month" name="billing_month" class="form-control" value="{{ request('billing_month') }}">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="voucher_type">Voucher Type</label>
                            <select class="form-control" id="voucher_type" name="voucher_type">
                                <option value="" selected>Select</option>
                                @foreach(App\Helpers\General::VoucherType() as $key => $value)
                                <option value="{{ $key }}" {{ request('voucher_type') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="created_by">Created By</label>
                            <select class="form-control" id="created_by" name="created_by">
                                @php
                                $createdByUsers = \App\Models\Vouchers::whereNotNull('Created_By')
                                ->pluck('Created_By')
                                ->unique();
                                @endphp
                                <option value="" selected>Select</option>
                                @foreach($createdByUsers as $userId)
                                @php
                                $user = \App\Models\User::find($userId);
                                @endphp
                                @if($user)
                                <option value="{{ $userId }}" {{ request('created_by') == $userId ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="quick_search">Quick Search</label>
                            <input type="text" name="quick_search" id="quickSearchSidebar" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                        </div>
                        <div class="col-md-12 form-group text-center">
                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                            <a href="{{ route('vouchers.index') }}" class="btn btn-secondary pull-right mt-3 mr-2"><i class="fa fa-refresh mx-2"></i> Clear Filters</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="filter-overlay" id="filterOverlay"></div>

        {{-- Include Column Control Panel --}}
        @php
        $tableColumns = [
        ['data' => 'voucher_id', 'title' => 'Voucher ID'],
        ['data' => 'trans_date', 'title' => 'Date'],
        ['data' => 'trans_code', 'title' => 'Trans Code'],
        ['data' => 'billing_month', 'title' => 'Billing Month'],
        ['data' => 'voucher_type', 'title' => 'Type'],
        ['data' => 'amount', 'title' => 'Amount'],
        ['data' => 'created_by', 'title' => 'Created By'],
        ['data' => 'updated_by', 'title' => 'Updated By'],
        ['data' => 'attach_file', 'title' => 'File'],
        ['data' => 'action', 'title' => 'Actions'],
        ['data' => 'search', 'title' => 'Search'],
        ['data' => 'control', 'title' => 'Control']
        ];
        @endphp
        @include('components.column-control-panel', [
        'tableColumns' => $tableColumns,
        'tableIdentifier' => 'vouchers_table'
        ])
        <!-- Column Control Overlay -->
        <div class="filter-overlay" id="columnControlOverlay"></div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-title">
                    <h3>Vouchers @if(isset($data)) ({{ $data->total() }} Records) @endif</h3>
                </div>
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
            </div>
            <div class="card-body  px-2 py-0 table-responsive" id="table-data">
                @include('vouchers.table', ['data' => $data ?? collect()])
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div class="text-white text-center">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2">Loading...</div>
    </div>
</div>
@endsection

@section('page-script')
<!-- Include Sortable.js for column reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script type="text/javascript">
    // Wait for jQuery to be available
    (function checkJQuery() {
        if (typeof jQuery === 'undefined') {
            console.warn('jQuery not loaded yet, waiting...');
            setTimeout(checkJQuery, 50);
            return;
        }

        $(document).ready(function() {
            console.log('Vouchers filter script loaded'); // Debug line

            // Test jQuery is working
            console.log('jQuery version:', $.fn.jquery);

            // Function to initialize Bootstrap dropdowns
            function initializeDropdowns() {
                console.log('Initializing dropdowns'); // Debug line

                // Wait for Bootstrap to be available
                var attempts = 0;
                var maxAttempts = 10;

                function tryInitialize() {
                    attempts++;

                    if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                        // Initialize Bootstrap 5 dropdowns
                        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                            try {
                                return new bootstrap.Dropdown(dropdownToggleEl);
                            } catch (e) {
                                console.warn('Failed to initialize dropdown:', e);
                                return null;
                            }
                        }).filter(Boolean);

                        console.log('Dropdowns initialized:', dropdownList.length); // Debug line
                    } else if (attempts < maxAttempts) {
                        console.log('Bootstrap not ready, retrying...', attempts);
                        setTimeout(tryInitialize, 100);
                    } else {
                        console.warn('Bootstrap dropdown initialization failed after', maxAttempts, 'attempts');
                    }
                }

                tryInitialize();
            }

            // Initialize dropdowns on page load
            initializeDropdowns();

            // Initialize Select2 for filter dropdowns if available
            if (typeof $.fn.select2 !== 'undefined') {
                $('#voucher_type').select2({
                    dropdownParent: $('#searchTopbody'),
                    placeholder: "Filter By Voucher Type",
                    allowClear: true,
                });
                $('#created_by').select2({
                    dropdownParent: $('#searchTopbody'),
                    placeholder: "Filter By Created By",
                    allowClear: true,
                });
            }

            // Sidebar open/close with event delegation
            $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
                e.preventDefault();
                console.log('Filter button clicked!'); // Debug line
                $('#filterSidebar').addClass('open');
                $('#filterOverlay').addClass('show');
                return false;
            });

            $(document).on('click', '#closeSidebar, #filterOverlay', function(e) {
                e.preventDefault();
                console.log('Close button clicked!'); // Debug line
                $('#filterSidebar').removeClass('open');
                $('#filterOverlay').removeClass('show');
                return false;
            });

            // Column control button with event delegation
            $(document).on('click', '.openColumnControlSidebar', function(e) {
                e.preventDefault();
                console.log('Column control button clicked!'); // Debug line
                $('#columnControlSidebar').addClass('open');
                $('#filterOverlay').addClass('show');
                return false;
            });

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Filter form submitted'); // Debug line

                $('#loading-overlay').show();
                $('#filterSidebar').removeClass('open');
                $('#filterOverlay').removeClass('show');
                const loaderStartTime = Date.now();
                let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
                let formData = $.param(filteredFields);

                $.ajax({
                    url: "{{ route('vouchers.index') }}",
                    type: "GET",
                    data: formData,
                    success: function(data) {
                        console.log('Filter response received:', data); // Debug line
                        $('#table-data').html(data.tableData);
                        let newUrl = "{{ route('vouchers.index') }}" + (formData ? '?' + formData : '');
                        history.pushState(null, '', newUrl);
                        if (filteredFields.length > 0) {
                            $('#clearFilterBtn').show();
                        } else {
                            $('#clearFilterBtn').hide();
                        }

                        // Reinitialize dropdowns after loading new content
                        setTimeout(function() {
                            initializeDropdowns();
                        }, 100);

                        // Reapply column control settings after table update
                        if (window.ColumnController) {
                            setTimeout(() => {
                                window.ColumnController.reapplySettings();
                                window.ColumnController.initializeDropdowns();
                            }, 100);
                        }

                        const elapsed = Date.now() - loaderStartTime;
                        const remaining = 1000 - elapsed;
                        setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                    },
                    error: function(xhr, status, error) {
                        console.error('Filter error:', xhr, status, error); // Debug line
                        const elapsed = Date.now() - loaderStartTime;
                        const remaining = 1000 - elapsed;
                        setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                    }
                });
            });

            // Quick search input (main)
            $('#quickSearch').on('keyup', function(e) {
                if (e.keyCode === 13 || $(this).val().length === 0) {
                    // Set the sidebar quick search too
                    $('#quickSearchSidebar').val($(this).val());
                    $('#filterForm').submit();
                }
            });

            // Quick search input (sidebar)
            $('#quickSearchSidebar').on('keyup', function(e) {
                if (e.keyCode === 13 || $(this).val().length === 0) {
                    // Set the main quick search too
                    $('#quickSearch').val($(this).val());
                    $('#filterForm').submit();
                }
            });
        }); // End $(document).ready

    })(); // End jQuery availability check
</script>
@endsection
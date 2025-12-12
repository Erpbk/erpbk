@extends('layouts.app')

@section('title','RTA Fines')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $account->name }} | Rta Fines</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-action="{{ route('rtaFines.create' , $account->id) }}" data-size="lg" data-title="New Fine">
                    Add New
                </a>
                <a class="btn btn-success waves-effect waves-light action-btn me-2" href="{{ route('rtaFines.import.form', $account->id) }}">
                    <i class="fas fa-upload"></i> Import Excel
                </a>
                <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter Fines</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="{{ route('rtaFines.tickets', $account->id) }}" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="ticket_no">Ticket Number</label>
                                            <input type="number" name="ticket_no" class="form-control" placeholder="Filter By Ticket Number" value="{{ request('ticket_no') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="trans_code">Transcation Code</label>
                                            <input type="text" name="trans_code" class="form-control" placeholder="Filter By Transcation Code" value="{{ request('trans_code') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="billing_month">Billing Month</label>
                                            <input type="month" name="billing_month" class="form-control" placeholder="Filter By Billing Month" value="{{ request('billing_month') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="rider_id">Filter by Rider</label>
                                            <select class="form-control " id="rider_id" name="rider_id">
                                                <option value="">Select</option>
                                                @php
                                                $riderid = DB::table('rta_fines')
                                                ->whereNotNull('rider_id')
                                                ->where('rider_id', '!=', '')
                                                ->pluck('rider_id')
                                                ->unique();
                                                $riders = DB::table('riders')
                                                ->whereIn('id', $riderid)
                                                ->select('id', 'rider_id', 'name')
                                                ->get();
                                                @endphp
                                                @foreach($riders as $rider)
                                                <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>{{ $rider->rider_id }} - {{ $rider->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="bike_id">Filter by Bike</label>
                                            <select class="form-control " id="bike_id" name="bike_id">
                                                @php
                                                $bikeid = DB::table('rta_fines')
                                                ->whereNotNull('bike_id')
                                                ->where('bike_id', '!=', '')
                                                ->pluck('bike_id')
                                                ->unique();
                                                $bikes = DB::table('bikes')
                                                ->whereIn('id', $bikeid)
                                                ->select('id', 'plate')
                                                ->get();
                                                @endphp
                                                <option value="" selected>Select</option>
                                                @foreach($bikes as $bike)
                                                <option value="{{ $bike->id }}" {{ request('bike_id') == $bike->id ? 'selected' : '' }}>{{ $bike->plate }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-12 form-group text-center">
                                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-12 mt-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-end">
                        <div class="w-100">
                            <div class="row g-3">
                                <!-- Total Tickets -->
                                <div class="col-md-4 col-sm-6">
                                    <div class="d-flex align-items-center p-3 rounded shadow-sm bg-white">
                                        <div class="badge rounded bg-danger me-3 p-3">
                                            <i class="menu-icon tf-icons ti ti-ticket text-white"></i>
                                        </div>
                                        <div class="card-info">
                                            <h4 class="mb-1 text-danger unpaid-amount fw-bold">AED {{ $totalAmount ?? 0 }}</h4>
                                            <small class="fw-semibold">🎫 Total Tickets ({{ $totaltickets ?? 0 }})</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Service Charges -->
                                <div class="col-md-4 col-sm-6">
                                    <div class="d-flex align-items-center p-3 rounded shadow-sm bg-white">
                                        <div class="badge rounded bg-warning me-3 p-3">
                                            <i class="menu-icon tf-icons ti ti-cash text-white"></i>
                                        </div>
                                        <div class="card-info">
                                            <h4 class="mb-1 text-warning unpaid-amount fw-bold">AED {{ $serviceCharges ?? 0 }}</h4>
                                            <small class="fw-semibold">⚙️ Service Charges</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin Fee -->
                                <div class="col-md-4 col-sm-6">
                                    <div class="d-flex align-items-center p-3 rounded shadow-sm bg-white">
                                        <div class="badge rounded bg-secondary me-3 p-3">
                                            <i class="menu-icon tf-icons ti ti-cash text-white"></i>
                                        </div>
                                        <div class="card-info">
                                            <h4 class="mb-1 text-secondary unpaid-amount fw-bold">AED {{ $adminFee ?? 0 }}</h4>
                                            <small class="fw-semibold">👨‍💼 Admin Fee</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Amount -->
                                <div class="col-md-4 col-sm-6">
                                    <div class="d-flex align-items-center p-3 rounded shadow-sm bg-white">
                                        <div class="badge rounded bg-primary me-3 p-3">
                                            <i class="menu-icon tf-icons ti ti-cash text-white"></i>
                                        </div>
                                        <div class="card-info">
                                            <h4 class="mb-1 text-primary unpaid-amount fw-bold">AED {{ $total_Amount ?? 0 }}</h4>
                                            <small class="fw-semibold">💰 Total Amount</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Paid Fines -->
                                <div class="col-md-4 col-sm-6">
                                    <div class="d-flex align-items-center p-3 rounded shadow-sm bg-white">
                                        <div class="badge rounded bg-success me-3 p-3">
                                            <i class="menu-icon tf-icons ti ti-check text-white"></i>
                                        </div>
                                        <div class="card-info">
                                            <h4 class="mb-1 text-success paid-amount fw-bold">AED {{ $paidAmount ?? 0 }}</h4>
                                            <small class="fw-semibold">✅ Paid Fines ({{ $paidCount ?? 0 }})</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Unpaid Fines -->
                                <div class="col-md-4 col-sm-6">
                                    <div class="d-flex align-items-center p-3 rounded shadow-sm bg-white">
                                        <div class="badge rounded bg-danger me-3 p-3">
                                            <i class="menu-icon tf-icons ti ti-alert-circle text-white"></i>
                                        </div>
                                        <div class="card-info">
                                            <h4 class="mb-1 text-danger unpaid-amount fw-bold">AED {{ $unpaidAmount ?? 0 }}</h4>
                                            <small class="fw-semibold">❌ Unpaid Fines ({{ $unpaidCount ?? 0 }})</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('rta_fines.table', ['data' => $data])
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
        $('#rider_id').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Rider",
        });
        $('#bike_id').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Bike Plate",
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();

            $('#loading-overlay').show();
            $('#searchModal').modal('hide');

            const loaderStartTime = Date.now();

            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);

            $.ajax({
                url: "{{ route('rtaFines.tickets', $account->id) }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);

                    // Update the totals
                    $('.paid-amount').text(data.totals.paidAmount);
                    $('.unpaid-amount').text(data.totals.unpaidAmount);
                    $('.paid-count').text(data.totals.paidCount);
                    $('.unpaid-count').text(data.totals.unpaidCount);

                    // Update the URL
                    let newUrl = "{{ route('rtaFines.tickets', $account->id) }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);

                    // Minimum 1s loader
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('#dataTableBuilder');
        const headers = table.querySelectorAll('th.sorting');
        const tbody = table.querySelector('tbody');

        headers.forEach((header, colIndex) => {
            header.addEventListener('click', () => {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = header.classList.contains('sorted-asc');

                // Clear previous sort classes
                headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

                // Add new sort direction
                header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

                // Sort logic
                rows.sort((a, b) => {
                    let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
                    let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

                    const aVal = isNaN(aText) ? aText : parseFloat(aText);
                    const bVal = isNaN(bText) ? bText : parseFloat(bText);

                    if (aVal < bVal) return isAsc ? 1 : -1;
                    if (aVal > bVal) return isAsc ? -1 : 1;
                    return 0;
                });

                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
</script>
@endsection
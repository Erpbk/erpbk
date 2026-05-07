@extends('layouts.app')

@section('title','Visa Expenses')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Visa Expense Accounts</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createaccount">
                <i class="fa fa-plus me-1"></i> Create Expense Account
            </button>
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')
    <div class="card mb-3">
        <div class="card-body">
            <form id="filterForm" action="{{ route('VisaExpense.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Quick Search</label>
                    <input type="text" name="quick_search" class="form-control" placeholder="Rider ID, name, person code" value="{{ request('quick_search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Account name" value="{{ request('name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Status</label>
                    <select class="form-select" name="payment_status" id="payment_status">
                        <option value="">All</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search me-1"></i> Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label">Total Accounts</div>
                <div class="value">{{ $data->total() }}</div>
            </div>
            <div class="total-card total-black">
                <div class="label">Unpaid Entries</div>
                <div class="value">{{ $stats['unpaid_accounts'] }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label">Paid Amount</div>
                <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $stats['paid_amount'], 2) }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label">Unpaid Amount</div>
                <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $stats['unpaid_amount'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('visa_expenses.account_table', ['data' => $data])
        </div>
    </div>
</div>

<div class="modal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Visa Expense Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('VisaExpense.accountcreate') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="rider_id" class="form-label">Select Rider</label>
                            <select class="form-select" id="rider_id" name="rider_id" required>
                                <option value="">Select</option>
                                @foreach($riders as $r)
                                <option value="{{ $r->id }}">{{ $r->rider_id }} - {{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                    </div>
                </form>
            </div>
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
$(document).ready(function () {
    $('#rider_id').select2({
        dropdownParent: $('#createaccount'),
        placeholder: "Rider",
            allowClear: true
    });
    $('#payment_status').select2({
        dropdownParent: $('#filterForm'),
        placeholder: "Filter By Payment Status",
            allowClear: true
    });
});

$(document).on('click', '.js-delete-expense-account', function () {
    var url = $(this).data('delete-url');
    if (url) {
        confirmDelete(url);
    }
});
</script>
@endsection



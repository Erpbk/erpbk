@extends('loans.viewindex')
@section('page_content')

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Loans</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('loans.index') }}" method="GET">
            <div class="row">
                @if(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-12">
                    <label for="branch_id">Branch</label>
                    <select class="form-control" id="branch_id" name="branch_id">
                        @foreach(auth()->user()->branchDropdown() as $bid => $bname)
                        <option value="{{ $bid }}" {{ request('branch_id') == $bid ? 'selected' : '' }}>{{ $bname }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group col-md-12">
                    <label for="loan_number">Loan Number</label>
                    <input type="text" name="loan_number" class="form-control" value="{{ request('loan_number') }}" placeholder="LN-2026-0001">
                </div>
                <div class="form-group col-md-12">
                    <label for="bank_id">Lender Bank</label>
                    <select class="form-control" id="bank_id" name="bank_id">
                        <option value="">All</option>
                        @foreach(\App\Models\Banks::orderBy('name')->pluck('name', 'id') as $bid => $bname)
                        <option value="{{ $bid }}" {{ request('bank_id') == $bid ? 'selected' : '' }}>{{ $bname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                        <option value="defaulted" {{ request('status') == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="maturity_month">Maturity Month</label>
                    <input type="month" name="maturity_month" class="form-control" value="{{ request('maturity_month') }}">
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="filterOverlay" class="filter-overlay"></div>

<div class="content py-1">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="row mb-3">
        <div class="col-md-4 col-lg">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Outstanding Principal</small>
                    <h4 class="mb-0">{{ number_format($summary['total_outstanding'] ?? 0, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Paid Principal</small>
                    <h4 class="mb-0">{{ number_format($summary['paid_principal'] ?? 0, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Paid Interest</small>
                    <h4 class="mb-0">{{ number_format($summary['paid_interest'] ?? 0, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Active Loans</small>
                    <h4 class="mb-0">{{ $summary['active_count'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Overdue Installments</small>
                    <h4 class="mb-0 text-danger">{{ $summary['overdue_count'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <a href="{{ route('loans.upcomingInstallments') }}" class="btn btn-outline-secondary btn-sm me-1">Upcoming EMIs</a>
                <a href="{{ route('loans.interestSummary') }}" class="btn btn-outline-secondary btn-sm">Interest Summary</a>
            </div>
            <button class="btn btn-primary openFilterSidebar"><i class="fa fa-search"></i> Filter</button>
        </div>
        <div class="card-body table-responsive py-0" id="table-data">
            @include('loans.table', ['data' => $data])
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDisburse(url) {
    Swal.fire({
        title: 'Disburse this loan?',
        text: 'This will create the COA account and post GL entries.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, disburse'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $('#loading-overlay').show();
        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                $('#loading-overlay').hide();
                Swal.fire({ icon: 'success', title: 'Disbursed', text: response.message || 'Loan disbursed successfully.' }).then(() => location.reload());
            },
            error: function(xhr) {
                $('#loading-overlay').hide();
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Disbursement failed.' });
            }
        });
    });
}

function confirmDelete(url) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will move the loan to the Recycle Bin.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $('#loading-overlay').show();
        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                $('#loading-overlay').hide();
                Swal.fire({ icon: 'success', title: 'Deleted', text: response.message }).then(() => location.reload());
            },
            error: function(xhr) {
                $('#loading-overlay').hide();
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Delete failed.' });
            }
        });
    });
}

$(document).ready(function() {
    $('#bank_id, #status, #branch_id').select2({ dropdownParent: $('#searchTopbody'), allowClear: true });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        $('#loading-overlay').show();
        let formData = $(this).serializeArray().filter(f => f.name !== '_token' && f.value.trim() !== '');
        $.get("{{ route('loans.index') }}", $.param(formData), function(data) {
            $('#table-data').html(data.tableData);
            history.pushState(null, '', "{{ route('loans.index') }}" + (formData.length ? '?' + $.param(formData) : ''));
            $('#loading-overlay').hide();
        });
    });

    $('.openFilterSidebar').on('click', function() {
        $('#filterSidebar, #filterOverlay').addClass('active');
    });
    $('#closeSidebar, #filterOverlay').on('click', function() {
        $('#filterSidebar, #filterOverlay').removeClass('active');
    });

    $('#addLoanDropdownBtn').on('click', function(e) {
        e.stopPropagation();
        $('#addLoanDropdown').toggleClass('show');
        $(this).toggleClass('open');
    });
});
</script>
@endsection

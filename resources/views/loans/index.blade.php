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
                    <label for="bank_name">Lender Bank</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control" value="{{ request('bank_name') }}" placeholder="Bank name">
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

<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div></div>
            <button class="btn btn-primary openFilterSidebar"><i class="fa fa-search"></i> Filter Loans</button>
        </div>
        <div class="totals-cards totals-cards-single-row">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-university"></i>Active Loans</div>
                <div class="value">{{ $summary['active_count'] ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="far fa-money-bill-alt"></i>Outstanding Principal</div>
                <div class="value">{{ \App\Helpers\Currency::format($summary['total_outstanding'] ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fas fa-check-circle"></i>Paid Principal</div>
                <div class="value">{{ \App\Helpers\Currency::format($summary['paid_principal'] ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-percent"></i>Paid Interest</div>
                <div class="value">{{ \App\Helpers\Currency::format($summary['paid_interest'] ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-exclamation-circle"></i>Overdue Installments</div>
                <div class="value">{{ $summary['overdue_count'] ?? 0 }}</div>
            </div>
            <div class="total-card total-4">
                <div class="label"><i class="fa fa-file-alt"></i>Draft Loans</div>
                <div class="value">{{ $summary['draft_count'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
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
        text: 'This will move the loan (and its installments) to the Recycle Bin, or submit a delete request if approval is required.',
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
                Swal.fire({
                    icon: 'success',
                    title: response.pending_approval ? 'Request Submitted' : 'Deleted',
                    text: response.message
                }).then(() => location.reload());
            },
            error: function(xhr) {
                $('#loading-overlay').hide();
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Delete failed.' });
            }
        });
    });
}

$(document).ready(function() {
    $('#status, #branch_id').select2({ dropdownParent: $('#searchTopbody'), allowClear: true });

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

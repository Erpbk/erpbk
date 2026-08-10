@extends('loans.viewindex')
@section('page_content')
<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Interest Paid Summary</h5>
            <form method="GET" class="d-flex align-items-center gap-2 flex-nowrap">
                <input type="date" name="from_date" value="{{ $from }}" class="form-control" style="width: auto; min-width: 10rem;">
                <input type="date" name="to_date" value="{{ $to }}" class="form-control" style="width: auto; min-width: 10rem;">
                <button type="submit" class="btn btn-primary flex-shrink-0 text-nowrap">Apply</button>
            </form>
        </div>
        <div class="card-body">
            @forelse($rows as $bankName => $installments)
            <h6 class="mt-3">{{ $bankName }}</h6>
            <table class="table table-sm table-striped">
                <thead><tr><th>Loan</th><th>Installment</th><th>Paid Date</th><th>Billing Month</th><th>Interest</th></tr></thead>
                <tbody>
                    @foreach($installments as $inst)
                    <tr>
                        <td>{{ $inst->loan?->loan_number }}</td>
                        <td>#{{ $inst->installment_no }}</td>
                        <td>{{ $inst->paid_date?->format('d M Y') }}</td>
                        <td>{{ $inst->paid_date?->format('M Y') }}</td>
                        <td>{{ number_format($inst->interest_amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">Subtotal</td>
                        <td>{{ number_format($installments->sum('interest_amount'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
            @empty
            <p class="text-muted mb-0">No interest payments in this period.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    $('#addLoanDropdownBtn').on('click', function(e) {
        e.stopPropagation();
        $('#addLoanDropdown').toggleClass('show');
        $(this).toggleClass('open');
    });
});
</script>
@endsection

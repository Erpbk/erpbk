@extends('loans.viewindex')
@section('page_content')
<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Upcoming Installments ({{ $days }} days)</h5>
            <div>
                <a href="{{ route('loans.upcomingInstallments', ['days' => 7]) }}" class="btn btn-sm {{ $days == 7 ? 'btn-primary' : 'btn-outline-primary' }}">7 days</a>
                <a href="{{ route('loans.upcomingInstallments', ['days' => 30]) }}" class="btn btn-sm {{ $days == 30 ? 'btn-primary' : 'btn-outline-primary' }}">30 days</a>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0">
            @include('loans.upcoming_table', ['data' => $data])
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

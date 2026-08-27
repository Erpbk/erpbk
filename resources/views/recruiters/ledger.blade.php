@extends('recruiters.view')

@section('page_content')
<div class="card mb-1">
    <div class="card-header">
        <h5 class="mb-2">
            <i class="ti ti-file-stack ti-lg text-body me-2"></i>
            Account Ledger
        </h5>
        <div class="d-flex justify-content-end align-items-center w-100">
            <form action="" method="get" class="d-flex align-items-center flex-wrap gap-3 justify-content-end w-100">
                <div class="d-flex align-items-center gap-2">
                    <label class="fw-semibold mb-0">From</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control" style="width: 160px; height:38px;">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="fw-semibold mb-0">To</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control" style="width: 160px; height:38px;">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="fw-semibold mb-0">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control" style="width: 160px; height:38px;">
                </div>
                <button type="submit" class="btn btn-primary" style="height:38px;">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body pt-0 px-2">
        @push('third_party_stylesheets')
            @include('layouts.datatables_css')
        @endpush
        <div class="card-body px-0">
            {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']) !!}
        </div>
        @push('third_party_scripts')
            @include('layouts.datatables_js')
            {!! $dataTable->scripts() !!}
        @endpush
    </div>
</div>
@endsection

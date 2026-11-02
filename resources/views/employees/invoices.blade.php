@extends('employees.view')

@section('page-content')

@push('third_party_stylesheets')
@include('layouts.datatables_css')
<style>
  .employee-invoices-table-wrap {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .employee-invoices-table-wrap .dataTables_wrapper {
    width: 100%;
  }

  .employee-invoices-table-wrap table.dataTable {
    width: 100% !important;
    margin: 0 !important;
  }

  .employee-invoices-table-wrap table.dataTable th,
  .employee-invoices-table-wrap table.dataTable td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .employee-invoices-table-wrap table.dataTable td.col-descriptions {
    white-space: normal;
    min-width: 180px;
    max-width: 260px;
    word-break: break-word;
  }

  .employee-invoices-table-wrap .dataTables_scrollHead,
  .employee-invoices-table-wrap .dataTables_scrollBody {
    width: 100% !important;
  }
</style>
@endpush

<div class="card card-action mb-1">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-file-invoice ti-lg text-body me-2"></i>Invoices</h5>
    <form action="" method="get">
      <input type="month" name="month" value="{{ request('month') }}" class="form-control" onchange="form.submit();"/>
    </form>
  </div>
  <div class="card-body pt-0 px-2">
    <div class="employee-invoices-table-wrap table-responsive">
      {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm dataTable nowrap w-100']) !!}
    </div>
  </div>
</div>

@push('third_party_scripts')
@include('layouts.datatables_js')
{!! $dataTable->scripts() !!}
@endpush

@endsection

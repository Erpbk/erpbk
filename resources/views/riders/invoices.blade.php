@extends('riders.view')

@section('page_content')

@push('third_party_stylesheets')
@include('layouts.datatables_css')
<style>
  .rider-invoices-table-wrap {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .rider-invoices-table-wrap .dataTables_wrapper {
    width: 100%;
  }

  .rider-invoices-table-wrap table.dataTable {
    width: 100% !important;
    margin: 0 !important;
  }

  .rider-invoices-table-wrap table.dataTable th,
  .rider-invoices-table-wrap table.dataTable td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .rider-invoices-table-wrap table.dataTable td.col-descriptions {
    white-space: normal;
    min-width: 180px;
    max-width: 260px;
    word-break: break-word;
  }

  .rider-invoices-table-wrap table.dataTable td.col-rider {
    white-space: normal;
    min-width: 140px;
    max-width: 200px;
    word-break: break-word;
  }

  .rider-invoices-table-wrap .dataTables_scrollHead,
  .rider-invoices-table-wrap .dataTables_scrollBody {
    width: 100% !important;
  }

  .invoice-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    line-height: 1.35;
    border: 1px solid transparent;
    animation: invoice-status-blink 1.25s ease-in-out infinite;
  }

  .invoice-status-paid {
    color: #166534;
    background: #dcfce7;
    border-color: #86efac;
  }

  .invoice-status-unpaid {
    color: #991b1b;
    background: #fee2e2;
    border-color: #fca5a5;
    animation-name: invoice-status-blink-red;
  }

  @keyframes invoice-status-blink {
    0%, 100% {
      opacity: 1;
      box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
    }
    50% {
      opacity: 0.55;
      box-shadow: 0 0 0 4px rgba(34, 197, 94, 0);
    }
  }

  @keyframes invoice-status-blink-red {
    0%, 100% {
      opacity: 1;
      box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
    }
    50% {
      opacity: 0.55;
      box-shadow: 0 0 0 4px rgba(239, 68, 68, 0);
    }
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
    <div class="rider-invoices-table-wrap table-responsive">
      {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm dataTable nowrap w-100']) !!}
    </div>
  </div>
</div>

@push('third_party_scripts')
@include('layouts.datatables_js')
{!! $dataTable->scripts() !!}
@endpush

@endsection

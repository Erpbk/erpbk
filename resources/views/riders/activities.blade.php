@extends('riders.view')
@section('page_content')
<div class="card card-action mb-0">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-calendar-check ti-lg text-body me-2"></i>Activities</h5>
    <form action="" method="get">
      <input type="month" name="month" value="{{request('month')??date('Y-m')}}" class="form-control" onchange="form.submit();" />
    </form>
  </div>
  <div class="card-body pt-0 px-2">
    @push('third_party_stylesheets')
    @include('layouts.datatables_css')
    @endpush
    <div class="card-body px-0 pt-0">
      {{-- {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable','footer' => true]) !!}--}}
      <table id="dataTableBuilder" class="table table-striped dataTable" width="100%">
        <thead>
          <tr>
            <th>Date</th>
            <th>ID</th>
            <th>Name</th>
            <th>Payout</th>
            <th>Delivered</th>
            <th>Ontime%</th>
            <th>Rejected</th>
            <th>HR</th>
            <th>Rating</th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <th>Date</th>
            <th>ID</th>
            <th>Name</th>
            <th>Payout</th>
            <th>Delivered</th>
            <th>Ontime%</th>
            <th>Rejected</th>
            <th>HR</th>
            <th>Rating</th>
          </tr>
        </tfoot>
      </table>

      {!! $dataTable->scripts() !!}

    </div>

    @push('third_party_scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    @endpush
  </div>
</div>

@endsection
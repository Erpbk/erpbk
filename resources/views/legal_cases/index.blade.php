@extends('riders.view')
@section('title','Legal Cases')
@section('page_content')
@php
$pendingCount = company_table('legal_cases')->where('legal_case_account_id', $account->id)->where('step_status', 'pending')->count();
$completedCount = company_table('legal_cases')->where('legal_case_account_id', $account->id)->where('step_status', 'completed')->count();
@endphp

<div class="content">
  @include('flash::message')
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Legal Case - {{ $account->name }}</h3>
      @can('legalcase_create')
      <a class="btn btn-primary action-btn show-modal"
        href="javascript:void(0);" data-action="{{ route('LegalCase.create' , $account->id) }}" data-size="lg" data-title="New Legal Case Entry">
        Add New Case
      </a>
      @endcan
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Pending Steps</div>
        <div class="value">{{ $pendingCount }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Completed Steps</div>
        <div class="value">{{ $completedCount }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('legal_cases.table', ['data' => $data])
    </div>
  </div>
</div>
@endsection
@section('page-script')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#step_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Step Status",
      allowClear: true
    });
    $('#case_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Case Status",
      allowClear: true
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
        url: $(this).attr('action'),
        type: 'GET',
        data: formData,
        success: function(response) {
          const elapsed = Date.now() - loaderStartTime;
          const remaining = Math.max(0, 300 - elapsed);
          setTimeout(function() {
            $('#table-data').html(response.tableData);
            $('#loading-overlay').hide();
          }, remaining);
        },
        error: function() {
          $('#loading-overlay').hide();
        }
      });
    });
  });
</script>
@endsection

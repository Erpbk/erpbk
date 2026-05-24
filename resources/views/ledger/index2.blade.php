@extends('layouts.app')
@section('title', 'Ledger')
@section('content')

<div class="container-fluid card mb-1">

  <div class="card-header">

    {{-- Title --}}
    <h5 class="mb-2">
        <i class="ti ti-file-stack ti-lg text-body me-2"></i>
        Account Ledger
    </h5>

    <div class="d-flex justify-content-end align-items-center w-100">

        {{-- Filters --}}
        <form action="" method="get"
              class="d-flex align-items-center flex-wrap gap-3 justify-content-end w-100">

            {{-- From Date --}}
            <div class="d-flex align-items-center gap-2">
                <label class="fw-semibold mb-0">From</label>
                <input type="date" name="from_date"
                       value="{{ request('from_date') }}"
                       class="form-control"
                       style="width: 160px; height:38px;">
            </div>

            {{-- To Date --}}
            <div class="d-flex align-items-center gap-2">
                <label class="fw-semibold mb-0">To</label>
                <input type="date" name="to_date"
                       value="{{ request('to_date') }}"
                       class="form-control"
                       style="width: 160px; height:38px;">
            </div>

            {{-- Month --}}
            <div class="d-flex align-items-center gap-2">
                <label class="fw-semibold mb-0">Month</label>
                <input type="month" name="month"
                       value="{{ request('month') }}"
                       class="form-control"
                       style="width: 160px; height:38px;">
            </div>
            {{-- Account --}}
                <div class="d-flex align-items-center gap-2">
                    <label class="fw-semibold mb-0" style="min-width: 70px;">
                        Account
                    </label>

                    <select name="account"
                            class="form-control select-2"
                            style="width: 220px; height:38px;">

                        <option value="">Select Account</option>

                        @foreach(\App\Models\Accounts::all() as $account)
                            <option value="{{ $account->id }}"
                                {{ request('account') == $account->id ? 'selected' : '' }}>
                                {{ $account->account_code . '-' . $account->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

            {{-- Submit --}}
            <button type="submit"
                    class="btn btn-primary"
                    style="height:38px;">
                Filter
            </button>

        </form>

    </div>
</div>
  
  <div class="card-body pt-0 px-0">
    <div class="table-responsive" style="max-height: 800px; overflow: auto;">
      @push('third_party_stylesheets')
        @include('layouts.datatables_css')
      @endpush

      @if(isset($data) && $data === false)
        <div class="alert alert-warning m-3">
          Please select an account to view the ledger.
        </div>
        
      @else
      
      {!! $dataTable->table([
          'width' => '100%', 
          'class' => 'table table-striped datatable',
      ]) !!}
      
      @push('third_party_scripts')
        @include('layouts.datatables_js')
        <script>
            $(document).ready(function() {
                $('.select-2').select2({
                    width: '300px',
                });
            });
        </script>
        {!! $dataTable->scripts() !!}
      @endpush

      @endif
    </div>
  </div>
</div>
@endsection

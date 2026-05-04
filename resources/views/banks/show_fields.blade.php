@php
  $bankFieldKeys = \App\Support\BankFormLayout::userFacingFieldKeys();
@endphp
@foreach($bankFieldKeys as $col)
  <div class="col-sm-12">
    <strong>{{ \App\Support\BankFormLayout::labelForFieldKey($col) }}:</strong>
    <p>
      @if($col === 'status')
        @if($banks->status == 1)
          <span class="badge bg-success">{{ __('Active') }}</span>
        @else
          <span class="badge bg-danger">{{ __('Inactive') }}</span>
        @endif
      @elseif($col === 'branch_id')
        {{ $banks->branch_name ?? $banks->branch_id }}
      @else
        {{ data_get($banks, $col) }}
      @endif
    </p>
  </div>
@endforeach

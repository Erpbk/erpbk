@php
  $bankFieldKeys = array_values(array_filter(
      \App\Support\BankFormLayout::userFacingFieldKeys(),
      static fn ($col) => field_visible('bank', (string) $col)
  ));
@endphp
@foreach($bankFieldKeys as $col)
  @php
    if ($col === 'status') {
      $value = ((int) $banks->status === 1) ? 'Active' : 'Inactive';
    } elseif ($col === 'branch_id') {
      $value = $banks->branch_name ?? $banks->branch_id;
    } else {
      $value = data_get($banks, $col);
    }
  @endphp
  <x-entity-info-field
    :label="\App\Support\BankFormLayout::labelForFieldKey($col)"
    :value="$value"
    :expiry="\App\Support\EntityExpiry::isExpiryKey((string) $col)"
  />
@endforeach

@php
  $spec = [];
  if ($item->kind === 'fixed') {
    $value = $rider->{$item->field_key} ?? null;
    $spec = $item->spec ?? [];
  } else {
    $cfValues = is_array($rider->custom_field_values ?? null) ? $rider->custom_field_values : [];
    $value = $cfValues[$item->field->id] ?? $item->field->default_value ?? null;
  }
  $displayValue = $value;
  if ($value !== null && $value !== '') {
    if ($item->kind === 'fixed' && in_array($spec['type'] ?? '', ['date', 'datetime'], true)) {
      try {
        $displayValue = \App\Helpers\General::DateFormat($value);
      } catch (\Throwable $e) {
        $displayValue = $value;
      }
    } elseif ($item->kind === 'custom') {
      if (in_array($item->field->data_type ?? '', ['date', 'datetime'], true)) {
        try {
          $displayValue = \App\Helpers\General::DateFormat($value);
        } catch (\Throwable $e) {
          $displayValue = $value;
        }
      } elseif (($item->field->data_type ?? '') === 'checkbox') {
        $displayValue = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
      }
    } elseif ($item->kind === 'fixed' && ($spec['type'] ?? '') === 'checkbox') {
      $displayValue = ($value == 1 || $value === true) ? 'Yes' : 'No';
    }
  } else {
    $displayValue = '—';
  }
  if ($item->kind === 'fixed' && ($spec['type'] ?? '') === 'select' && $value !== null && $value !== '') {
    $dropdown = $spec['dropdown'] ?? '';
    if ($dropdown === 'countries' && $rider->country) {
      $displayValue = $rider->country->name;
    } elseif ($dropdown === 'vendors' && $rider->vendor) {
      $displayValue = $rider->vendor->name;
    } elseif ($dropdown === 'recruiters' && $rider->recruiter) {
      $displayValue = $rider->recruiter->name;
    }
  }
@endphp
<div class="col-md-3 form-group col-3">
  <label>{{ $item->kind === 'fixed' ? $item->label : $item->field->label }}</label>
  <p>{{ $displayValue }}</p>
</div>

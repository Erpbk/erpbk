@php
  $val = $val ?? '';
  $fname = $fname ?? '';
  $field = $field ?? null;
  if (!$field) {
    return;
  }

  $ui = \App\Support\RiderDocumentFieldValues::presentationForDocumentField($field);
  $type = $ui['type'] ?? 'text';
  $label = $ui['label'] ?? $field->label;
  $required = (bool) ($ui['required'] ?? $field->is_required);
  $selectOptions = $ui['select_options'] ?? [];
  $useSelect2 = (bool) ($ui['use_select2'] ?? false);
  $maxlength = $ui['maxlength'] ?? null;
  $placeholder = $ui['placeholder'] ?? null;
  $rows = $ui['rows'] ?? 2;

  // Normalize date values for HTML date inputs.
  $inputValue = $val;
  if ($type === 'date' && $inputValue !== '' && $inputValue !== null) {
    try {
      $inputValue = \Carbon\Carbon::parse((string) $inputValue)->format('Y-m-d');
    } catch (\Throwable) {
      // keep raw
    }
  }
@endphp
<div class="col-md-6">
  <label class="form-label{{ $required ? ' required' : '' }}">{{ $label }}</label>

  @if($type === 'textarea')
    <textarea
      name="{{ $fname }}"
      class="form-control"
      rows="{{ (int) $rows }}"
      @if($placeholder) placeholder="{{ $placeholder }}" @endif
      @if($required) required @endif>{{ $val }}</textarea>

  @elseif($type === 'select')
    <select
      name="{{ $fname }}"
      class="form-select{{ $useSelect2 ? ' select2' : '' }}"
      @if($useSelect2) data-placeholder="Select {{ $label }}" @endif
      @if($required) required @endif>
      <option value="">— Select —</option>
      @foreach($selectOptions as $ov => $ol)
        @if((string) $ov === '')
          @continue
        @endif
        <option value="{{ $ov }}"
          @selected(
            (string) $val === (string) $ov
            || strcasecmp((string) $val, (string) $ol) === 0
          )>
          {{ $ol }}
        </option>
      @endforeach
    </select>

  @elseif($type === 'checkbox')
    <div class="form-check mt-2">
      <input type="hidden" name="{{ $fname }}" value="0">
      <input
        type="checkbox"
        name="{{ $fname }}"
        value="1"
        class="form-check-input"
        id="doc_field_{{ $field->id }}"
        @checked(filter_var($val, FILTER_VALIDATE_BOOLEAN) || (string) $val === '1')>
      <label class="form-check-label" for="doc_field_{{ $field->id }}">Yes</label>
    </div>

  @else
    <input
      type="{{ in_array($type, ['number', 'date', 'email', 'tel', 'url'], true) ? $type : 'text' }}"
      name="{{ $fname }}"
      class="form-control"
      value="{{ $inputValue }}"
      @if($maxlength) maxlength="{{ $maxlength }}" @endif
      @if($placeholder) placeholder="{{ $placeholder }}" @endif
      @if($required) required @endif>
  @endif
</div>

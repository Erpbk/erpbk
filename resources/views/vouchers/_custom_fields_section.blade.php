@php
  $voucherCustomFields = $voucherCustomFields ?? \App\Models\VoucherCustomField::orderBy('display_order')->get();
  $voucherForValues = $voucher ?? $vouchers ?? null;
  $customValues = $voucherForValues ? ($voucherForValues->custom_field_values ?? []) : [];
@endphp
@if($voucherCustomFields->isNotEmpty())
<div class="row mt-3 border-top pt-3">
  <div class="col-12">
    <h6 class="text-muted mb-2">Custom Fields</h6>
  </div>
  @foreach($voucherCustomFields as $field)
  @php
    $value = $customValues[$field->id] ?? $field->default_value ?? '';
    $config = $field->config ?? [];
    $placeholder = $config['placeholder'] ?? '';
    $maxLength = $config['max_length'] ?? ($field->data_type === 'text' ? 255 : 1000);
    $options = isset($config['options']) ? (is_array($config['options']) ? $config['options'] : array_filter(array_map('trim', explode("\n", $config['options'] ?? '')))) : [];
  @endphp
  <div class="form-group col-md-{{ $field->data_type === 'textarea' ? '12' : '6' }} mb-2">
    <label for="voucher_custom_field_{{ $field->id }}">
      {{ $field->label }}
      @if($field->is_mandatory)<span class="text-danger">*</span>@endif
    </label>
    @if($field->help_text)
      <p class="form-text small text-muted mb-1">{{ $field->help_text }}</p>
    @endif
    @if($field->data_type === 'text')
      <input type="text" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, $value) }}" placeholder="{{ $placeholder }}" maxlength="{{ $maxLength }}" @if($field->is_mandatory) required @endif>
    @elseif($field->data_type === 'textarea')
      <textarea name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" rows="{{ $config['rows'] ?? 4 }}" maxlength="{{ $maxLength }}" @if($field->is_mandatory) required @endif>{{ old('voucher_custom_fields.'.$field->id, $value) }}</textarea>
    @elseif($field->data_type === 'number')
      <input type="number" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, $value) }}" step="{{ $config['step'] ?? 1 }}" @if(isset($config['min'])) min="{{ $config['min'] }}" @endif @if(isset($config['max'])) max="{{ $config['max'] }}" @endif @if($field->is_mandatory) required @endif>
    @elseif($field->data_type === 'decimal')
      <input type="number" step="{{ isset($config['decimals']) ? '0.'.str_repeat('0', $config['decimals']).'1' : '0.01' }}" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, $value) }}" @if(isset($config['min'])) min="{{ $config['min'] }}" @endif @if(isset($config['max'])) max="{{ $config['max'] }}" @endif @if($field->is_mandatory) required @endif>
    @elseif($field->data_type === 'date')
      <input type="date" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, is_scalar($value) ? $value : '') }}" @if($field->is_mandatory) required @endif>
    @elseif($field->data_type === 'datetime')
      <input type="datetime-local" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, is_scalar($value) ? $value : '') }}" @if($field->is_mandatory) required @endif>
    @elseif($field->data_type === 'dropdown')
      <select name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm form-select" @if($field->is_mandatory) required @endif>
        <option value="">-- Select --</option>
        @foreach($options as $opt)
          <option value="{{ $opt }}" {{ old('voucher_custom_fields.'.$field->id, $value) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
      </select>
    @elseif($field->data_type === 'checkbox')
      <div class="form-check">
        <input type="hidden" name="voucher_custom_fields[{{ $field->id }}]" value="0">
        <input type="checkbox" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-check-input" value="1" {{ old('voucher_custom_fields.'.$field->id, $value) == '1' || $value === true ? 'checked' : '' }}>
        <label class="form-check-label" for="voucher_custom_field_{{ $field->id }}">Yes</label>
      </div>
    @elseif($field->data_type === 'email')
      <input type="email" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, $value) }}" placeholder="{{ $placeholder }}" @if($field->is_mandatory) required @endif>
    @elseif($field->data_type === 'url')
      <input type="url" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, $value) }}" placeholder="{{ $placeholder }}" @if($field->is_mandatory) required @endif>
    @else
      <input type="text" name="voucher_custom_fields[{{ $field->id }}]" id="voucher_custom_field_{{ $field->id }}" class="form-control form-control-sm" value="{{ old('voucher_custom_fields.'.$field->id, $value) }}" @if($field->is_mandatory) required @endif>
    @endif
  </div>
  @endforeach
</div>
@endif

@php
$isEdit = isset($riders);
$value = null;
if ($item->kind === 'fixed') {
$name = $item->field_key;
$value = $isEdit ? $riders->{$item->field_key} : old($item->field_key);
} else {
$name = 'custom_field_values[' . $item->field->id . ']';
if ($isEdit && is_array($riders->custom_field_values ?? null)) {
$value = $riders->custom_field_values[$item->field->id] ?? $item->field->default_value ?? null;
} else {
$value = old('custom_field_values.' . $item->field->id) ?? $item->field->default_value ?? null;
}
}
$rfpEntity = 'rider';
$rfpField = $item->kind === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
$rfpVisible = field_visible($rfpEntity, (string) $rfpField);
$rfpEditable = field_editable($rfpEntity, (string) $rfpField);
$rfpSelectLock = $rfpEditable ? [] : ['disabled' => true];
@endphp
@if ($rfpVisible)
<div class="form-group col-sm-4">
  @if ($item->kind === 'fixed')
  @php
  $spec = $item->spec;
  $req = !empty($spec['required']);
  $isReadonly = !empty($spec['readonly'])
  || \App\Support\SimAssigneeContactSync::isManagedFixedFieldKey($item->field_key ?? null);
  $readonlyAttrs = ($isReadonly || !$rfpEditable) ? ['readonly' => 'readonly'] : [];
  @endphp
  @if (($spec['type'] ?? 'text') === 'select')
  {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required fw-bold'] : []) !!}
  @php
  $opts = [];
  // Prefer user-configured options (stored in Rider Settings) when available.
  $rawOptions = $spec['options'] ?? null;
  $parsedOptions = [];
  if ($rawOptions !== null && $rawOptions !== '') {
  $lines = is_array($rawOptions) ? $rawOptions : preg_split('/\r\n|\r|\n/', (string) $rawOptions);
  foreach ($lines as $line) {
  $line = trim((string) $line);
  if ($line !== '') {
  $parsedOptions[] = $line;
  }
  }
  }

  if (!empty($parsedOptions)) {
  foreach ($parsedOptions as $opt) {
  // Store both key and label as the same value for now.
  $opts[$opt] = $opt;
  }
  } elseif (($spec['dropdown'] ?? '') === 'countries') {
  $opts = \App\Models\Countries::list()->toArray();
  } elseif (($spec['dropdown'] ?? '') === 'vendors') {
  $opts = \App\Models\Vendors::dropdown();
  } elseif (($spec['dropdown'] ?? '') === 'recruiters') {
  $opts = ['' => 'Select Recruiter'];
  foreach (company_table('recruiters')->where('status', 1)->get() as $r) {
  $opts[$r->id] = $r->name;
  }
  } elseif (($spec['dropdown'] ?? '') === 'accounts') {
  $opts = \App\Models\Accounts::dropdown(null) ?? ['' => 'Select'];
  } elseif (($spec['dropdown'] ?? '') === 'customers') {
  $opts = \App\Models\Customers::pluck('name', 'id')->prepend('Select', '')->toArray();
  } elseif (($spec['dropdown'] ?? '') === 'branch') {
  $opts = ['' => 'Select'];
  foreach (\App\Models\Branch::active()->orderBy('name')->get(['id', 'name', 'code']) as $__branch) {
  $opts[$__branch->id] = trim($__branch->name . ($__branch->code ? ' (' . $__branch->code . ')' : ''));
  }
  } else {
  $opts = Common::Dropdowns($spec['dropdown'] ?? '');
  }
  $hasEmptyOption = array_key_exists('', $opts);
  $selectAttributes = ['class' => 'form-select', 'id' => $item->field_key === 'rider_id' ? 'rider_id_field' : null];
  if (!$hasEmptyOption) {
  $selectAttributes['placeholder'] = 'Select ' . $item->label;
  }
  if ($req) {
  $selectAttributes['required'] = true;
  }
  $selectAttributes = $selectAttributes + $rfpSelectLock;
  @endphp
  {!! Form::select($item->field_key, $opts, $value, $selectAttributes) !!}
  @if ($item->field_key === 'rider_id')
  <div class="invalid-feedback" id="rider_id_error" style="display: none;"></div>
  @endif
  @elseif (($spec['type'] ?? '') === 'textarea')
  {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required fw-bold'] : []) !!}
  {!! Form::textarea($item->field_key, $value, ['class' => 'form-control', 'rows' => $spec['rows'] ?? 3] + ($req ? ['required' => true] : []) + $readonlyAttrs) !!}
  @elseif (($spec['type'] ?? '') === 'checkbox')
  <div class="form-check mt-4">
    <input type="hidden" name="{{ $item->field_key }}" value="{{ in_array($item->field_key, ['vat'], true) ? '2' : '0' }}" />
    {!! Form::checkbox($item->field_key, $spec['value'] ?? 1, $value == 1 || $value === true, ['class' => 'form-check-input', 'id' => 'field_' . $item->field_key]) !!}
    {!! Form::label('field_' . $item->field_key, $item->label, ['class' => 'form-check-label fw-bold pt-0' . ($req ? ' required' : '')]) !!}
  </div>
  @else
  {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required fw-bold'] : []) !!}
  @if ($item->field_key === 'rider_id')
  {!! Form::text($item->field_key, $value, ['class' => 'form-control', 'id' => 'rider_id_field'] + array_filter(['required' => $req, 'maxlength' => $spec['maxlength'] ?? null, 'placeholder' => $spec['placeholder'] ?? null]) + $readonlyAttrs) !!}
  <div class="invalid-feedback" id="rider_id_error" style="display: none;"></div>
  @else
  {!! Form::input($spec['type'] ?? 'text', $item->field_key, $value, ['class' => 'form-control'] + array_filter(['required' => $req, 'maxlength' => $spec['maxlength'] ?? null, 'placeholder' => $spec['placeholder'] ?? null]) + $readonlyAttrs) !!}
  @endif
  @endif
  @error($item->field_key)<span class="text-danger">{{ $message }}</span>@enderror
  @else
  @php
  $f = $item->field;
  $req = $f->is_mandatory ?? false;
  $isReadonly = \App\Support\SimAssigneeContactSync::isManagedCustomFieldId((int) $f->id, 'rider_custom_fields');
  $readonlyAttrs = ($isReadonly || !$rfpEditable) ? ['readonly' => 'readonly'] : [];
  @endphp
  {!! Form::label($name, $f->label . ($req ? ':' : ''), $req ? ['class' => 'required fw-bold'] : []) !!}
  @if ($f->help_text)
  <p class="form-text small text-muted mb-1">{{ $f->help_text }}</p>
  @endif
  @switch($f->data_type)
  @case('textarea')
  {!! Form::textarea($name, $value, ['class' => 'form-control', 'rows' => $f->config['rows'] ?? 4] + ($req ? ['required' => true] : []) + $readonlyAttrs) !!}
  @break
  @case('number')
  @case('decimal')
  {!! Form::input($f->data_type, $name, $value, ['class' => 'form-control', 'step' => $f->data_type === 'decimal' ? '0.01' : '1'] + ($req ? ['required' => true] : []) + $readonlyAttrs) !!}
  @break
  @case('date')
  {!! Form::date($name, $value ? (\Carbon\Carbon::parse($value)->format('Y-m-d')) : null, ['class' => 'form-control'] + ($req ? ['required' => true] : []) + $readonlyAttrs) !!}
  @break
  @case('datetime')
  {!! Form::input('datetime-local', $name, $value ? (\Carbon\Carbon::parse($value)->format('Y-m-d\TH:i')) : null, ['class' => 'form-control'] + ($req ? ['required' => true] : []) + $readonlyAttrs) !!}
  @break
  @case('dropdown')
  @php
  $lines = isset($f->config['options']) ? preg_split('/\r\n|\r|\n/', $f->config['options']) : [];
  $dd = ['' => 'Select'];
  foreach ($lines as $line) {
  $line = trim($line);
  if ($line !== '') $dd[$line] = $line;
  }
  @endphp
  {!! Form::select($name, $dd, $value, ['class' => 'form-select', 'placeholder' => 'Select'] + ($req ? ['required' => true] : []) + $rfpSelectLock) !!}
  @break
  @case('checkbox')
  <div class="form-check mt-2">
    <input type="hidden" name="{{ $name }}" value="0" />
    {!! Form::checkbox($name, '1', filter_var($value, FILTER_VALIDATE_BOOLEAN), ['class' => 'form-check-input', 'id' => 'cf_' . $f->id]) !!}
    <label class="form-check-label fw-bold" for="cf_{{ $f->id }}">Yes</label>
  </div>
  @break
  @default
  {!! Form::input($f->data_type === 'email' ? 'email' : ($f->data_type === 'url' ? 'url' : 'text'), $name, $value, ['class' => 'form-control'] + ($req ? ['required' => true] : []) + (!empty($f->config['placeholder']) ? ['placeholder' => $f->config['placeholder']] : []) + $readonlyAttrs) !!}
  @endswitch
  @error($name)<span class="text-danger">{{ $message }}</span>@enderror
  @endif
</div>
@endif
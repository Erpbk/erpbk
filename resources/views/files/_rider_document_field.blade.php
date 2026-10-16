@php
  $val = $val ?? '';
  $fname = $fname ?? '';
  $field = $field ?? null;
  $countries = $countries ?? collect();
  if (!$field) {
    return;
  }
  $isNationality = ($field->key ?? '') === 'nationality'
    || ($field->rider_column ?? '') === 'nationality'
    || strcasecmp((string) ($field->label ?? ''), 'Nationality') === 0;
@endphp
<div class="col-md-6">
  <label class="form-label{{ $field->is_required ? ' required' : '' }}">{{ $field->label }}</label>
  @if($isNationality)
    <select name="{{ $fname }}" class="form-select select2" data-placeholder="Select Nationality" @if($field->is_required) required @endif>
      <option value="">— Select —</option>
      @foreach($countries as $id => $name)
        <option value="{{ $id }}"
          @selected((string) $val === (string) $id || strcasecmp((string) $val, (string) $name) === 0)>
          {{ $name }}
        </option>
      @endforeach
    </select>
  @elseif($field->input_type === 'textarea')
    <textarea name="{{ $fname }}" class="form-control" rows="2" @if($field->is_required) required @endif>{{ $val }}</textarea>
  @elseif($field->input_type === 'select')
    <select name="{{ $fname }}" class="form-select" @if($field->is_required) required @endif>
      <option value="">— Select —</option>
      @foreach(($field->options ?? []) as $opt)
        @php
          $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : (string) $opt;
          $ol = is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : (string) $opt;
        @endphp
        <option value="{{ $ov }}" @selected((string)$val === (string)$ov)>{{ $ol }}</option>
      @endforeach
    </select>
  @else
    <input
      type="{{ $field->input_type === 'number' ? 'number' : ($field->input_type === 'date' ? 'date' : 'text') }}"
      name="{{ $fname }}"
      class="form-control"
      value="{{ $val }}"
      @if($field->is_required) required @endif>
  @endif
</div>

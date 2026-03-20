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
@endphp
<div class="form-group col-sm-4">
  @if ($item->kind === 'fixed')
    @php $spec = $item->spec; $req = !empty($spec['required']); @endphp
    @if (($spec['type'] ?? 'text') === 'select')
      {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      @php
        $opts = [];
        if (($spec['dropdown'] ?? '') === 'countries') {
          $opts = \App\Models\Countries::list()->toArray();
        } elseif (($spec['dropdown'] ?? '') === 'vendors') {
          $opts = \App\Models\Vendors::dropdown();
        } elseif (($spec['dropdown'] ?? '') === 'recruiters') {
          $opts = ['' => 'Select Recruiter'];
          foreach (DB::table('recruiters')->where('status', 1)->get() as $r) {
            $opts[$r->id] = $r->name;
          }
        } elseif (($spec['dropdown'] ?? '') === 'accounts') {
          $opts = \App\Models\Accounts::dropdown(null) ?? ['' => 'Select'];
        } elseif (($spec['dropdown'] ?? '') === 'customers') {
          $opts = \App\Models\Customers::pluck('name', 'id')->prepend('Select', '')->toArray();
        } else {
          $opts = Common::Dropdowns($spec['dropdown'] ?? '');
        }
      @endphp
      {!! Form::select($item->field_key, $opts, $value, ['class' => 'form-select', 'placeholder' => 'Select ' . $item->label, 'id' => $item->field_key === 'rider_id' ? 'rider_id_field' : null] + ($req ? ['required' => true] : [])) !!}
      @if ($item->field_key === 'rider_id')
        <div class="invalid-feedback" id="rider_id_error" style="display: none;"></div>
      @endif
    @elseif (($spec['type'] ?? '') === 'textarea')
      {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::textarea($item->field_key, $value, ['class' => 'form-control', 'rows' => $spec['rows'] ?? 3] + ($req ? ['required' => true] : [])) !!}
    @elseif (($spec['type'] ?? '') === 'checkbox')
      <div class="form-check mt-4">
        <input type="hidden" name="{{ $item->field_key }}" value="{{ in_array($item->field_key, ['vat'], true) ? '2' : '0' }}" />
        {!! Form::checkbox($item->field_key, $spec['value'] ?? 1, $value == 1 || $value === true, ['class' => 'form-check-input', 'id' => 'field_' . $item->field_key]) !!}
        {!! Form::label('field_' . $item->field_key, $item->label, ['class' => 'form-check-label pt-0']) !!}
      </div>
    @else
      {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      @if ($item->field_key === 'rider_id')
        {!! Form::text($item->field_key, $value, ['class' => 'form-control', 'id' => 'rider_id_field'] + array_filter(['required' => $req, 'maxlength' => $spec['maxlength'] ?? null, 'placeholder' => $spec['placeholder'] ?? null])) !!}
        <div class="invalid-feedback" id="rider_id_error" style="display: none;"></div>
      @else
        {!! Form::input($spec['type'] ?? 'text', $item->field_key, $value, ['class' => 'form-control'] + array_filter(['required' => $req, 'maxlength' => $spec['maxlength'] ?? null, 'placeholder' => $spec['placeholder'] ?? null])) !!}
      @endif
    @endif
    @error($item->field_key)<span class="text-danger">{{ $message }}</span>@enderror
  @else
    @php $f = $item->field; $req = $f->is_mandatory ?? false; @endphp
    {!! Form::label($name, $f->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
    @if ($f->help_text)
      <p class="form-text small text-muted mb-1">{{ $f->help_text }}</p>
    @endif
    @switch($f->data_type)
      @case('textarea')
        {!! Form::textarea($name, $value, ['class' => 'form-control', 'rows' => $f->config['rows'] ?? 4] + ($req ? ['required' => true] : [])) !!}
        @break
      @case('number')
      @case('decimal')
        {!! Form::input($f->data_type, $name, $value, ['class' => 'form-control', 'step' => $f->data_type === 'decimal' ? '0.01' : '1'] + ($req ? ['required' => true] : [])) !!}
        @break
      @case('date')
        {!! Form::date($name, $value ? (\Carbon\Carbon::parse($value)->format('Y-m-d')) : null, ['class' => 'form-control'] + ($req ? ['required' => true] : [])) !!}
        @break
      @case('datetime')
        {!! Form::input('datetime-local', $name, $value ? (\Carbon\Carbon::parse($value)->format('Y-m-d\TH:i')) : null, ['class' => 'form-control'] + ($req ? ['required' => true] : [])) !!}
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
        {!! Form::select($name, $dd, $value, ['class' => 'form-select', 'placeholder' => 'Select'] + ($req ? ['required' => true] : [])) !!}
        @break
      @case('checkbox')
        <div class="form-check mt-2">
          <input type="hidden" name="{{ $name }}" value="0" />
          {!! Form::checkbox($name, '1', filter_var($value, FILTER_VALIDATE_BOOLEAN), ['class' => 'form-check-input', 'id' => 'cf_' . $f->id]) !!}
          <label class="form-check-label" for="cf_{{ $f->id }}">Yes</label>
        </div>
        @break
      @default
        {!! Form::input($f->data_type === 'email' ? 'email' : ($f->data_type === 'url' ? 'url' : 'text'), $name, $value, ['class' => 'form-control'] + ($req ? ['required' => true] : []) + (!empty($f->config['placeholder']) ? ['placeholder' => $f->config['placeholder']] : [])) !!}
    @endswitch
    @error($name)<span class="text-danger">{{ $message }}</span>@enderror
  @endif
</div>

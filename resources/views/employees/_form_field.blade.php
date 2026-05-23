@php
$isEdit = isset($employee) && $employee;
$value = null;
if (($item->kind ?? '') === 'fixed') {
    $name = $item->field_key;
    $value = $isEdit
        ? ($employee->{$item->field_key} ?? null)
        : old($item->field_key, ($item->field_key === 'employee_id' ? ($empId ?? null) : null));
} else {
    $name = 'custom_field_values[' . $item->field->id . ']';
    if ($isEdit && is_array($employee->custom_field_values ?? null)) {
        $value = $employee->custom_field_values[$item->field->id] ?? $item->field->default_value ?? null;
    } else {
        $value = old('custom_field_values.' . $item->field->id) ?? $item->field->default_value ?? null;
    }
}
@endphp
<div class="form-group col-sm-4 mb-3">
    @if (($item->kind ?? '') === 'fixed')
        @php
        $spec = $item->spec ?? [];
        $req = !empty($spec['required']);
        $isReadonly = !empty($spec['readonly'])
            || \App\Support\SimAssigneeContactSync::isManagedFixedFieldKey($item->field_key ?? null);
        $readonlyAttrs = $isReadonly ? ['readonly' => 'readonly'] : [];
        @endphp
        {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required fw-bold'] : []) !!}
        @if (($spec['type'] ?? 'text') === 'select')
            @php
                $opts = [];
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
                        $opts[$opt] = $opt;
                    }
                } elseif ($item->field_key === 'branch_id') {
                    $opts = \App\Models\Branch::active()->pluck('name', 'id')->prepend('Select', '')->toArray();
                } elseif ($item->field_key === 'nationality_id') {
                    $opts = \App\Models\Countries::list()->toArray();
                } elseif ($item->field_key === 'department_id') {
                    $opts = \App\Models\Departments::pluck('name', 'id')->prepend('Select', '')->toArray();
                } elseif ($item->field_key === 'status') {
                    $opts = ['' => 'Select', 'active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave'];
                    if (!empty($parsedOptions)) {
                        $opts = ['' => 'Select'];
                        foreach ($parsedOptions as $opt) {
                            $opts[$opt] = ucwords(str_replace('_', ' ', $opt));
                        }
                    }
                }
            @endphp
            {!! Form::select($item->field_key, $opts, $value, ['class' => 'form-select'] + ($req ? ['required' => true] : [])) !!}
        @elseif (($spec['type'] ?? '') === 'textarea')
            {!! Form::textarea($item->field_key, $value, ['class' => 'form-control', 'rows' => $spec['rows'] ?? 3] + ($req ? ['required' => true] : []) + $readonlyAttrs) !!}
        @elseif (($spec['type'] ?? '') === 'checkbox')
            <div class="form-check mt-2">
                <input type="hidden" name="{{ $item->field_key }}" value="0" />
                {!! Form::checkbox($item->field_key, 1, $value == 1 || $value === true, ['class' => 'form-check-input', 'id' => 'field_' . $item->field_key]) !!}
                {!! Form::label('field_' . $item->field_key, $item->label, ['class' => 'form-check-label fw-bold pt-0']) !!}
            </div>
        @else
            {!! Form::input($spec['type'] ?? 'text', $item->field_key, $value, ['class' => 'form-control'] + array_filter(['required' => $req]) + $readonlyAttrs) !!}
        @endif
    @else
        @php
        $f = $item->field;
        $req = $f->is_mandatory ?? false;
        $isReadonly = \App\Support\SimAssigneeContactSync::isManagedCustomFieldId((int) $f->id, 'employee_custom_fields');
        $readonlyAttrs = $isReadonly ? ['readonly' => 'readonly'] : [];
        @endphp
        {!! Form::label($name, $f->label . ($req ? ':' : ''), $req ? ['class' => 'required fw-bold'] : []) !!}
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
            @case('dropdown')
                @php
                    $lines = isset($f->config['options']) ? preg_split('/\r\n|\r|\n/', $f->config['options']) : [];
                    $dd = ['' => 'Select'];
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $dd[$line] = $line;
                        }
                    }
                @endphp
                {!! Form::select($name, $dd, $value, ['class' => 'form-select'] + ($req ? ['required' => true] : [])) !!}
                @break
            @default
                {!! Form::input('text', $name, $value, ['class' => 'form-control'] + ($req ? ['required' => true] : []) + $readonlyAttrs) !!}
        @endswitch
    @endif
</div>

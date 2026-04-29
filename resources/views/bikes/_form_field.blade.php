@php
    $isEdit = isset($bikes);
    $value = null;

    if ($item->kind === 'fixed') {
        $name = $item->field_key;
        $value = $isEdit ? ($bikes->{$item->field_key} ?? null) : old($item->field_key);
    } else {
        // Custom fields are stored in bikes.custom_field_values as an array keyed by field id.
        $name = 'custom_field_values[' . $item->field->id . ']';
        if ($isEdit && is_array($bikes->custom_field_values ?? null)) {
            $value = $bikes->custom_field_values[$item->field->id] ?? $item->field->default_value ?? null;
        } else {
            $value = old('custom_field_values.' . $item->field->id) ?? $item->field->default_value ?? null;
        }
    }

    // Existing UI uses this class to hide cyclist-only inputs.
    $cyclistHideFields = [
        'bike_code',
        'chassis_number',
        'engine',
        'model_type',
        'traffic_file_number',
        'expiry_date',
        'insurance_expiry',
        'insurance_co',
        'policy_no',
    ];

    $wrapperExtraClass = ($item->kind === 'fixed' && in_array($item->field_key, $cyclistHideFields, true)) ? ' hide-if-cyclist' : '';
@endphp

<div class="form-group col-sm-4{{ $wrapperExtraClass }}">
    @if ($item->kind === 'fixed')
        @php
            $spec = $item->spec ?? [];
            $req = !empty($spec['required']);
            $fieldId = $item->field_key === 'vehicle_type' ? 'vehicle_type' : null;
        @endphp

        @if (($spec['type'] ?? 'text') === 'select')
            {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
            @php
                $dropdownKey = $spec['dropdown'] ?? null;
                $opts = [];

                // Prefer user-configured fixed-field options (stored in Bike Settings).
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
                    // Skip dropdown-key-based options since we already have explicit options.
                } else {
                    switch ($dropdownKey) {
                        case 'vehicle_models':
                            $opts = \DB::table('vehicle_models')->where('status', 1)->pluck('name', 'id')->toArray();
                            $opts = ['' => 'Select Model'] + $opts;
                            break;
                        case 'branch':
                            $opts = \App\Models\Branch::dropdown();
                            break;
                        case 'leasing_companies':
                            $opts = \App\Models\LeasingCompanies::dropdown();
                            break;
                        case 'customers':
                            $opts = \App\Models\Customers::pluck('name', 'id')->prepend('Select', '')->toArray();
                            break;
                        case 'riders':
                            $opts = \App\Models\Riders::dropdown();
                            break;
                        case 'warehouse':
                            $opts = [
                                '' => 'Select Warehouse',
                                'Active' => 'Active',
                                'Return' => 'Return',
                                'Vacation' => 'Vacation',
                                'Express Garage' => 'Express Garage',
                                'Absconded' => 'Absconded',
                            ];
                            break;
                        default:
                            // Works for dropdown keys configured in the central dropdowns table.
                            $opts = !empty($dropdownKey) ? \App\Helpers\Common::Dropdowns($dropdownKey) : [];
                            if (empty($opts) && !empty($dropdownKey)) {
                                $opts = ['' => 'Select'] + (array) \App\Helpers\Common::Dropdowns($dropdownKey);
                            }
                            if (empty($opts)) {
                                $opts = ['' => 'Select'];
                            }
                            break;
                    }
                }
            @endphp

            {!! Form::select(
                $item->field_key,
                $opts,
                $value,
                [
                    'class' => 'form-select select2',
                    'placeholder' => 'Select ' . $item->label,
                    'id' => $fieldId,
                    'required' => $req,
                ]
            ) !!}
        @elseif (($spec['type'] ?? '') === 'textarea')
            {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
            {!! Form::textarea(
                $item->field_key,
                $value,
                ['class' => 'form-control', 'rows' => $spec['rows'] ?? 3, 'required' => $req]
            ) !!}
        @elseif (($spec['type'] ?? '') === 'checkbox')
            <div class="form-check mt-4">
                @php
                    // Bikes use: 1 = Active, 2 = Inactive (see existing edit UI).
                    $hiddenValue = $item->field_key === 'status' ? '2' : '0';
                @endphp
                <input type="hidden" name="{{ $item->field_key }}" value="{{ $hiddenValue }}" />
                {!! Form::checkbox(
                    $item->field_key,
                    $spec['value'] ?? 1,
                    $value == 1 || $value === true,
                    ['class' => 'form-check-input', 'id' => 'field_' . $item->field_key]
                ) !!}
                {!! Form::label('field_' . $item->field_key, $item->field_key === 'status' ? 'Is Active' : $item->label, ['class' => 'form-check-label pt-0']) !!}
            </div>
        @else
            {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
            {!! Form::input(
                $spec['type'] ?? 'text',
                $item->field_key,
                $value,
                [
                    'class' => 'form-control',
                    'id' => $fieldId,
                    'required' => $req,
                    // Add max-length if provided via config/spec.
                    'maxlength' => $spec['maxlength'] ?? null,
                    'autocomplete' => 'off',
                ]
            ) !!}
        @endif

        @error($item->field_key)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    @else
        @php
            $f = $item->field;
            $req = $f->is_mandatory ?? false;
        @endphp
        {!! Form::label($name, $f->label . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
        @if ($f->help_text)
            <p class="form-text small text-muted mb-1">{{ $f->help_text }}</p>
        @endif

        @switch($f->data_type)
            @case('textarea')
                {!! Form::textarea($name, $value, ['class' => 'form-control', 'rows' => $f->config['rows'] ?? 4, 'required' => $req]) !!}
            @break

            @case('number')
            @case('decimal')
                {!! Form::input($f->data_type, $name, $value, [
                    'class' => 'form-control',
                    'step' => $f->data_type === 'decimal' ? '0.01' : '1',
                    'required' => $req,
                ]) !!}
            @break

            @case('date')
                {!! Form::date($name, $value ? (\Carbon\Carbon::parse($value)->format('Y-m-d')) : null, ['class' => 'form-control', 'required' => $req]) !!}
            @break

            @case('datetime')
                {!! Form::input('datetime-local', $name, $value ? (\Carbon\Carbon::parse($value)->format('Y-m-d\TH:i')) : null, ['class' => 'form-control', 'required' => $req]) !!}
            @break

            @case('dropdown')
                @php
                    $lines = isset($f->config['options']) ? preg_split('/\r\n|\r|\n/', (string) $f->config['options']) : [];
                    $dd = ['' => 'Select'];
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $dd[$line] = $line;
                        }
                    }
                @endphp
                {!! Form::select($name, $dd, $value, ['class' => 'form-select', 'placeholder' => 'Select', 'required' => $req]) !!}
            @break

            @case('checkbox')
                <div class="form-check mt-2">
                    <input type="hidden" name="{{ $name }}" value="0" />
                    {!! Form::checkbox($name, '1', filter_var($value, FILTER_VALIDATE_BOOLEAN), ['class' => 'form-check-input', 'id' => 'cf_' . $f->id]) !!}
                    {!! Form::label('cf_' . $f->id, 'Yes', ['class' => 'form-check-label']) !!}
                </div>
            @break

            @default
                {!! Form::input(
                    $f->data_type === 'email' ? 'email' : ($f->data_type === 'url' ? 'url' : 'text'),
                    $name,
                    $value,
                    ['class' => 'form-control', 'required' => $req] + (!empty($f->config['placeholder']) ? ['placeholder' => $f->config['placeholder']] : [])
                ) !!}
        @endswitch

        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    @endif
</div>


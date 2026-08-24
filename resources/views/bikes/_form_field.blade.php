@php
$isEdit = isset($bikes);
$value = null;

$formatBikeDateInput = function ($raw, bool $asDatetime = false): ?string {
    if ($raw === null || $raw === '' || $raw === false) {
        return null;
    }
    if (is_string($raw) && (str_starts_with($raw, '0000-00-00') || trim($raw) === '0')) {
        return null;
    }
    try {
        $parsed = \Carbon\Carbon::parse($raw);
        return $asDatetime ? $parsed->format('Y-m-d\TH:i') : $parsed->format('Y-m-d');
    } catch (\Throwable $e) {
        return null;
    }
};

if ($item->kind === 'fixed') {
$name = $item->field_key;
$fixedDefault = ($item->spec['default'] ?? null);
$storedFixed = $isEdit ? ($bikes->{$item->field_key} ?? null) : $fixedDefault;
$value = old($item->field_key, $storedFixed);
} else {
// Custom fields are stored in bikes.custom_field_values as an array keyed by field id.
$name = 'custom_field_values[' . $item->field->id . ']';
$storedCustom = ($isEdit && is_array($bikes->custom_field_values ?? null))
    ? ($bikes->custom_field_values[$item->field->id] ?? $item->field->default_value ?? null)
    : ($item->field->default_value ?? null);
$value = old('custom_field_values.' . $item->field->id, $storedCustom);
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

$rfpEntity = 'bike';
$rfpField = $item->kind === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
$rfpVisible = field_visible($rfpEntity, (string) $rfpField);
$rfpEditable = field_editable($rfpEntity, (string) $rfpField);
$rfpRequired = field_required($rfpEntity, (string) $rfpField);
$rfpLock = $rfpEditable ? [] : ['readonly' => 'readonly'];
$rfpSelectLock = $rfpEditable ? [] : ['disabled' => true];

// Owned bikes store company=null; form uses sentinel value "own".
if ($item->kind === 'fixed' && $item->field_key === 'company') {
$isOwned = $isEdit
&& strcasecmp((string) ($bikes->bike_owner ?? ''), 'Owned') === 0
&& ($bikes->company === null || $bikes->company === '');
if ($isOwned || old('company') === 'own') {
$value = 'own';
}
}
@endphp

@if ($rfpVisible)
<div class="form-group {{ !empty($fullWidth) ? 'col-sm-12' : 'col-sm-4' }}{{ $wrapperExtraClass }}" data-rfp-entity="bike" data-rfp-field="{{ $rfpField }}">
    @if ($item->kind === 'fixed')
    @php
    $spec = $item->spec ?? [];
    // Required is controlled only by Role Field Permissions (not hardcoded $spec['required']).
    $req = $rfpRequired && $rfpEditable;
    $fieldId = $item->field_key === 'vehicle_type' ? $item->field_key : null;
    @endphp

    @if (($spec['type'] ?? 'text') === 'select')
    {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), ['class' => 'fw-bold' . ($req ? ' required' : '')]) !!}
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

    if (!empty($parsedOptions) && $item->field_key !== 'company') {
    foreach ($parsedOptions as $opt) {
    $opts[$opt] = $opt;
    }
    // Skip dropdown-key-based options since we already have explicit options.
    } elseif ($item->field_key === 'company') {
    $opts = \App\Models\LeasingCompanies::dropdownWithOwnOption();
    } else {
    switch ($dropdownKey) {
    case 'vehicle_models':
    $opts = company_table('vehicle_models')->where('status', 1)->pluck('name', 'id')->toArray();
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
    case 'emirates-hub':
    $opts = \App\Helpers\Common::Dropdowns('emirates-hub');
    if (empty($opts)) {
    $opts = \App\Models\BikeCustomField::emiratesHubSelectOptions();
    } else {
    $opts = ['' => 'Select'] + $opts;
    }
    break;
    default:
    // Works for dropdown keys configured in the central dropdowns table.
    $opts = !empty($dropdownKey) ? \App\Helpers\Common::Dropdowns($dropdownKey) : [];
    if (empty($opts) && !empty($dropdownKey)) {
    $opts = ['' => 'Select'] + (array) \App\Helpers\Common::Dropdowns($dropdownKey);
    }
                if (empty($opts)) {
                    $opts = [];
                }
                break;
    }
    }
    $opts = \App\Models\BikeCustomField::withoutPlaceholderSelectOptions($opts);
    $selectPlaceholder = \App\Models\BikeCustomField::selectPlaceholderLabel($item->label);
    @endphp

    {!! Form::select(
    $item->field_key,
    $opts,
    $value,
    [
    'class' => 'form-select select2',
    'id' => $fieldId,
    'required' => $req,
    'placeholder' => $selectPlaceholder,
    ] + $rfpSelectLock
    ) !!}
    @elseif (($spec['type'] ?? '') === 'textarea')
    {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), ['class' => 'fw-bold' . ($req ? ' required' : '')]) !!}
    {!! Form::textarea(
    $item->field_key,
    $value,
    [
    'class' => 'form-control',
    'rows' => $spec['rows'] ?? 3,
    'required' => $req,
    'maxlength' => $spec['maxlength'] ?? null,
    'placeholder' => $spec['placeholder'] ?? null,
    ] + $rfpLock
    ) !!}
    @elseif (($spec['type'] ?? '') === 'checkbox')
    <div class="form-check mt-4">
        @php
        // Bikes use: 1 = Active, 2 = Inactive (see existing edit UI).
        $hiddenValue = $item->field_key === 'status' ? '2' : '0';
        @endphp
        <input type="hidden" name="{{ $item->field_key }}" value="{{ $hiddenValue }}" />
        @php
        $checked = $value == 1 || $value === true;
        if ($item->field_key !== 'status' && !$isEdit && ($value === null || $value === '' || $value === false) && !empty($spec['default_checked'])) {
            $checked = true;
        }
        @endphp
        {!! Form::checkbox(
        $item->field_key,
        $spec['value'] ?? 1,
        $checked,
        ['class' => 'form-check-input', 'id' => 'field_' . $item->field_key] + $rfpSelectLock
        ) !!}
        {!! Form::label(
        'field_' . $item->field_key,
        $item->field_key === 'status' ? 'Is Active' : $item->label,
        ['class' => 'form-check-label pt-0' . ($req ? ' required' : '')]
        ) !!}
    </div>
    @elseif (($spec['type'] ?? '') === 'date')
    {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), ['class' => 'fw-bold' . ($req ? ' required' : '')]) !!}
    {!! Form::date(
    $item->field_key,
    $formatBikeDateInput($value),
    [
    'class' => 'form-control',
    'id' => $fieldId,
    'required' => $req,
    'autocomplete' => 'off',
    ] + $rfpLock
    ) !!}
    @elseif (($spec['type'] ?? '') === 'datetime')
    {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), ['class' => 'fw-bold' . ($req ? ' required' : '')]) !!}
    {!! Form::input(
    'datetime-local',
    $item->field_key,
    $formatBikeDateInput($value, true),
    [
    'class' => 'form-control',
    'id' => $fieldId,
    'required' => $req,
    'autocomplete' => 'off',
    ] + $rfpLock
    ) !!}
    @else
    {!! Form::label($item->field_key, $item->label . ($req ? ':' : ''), ['class' => 'fw-bold' . ($req ? ' required' : '')]) !!}
    {!! Form::input(
    $spec['type'] ?? 'text',
    $item->field_key,
    $value,
    [
    'class' => 'form-control',
    'id' => $fieldId,
    'required' => $req,
    'maxlength' => $spec['maxlength'] ?? null,
    'placeholder' => $spec['placeholder'] ?? null,
    'min' => $spec['min'] ?? null,
    'max' => $spec['max'] ?? null,
    'step' => $spec['step'] ?? null,
    'autocomplete' => 'off',
    ] + $rfpLock
    ) !!}
    @endif

    @if (!empty($spec['help_text']))
    <p class="form-text small text-muted mb-1">{{ $spec['help_text'] }}</p>
    @endif

    @error($item->field_key)
    <span class="text-danger">{{ $message }}</span>
    @enderror
    @else
    @php
    $f = $item->field;
    $req = $rfpRequired && $rfpEditable;
    @endphp
    {!! Form::label($name, $f->label . ($req ? ':' : ''), ['class' => 'fw-bold' . ($req ? ' required' : '')]) !!}
    @if ($f->help_text)
    <p class="form-text small text-muted mb-1">{{ $f->help_text }}</p>
    @endif

    @switch($f->data_type)
    @case('textarea')
    {!! Form::textarea($name, $value, ['class' => 'form-control', 'rows' => $f->config['rows'] ?? 4, 'maxlength' => $f->config['max_length'] ?? null, 'required' => $req] + $rfpLock) !!}
    @break

    @case('number')
    @case('decimal')
    {!! Form::input($f->data_type, $name, $value, [
    'class' => 'form-control',
    'step' => $f->data_type === 'decimal' ? '0.01' : '1',
    'required' => $req,
    ] + $rfpLock) !!}
    @break

    @case('date')
    {!! Form::date($name, $formatBikeDateInput($value), ['class' => 'form-control', 'required' => $req] + $rfpLock) !!}
    @break

    @case('datetime')
    {!! Form::input('datetime-local', $name, $formatBikeDateInput($value, true), ['class' => 'form-control', 'required' => $req] + $rfpLock) !!}
    @break

    @case('dropdown')
    @php
    $lines = isset($f->config['options']) ? preg_split('/\r\n|\r|\n/', (string) $f->config['options']) : [];
    $dd = [];
    foreach ($lines as $line) {
    $line = trim($line);
    if ($line !== '') {
    $dd[$line] = $line;
    }
    }
    $dd = \App\Models\BikeCustomField::withoutPlaceholderSelectOptions($dd);
    $selectPlaceholder = \App\Models\BikeCustomField::selectPlaceholderLabel($f->label);
    @endphp
    {!! Form::select($name, $dd, $value, ['class' => 'form-select select2', 'required' => $req, 'placeholder' => $selectPlaceholder] + $rfpSelectLock) !!}
    @break

    @case('checkbox')
    <div class="form-check mt-2">
        <input type="hidden" name="{{ $name }}" value="0" />
        {!! Form::checkbox($name, '1', filter_var($value, FILTER_VALIDATE_BOOLEAN), ['class' => 'form-check-input', 'id' => 'cf_' . $f->id] + $rfpSelectLock) !!}
        {!! Form::label('cf_' . $f->id, 'Yes', ['class' => 'form-check-label ']) !!}
    </div>
    @break

    @default
    {!! Form::input(
    $f->data_type === 'email' ? 'email' : ($f->data_type === 'url' ? 'url' : 'text'),
    $name,
    $value,
    ['class' => 'form-control', 'required' => $req] + (!empty($f->config['placeholder']) ? ['placeholder' => $f->config['placeholder']] : []) + $rfpLock
    ) !!}
    @endswitch

    @error($name)
    <span class="text-danger">{{ $message }}</span>
    @enderror
    @endif
</div>
@endif
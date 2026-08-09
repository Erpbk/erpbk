@php
$refType = $refType ?? 'rider';
$prefix = $prefix ?? '';
$namePrefix = $prefix !== '' ? rtrim($prefix, '.') . '.' : '';
$fieldIdPrefix = $fieldIdPrefix ?? ($prefix !== '' ? str_replace(['.', '[', ']'], '_', $prefix) : '');
$isRider = $refType === 'rider';

$normalizeMetric = static function ($value, $default = '') {
    if ($value === null || $value === '') {
        return $default;
    }

    return $value;
};

$fields = [
    'total_orders' => [
        'label' => 'Total Orders',
        'value' => $normalizeMetric($total_orders ?? old($namePrefix . 'total_orders'), ''),
        'step' => '1',
        'required' => true,
    ],
    'working_hours' => [
        'label' => 'Working Hours',
        'value' => $normalizeMetric($working_hours ?? old($namePrefix . 'working_hours'), ''),
        'step' => '0.01',
        'required' => true,
    ],
    'cancelled_orders' => [
        'label' => 'Cancelled Orders',
        'value' => $normalizeMetric($cancelled_orders ?? old($namePrefix . 'cancelled_orders'), 0),
        'step' => '1',
        'required' => false,
        'default_zero' => true,
    ],
    'rejected_orders' => [
        'label' => 'Rejected Orders',
        'value' => $normalizeMetric($rejected_orders ?? old($namePrefix . 'rejected_orders'), 0),
        'step' => '1',
        'required' => false,
        'default_zero' => true,
    ],
    'ontime_orders_percentage' => [
        'label' => 'On-Time Performance (%)',
        'value' => $normalizeMetric($ontime_orders_percentage ?? old($namePrefix . 'ontime_orders_percentage'), ''),
        'step' => '0.01',
        'required' => false,
    ],
];
@endphp

{{-- Always render so ref_type toggle can show/hide without a full reload --}}
<div class="rider-activity-fields-section mb-4 {{ $wrapperClass ?? '' }}"
    id="{{ $fieldIdPrefix }}rider_activity_section"
    style="{{ $isRider ? '' : 'display:none;' }}">
    <div class="card border-info">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0 text-info">
                <i class="fas fa-chart-line me-2"></i>Rider Activity / Performance
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($fields as $fieldKey => $field)
                @php
                    $inputName = $namePrefix . $fieldKey;
                    $inputId = $fieldIdPrefix . $fieldKey;
                    $isRequired = !empty($field['required']);
                @endphp
                <div class="col-md-6">
                    <label for="{{ $inputId }}" class="form-label fw-semibold">
                        {{ $field['label'] }}
                        @if($isRequired)
                            <span class="text-danger">*</span>
                        @endif
                    </label>
                    <input type="number"
                        class="form-control rider-activity-order-input @error($inputName) is-invalid @enderror"
                        id="{{ $inputId }}"
                        name="{{ $inputName }}"
                        min="0"
                        step="{{ $field['step'] }}"
                        value="{{ $field['value'] }}"
                        placeholder="0"
                        @if($isRequired) data-rider-metric-required="1" @endif
                        @if(!empty($field['default_zero'])) data-rider-metric-default="0" @endif
                        @if($isRider && $isRequired) required @endif>
                    @error($inputName)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

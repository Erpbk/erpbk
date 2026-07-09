@php
$refType = $refType ?? 'rider';
$prefix = $prefix ?? '';
$namePrefix = $prefix !== '' ? rtrim($prefix, '.') . '.' : '';
$fieldIdPrefix = $fieldIdPrefix ?? ($prefix !== '' ? str_replace(['.', '[', ']'], '_', $prefix) : '');

$fields = [
'total_orders' => [
'label' => 'Total Orders',
'value' => $total_orders ?? old($namePrefix . 'total_orders', ''),
'step' => '1',
],
'working_hours' => [
'label' => 'Working Hours',
'value' => $working_hours ?? old($namePrefix . 'working_hours', ''),
'step' => '0.01',
],
'cancelled_orders' => [
'label' => 'Cancelled Orders',
'value' => $cancelled_orders ?? old($namePrefix . 'cancelled_orders', ''),
'step' => '1',
],
'rejected_orders' => [
'label' => 'Rejected Orders',
'value' => $rejected_orders ?? old($namePrefix . 'rejected_orders', ''),
'step' => '1',
],
'ontime_orders_percentage' => [
'label' => 'On-Time Performance (%)',
'value' => $ontime_orders_percentage ?? old($namePrefix . 'ontime_orders_percentage', ''),
'step' => '0.01',
],
];
@endphp

@if($refType === 'rider')
<div class="rider-activity-fields-section mb-4 {{ $wrapperClass ?? '' }}" id="{{ $fieldIdPrefix }}rider_activity_section">
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
                @endphp
                <div class="col-md-6">
                    <label for="{{ $inputId }}" class="form-label fw-semibold">{{ $field['label'] }}</label>
                    <input type="number"
                        class="form-control rider-activity-order-input"
                        id="{{ $inputId }}"
                        name="{{ $inputName }}"
                        min="0"
                        step="{{ $field['step'] }}"
                        value="{{ $field['value'] !== null && $field['value'] !== '' ? $field['value'] : '' }}"
                        placeholder="0">
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

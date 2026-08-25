@php
    $currency = \App\Helpers\Currency::code();
    // card_issue_date is cast to a Carbon instance, which input[type=date] cannot parse.
    $issueDate = old('card_issue_date', optional($fuelCard->card_issue_date ?? null)->format('Y-m-d'));

    // Fields the controller always demands, on top of anything an admin marks required.
    $alwaysRequired = ['card_number' => true, 'fuel_company_id' => true, 'card_issue_date' => true];
    $fuelRequired = static function (string $field) use ($alwaysRequired): bool {
        return (($alwaysRequired[$field] ?? false) || field_required('fuel', $field))
            && field_editable('fuel', $field);
    };
    $fuelAttrs = static function (string $field, array $extra = [], string $control = 'input') use ($fuelRequired): array {
        if ($fuelRequired($field)) {
            $extra['required'] = 'required';
        }
        return $extra + field_lock('fuel', $field, $control);
    };
@endphp

<div data-rfp-entity="fuel" class="row">

    <!-- Card Number Field -->
    @fieldVisible('fuel', 'card_number')
    <div class="form-group col-md-6 mb-3">
        <label for="card_number" class="form-label">Card Number @if($fuelRequired('card_number'))<span class="text-danger">*</span>@endif</label>
        {!! Form::text('card_number', null, $fuelAttrs('card_number', ['class' => 'form-control', 'id' => 'card_number', 'placeholder' => 'Enter card number'])) !!}
    </div>
    @endfieldVisible

    <!-- Fuel Company Field -->
    @fieldVisible('fuel', 'fuel_company_id')
    <div class="form-group col-md-6 mb-3">
        <label for="fuel_company_id" class="form-label">Fuel Company @if($fuelRequired('fuel_company_id'))<span class="text-danger">*</span>@endif</label>
        {!! Form::select('fuel_company_id', \App\Models\FuelCompany::dropdown(), null, $fuelAttrs('fuel_company_id', ['class' => 'form-control select2', 'id' => 'fuel_company_id'], 'select')) !!}
    </div>
    @endfieldVisible

    <!-- Service Charges Field -->
    @fieldVisible('fuel', 'service_charges')
    <div class="form-group col-md-6 mb-3">
        <label for="service_charges" class="form-label">Service Charges ({{ $currency }}) @if($fuelRequired('service_charges'))<span class="text-danger">*</span>@endif</label>
        <div class="input-group">
            {!! Form::number('service_charges', null, $fuelAttrs('service_charges', ['class' => 'form-control', 'id' => 'service_charges', 'step' => '0.01', 'min' => '0', 'placeholder' => 'Enter service charges'])) !!}
            <span class="input-group-text">{{ $currency }}</span>
        </div>
    </div>
    @endfieldVisible

    <!-- Card Issue Date Field -->
    @fieldVisible('fuel', 'card_issue_date')
    <div class="form-group col-md-6 mb-3">
        <label for="card_issue_date" class="form-label">Card Issue Date @if($fuelRequired('card_issue_date'))<span class="text-danger">*</span>@endif</label>
        {!! Form::date('card_issue_date', $issueDate, $fuelAttrs('card_issue_date', ['class' => 'form-control', 'id' => 'card_issue_date'])) !!}
    </div>
    @endfieldVisible

    <!-- Remarks Field -->
    @fieldVisible('fuel', 'remarks')
    <div class="form-group col-12 mb-3">
        <label for="remarks" class="form-label">Remarks @if($fuelRequired('remarks'))<span class="text-danger">*</span>@endif</label>
        {!! Form::textarea('remarks', null, $fuelAttrs('remarks', ['class' => 'form-control', 'id' => 'remarks', 'rows' => 3, 'placeholder' => 'Enter remarks (optional)'])) !!}
    </div>
    @endfieldVisible

</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $('#formajax'),
            allowClear: true,
            placeholder: 'Select fuel company'
        });
    });
</script>

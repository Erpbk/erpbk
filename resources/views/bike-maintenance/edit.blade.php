{!! Form::open(['route' => ['bikeMaintenance.update',$maintenance], 'method' => 'patch', 'id' => 'formajax', 'files' => true]) !!}
    @csrf

@php
$currencyCode = \App\Helpers\Currency::code();
$invItemsCols = '1.8fr .7fr .8fr .6fr 1fr 1fr .5fr';
@endphp
@include('invoices.partials.invoice_form_styles', ['invItemsCols' => $invItemsCols])

<div class="inv-form-wrap">
    <div class="row g-3">
        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-wrench"></i> Maintenance Details</h6>
                <div class="row g-3">
            {{-- Bike Information (Read-only) --}}
            <div class="form-group col-md-2">
                {!! Form::label('bike_info', 'Bike', ['class' => 'form-label']) !!}
                {!! Form::text('bike_info', $bike->emirates .'-'. $bike->plate, ['class'=>'form-control', 'readonly' => true ]) !!}
                <input type="hidden" name="bike_id" value="{{ $bike->id }}"/>
            </div>

            {{-- Rider Information (Read-only) --}}
            <div class="form-group col-md-4">
                {!! Form::label('rider_info', 'User') !!}
                {!! Form::hidden('rider_id',$bike->rider? $bike->rider->id :null) !!}
                {!! Form::hidden('rental_company_id',$bike->rentalCompany? $bike->rentalCompany->id :null) !!}
                {!! Form::text('rider_info', $bike->rider? ($bike->rider->rider_id.'-'.$bike->rider->name) : ($bike->rentalCompany? $bike->rentalCompany->name: 'No User Assigned'), ['class' => 'form-control', 'readonly' => true]) !!}
            </div>

            {{-- Maintenance Date --}}
            <div class="form-group col-md-3">
                {!! Form::label('maintenance_date', 'Maintenance Date', ['class' => 'required']) !!}
                {!! Form::date('maintenance_date', $maintenance->maintenance_date , ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Attachment --}}
            <div class="form-group col-md-3">
                @include('partials.universal_document_upload', [
                  'name' => 'attachment',
                  'label' => 'Attachment',
                  'required' => false,
                  'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx',
                  'inputClass' => 'form-control',
                  'showHint' => true,
                ])
            </div>

            {{-- Billing Month --}}
            <div class="form-group col-md-3">
                {!! Form::label('billing_month', 'Billing Month') !!}
                {!! Form::month('billing_month', $maintenance->billing_month ?? now(), ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Garage --}}
            <div class="form-group col-md-3">
                {!! Form::label('garage', 'Garage:') !!}
                <select name="garage_id" class="form-control select2" required>
                    <option value="">Select</option>
                    @foreach ($garages as $garage)
                        <option value="{{ $garage->id }}" @if($garage->id == $maintenance->garage_id) selected @endif>{{ $garage->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Maintenance Type --}}
            <div class="form-group col-md-3">
                {!! Form::label('maintenance_type', 'Maintenance Type', ['class' => 'required']) !!}
                @php
                    $selectedType = ((float) ($maintenance->current_km ?? 0) > 0) ? 'Scheduled' : 'Repairs';
                @endphp
                <select name="maintenance_type" id="maintenance_type" class="form-control select2" required>
                    <option value="">Select</option>
                    <option value="Scheduled" @if($selectedType === 'Scheduled') selected @endif>Scheduled</option>
                    <option value="Repairs" @if($selectedType === 'Repairs') selected @endif>Repairs</option>
                </select>
            </div>

            {{-- Description moved to Notes card below --}}
            </div>
            </div>
        </div>

        <div class="col-12" id="odometer-fields" @if($selectedType !== 'Scheduled') style="display: none;" @endif>
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-tachometer-alt"></i> Odometer</h6>
                <div class="row g-3">

            {{-- Previous KM --}}
            <div class="form-group col-md-2">
                {!! Form::label('previous_km', 'Previous Reading', ['class' => 'form-label']) !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('previous_km', $maintenance->previous_km ?? null, [
                        'class' => 'form-control odometer-input', 
                        'step' => 'any', 
                        'min' => '0',
                        'id' => 'previous_km',
                        'readonly' => !is_null($maintenance->previous_km)
                    ]) !!}
                </div>
            </div>

            {{-- Current KM --}}
            <div class="form-group col-md-2">
                {!! Form::label('current_km', 'Current Reading') !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('current_km', $maintenance->current_km ?? null, [
                        'class' => 'form-control odometer-input', 
                        'step' => 'any', 
                        'min' => '0',
                        'id' => 'current_km',
                    ]) !!}
                </div>
            </div>

            {{-- Maintenance KM (interval for maintenance) --}}
            <div class="form-group col-md-2">
                {!! Form::label('maintenance_km', 'Maintenance Interval') !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('maintenance_km', $bike->maintenance_km ?? null, [
                        'class' => 'form-control odometer-input', 
                        'step' => 'any', 
                        'min' => '0',
                        'id' => 'maintenance_km',
                    ]) !!}
                </div>
            </div>

            {{-- Overdue KM (calculated field) --}}
            <div class="form-group col-md-2">
                {!! Form::label('overdue_km', 'Overdue Reading') !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('overdue_km', $maintenance->overdue_km ?? null, [
                        'class' => 'form-control odometer-input', 
                        'step' => 'any',
                        'readonly' => true,
                        'id' => 'overdue_km'
                    ]) !!}
                </div>
            </div>

            {{-- Overdue Cost Per KM --}}
            <div class="form-group col-md-2">
                {!! Form::label('overdue_cost_per_km', 'Cost Per Overdue KM') !!}
                <div class="input-group">
                    <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                    {!! Form::number('overdue_cost_per_km', $maintenance->overdue_cost_per_km ?? 1, [
                        'class' => 'form-control odometer-input', 
                        'step' => '0.01', 
                        'min' => '0',
                        'id' => 'cost_per_km',
                        'placeholder' => '0.00'
                    ]) !!}
                </div>
            </div>

            {{-- Total Overdue Cost (calculated field) --}}
            <div class="form-group col-md-2">
                {!! Form::label('overdue_cost', 'Overdue Cost') !!}
                <div class="input-group">
                    <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                    {!! Form::number('overdue_cost', null, [
                        'class' => 'form-control odometer-input', 
                        'step' => '0.01',
                        'readonly' => true,
                        'id' => 'overdue_cost'
                    ]) !!}
                </div>
            </div>

            <div class="form-group col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                        name="overdue_paidby" value="Rider" id="charge_overdue_rider"
                        @if(($maintenance->overdue_paidby ?? null) === 'Rider') checked @endif>
                    <label class="form-check-label fw-bold" for="charge_overdue_rider">Charge Overdue to Rider</label>
                </div>
            </div>
        </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-list"></i> Maintenance Items</h6>
                <div class="inv-items-head">
                    <span>Item</span>
                    <span>Qty</span>
                    <span>Rate</span>
                    <span>VAT %</span>
                    <span>Total</span>
                    <span>Charge To</span>
                    <span>Action</span>
                </div>
        <div id="rows-container">
            @foreach ($items as $index => $item)
            <div class="row mt-1">
                <div class="form-group col-md-2">
                    <select name="item_id[]" class="form-control select2 item">
                        <option value="">Select</option>
                        @foreach(\App\Models\Items::dropdown('garage') as $i)
                        <option data-price="{{ $i->price }}" data-vat="{{ $i->vat }}" value="{{ $i->id }}" @if($i->id === $item->item_id) selected @endif>{{ $i->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    {!! Form::number('quantity[]', $item->quantity ?? null, ['class' => 'form-control qty']) !!}
                </div>
                <div class="form-group col-md-2">
                    {!! Form::number('rate[]', $item->rate ?? null, ['class' => 'form-control rate', 'step' => 'any']) !!}
                </div>
                <div class="form-group col-md-1">
                    {!! Form::number('vat[]', $item->vat ?? null, ['class' => 'form-control vat', 'step' => 'any']) !!}
                </div>
                <input type="hidden" name="vat_amount[]" value="0" class="vat_amount">
                <div class="form-group col-md-2">
                    {!! Form::number('item_total[]', $item->total_amount, ['class' => 'form-control amount', 'step' => 'any', 'readonly' => true]) !!}
                </div>
                <div class="form-group col-md-2">
                    <select name="charge_to[]" class="form-control select2">
                        <option value="">Select</option>
                        <option value="Company" @if($item->charge_to == 'Company') selected @endif>Company</option>
                        <option value="User" @if($item->charge_to == 'User') selected @endif>User</option>
                    </select>
                </div>
                <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                    <a href="javascript:void(0);" class="btn-remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endforeach
        </div>
                <div class="mt-2">
                    <button type="button" id="add-new-row" class="btn inv-add-item btn-sm">
                        <i class="fa fa-plus"></i> Add Item
                    </button>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-totals-area">
                <div class="inv-totals-notes">
                    <h4>Notes</h4>
                    {!! Form::textarea('description', $maintenance->description ?? null, [
                        'class' => 'form-control',
                        'rows' => 4,
                        'placeholder' => 'Notes about maintenance performed...'
                    ]) !!}
                </div>
                <div class="inv-totals">
                    <div class="inv-summary-row">
                        <span>Subtotal (excl. VAT)</span>
                        <span class="inv-summary-value"><span id="subtotal_display">0.00</span></span>
                    </div>
                    <div class="inv-summary-row">
                        <span>VAT Amount</span>
                        <span class="inv-summary-value"><span id="vat_total_display">0.00</span></span>
                    </div>
                    <div class="inv-summary-row inv-summary-total">
                        <span>Total Due</span>
                        <span class="inv-summary-value"><span id="total_display">0.00</span> {{ $currencyCode }}</span>
                    </div>
                    <input type="hidden" name="subtotal" id="subtotal" value="0">
                    <input type="hidden" name="vat_total" id="vat_total" value="0">
                    <input type="hidden" name="total_cost" id="total" value="0">
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="action-btn pt-3">
        {!! Form::submit('Save Maintenance Record', ['class' => 'btn btn-primary']) !!}
    </div>


<script>
$(document).ready(function() {
    function syncSummaryDisplay() {
        var subtotal = parseFloat($('#subtotal').val()) || 0;
        var vat = parseFloat($('#vat_total').val()) || 0;
        var total = parseFloat($('#total').val()) || 0;
        $('#subtotal_display').text(subtotal.toFixed(2));
        $('#vat_total_display').text(vat.toFixed(2));
        $('#total_display').text(total.toFixed(2));
    }

    if (typeof window.setTotal === 'function' && !window.setTotal._invPatched) {
        var originalSetTotal = window.setTotal;
        window.setTotal = function() {
            originalSetTotal.apply(this, arguments);
            syncSummaryDisplay();
        };
        window.setTotal._invPatched = true;
    }

    // Initialize select2
    $('.select2').select2({
        allowClear: true,
        dropdownParent: $('#formajax'),
    });
    
    // Store jQuery objects for calculations
    const previousKm = $('#previous_km');
    const currentKm = $('#current_km');
    const maintenanceKm = $('#maintenance_km');
    const overdueKm = $('#overdue_km');
    const costPerKm = $('#cost_per_km');
    const overdueCost = $('#overdue_cost');
    const riderInfo = $('#rider_info');
    const riderIdHidden = $('#rider_id_hidden');
    const isGarageCustomer = @json(($bike->rentalCompany?->customer_type) == 'garage');
    
    function calculateOverdue() {
        const prev = parseFloat(previousKm.val());
        const current = parseFloat(currentKm.val());
        const maintenanceInterval = parseFloat(maintenanceKm.val());
        overdueCost.val('');
        overdueKm.val('');
        
        if (!isNaN(prev) && !isNaN(current) && !isNaN(maintenanceInterval)) {
            // Calculate overdue: Current - Previous - Maintenance Interval
            const overdue = current - prev - maintenanceInterval;
            
            // Only show positive overdue (if overdue > 0)
            overdueKm.val(overdue > 0 ? overdue.toFixed(3) : '0.000');
            
            // Calculate total cost if cost per km is provided
            const cost = parseFloat(costPerKm.val()) || 0;
            if (cost && overdue > 0) {
                overdueCost.val((overdue * cost).toFixed(2));
            } else {
                overdueCost.val('0.00');
            }
        }
    }
    
    // Add event listeners to all calculation fields
    previousKm.on('input change', calculateOverdue);
    currentKm.on('input change', calculateOverdue);
    maintenanceKm.on('input change', calculateOverdue);
    costPerKm.on('input change', calculateOverdue);
    
    // Initial calculations
    calculateOverdue();

    function toggleOdometerFields() {
        const type = $('#maintenance_type').val();
        const $odometer = $('#odometer-fields');
        const isScheduled = type === 'Scheduled';

        if (isScheduled) {
            $odometer.show();
            $('#current_km, #maintenance_km, #cost_per_km').prop('required', true);
        } else {
            $odometer.hide();
            $('#current_km, #maintenance_km, #cost_per_km').prop('required', false);
            if (type === 'Repairs') {
                $('#current_km, #overdue_km, #overdue_cost').val('');
                $('#cost_per_km').val('0');
                $('#overdue_km').val('0');
                $('#overdue_cost').val('0.00');
            }
        }
    }

    $('#maintenance_type').on('change', toggleOdometerFields);
    toggleOdometerFields();

    $('.row').each(function() {
        setItemTotal($(this));
    });
    setTotal();
    syncSummaryDisplay();

    toggleRiderChargeOption(isGarageCustomer);
});

function toggleRiderChargeOption(garageCustomer = false) {
    const riderText = $('#rider_info').val().trim();
    const noRider = riderText === 'No User Assigned';

    $('select[name="charge_to[]"]').each(function () {
        const riderOption = $(this).find('option[value="User"]');
        const companyOption = $(this).find('option[value="Company"]');


        if (noRider) {
            riderOption.prop('disabled', true);

            // If currently selected, reset it
            if ($(this).val() === 'User') {
                $(this).val('').trigger('change');
            }
        } else {
            riderOption.prop('disabled', false);
        }
        if(garageCustomer) {
            companyOption.prop('disabled', true);
            $(this).val('User').trigger('change');
        }else {
            companyOption.prop('disabled', false);
        }
    });
}

</script>

{!! Form::close() !!}

@php
$currencyCode = \App\Helpers\Currency::code();
$invItemsCols = '1.8fr .7fr .8fr .6fr 1fr 1fr .5fr';
@endphp

@include('invoices.partials.invoice_form_styles', ['invItemsCols' => $invItemsCols])

<div class="inv-form-wrap">
    <div class="row g-3">
        <div class="col-lg-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-wrench"></i> Maintenance Details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Bike</label>
                        {!! Form::text('bike_info', $bike->emirates .'-'. $bike->plate, ['class'=>'form-control', 'readonly' => true ]) !!}
                        <input type="hidden" name="bike_id" value="{{ $bike->id }}"/>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Rider</label>
                        {!! Form::hidden('rider_id',$bike->rider? $bike->rider->id : null) !!}
                        {!! Form::text('rider_info', $bike->rider? $bike->rider->rider_id.'-'.$bike->rider->name : 'No Rider Assigned', ['class' => 'form-control', 'readonly' => true]) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Maintenance Date</label>
                        {!! Form::date('maintenance_date', now(), ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Billing Month</label>
                        {!! Form::month('billing_month', now(), ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Garage</label>
                        <select name="garage_id" class="form-control select2" required>
                            <option value="">Select</option>
                            @foreach (App\Models\Garages::where('status',1)->get() as $garage)
                                <option value="{{ $garage->id }}">{{ $garage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Maintenance Type</label>
                        <select name="maintenance_type" id="maintenance_type" class="form-control select2" required>
                            <option value="">Select</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Repairs">Repairs</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        @include('partials.universal_document_upload', [
                          'name' => 'attachment',
                          'label' => 'Attachment',
                          'required' => false,
                          'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx',
                          'inputClass' => 'form-control',
                          'showHint' => true,
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" id="odometer-fields" style="display: none;">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-tachometer-alt"></i> Odometer</h6>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Previous Reading</label>
                        <div class="input-group">
                            <span class="input-group-text">KM</span>
                            {!! Form::number('previous_km', $bike->current_km ?? null, [
                                'class' => 'form-control odometer-input',
                                'step' => 'any',
                                'readonly' => true,
                                'min' => '0',
                                'id' => 'previous_km',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Current Reading</label>
                        <div class="input-group">
                            <span class="input-group-text">KM</span>
                            {!! Form::number('current_km', null, [
                                'class' => 'form-control odometer-input',
                                'step' => 'any',
                                'min' => '0',
                                'id' => 'current_km',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Maintenance Interval</label>
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
                    <div class="col-md-2">
                        <label class="form-label">Overdue Reading</label>
                        <div class="input-group">
                            <span class="input-group-text">KM</span>
                            {!! Form::number('overdue_km', null, [
                                'class' => 'form-control odometer-input',
                                'step' => 'any',
                                'readonly' => true,
                                'id' => 'overdue_km'
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cost Per Overdue KM</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ $currencyCode }}</span>
                            {!! Form::number('overdue_cost_per_km', 1, [
                                'class' => 'form-control odometer-input',
                                'step' => '0.01',
                                'min' => '0',
                                'id' => 'cost_per_km',
                                'placeholder' => '0.00'
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Overdue Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ $currencyCode }}</span>
                            {!! Form::number('overdue_cost', null, [
                                'class' => 'form-control odometer-input',
                                'step' => '0.01',
                                'readonly' => true,
                                'id' => 'overdue_cost'
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="overdue_paidby" value="Rider" id="charge_overdue_rider">
                            <label class="form-check-label fw-bold" for="charge_overdue_rider">Charge Overdue to Rider</label>
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
                    <div class="row mt-1">
                        <div class="form-group col-md-2">
                            <select name="item_id[]" class="form-control select2 item">
                                <option value="">Select</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}"
                                    data-price="{{ $item->price }}"
                                    data-vat="{{ $item->vat }}">
                                        {{ $item->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::number('quantity[]', 1, ['class' => 'form-control qty']) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::number('rate[]', 0, ['class' => 'form-control rate', 'step' => 'any']) !!}
                        </div>
                        <div class="form-group col-md-1">
                            {!! Form::number('vat[]', 0, ['class' => 'form-control vat', 'step' => 'any']) !!}
                        </div>
                        <input type="hidden" name="vat_amount[]" value="0" class="vat_amount">
                        <div class="form-group col-md-2">
                            {!! Form::number('item_total[]', null, ['class' => 'form-control amount', 'step' => 'any']) !!}
                        </div>
                        <div class="form-group col-md-2">
                            <select name="charge_to[]" class="form-control select2">
                                <option value="">Select</option>
                                <option value="Company">Company</option>
                                <option value="User">User</option>
                            </select>
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                            <a href="javascript:void(0);" class="btn-remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
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
                    {!! Form::textarea('description', null, [
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

    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });
    const previousKm = $('#previous_km');
    const currentKm = $('#current_km');
    const maintenanceKm = $('#maintenance_km');
    const overdueKm = $('#overdue_km');
    const costPerKm = $('#cost_per_km');
    const overdueCost = $('#overdue_cost');

    function calculateOverdue() {
        const prev = parseFloat(previousKm.val()) ;
        const current = parseFloat(currentKm.val()) ;
        const maintenanceInterval = parseFloat(maintenanceKm.val());
        overdueCost.val('');
        overdueKm.val('');

        if (!isNaN(prev) && !isNaN(current) && !isNaN(maintenanceInterval)) {
            const overdue = current - prev - maintenanceInterval;
            overdueKm.val(overdue > 0 ? overdue.toFixed(3) : '0.000');
            const cost = parseFloat(costPerKm.val()) || 0;
            if (cost && overdue > 0) {
                overdueCost.val((overdue * cost).toFixed(2));
            } else {
                overdueCost.val('0.00');
            }
        }
    }

    previousKm.on('input change', calculateOverdue);
    currentKm.on('input change', calculateOverdue);
    maintenanceKm.on('input change', calculateOverdue);
    costPerKm.on('input change', calculateOverdue);
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

    $('#rows-container .row').each(function() {
        setItemTotal($(this));
    });
    setTotal();
    syncSummaryDisplay();
    toggleRiderChargeOption();
});

function toggleRiderChargeOption() {
    const riderText = $('#rider_info').val().trim();
    const noRider = riderText === 'No Rider Assigned';

    $('select[name="charge_to[]"]').each(function () {
        const riderOption = $(this).find('option[value="User"]');

        if (noRider) {
            riderOption.prop('disabled', true);
            if ($(this).val() === 'User') {
                $(this).val('').trigger('change');
            }
        } else {
            riderOption.prop('disabled', false);
        }
    });
}
</script>

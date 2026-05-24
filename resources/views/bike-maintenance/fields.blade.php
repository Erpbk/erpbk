 <div class="card-body">
        <div class="row">
            {{-- Bike Information (Read-only) --}}
            <div class="form-group col-md-2">
                {!! Form::label('bike_info', 'Bike') !!}
                {!! Form::text('bike_info', $bike->emirates .'-'. $bike->plate, ['class'=>'form-control', 'readonly' => true ]) !!}
                <input type="hidden" name="bike_id" value="{{ $bike->id }}"/>
            </div>

            {{-- Rider Information (Read-only) --}}
            <div class="form-group col-md-4">
                {!! Form::label('rider_info', 'Rider') !!}
                {!! Form::hidden('rider_id',$bike->rider? $bike->rider->id : null) !!}
                {!! Form::text('rider_info', $bike->rider? $bike->rider->rider_id.'-'.$bike->rider->name : 'No Rider Assigned', ['class' => 'form-control', 'readonly' => true]) !!}
            </div>

            {{-- Maintenance Date --}}
            <div class="form-group col-md-3">
                {!! Form::label('maintenance_date', 'Maintenance Date') !!}
                {!! Form::date('maintenance_date', now(), ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Attachment --}}
            <div class="form-group col-md-3">
                {!! Form::label('attachment', 'Attachment') !!}
                {!! Form::file('attachment', [
                    'class' => 'form-control',
                    'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx'
                ]) !!}
            </div>

            {{-- Garage --}}
            <div class="form-group col-md-3">
                {!! Form::label('garage', 'Garage:') !!}
                <select name="garage_id" class="form-control select2" required>
                    <option value="">Select</option>
                    @foreach (App\Models\Garages::where('status',1)->get() as $garage)
                        <option value="{{ $garage->id }}">{{ $garage->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Billing Month --}}
            <div class="form-group col-md-3">
                {!! Form::label('billing_month', 'Billing Month',) !!}
                {!! Form::month('billing_month', now(), ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Overdue Paid By --}}
            <div class="form-group col-md-3">
                <div class="form-check mt-5">
                    {!! Form::checkbox('overdue_paidby', 'Rider', null, [
                        'class' => 'form-check-input',
                        'id' => 'charge_rider'
                    ]) !!}
                    {!! Form::label('charge_rider', 'Charge Overdue to Rider', [
                        'class' => 'fw-bold'
                    ]) !!}
                </div>
            </div>

            {{-- Description --}}
            <div class="form-group col-md-6">
                {!! Form::label('description', 'Notes') !!}
                {!! Form::textarea('description', null, [
                    'class' => 'form-control', 
                    'rows' => 3,
                    'placeholder' => 'Notes about maintenance performed...'
                ]) !!}
            </div>
        </div>
        <div class="row my-5">
            

            {{-- Previous KM --}}
            <div class="form-group col-md-2">
                {!! Form::label('previous_km', 'Previous Reading') !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('previous_km', $bike->previous_km ?? null, [
                        'class' => 'form-control', 
                        'step' => 'any', 
                        'readonly' => true,
                        'min' => '0',
                        'id' => 'previous_km',
                    ]) !!}
                </div>
            </div>

            {{-- Current KM --}}
            <div class="form-group col-md-2">
                {!! Form::label('current_km', 'Current Reading') !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('current_km', null, [
                        'class' => 'form-control', 
                        'step' => 'any', 
                        'min' => '0',
                        'required' => true,
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
                        'class' => 'form-control', 
                        'step' => 'any', 
                        'required' => true,
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
                    {!! Form::number('overdue_km', null, [
                        'class' => 'form-control', 
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
                    {!! Form::number('overdue_cost_per_km', 1, [
                        'class' => 'form-control', 
                        'step' => '0.01', 
                        'required' => true,
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
                        'class' => 'form-control', 
                        'step' => '0.01',
                        'readonly' => true,
                        'id' => 'overdue_cost'
                    ]) !!}
                </div>
            </div>
        </div>
    </div>
    
    <h5 class="my-3">Maintenance Items</h5>
    <div class="scrollbar p-2 border rounded">
        <div class="row">
            <div class="form-group col-md-2">
                {!! Form::label('item', 'Item') !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('qty', 'Qty') !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('rate', 'Rate') !!}
            </div>
            <div class="form-group col-md-1">
                {!! Form::label('vat', 'VAT(%)') !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('amount', 'Total Amount:') !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('charge_to', 'Charge To') !!}
            </div>
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
                        <option value="Rider">Rider</option>
                    </select>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
        </div>
    </div>
<div>
        <button type="button" id="add-new-row" class="btn btn-success btn-sm">Add Item</button>
    </div>
    <br>

    <div class="d-flex justify-content-between align-items-center gap-3">
        <div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="input-group">
                <span class="input-group-text bg-light">Subtotal</span>
                <input type="number" name="subtotal" class="form-control" id="subtotal" readonly style="min-width: 150px;">
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light">VAT Amount</span>
                <input type="number" name="vat_total" class="form-control" id="vat_total" readonly style="min-width: 150px;">
            </div>
            <div class="input-group">
                <span class="input-group-text bg-primary text-white">Total</span>
                <input type="number" name="total_cost" class="form-control" id="total" readonly style="min-width: 150px; font-weight: bold;" >
            </div>
        </div>
    </div>

    <div class="action-btn pt-3">
        {!! Form::submit('Save Maintenance Record', ['class' => 'btn btn-primary']) !!}
    </div>


    <script>
$(document).ready(function() {
    // Store jQuery objects for calculations
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
    $('#rows-container .row').each(function() {
        setItemTotal($(this));
    });
    setTotal();

    toggleRiderChargeOption()
});

function toggleRiderChargeOption() {
    const riderText = $('#rider_info').val().trim();
    const noRider = riderText === 'No Rider Assigned';

    $('select[name="charge_to[]"]').each(function () {
        const riderOption = $(this).find('option[value="Rider"]');

        if (noRider) {
            riderOption.prop('disabled', true);

            // If currently selected, reset it
            if ($(this).val() === 'Rider') {
                $(this).val('').trigger('change');
            }
        } else {
            riderOption.prop('disabled', false);
        }
    });
}
</script>
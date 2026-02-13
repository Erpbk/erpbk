{!! Form::open(['route' => ['bikeMaintenance.store'], 'method' => 'post', 'id' => 'formajax', 'files' => true]) !!}
    @csrf
    
    <div class="card-body">
        <div class="row">
            {{-- Bike Information --}}
            <div class="form-group col-md-3">
                {!! Form::label('bike_info', 'Bike') !!}
                <select name="bike_id" class="form-control select2 bike" id="bike_select">
                    <option value="">Select</option>
                    @foreach(\App\Models\Bikes::where('status', 1)->get() as $bike)
                    <option value="{{ $bike->id }}"
                        data-rider="{{ $bike->rider ? $bike->rider->rider_id .'-'. $bike->rider->name : 'No Rider Assigned' }}"
                        data-rider-id="{{ $bike->rider->id ?? null }}"
                        data-previous-km="{{ $bike->previous_km }}"
                        data-maintenance-km="{{ $bike->maintenance_km }}">
                            {{ $bike->emirates.'-'.$bike->plate }}
                    </option>
                    @endforeach
                </select>
                {!! Form::hidden('rider_id', null, ['id' => 'rider_id_hidden']) !!}
            </div>

            {{-- Rider Information (Read-only) --}}
            <div class="form-group col-md-3">
                {!! Form::label('rider_info', 'Rider') !!}
                {!! Form::text('rider_info', "No Rider Assigned", ['class' => 'form-control rider', 'readonly' => true, 'id' => 'rider_info']) !!}
            </div>

            {{-- Maintenance Date --}}
            <div class="form-group col-md-3">
                {!! Form::label('maintenance_date', 'Maintenance Date', ['class' => 'required']) !!}
                {!! Form::date('maintenance_date', null, ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Attachment --}}
            <div class="form-group col-md-3">
                {!! Form::label('attachment', 'Attachment:') !!}
                {!! Form::file('attachment', [
                    'class' => 'form-control',
                    'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx'
                ]) !!}
            </div>

            {{-- Previous KM --}}
            <div class="form-group col-md-3">
                {!! Form::label('previous_km', 'Previous Reading', ['class' => 'required']) !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('previous_km', null, [
                        'class' => 'form-control', 
                        'step' => 'any', 
                        'readonly' => true,
                        'min' => '0',
                        'id' => 'previous_km',
                    ]) !!}
                </div>
            </div>

            {{-- Current KM --}}
            <div class="form-group col-md-3">
                {!! Form::label('current_km', 'Current Reading') !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('current_km',  null, [
                        'class' => 'form-control', 
                        'step' => 'any', 
                        'min' => '0',
                        'id' => 'current_km',
                    ]) !!}
                </div>
            </div>

            {{-- Maintenance KM (interval for maintenance) --}}
            <div class="form-group col-md-3">
                {!! Form::label('maintenance_km', 'Maintenance Interval', ['class' => 'required']) !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('maintenance_km', null, [
                        'class' => 'form-control', 
                        'step' => 'any', 
                        'required' => true,
                        'min' => '0',
                        'id' => 'maintenance_km',
                    ]) !!}
                </div>
            </div>

            {{-- Overdue KM (calculated field) --}}
            <div class="form-group col-md-3">
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
            <div class="form-group col-md-3">
                {!! Form::label('overdue_cost_per_km', 'Cost Per Overdue KM', ['class' => 'required']) !!}
                <div class="input-group">
                    <span class="input-group-text">AED</span>
                    {!! Form::number('overdue_cost_per_km', 1, [
                        'class' => 'form-control', 
                        'step' => '0.01', 
                        'required' => true,
                        'min' => '0',
                        'id' => 'cost_per_km',
                        'placeholder' => '0.00'
                    ]) !!}
                </div>
            </div>

            {{-- Total Overdue Cost (calculated field) --}}
            <div class="form-group col-md-3">
                {!! Form::label('overdue_cost', 'Overdue Cost') !!}
                <div class="input-group">
                    <span class="input-group-text">AED</span>
                    {!! Form::number('overdue_cost', null, [
                        'class' => 'form-control', 
                        'step' => '0.01',
                        'readonly' => true,
                        'id' => 'overdue_cost'
                    ]) !!}
                </div>
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
            {{-- <div class="form-group col-md-3">
                {!! Form::label('overdue_paidby', 'Overdue Cost Paid By') !!}
                {!! Form::select('overdue_paidby', [
                    'Company' => 'Company',
                    'Rider' => 'Rider',
                ], null, ['class' => 'form-control select2', 'placeholder' => 'Select who paid...']) !!}
            </div> --}}

            {{-- Description --}}
            <div class="form-group col-md-6">
                {!! Form::label('description', 'Notes') !!}
                {!! Form::textarea('description', null, [
                    'class' => 'form-control', 
                    'rows' => 3,
                    'placeholder' => 'Any notes about maintenance performed...'
                ]) !!}
            </div>
        </div>
    </div>
    
    <h5 class="my-3">Maintenance Items</h5>
    <div class="scrollbar p-2">
        <div id="row-container">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-3">
        <div>
            <button type="button" id="add-row" class="btn btn-success btn-sm">Add Item</button>
        </div>
        <div class="d-flex align-items-center">
            <div class="input-group flex-nowrap">
                <span class="input-group-text">Maintenance Cost</span>
                <input type="number" name="total_cost" value="0" class="form-control" id="maintenance_total_cost" readonly style="min-width: 120px;">
            </div>
        </div>
    </div>

    <div class="action-btn pt-3">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
        {!! Form::submit('Save Maintenance Record', ['class' => 'btn btn-primary']) !!}
    </div>

{!! Form::close() !!}

<script>
$(document).ready(function() {
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
    $('.row').each(function() {
        setItemTotal($(this));
    });
    setTotal();

    $(document).on('change', '#bike_select', function(){
        const selectedOption = $(this).find('option:selected');
        const riderData = selectedOption.data('rider');
        const riderId = selectedOption.data('rider-id');
        const previousKmData = selectedOption.data('previous-km');
        const maintenanceKmData = selectedOption.data('maintenance-km');

        
        // Update rider information
        riderInfo.val(riderData);
        riderIdHidden.val(riderId);
        previousKm.val(previousKmData);
        maintenanceKm.val(maintenanceKmData);
        
        calculateOverdue();
        $(this).select2('close');
    });

    $(document).on('input change', '.qty, .rate, .discount, .vat', function() {
        const row = $(this).closest('.row');
        setItemTotal(row);
        setTotal();
    });

    $(document).on('change', '.item', function() {
        const row = $(this).closest('.row');
        const selectedOption = $(this).find('option:selected');
        const itemPrice = parseFloat(selectedOption.data('price')) || 0;
        row.find('.rate').val(itemPrice.toFixed(2));
        setItemTotal(row);
        setTotal();
    });

    $(document).on('click', '.remove-row', function(e){
        e.preventDefault();
        $(this).closest('.row').remove();
        setTotal();
    });

    // Add row button click
    $('#add-row').click(addNewRow);
});

function addNewRow(){
    // for now we dont need these fields, if needed add them later
    // <div class="form-group col-md-1">
    //     {!! Form::label('discount', 'Discount') !!}
    //     {!! Form::number('discount[]', 0, ['class' => 'form-control discount', 'step' => 'any']) !!}
    // </div>
    const newRow = $(`
        <div class="row">
            <div class="form-group col-md-2">
                {!! Form::label('item', 'Item') !!}
                <select name="item_id[]" class="form-control select2 item">
                    <option value="">Select</option>
                    @foreach(\App\Models\Items::where('status', 1)->get() as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('qty', 'Qty') !!}
                {!! Form::number('quantity[]', 1, ['class' => 'form-control qty']) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('rate', 'Rate') !!}
                {!! Form::number('rate[]', 0, ['class' => 'form-control rate', 'step' => 'any']) !!}
            </div>
            <div class="form-group col-md-1">
                {!! Form::label('vat', 'VAT(%)') !!}
                {!! Form::number('vat[]', 0, ['class' => 'form-control vat', 'step' => 'any']) !!}
            </div>
            <input type="hidden" name="vat_amount[]" value="0" class="vat_amount">
            <div class="form-group col-md-2">
                {!! Form::label('amount', 'Total Amount:') !!}
                {!! Form::number('item_total[]', null, ['class' => 'form-control item_total', 'step' => 'any']) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('charge_to', 'Charge To') !!}

                <select name="charge_to[]" class="form-control select2">
                    <option value="">Select</option>
                    <option value="Company">Company</option>
                    <option value="Rider">Rider</option>
                </select>
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger remove-row"><i class="fa fa-trash"></i></a>
            </div>
        </div>
    `);
    
    // Append to container
    $('#row-container').append(newRow);
    
    // Initialize select2 for the new row's select element
    newRow.find('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });
    
    // Calculate total
    setItemTotal(newRow);
    setTotal();
}

function setItemTotal(row) {
    const qty = parseFloat(row.find('.qty').val()) || 0;
    const rate = parseFloat(row.find('.rate').val()) || 0;
    const discount = parseFloat(row.find('.discount').val()) || 0;
    const vat = parseFloat(row.find('.vat').val()) || 0;
    const vatAmount = row.find('.vat_amount');
    
    let subtotal = qty * rate;
    if (discount > 0) {
        subtotal -= discount;
    }
    let amount = 0;
    if (vat > 0) {
        amount= subtotal * (vat / 100);
        subtotal += amount;
        vatAmount.val(amount);
    }
    
    row.find('.item_total').val(subtotal.toFixed(2));
}

function setTotal() {
    let total = 0;
    
    // Calculate sum of all item totals
    $('.row').each(function() {
        const itemTotal = parseFloat($(this).find('.item_total').val()) || 0;
        total += itemTotal;
    });
    $('#maintenance_total_cost').val(total.toFixed(2));
}
</script>
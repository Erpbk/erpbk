{!! Form::open(['route' => ['bikeMaintenance.store'], 'method' => 'post', 'id' => 'formajax', 'files' => true]) !!}

    @csrf
    
    <div class="card-body">
        <div class="row">
            {{-- Bike Information --}}
            <div class="form-group col-md-3">
                {!! Form::label('bike_info', 'Bike') !!}
                <select name="bike_id" class="form-control select2 bike" id="bike_select" required>
                    <option value="">Select</option>
                    @foreach($bikes as $bike)
                    <option value="{{ $bike->id }}" @if($bikee && $bikee->id == $bike->id) selected @endif>
                        {{ $bike->emirates.'-'.$bike->plate }}
                    </option>
                    @endforeach
                </select>
                {!! Form::hidden('rider_id', null, ['id' => 'rider_id_hidden']) !!}
                {!! Form::hidden('rental_company_id', null, ['id' => 'rental_id_hidden']) !!}
            </div>

            {{-- Assigned users from bike history + current assignment --}}
            <div class="form-group col-md-3">
                {!! Form::label('assignee_select', 'Assigned To') !!}
                <select id="assignee_select" class="form-control bm-assignee-select" data-placeholder="Select assigned user">
                    <option value="">No User Assigned</option>
                </select>
            </div>

            {{-- Maintenance Date --}}
            <div class="form-group col-md-3">
                {!! Form::label('maintenance_date', 'Maintenance Date') !!}
                {!! Form::date('maintenance_date', now(), ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Attachment --}}
            <div class="form-group col-md-3">
                @include('partials.universal_document_upload', [
                  'name' => 'attachment',
                  'label' => 'Attachment:',
                  'required' => false,
                  'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx',
                  'inputClass' => 'form-control',
                  'showHint' => true,
                ])
            </div>

            {{-- Billing Month --}}
            <div class="form-group col-md-3">
                {!! Form::label('billing_month', 'Billing Month') !!}
                {!! Form::month('billing_month', now(), ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Garage --}}
            <div class="form-group col-md-3">
                {!! Form::label('garage', 'Garage:') !!}
                <select name="garage_id" class="form-control select2" required>
                    <option value="">Select</option>
                    @foreach ($garages as $garage)
                        <option value="{{ $garage->id }}">{{ $garage->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Maintenance Type --}}
            <div class="form-group col-md-3">
                {!! Form::label('maintenance_type', 'Maintenance Type', ['class' => 'required']) !!}
                <select name="maintenance_type" id="maintenance_type" class="form-control select2" required>
                    <option value="">Select</option>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Repairs">Repairs</option>
                </select>
            </div>

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
        <div class="row my-5" id="odometer-fields" style="display: none;">
            {{-- Previous KM --}}
            <div class="form-group col-md-2">
                {!! Form::label('previous_km', 'Previous Reading') !!}
                <div class="input-group">
                    <span class="input-group-text">KM</span>
                    {!! Form::number('previous_km', null, [
                        'class' => 'form-control odometer-input', 
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
                    {!! Form::number('current_km',  null, [
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
                    {!! Form::number('maintenance_km', 2000, [
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
                    {!! Form::number('overdue_km', null, [
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
                    {!! Form::number('overdue_cost_per_km', 1, [
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
                        name="overdue_paidby" value="Rider" id="charge_overdue_rider">
                    <label class="form-check-label fw-bold" for="charge_overdue_rider">Charge Overdue to Rider</label>
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
                        <option data-price="{{ $item->price }}"
                            data-vat="{{ $item->vat }}"
                            value="{{ $item->id }}">
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
                    {{-- @if($type == 'garage')
                    <input name="charge_to[]" class="form-control" value="User" readonly>
                    @else --}}
                    <select name="charge_to[]" class="form-control select2 charge_to">
                        <option value="">Select</option>
                        <option value="Company">Company</option>
                        <option value="User">User</option>
                    </select>
                    {{-- @endif --}}
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

{!! Form::close() !!}

@include('bike-maintenance.partials.assignee_select_script')

<script>
$(document).ready(function() {
    $('.select2').not('#assignee_select, .bm-assignee-select').select2({
        allowClear: true,
        dropdownParent: $('#formajax'),
    });

    const previousKm = $('#previous_km');
    const currentKm = $('#current_km');
    const maintenanceKm = $('#maintenance_km');
    const overdueKm = $('#overdue_km');
    const costPerKm = $('#cost_per_km');
    const overdueCost = $('#overdue_cost');

    window.calculateOverdue = function () {
        const prev = parseFloat(previousKm.val());
        const current = parseFloat(currentKm.val());
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
    };

    previousKm.on('input change', window.calculateOverdue);
    currentKm.on('input change', window.calculateOverdue);
    maintenanceKm.on('input change', window.calculateOverdue);
    costPerKm.on('input change', window.calculateOverdue);
    window.calculateOverdue();

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

    $('#formajax').on('submit', function () {
        $('.charge_to').each(function () {
            $(this).prop('disabled', false);
        });
    });

    window.toggleRiderChargeOption = function (garageCustomer = false, noAssignee = false, preserveChargeTo = false) {
        $('select[name="charge_to[]"]').each(function () {
            const riderOption = $(this).find('option[value="User"]');
            const companyOption = $(this).find('option[value="Company"]');

            if (noAssignee) {
                riderOption.prop('disabled', true);
                if (!preserveChargeTo || $(this).val() === 'User') {
                    $(this).val('Company').trigger('change');
                }
            } else {
                riderOption.prop('disabled', false);
            }

            if (garageCustomer) {
                companyOption.prop('disabled', true);
                if (!preserveChargeTo || $(this).val() !== 'User') {
                    $(this).val('User').trigger('change');
                }
            } else {
                companyOption.prop('disabled', false);
            }

            if (!preserveChargeTo && !noAssignee && !garageCustomer) {
                $(this).val('').trigger('change');
            }
        });
    };

    BikeMaintenanceAssignees.bind({
        bikeSelect: '#bike_select',
        onBikeLoaded: function () {
            window.calculateOverdue();
        }
    });
});
</script>
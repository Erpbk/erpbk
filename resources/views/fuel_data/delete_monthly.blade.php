{!! Form::open(['route' => 'fuel_data.deleteMonthly', 'method' => 'post', 'id' => 'formajax']) !!}
@csrf

<div class="row">
    <div class="col-md-12 mb-3">
        <label for="billing_month" class="form-label">Billing Month <span class="text-danger">*</span></label>
        <input type="month"
            name="billing_month"
            id="billing_month"
            class="form-control"
            value="{{ old('billing_month') }}"
            required>
        <small class="text-muted">Required. Fuel transactions in this month are deleted, narrowed by the optional filters below.</small>
    </div>

    <div class="col-md-12 mb-3">
        <label for="fuel_company_id" class="form-label">Fuel Company</label>
        <select name="fuel_company_id" id="delete_monthly_fuel_company_id" class="form-control select2">
            <option value="">All fuel companies</option>
            @foreach($fuelCompanies as $fuelCompany)
            <option value="{{ $fuelCompany->id }}" {{ old('fuel_company_id') == $fuelCompany->id ? 'selected' : '' }}>{{ $fuelCompany->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Optional. Leave empty to delete fuel data across every fuel company, matching the other filters.</small>
    </div>

    <div class="col-md-12 mb-3">
        <label for="rider_id" class="form-label">Rider</label>
        <select name="rider_id" id="delete_monthly_rider_id" class="form-control select2">
            <option value="">All riders</option>
            @foreach($riders as $rider)
            <option value="{{ $rider->id }}" {{ old('rider_id') == $rider->id ? 'selected' : '' }}>{{ $rider->rider_id }} - {{ $rider->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Optional. Leave empty to delete fuel data for every rider in the selected month.</small>
    </div>

    <div class="col-md-12">
        <div class="alert alert-warning mb-0">
            <i class="ti ti-alert-triangle me-1"></i>
            This will permanently remove matching fuel transactions and rebuild related ledger entries. This cannot be undone from this screen.
        </div>
    </div>
</div>

<div class="action-btn mt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Delete Monthly Data', ['class' => 'btn btn-danger', 'onclick' => "return confirm('Are you sure you want to delete this monthly fuel data?');"]) !!}
</div>

{!! Form::close() !!}

<script>
    $(document).ready(function() {
        var dropdownParent = $('#modalTopbody').length ? $('#modalTopbody') : $(document.body);

        $('#delete_monthly_fuel_company_id').select2({
            allowClear: true,
            placeholder: 'All fuel companies',
            dropdownParent: dropdownParent
        });

        $('#delete_monthly_rider_id').select2({
            allowClear: true,
            placeholder: 'All riders',
            dropdownParent: dropdownParent
        });
    });
</script>

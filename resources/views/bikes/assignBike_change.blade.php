@php
$bike = \App\Models\Bikes::find($id);
$vehicleTypeName = $bike->vehicle_type ?? '';
$rider = null;
$company = null;
if ($bike && $bike->rider_id) {
$rider = $bike->rider;
}
if ($bike && $bike->rental_company_id) {
$company = $bike->rentalCompany;
}


$assignFields = $assignFields ?? \App\Models\BikeCustomField::assignModalFields('change');
$inlineFields = $assignFields->filter(function ($f) {
if (($f->field_key ?? '') === 'notes') {
return false;
}
if ($f->kind === 'custom' && ($f->resolvedInputSpec()['type'] ?? '') === 'textarea') {
return false;
}

return true;
});
$wideFields = $assignFields->filter(function ($f) {
if (($f->field_key ?? '') === 'notes') {
return true;
}
if ($f->kind === 'custom' && ($f->resolvedInputSpec()['type'] ?? '') === 'textarea') {
return true;
}

return false;
});
@endphp

<script src="{{ asset('js/modal_custom.js') }}"></script>
<form action="{{ route('bikes.assignrider', $id) }}" method="post" id="formajax">
    @csrf
    <input type="hidden" name="bike_id" value="{{ $id }}" />

    <div class="row">
        @if($rider)
        <div class="col-md-3 form-group">
            <label>Rider</label>
            <input type="text" name="rider" class="form-control" readonly value="{{ $rider->rider_id . '-' . $rider->name }}">
        </div>
        <div class="col-md-3 form-group">
            <label>Project</label>
            <input type="text" name="customer_id" class="form-control" readonly value="{{ App\Models\Customers::find($bike->customer_id)->name ?? 'N/A' }}">
        </div>
        @endif
        @if($company)
        <div class="col-md-3 form-group">
            <label>Rental Company</label>
            <input type="text" class="form-control" readonly value="{{ $company->name ?? 'N/A' }}">
        </div>
        @endif

        @foreach($inlineFields as $field)
        @include('bikes._assign_modal_field', [
        'field' => $field,
        'assignContext' => 'change',
        'bike' => $bike,
        'rider' => $rider,
        ])
        @endforeach
    </div>

    @if($wideFields->isNotEmpty())
    <div class="row mt-3">
        @foreach($wideFields as $field)
        @include('bikes._assign_modal_field', [
        'field' => $field,
        'assignContext' => 'change',
        'bike' => $bike,
        'rider' => $rider,
        ])
        @endforeach
    </div>
    @endif

    <div class="row">
        <div class="col-md-12 mt-2">
            <button type="submit" class="btn btn-primary pull-right">Save</button>
        </div>
    </div>
</form>

<style>
    .hidden-field {
        display: none !important;
    }
</style>

<script>
    var vehicleTypeName = @json($vehicleTypeName);

    $(document).ready(function() {
        $('.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });
    });
</script>
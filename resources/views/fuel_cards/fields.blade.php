<!-- Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('card_number', 'Number:') !!}
    {!! Form::text('card_number', null, ['class' => 'form-control', 'readonly' => isset($fuelCard) ]) !!}
</div>

<!-- Company Field -->
<div class="form-group col-sm-6">
    {!! Form::label('card_type', 'Card type:') !!}
    {!! Form::text('card_type', null, ['class' => 'form-control']) !!}
</div>

<!-- Assigned To -->
<div class="form-group col-sm-6">
    {!! Form::label('assigned_to', 'Assigned To:') !!}
    <select name="assigned_to" class="form-control account-select select2">
        <option value="">Select</option>
        @foreach(\App\Models\Riders::where('status', 1)->get() as $user)
        <option value="{{ $user->id }}" 
            {{ old('assigned_to', isset($fuelCard) ? $fuelCard->assigned_to : '') == $user->id ? 'selected' : '' }}>
            {{ $user->rider_id.'-'.$user->name }}
        </option>
        @endforeach
    </select>
</div>

<!-- status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::select('status', 
        [ 'Active' => 'Active', 'Inactive' => 'Inactive'], 
        old('status', $fuelCard->status ?? ''), 
        ['class' => 'form-control select2', 'placeholder' => 'Select Status']
    ) !!}
</div>


<script type="text/javascript">

$(document).ready(function () {
    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });
});
</script>
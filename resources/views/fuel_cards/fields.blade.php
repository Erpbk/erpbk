<!-- Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('card_number', 'Number:', ['class' => 'required']) !!}
    {!! Form::text('card_number', null, ['class' => 'form-control']) !!}
</div>

<!-- Company Field -->
<div class="form-group col-sm-6">
    {!! Form::label('card_type', 'Card type:', ['class' => 'required']) !!}
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

<!-- Assign Date Field -->
<div class="form-group col-md-6">
    <label for="assign_date">Assign Date</label>
    <input type="date" name="assign_date" class="form-control">
</div>



<script type="text/javascript">

$(document).ready(function () {
    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });
});
</script>
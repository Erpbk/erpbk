<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="form-group col-sm-6">
    {!! Form::label('reference_number', 'Reference Number:', ['class' => 'required']) !!}
    {!! Form::text('reference_number', $legalCases->reference_number ?? '', ['class' => 'form-control', 'placeholder' => 'Reference Number', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:' , ['class' => 'required']) !!}
    {!! Form::date('date', $legalCases->date ?? now()->format('Y-m-d'), ['class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    <label class="required">Case Status:</label>
    <select class="form-control select2" id="case_status" name="case_status" required>
        <option value="">Select Status</option>
        @foreach($legalCaseStatuses as $status)
        <option value="{{ $status->name }}"
            {{ (isset($legalCases) && $legalCases->case_status == $status->name) ? 'selected' : '' }}>
            {{ $status->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
    {!! Form::month('billing_month', isset($legalCases) && $legalCases->billing_month ? \Carbon\Carbon::parse($legalCases->billing_month)->format('Y-m') : now()->format('Y-m'), ['class' => 'form-control' , 'required']) !!}
</div>
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    {!! Form::textarea('detail', $legalCases->detail ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3]) !!}
</div>

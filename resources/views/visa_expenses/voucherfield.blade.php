<script src="{{ asset('js/modal_custom.js') }}"></script>
<!-- Trip Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:' , ['class' => 'required']) !!}
    {!! Form::date('date', $data->date, ['class' => 'form-control']) !!}
</div>

<!-- Billing Month Field -->
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
    {!! Form::month('billing_month', \Carbon\Carbon::parse($data->billing_month)->format('Y-m'), ['class' => 'form-control']) !!}

</div>


<div class="form-group col-sm-6">
    <label class="">Visa Status:</label>
    <select class="form select select2" id="visa_status" name="visa_status" readonly>
        <option value=""></option>
        <option value="Job offer Letter" @if($data->visa_status == 'Job offer Letter') selected @endif>Job offer Letter</option>
        <option value="Labor Insurance" @if($data->visa_status == 'Labor Insurance') selected @endif>Labor Insurance</option>
        <option value="Work Permit" @if($data->visa_status == 'Work Permit') selected @endif>Work Permit</option>
        <option value="Work Man Insurance" @if($data->visa_status == 'Work Man Insurance') selected @endif>Work Man Insurance</option>
        <option value="Entry Permit (Inside)" @if($data->visa_status == 'Entry Permit (Inside)') selected @endif>Entry Permit (Inside)</option>
        <option value="Entry Permit (Outside)" @if($data->visa_status == 'Entry Permit (Outside)') selected @endif>Entry Permit (Outside)</option>
        <option value="Status Change" {{ (isset($visaExpenses) && $visaExpenses->visa_status == 'Status Change') ? 'selected' : '' }}>Status Change</option>
        <option value="Medical" @if($data->visa_status == 'Tawjeeh') selected @endif>Tawjeeh</option>
        <option value="Medical" @if($data->visa_status == 'Medical') selected @endif>Medical</option>
        <option value="Emirates ID + Residency" @if($data->visa_status == 'Emirates ID + Residency') selected @endif>Emirates ID + Residency</option>
        <option value="Emirates ID" @if($data->visa_status == 'Emirates ID') selected @endif>Emirates ID</option>
        <option value="Residency" @if($data->visa_status == 'Residency') selected @endif>Residency</option>
        <option value="Bike License" @if($data->visa_status == 'Bike License') selected @endif>Bike License</option>
        <option value="Violation" @if($data->visa_status == 'Violation') selected @endif>Violation</option>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="">Payment Status:</label>
    <select class="form select select2" id="payment_status" name="payment_status" required>
        <option value=""></option>
        <option value="Paid" @if($data->payment_status == 'paid') selected @endif>Paid</option>
        <option value="Unpaid" @if($data->payment_status == 'unpaid') selected @endif>Unpaid</option>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="readonly">Debit Account:</label>
    <select class="form-control select select2" id="rider_id" name="rider_id" readonly>
        <option value=""></option>
        @foreach(DB::table('accounts')->where('status' , 1)->get() as $a)
        <option value="{{ $a->id }}" @if($data->rider_id == $a->id) selected @endif>{{ $a->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Credit Account:</label>
    <select class="form-control" id="account_id" name="account" required>
        <option value=""></option>
        @php
        $bank = DB::table('accounts')->where('name', 'Bank')->first();
        $cash = DB::table('accounts')->where('name', 'Cash in Hand')->first();
        $recruiters = DB::table('accounts')->where('name', 'Recruiters')->first();

        $parentIds = [];
        if ($bank) $parentIds[] = $bank->id;
        if ($cash) $parentIds[] = $cash->id;
        if ($recruiters) $parentIds[] = $recruiters->id;
        @endphp

        @foreach(DB::table('accounts')
        ->where('status', 1)
        ->where(function($query) use ($parentIds) {
        $query->whereIn('parent_id', $parentIds)
        ->orWhere('id', 2172);
        })
        ->orderBy('id', 'asc')
        ->get() as $acc)
        <option value="{{ $acc->id }}">
            {{ $acc->name }}
        </option>
        @endforeach

    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Attachment</label>
    <input type="file" name="attach_file" class="form-control" required>
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'readonly']) !!}
    {!! Form::text('', 'AED ' . number_format($data->amount, 2), ['class' => 'form-control', 'readonly']) !!}
</div>


<!-- Detail Field -->
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'readonly']) !!}
    {!! Form::textarea('detail', $data->detail, ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'readonly']) !!}
</div>
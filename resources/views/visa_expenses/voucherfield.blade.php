@php
$visaStatuses = DB::table('visa_statuses')->where('is_active', 1)->orderBy('display_order', 'asc')->get();
@endphp
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
        @foreach ($visaStatuses as $status)
        <option value="{{ $status->name }}" @if($data->visa_status == $status->name) selected @endif>{{ $status->name }}</option>
        @endforeach
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
    <select class="form-control select select2" id="expense_account_id" name="expense_account_id" readonly>
        <option value=""></option>
        <option value="{{ \App\Helpers\HeadAccount::VISA_EXPENSE_ACCOUNT }}" selected>{{ DB::table('accounts')->where('id', \App\Helpers\HeadAccount::VISA_EXPENSE_ACCOUNT)->first()->name }}</option>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Credit Account:</label>
    <select class="form-control" id="account_id" name="account" required>
        <option value=""></option>
        @php
        $bank = DB::table('accounts')->where('name', 'cash & bank')->first();
        $parentIds = [];
        if ($bank) $parentIds[] = $bank->id;
        @endphp

        @foreach(DB::table('accounts')
        ->where('status', 1)
        ->where(function($query) use ($parentIds) {
        $query->whereIn('parent_id', $parentIds);
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
<div class="form-group col-sm-6">
    <label class="required">Document Expiry Date:</label>
    {!! Form::date('expiry_date', old('expiry_date', $data->expiry_date ?? null), ['class' => 'form-control' , 'required']) !!}
</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'readonly']) !!}
    {!! Form::text('', \App\Helpers\Currency::code() . ' ' . number_format((float) $data->amount, 2), ['class' => 'form-control', 'readonly']) !!}
</div>


<!-- Detail Field -->
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'readonly']) !!}
    {!! Form::textarea('detail', $data->detail, ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'readonly']) !!}
</div>
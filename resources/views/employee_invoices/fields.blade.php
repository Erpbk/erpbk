<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date" class="form-control"
            value="{{ isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d') }}"
            name="inv_date" placeholder="Invoice Date">
    </div>
    <div class="col-md-4 form-group">
        <label>Employee</label>
        {!! Form::select('employee_id', $employees, null, ['class' => 'form-select form-select-sm select2', 'id' => 'employee_id']) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Project</label>
        {!! Form::text('zone', null, ['class' => 'form-control', 'placeholder' => 'Project']) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Login Hours</label>
        {!! Form::text('login_hours', null, ['class' => 'form-control', 'placeholder' => 'Login Hours']) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Working Days</label>
        {!! Form::text('working_days', null, ['class' => 'form-control', 'placeholder' => 'Working Days']) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Perfect Attendance</label>
        {!! Form::text('perfect_attendance', null, ['class' => 'form-control', 'placeholder' => 'Perfect Attendance']) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Rejection</label>
        {!! Form::text('rejection', null, ['class' => 'form-control', 'placeholder' => 'Rejection']) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Performance</label>
        {!! Form::text('performance', null, ['class' => 'form-control', 'placeholder' => 'Performance']) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Off</label>
        {!! Form::text('off', null, ['class' => 'form-control', 'placeholder' => 'Off']) !!}
    </div>
    <div class="form-group col-md-2">
        <label>Billing Month</label>
        <input type="month" name="billing_month" class="form-control"
            value="@isset($invoice->billing_month){{ date('Y-m', strtotime($invoice->billing_month)) }}@endisset"
            id="billing_month" />
    </div>
    <div class="col-md-6 form-group">
        <label>Descriptions</label>
        {!! Form::textarea('descriptions', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Descriptions']) !!}
    </div>
    <div class="col-md-6 form-group">
        <label>Notes</label>
        {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Notes']) !!}
    </div>
</div>

<div>
    <div class="card-header bg-blue mt-3">
        <b class="card-title">Item Details</b>
    </div>
    <div id="rows-container">
        @isset($invoice)
            @foreach ($invoice->items as $item)
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Item Description</label>
                        {!! Form::select('item_id[]', $items, $item->item_id, ['class' => 'form-select form-select-sm select2', 'onchange' => 'rider_price(this);']) !!}
                    </div>
                    <div class="col-md-1 form-group">
                        <label>Qty</label>
                        <input type="text" value="{{ $item->qty }}" class="form-control qty" name="qty[]" onkeyup="calculate_price(this);">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Rate</label>
                        <input type="text" value="{{ $item->rate }}" class="form-control rate" name="rate[]" onkeyup="calculate_price(this);">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Discount</label>
                        <input type="text" value="{{ $item->discount }}" class="form-control discount" name="discount[]" onkeyup="calculate_price(this);">
                    </div>
                    <div class="col-md-1 form-group">
                        <label>VAT</label>
                        <input type="text" value="{{ $item->tax }}" class="form-control tax" name="tax[]" onkeyup="calculate_price(this);">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Amount</label>
                        <input type="text" class="form-control amount" readonly name="amount[]" value="AED {{ number_format($item->amount, 2) }}" data-numeric-value="{{ number_format(round($item->amount, 2), 2, '.', '') }}">
                    </div>
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                    </div>
                </div>
            @endforeach
        @endisset

        <div class="row">
            <div class="col-md-3 form-group">
                <label>Item Description</label>
                {!! Form::select('item_id[]', $items, null, ['class' => 'form-select form-select-sm select2', 'onchange' => 'rider_price(this);']) !!}
            </div>
            <div class="col-md-1 form-group">
                <label>Qty</label>
                <input type="text" class="form-control qty" name="qty[]" placeholder="0" value="1" onkeyup="calculate_price(this);">
            </div>
            <div class="col-md-2 form-group">
                <label>Rate</label>
                <input type="text" class="form-control rate" name="rate[]" placeholder="0" value="0" onkeyup="calculate_price(this);">
            </div>
            <div class="col-md-2 form-group">
                <label>Discount</label>
                <input type="text" class="form-control discount" name="discount[]" placeholder="0" value="0" onkeyup="calculate_price(this);">
            </div>
            <div class="col-md-1 form-group">
                <label>VAT</label>
                <input type="text" class="form-control tax" name="tax[]" placeholder="0" value="0" onkeyup="calculate_price(this);">
            </div>
            <div class="col-md-2 form-group">
                <label>Amount</label>
                <input type="text" class="form-control amount" readonly name="amount[]" placeholder="AED 0.00" value="AED 0.00">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
            </div>
        </div>
    </div>

    <div class="col-md-1 form-group">
        <label style="visibility: hidden">Assign Price</label>
        <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>
    </div>
    <div class="row">
        <div class="col-md-2 offset-7 form-group text-right">
            <label><strong>Sub Total</strong>:</label>
        </div>
        <div class="col-md-2 form-group text-left">
            <input type="text" name="total_amount" class="form-control" id="sub_total"
                value="@isset($invoice->total_amount){{ $invoice->total_amount }}@endisset" readonly>
        </div>
    </div>
</div>


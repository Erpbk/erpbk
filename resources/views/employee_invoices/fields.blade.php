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
</div>

<div>
    <div class="card-header bg-blue mt-3">
        <b class="card-title">Item Details</b>
    </div>
    <div class="scrollbar p-2 border rounded">
        <div class="row">
            <div class="col-md-3 form-group">
                {!! Form::label('item', 'Item') !!}
            </div>
            <div class="col-md-1 form-group">
                <label>Qty</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Rate</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Discount</label>
            </div>
            <div class="col-md-1 form-group">
                <label>VAT</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Total Amount</label>
            </div>
        </div>
        <div id="rows-container">
            @if(isset($invoice))
                @foreach ($invoice->items as $item)
                    <div class="row mt-2">
                        <div class="col-md-3 form-group">
                            <select name="item_id[]" class="form-control select2 item">
                                <option value="">Select</option>
                                @foreach($items as $itm)
                                <option value="{{ $itm->id }}"
                                    data-price="{{ $itm->price }}"
                                    data-vat="{{ $itm->vat }}"
                                    {{ $item->item_id == $itm->id ? 'selected' : ''}}>
                                        {{ $itm->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="text" value="{{ $item->qty }}" class="form-control qty" name="qty[]">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" value="{{ $item->rate }}" class="form-control rate" name="rate[]">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" value="{{ $item->discount }}" class="form-control discount" name="discount[]">
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="text" value="{{ $item->tax }}" class="form-control vat" name="tax[]">
                            <input type="hidden" value="0.00" name="vat_amount[]" class="vat_amount">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" class="form-control amount" readonly name="amount[]" value="{{ number_format($item->amount, 2) }}" data-numeric-value="{{ number_format(round($item->amount, 2), 2, '.', '') }}">
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                @endforeach
            @else

            <div class="row mt-2">
                <div class="col-md-3 form-group">
                    <select name="item_id[]" class="form-control select2 item">
                        <option value="">Select</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}"
                            data-price="{{ $item->price }}"
                            data-vat="{{ $item->vat }}">
                                {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 form-group">
                    <input type="text" class="form-control qty" name="qty[]" placeholder="0" value="1">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control rate" name="rate[]" placeholder="0" value="0">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control discount" name="discount[]" placeholder="0" value="0">
                </div>
                <div class="col-md-1 form-group">
                    <input type="text" class="form-control vat" name="tax[]" placeholder="0" value="0">
                    <input type="hidden" value="0.00" name="vat_amount[]" class="vat_amount">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control amount" readonly name="amount[]" placeholder="0.00" value="0.00">
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="col-md-1 form-group">
        <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-1 mb-3">Add New</button>
    </div>
    <div class="col-md-6 form-group mt-1">
        <label>Notes</label>
        {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Notes']) !!}
    </div>
    <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
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
                <input type="number" name="total_amount" class="form-control" id="total" readonly style="min-width: 150px; font-weight: bold;" >
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // Initialize select2
    $('.select2').select2({
        allowClear: true,
        dropdownParent: $('#modalTopbody')
    });
    $('#rows-container .row').each(function() {
        setItemTotal($(this));
    });
    setTotal();
});
</script>

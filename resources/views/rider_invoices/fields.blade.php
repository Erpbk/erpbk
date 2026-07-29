<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
    @if(isset($invoiceTemplates) && $invoiceTemplates->count() > 0)
    <div class="col-md-4 form-group">
        <label>Invoice Template</label>
        <select name="template_id" class="form-select form-select-sm">
            @foreach($invoiceTemplates as $tpl)
            <option value="{{ $tpl->id }}" @selected((int) old('template_id', isset($invoice) ? ($invoice->template_id ?? ($defaultTemplate->id ?? 0)) : ($defaultTemplate->id ?? 0)) === (int) $tpl->id)>
                {{ $tpl->template_name }}@if($tpl->is_default) (Default)@endif
            </option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date"
            class="form-control"
            value="{{ isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d') }}"
            name="inv_date"
            placeholder="Invoice Date">
    </div>
    <div class="col-md-2 form-group">
        <label>Service Period From</label>
        <input type="date"
            class="form-control"
            name="service_period_from"
            id="service_period_from"
            required
            value="{{ old('service_period_from', isset($invoice) ? optional($invoice->service_period_from)->format('Y-m-d') ?? date('Y-m-01', strtotime($invoice->billing_month)) : date('Y-m-01')) }}">
    </div>
    <div class="col-md-2 form-group">
        <label>Service Period To</label>
        <input type="date"
            class="form-control"
            name="service_period_to"
            id="service_period_to"
            required
            value="{{ old('service_period_to', isset($invoice) ? optional($invoice->service_period_to)->format('Y-m-d') ?? date('Y-m-t', strtotime($invoice->billing_month)) : date('Y-m-t')) }}">
    </div>
    <!--col-->
    <div class="col-md-4 form-group">
        <label>Rider</label>
        {!! Form::select('rider_id', $riders, null, ['class' => 'form-select form-select-sm select2','id'=>'rider_id']) !!}
    </div>
    <!--col-->
    <div class="col-md-2 form-group">
        <label>Zone</label>
        {!! Form::text('zone', null, ['class' => 'form-control','placeholder'=>'Zone']) !!}
    </div>
    <!--col-->
    <div class="col-md-2 form-group">
        <label>Login Hours</label>
        {!! Form::text('login_hours', null, ['class' => 'form-control','placeholder'=>'Login Hours']) !!}
    </div>
    <!--col-->
    <div class="col-md-2 form-group">
        <label>Working Days</label>
        {!! Form::text('working_days', null, ['class' => 'form-control','placeholder'=>'Working Days']) !!}
    </div>
    <!--col-->
    <div class="col-md-2 form-group">
        <label>Perfect Attendance</label>
        {!! Form::text('perfect_attendance', null, ['class' => 'form-control','placeholder'=>'Perfect Attendance']) !!}
    </div>
    <!--col-->
    <div class="col-md-2 form-group">
        <label>Rejection</label>
        {!! Form::text('rejection', null, ['class' => 'form-control','placeholder'=>'Rejection']) !!}
    </div>
    <!--col-->
    <div class="col-md-2 form-group">
        <label>Performance</label>
        {!! Form::text('performance', null, ['class' => 'form-control','placeholder'=>'Performance']) !!}
    </div>
    <!--col-->
    <div class="col-md-2 form-group">
        <label>Off</label>
        {!! Form::text('off', null, ['class' => 'form-control','placeholder'=>'Off']) !!}
    </div>
    <!--col-->
    {{-- <div class="col-md-3 form-group">
      <label>Month of Invoice</label>
      <select class="form-control form-control" name="month_invoice">
          @for($i=1; $i<=12; $i++)
              <option value="{{ $i }}">{{ date('F',mktime(0, 0, 0, $i, 10)) }}</option>
    @endfor
    </select>
</div> --}}
<div class="form-group col-md-2">
    <label for="exampleInputEmail1">Billing Month</label>
    <input type="month" name="billing_month" class="form-control form-control" value="@isset($invoice->billing_month){{date('Y-m',strtotime($invoice->billing_month)) }}@endisset" id="billing_month" />

    {{-- {!! Form::select('billing_month',App\Helpers\CommonHelper::BillingMonth(),null ,['class' => 'form-control form-control select2 ','id'=>'billing_month']) !!}
--}}
</div>
<!--col-->
<div class="col-md-6 form-group">
    <label>Descriptions</label>
    {!! Form::textarea('descriptions', null, ['class' => 'form-control form-control','placeholder'=>'Descriptions','rows'=>2]) !!}

</div>
<!--col-->
</div>
<!--row-->
<div class="mt-2">
    <div class="card-header bg-blue m-3">
        <h5 class="card-title ">Item Details</h5>
    </div>
    <!-- /.card-header -->
    <div class="scrollbar p-2 border rounded">
        <div class="row">
            <div class="col-md-3 form-group">
                <label>Item Description</label>
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
                <label>Amount</label>
            </div>
        </div>
        <div class="" id="rows-container">

            @if(isset($invoice))
                @foreach($invoice->items as $item)

                <div class="row mt-1">
                    <div class="col-md-3 form-group">
                        <select name="item_ids[]" class="form-select item-select select2 item" required>
                            <option value="">Select Item</option>
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
                    <!--col-->
                    <div class="col-md-1 form-group">
                        <input type="text" value="{{$item->qty}}" class="form-control form-control qty" name="qty[]" placeholder="0">
                    </div>
                    <!--col-->
                    <div class="col-md-2 form-group">
                        <input type="text" value="{{$item->rate}}" class="form-control form-control rate" name="rate[]" placeholder="{{ \App\Helpers\Currency::code() }}">
                    </div>
                    <!--col-->
                    <div class="col-md-2 form-group">
                        <input type="text" value="{{$item->discount}}" class="form-control form-control discount" name="discount[]" placeholder="0">
                    </div>
                    <!--col-->
                    <div class="col-md-1 form-group">
                        <input type="text" value="{{$item->tax}}" class="form-control form-control vat" name="tax[]" placeholder="0">
                        <input type="hidden" value="0.00" name="vat_amount[]" class="vat_amount">
                    </div>
                    <!--col-->
                    <div class="col-md-2 form-group">
                        <input type="text" class="form-control form-control amount" readonly name="amount[]" value="{{ number_format(round($item->amount, 2), 2, '.', '') }}" placeholder="0.00" data-numeric-value="{{ number_format(round($item->amount, 2), 2, '.', '') }}">
                    </div>
                    <!--col-->
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                    </div>
                    <!--col-->
                </div>
                @endforeach
            @else

            <div class="row mt-1">
                <div class="col-md-3 form-group">
                    <select name="item_ids[]" class="form-select item-select select2 item" required>
                        <option value="">Select Item</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}"
                            data-price="{{ $item->price ?? 0 }}"
                            data-vat="{{ $item->vat ?? 0 }}">
                            {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <!--col-->
                <div class="col-md-1 form-group">
                    <input type="text" class="form-control form-control qty" name="qty[]" value="{{ 1 }}" >
                </div>
                <!--col-->
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control form-control rate" name="rate[]" placeholder="0" value="0" >
                </div>
                <!--col-->
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control form-control discount" name="discount[]" placeholder="0" value="0" >
                </div>
                <!--col-->
                <div class="col-md-1 form-group">
                    <input type="text" class="form-control form-control vat" name="tax[]" placeholder="0" value="0" >
                    <input type="hidden" value="0.00" name="vat_amount[]" class="vat_amount">
                </div>
                <!--col-->
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control form-control amount" readonly name="amount[]" placeholder="0.00" value="0.00" >
                </div>
                <!--col-->
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
                <!--col-->
            </div>
            @endif
        </div>
    </div>
    <!--row-->
    <div>
        {{-- <button type="button" class="btn btn-sm btn-primary new_line_item"><i class="fa fa-plus"></i> </button>
 --}} <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>

    </div>
    
    <div class="col-md-6 form-group">
        <label>Notes</label>
        {!! Form::textarea('notes', null, ['class' => 'form-control form-control','placeholder'=>'Notes','rows'=>2]) !!}

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

    $('#billing_month').on('change', function() {
        var billingMonth = $(this).val();
        if (!billingMonth) {
            return;
        }

        var parts = billingMonth.split('-');
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        var lastDay = new Date(year, month, 0).getDate();

        $('#service_period_from').val(billingMonth + '-01');
        $('#service_period_to').val(billingMonth + '-' + String(lastDay).padStart(2, '0'));
    });
});
</script>
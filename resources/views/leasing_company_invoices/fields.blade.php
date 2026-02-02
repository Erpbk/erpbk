<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date" class="form-control" value="{{ isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d') }}" name="inv_date" placeholder="Invoice Date">
    </div>

    <div class="col-md-4 form-group">
        <label>Leasing Company</label>
        @php
        $selectedLeasingCompany = isset($invoice) ? $invoice->leasing_company_id : (isset($leasingCompany) && $leasingCompany ? $leasingCompany->id : null);
        @endphp
        {!! Form::select('leasing_company_id', $leasingCompanies, $selectedLeasingCompany, ['class' => 'form-select form-select-sm select2', 'id' => 'leasing_company_id']) !!}
    </div>

    <div class="form-group col-md-2">
        <label>Billing Month</label>
        <input type="month" name="billing_month" class="form-control" value="@isset($invoice->billing_month){{ date('Y-m', strtotime($invoice->billing_month)) }}@else{{ date('Y-m') }}@endisset" id="billing_month" />
    </div>

    <div class="col-md-2 form-group">
        <label>Reference Number</label>
        <input type="text" name="reference_number" class="form-control" value="{{ isset($invoice) ? $invoice->reference_number : '' }}" placeholder="Reference No.">
    </div>

    <div class="col-md-12 form-group">
        <label>Descriptions</label>
        {!! Form::textarea('descriptions', null, ['class' => 'form-control', 'placeholder' => 'Descriptions', 'rows' => 2]) !!}
    </div>
</div>

<div class="">
    <div class="card-header bg-blue mt-3">
        <b class="card-title">Item Details</b>
    </div>

    <div id="rows-container">
        @isset($invoice)
        @foreach($invoice->items as $item)
        <div class="row mb-2 invoice-item-row">
            <div class="col-md-3 form-group">
                <label>Bike</label>
                {!! Form::select('bike_id[]', $bikes, $item->bike_id, ['class' => 'form-select form-select-sm select2 bike-select']) !!}
            </div>
            <div class="col-md-1 form-group">
                <label>Qty</label>
                <input type="number" name="qty[]" value="1" class="form-control qty" min="1" step="1" readonly>
            </div>
            <div class="col-md-1 form-group">
                <label>Days</label>
                <input type="number" name="days[]" value="{{ $item->days ?? 1 }}" class="form-control days" min="1" step="1" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="1">
            </div>
            <div class="col-md-2 form-group">
                <label>Monthly Rate (AED)</label>
                <input type="number" name="rental_amount[]" value="{{ $item->rental_amount }}" class="form-control rate" step="0.01" onkeyup="leasing_calculate_price(this);" placeholder="0.00">
            </div>
            <div class="col-md-1 form-group">
                <label>Tax %</label>
                <input type="number" name="tax_rate[]" value="{{ $item->tax_rate }}" class="form-control tax" step="0.01" onkeyup="leasing_calculate_price(this);" placeholder="5">
            </div>
            <div class="col-md-2 form-group">
                <label>Amount</label>
                <input type="text" class="form-control amount" readonly value="AED {{ number_format($item->total_amount, 2) }}" data-numeric-value="{{ $item->total_amount }}">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
            </div>
        </div>
        @endforeach
        @endisset

        <div class="row mb-2 invoice-item-row">
            <div class="col-md-3 form-group">
                <label>Bike</label>
                {!! Form::select('bike_id[]', $bikes ?? [], null, ['class' => 'form-select form-select-sm select2 bike-select']) !!}
            </div>
            <div class="col-md-1 form-group">
                <label>Qty</label>
                <input type="number" name="qty[]" value="1" class="form-control qty" min="1" step="1" readonly>
            </div>
            <div class="col-md-1 form-group">
                <label>Days</label>
                <input type="number" name="days[]" class="form-control days" min="1" step="1" value="1" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="1">
            </div>
            <div class="col-md-2 form-group">
                <label>Rate (AED)</label>
                <input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0" onkeyup="leasing_calculate_price(this);" placeholder="0.00">
            </div>
            <div class="col-md-1 form-group">
                <label>Tax %</label>
                <input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="{{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }}" onkeyup="leasing_calculate_price(this);" placeholder="5">
            </div>
            <div class="col-md-2 form-group">
                <label>Amount</label>
                <input type="text" class="form-control amount" readonly value="AED 0.00" data-numeric-value="0">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
            </div>
        </div>
    </div>

    <div class="append-line"></div>
    <div class="col-md-1 form-group">
        <label style="visibility: hidden">Add</label>
        <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>
    </div>

    <div class="row mt-2">
        <div class="col-md-12 form-group">
            <label>Notes</label>
            {!! Form::textarea('notes', null, ['class' => 'form-control', 'placeholder' => 'Notes', 'rows' => 2]) !!}
        </div>
    </div>

    <div class="row mt-2" style="justify-content: flex-end;">
        <div class="col-md-2 form-group">
            <label><strong>Sub Total</strong>:</label>
        </div>
        <div class="col-md-2 form-group">
            <input type="text" name="total_amount_display" class="form-control" id="sub_total" value="@isset($invoice->total_amount)AED {{ number_format($invoice->total_amount, 2) }}@else AED 0.00 @endisset" readonly>
        </div>
    </div>
</div>

<script>
    function leasing_getDaysInMonth() {
        var billingMonth = $('#billing_month').val();
        if (!billingMonth) return 30;
        var parts = billingMonth.split('-');
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10) - 1;
        return new Date(year, month + 1, 0).getDate();
    }

    function leasing_calculate_price(el) {
        var row = $(el).closest('.invoice-item-row');
        var monthlyRate = parseFloat(row.find('.rate').val()) || 0;
        var days = parseInt(row.find('.days').val(), 10) || 1;
        var daysInMonth = leasing_getDaysInMonth();
        if (daysInMonth < 1) daysInMonth = 30;
        days = Math.min(Math.max(1, days), daysInMonth);
        var prorated = monthlyRate * (days / daysInMonth);
        var taxPct = parseFloat(row.find('.tax').val()) || 0;
        var taxAmt = prorated * (taxPct / 100);
        var total = prorated + taxAmt;
        row.find('.amount').val('AED ' + total.toFixed(2)).data('numeric-value', total);
        leasing_getTotal();
    }

    function leasing_getTotal() {
        var total = 0;
        $('#rows-container .invoice-item-row').each(function() {
            var v = $(this).find('.amount').data('numeric-value');
            if (v) total += parseFloat(v);
        });
        $('#sub_total').val('AED ' + total.toFixed(2));
    }

    $(document).ready(function() {
        var defaultTax = {
            {
                \
                App\ Helpers\ Common::getSetting('vat_percentage') ?? 5
            }
        };
        var rentalByCompany = @json($rentalAmountByCompany ?? []);
        var $modalBody = $('#formajax').closest('.modal-body');
        if ($modalBody.length === 0) $modalBody = $('#modalTopbody');
        if ($.fn.select2) {
            $('#leasing_company_id').select2({
                dropdownParent: $modalBody.length ? $modalBody : $('body'),
                width: '100%'
            });
            $('.bike-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        dropdownParent: $modalBody.length ? $modalBody : $('body'),
                        width: '100%'
                    });
                }
            });
        }

        $('#leasing_company_id').on('change', function() {
            var id = $(this).val();
            var rate = rentalByCompany[id] || 0;
            $('.rate').val(rate);
            $('#rows-container .invoice-item-row').each(function() {
                leasing_calculate_price($(this).find('.rate')[0]);
            });
        });

        $('#billing_month').on('change', function() {
            $('#rows-container .invoice-item-row').each(function() {
                leasing_calculate_price($(this).find('.rate')[0]);
            });
        });

        $(document).on('click', '.btn-remove-row', function() {
            $(this).closest('.invoice-item-row').remove();
            leasing_getTotal();
        });

        var leasingBikesOptions = @json($bikes ?? []);
        $('#add-new-row').off('click').on('click', function() {
            var opts = '<option value="">Select Bike</option>';
            for (var id in leasingBikesOptions) {
                opts += '<option value="' + id + '">' + leasingBikesOptions[id] + '</option>';
            }
            var html = '<div class="row mb-2 invoice-item-row">' +
                '<div class="col-md-3 form-group"><label>Bike</label><select name="bike_id[]" class="form-select form-select-sm select2 bike-select">' + opts + '</select></div>' +
                '<div class="col-md-1 form-group"><label>Qty</label><input type="number" name="qty[]" value="1" class="form-control qty" min="1" readonly></div>' +
                '<div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" class="form-control days" min="1" value="1" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);"></div>' +
                '<div class="col-md-2 form-group"><label>Monthly Rate (AED)</label><input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0" onkeyup="leasing_calculate_price(this);"></div>' +
                '<div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="' + defaultTax + '" onkeyup="leasing_calculate_price(this);"></div>' +
                '<div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="AED 0.00" data-numeric-value="0"></div>' +
                '<div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>' +
                '</div>';
            $('#rows-container').append(html);
            if ($.fn.select2) {
                var $modalBody = $('#formajax').closest('.modal-body');
                $('#rows-container .invoice-item-row:last .bike-select').select2({
                    dropdownParent: $modalBody.length ? $modalBody : $('body'),
                    width: '100%'
                });
            }
        });

        leasing_getTotal();
    });
</script>
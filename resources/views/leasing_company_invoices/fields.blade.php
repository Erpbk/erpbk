<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date" class="form-control" value="{{ isset($cloneFromInvoice) ? $cloneFromInvoice->inv_date : (isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d')) }}" name="inv_date" placeholder="Invoice Date">
    </div>

    <div class="col-md-4 form-group">
        <label>Leasing Company</label>
        @php
        $selectedLeasingCompany = isset($cloneFromInvoice) ? $cloneFromInvoice->leasing_company_id : (isset($invoice) ? $invoice->leasing_company_id : (isset($leasingCompany) && $leasingCompany ? $leasingCompany->id : null));
        $isClone = isset($cloneFromInvoice);
        @endphp
        {!! Form::select('leasing_company_id', $leasingCompanies, $selectedLeasingCompany, ['class' => 'form-select form-select-sm select2', 'id' => 'leasing_company_id', 'disabled' => $isClone]) !!}
        @if($isClone)
        <input type="hidden" name="leasing_company_id" value="{{ $selectedLeasingCompany }}">
        <small class="text-muted">Leasing company is locked when cloning an invoice.</small>
        @endif
    </div>

    <div class="form-group col-md-2">
        <label>Billing Month</label>
        <input type="month" name="billing_month" class="form-control" value="{{ isset($nextBillingMonth) ? $nextBillingMonth : (isset($invoice) && $invoice->billing_month ? date('Y-m', strtotime($invoice->billing_month)) : date('Y-m')) }}" id="billing_month" />
    </div>

    <div class="col-md-2 form-group">
        <label>Reference Number <span class="text-danger">*</span></label>
        <input type="text" name="reference_number" class="form-control" value="{{ isset($cloneFromInvoice) ? '' : (isset($invoice) ? $invoice->reference_number : '') }}" placeholder="Reference No." required>
    </div>
    @if(isset($invoice) || isset($cloneFromInvoice))
    <div class="col-md-3 form-group">
        <label> Invoice Number <span class="text-danger">*</span></label>
        <input type="text" name="leasing_company_invoice_number" class="form-control" value="{{ isset($cloneFromInvoice) ? '' : (isset($invoice) ? $invoice->leasing_company_invoice_number : '') }}" placeholder="Invoice No." required>
    </div>
    @endif
    <div class="col-md-3 form-group">
        <label>Attachment</label>
        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        @isset($invoice->attachment)
        <small class="text-muted">Current file: <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank" class="text-primary">{{ basename($invoice->attachment) }}</a></small>
        @endisset
    </div>
    <div class="col-md-12 form-group">
        <label>Descriptions</label>
        {!! Form::textarea('descriptions', isset($cloneFromInvoice) ? $cloneFromInvoice->descriptions : null, ['class' => 'form-control', 'placeholder' => 'Descriptions', 'rows' => 2]) !!}
    </div>
</div>

<div class="">
    <div class="card-header bg-blue mt-3">
        <b class="card-title">Item Details</b>
    </div>

    <div id="rows-container">
        @isset($invoice)
        @foreach($invoice->items as $item)
        @php
        // Always use 30 days for calculation regardless of actual month days
        $proratedEdit = $item->rental_amount * (($item->days ?? 1) / 30);
        $taxAmtEdit = $proratedEdit * ($item->tax_rate / 100);
        $lineTotalEdit = $proratedEdit + $taxAmtEdit;
        @endphp
        <div class="row mb-2 invoice-item-row">
            <div class="col-md-2 form-group">
                <label>Bike <span class="text-danger">*</span></label>
                {!! Form::select('bike_id[]', $bikes, $item->bike_id, ['class' => 'form-select form-select-sm select2 bike-select', 'required' => true]) !!}
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
                <input type="number" name="rental_amount[]" value="{{ $item->rental_amount }}" class="form-control rate" step="0.01" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="0.00">
            </div>
            <div class="col-md-1 form-group">
                <label>Tax %</label>
                <input type="number" name="tax_rate[]" value="{{ $item->tax_rate }}" class="form-control tax" step="0.01" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="5">
            </div>
            <div class="col-md-2 form-group">
                <label>Tax Amount (AED)</label>
                <input type="text" class="form-control tax_amount_display" readonly value="{{ number_format($taxAmtEdit, 2) }}" data-numeric-value="{{ $taxAmtEdit }}" placeholder="0.00">
            </div>
            <div class="col-md-2 form-group">
                <label>Amount</label>
                <input type="text" class="form-control amount" readonly value="{{ number_format($lineTotalEdit, 2) }}" data-numeric-value="{{ $lineTotalEdit }}">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
            </div>
        </div>
        @endforeach
        @endisset

        @isset($cloneItems)
        @foreach($cloneItems as $item)
        {{-- Inactive bike styling and auto-exclude disabled: allow inactive bikes to be added; user will remove manually if needed --}}
        <div class="row mb-2 invoice-item-row">
            {{-- @if($item['is_inactive'])
            <div class="col-12 small text-danger mb-1"><i class="fa fa-exclamation-triangle"></i> Inactive/Returned — this bike will be automatically excluded when saving.</div>
            @endif --}}
            <div class="col-md-2 form-group">
                <label>Bike <span class="text-danger">*</span></label>
                {!! Form::select('bike_id[]', $bikes ?? [], $item['bike_id'], ['class' => 'form-select form-select-sm select2 bike-select', 'required' => true]) !!}
            </div>
            <div class="col-md-1 form-group">
                <label>Qty</label>
                <input type="number" name="qty[]" value="1" class="form-control qty" min="1" step="1" readonly>
            </div>
            <div class="col-md-1 form-group">
                <label>Days</label>
                <input type="number" name="days[]" value="{{ $item['days'] }}" class="form-control days" min="1" step="1" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="1">
            </div>
            <div class="col-md-2 form-group">
                <label>Monthly Rate (AED)</label>
                <input type="number" name="rental_amount[]" value="{{ $item['rental_amount'] }}" class="form-control rate" step="0.01" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="0.00">
            </div>
            <div class="col-md-1 form-group">
                <label>Tax %</label>
                <input type="number" name="tax_rate[]" value="{{ $item['tax_rate'] }}" class="form-control tax" step="0.01" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="5">
            </div>
            <div class="col-md-2 form-group">
                <label>Tax Amount (AED)</label>
                @php
                // Always use 30 days for calculation regardless of actual month days
                $proratedClone = $item['rental_amount'] * ($item['days'] / 30);
                $taxAmtClone = $proratedClone * ($item['tax_rate'] / 100);
                $lineTotalClone = $proratedClone + $taxAmtClone;
                @endphp
                <input type="text" class="form-control tax_amount_display" readonly value="{{ number_format($taxAmtClone, 2) }}" data-numeric-value="{{ $taxAmtClone }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Amount</label>
                <input type="text" class="form-control amount" readonly value="{{ number_format($lineTotalClone, 2) }}" data-numeric-value="{{ $lineTotalClone }}">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
            </div>
        </div>
        @endforeach
        @endisset

        <div class="row mb-2 invoice-item-row">
            <div class="col-md-2 form-group">
                <label>Bike <span class="text-danger">*</span></label>
                {!! Form::select('bike_id[]', $bikes ?? [], null, ['class' => 'form-select form-select-sm select2 bike-select', 'required' => true]) !!}
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
                <input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="0.00">
            </div>
            <div class="col-md-1 form-group">
                <label>Tax %</label>
                <input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="{{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }}" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);" placeholder="5">
            </div>
            <div class="col-md-2 form-group">
                <label>Tax Amount (AED)</label>
                <input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0">
            </div>
            <div class="col-md-2 form-group">
                <label>Amount</label>
                <input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0">
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
            {!! Form::textarea('notes', isset($cloneFromInvoice) ? ($cloneFromInvoice->notes ?? '') : (isset($invoice) ? $invoice->notes : null), ['class' => 'form-control', 'placeholder' => 'Notes', 'rows' => 2]) !!}
        </div>
    </div>

    <div class="row mt-2" style="justify-content: flex-end;">
        <div class="col-md-2 form-group">
            <label><strong>Sub Total</strong>:</label>
        </div>
        <div class="col-md-2 form-group">
            @php
            $calculatedTotal = 0;
            if(isset($invoice)) {
            foreach($invoice->items as $item) {
            $prorated = $item->rental_amount * (($item->days ?? 1) / 30);
            $taxAmt = $prorated * ($item->tax_rate / 100);
            $calculatedTotal += ($prorated + $taxAmt);
            }
            }
            @endphp
            <input type="text" name="total_amount_display" class="form-control" id="sub_total" value="{{ isset($invoice) ? number_format($calculatedTotal, 2) : '0.00' }}" readonly>
        </div>
    </div>
</div>

<script>
    // Define functions globally to ensure they're available for inline event handlers
    window.leasing_getDaysInMonth = function() {
        // Always use 30 days for calculation regardless of actual month days
        return 30;
    };

    window.leasing_calculate_price = function(el) {
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
        row.find('.tax_amount_display').val(taxAmt.toFixed(2)).data('numeric-value', taxAmt);
        row.find('.amount').val(total.toFixed(2)).data('numeric-value', total);
        leasing_getTotal();
    };

    window.leasing_getTotal = function() {
        var total = 0;
        $('#rows-container .invoice-item-row').each(function() {
            // if ($(this).data('inactive') === 1) return; // commented out: include inactive bikes in total
            var v = $(this).find('.amount').data('numeric-value');
            if (v) total += parseFloat(v);
        });
        $('#sub_total').val(total.toFixed(2));
    };

    $(document).ready(function() {
        var defaultTax = {{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }};
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
                '<div class="col-md-2 form-group"><label>Bike <span class="text-danger">*</span></label><select name="bike_id[]" class="form-select form-select-sm select2 bike-select" required>' + opts + '</select></div>' +
                '<div class="col-md-1 form-group"><label>Qty</label><input type="number" name="qty[]" value="1" class="form-control qty" min="1" readonly></div>' +
                '<div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" class="form-control days" min="1" value="1" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);"></div>' +
                '<div class="col-md-2 form-group"><label>Monthly Rate (AED)</label><input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);"></div>' +
                '<div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="' + defaultTax + '" onkeyup="leasing_calculate_price(this);" onchange="leasing_calculate_price(this);"></div>' +
                '<div class="col-md-2 form-group"><label>Tax Amount (AED)</label><input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0"></div>' +
                '<div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0"></div>' +
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
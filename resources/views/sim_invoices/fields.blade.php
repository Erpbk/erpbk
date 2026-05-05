<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date" class="form-control" value="{{ isset($cloneFromInvoice) ? $cloneFromInvoice->inv_date : (isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d')) }}" name="inv_date">
    </div>

    <div class="col-md-4 form-group">
        <label>Company</label>
        @php
        $selectedCompany = isset($cloneFromInvoice) ? $cloneFromInvoice->company_id : (isset($invoice) ? $invoice->company_id : (isset($company) && $company ? $company->id : null));
        $isClone = isset($cloneFromInvoice);
        @endphp
        {!! Form::select('vendor_id', $companies, $selectedCompany, ['class' => 'form-select form-select-sm select2', 'id' => 'company_id', 'disabled' => $isClone]) !!}
        @if($isClone)
        <input type="hidden" name="vendor_id" value="{{ $selectedVendor }}">
        @endif
    </div>

    <div class="form-group col-md-2">
        <label>Billing Month</label>
        <input type="month" name="billing_month" class="form-control" value="{{ isset($nextBillingMonth) ? $nextBillingMonth : (isset($invoice) && $invoice->billing_month ? date('Y-m', strtotime($invoice->billing_month)) : date('Y-m')) }}" id="billing_month" />
    </div>

    <div class="col-md-2 form-group">
        <label>Reference Number <span class="text-danger">*</span></label>
        <input type="text" name="reference_number" class="form-control" value="{{ isset($cloneFromInvoice) ? '' : (isset($invoice) ? $invoice->reference_number : '') }}" required>
    </div>

    @if(isset($invoice) || isset($cloneFromInvoice))
    <div class="col-md-3 form-group">
        <label>SIM Invoice Number <span class="text-danger">*</span></label>
        <input type="text" name="sim_invoice_number" class="form-control" value="{{ isset($cloneFromInvoice) ? '' : (isset($invoice) ? $invoice->sim_invoice_number : '') }}" required>
    </div>
    @endif

    <div class="col-md-3 form-group">
        <label>Attachment</label>
        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
    </div>

    <div class="col-md-12 form-group">
        <label>Descriptions</label>
        {!! Form::textarea('descriptions', isset($cloneFromInvoice) ? $cloneFromInvoice->descriptions : null, ['class' => 'form-control', 'rows' => 2]) !!}
    </div>
</div>

<div class="">
    <div class="card-header bg-blue mt-3">
        <b class="card-title">Item Details</b>
    </div>

    <div id="rows-container">
        @if(isset($invoice))
        @foreach($invoice->items as $item)
        @php
        $proratedEdit = $item->rental_amount * (($item->days ?? 1) / 30);
        $taxAmtEdit = $proratedEdit * ($item->tax_rate / 100);
        $lineTotalEdit = $proratedEdit + $taxAmtEdit;
        @endphp
        <div class="row mb-2 invoice-item-row">
            <div class="col-md-2 form-group">
                <label>SIM <span class="text-danger">*</span></label>
                {!! Form::select('sim_id[]', $sims, $item->sim_id, ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
            </div>
            <div class="col-md-1 form-group"><label>Qty</label><input type="number" name="qty[]" value="1" class="form-control qty" readonly></div>
            <div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" value="{{ $item->days ?? 1 }}" class="form-control days" min="1" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-2 form-group"><label>Monthly Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" value="{{ $item->rental_amount }}" class="form-control rate" step="0.01" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" value="{{ $item->tax_rate }}" class="form-control tax" step="0.01" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-2 form-group"><label>Tax Amount ({{ \App\Helpers\Currency::code() }})</label><input type="text" class="form-control tax_amount_display" readonly value="{{ number_format($taxAmtEdit, 2) }}" data-numeric-value="{{ $taxAmtEdit }}"></div>
            <div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="{{ number_format($lineTotalEdit, 2) }}" data-numeric-value="{{ $lineTotalEdit }}"></div>
            <div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>
        </div>
        @endforeach
        @endif

        @if(isset($cloneItems))
        @foreach($cloneItems as $item)
        @php
        $proratedClone = $item['rental_amount'] * ($item['days'] / 30);
        $taxAmtClone = $proratedClone * ($item['tax_rate'] / 100);
        $lineTotalClone = $proratedClone + $taxAmtClone;
        @endphp
        <div class="row mb-2 invoice-item-row">
            <div class="col-md-2 form-group">
                <label>SIM <span class="text-danger">*</span></label>
                {!! Form::select('sim_id[]', $sims ?? [], $item['sim_id'], ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
            </div>
            <div class="col-md-1 form-group"><label>Qty</label><input type="number" name="qty[]" value="1" class="form-control qty" readonly></div>
            <div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" value="{{ $item['days'] }}" class="form-control days" min="1" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-2 form-group"><label>Monthly Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" value="{{ $item['rental_amount'] }}" class="form-control rate" step="0.01" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" value="{{ $item['tax_rate'] }}" class="form-control tax" step="0.01" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-2 form-group"><label>Tax Amount ({{ \App\Helpers\Currency::code() }})</label><input type="text" class="form-control tax_amount_display" readonly value="{{ number_format($taxAmtClone, 2) }}" data-numeric-value="{{ $taxAmtClone }}"></div>
            <div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="{{ number_format($lineTotalClone, 2) }}" data-numeric-value="{{ $lineTotalClone }}"></div>
            <div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>
        </div>
        @endforeach
        @endif

        <div class="row mb-2 invoice-item-row">
            <div class="col-md-2 form-group">
                <label>SIM <span class="text-danger">*</span></label>
                {!! Form::select('sim_id[]', $sims ?? [], null, ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
            </div>
            <div class="col-md-1 form-group"><label>Qty</label><input type="number" name="qty[]" value="1" class="form-control qty" readonly></div>
            <div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" class="form-control days" min="1" value="1" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-2 form-group"><label>Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="{{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }}" onchange="sim_calculate_price(this);"></div>
            <div class="col-md-2 form-group"><label>Tax Amount ({{ \App\Helpers\Currency::code() }})</label><input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0"></div>
            <div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0"></div>
            <div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>
        </div>
    </div>

    <div class="col-md-1 form-group">
        <label style="visibility: hidden">Add</label>
        <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>
    </div>

    <div class="row mt-2">
        <div class="col-md-12 form-group">
            <label>Notes</label>
            {!! Form::textarea('notes', isset($cloneFromInvoice) ? ($cloneFromInvoice->notes ?? '') : (isset($invoice) ? $invoice->notes : null), ['class' => 'form-control', 'rows' => 2]) !!}
        </div>
    </div>

    <div class="row mt-2" style="justify-content: flex-end;">
        <div class="col-md-2 form-group"><label><strong>Sub Total</strong>:</label></div>
        <div class="col-md-2 form-group"><input type="text" class="form-control" id="sub_total" value="0.00" readonly></div>
    </div>
</div>

<script>
    window.sim_calculate_price = function(el) {
        var row = $(el).closest('.invoice-item-row');
        var monthlyRate = parseFloat(row.find('.rate').val()) || 0;
        var days = Math.min(Math.max(parseInt(row.find('.days').val(), 10) || 1, 1), 30);
        var prorated = monthlyRate * (days / 30);
        var taxPct = parseFloat(row.find('.tax').val()) || 0;
        var taxAmt = prorated * (taxPct / 100);
        var total = prorated + taxAmt;
        row.find('.tax_amount_display').val(taxAmt.toFixed(2)).data('numeric-value', taxAmt);
        row.find('.amount').val(total.toFixed(2)).data('numeric-value', total);
        sim_getTotal();
    };

    window.sim_getTotal = function() {
        var total = 0;
        $('#rows-container .invoice-item-row').each(function() {
            var v = $(this).find('.amount').data('numeric-value');
            if (v) total += parseFloat(v);
        });
        $('#sub_total').val(total.toFixed(2));
    };

    $(document).ready(function() {
        var defaultTax = {
            {
                \
                App\ Helpers\ Common::getSetting('vat_percentage') ?? 5
            }
        };
        var simsOptions = @json($sims ?? []);
        var $modalBody = $('#formajax').closest('.modal-body');
        if ($modalBody.length === 0) $modalBody = $('#modalTopbody');

        if ($.fn.select2) {
            $('#vendor_id, .sim-select').select2({
                dropdownParent: $modalBody.length ? $modalBody : $('body'),
                width: '100%'
            });
        }

        $(document).on('click', '.btn-remove-row', function() {
            $(this).closest('.invoice-item-row').remove();
            sim_getTotal();
        });

        $('#add-new-row').off('click').on('click', function() {
            var opts = '<option value="">Select SIM</option>';
            for (var id in simsOptions) {
                opts += '<option value="' + id + '">' + simsOptions[id] + '</option>';
            }

            var html = '<div class="row mb-2 invoice-item-row">' +
                '<div class="col-md-2 form-group"><label>SIM <span class="text-danger">*</span></label><select name="sim_id[]" class="form-select form-select-sm select2 sim-select" required>' + opts + '</select></div>' +
                '<div class="col-md-1 form-group"><label>Qty</label><input type="number" name="qty[]" value="1" class="form-control qty" readonly></div>' +
                '<div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" class="form-control days" min="1" value="1" onchange="sim_calculate_price(this);"></div>' +
                '<div class="col-md-2 form-group"><label>Monthly Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0" onchange="sim_calculate_price(this);"></div>' +
                '<div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="' + defaultTax + '" onchange="sim_calculate_price(this);"></div>' +
                '<div class="col-md-2 form-group"><label>Tax Amount ({{ \App\Helpers\Currency::code() }})</label><input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0"></div>' +
                '<div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0"></div>' +
                '<div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>' +
                '</div>';
            $('#rows-container').append(html);
            if ($.fn.select2) {
                $('#rows-container .invoice-item-row:last .sim-select').select2({
                    dropdownParent: $modalBody.length ? $modalBody : $('body'),
                    width: '100%'
                });
            }
        });

        $('#vendor_id').on('change', function() {
            var vendorId = $(this).val();
            if (!vendorId) return;
            var endpoint = "{{ route('simInvoices.getSims', ['id' => '__VENDOR_ID__']) }}".replace('__VENDOR_ID__', vendorId);
            $.get(endpoint, function(resp) {
                if (!resp.sims) return;
                simsOptions = {};
                $.each(resp.sims, function(_, sim) {
                    simsOptions[sim.id] = sim.number + ' - ' + (sim.company || '');
                });
                $('.sim-select').each(function() {
                    var $sel = $(this);
                    var current = $sel.val();
                    $sel.empty().append('<option value="">Select SIM</option>');
                    $.each(simsOptions, function(id, label) {
                        $sel.append('<option value="' + id + '">' + label + '</option>');
                    });
                    $sel.val(current).trigger('change.select2');
                });
            });
        });

        $('#rows-container .invoice-item-row').each(function() {
            sim_calculate_price($(this).find('.rate')[0]);
        });
        sim_getTotal();
    });
</script>
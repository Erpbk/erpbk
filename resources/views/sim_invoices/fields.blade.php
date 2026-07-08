<div class="row">

    <div class="col-md-3 form-group">
        <label>Company</label>
        @php
        $selectedCompany = isset($cloneFromInvoice) ? $cloneFromInvoice->company_id : (isset($invoice) ? $invoice->company_id : (isset($company) && $company ? $company->id : null));
        $isClone = isset($cloneFromInvoice);
        @endphp
        {!! Form::select('vendor_id', $companies, $selectedCompany, ['class' => 'form-select select2', 'id' => 'company_id', 'disabled' => $isClone]) !!}
        @if($isClone)
        <input type="hidden" name="vendor_id" value="{{ $selectedVendor }}">
        @endif
    </div>

    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date" class="form-control" value="{{ isset($cloneFromInvoice) ? $cloneFromInvoice->inv_date : (isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : null) }}" name="inv_date">
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

    <div class="col-md-6 form-group">
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
            <div class="col-md-3 form-group">
                <label>SIM <span class="text-danger">*</span></label>
                {!! Form::select('sim_id[]', $sims, $item->sim_id, ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
            </div>
            <div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" value="{{ $item->days ?? 1 }}" class="form-control days" min="1"></div>
            <div class="col-md-2 form-group"><label>Monthly Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" value="{{ $item->rental_amount }}" class="form-control rate" step="0.01"></div>
            <div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" value="{{ $item->tax_rate }}" class="form-control tax" step="0.01"></div>
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
            <div class="col-md-3 form-group">
                <label>SIM <span class="text-danger">*</span></label>
                {!! Form::select('sim_id[]', $sims ?? [], $item['sim_id'], ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
            </div>
            <div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" value="{{ $item['days'] }}" class="form-control days" min="1"></div>
            <div class="col-md-2 form-group"><label>Monthly Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" value="{{ $item['rental_amount'] }}" class="form-control rate" step="0.01"></div>
            <div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" value="{{ $item['tax_rate'] }}" class="form-control tax" step="0.01"></div>
            <div class="col-md-2 form-group"><label>Tax Amount ({{ \App\Helpers\Currency::code() }})</label><input type="text" class="form-control tax_amount_display" readonly value="{{ number_format($taxAmtClone, 2) }}" data-numeric-value="{{ $taxAmtClone }}"></div>
            <div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="{{ number_format($lineTotalClone, 2) }}" data-numeric-value="{{ $lineTotalClone }}"></div>
            <div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>
        </div>
        @endforeach
        @endif

        <div class="row mb-2 invoice-item-row">
            <div class="col-md-3 form-group">
                <label>SIM <span class="text-danger">*</span></label>
                {!! Form::select('sim_id[]', $sims ?? [], null, ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
            </div>
            <div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" class="form-control days" min="1" value="1"></div>
            <div class="col-md-2 form-group"><label>Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0"></div>
            <div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="{{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }}"></div>
            <div class="col-md-2 form-group"><label>Tax Amount ({{ \App\Helpers\Currency::code() }})</label><input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0"></div>
            <div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0"></div>
            <div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>
        </div>
    </div>

    <div class="col-md-1 form-group">
        <label style="visibility: hidden">Add</label>
        <button type="button" id="sim-add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>
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
        if (!row.length) return;
        var monthlyRate = parseFloat(row.find('.rate').val()) || 0;
        var days = Math.min(Math.max(parseInt(row.find('.days').val(), 10) || 1, 1), 30);
        var prorated = monthlyRate * (days / 30);
        var taxPct = parseFloat(row.find('.tax').val()) || 0;
        var taxAmt = prorated * (taxPct / 100);
        var total = prorated + taxAmt;
        row.find('.tax_amount_display').val(taxAmt.toFixed(2)).attr('data-numeric-value', taxAmt).data('numeric-value', taxAmt);
        row.find('.amount').val(total.toFixed(2)).attr('data-numeric-value', total).data('numeric-value', total);
        sim_getTotal();
    };

    window.sim_getTotal = function() {
        var total = 0;
        $('#rows-container .invoice-item-row').each(function() {
            var $amount = $(this).find('.amount');
            var v = $amount.data('numeric-value');
            if (v === undefined || v === null || v === '') {
                v = parseFloat($amount.val()) || 0;
            }
            total += parseFloat(v) || 0;
        });
        $('#sub_total').val(total.toFixed(2));
    };

    function simInitSelect2($scope) {
        if (!$.fn.select2) return;
        var $modalBody = $('#formajax').closest('.modal-body');
        if ($modalBody.length === 0) $modalBody = $('#modalTopbody');
        var $root = $scope && $scope.length ? $scope : $('#formajax');
        if ($root.length === 0) $root = $(document);
        $root.find('select').each(function() {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                dropdownParent: $modalBody.length ? $modalBody : $('body'),
                width: '100%'
            });
        });
    }

    (function initSimInvoiceFields() {
        if (window.__simInvoiceFieldsBound) {
            return;
        }
        window.__simInvoiceFieldsBound = true;

        var defaultTax = {{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }};
        var simsOptions = @json($sims ?? []);

        simInitSelect2($('#formajax'));

        $(document)
            .off('click.simInvoice', '.invoice-item-row .btn-remove-row')
            .on('click.simInvoice', '.invoice-item-row .btn-remove-row', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var $rows = $('#rows-container .invoice-item-row');
                if ($rows.length <= 1) {
                    return;
                }
                $(this).closest('.invoice-item-row').remove();
                sim_getTotal();
            });

        $(document)
            .off('click.simInvoice', '#sim-add-new-row')
            .on('click.simInvoice', '#sim-add-new-row', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var opts = '<option value="">Select SIM</option>';
                for (var id in simsOptions) {
                    if (Object.prototype.hasOwnProperty.call(simsOptions, id)) {
                        opts += '<option value="' + id + '">' + simsOptions[id] + '</option>';
                    }
                }

                var html = '<div class="row mb-2 invoice-item-row">' +
                    '<div class="col-md-3 form-group"><label>SIM <span class="text-danger">*</span></label><select name="sim_id[]" class="form-select form-select-sm select2 sim-select" required>' + opts + '</select></div>' +
                    '<div class="col-md-1 form-group"><label>Days</label><input type="number" name="days[]" class="form-control days" min="1" value="1"></div>' +
                    '<div class="col-md-2 form-group"><label>Monthly Rate ({{ \App\Helpers\Currency::code() }})</label><input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0"></div>' +
                    '<div class="col-md-1 form-group"><label>Tax %</label><input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="' + defaultTax + '"></div>' +
                    '<div class="col-md-2 form-group"><label>Tax Amount ({{ \App\Helpers\Currency::code() }})</label><input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0"></div>' +
                    '<div class="col-md-2 form-group"><label>Amount</label><input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0"></div>' +
                    '<div class="form-group col-md-1 d-flex align-items-end"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>' +
                    '</div>';
                $('#rows-container').append(html);
                simInitSelect2($('#rows-container .invoice-item-row:last'));
            });

        $(document)
            .off('input.simInvoice change.simInvoice', '#rows-container .invoice-item-row .days, #rows-container .invoice-item-row .rate, #rows-container .invoice-item-row .tax')
            .on('input.simInvoice change.simInvoice', '#rows-container .invoice-item-row .days, #rows-container .invoice-item-row .rate, #rows-container .invoice-item-row .tax', function() {
                sim_calculate_price(this);
            });

        $(document)
            .off('change.simInvoice', '#company_id, select[name="vendor_id"]')
            .on('change.simInvoice', '#company_id, select[name="vendor_id"]', function() {
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
            var rateEl = $(this).find('.rate')[0];
            if (rateEl) {
                sim_calculate_price(rateEl);
            }
        });
        sim_getTotal();
    })();
</script>

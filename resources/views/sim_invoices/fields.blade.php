<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date" class="form-control" value="{{ isset($cloneFromInvoice) ? $cloneFromInvoice->inv_date : (isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d')) }}" name="inv_date" placeholder="Invoice Date">
    </div>

    <div class="col-md-4 form-group">
        <label>Company</label>
        @php
        $selectedCompany = isset($cloneFromInvoice) ? $cloneFromInvoice->vendor_id : (isset($invoice) ? $invoice->vendor_id : (isset($company) && $company ? $company->id : null));
        $isClone = isset($cloneFromInvoice);
        @endphp
        {!! Form::select('vendor_id', $companies, $selectedCompany, ['class' => 'form-select form-select-sm select2', 'id' => 'sim_invoice_company_id', 'disabled' => $isClone]) !!}
        @if($isClone)
        <input type="hidden" name="vendor_id" value="{{ $selectedCompany }}">
        <small class="text-muted">Company is locked when cloning an invoice.</small>
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
        <label>SIM Invoice Number <span class="text-danger">*</span></label>
        <input type="text" name="sim_invoice_number" class="form-control" value="{{ isset($cloneFromInvoice) ? '' : (isset($invoice) ? $invoice->sim_invoice_number : '') }}" placeholder="Invoice No." required>
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

<div class="mt-2">
    <div class="card-header bg-blue m-3">
        <h5 class="card-title">Item Details</h5>
    </div>

    <div class="scrollbar p-2 border rounded">
        <div class="row">
            <div class="col-md-3 form-group">
                <label>SIM <span class="text-danger">*</span></label>
            </div>
            <div class="col-md-1 form-group">
                <label>Days</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Monthly Rate</label>
            </div>
            <div class="col-md-1 form-group">
                <label>VAT %</label>
            </div>
            <div class="col-md-2 form-group">
                <label>VAT Amount</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Amount</label>
            </div>
        </div>

        <div id="rows-container">
            @isset($invoice)
            @foreach($invoice->items as $item)
            @php
            $proratedEdit = $item->rental_amount * (($item->days ?? 1) / 30);
            $taxAmtEdit = $proratedEdit * ($item->tax_rate / 100);
            $lineTotalEdit = $proratedEdit + $taxAmtEdit;
            @endphp
            <div class="row mt-1 invoice-item-row">
                <div class="col-md-3 form-group">
                    {!! Form::select('sim_id[]', $sims, $item->sim_id, ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="days[]" value="{{ $item->days ?? 1 }}" class="form-control days" min="1" step="1" placeholder="1">
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="rental_amount[]" value="{{ $item->rental_amount }}" class="form-control rate" step="0.01" placeholder="0.00">
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="tax_rate[]" value="{{ $item->tax_rate }}" class="form-control tax" step="0.01" placeholder="5">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control tax_amount_display" readonly value="{{ number_format($taxAmtEdit, 2) }}" data-numeric-value="{{ $taxAmtEdit }}" placeholder="0.00">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control amount" readonly value="{{ number_format($lineTotalEdit, 2) }}" data-numeric-value="{{ $lineTotalEdit }}">
                </div>
                <div class="form-group col-md-1 d-flex align-items-center">
                    <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endforeach
            @endisset

            @isset($cloneItems)
            @foreach($cloneItems as $item)
            @php
            $proratedClone = $item['rental_amount'] * ($item['days'] / 30);
            $taxAmtClone = $proratedClone * ($item['tax_rate'] / 100);
            $lineTotalClone = $proratedClone + $taxAmtClone;
            @endphp
            <div class="row mt-1 invoice-item-row">
                <div class="col-md-3 form-group">
                    {!! Form::select('sim_id[]', $sims ?? [], $item['sim_id'], ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="days[]" value="{{ $item['days'] }}" class="form-control days" min="1" step="1" placeholder="1">
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="rental_amount[]" value="{{ $item['rental_amount'] }}" class="form-control rate" step="0.01" placeholder="0.00">
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="tax_rate[]" value="{{ $item['tax_rate'] }}" class="form-control tax" step="0.01" placeholder="5">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control tax_amount_display" readonly value="{{ number_format($taxAmtClone, 2) }}" data-numeric-value="{{ $taxAmtClone }}">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control amount" readonly value="{{ number_format($lineTotalClone, 2) }}" data-numeric-value="{{ $lineTotalClone }}">
                </div>
                <div class="form-group col-md-1 d-flex align-items-center">
                    <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endforeach
            @endisset

            @if(!isset($invoice) && !isset($cloneItems))
            <div class="row mt-1 invoice-item-row">
                <div class="col-md-3 form-group">
                    {!! Form::select('sim_id[]', $sims ?? [], null, ['class' => 'form-select form-select-sm select2 sim-select', 'required' => true]) !!}
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="days[]" class="form-control days" min="1" step="1" value="1" placeholder="1">
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0" placeholder="0.00">
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="{{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }}" placeholder="5">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0">
                </div>
                <div class="form-group col-md-1 d-flex align-items-center">
                    <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div>
        <button type="button" id="sim-add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>
    </div>

    <div class="row mt-2">
        <div class="col-md-12 form-group">
            <label>Notes</label>
            {!! Form::textarea('notes', isset($cloneFromInvoice) ? ($cloneFromInvoice->notes ?? '') : (isset($invoice) ? $invoice->notes : null), ['class' => 'form-control', 'placeholder' => 'Notes', 'rows' => 2]) !!}
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
        <div></div>
        <div class="d-flex align-items-center gap-3">
            @php
            $calculatedSubtotal = 0;
            $calculatedVat = 0;
            if(isset($invoice)) {
                foreach($invoice->items as $item) {
                    $prorated = $item->rental_amount * (($item->days ?? 1) / 30);
                    $taxAmt = $prorated * ($item->tax_rate / 100);
                    $calculatedSubtotal += $prorated;
                    $calculatedVat += $taxAmt;
                }
            }
            $calculatedTotal = $calculatedSubtotal + $calculatedVat;
            @endphp
            <div class="input-group">
                <span class="input-group-text bg-light">Subtotal</span>
                <input type="number" name="subtotal" class="form-control" id="subtotal" value="{{ number_format($calculatedSubtotal, 2, '.', '') }}" readonly style="min-width: 150px;">
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light">VAT Amount</span>
                <input type="number" name="vat_total" class="form-control" id="vat_total" value="{{ number_format($calculatedVat, 2, '.', '') }}" readonly style="min-width: 150px;">
            </div>
            <div class="input-group">
                <span class="input-group-text bg-primary text-white">Total</span>
                <input type="number" name="total_amount_display" class="form-control" id="total" value="{{ number_format($calculatedTotal, 2, '.', '') }}" readonly style="min-width: 150px; font-weight: bold;">
            </div>
        </div>
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
        var subtotal = 0;
        var vat = 0;
        var total = 0;
        $('#rows-container .invoice-item-row').each(function() {
            var tax = parseFloat($(this).find('.tax_amount_display').data('numeric-value'));
            if (isNaN(tax)) {
                tax = parseFloat($(this).find('.tax_amount_display').val()) || 0;
            }
            var lineTotal = parseFloat($(this).find('.amount').data('numeric-value'));
            if (isNaN(lineTotal)) {
                lineTotal = parseFloat($(this).find('.amount').val()) || 0;
            }
            vat += tax;
            total += lineTotal;
            subtotal += (lineTotal - tax);
        });
        $('#subtotal').val(subtotal.toFixed(2));
        $('#vat_total').val(vat.toFixed(2));
        $('#total').val(total.toFixed(2));
    };

    $(document).ready(function() {
        var defaultTax = {{ \App\Helpers\Common::getSetting('vat_percentage') ?? 5 }};
        var simsOptions = @json($sims ?? []);
        var $modalBody = $('#formajax').closest('.modal-body');
        if ($modalBody.length === 0) $modalBody = $('#modalTopbody');

        if ($.fn.select2) {
            $('#sim_invoice_company_id').select2({
                dropdownParent: $modalBody.length ? $modalBody : $('body'),
                width: '100%'
            });
            $('.sim-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        dropdownParent: $modalBody.length ? $modalBody : $('body'),
                        width: '100%'
                    });
                }
            });
        }

        $(document)
            .off('click.simInvoice', '#rows-container .btn-remove-row')
            .on('click.simInvoice', '#rows-container .btn-remove-row', function(e) {
                e.preventDefault();
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
                var opts = '<option value="">Select SIM</option>';
                for (var id in simsOptions) {
                    if (Object.prototype.hasOwnProperty.call(simsOptions, id)) {
                        opts += '<option value="' + id + '">' + simsOptions[id] + '</option>';
                    }
                }
                var html = '<div class="row mt-1 invoice-item-row">' +
                    '<div class="col-md-3 form-group"><select name="sim_id[]" class="form-select form-select-sm select2 sim-select" required>' + opts + '</select></div>' +
                    '<div class="col-md-1 form-group"><input type="number" name="days[]" class="form-control days" min="1" value="1"></div>' +
                    '<div class="col-md-2 form-group"><input type="number" name="rental_amount[]" class="form-control rate" step="0.01" value="0"></div>' +
                    '<div class="col-md-1 form-group"><input type="number" name="tax_rate[]" class="form-control tax" step="0.01" value="' + defaultTax + '"></div>' +
                    '<div class="col-md-2 form-group"><input type="text" class="form-control tax_amount_display" readonly value="0.00" data-numeric-value="0"></div>' +
                    '<div class="col-md-2 form-group"><input type="text" class="form-control amount" readonly value="0.00" data-numeric-value="0"></div>' +
                    '<div class="form-group col-md-1 d-flex align-items-center"><a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a></div>' +
                    '</div>';
                $('#rows-container').append(html);
                if ($.fn.select2) {
                    var $parent = $('#formajax').closest('.modal-body');
                    if ($parent.length === 0) $parent = $('#modalTopbody');
                    $('#rows-container .invoice-item-row:last .sim-select').select2({
                        dropdownParent: $parent.length ? $parent : $('body'),
                        width: '100%'
                    });
                }
            });

        $(document)
            .off('input.simInvoice change.simInvoice', '#rows-container .days, #rows-container .rate, #rows-container .tax')
            .on('input.simInvoice change.simInvoice', '#rows-container .days, #rows-container .rate, #rows-container .tax', function() {
                sim_calculate_price(this);
            });

        $(document)
            .off('change.simInvoice', '#sim_invoice_company_id')
            .on('change.simInvoice', '#sim_invoice_company_id', function() {
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
    });
</script>

@php
    $catalogItems = ($items ?? collect())->map(function ($item) {
        return [
            'id' => (int) $item->id,
            'name' => $item->name,
            'price' => (float) ($item->price ?? 0),
            'vat' => (float) ($item->vat ?? 0),
        ];
    })->values();
    $defaultVat = (float) ($defaultVat ?? (\App\Helpers\Common::getSetting('vat_percentage') ?? 5));
    $selectedCompany = isset($cloneFromInvoice)
        ? ($cloneFromInvoice->company_id ?? $cloneFromInvoice->vendor_id)
        : (isset($invoice) ? $invoice->vendor_id : (isset($company) && $company ? $company->id : null));
    $isClone = isset($cloneFromInvoice);
    $initialColumns = collect($pivotColumns ?? [])->map(function ($item) {
        return [
            'id' => (int) $item->id,
            'name' => $item->name,
            'price' => (float) ($item->price ?? 0),
            'vat' => (float) ($item->vat ?? 0),
        ];
    })->values();
    $initialRows = collect($pivotRows ?? [])->map(function ($row) {
        return [
            'sim_id' => (int) $row['sim_id'],
            'charges' => $row['qtys'] ?? [],
            'vat_percent' => (float) ($row['vat_percent'] ?? 0),
        ];
    })->values();
@endphp

<style>
    .sim-pivot-wrap {
        border: 1px solid #e7eaf3;
        border-radius: .5rem;
        background: #fff;
        overflow: hidden;
    }
    .sim-pivot-scroll {
        overflow: auto;
        max-height: 520px;
    }
    .sim-pivot-table {
        min-width: 720px;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .sim-pivot-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f5f7fb;
        color: #4b5675;
        font-size: .78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .02em;
        text-align: center;
        border-bottom: 1px solid #e7eaf3 !important;
        white-space: nowrap;
        vertical-align: middle;
        padding: .7rem .75rem;
    }
    .sim-pivot-table thead th .sim-col-rate {
        display: block;
        margin-top: .2rem;
        font-size: .7rem;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
        color: #99a1b7;
    }
    .sim-pivot-table tbody td,
    .sim-pivot-table tfoot td {
        vertical-align: middle;
        white-space: nowrap;
        padding: .55rem .75rem;
        border-color: #eef1f7;
    }
    .sim-pivot-table tbody tr:hover {
        background: #fafbff;
    }
    .sim-pivot-table tfoot td {
        position: sticky;
        bottom: 0;
        z-index: 2;
        background: #f8f9fc;
        border-top: 1px solid #e7eaf3 !important;
        font-weight: 600;
    }
    .sim-pivot-table .sim-sim-col { min-width: 240px; width: 240px; }
    .sim-pivot-table .sim-charge-col { min-width: 130px; }
    .sim-pivot-table .sim-vat-col { min-width: 96px; width: 96px; }
    .sim-pivot-table .sim-total-col { min-width: 120px; width: 120px; }
    .sim-pivot-table .sim-actions-col { width: 52px; text-align: center; }
    .sim-pivot-table .sim-charge-input,
    .sim-pivot-table .sim-vat-input {
        text-align: right;
        min-width: 100px;
        background: #fff;
    }
    .sim-pivot-table .sim-row-total {
        font-variant-numeric: tabular-nums;
        color: #1b84ff;
    }
    .sim-pivot-empty {
        text-align: center;
        padding: 2rem 1rem !important;
        color: #99a1b7;
        background: #fcfcfd;
        white-space: normal !important;
    }
    .sim-selected-items .badge {
        font-weight: 500;
        padding: .45rem .65rem;
    }
    #sim_lines_hint {
        font-size: .825rem;
    }
</style>

<div class="row">
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        <input type="date" class="form-control"
            value="{{ isset($cloneFromInvoice) ? $cloneFromInvoice->inv_date : (isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d')) }}"
            name="inv_date">
    </div>

    <div class="col-md-3 form-group">
        <label>Company <span class="text-danger">*</span></label>
        {!! Form::select('company_id', $companies, $selectedCompany, [
            'class' => 'form-select form-select-sm select2',
            'id' => 'sim_invoice_company_id',
            'disabled' => $isClone,
        ]) !!}
        @if($isClone)
            <input type="hidden" name="company_id" value="{{ $selectedCompany }}">
            <small class="text-muted">Company is locked when cloning an invoice.</small>
        @else
            <small class="text-muted">SIM numbers load after you choose a company.</small>
        @endif
    </div>

    <div class="form-group col-md-2">
        <label>Billing Month</label>
        <input type="month" name="billing_month" class="form-control" id="billing_month"
            value="{{ isset($nextBillingMonth) ? $nextBillingMonth : (isset($invoice) && $invoice->billing_month ? date('Y-m', strtotime($invoice->billing_month)) : date('Y-m')) }}">
    </div>

    <div class="col-md-2 form-group">
        <label>Reference Number <span class="text-danger">*</span></label>
        <input type="text" name="reference_number" class="form-control" required
            value="{{ isset($cloneFromInvoice) ? '' : (isset($invoice) ? $invoice->reference_number : '') }}"
            placeholder="Reference No.">
    </div>

    <div class="col-md-3 form-group">
        <label>Attachment</label>
        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        @isset($invoice->attachment)
            <small class="text-muted">Current file:
                <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank" class="text-primary">{{ basename($invoice->attachment) }}</a>
            </small>
        @endisset
    </div>

    <div class="col-md-6 col-lg-3 form-group">
        <label>Descriptions</label>
        {!! Form::textarea('descriptions', isset($cloneFromInvoice) ? $cloneFromInvoice->descriptions : null, [
            'class' => 'form-control',
            'placeholder' => 'Descriptions',
            'rows' => 2,
        ]) !!}
    </div>
    <div class="col-md-6 col-lg-3 form-group">
        <label>Notes</label>
        {!! Form::textarea('notes', isset($cloneFromInvoice) ? ($cloneFromInvoice->notes ?? null) : null, [
            'class' => 'form-control',
            'placeholder' => 'Notes',
            'rows' => 2,
        ]) !!}
    </div>
</div>

<div class="mt-3">
    <div class="card-header bg-blue m-0 mb-2">
        <h5 class="card-title mb-0">Charge Items</h5>
    </div>
    <div class="row align-items-end mb-3">
        <div class="col-md-6 col-lg-3 form-group mb-0">
            <label>Add charge columns</label>
            <select id="sim_charge_item_picker" class="form-select form-select-sm">
                <option value="">Select item…</option>
                @foreach($catalogItems as $item)
                    <option value="{{ $item['id'] }}"
                        data-name="{{ $item['name'] }}"
                        data-price="{{ $item['price'] }}"
                        data-vat="{{ $item['vat'] }}">{{ $item['name'] }}</option>
                @endforeach
            </select>
            <small class="text-muted">Selected items become columns on each SIM row.</small>
        </div>
        <div class="col-md-6 col-lg-9">
            <div id="sim_selected_items" class="sim-selected-items d-flex flex-wrap gap-1"></div>
        </div>
    </div>

    <div class="card-header bg-blue m-0 mb-2 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0">SIM Lines</h5>
            <div id="sim_lines_hint" class="text-white-50 small mt-1">Choose a company, add charge columns, then add SIM rows.</div>
        </div>
        <button type="button" class="btn btn-sm btn-light" id="sim_add_row">
            <i class="fa fa-plus"></i> Add SIM
        </button>
    </div>

    <div class="sim-pivot-wrap">
        <div class="sim-pivot-scroll">
            <table class="table table-sm sim-pivot-table" id="sim_pivot_table">
                <thead>
                    <tr id="sim_pivot_header">
                        <th class="sim-sim-col text-center">SIM <span class="text-danger">*</span></th>
                        <th class="sim-vat-col text-center">VAT %</th>
                        <th class="sim-total-col text-center">Total</th>
                        <th class="sim-actions-col"></th>
                    </tr>
                </thead>
                <tbody id="sim_pivot_body"></tbody>
                <tfoot id="sim_pivot_footer">
                    <tr>
                        <td class="text-end" id="sim_pivot_footer_label">Invoice totals</td>
                        <td></td>
                        <td class="text-end" id="sim_invoice_grand_total">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div id="sim_charge_item_ids_container"></div>
</div>

@push('page-scripts')
<script>
(function () {
    var simsOptions = @json($sims ?? ['' => 'Select company first']);
    var defaultVat = @json($defaultVat);
    var initialColumns = @json($initialColumns);
    var initialRows = @json($initialRows);
    var getSimsUrlTemplate = @json(route('simInvoices.getSims', ['id' => '__ID__']));
    var hasCompanyPreselected = @json((bool) $selectedCompany);

    var selectedColumns = [];
    var rowSeq = 0;
    var simsLoaded = false;

    function money(n) {
        return (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2);
    }

    function selectedCompanyId() {
        return $('#sim_invoice_company_id').val() || '';
    }

    function ensureSelect2($el, placeholder) {
        if (!$.fn.select2 || !$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({
            width: '100%',
            placeholder: placeholder || 'Select',
            allowClear: true
        });
    }

    function rebuildHiddenItemIds() {
        var $box = $('#sim_charge_item_ids_container').empty();
        selectedColumns.forEach(function (col) {
            $box.append('<input type="hidden" name="charge_item_ids[]" value="' + col.id + '">');
        });
        renderSelectedBadges();
    }

    function renderSelectedBadges() {
        var $wrap = $('#sim_selected_items').empty();
        selectedColumns.forEach(function (col) {
            var $badge = $('<span class="badge bg-primary d-inline-flex align-items-center gap-1"></span>');
            $badge.append(document.createTextNode(col.name + ' '));
            var $btn = $('<button type="button" class="btn btn-sm btn-link text-white p-0" title="Remove">&times;</button>');
            $btn.on('click', function () { removeColumn(col.id); });
            $badge.append($btn);
            $wrap.append($badge);
        });
    }

    function updateHint() {
        var hint;
        if (!selectedCompanyId()) {
            hint = 'Select a company to load its SIM numbers.';
        } else if (!simsLoaded) {
            hint = 'Loading SIMs for the selected company…';
        } else if (!selectedColumns.length) {
            hint = 'Add at least one charge item column, then add SIM rows.';
        } else {
            hint = 'Enter quantity per item. Totals use item rate × qty + VAT.';
        }
        $('#sim_lines_hint').text(hint);
        $('#sim_add_row').prop('disabled', !selectedCompanyId() || !selectedColumns.length);
    }

    function colSpanForEmpty() {
        return 3 + selectedColumns.length;
    }

    function renderEmptyState() {
        var $body = $('#sim_pivot_body');
        if ($body.find('tr.sim-pivot-row').length) {
            $body.find('tr.sim-pivot-empty-row').remove();
            return;
        }
        var msg = !selectedCompanyId()
            ? 'Select a company first, then add charge columns and SIM rows.'
            : (!selectedColumns.length
                ? 'Add charge item columns above to start entering amounts.'
                : 'No SIM rows yet. Click “Add SIM” to begin.');
        $body.html(
            '<tr class="sim-pivot-empty-row"><td class="sim-pivot-empty" colspan="' + colSpanForEmpty() + '">' +
            msg +
            '</td></tr>'
        );
    }

    function rebuildHeader() {
        var $tr = $('#sim_pivot_header').empty();
        $tr.append('<th class="sim-sim-col text-center">SIM <span class="text-danger">*</span></th>');
        selectedColumns.forEach(function (col) {
            var rate = money(Number(col.price || 0));
            $tr.append(
                '<th class="sim-charge-col text-center">' +
                '<span>' + $('<div>').text(col.name).html() + '</span>' +
                '<span class="sim-col-rate">Rate: ' + rate + ' · Qty</span>' +
                '</th>'
            );
        });
        $tr.append('<th class="sim-vat-col text-center">VAT %</th>');
        $tr.append('<th class="sim-total-col text-center">Total</th>');
        $tr.append('<th class="sim-actions-col"></th>');
        rebuildFooterStructure();
        rebuildHiddenItemIds();
        renderEmptyState();
        updateHint();
    }

    function rebuildFooterStructure() {
        var $tr = $('#sim_pivot_footer').empty().append('<tr></tr>').find('tr');
        $tr.append('<td class="text-end" id="sim_pivot_footer_label">Invoice totals</td>');
        selectedColumns.forEach(function (col) {
            $tr.append('<td class="text-end sim-col-total" data-item-id="' + col.id + '">0.00</td>');
        });
        $tr.append('<td></td>');
        $tr.append('<td class="text-end" id="sim_invoice_grand_total">0.00</td>');
        $tr.append('<td></td>');
    }

    function simSelectHtml(selectedId) {
        var placeholder = selectedCompanyId()
            ? (simsLoaded ? 'Select SIM' : 'Loading SIMs…')
            : 'Select company first';
        var html = '<select name="sim_id[]" class="form-select form-select-sm sim-row-sim select2" required' +
            (selectedCompanyId() && simsLoaded ? '' : ' disabled') +
            '><option value="">' + placeholder + '</option>';
        if (selectedCompanyId() && simsLoaded) {
            Object.keys(simsOptions).forEach(function (id) {
                if (id === '' || id === null) return;
                var sel = String(selectedId) === String(id) ? ' selected' : '';
                html += '<option value="' + id + '"' + sel + '>' + $('<div>').text(simsOptions[id]).html() + '</option>';
            });
        }
        html += '</select>';
        return html;
    }

    function refreshAllSimSelects(keepSelection) {
        $('#sim_pivot_body tr.sim-pivot-row').each(function () {
            var $sel = $(this).find('.sim-row-sim');
            var current = keepSelection ? $sel.val() : '';
            var html = simSelectHtml(current);
            var $parent = $sel.parent();
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
            }
            $sel.replaceWith(html);
            ensureSelect2($parent.find('.sim-row-sim'), selectedCompanyId() ? 'Select SIM' : 'Select company first');
        });
    }

    function chargeInputHtml(idx, col, qtyVal) {
        var rate = Number(col.price || 0);
        var qty = qtyVal === '' || qtyVal == null ? '' : String(qtyVal);
        return '<td class="sim-charge-col">' +
            '<input type="number" step="0.01" min="0" class="form-control form-control-sm sim-charge-input" ' +
            'name="charges[' + idx + '][' + col.id + ']" data-item-id="' + col.id + '" data-rate="' + money(rate) + '" ' +
            'placeholder="Qty" value="' + qty + '" title="Quantity × rate ' + money(rate) + '">' +
            '</td>';
    }

    function lineExclFromInput($input) {
        var qty = parseFloat($input.val()) || 0;
        var rate = parseFloat($input.data('rate')) || 0;
        return qty * rate;
    }

    function addRow(prefill) {
        prefill = prefill || {};
        if (!selectedCompanyId()) {
            alert('Select a company first.');
            return;
        }
        if (!selectedColumns.length) {
            alert('Add at least one charge item column first.');
            return;
        }

        $('#sim_pivot_body tr.sim-pivot-empty-row').remove();

        var idx = rowSeq++;
        var $tr = $('<tr class="sim-pivot-row" data-row-index="' + idx + '"></tr>');
        $tr.append('<td class="sim-sim-col">' + simSelectHtml(prefill.sim_id || '') + '</td>');

        selectedColumns.forEach(function (col) {
            var val = prefill.charges && prefill.charges[col.id] != null ? prefill.charges[col.id] : '';
            $tr.append(chargeInputHtml(idx, col, val === '' ? '' : Number(val)));
        });

        var vat = prefill.vat_percent != null ? prefill.vat_percent : defaultVat;
        $tr.append(
            '<td class="sim-vat-col">' +
            '<input type="number" step="0.01" min="0" class="form-control form-control-sm sim-vat-input" ' +
            'name="vat_percent[]" value="' + money(Number(vat)) + '">' +
            '</td>'
        );
        $tr.append('<td class="sim-total-col text-end fw-semibold sim-row-total">0.00</td>');
        $tr.append('<td class="sim-actions-col"><button type="button" class="btn btn-sm btn-icon btn-light-danger sim-remove-row" title="Remove row"><i class="fa fa-trash"></i></button></td>');

        $('#sim_pivot_body').append($tr);
        ensureSelect2($tr.find('.sim-row-sim'), 'Select SIM');
        recalcRow($tr);
        updateHint();
    }

    function syncRowChargeInputs($tr) {
        var idx = $tr.data('row-index');
        var existing = {};
        $tr.find('.sim-charge-input').each(function () {
            var name = $(this).attr('name') || '';
            var m = name.match(/charges\[\d+]\[(\d+)]/);
            if (m) existing[m[1]] = $(this).val();
        });

        $tr.find('.sim-charge-col').remove();
        var $vatTd = $tr.find('.sim-vat-col');
        selectedColumns.slice().reverse().forEach(function (col) {
            var val = existing[col.id] != null ? existing[col.id] : '';
            $vatTd.before(chargeInputHtml(idx, col, val));
        });
        recalcRow($tr);
    }

    function addColumn(col) {
        if (selectedColumns.some(function (c) { return Number(c.id) === Number(col.id); })) {
            return;
        }
        selectedColumns.push(col);
        rebuildHeader();
        $('#sim_pivot_body tr.sim-pivot-row').each(function () {
            syncRowChargeInputs($(this));
        });
        if ($('#sim_pivot_body tr.sim-pivot-row').length === 0 && selectedCompanyId() && simsLoaded) {
            addRow();
        } else {
            renderEmptyState();
        }
        recalcAll();
        updateHint();
    }

    function removeColumn(itemId) {
        selectedColumns = selectedColumns.filter(function (c) { return Number(c.id) !== Number(itemId); });
        rebuildHeader();
        $('#sim_pivot_body tr.sim-pivot-row').each(function () {
            syncRowChargeInputs($(this));
        });
        if (!selectedColumns.length) {
            $('#sim_pivot_body').empty();
        }
        renderEmptyState();
        recalcAll();
        updateHint();
    }

    function reindexRows() {
        $('#sim_pivot_body tr.sim-pivot-row').each(function (idx) {
            var $tr = $(this);
            $tr.attr('data-row-index', idx);
            $tr.find('.sim-charge-input').each(function () {
                var name = $(this).attr('name') || '';
                var m = name.match(/charges\[\d+]\[(\d+)]/);
                if (m) {
                    $(this).attr('name', 'charges[' + idx + '][' + m[1] + ']');
                }
            });
        });
    }

    function recalcRow($tr) {
        var excl = 0;
        $tr.find('.sim-charge-input').each(function () {
            excl += lineExclFromInput($(this));
        });
        var vatPct = parseFloat($tr.find('.sim-vat-input').val()) || 0;
        var total = excl + (excl * vatPct / 100);
        $tr.find('.sim-row-total').text(money(total));
        recalcAll();
    }

    function recalcAll() {
        var grand = 0;
        var colTotals = {};
        selectedColumns.forEach(function (col) {
            colTotals[col.id] = 0;
        });

        $('#sim_pivot_body tr.sim-pivot-row').each(function () {
            var excl = 0;
            $(this).find('.sim-charge-input').each(function () {
                var lineExcl = lineExclFromInput($(this));
                excl += lineExcl;
                var itemId = $(this).data('item-id');
                if (itemId != null && colTotals[itemId] != null) {
                    colTotals[itemId] += lineExcl;
                }
            });
            var vatPct = parseFloat($(this).find('.sim-vat-input').val()) || 0;
            grand += excl + (excl * vatPct / 100);
        });

        selectedColumns.forEach(function (col) {
            $('.sim-col-total[data-item-id="' + col.id + '"]').text(money(colTotals[col.id] || 0));
        });
        $('#sim_invoice_grand_total').text(money(grand));
    }

    function clearSims() {
        simsOptions = {};
        simsLoaded = false;
        refreshAllSimSelects(false);
        updateHint();
        renderEmptyState();
    }

    function loadSimsForCompany(companyId, keepSelection, done) {
        if (!companyId) {
            clearSims();
            if (typeof done === 'function') done();
            return;
        }

        simsLoaded = false;
        updateHint();
        refreshAllSimSelects(false);

        $.get(getSimsUrlTemplate.replace('__ID__', companyId))
            .done(function (resp) {
                var map = {};
                (resp.sims || []).forEach(function (sim) {
                    map[sim.id] = String(sim.number);
                });
                simsOptions = map;
                simsLoaded = true;
                refreshAllSimSelects(!!keepSelection);
                updateHint();
                renderEmptyState();
                if (typeof done === 'function') done();
            })
            .fail(function () {
                clearSims();
                alert('Could not load SIMs for the selected company.');
                if (typeof done === 'function') done();
            });
    }

    $(function () {
        ensureSelect2($('#sim_invoice_company_id'), 'Select company');
        ensureSelect2($('#sim_charge_item_picker'), 'Select item…');

        // Create starts with no SIMs; edit/clone already have company-scoped options.
        if (hasCompanyPreselected) {
            var seeded = {};
            Object.keys(simsOptions || {}).forEach(function (id) {
                if (id === '' || id === null) return;
                seeded[id] = simsOptions[id];
            });
            simsOptions = seeded;
            simsLoaded = true;
        } else {
            simsOptions = {};
            simsLoaded = false;
        }

        rebuildHeader();

        if (initialColumns.length) {
            initialColumns.forEach(function (col) { addColumn(col); });
        }

        if (initialRows.length && selectedCompanyId()) {
            $('#sim_pivot_body').empty();
            rowSeq = 0;
            initialRows.forEach(function (row) { addRow(row); });
        }

        renderEmptyState();
        updateHint();

        $('#sim_charge_item_picker').on('change', function () {
            var $opt = $(this).find('option:selected');
            var id = $opt.val();
            if (!id) return;
            addColumn({
                id: Number(id),
                name: $opt.data('name'),
                price: Number($opt.data('price') || 0),
                vat: Number($opt.data('vat') || 0)
            });
            $(this).val('').trigger('change');
        });

        $('#sim_add_row').on('click', function () {
            if (!selectedCompanyId()) {
                alert('Select a company first.');
                return;
            }
            if (!simsLoaded) {
                alert('Wait for SIMs to finish loading.');
                return;
            }
            if (!selectedColumns.length) {
                alert('Add at least one charge item column first.');
                return;
            }
            addRow();
        });

        $('#sim_pivot_body').on('click', '.sim-remove-row', function () {
            $(this).closest('tr').remove();
            reindexRows();
            renderEmptyState();
            recalcAll();
            updateHint();
        });

        $('#simInvoiceForm').on('submit', function () {
            reindexRows();
            if (!selectedCompanyId()) {
                alert('Select a company.');
                return false;
            }
            if (!selectedColumns.length) {
                alert('Add at least one charge item column.');
                return false;
            }
            if ($('#sim_pivot_body tr.sim-pivot-row').length === 0) {
                alert('Add at least one SIM row.');
                return false;
            }
        });

        $('#sim_pivot_body').on('input', '.sim-charge-input, .sim-vat-input', function () {
            recalcRow($(this).closest('tr'));
        });

        $('#sim_invoice_company_id').on('change', function () {
            var companyId = $(this).val();
            // Changing company invalidates current SIM selections.
            $('#sim_pivot_body tr.sim-pivot-row .sim-row-sim').val('').trigger('change');
            loadSimsForCompany(companyId, false);
        });

        @if(!$isClone)
        var companyId = selectedCompanyId();
        if (companyId && !simsLoaded) {
            loadSimsForCompany(companyId, true);
        }
        @else
        simsLoaded = true;
        updateHint();
        @endif
    });
})();
</script>
@endpush

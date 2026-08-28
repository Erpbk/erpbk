@extends('layouts.app')
@section('title', 'Import Paid Rider Vouchers')
@push('third_party_stylesheets')
<style>
    #filePreviewTable td, #filePreviewTable th { white-space: nowrap; font-size: 12px; padding: 4px 8px; vertical-align: middle; }
    #filePreviewTable thead th { background-color: #f8f9fa; position: sticky; top: 0; z-index: 5; }
    #filePreviewTable .col-head.mapped { background-color: #eaf3ff; }
    #filePreviewTable .col-head.dup-col { background-color: #fdeaea; }
    .map-badges { min-height: 18px; margin-top: 2px; }
    .map-badges .badge { font-size: 10px; font-weight: 500; margin: 0 2px 2px 0; display: inline-block; }
    .map-field select.map-select { font-size: 13px; }
    .map-field.is-unmapped select.map-select { border-color: #dc3545; }
    #mappingStatus.complete { color: #28a745; font-weight: 600; }
    #mappingStatus.incomplete { color: #d39e00; font-weight: 600; }
    #previewEmpty {
        min-height: 280px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 1.05rem;
        font-weight: 500;
    }
</style>
@endpush
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Import Paid Vouchers</h1></div>
            <div class="col-sm-6">
                <div class="d-flex justify-content-end">
                    <a class="btn btn-primary" href="{{ route('riderInvoices.index') }}">Back to Invoices</a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                Each row finds the unpaid rider invoice for that <strong>Rider ID + Billing Month</strong> and creates a
                payment voucher the same way as recording a payment on the invoice. Any amount marks the invoice paid.
                The credit (paying) account is matched by <strong>account code</strong>.
            </div>

            <form id="paidVoucherImportForm" action="{{ route('riderInvoices.importPaid') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="file">Import File</label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.csv,.xls" required>
                            <small class="text-muted">One row = one payment against the matching rider invoice.</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <a href="javascript:void(0);" id="toggleMappingHelp" class="small"><i class="fas fa-question-circle"></i> How does column mapping work?</a>
                </div>
                <div id="mappingHelpBox" class="alert alert-info mb-4" style="display:none;">
                    <h6 class="alert-heading mb-2"><i class="fas fa-info-circle"></i> How to map columns</h6>
                    <ul class="mb-0 pl-3">
                        <li>Select a file to preview columns, then map every field below.</li>
                        <li>Rows missing any mapped value, or without a matching unpaid invoice, are skipped.</li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-3">
                        <div id="fileReadError" class="alert alert-danger py-2 mb-3" style="display:none;">
                            <i class="fas fa-exclamation-triangle"></i> Could not read the selected file. Please choose a valid .xlsx or .csv file.
                        </div>

                        <div id="previewCard" class="card border mb-0">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>File Preview <small class="text-muted" id="previewFileName"></small></strong>
                                <span id="mappingStatus"></span>
                            </div>
                            <div class="card-body p-2">
                                <div id="previewEmpty">Select a File to Preview</div>
                                <div id="previewTableWrap" class="table-responsive" style="max-height: 480px; display:none;">
                                    <table class="table table-sm table-bordered mb-0" id="filePreviewTable"></table>
                                </div>
                                <div id="mappingWarning" class="text-danger small mt-2" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-3">
                        <h5 class="mb-3">Required Columns</h5>
                        <div class="row">
                            <div class="form-group col-md-6 map-field" data-field="rider_id" data-required="1">
                                <label for="col_rider_id">Rider ID</label>
                                <select name="col_rider_id" id="col_rider_id" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 map-field" data-field="billing_month" data-required="1">
                                <label for="col_billing_month">Billing Month</label>
                                <select name="col_billing_month" id="col_billing_month" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 map-field" data-field="account_code" data-required="1">
                                <label for="col_account_code">Paying Account Code</label>
                                <select name="col_account_code" id="col_account_code" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                                <small class="text-muted">Credit account, matched by account code</small>
                            </div>
                            <div class="form-group col-md-6 map-field" data-field="amount" data-required="1">
                                <label for="col_amount">Amount</label>
                                <select name="col_amount" id="col_amount" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 map-field" data-field="payment_date" data-required="1">
                                <label for="col_payment_date">Payment Date</label>
                                <select name="col_payment_date" id="col_payment_date" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 map-field" data-field="description" data-required="1">
                                <label for="col_description">Description</label>
                                <select name="col_description" id="col_description" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group text-center mt-3">
                    <button type="submit" class="btn btn-success" id="importBtn" disabled><i class="fas fa-upload"></i> Import Paid Vouchers</button>
                </div>

                <div id="importProgressWrap" class="mt-3" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong id="importProgressLabel">Uploading file...</strong>
                        <span id="importProgressPct">0%</span>
                    </div>
                    <div class="progress" style="height: 22px;">
                        <div id="importProgressBar"
                             class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                             role="progressbar"
                             style="width: 0%;"
                             aria-valuenow="0"
                             aria-valuemin="0"
                             aria-valuemax="100">0%</div>
                    </div>
                    <small class="text-muted" id="importProgressHint">Please keep this page open until import finishes.</small>
                </div>

                <div id="importResult" class="mt-3" style="display:none;"></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="{{ asset('assets/vendor/libs/xlsx/xlsx.full.min.js') }}"></script>
<script>
(function () {
    var processTimer = null;

    function setProgress(pct, label) {
        pct = Math.max(0, Math.min(100, Math.round(pct)));
        $('#importProgressBar').css('width', pct + '%').attr('aria-valuenow', pct).text(pct + '%');
        $('#importProgressPct').text(pct + '%');
        if (label) $('#importProgressLabel').text(label);
    }

    function startProcessingAnimation() {
        var pct = 55;
        setProgress(pct, 'Creating payment vouchers...');
        clearInterval(processTimer);
        processTimer = setInterval(function () {
            if (pct >= 92) return;
            pct += Math.max(0.4, (92 - pct) * 0.04);
            setProgress(pct, 'Creating payment vouchers...');
        }, 400);
    }

    function stopProgress(success, detailMessage) {
        clearInterval(processTimer);
        processTimer = null;
        setProgress(100, success ? 'Import complete' : 'Import failed');
        $('#importProgressBar').removeClass('progress-bar-animated').toggleClass('bg-success', !!success).toggleClass('bg-danger', !success);
        if (detailMessage) $('#importProgressHint').text(detailMessage);
    }

    function resetProgressUi() {
        clearInterval(processTimer);
        processTimer = null;
        $('#importProgressWrap').hide();
        $('#importProgressBar').addClass('progress-bar-animated progress-bar-striped').removeClass('bg-danger').addClass('bg-success');
        setProgress(0, 'Uploading file...');
        $('#importProgressHint').text('Please keep this page open until import finishes.');
    }

    $('#paidVoucherImportForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        $('#importBtn').prop('disabled', true);
        $('#importResult').hide().empty();
        resetProgressUi();
        $('#importProgressWrap').show();
        setProgress(0, 'Uploading file...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function (event) {
                        if (!event.lengthComputable) return;
                        setProgress((event.loaded / event.total) * 50, 'Uploading file...');
                        if (event.loaded >= event.total) startProcessingAnimation();
                    }, false);
                }
                return xhr;
            },
            beforeSend: function () {
                setTimeout(function () { if (!processTimer) startProcessingAnimation(); }, 800);
            },
            success: function (response) {
                var imported = parseInt((response && response.imported_count) || 0, 10) || 0;
                var skipped = parseInt((response && response.skipped_count) || 0, 10) || 0;
                var skippedLog = [];
                if (response && response.skipped_log) {
                    skippedLog = Array.isArray(response.skipped_log) ? response.skipped_log : Object.values(response.skipped_log);
                }
                var countLabel = imported + ' payment' + (imported === 1 ? '' : 's') + ' created';
                if (skipped > 0) countLabel += ', ' + skipped + ' skipped';
                stopProgress(true, countLabel);

                var message = (response && response.message) ? response.message : ('Import finished. Created: ' + imported + '.');
                var html = '<div class="alert alert-success mb-2">' + $('<div>').text(message).html() + '</div>';
                if (skippedLog.length || skipped > 0) {
                    html += '<div class="alert alert-warning mb-2"><strong>Skipped rows (' + (skippedLog.length || skipped) + '):</strong>';
                    if (skippedLog.length) {
                        html += '<ul class="mb-0 mt-2 pl-3" style="max-height:240px;overflow:auto;">';
                        skippedLog.forEach(function (line) {
                            html += '<li>' + $('<div>').text(line).html() + '</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    html += '<div class="text-center"><a href="{{ route('riderInvoices.index') }}" class="btn btn-primary">Back to Invoices</a></div>';
                }

                $('#importProgressWrap').hide();
                $('#importResult').html(html).show();
                $('#importBtn').prop('disabled', false);

                if (skipped === 0 && !skippedLog.length) {
                    setTimeout(function () { window.location.href = "{{ route('riderInvoices.index') }}"; }, 1500);
                }
            },
            error: function (xhr) {
                stopProgress(false, 'Import failed');
                var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Import failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    message = xhr.responseJSON.errors[firstKey][0] || message;
                }
                $('#importProgressWrap').hide();
                $('#importResult').html('<div class="alert alert-danger">' + $('<div>').text(message).html() + '</div>').show();
                $('#importBtn').prop('disabled', false);
            }
        });
    });
})();

(function () {
    var FIELDS = [
        { key: 'rider_id', label: 'Rider ID', required: true, match: [/rider id/, /rider_id/, /\bid\b/] },
        { key: 'billing_month', label: 'Billing Month', required: true, match: [/billing/, /\bmonth\b/] },
        { key: 'account_code', label: 'Account Code', required: true, match: [/account code/, /account_code/, /acc(ount)?\s*code/, /\bcode\b/] },
        { key: 'amount', label: 'Amount', required: true, match: [/amount/, /\bpaid\b/, /payment amt/] },
        { key: 'payment_date', label: 'Payment Date', required: true, match: [/payment date/, /date of payment/, /\bdate\b/] },
        { key: 'description', label: 'Description', required: true, match: [/description/, /narration/, /remarks/] }
    ];
    var TOTAL_REQUIRED = FIELDS.filter(function (f) { return f.required; }).length;
    var MAX_COLS = 60;
    var PREVIEW_ROWS = 20;
    var previewActive = false;
    var headers = [];
    var colCount = 0;

    function colLetter(n) {
        var s = '';
        while (n > 0) {
            var m = (n - 1) % 26;
            s = String.fromCharCode(65 + m) + s;
            n = Math.floor((n - 1) / 26);
        }
        return s;
    }
    function normalize(text) { return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim(); }
    function escapeHtml(text) {
        return String(text == null ? '' : text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function truncate(text, len) {
        text = String(text == null ? '' : text);
        return text.length > len ? text.slice(0, len) + '…' : text;
    }
    function getMapping() {
        var mapping = {};
        FIELDS.forEach(function (f) {
            var v = $('#col_' + f.key).val();
            mapping[f.key] = v ? parseInt(v, 10) : null;
        });
        return mapping;
    }
    function autoMap() {
        var mapping = {}, assigned = {};
        FIELDS.forEach(function (f) { mapping[f.key] = null; });
        FIELDS.forEach(function (f) {
            for (var i = 0; i < f.match.length && !mapping[f.key]; i++) {
                for (var c = 1; c <= colCount; c++) {
                    if (assigned[c]) continue;
                    var h = normalize(headers[c - 1]);
                    if (h && f.match[i].test(h)) {
                        mapping[f.key] = c;
                        assigned[c] = f.key;
                        break;
                    }
                }
            }
        });
        return mapping;
    }
    function buildSelects(mapping) {
        $('.map-field').each(function () {
            var key = $(this).data('field');
            var field = FIELDS.filter(function (f) { return f.key === key; })[0];
            var $sel = $(this).find('select.map-select');
            $sel.empty().prop('disabled', false);
            $sel.append($('<option>', { value: '', text: field.required ? 'Select column…' : '— Not mapped —' }));
            for (var c = 1; c <= colCount; c++) {
                var label = colLetter(c);
                var h = String(headers[c - 1] || '').trim();
                if (h) label += ' — ' + truncate(h, 28);
                $sel.append($('<option>', { value: c, text: label }));
            }
            $sel.val(mapping[key] ? String(mapping[key]) : '');
        });
    }
    function resetSelects() {
        $('.map-field').each(function () {
            $(this).find('select.map-select').empty().append($('<option>', { value: '', text: 'Select a file first…' })).prop('disabled', true);
            $(this).removeClass('is-unmapped');
        });
    }
    function renderPreview(rows) {
        var html = '<thead><tr><th class="text-muted text-center" style="width:36px;">#</th>';
        for (var c = 1; c <= colCount; c++) {
            html += '<th class="text-center col-head" data-col="' + c + '"><div>' + colLetter(c) + '</div><div class="map-badges"></div></th>';
        }
        html += '</tr></thead><tbody>';
        rows.forEach(function (row, idx) {
            html += '<tr><th class="text-muted text-center">' + (idx + 1) + '</th>';
            for (var c = 1; c <= colCount; c++) html += '<td>' + escapeHtml(truncate(row[c - 1], 40)) + '</td>';
            html += '</tr>';
        });
        html += '</tbody>';
        $('#filePreviewTable').html(html);
    }
    function requiredDuplicates(mapping) {
        var byCol = {};
        FIELDS.forEach(function (f) {
            if (!f.required || !mapping[f.key]) return;
            (byCol[mapping[f.key]] = byCol[mapping[f.key]] || []).push(f);
        });
        var dups = [];
        Object.keys(byCol).forEach(function (c) {
            if (byCol[c].length > 1) dups.push({ col: parseInt(c, 10), fields: byCol[c] });
        });
        return dups;
    }
    function updateUi() {
        if (!previewActive) return;
        var mapping = getMapping();
        var dups = requiredDuplicates(mapping);
        var dupCols = {};
        dups.forEach(function (d) { dupCols[d.col] = true; });
        $('#filePreviewTable .col-head').each(function () {
            var c = parseInt($(this).attr('data-col'), 10);
            var $badges = $(this).find('.map-badges').empty();
            var mapped = false;
            FIELDS.forEach(function (f) {
                if (mapping[f.key] !== c) return;
                mapped = true;
                $badges.append('<span class="badge ' + (dupCols[c] ? 'bg-danger' : (f.required ? 'bg-primary' : 'bg-secondary')) + ' text-white">' + f.label + '</span>');
            });
            $(this).toggleClass('mapped', mapped && !dupCols[c]).toggleClass('dup-col', !!dupCols[c]);
        });
        $('.map-field[data-required="1"]').each(function () {
            $(this).toggleClass('is-unmapped', !mapping[$(this).data('field')]);
        });
        var mappedRequired = FIELDS.filter(function (f) { return f.required && mapping[f.key]; }).length;
        var complete = mappedRequired === TOTAL_REQUIRED && dups.length === 0;
        $('#mappingStatus').text(mappedRequired + ' of ' + TOTAL_REQUIRED + ' required columns mapped')
            .toggleClass('complete', complete).toggleClass('incomplete', !complete);
        if (dups.length) {
            $('#mappingWarning').text('Duplicate mapping: ' + dups.map(function (d) {
                return 'column ' + colLetter(d.col) + ' is assigned to ' + d.fields.map(function (f) { return f.label; }).join(' and ');
            }).join('; ') + '.').show();
        } else {
            $('#mappingWarning').hide().empty();
        }
        $('#importBtn').prop('disabled', !complete);
    }
    function showPreviewMode(fileName, rows) {
        previewActive = true;
        $('#fileReadError').hide();
        $('#previewFileName').text('— ' + fileName);
        $('#previewEmpty').hide();
        $('#previewTableWrap').show();
        renderPreview(rows);
        buildSelects(autoMap());
        updateUi();
    }
    function resetMappingUi(showError) {
        previewActive = false;
        resetSelects();
        $('#previewFileName').text('');
        $('#mappingStatus').text('').removeClass('complete incomplete');
        $('#filePreviewTable').empty();
        $('#previewTableWrap').hide();
        $('#previewEmpty').css('display', 'flex');
        $('#mappingWarning').hide().empty();
        $('#importBtn').prop('disabled', true);
        $('#fileReadError').toggle(!!showError);
    }

    $(document).on('change', '.map-select', updateUi);
    $('#toggleMappingHelp').on('click', function () { $('#mappingHelpBox').slideToggle(150); });
    $('#file').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) { resetMappingUi(false); return; }
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var workbook = XLSX.read(new Uint8Array(e.target.result), { type: 'array', sheetRows: PREVIEW_ROWS + 1 });
                var sheet = workbook.Sheets[workbook.SheetNames[0]];
                var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                colCount = 0;
                rows.forEach(function (row) { colCount = Math.max(colCount, row.length); });
                colCount = Math.min(colCount, MAX_COLS);
                if (!rows.length || !colCount) throw new Error('Empty sheet');
                headers = (rows[0] || []).slice(0, colCount);
                showPreviewMode(file.name, rows.slice(0, PREVIEW_ROWS));
            } catch (err) {
                resetMappingUi(true);
            }
        };
        reader.onerror = function () { resetMappingUi(true); };
        reader.readAsArrayBuffer(file);
    });
})();
</script>
@endsection

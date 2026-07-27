@extends('layouts.app')
@section('title', 'Import Salik Records')
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
</style>
@endpush
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Import Salik Records</h1></div>
            <div class="col-sm-6 text-right">
                <a class="btn btn-primary float-right" href="{{ route('salik.index') }}">Back to Salik List</a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="card">
        <div class="card-body">
            <form id="salikImportForm" action="{{ route('salik.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="file">Import File</label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.csv" required>
                            <small class="text-muted">Upload the Excel file containing Salik records.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="admin_charge_per_salik">Default Admin Charge per Salik</label>
                            <input type="number" name="admin_charge_per_salik" id="admin_charge_per_salik" class="form-control" step="0.01" min="0" value="0">
                            <small class="text-muted">Applied to every imported salik. Leave 0 for none.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="salik_vat_percent">Salik VAT %</label>
                            <input type="number" name="salik_vat_percent" id="salik_vat_percent" class="form-control" step="0.01" min="0" value="0">
                            <small class="text-muted">Applied to all records (amount × %).</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="admin_vat_percent">Admin VAT %</label>
                            <input type="number" name="admin_vat_percent" id="admin_vat_percent" class="form-control" step="0.01" min="0" value="0">
                            <small class="text-muted">Applied to all records (admin charge × %).</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <a href="javascript:void(0);" id="toggleMappingHelp" class="small"><i class="fas fa-question-circle"></i> How does column mapping work?</a>
                </div>
                <div id="mappingHelpBox" class="alert alert-info mb-4" style="display:none;">
                    <h6 class="alert-heading mb-2"><i class="fas fa-info-circle"></i> How to map columns</h6>
                    <ul class="mb-3 pl-3">
                        <li>Select a file above and a preview of its columns will appear. Each field below becomes a dropdown listing the file's columns (<strong>A</strong>, <strong>B</strong>, <strong>C</strong>, ...) — pick the matching one. Mappings are suggested automatically from the header row.</li>
                        <li>If trip date and time are in the <strong>same cell</strong>, select that <strong>same column</strong> for both Trip Date and Trip Time.</li>
                        <li><strong>Merged cells:</strong> use the <strong>leftmost</strong> column in the merge (where Excel stores the value). Example: if Date+Time is merged across columns B and C, select column <strong>B</strong> for both Trip Date and Trip Time.</li>
                        <li>Leave optional columns unmapped to use the defaults shown under each field.</li>
                    </ul>

                    <p class="mb-2"><strong>Example Excel layout</strong> (header row + data):</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white mb-2" style="max-width: 720px;">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-muted" style="width: 48px;"></th>
                                    <th class="text-center">A</th>
                                    <th class="text-center" colspan="2">B–C merged <small class="text-muted">(use B)</small></th>
                                    <th class="text-center">D</th>
                                    <th class="text-center">E</th>
                                    <th class="text-center">F</th>
                                </tr>
                                <tr>
                                    <th class="text-muted">1</th>
                                    <th>Transaction ID</th>
                                    <th colspan="2">Trip Date / Time</th>
                                    <th>Toll Gate</th>
                                    <th>Plate</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th class="text-muted">2</th>
                                    <td>1234567890</td>
                                    <td colspan="2">25/07/2026 09:15 AM</td>
                                    <td>Al Garhoud</td>
                                    <td>12345</td>
                                    <td>4.00</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">3</th>
                                    <td>1234567891</td>
                                    <td colspan="2">25/07/2026 10:02 AM</td>
                                    <td>Business Bay</td>
                                    <td>12345</td>
                                    <td>4.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mb-0 small">
                        For the sample above you would map:
                        Transaction ID = <strong>A</strong>,
                        Trip Date = <strong>B</strong>,
                        Trip Time = <strong>B</strong>,
                        Toll Gate = <strong>D</strong>,
                        Plate = <strong>E</strong>,
                        Amount = <strong>F</strong>
                        (skip column C because it is part of the B–C merge).
                    </p>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-3">
                        <div id="chooseFileNote" class="alert alert-info py-2 mb-3">
                            <i class="fas fa-info-circle"></i> Choose an import file above to preview it and map its columns.
                        </div>
                        <div id="fileReadError" class="alert alert-danger py-2 mb-3" style="display:none;">
                            <i class="fas fa-exclamation-triangle"></i> Could not read the selected file. Please choose a valid .xlsx or .csv file.
                        </div>

                        <div id="previewCard" class="card border mb-0" style="display:none;">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <strong>File Preview <small class="text-muted" id="previewFileName"></small></strong>
                                <span id="mappingStatus"></span>
                            </div>
                            <div class="card-body p-2">
                                <div class="table-responsive" style="max-height: 480px;">
                                    <table class="table table-sm table-bordered mb-0" id="filePreviewTable"></table>
                                </div>
                                <div id="mappingWarning" class="text-danger small mt-2" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-3">
                        <h5 class="mb-3">Required Columns</h5>
                        <div class="row">
                            <div class="form-group col-md-4 map-field" data-field="transaction_id" data-required="1">
                                <label for="col_transaction_id">Transaction ID</label>
                                <select name="col_transaction_id" id="col_transaction_id" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 map-field" data-field="trip_date" data-required="1">
                                <label for="col_trip_date">Trip Date</label>
                                <select name="col_trip_date" id="col_trip_date" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 map-field" data-field="trip_time" data-required="1">
                                <label for="col_trip_time">Trip Time</label>
                                <select name="col_trip_time" id="col_trip_time" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 map-field" data-field="toll_gate" data-required="1">
                                <label for="col_toll_gate">Toll Gate</label>
                                <select name="col_toll_gate" id="col_toll_gate" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 map-field" data-field="direction" data-required="1">
                                <label for="col_direction">Direction</label>
                                <select name="col_direction" id="col_direction" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 map-field" data-field="tag_number" data-required="1">
                                <label for="col_tag_number">Tag Number</label>
                                <select name="col_tag_number" id="col_tag_number" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 map-field" data-field="plate" data-required="1">
                                <label for="col_plate">Plate Number</label>
                                <select name="col_plate" id="col_plate" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 map-field" data-field="amount" data-required="1">
                                <label for="col_amount">Amount</label>
                                <select name="col_amount" id="col_amount" class="form-control map-select" required disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                            </div>
                        </div>

                        <h5 class="mb-3 mt-2">Optional Columns</h5>
                        <div class="row">
                            <div class="form-group col-md-6 map-field" data-field="transaction_post_date" data-required="0">
                                <label for="col_transaction_post_date">Transaction Post Date</label>
                                <select name="col_transaction_post_date" id="col_transaction_post_date" class="form-control map-select" disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                                <small class="text-muted">Default: mirrors trip date</small>
                            </div>
                            <div class="form-group col-md-6 map-field" data-field="billing_month" data-required="0">
                                <label for="col_billing_month">Billing Month</label>
                                <select name="col_billing_month" id="col_billing_month" class="form-control map-select" disabled>
                                    <option value="">Select a file first…</option>
                                </select>
                                <small class="text-muted">Default: from trip date</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group text-center mt-3">
                    <button type="submit" class="btn btn-success" id="importBtn" disabled><i class="fas fa-upload"></i> Import Salik Records</button>
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
        $('#importProgressBar')
            .css('width', pct + '%')
            .attr('aria-valuenow', pct)
            .text(pct + '%');
        $('#importProgressPct').text(pct + '%');
        if (label) {
            $('#importProgressLabel').text(label);
        }
    }

    function startProcessingAnimation() {
        var pct = 55;
        setProgress(pct, 'Processing Salik records...');
        clearInterval(processTimer);
        processTimer = setInterval(function () {
            if (pct >= 92) {
                return;
            }
            pct += Math.max(0.4, (92 - pct) * 0.04);
            setProgress(pct, 'Processing Salik records...');
        }, 400);
    }

    function stopProgress(success, detailMessage) {
        clearInterval(processTimer);
        processTimer = null;
        setProgress(100, success ? 'Import complete' : 'Import failed');
        $('#importProgressBar')
            .removeClass('progress-bar-animated')
            .toggleClass('bg-success', !!success)
            .toggleClass('bg-danger', !success);
        if (detailMessage) {
            $('#importProgressHint').text(detailMessage);
        }
    }

    function resetProgressUi() {
        clearInterval(processTimer);
        processTimer = null;
        $('#importProgressWrap').hide();
        $('#importProgressBar')
            .addClass('progress-bar-animated progress-bar-striped')
            .removeClass('bg-danger')
            .addClass('bg-success');
        setProgress(0, 'Uploading file...');
        $('#importProgressHint').text('Please keep this page open until import finishes.');
    }

    $('#salikImportForm').on('submit', function (e) {
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
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function (event) {
                        if (!event.lengthComputable) {
                            return;
                        }
                        // Reserve 0–50% for upload, rest for server processing
                        var uploadPct = (event.loaded / event.total) * 50;
                        setProgress(uploadPct, 'Uploading file...');
                        if (event.loaded >= event.total) {
                            startProcessingAnimation();
                        }
                    }, false);
                }
                return xhr;
            },
            beforeSend: function () {
                // Fallback when upload progress events are unavailable / too fast
                setTimeout(function () {
                    if (!processTimer) {
                        startProcessingAnimation();
                    }
                }, 800);
            },
            success: function (response) {
                var imported = (response && typeof response.imported_count !== 'undefined')
                    ? parseInt(response.imported_count, 10)
                    : 0;
                if (isNaN(imported)) {
                    imported = 0;
                }
                var countLabel = imported + ' record' + (imported === 1 ? '' : 's') + ' imported';

                stopProgress(true, countLabel);
                setProgress(100, countLabel);

                var message = (response && response.message)
                    ? response.message
                    : ('Salik records imported successfully. Records imported: ' + imported + '.');

                $('#importResult').html('<div class="alert alert-success">' + message + '</div>').show();
                setTimeout(function () {
                    window.location.href = "{{ route('salik.index') }}";
                }, 1500);
            },
            error: function (xhr) {
                stopProgress(false, 'Import failed');
                var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Import failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    message = xhr.responseJSON.errors[firstKey][0] || message;
                }
                $('#importResult').html('<div class="alert alert-danger">' + message + '</div>').show();
                $('#importBtn').prop('disabled', false);
            }
        });
    });
})();

/* ---- Column mapping: file preview, dropdown mapping, auto-suggestion ---- */
(function () {
    // Order matters for auto-mapping: more specific matchers (e.g. Post Date) run
    // before generic ones (e.g. Trip Date matching plain "date").
    var FIELDS = [
        { key: 'transaction_post_date', label: 'Post Date', required: false, match: [/post\w* date/, /posting/] },
        { key: 'transaction_id', label: 'Transaction ID', required: true, match: [/transaction/, /txn/, /trans (id|no)/] },
        { key: 'trip_date', label: 'Trip Date', required: true, match: [/trip date/, /trip.*date/, /\bdate\b/] },
        { key: 'trip_time', label: 'Trip Time', required: true, match: [/trip time/, /\btime\b/] },
        { key: 'toll_gate', label: 'Toll Gate', required: true, match: [/toll/, /\bgate\b/] },
        { key: 'direction', label: 'Direction', required: true, match: [/direction/] },
        { key: 'tag_number', label: 'Tag Number', required: true, match: [/\btag\b/] },
        { key: 'plate', label: 'Plate Number', required: true, match: [/plate/] },
        { key: 'amount', label: 'Amount', required: true, match: [/amount/, /charge/] },
        { key: 'billing_month', label: 'Billing Month', required: false, match: [/billing/, /\bmonth\b/] }
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

    function normalize(text) {
        return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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
        var mapping = {};
        var assigned = {};
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

        // Combined "Trip Date/Time" column: reuse the trip date column for trip time
        if (!mapping.trip_time && mapping.trip_date && /time/.test(normalize(headers[mapping.trip_date - 1]))) {
            mapping.trip_time = mapping.trip_date;
        }
        return mapping;
    }

    function buildSelects(mapping) {
        $('.map-field').each(function () {
            var $wrap = $(this);
            var key = $wrap.data('field');
            var field = FIELDS.filter(function (f) { return f.key === key; })[0];
            var $sel = $wrap.find('select.map-select');
            $sel.empty().prop('disabled', false);
            $sel.append($('<option>', { value: '', text: field.required ? 'Select column…' : '— Not mapped —' }));
            for (var c = 1; c <= colCount; c++) {
                var label = colLetter(c);
                var h = String(headers[c - 1] || '').trim();
                if (h) {
                    label += ' — ' + truncate(h, 28);
                }
                $sel.append($('<option>', { value: c, text: label }));
            }
            $sel.val(mapping[key] ? String(mapping[key]) : '');
        });
    }

    function resetSelects() {
        $('.map-field').each(function () {
            $(this).find('select.map-select')
                .empty()
                .append($('<option>', { value: '', text: 'Select a file first…' }))
                .prop('disabled', true);
            $(this).removeClass('is-unmapped');
        });
    }

    function renderPreview(rows) {
        var html = '<thead><tr><th class="text-muted text-center" style="width:36px;">#</th>';
        for (var c = 1; c <= colCount; c++) {
            html += '<th class="text-center col-head" data-col="' + c + '">'
                + '<div>' + colLetter(c) + '</div>'
                + '<div class="map-badges"></div>'
                + '</th>';
        }
        html += '</tr></thead><tbody>';
        rows.forEach(function (row, idx) {
            html += '<tr><th class="text-muted text-center">' + (idx + 1) + '</th>';
            for (var c = 1; c <= colCount; c++) {
                html += '<td>' + escapeHtml(truncate(row[c - 1], 40)) + '</td>';
            }
            html += '</tr>';
        });
        html += '</tbody>';
        $('#filePreviewTable').html(html);
    }

    function requiredDuplicates(mapping) {
        var byCol = {};
        FIELDS.forEach(function (f) {
            if (!f.required || !mapping[f.key]) return;
            var c = mapping[f.key];
            (byCol[c] = byCol[c] || []).push(f);
        });
        var dups = [];
        Object.keys(byCol).forEach(function (c) {
            var fields = byCol[c];
            if (fields.length < 2) return;
            var keys = fields.map(function (f) { return f.key; });
            // Trip Date + Trip Time may legitimately share a column (combined date/time cell)
            var allowedPair = keys.length === 2 && keys.indexOf('trip_date') !== -1 && keys.indexOf('trip_time') !== -1;
            if (!allowedPair) {
                dups.push({ col: parseInt(c, 10), fields: fields });
            }
        });
        return dups;
    }

    function updateUi() {
        if (!previewActive) return;

        var mapping = getMapping();
        var dups = requiredDuplicates(mapping);
        var dupCols = {};
        dups.forEach(function (d) { dupCols[d.col] = true; });

        // Badges on preview columns
        $('#filePreviewTable .col-head').each(function () {
            var c = parseInt($(this).attr('data-col'), 10);
            var $badges = $(this).find('.map-badges').empty();
            var mapped = false;
            FIELDS.forEach(function (f) {
                if (mapping[f.key] !== c) return;
                mapped = true;
                var cls = dupCols[c] ? 'bg-danger' : (f.required ? 'bg-primary' : 'bg-secondary');
                $badges.append('<span class="badge ' + cls + ' text-white">' + f.label + '</span>');
            });
            $(this).toggleClass('mapped', mapped && !dupCols[c]);
            $(this).toggleClass('dup-col', !!dupCols[c]);
        });

        // Unmapped highlight on required selects
        $('.map-field[data-required="1"]').each(function () {
            var key = $(this).data('field');
            $(this).toggleClass('is-unmapped', !mapping[key]);
        });

        // Completeness indicator
        var mappedRequired = FIELDS.filter(function (f) { return f.required && mapping[f.key]; }).length;
        var complete = mappedRequired === TOTAL_REQUIRED && dups.length === 0;
        $('#mappingStatus')
            .text(mappedRequired + ' of ' + TOTAL_REQUIRED + ' required columns mapped')
            .toggleClass('complete', complete)
            .toggleClass('incomplete', !complete);

        // Duplicate warning
        if (dups.length) {
            var parts = dups.map(function (d) {
                var names = d.fields.map(function (f) { return f.label; }).join(' and ');
                return 'column ' + colLetter(d.col) + ' is assigned to ' + names;
            });
            $('#mappingWarning').text('Duplicate mapping: ' + parts.join('; ') + '.').show();
        } else {
            $('#mappingWarning').hide().empty();
        }

        $('#importBtn').prop('disabled', !complete);
    }

    function showPreviewMode(fileName, rows) {
        previewActive = true;
        $('#chooseFileNote').hide();
        $('#fileReadError').hide();
        $('#previewFileName').text('— ' + fileName);
        renderPreview(rows);
        buildSelects(autoMap());
        $('#previewCard').show();
        updateUi();
    }

    function resetMappingUi(showError) {
        previewActive = false;
        resetSelects();
        $('#previewCard').hide();
        $('#filePreviewTable').empty();
        $('#mappingWarning').hide().empty();
        $('#importBtn').prop('disabled', true);
        $('#fileReadError').toggle(!!showError);
        $('#chooseFileNote').toggle(!showError);
    }

    $(document).on('change', '.map-select', updateUi);

    $('#toggleMappingHelp').on('click', function () {
        $('#mappingHelpBox').slideToggle(150);
    });

    $('#file').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) {
            resetMappingUi(false);
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var workbook = XLSX.read(new Uint8Array(e.target.result), { type: 'array', sheetRows: PREVIEW_ROWS + 1 });
                var sheet = workbook.Sheets[workbook.SheetNames[0]];
                var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                colCount = 0;
                rows.forEach(function (row) { colCount = Math.max(colCount, row.length); });
                colCount = Math.min(colCount, MAX_COLS);
                if (!rows.length || !colCount) {
                    throw new Error('Empty sheet');
                }
                headers = (rows[0] || []).slice(0, colCount);
                showPreviewMode(file.name, rows.slice(0, PREVIEW_ROWS));
            } catch (err) {
                resetMappingUi(true);
            }
        };
        reader.onerror = function () {
            resetMappingUi(true);
        };
        reader.readAsArrayBuffer(file);
    });
})();
</script>
@endsection

@extends('layouts.app')
@section('title', 'Import Salik Records')
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
                            <small class="text-muted">Used when Admin Charges column is not mapped, or cell is empty.</small>
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

                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading mb-2"><i class="fas fa-info-circle"></i> How to map columns</h6>
                    <ul class="mb-3 pl-3">
                        <li>Column numbers are <strong>1-based</strong>: Excel column <strong>A = 1</strong>, <strong>B = 2</strong>, <strong>C = 3</strong>, and so on.</li>
                        <li>If trip date and time are in the <strong>same cell</strong>, enter that <strong>same column number</strong> for both Trip Date and Trip Time.</li>
                        <li><strong>Merged cells:</strong> use the number of the <strong>leftmost</strong> column in the merge (where Excel stores the value). Example: if Date+Time is merged across columns B and C, enter <strong>2</strong> for both Trip Date and Trip Time.</li>
                        <li>Leave optional columns blank to use the defaults shown under each field.</li>
                    </ul>

                    <p class="mb-2"><strong>Example Excel layout</strong> (header row + data):</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white mb-2" style="max-width: 720px;">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-muted" style="width: 48px;"></th>
                                    <th class="text-center">A <small class="text-muted">(1)</small></th>
                                    <th class="text-center" colspan="2">B–C merged <small class="text-muted">(use 2)</small></th>
                                    <th class="text-center">D <small class="text-muted">(4)</small></th>
                                    <th class="text-center">E <small class="text-muted">(5)</small></th>
                                    <th class="text-center">F <small class="text-muted">(6)</small></th>
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
                        Transaction ID = <strong>1</strong>,
                        Trip Date = <strong>2</strong>,
                        Trip Time = <strong>2</strong>,
                        Toll Gate = <strong>4</strong>,
                        Plate = <strong>5</strong>,
                        Amount = <strong>6</strong>
                        (skip column C because it is part of the B–C merge).
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                        <h5 class="mb-3">Required Columns</h5>
                        <div class="form-group col-md-4">
                            <label for="col_transaction_id">Transaction ID</label>
                            <input type="number" name="col_transaction_id" id="col_transaction_id" class="form-control" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_trip_date">Trip Date</label>
                            <input type="number" name="col_trip_date" id="col_trip_date" class="form-control" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_trip_time">Trip Time</label>
                            <input type="number" name="col_trip_time" id="col_trip_time" class="form-control" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_toll_gate">Toll Gate</label>
                            <input type="number" name="col_toll_gate" id="col_toll_gate" class="form-control" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_direction">Direction</label>
                            <input type="number" name="col_direction" id="col_direction" class="form-control" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_tag_number">Tag Number</label>
                            <input type="number" name="col_tag_number" id="col_tag_number" class="form-control" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_plate">Plate Number</label>
                            <input type="number" name="col_plate" id="col_plate" class="form-control" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_amount">Amount</label>
                            <input type="number" name="col_amount" id="col_amount" class="form-control" min="1" required>
                        </div>
                        </div>
                    </div>
                    <div class="col-md-1"></div>
                    <div class="col-md-7">
                        <h5 class="mb-3">Optional Columns</h5>
                        <div class="row">
                        <div class="form-group col-md-4">
                            <label for="col_transaction_post_date">Transaction Post Date</label>
                            <input type="number" name="col_transaction_post_date" id="col_transaction_post_date" class="form-control" min="1">
                            <small class="text-muted">Default: mirrors trip date</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_billing_month">Billing Month</label>
                            <input type="number" name="col_billing_month" id="col_billing_month" class="form-control" min="1">
                            <small class="text-muted">Default: from trip date</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_details">Details</label>
                            <input type="number" name="col_details" id="col_details" class="form-control" min="1">
                            <small class="text-muted">Default: Salik Charges - Month Year</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="col_admin_charges">Admin Charges</label>
                            <input type="number" name="col_admin_charges" id="col_admin_charges" class="form-control" min="1">
                            <small class="text-muted">Per-row from Excel; empty cells use default above. 0 stays 0.</small>
                        </div>
                        </div>
                    </div>
                </div>

                <div class="form-group text-center mt-3">
                    <button type="submit" class="btn btn-success" id="importBtn"><i class="fas fa-upload"></i> Import Salik Records</button>
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
</script>
@endsection

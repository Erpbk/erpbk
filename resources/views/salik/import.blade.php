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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="file">Import File</label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.csv" required>
                            <a href="{{ route('salik.import_template') }}" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="fas fa-download"></i> Download CSV Template
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="admin_charge_per_salik">Default Admin Charge per Salik</label>
                            <input type="number" name="admin_charge_per_salik" id="admin_charge_per_salik" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="alert alert-info">
                    <strong>CSV columns:</strong> Transaction ID, Trip Date, Trip Time, Transaction Post Date, Toll Gate, Direction, Tag Number, Plate Number, Amount, Billing Month, Salik VAT %, Admin Charges, Admin VAT %, Details.
                    <br><strong>Required fields:</strong> Transaction ID, Trip Date, Plate Number, Amount.
                </div>
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-success" id="importBtn"><i class="fas fa-upload"></i> Import Salik Records</button>
                </div>
                <div id="importResult" class="mt-3" style="display:none;"></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
$('#salikImportForm').on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    $('#importBtn').prop('disabled', true);
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            $('#importResult').html('<div class="alert alert-success">' + response.message + '</div>').show();
            setTimeout(function () { window.location.href = "{{ route('salik.index') }}"; }, 2000);
        },
        error: function (xhr) {
            $('#importResult').html('<div class="alert alert-danger">' + (xhr.responseJSON?.message || 'Import failed') + '</div>').show();
            $('#importBtn').prop('disabled', false);
        }
    });
});
</script>
@endsection

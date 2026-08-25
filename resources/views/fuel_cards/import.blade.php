<form action="{{ route('fuelCards.import') }}" method="POST" enctype="multipart/form-data" id="formajax">
    @csrf
    <div class="row">
        <div class="col-12">
            <a href="{{ route('fuelCards.import_template') }}" class="text-success w-100" download>
                <i class="fa fa-file-download text-success"></i> &nbsp; Download Sample File
            </a>
        </div>
        <div class="col-12 mt-3 mb-3">
            <label class="mb-3 pl-2">Select file</label>
            <input type="file" name="file" class="form-control mb-3" style="height: 40px;" accept=".xlsx,.xls,.csv" required />
            <small class="text-muted">Columns: Card Number, Fuel Company, Service Charges (optional), Card Issue Date, Remarks (optional)</small>
        </div>
    </div>
    <button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Start Import</button>
</form>

<form action="{{ route('riders.import_rider_vouchers') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ url('sample/rider_voucher_sample.xlsx') }}" class="text-success w-100" download="Rider Vouchers Sample">
                    <i class="fa fa-file-download text-success"></i> &nbsp; Download Sample File
                </a>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label for="file">Excel File (.xlsx)</label>
        <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx">
        @error('file')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Columns: Rider ID, Billing Month, Date, Amount, Voucher Type, Account_id</small>
    </div>
    <div class="text-right">
        <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Import</button>
    </div>
</form>
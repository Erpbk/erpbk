<form action="{{ route('riders.voucher_import') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label for="file">Select Excel File (.xlsx)</label>
        <input type="file" class="form-control-file form-control mb-3 @error('file') is-invalid @enderror" name="file" id="file" accept=".xlsx">
        @error('file')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Expected columns: Rider ID, Billing Month, Date, Amount, Voucher Type, Account_id</small>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Import Vouchers</button>
        <a href="{{ route('riders.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
    </div>
</form>
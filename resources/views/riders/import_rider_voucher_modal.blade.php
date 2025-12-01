<form action="{{ route('riders.import_rider_vouchers') }}" method="POST" enctype="multipart/form-data">
    @csrf
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
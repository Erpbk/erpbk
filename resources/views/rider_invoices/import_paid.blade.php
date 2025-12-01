<div class="alert alert-info">
    <h5><i class="icon fas fa-info"></i> Important Notes:</h5>
    <ul>
        <li>This will only update existing unpaid invoices for the same Rider ID and Billing Month.</li>
        <li>If no matching unpaid invoice is found, the record will be skipped.</li>
        <li>The Excel file should include a bank account column for creating voucher entries.</li>
        <li>Make sure the Excel format matches the expected structure.</li>
    </ul>
</div>

<form action="{{ route('riderInvoices.importPaid') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label for="file">Select Excel File:</label>

        <input type="file" class="form-control-file form-control mb-3 @error('file') is-invalid @enderror"
            id="file" name="file" accept=".xlsx">
        @error('file')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-upload"></i> Import Paid Invoices
        </button>
        <a href="{{ route('riderInvoices.index') }}" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>
</form>
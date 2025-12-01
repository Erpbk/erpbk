@extends('layouts.app')
@section('title','Import Rider Vouchers')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Import Rider Vouchers</h1>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('riders.import_rider_vouchers') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="alert alert-info">
                        Expected columns (order):
                        1) Rider ID, 2) Billing Month, 3) Date, 4) Amount, 5) Voucher Type, 6) Account_id
                    </div>
                    <div class="form-group">
                        <label for="file">Excel File (.xlsx)</label>
                        <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx">
                        @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Import</button>
                    <a href="{{ route('riders.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
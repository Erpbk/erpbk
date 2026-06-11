@extends('layouts.app')

@section('title', 'Inventory Return Contract — ' . $rider->name)

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Inventory Return Contract</h3>
                <small class="text-muted">{{ $rider->rider_id }} — {{ $rider->name }}</small>
            </div>
            <a href="{{ route('RiderInventory.show', $rider->id) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left"></i> Back
            </a>
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')

    <form method="POST" action="{{ route('RiderInventory.returnContractProcess', $rider->id) }}">
        @csrf
        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label required">Return / Process Date</label>
                    <input type="date" name="return_date" class="form-control" value="{{ old('return_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Optional remarks for this return contract">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Select disposition for each assigned item</h5>
            </div>
            <div class="card-body table-responsive">
                @error('dispositions')<div class="alert alert-danger">{{ $message }}</div>@enderror
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Assignment Date</th>
                            <th>Item Value</th>
                            <th>Returned</th>
                            <th>Lost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $row)
                        @php $oldDisp = old('dispositions.' . $row->id, 'returned'); @endphp
                        <tr>
                            <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                            <td>{{ $row->assigned_date?->format('Y-m-d') }}</td>
                            <td>{{ number_format((float) $row->amount, 2) }}</td>
                            <td>
                                <input type="radio" name="dispositions[{{ $row->id }}]" value="returned" {{ $oldDisp === 'returned' ? 'checked' : '' }} required>
                            </td>
                            <td>
                                <input type="radio" name="dispositions[{{ $row->id }}]" value="lost" {{ $oldDisp === 'lost' ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-certificate"></i> Process &amp; Generate Return Contract
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

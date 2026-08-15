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
                <small class="text-muted">Use <strong>Keep Assigned</strong> to leave an item unchanged on this contract.</small>
            </div>
            <div class="card-body table-responsive">
                @error('dispositions')<div class="alert alert-danger">{{ $message }}</div>@enderror
                @error('amounts')<div class="alert alert-danger">{{ $message }}</div>@enderror
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Assignment Date</th>
                            <th>Item Value (Total)</th>
                            <th>Returned</th>
                            <th>Lost</th>
                            <th>Keep Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $row)
                        @php $oldDisp = old('dispositions.' . $row->id, 'skip'); @endphp
                        <tr data-assignment-row="{{ $row->id }}">
                            <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                            <td>{{ (int) ($row->qty ?? 1) }}</td>
                            <td>{{ $row->assigned_date?->format('Y-m-d') }}</td>
                            <td style="min-width: 140px;">
                                <input type="number"
                                    name="amounts[{{ $row->id }}]"
                                    class="form-control form-control-sm js-assignment-amount"
                                    data-assignment-id="{{ $row->id }}"
                                    step="0.01"
                                    min="0.01"
                                    value="{{ old('amounts.' . $row->id, $row->lineTotal()) }}"
                                    {{ $oldDisp === 'skip' ? 'disabled' : '' }}>
                            </td>
                            <td>
                                <input type="radio" class="js-disposition-radio" name="dispositions[{{ $row->id }}]" value="returned" data-assignment-id="{{ $row->id }}" {{ $oldDisp === 'returned' ? 'checked' : '' }}>
                            </td>
                            <td>
                                <input type="radio" class="js-disposition-radio" name="dispositions[{{ $row->id }}]" value="lost" data-assignment-id="{{ $row->id }}" {{ $oldDisp === 'lost' ? 'checked' : '' }}>
                            </td>
                            <td>
                                <input type="radio" class="js-disposition-radio" name="dispositions[{{ $row->id }}]" value="skip" data-assignment-id="{{ $row->id }}" {{ $oldDisp === 'skip' ? 'checked' : '' }}>
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

@push('page-scripts')
<script>
    (function () {
        function toggleAmountForRow(assignmentId) {
            var selected = document.querySelector('input[name="dispositions[' + assignmentId + ']"]:checked');
            var amountInput = document.querySelector('.js-assignment-amount[data-assignment-id="' + assignmentId + '"]');
            if (!amountInput) {
                return;
            }
            var isSkipped = !selected || selected.value === 'skip';
            amountInput.disabled = isSkipped;
            amountInput.required = !isSkipped;
        }

        document.querySelectorAll('.js-disposition-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                toggleAmountForRow(this.dataset.assignmentId);
            });
        });

        document.querySelectorAll('[data-assignment-row]').forEach(function (row) {
            toggleAmountForRow(row.dataset.assignmentRow);
        });
    })();
</script>
@endpush

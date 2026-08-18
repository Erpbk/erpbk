@extends('layouts.app')

@section('title', 'Bulk Return — ' . $rider->name)

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Bulk Return</h3>
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

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

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
                <h5 class="mb-0">Set returned and/or lost quantities per item</h5>
                <small class="text-muted">
                    Enter a <strong>Returned Qty</strong> and/or <strong>Lost Qty</strong> for each item to process.
                    Their sum must not exceed open qty. Leave both at 0 to keep the item assigned.
                </small>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Open Qty</th>
                            <th>Assignment Date</th>
                            <th>Unit Price</th>
                            <th>Returned Qty</th>
                            <th>Lost Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $row)
                        @php
                            $openQty = max(1, (int) ($row->qty ?? 1));
                            $oldReturned = old('returned_qty.' . $row->id);
                            $oldLost = old('lost_qty.' . $row->id);
                            $returnedValue = $oldReturned !== null ? (int) $oldReturned : $openQty;
                            $lostValue = $oldLost !== null ? (int) $oldLost : 0;
                        @endphp
                        <tr data-assignment-row="{{ $row->id }}" data-open-qty="{{ $openQty }}">
                            <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                            <td>{{ $openQty }}</td>
                            <td>{{ $row->assigned_date?->format('Y-m-d') }}</td>
                            <td style="min-width: 120px;">
                                <input type="number"
                                    name="amounts[{{ $row->id }}]"
                                    class="form-control form-control-sm js-assignment-amount"
                                    data-assignment-id="{{ $row->id }}"
                                    step="0.01"
                                    min="0"
                                    value="{{ old('amounts.' . $row->id, number_format((float) $row->amount, 2, '.', '')) }}">
                            </td>
                            <td style="min-width: 100px;">
                                <input type="number"
                                    name="returned_qty[{{ $row->id }}]"
                                    class="form-control form-control-sm js-returned-qty"
                                    data-assignment-id="{{ $row->id }}"
                                    min="0"
                                    max="{{ $openQty }}"
                                    value="{{ $returnedValue }}">
                            </td>
                            <td style="min-width: 100px;">
                                <input type="number"
                                    name="lost_qty[{{ $row->id }}]"
                                    class="form-control form-control-sm js-lost-qty"
                                    data-assignment-id="{{ $row->id }}"
                                    min="0"
                                    max="{{ $openQty }}"
                                    value="{{ $lostValue }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-certificate"></i> Process Bulk Return
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('page-scripts')
<script>
    (function () {
        function highlightRow(assignmentId) {
            var row = document.querySelector('[data-assignment-row="' + assignmentId + '"]');
            if (!row) return;

            var returnedInput = row.querySelector('.js-returned-qty');
            var lostInput = row.querySelector('.js-lost-qty');
            var openQty = parseInt(row.getAttribute('data-open-qty'), 10) || 0;
            var returnedQty = parseInt(returnedInput && returnedInput.value, 10) || 0;
            var lostQty = parseInt(lostInput && lostInput.value, 10) || 0;

            if (returnedQty + lostQty > openQty) {
                row.classList.add('table-danger');
            } else {
                row.classList.remove('table-danger');
            }
        }

        document.querySelectorAll('.js-returned-qty, .js-lost-qty').forEach(function (input) {
            input.addEventListener('input', function () {
                highlightRow(this.dataset.assignmentId);
            });
            input.addEventListener('change', function () {
                highlightRow(this.dataset.assignmentId);
            });
        });

        document.querySelectorAll('[data-assignment-row]').forEach(function (row) {
            highlightRow(row.dataset.assignmentRow);
        });
    })();
</script>
@endpush

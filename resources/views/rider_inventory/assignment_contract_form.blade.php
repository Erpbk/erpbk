@extends('layouts.app')

@section('title', 'Assignment Contract — ' . $rider->name)

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Assignment Contract</h3>
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

    <form method="POST" action="{{ route('RiderInventory.assignmentContractProcess', $rider->id) }}">
        @csrf
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Select items to include</h5>
                    <small class="text-muted">Only checked assigned items will appear on the assignment contract.</small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-assignments" checked>
                    <label class="form-check-label" for="select-all-assignments">Select all</label>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th style="width:48px;"></th>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Customer</th>
                            <th>Assigned Date</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $row)
                        @php
                            $checked = collect(old('assignment_ids', $assignments->pluck('id')->all()))
                                ->map(fn ($id) => (int) $id)
                                ->contains((int) $row->id);
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox"
                                    class="form-check-input js-assignment-check"
                                    name="assignment_ids[]"
                                    value="{{ $row->id }}"
                                    {{ $checked ? 'checked' : '' }}>
                            </td>
                            <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                            <td>{{ (int) ($row->qty ?? 1) }}</td>
                            <td>
                                @if($row->customer)
                                    {{ $row->customer->name }}{{ $row->customer->company_name ? ' — ' . $row->customer->company_name : '' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $row->assigned_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ number_format((float) $row->amount, 2) }}</td>
                            <td>{{ number_format($row->lineTotal(), 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-certificate"></i> Generate Assignment Contract
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('page-scripts')
<script>
    (function () {
        var selectAll = document.getElementById('select-all-assignments');
        var checks = Array.prototype.slice.call(document.querySelectorAll('.js-assignment-check'));

        function syncSelectAll() {
            if (!selectAll) return;
            selectAll.checked = checks.length > 0 && checks.every(function (c) { return c.checked; });
            selectAll.indeterminate = checks.some(function (c) { return c.checked; }) && !selectAll.checked;
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checks.forEach(function (c) { c.checked = selectAll.checked; });
                selectAll.indeterminate = false;
            });
        }

        checks.forEach(function (c) {
            c.addEventListener('change', syncSelectAll);
        });

        syncSelectAll();
    })();
</script>
@endpush

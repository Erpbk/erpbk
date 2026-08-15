@php
    $manageActions = false;
@endphp
<div class="p-3">
    <div class="mb-3">
        <h5 class="mb-1">{{ $rider->name }}</h5>
        <div class="text-muted small">{{ $rider->rider_id ?? $rider->id }}</div>
    </div>
    <div class="table-responsive" id="assignmentTableWrapper">
        @include('rider_inventory.assignment_table', compact('assignments', 'rider', 'manageActions'))
    </div>
</div>

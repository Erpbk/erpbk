@php
    $branchid = isset($employee)
        ? ($employee->branch_id ?? null)
        : (isset($rider) ? ($rider->branch_id ?? null) : null);
@endphp
<input type="hidden" name="branch_id" value="{{ $branchid }}">

@php
    $branchid = isset($rider) ? ($rider->branch_id ?? null) : null;
@endphp
<input type="hidden" name="branch_id" value="{{ $branchid }}">

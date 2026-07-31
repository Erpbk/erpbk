@php
    $branchParty = $employee ?? $rider ?? ($party ?? null);
    $branchid = $branchParty->branch_id ?? null;
@endphp
<input type="hidden" name="branch_id" value="{{ $branchid }}">

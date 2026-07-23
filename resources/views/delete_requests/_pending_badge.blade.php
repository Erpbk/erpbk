@php
$isPendingDeletion = $model && method_exists($model, 'isPendingDeletion') && $model->isPendingDeletion();
@endphp
@if($isPendingDeletion)
<span class="badge bg-warning text-dark pending-deletion-badge" title="Awaiting administrator approval">
    <i class="ti ti-lock me-1"></i>Pending Deletion
</span>
@endif
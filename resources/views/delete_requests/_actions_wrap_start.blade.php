@php $__pendingDeletion = record_is_pending_deletion($model); @endphp
@if($__pendingDeletion)
    @include('delete_requests._locked_cell', ['model' => $model])
@else

@php
$assignFields = $assignFields ?? \App\Support\SimAssignFields::assignModalFields('return');
$inlineFields = $assignFields->filter(function ($f) {
    if (($f->field_key ?? '') === 'notes') {
        return false;
    }
    if ($f->kind === 'custom' && ($f->resolvedInputSpec()['type'] ?? '') === 'textarea') {
        return false;
    }

    return true;
});
$wideFields = $assignFields->filter(function ($f) {
    if (($f->field_key ?? '') === 'notes') {
        return true;
    }
    if ($f->kind === 'custom' && ($f->resolvedInputSpec()['type'] ?? '') === 'textarea') {
        return true;
    }

    return false;
});
@endphp

{!! Form::model($sims, ['url' => route('sims.return', $sims->id), 'method' => 'post', 'id' => 'formajax']) !!}

<div class="card-body">
    <div class="row">
        @foreach($inlineFields as $field)
            @include('sims._assign_modal_field', [
                'field' => $field,
                'assignContext' => 'return',
                'sims' => $sims,
                'assignee_name' => $assignee_name ?? null,
            ])
        @endforeach
    </div>

    @if($wideFields->isNotEmpty())
    <div class="row mt-3">
        @foreach($wideFields as $field)
            @include('sims._assign_modal_field', [
                'field' => $field,
                'assignContext' => 'return',
                'sims' => $sims,
                'assignee_name' => $assignee_name ?? null,
            ])
        @endforeach
    </div>
    @endif
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Return', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}

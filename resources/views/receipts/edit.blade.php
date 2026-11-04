{!! Form::model($receipt, ['route' => ['receipts.update', $receipt->id], 'method' => 'patch','id'=>'formajax']) !!}
@if(!empty($fromCheques))
    {!! Form::hidden('from_cheques', 1) !!}
    @if(!empty($chequeClearEditToken))
        {!! Form::hidden('cheque_clear_edit_token', $chequeClearEditToken) !!}
    @endif
@endif

<div class="card-body">
    <div class="row">
        @include('receipts.fields2')
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
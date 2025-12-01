{!! Form::model($payment, ['route' => ['payments.update', $payment->id], 'method' => 'patch','id'=>'formajax']) !!}

<div class="card-body">
    <div class="row">
        @include('payments.fields')
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
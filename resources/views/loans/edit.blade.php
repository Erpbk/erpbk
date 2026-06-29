{!! Form::model($loan, ['route' => ['loans.update', $loan->id], 'method' => 'patch', 'id' => 'formajax']) !!}
<div class="card-body">
    <div class="row">
        @include('loans.fields')
    </div>
</div>
<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
</div>
{!! Form::close() !!}

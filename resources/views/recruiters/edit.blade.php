{!! Form::model($recruiters, ['route' => ['recruiters.update', $recruiters->id], 'method' => 'patch','id'=>'formajax']) !!}

<div class="card-body">
    <div class="row">
        @include('recruiters.fields')
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
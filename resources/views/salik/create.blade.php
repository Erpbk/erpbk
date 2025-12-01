{!! Form::open(['route' => 'salik.store','id'=>'formajax']) !!}


<input type="hidden" id="reload_page" value="1">
<div class="row">
    @include('salik.fields')
</div>



<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
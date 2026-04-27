{!! Form::model($data, ['route' => ['fuel_data.update', $data->id], 'method' => 'post', 'id' => 'formajax', 'files' => true]) !!}
    @csrf
    
   @include('fuel_data.fields')

    <div class="action-btn">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    </div>

{!! Form::close() !!}

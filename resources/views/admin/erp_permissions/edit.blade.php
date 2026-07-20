{!! Form::model($permission, ['route' => ['admin.erp-permissions.update', $permission->id], 'method' => 'patch', 'id' => 'formajax', 'class' => 'form-ajax-submit', 'data-reload-table' => '0']) !!}

    @include('permissions.fields')

    <div class="action-btn">
        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    </div>

{!! Form::close() !!}

{!! Form::open(['route' => ['riderAttendances.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    @can('attendance_view')
    <a href="{{ route('riderAttendances.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan
    @can('attendance_edit')
    <a href="{{ route('riderAttendances.edit', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan
    @can('attendance_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

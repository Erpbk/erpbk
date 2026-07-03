{!! Form::open(['route' => ['riderActivities.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    @can('activity_view')
    <a href="{{ route('riderActivities.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan
    @can('rider_edit')
    <a href="{{ route('riderActivities.edit', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

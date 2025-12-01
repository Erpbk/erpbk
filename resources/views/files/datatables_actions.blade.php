{!! Form::open(['route' => ['files.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
  @can('files_view')
    <a href="{{ url('storage2/' . $type . '/'.$type_id.'/'.$file_name)}}" target="_blank" class='btn btn-default btn-sm'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan
    {{-- <a href="{{ route('files.edit', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-edit"></i>
    </a> --}}
    @can('files_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure you want to delete this?")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

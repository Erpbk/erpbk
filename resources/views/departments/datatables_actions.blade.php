{!! Form::open(['route' => ['departments.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    {{-- @can('department_view')
    <a href="javascript:void(0);" data-title="View" data-size="sm" data-action="{{ route('departments.show', $id) }}" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan --}}
    @can('department_edit')
    <a href="javascript:void(0);" data-title="Edit" data-size="sm" data-action="{{ route('departments.edit', $id) }}" class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('department_delete')

    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => 'return confirm("Are you sure, want to delete this department ?")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

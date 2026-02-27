{!! Form::open(['route' => ['roles.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
  {{--   <a href="{{ route('roles.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a> --}}
    @can('role_edit')
    <a data-action="{{ route(((View::shared('settings_panel') ?? false) ? 'settings-panel.roles' : 'roles') . '.edit', $id) }}" data-title="Edit Role" data-size="lg" class='btn btn-info btn-sm show-modal' href="javascript:void(0);">
        <i class="fa fa-edit"></i>
    </a>
    @endcan
   {{--  {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure, want to delete this role ?")'

    ]) !!} --}}
</div>
{!! Form::close() !!}

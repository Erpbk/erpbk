@php $permissionsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.permissions' : 'permissions'; @endphp
{!! Form::open(['route' => [$permissionsRoute . '.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
   @can('role_edit')
    <a href="javascript:void(0)" 
    class='btn btn-info btn-sm show-modal'
    data-action="{{ route($permissionsRoute . '.edit', $id) }}"
    data-size="lg"
    data-title="Edit Permissions">
        <i class="fa fa-edit"></i>
    </a>
    @endcan
    @can('role_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

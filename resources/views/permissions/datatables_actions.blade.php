@php $permissionsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.permissions' : 'permissions'; @endphp
{!! Form::open(['route' => [$permissionsRoute . '.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
   {{--  <a href="{{ route($permissionsRoute . '.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a> --}}
    <a href="javascript:void(0)" 
    class='btn btn-info btn-sm show-modal'
    data-action="{{ route($permissionsRoute . '.edit', $id) }}"
    data-size="lg"
    data-title="Edit Permissions">
        <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!}
</div>
{!! Form::close() !!}

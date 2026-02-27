@php $usersRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.users' : 'users'; @endphp
{!! Form::open(['route' => [$usersRoute . '.destroy', $id], 'method' => 'delete','id'=> 'formajax']) !!}
<div class='btn-group' style="float: right;">

    @can('user_view')
   {{--  <a href="{{ route($usersRoute . '.show', $id) }}"  class='btn btn-default btn-sm'>
        <i class="fa fa-eye"></i>
    </a> --}}
    @endcan
    @can('user_edit')
    <a href="javascript:void(0);" data-action="{{ route($usersRoute . '.edit', $id) }}" data-title="Edit User" data-size="xl" class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('user_delete')
    @if($id !=1 && $id !=2)
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure, want to delete this user ?")'

    ]) !!}
    @endif
    @endcan
</div>
{!! Form::close() !!}

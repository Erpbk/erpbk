<div class="btn-group">
    <a href="javascript:void(0);"
       data-action="{{ route('admin.users.edit', $id) }}"
       data-title="Edit User"
       data-size="lg"
       class="btn btn-info btn-sm show-modal">
        <i class="fa fa-edit"></i>
    </a>

    <a href="javascript:void(0);"
       data-action="{{ route('admin.users.edit-roles', $id) }}"
       data-title="Assign Roles"
       data-size="md"
       class="btn btn-primary btn-sm show-modal">
        <i class="fa fa-user-shield"></i>
    </a>

    {!! Form::open(['route' => ['admin.users.destroy', $id], 'method' => 'delete', 'id' => 'formajax']) !!}
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure, want to delete this user ?")'
    ]) !!}
    {!! Form::close() !!}
</div>


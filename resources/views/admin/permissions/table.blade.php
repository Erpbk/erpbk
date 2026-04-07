<div class="card-body px-4">
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th >{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        <td class="d-flex justify-content-center">
                            <a href="javascript:void(0)"
                               class="btn btn-primary mx-2 btn-sm show-modal"
                               data-action="{!! route('admin.permissions.edit', $permission->id) !!}"
                               data-title="{{ __('Edit Permission') }}">
                                <i class="fa fa-edit"></i>
                            </a>
                            {!! Form::open(['route' => ['admin.permissions.destroy', $permission->id], 'method' => 'delete']) !!}
                                {!! Form::button('<i class="fa fa-trash"></i>', [
                                    'type' => 'submit',
                                    'class' => 'btn btn-danger btn-sm',
                                    'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'
                                ]) !!}
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">{{ __('No permissions found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


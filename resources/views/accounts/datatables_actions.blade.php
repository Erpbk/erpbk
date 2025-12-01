{!! Form::open(['route' => ['accounts.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    {{-- <a href="javascript:void(0);" data-action="{{ route('accounts.show', $id) }}" class='btn btn-default btn-sm'>
        <i class="fa fa-eye"></i>
    </a> --}}
    @can('account_edit')
    @if(!$parent_id)
    <a href="javascript:void(0);" data-size="lg" data-title="Edit Account" data-action="{{ route('accounts.edit', $id) }}" class='btn btn-info btn-sm show-modal' >
        <i class="fa fa-edit"></i>
    </a>
    @endif
    @endcan

    @can('account_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure? You will not be able to revert this!")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

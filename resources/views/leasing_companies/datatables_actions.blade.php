{!! Form::open(['route' => ['leasingCompanies.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    {{-- <a href="javascript:void(0);" data-size="md" data-title="New Item" data-action=="{{ route('leasingCompanies.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a> --}}
    @can('leasing_edit')
    <a href="javascript:void(0);" data-size="md" data-title="Edit" data-action="{{ route('leasingCompanies.edit', $id) }}" class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('leasing_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure?")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

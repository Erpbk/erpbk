{!! Form::open(['route' => ['vendors.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    {{-- <a href="javascript:void(0);" data-action="{{ route('vendors.show', $id) }}" class='btn btn-default btn-sm show-modal' data-size="lg" data-title="View">
    <i class="fa fa-eye"></i>
    </a> --}}
    @can('vendor_edit')
    <a href="javascript:void(0);" data-action="{{ route('vendors.edit', $id) }}" class='btn btn-info btn-sm show-modal' data-size="lg" data-title="Update vendor">
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('vendor_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm confirm-modal',
    'onclick' => 'return confirm("Are you sure? You will not be able to revert this!")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}
{!! Form::open(['route' => ['customers.destroy', $id], 'method' => 'delete','id'=>'formjax']) !!}
<div class='btn-group'>
    {{-- <a href="javascript:void(0);" data-size="lg" data-title="Update Bike" data-action="{{ route('customers.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a> --}}
    @can('customer_edit')
    <a href="javascript:void(0);" data-size="lg" data-title="Update Customer" data-action="{{ route('customers.edit', $id) }}" class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan
    @can('customer_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure?")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

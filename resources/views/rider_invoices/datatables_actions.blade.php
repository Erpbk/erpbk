{!! Form::open(['route' => ['riderInvoices.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    @can('riderinvoice_view')
    <a href="{{ route('riderInvoices.show', $id) }}" class='btn btn-default btn-sm' target="_blank">
        <i class="fa fa-eye"></i>
    </a>
    @endcan
    @can('riderinvoice_edit')
    <a href="javascript:void(0);" data-title="Edit Invoice" data-size="xl" data-action="{{ route('riderInvoices.edit', $id) }}" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan
    @can('riderinvoice_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm',
    'onclick' => 'return confirm("Are you sure?")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}
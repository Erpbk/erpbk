{!! Form::open(['route' => ['supplierInvoices.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    <a href="{{ route('supplierInvoices.show', $id) }}" class='btn btn-default btn-sm' target="_blank">
        <i class="fa fa-eye"></i>
    </a>
    <a href="javascript:void(0);" data-title="Edit Invoice" data-size="xl" data-action="{{ route('supplierInvoices.edit', $id) }}" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm',
    'onclick' => 'return confirm("Are you sure?")'
    ]) !!}
</div>
{!! Form::close() !!}
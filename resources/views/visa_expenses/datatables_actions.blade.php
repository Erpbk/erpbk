{!! Form::open(['route' => ['VisaExpense.delete', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    @can('visaexpense_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure to delete this?")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

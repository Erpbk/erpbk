{!! Form::open(['route' => ['salik.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    @can('salik_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm',
    'onclick' => 'return confirm("Are you sure to delete this?")'
    ]) !!}
    @endcan
</div>
{!! Form::close() !!}
{!! Form::open(['route' => ['items.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
   {{--  <a href="javascript:void(0);" data-size="md" data-title="View" data-action="{{ route('items.show', $id) }}" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-eye"></i>
    </a> --}}
    @can('item_edit')
    <a href="javascript:void(0);" data-size="md" data-title="Edit Item" data-action="{{ route('items.edit', $id) }}" class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan

    @can('item_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure? You will not be able to revert this!")'

    ]) !!}
    @endcan
</div>
{!! Form::close() !!}

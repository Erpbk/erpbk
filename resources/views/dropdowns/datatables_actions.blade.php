{!! Form::open(['route' => ['dropdowns.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    {{-- <a href="{{ route('dropdowns.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
    </a> --}}
    @can('dropdown_view')
    <a href="javascript:void(0);" data-size="lg" data-title="Edit Dropdown" data-action="{{ route('dropdowns.edit', $id) }}" class='show-modal btn btn-info btn-sm'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan
   {{--  {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!} --}}
</div>
{!! Form::close() !!}

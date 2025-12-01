{!! Form::open(['route' => ['riderEmails.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    <a href="javascript:void(0);" data-action="{{ route('riderEmails.show', $id) }}" data-title="View Email" data-size="md" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-eye"></i>
    </a>
    {{-- <a href="{{ route('riderEmails.edit', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-edit"></i>
    </a>
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!} --}}
</div>
{!! Form::close() !!}

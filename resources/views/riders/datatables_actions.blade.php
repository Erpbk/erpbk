{!! Form::open(['route' => ['riders.destroy', $id], 'method' => 'delete']) !!}
<div class="dropdown">
    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
        @can('rider_edit')
        <a href="javascript:void();" data-action="{{route('rider_contract_upload', $id)}}" data-size="md"
            data-title="{{$name . ' (' . $rider_id }}') Contract" class="dropdown-item waves-effect show-modal"><i class="fas fa-file my-1"></i> Contract</a>

        <a href="javascript:void();" data-action="{{route('rider.sendemail', $id)}}" data-size="md"
            data-title="{{$name . ' (' . $rider_id }}')" class="dropdown-item waves-effect show-modal"><i class="fas fa-envelope my-1"></i> Send Email</a>

        <a href="{{ route('riders.edit', $id) }}" class='dropdown-item waves-effect'>
            <i class="fa fa-edit my-1"></i> Edit
        </a>
        @endcan

    </div>
    <div class='btn-group'>
        {{-- <a href="{{ route('riders.show', $id) }}" class='btn btn-default btn-xs'>
        <i class="fa fa-eye"></i>
        </a> --}}

        {{-- {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!} {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-xs',
        'onclick' => 'return confirm("'.__('crud.are_you_sure').'")'

    ]) !!} --}}
    </div>
    {!! Form::close() !!}
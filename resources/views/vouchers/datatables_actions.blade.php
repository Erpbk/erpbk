{!! Form::open(['route' => ['vouchers.destroy', $trans_code], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    @can('voucher_document')
    <a href="javascript:void(0);" data-size="sm" data-title="Upload Document"
        data-action="{{ url('voucher/attach_file/'.$id) }}" class='btn btn-success btn-sm show-modal'>
        <i class="fa fa-file"></i>
    </a>
    @endcan
    @can('voucher_view')
    <a href="{{ route('vouchers.show', $id) }}" target="_blank" class='btn btn-default btn-sm'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan
    {{-- @can('jv_edit') --}}
    @can('voucher_edit')
    <a href="javascript:void(0);" @if($voucher_type !='RFV' && $voucher_type !='SV' && $voucher_type !='VL' ) data-size="xl" data-title="Edit Voucher No. {{$voucher_type.'-'.str_pad($id,4,"0",STR_PAD_LEFT)}}"
        data-action="{{ route('vouchers.edit', $trans_code) }}" @endif class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endcan
    @can('voucher_delete')
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm',
    'onclick' => "return confirm('Are you sure?')"
    ]) !!}
    @endcan
</div>
{!! Form::close() !!}
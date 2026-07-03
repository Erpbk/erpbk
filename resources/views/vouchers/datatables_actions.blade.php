@php
  $editDeleteFlags = $editDeleteFlags ?? [];
  $canEdit = !empty($editDeleteFlags[$voucher_type]['can_edit']);
  $canDelete = !empty($editDeleteFlags[$voucher_type]['can_delete']);
  $__companySlug = \App\Support\CompanyRouteContext::slug();
  $voucherRouteParams = static function ($voucherKey) use ($__companySlug): array {
    $params = ['voucher' => $voucherKey];
    if (!empty($__companySlug)) {
      $params['company_slug'] = $__companySlug;
    }
    return $params;
  };
@endphp
{!! Form::open(['route' => ['vouchers.destroy', $trans_code], 'method' => 'delete','id'=>'formajax']) !!}
<div class='btn-group'>
    @can('voucher_document')
    <a href="javascript:void(0);" data-size="sm" data-title="Upload Document"
        data-action="{{ url('voucher/attach_file/'.$id) }}" class='btn btn-success btn-sm show-modal'>
        <i class="fa fa-file"></i>
    </a>
    @endcan
    @can('voucher_view')
    <a href="{{ route('vouchers.show', $voucherRouteParams($id)) }}" target="_blank" class='btn btn-default btn-sm'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan
    @can('voucher_edit')
    @if($canEdit)
    <a href="javascript:void(0);" data-size="xl" data-title="Edit Voucher No. {{ $voucher_type.'-'.str_pad($id,4,'0',STR_PAD_LEFT) }}"
        data-action="{{ route('vouchers.edit', $voucherRouteParams($trans_code)) }}" class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    @endif
    @endcan
    @can('voucher_delete')
    @if($canDelete)
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm',
    'onclick' => "return confirm('Are you sure?')"
    ]) !!}
    @endif
    @endcan
</div>
{!! Form::close() !!}
@php
// Field-level visibility (respects Role → Field Permissions). A column is hidden
// everywhere in this module when its field is marked not-visible for the user.
$vfShow = static fn (string $field): bool => field_visible('voucher', $field);
$vfCols = [
'trans_date' => $vfShow('trans_date'),
'trans_code' => $vfShow('trans_code'),
'billing_month' => $vfShow('billing_month'),
'reference_number' => $vfShow('reference_number'),
'voucher_type' => $vfShow('voucher_type'),
'amount' => $vfShow('amount'),
'created_by' => $vfShow('created_by'),
'updated_by' => $vfShow('updated_by'),
'attach_file' => $vfShow('attach_file'),
];
// colspan for the "no data" row = visible data columns + Voucher ID + Actions.
$vfColspan = count(array_filter($vfCols)) + 2;
@endphp
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
  <thead class="text-center">
    <tr role="row">
      <th title="Voucher ID" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
      @if($vfCols['trans_date'])<th title="Date" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>@endif
      @if($vfCols['trans_code'])<th title="Trans Code" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Trans Code: activate to sort column ascending">Trans Code</th>@endif
      @if($vfCols['billing_month'])<th title="Billing Month" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>@endif
      @if($vfCols['reference_number'])<th title="Reference Number" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Reference Number: activate to sort column ascending">Reference Number</th>@endif
      @if($vfCols['voucher_type'])<th title="Type" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Type: activate to sort column ascending">Type</th>@endif
      @if($vfCols['amount'])<th title="Amount" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>@endif
      @if($vfCols['created_by'])<th title="Created By" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Created By: activate to sort column ascending">Created By</th>@endif
      @if($vfCols['updated_by'])<th title="Updated By" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Updated By: activate to sort column ascending">Updated By</th>@endif
      @if($vfCols['attach_file'])<th title="File" class="sorting_disabled" rowspan="1" colspan="1" aria-label="File">File</th>@endif
      <th title="Actions" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Actions">Actions</th>
      {{-- Trailing fixed columns (search + control) expected by the Column Control panel. Hidden from view but present so the panel's index math (headerCells.length - 2) stays aligned with the table body. --}}
      <th class="sorting_disabled" rowspan="1" colspan="1" aria-hidden="true" style="display:none;"></th>
      <th class="sorting_disabled" rowspan="1" colspan="1" aria-hidden="true" style="display:none;"></th>
    </tr>
  </thead>
  <tbody>
    @php
    $__companySlug = \App\Support\CompanyRouteContext::slug();
    $editDeleteFlags = $editDeleteFlags ?? [];
    $voucherTypes = \App\Helpers\General::VoucherType();
    $voucherRouteParams = static function ($voucherKey) use ($__companySlug): array {
    $params = ['voucher' => $voucherKey];
    if (!empty($__companySlug)) {
    $params['company_slug'] = $__companySlug;
    }
    return $params;
    };
    $voucherCloneParams = static function ($transCode) use ($__companySlug): array {
    $params = ['id' => $transCode];
    if (!empty($__companySlug)) {
    $params['company_slug'] = $__companySlug;
    }
    return $params;
    };
    $listSidebarParams = !empty($__companySlug) ? ['company_slug' => $__companySlug] : [];
    @endphp
    @if(isset($data) && $data->count() > 0)
    @foreach($data as $voucher)
    @php $voucherPendingDeletion = record_is_pending_deletion($voucher); @endphp
    <tr class="text-center {{ $voucherPendingDeletion ? 'table-warning' : '' }}" data-id="{{ $voucher->id }}">
      <td>
        @php
        $voucherId = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
        @endphp
        <a href="javascript:void(0);" class="text-primary show-voucher-panel" data-action="{{ route('vouchers.show', $voucherRouteParams($voucher->id)) }}" data-title="{{ \App\Helpers\General::VoucherType($voucher->voucher_type) ?? $voucher->voucher_type }} #{{ $voucherId }}" data-collapse-sidebar="1" data-list-url="{{ route('vouchers.list-sidebar', $listSidebarParams) }}">{{ $voucherId }}</a>
      </td>
      @if($vfCols['trans_date'])<td>{{ \App\Helpers\Common::DateFormat($voucher->trans_date) }}</td>@endif
      @if($vfCols['trans_code'])<td>{{ $voucher->trans_code }}</td>@endif
      @if($vfCols['billing_month'])<td>{{ \App\Helpers\Common::MonthFormat($voucher->billing_month) }}</td>@endif
      @if($vfCols['reference_number'])<td>{{ $voucher->reference_number ?? 'N/A' }}</td>@endif
      @if($vfCols['voucher_type'])<td>
        <span class="badge bg-primary">{{ $voucherTypes[$voucher->voucher_type] ?? $voucher->voucher_type }}</span>
      </td>@endif
      @if($vfCols['amount'])<td class="text-end">{{ number_format($voucher->amount, 2) }}</td>@endif
      @if($vfCols['created_by'])<td>{{ \App\Helpers\Common::UserName($voucher->Created_By) }}</td>@endif
      @if($vfCols['updated_by'])<td>{{ \App\Helpers\Common::UserName($voucher->Updated_By) }}</td>@endif
      @if($vfCols['attach_file'])<td>
        @if($voucher->attach_file)
        @if($voucher->voucher_type == 'RFV')
        <a href="{{ url('storage/' . $voucher->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
          <i class="fa fa-file"></i> View
        </a>
        @elseif($voucher->voucher_type == 'LV')
        <a href="{{ url('storage/' . $voucher->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
          <i class="fa fa-file"></i> View
        </a>
        @else
        <a href="{{ url('storage/vouchers/' . $voucher->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
          <i class="fa fa-file"></i> View
        </a>
        @endif
        @else
        <span class="text-muted">-</span>
        @endif
      </td>@endif
      <td style="position: relative;">
        @if($voucherPendingDeletion)
        @include('delete_requests._locked_cell', ['model' => $voucher])
        @else
        <div class="dropdown">
          <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $voucher->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $voucher->id }}" style="z-index: 1050;">
            @can('voucher_document')
            @if(!in_array($voucher->voucher_type, ['PV','RV','EXP','RFV','SV','VL','LV','FAV','FDV']))
            <li><a href="javascript:void(0);" data-size="sm" data-title="Upload Document"
                data-action="{{ url('voucher/attach_file/'.$voucher->id) }}" class='dropdown-item waves-effect show-modal'>
                <i class="fa fa-file my-1"></i> Upload Document
              </a></li>
            @endif
            @endcan
            @can('voucher_view')
            <li><a href="javascript:void(0);" class="dropdown-item waves-effect show-voucher-panel" data-action="{{ route('vouchers.show', $voucherRouteParams($voucher->id)) }}" data-title="{{ $voucherTypes[$voucher->voucher_type] ?? $voucher->voucher_type }} #{{ $voucherId }}" data-collapse-sidebar="1" data-list-url="{{ route('vouchers.list-sidebar', $listSidebarParams) }}">
                <i class="fa fa-eye my-1"></i> View
              </a></li>
            @endcan
            @can('voucher_edit')
            @if(!empty($editDeleteFlags[$voucher->voucher_type]['can_edit']) && !in_array($voucher->voucher_type, ['PV','RV','EXP','RFV','SV','VL','LV','FAV','FDV']))
            <li><a href="javascript:void(0);" data-size="xl"
                data-title="Edit Voucher No. {{ $voucher->voucher_type.'-'.str_pad($voucher->id,4,'0',STR_PAD_LEFT) }}"
                data-action="{{ route('vouchers.edit', $voucherRouteParams($voucher->trans_code)) }}"
                class='dropdown-item waves-effect show-modal'>
                <i class="fa fa-edit my-1"></i> Edit
              </a></li>
            @endif
            @endcan
            @can('voucher_delete')
            @if(!empty($editDeleteFlags[$voucher->voucher_type]['can_delete']) && !in_array($voucher->voucher_type, ['PV','RV','EXP','RFV','SV','VL','LV','FAV','FDV']))
            <li><a href="javascript:void(0);" onclick="deleteVoucher('{{ $voucher->trans_code }}')" class='dropdown-item waves-effect text-danger'>
                <i class="fa fa-trash my-1"></i> Delete
              </a></li>
            @endif
            @endcan
            </ul>
          </div>
          @endif
      </td>
      <td style="display:none;"></td>
      <td style="display:none;"></td>
    </tr>
    @endforeach
    @else
    <tr>
      <td colspan="{{ $vfColspan + 2 }}" class="text-center">
        <div class="py-4">
          <i class="fa fa-info-circle text-muted"></i>
          <p class="text-muted mb-0">No vouchers found</p>
        </div>
      </td>
    </tr>
    @endif
  </tbody>
</table>

@if(isset($data))
<div class="pagination-wrapper">
  {!! $data->appends(request()->query())->links('pagination') !!}
</div>
@endif

<script>
  function deleteVoucher(transCode) {
    if (confirm('Submit a delete request for this voucher? It will stay in the list as Pending Deletion until an administrator approves.')) {
      $.ajax({
        url: @json(route('vouchers.destroy', $voucherRouteParams('___TC___'))).replace('___TC___', encodeURIComponent(transCode)),
        type: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        },
        data: {
          _token: '{{ csrf_token() }}'
        },
        success: function(result) {
          if (typeof toastr !== 'undefined') {
            toastr.success(result.message || 'Delete request submitted');
          } else {
            alert(result.message || 'Delete request submitted');
          }
          location.reload();
        },
        error: function(xhr) {
          if (typeof toastr !== 'undefined') {
            toastr.error((xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && xhr.responseJSON.errors.error))) || 'Error deleting voucher');
          } else {
            alert('Error deleting voucher');
          }
        }
      });
    }
  }

  // Initialize Bootstrap dropdowns when this content is loaded (run only after jQuery is available)
  (function runWhenJQueryReady() {
    var $ = window.jQuery || window.$;
    if (typeof $ === 'undefined') {
      setTimeout(runWhenJQueryReady, 50);
      return;
    }
    $(document).ready(function() {
      // Wait for Bootstrap to be available
      var attempts = 0;
      var maxAttempts = 10;

      function tryInitialize() {
        attempts++;

        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
          var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
          dropdownElementList.map(function(dropdownToggleEl) {
            try {
              return new bootstrap.Dropdown(dropdownToggleEl);
            } catch (e) {
              return null;
            }
          }).filter(Boolean);
        } else if (attempts < maxAttempts) {
          setTimeout(tryInitialize, 100);
        }
      }

      setTimeout(tryInitialize, 100);
    });
  })();
</script>
@include('delete_requests._pending_table_script', ['items' => $data])
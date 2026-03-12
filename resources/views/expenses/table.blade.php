<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
  <thead class="text-center">
    <tr role="row">
      <th title="Voucher ID" class="sorting" tabindex="0" rowspan="1" colspan="1">Voucher ID</th>
      <th title="Date" class="sorting" tabindex="0" rowspan="1" colspan="1">Date</th>
      <th title="Trans Code" class="sorting" tabindex="0" rowspan="1" colspan="1">Trans Code</th>
      <th title="Billing Month" class="sorting" tabindex="0" rowspan="1" colspan="1">Billing Month</th>
      <th title="Reference Number" class="sorting" tabindex="0" rowspan="1" colspan="1">Reference Number</th>
      <th title="Amount" class="sorting" tabindex="0" rowspan="1" colspan="1">Amount</th>
      <th title="Created By" class="sorting" tabindex="0" rowspan="1" colspan="1">Created By</th>
      <th title="Updated By" class="sorting" tabindex="0" rowspan="1" colspan="1">Updated By</th>
      <th title="File" class="sorting_disabled" rowspan="1" colspan="1">File</th>
      <th title="Actions" class="sorting_disabled" rowspan="1" colspan="1">Actions</th>
      <th tabindex="0" rowspan="1" colspan="1">
        <a class="openFilterSidebar" href="javascript:void(0);" title="Filters"> <i class="fa fa-search"></i></a>
      </th>
      <th tabindex="0" rowspan="1" colspan="1">
        <a class="openColumnControlSidebar" href="javascript:void(0);" title="Column Control"> <i class="fa fa-columns"></i></a>
      </th>
    </tr>
  </thead>
  <tbody>
    @if(isset($data) && $data->count() > 0)
    @foreach($data as $voucher)
    <tr class="text-center">
      <td>
        @php
        $voucherId = 'EXP-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
        @endphp
        <a href="javascript:void(0);" class="text-primary show-voucher-panel" data-action="{{ route('expenses.voucher.show', $voucher->id) }}" data-title="Expense Voucher #{{ $voucherId }}" data-collapse-sidebar="1" data-list-url="{{ route('expenses.list-sidebar') }}">{{ $voucherId }}</a>
      </td>
      <td>{{ \App\Helpers\Common::DateFormat($voucher->trans_date) }}</td>
      <td>{{ $voucher->trans_code }}</td>
      <td>{{ \App\Helpers\Common::MonthFormat($voucher->billing_month) }}</td>
      <td>{{ $voucher->reference_number ?? 'N/A' }}</td>
      <td class="text-end">{{ number_format($voucher->amount, 2) }}</td>
      <td>{{ \App\Helpers\Common::UserName($voucher->Created_By) }}</td>
      <td>{{ \App\Helpers\Common::UserName($voucher->Updated_By) }}</td>
      <td>
        @if($voucher->attach_file)
        <a href="{{ url('storage/vouchers/' . $voucher->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
          <i class="fa fa-file"></i> View
        </a>
        @else
        <span class="text-muted">-</span>
        @endif
      </td>
      <td style="position: relative;">
        <div class="dropdown">
          <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $voucher->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $voucher->id }}" style="z-index: 1050;">
            @can('voucher_document')
            <li><a href="javascript:void(0);" data-size="sm" data-title="Upload Document"
                data-action="{{ url('voucher/attach_file/'.$voucher->id) }}" class='dropdown-item waves-effect show-modal'>
                <i class="fa fa-file my-1"></i> Upload Document
              </a></li>
            @endcan
            @can('expenses_view')
            <li><a href="javascript:void(0);" class="dropdown-item waves-effect show-voucher-panel" data-action="{{ route('expenses.voucher.show', $voucher->id) }}" data-title="Expense Voucher #{{ $voucherId }}" data-collapse-sidebar="1" data-list-url="{{ route('expenses.list-sidebar') }}">
                <i class="fa fa-eye my-1"></i> View
              </a></li>
            @endcan
            @can('expenses_edit')
            <li><a href="javascript:void(0);" data-size="xl"
                data-title="Edit Expense Voucher {{ $voucherId }}"
                data-action="{{ route('expenses.voucher.edit', $voucher->id) }}"
                class='dropdown-item waves-effect show-modal'>
                <i class="fa fa-edit my-1"></i> Edit
              </a></li>
            @endcan
            @can('expenses_delete')
            <li><a href="javascript:void(0);" onclick="deleteExpenseVoucher('{{ $voucher->id }}')" class='dropdown-item waves-effect text-danger'>
                <i class="fa fa-trash my-1"></i> Delete
              </a></li>
            @endcan
            </ul>
          </div>
      </td>
      <td></td>
      <td></td>
    </tr>
    @endforeach
    @else
    <tr>
      <td colspan="12" class="text-center">
        <div class="py-4">
          <i class="fa fa-info-circle text-muted"></i>
          <p class="text-muted mb-0">No expense vouchers found</p>
        </div>
      </td>
    </tr>
    @endif
  </tbody>
</table>

@if(isset($data) && method_exists($data, 'links'))
<div class="pagination-wrapper">
  {!! $data->appends(request()->query())->links('pagination') !!}
</div>
@endif

<script>
  function deleteExpenseVoucher(voucherId) {
    if (confirm('Are you sure you want to delete this expense voucher?')) {
      $.ajax({
        url: '/expenses/voucher/' + voucherId,
        type: 'DELETE',
        data: {
          _token: '{{ csrf_token() }}'
        },
        success: function(result) {
          if (typeof toastr !== 'undefined') {
            toastr.success('Expense voucher deleted successfully');
          } else {
            alert('Expense voucher deleted successfully');
          }
          location.reload();
        },
        error: function(xhr) {
          if (typeof toastr !== 'undefined') {
            toastr.error('Error deleting expense voucher');
          } else {
            alert('Error deleting expense voucher');
          }
        }
      });
    }
  }

  (function runWhenJQueryReady() {
    var $ = window.jQuery || window.$;
    if (typeof $ === 'undefined') {
      setTimeout(runWhenJQueryReady, 50);
      return;
    }
    $(document).ready(function() {
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

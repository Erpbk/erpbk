@push('third_party_stylesheets')
@endpush
@php
$vf = static fn (string $f): bool => field_visible('visaexpense', $f);
$__companySlug = \App\Support\CompanyRouteContext::slug();
$voucherEditDeleteFlags = \App\Models\VoucherType::getEditDeleteFlagsByModule('vouchers');
@endphp
<div class="riders-list-demo" id="visa-expenses-inline-edit-scope">
   <div class="d-flex justify-content-end mb-2 px-1">
      @can('visa_expense_entries_delete')
      <button type="button" class="btn btn-sm btn-outline-danger js-visa-expense-delete-selected" id="visaExpenseDeleteSelectedBtn" style="display:none;" onclick="deleteSelectedVisaExpenses(this)">
         <i class="fa fa-trash me-1"></i> Delete Selected
      </button>
      @endcan
   </div>
   <div class="rl-tbox">
   <table class="table" id="visaExpensesDataTable">
      <thead class="text-center">
         <tr id="rlHead" role="row">
            @can('visa_expense_entries_delete')
            <th class="nosort" title="Select All" width="50px">
               <input type="checkbox" class="js-visa-expense-select-all" id="visaExpenseSelectAll" onchange="toggleVisaExpenseSelectAll(this)" style="transform:scale(1.2);cursor:pointer;">
            </th>
            @endcan
            @if($vf('date'))<th title="Date" class="sorting" tabindex="0">Date <span class="ar">⇅</span></th>@endif
            @if($vf('billing_month'))<th title="Billing Month" class="sorting" tabindex="0">Billing Month <span class="ar">⇅</span></th>@endif
            @if($vf('visa_status'))<th title="Visa Status" class="sorting" tabindex="0">Visa Status <span class="ar">⇅</span></th>@endif
            @if($vf('amount'))<th title="Amount" class="sorting" tabindex="0">Amount <span class="ar">⇅</span></th>@endif
            <th title="Total" class="sorting" tabindex="0">Total <span class="ar">⇅</span></th>
            @if($vf('expiry_date'))<th title="Expiry Date" class="sorting" tabindex="0">Expiry Date <span class="ar">⇅</span></th>@endif
            <th title="File" class="nosort">File</th>
            <th title="Voucher ID" class="sorting" tabindex="0">Voucher ID <span class="ar">⇅</span></th>
            @if($vf('payment_status'))<th title="Payment Status" class="sorting" tabindex="0">Payment Status <span class="ar">⇅</span></th>@endif
            <th title="Action" class="nosort" width="120px">Action</th>
         </tr>
      </thead>
      <tbody id="rlBody">
         @forelse($data as $r)
         @php
         $isPaidExpense = $r->payment_status === 'paid';
         $expensePendingDeletion = record_is_pending_deletion($r);
         $voucherPendingDeletion = $r->vouchers->contains(fn ($v) => record_is_pending_deletion($v));
         $rowPendingDeletion = $expensePendingDeletion || $voucherPendingDeletion;
         $allowInlineEdit = !$isPaidExpense && !$rowPendingDeletion;
         $entryTotal = $isPaidExpense && $r->vouchers->isNotEmpty()
            ? (float) $r->vouchers->sum('amount')
            : (float) $r->amount;
         @endphp
         <tr class="text-center {{ $rowPendingDeletion ? 'table-warning' : '' }}" data-id="{{ $r->id }}" data-row-id="{{ $r->id }}">
            @can('visa_expense_entries_delete')
            <td>
               @if(!$rowPendingDeletion && !$isPaidExpense)
               <input type="checkbox" class="visa-expense-checkbox" value="{{ $r->id }}" onchange="updateVisaExpenseDeleteButton(this)" style="transform:scale(1.2);cursor:pointer;">
               @endif
            </td>
            @endcan
            @if($vf('date'))<td>
               <span id="date_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</span>
               @can('visa_expense_entries_edit')
               @if($allowInlineEdit)
               <a href="javascript:void(0);" class="ms-2 js-edit-visa-field" data-id="{{ $r->id }}" data-field="date">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endif
               @endcan
               <input
                  type="date"
                  id="date_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}"
                  class="form-control form-control-sm d-none">
            </td>@endif
            @if($vf('billing_month'))<td>
               <span id="billing_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->billing_month)->format('M-Y') }}</span>
               @can('visa_expense_entries_edit')
               @if($allowInlineEdit)
               <a href="javascript:void(0);" class="ms-2 js-edit-visa-field" data-id="{{ $r->id }}" data-field="billing">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endif
               @endcan
               <input
                  type="month"
                  id="billing_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->billing_month)->format('Y-m') }}"
                  class="form-control form-control-sm d-none">
            </td>@endif
            @if($vf('visa_status'))
            <td class="visa-status-flow{{ $isPaidExpense ? ' is-done' : '' }}{{ $loop->last ? ' is-last' : '' }}">
               <span class="visa-status-bdg">{{ $r->visa_status }}</span>
            </td>
            @endif
            @if($vf('amount'))<td>
               <span id="amount_display_{{ $r->id }}">{{ number_format((float) $r->amount, 2) }}</span>
               @can('visa_expense_entries_edit')
               @if($allowInlineEdit)
               <a href="javascript:void(0);" class="ms-2 js-edit-visa-field" data-id="{{ $r->id }}" data-field="amount">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endif
               @endcan
               <input
                  type="number"
                  step="0.01"
                  min="0"
                  id="amount_input_{{ $r->id }}"
                  value="{{ number_format((float) $r->amount, 2, '.', '') }}"
                  class="form-control form-control-sm d-none">
            </td>@endif
            <td>
               <span id="total_display_{{ $r->id }}">{{ number_format($entryTotal, 2) }}</span>
            </td>
            @if($vf('expiry_date'))<td>
               @if($r->expiry_date)
               <span id="expiry_date_display_{{ $r->id }}">{{ $r->expiry_date ? \Carbon\Carbon::parse($r->expiry_date)->format('d M Y') : '-' }}</span>
               @can('visa_expense_entries_edit')
               @if($allowInlineEdit)
               <a href="javascript:void(0);" class="ms-2 js-edit-visa-field" data-id="{{ $r->id }}" data-field="expiry_date">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endif
               @endcan
               <input
                  type="date"
                  id="expiry_date_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->expiry_date)->format('Y-m-d') }}"
                  class="form-control form-control-sm d-none">
               @else
               <span class="text-muted">-</span>
               @endif
            </td>@endif
            <td>
               @php
               $attachedVouchers = $r->vouchers->filter(fn ($v) => filled($v->attach_file));
               @endphp
               @if($attachedVouchers->isNotEmpty())
               <div class="d-inline-flex flex-wrap align-items-center justify-content-center gap-1">
                  @foreach($attachedVouchers as $attachedVoucher)
                  @php
                  $attachPath = ltrim((string) $attachedVoucher->attach_file, '/');
                  $attachUrl = str_contains($attachPath, '/')
                  ? url('storage/' . $attachPath)
                  : url('storage/vouchers/' . $attachPath);
                  $attachName = basename($attachPath);
                  $attachExt = strtolower(pathinfo($attachName, PATHINFO_EXTENSION));
                  $isImage = in_array($attachExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                  @endphp
                  <a href="{{ $attachUrl }}"
                     class="btn btn-sm btn-outline-primary"
                     target="_blank"
                     title="{{ $attachName }}">
                     <i class="fa {{ $isImage ? 'fa-file-image' : 'fa-file' }}"></i>
                     @if($attachedVouchers->count() === 1)
                     View
                     @endif
                  </a>
                  @endforeach
               </div>
               @else
               <span class="text-muted">-</span>
               @endif
            </td>
            <td>
               <span id="voucher_ids_display_{{ $r->id }}" class="d-inline-flex flex-wrap align-items-center justify-content-center gap-1">
                  @if($isPaidExpense)
                  @if($r->vouchers->isNotEmpty())
                  @foreach($r->vouchers as $voucher)
                  @php
                  $voucherNumber = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
                  $moduleActions = \App\Support\VoucherModuleActions::forVoucher($voucher, $__companySlug);
                  $canEditVoucher = !empty($voucherEditDeleteFlags[$voucher->voucher_type]['can_edit']);
                  $canDeleteVoucher = !empty($voucherEditDeleteFlags[$voucher->voucher_type]['can_delete']);
                  @endphp
                  <span class="d-inline-flex align-items-center gap-1">
                     <a href="javascript:void(0);"
                        class="text-primary show-voucher-panel"
                        data-action="{{ route('vouchers.show', $voucher->id) }}"
                        data-title="{{ \App\Helpers\General::VoucherType($voucher->voucher_type) ?? $voucher->voucher_type }} #{{ $voucherNumber }}"
                        data-collapse-sidebar="1">{{ $voucherNumber }}</a>
                     @include('delete_requests._pending_badge', ['model' => $voucher])
                     @if(!$rowPendingDeletion)
                     @canany(['vouchers_edit', 'voucher_edit'])
                     @if($canEditVoucher)
                     <a href="javascript:void(0);"
                        class="show-modal text-body-secondary"
                        data-size="{{ $moduleActions['edit_size'] }}"
                        data-title="{{ $moduleActions['edit_title'] }}"
                        data-action="{{ $moduleActions['edit_url'] }}"
                        data-collapse-sidebar="1"
                        title="Edit {{ $voucherNumber }}">
                        <i class="fa fa-edit text-primary"></i>
                     </a>
                     @endif
                     @endcanany
                     @canany(['vouchers_delete', 'voucher_delete'])
                     @if($canDeleteVoucher)
                     <a href="javascript:void(0);"
                        class="text-danger"
                        onclick='confirmDelete(@json($moduleActions['delete_url']))'
                        title="Delete {{ $voucherNumber }}">
                        <i class="fa fa-trash"></i>
                     </a>
                     @endif
                     @endcanany
                     @endif
                  </span>@if(!$loop->last), @endif
                  @endforeach
                  @else
                  <span class="text-muted">No voucher</span>
                  @endif
                  @else
                  <span class="text-muted">-</span>
                  @endif
               </span>
            </td>
            @if($vf('payment_status'))<td>
               @if($isPaidExpense)
               <span class="badge bg-success">Paid</span>
               @else
               <span class="badge bg-danger">Unpaid</span>
               @endif
            </td>@endif
            <td>
               @if($expensePendingDeletion)
               @include('delete_requests._locked_cell', ['model' => $r])
               @elseif($voucherPendingDeletion)
               @include('delete_requests._locked_cell', ['model' => $r->vouchers->first(fn ($v) => record_is_pending_deletion($v))])
               @else
               <div class="dropdown">
                  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}">
                     @include('layouts.partials.module_contract_action', [
                     'module' => 'visa_expense',
                     'recordId' => $r->id,
                     ])
                     @can('visa_expense_entries_edit')
                     @if($r->vouchers->isNotEmpty() && !$rowPendingDeletion)
                     <a href="javascript:void(0);"
                        data-size="md"
                        data-title="Upload Document"
                        data-action="{{ route('VisaExpense.fileupload', $r->id) }}"
                        class="dropdown-item waves-effect show-modal">
                        <i class="fa fa-file me-1"></i> {{ $r->vouchers->contains(fn ($v) => filled($v->attach_file)) ? 'Update Document' : 'Upload Document' }}
                     </a>
                     @endif
                     @if(!$isPaidExpense && !$rowPendingDeletion)
                     <a href="javascript:void(0);"
                        data-action="{{ route('VisaExpense.payForm', $r->id) }}"
                        data-size="xl"
                        data-title="Pay Visa Expense — {{ $r->visa_status }}"
                        class="dropdown-item waves-effect show-modal">
                        Pay
                     </a>
                     <a href="javascript:void(0);" data-action="{{ route('VisaExpense.edit' , $r->id) }}" data-size="lg" data-title="Edit Visa Expense" class='dropdown-item waves-effect show-modal'>
                        Edit
                     </a>
                     @endif
                     @endcan
                     @can('visa_expense_entries_delete')
                     @if(!$isPaidExpense)
                     <a href="javascript:void(0);" data-delete-url="{{ route('VisaExpense.delete', $r->id) }}" class='dropdown-item confirm-modal js-delete-visa-expense' data-size="lg" data-title="Delete Sim">
                        delete
                     </a>
                     @endif
                     @endcan
                  </div>
               </div>
               @endif
            </td>
         </tr>
         @empty
         <tr>
            <td colspan="12" class="rl-empty text-center text-muted py-4">No Visa Expenses found</td>
         </tr>
         @endforelse
      </tbody>
   </table>
   </div>
   @if(method_exists($data, 'links'))
   {!! $data->links('components.global-pagination') !!}
   @endif
</div>
@include('delete_requests._pending_table_script', ['items' => $data])
<script>
   function visaInlineScope() {
      return document.getElementById('visa-expenses-inline-edit-scope');
   }

   function editVisaField(id, field) {
      var scope = visaInlineScope();
      if (!scope) return;
      var input = scope.querySelector('#' + field + '_input_' + id);
      var display = scope.querySelector('#' + field + '_display_' + id);
      if (!input || !display) return;
      display.classList.add('d-none');
      input.classList.remove('d-none');
      input.focus();
      if (field === 'amount') input.select();
   }

   function saveVisaInline(id) {
      var scope = visaInlineScope();
      if (!scope) return;
      var amountInput = scope.querySelector('#amount_input_' + id);
      var dateInput = scope.querySelector('#date_input_' + id);
      var billingInput = scope.querySelector('#billing_input_' + id);
      if (!amountInput || !dateInput || !billingInput) return;

      var amount = amountInput.value;
      var date = dateInput.value;
      var billingMonth = billingInput.value;
      if (!amount || !date || !billingMonth) return;

      var payload = new FormData();
      payload.append('_token', '{{ csrf_token() }}');
      payload.append('id', id);
      payload.append('amount', amount);
      payload.append('date', date);
      payload.append('billing_month', billingMonth);

      fetch('{{ route("VisaExpense.inlineUpdate") }}', {
         method: 'POST',
         body: payload,
         headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
         }
      }).then(function(res) {
         return res.json();
      }).then(function(data) {
         if (!data.success) throw new Error(data.message || 'Update failed');
         var scope = visaInlineScope();
         if (!scope) return;
         var amountDisp = scope.querySelector('#amount_display_' + id);
         var dateDisp = scope.querySelector('#date_display_' + id);
         var billDisp = scope.querySelector('#billing_display_' + id);
         if (amountDisp) amountDisp.textContent = data.amount;
         var d = new Date(data.date);
         if (dateDisp) dateDisp.textContent = d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
         });
         var bm = new Date(data.billing_month + '-01');
         if (billDisp) {
            var monthShort = bm.toLocaleDateString('en-US', { month: 'short' });
            billDisp.textContent = monthShort + '-' + bm.getFullYear();
         }
      }).catch(function() {
         if (typeof Swal !== 'undefined') {
            Swal.fire({
               icon: 'error',
               title: 'Error',
               text: 'Could not update entry.'
            });
         }
      }).finally(function() {
         var scope = visaInlineScope();
         if (!scope) return;
         ['amount', 'date', 'billing'].forEach(function(field) {
            var input = scope.querySelector('#' + field + '_input_' + id);
            var display = scope.querySelector('#' + field + '_display_' + id);
            if (input && display) {
               input.classList.add('d-none');
               display.classList.remove('d-none');
            }
         });
      });
   }

   document.addEventListener('click', function(e) {
      var editLink = e.target.closest('.js-edit-visa-field');
      if (!editLink) return;
      editVisaField(editLink.getAttribute('data-id'), editLink.getAttribute('data-field'));
   });

   document.addEventListener('blur', function(e) {
      var input = e.target.closest('[id^="amount_input_"], [id^="date_input_"], [id^="billing_input_"]');
      if (!input || !input.closest('#visa-expenses-inline-edit-scope')) return;
      var id = (input.id.split('_').pop() || '').trim();
      if (id) saveVisaInline(id);
   }, true);

   document.addEventListener('keydown', function(e) {
      var input = e.target.closest('[id^="amount_input_"], [id^="date_input_"], [id^="billing_input_"]');
      if (!input || !input.closest('#visa-expenses-inline-edit-scope') || e.key !== 'Enter') return;
      e.preventDefault();
      var id = (input.id.split('_').pop() || '').trim();
      if (id) saveVisaInline(id);
   });
</script>
<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Filter Riders</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body" id="searchTopbody">
            <div style="display: none;" class="loading-overlay" id="loading-overlay">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
            <form id="filterForm" action="{{ route('banks.index') }}" method="GET">
               <div class="row">
                  <div class="form-group col-md-12">
                     <input type="number" name="search" class="form-control" placeholder="Search">
                  </div>
                  <div class="col-md-12 form-group text-center">
                     <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>
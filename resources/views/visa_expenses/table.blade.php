@push('third_party_stylesheets')
@endpush
<div id="visa-expenses-inline-edit-scope">
   <table class="table table-striped dataTable no-footer" id="visaExpensesDataTable">
      <thead class="text-center">
         <tr role="row">
            <th title="Transation Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Transation Date: activate to sort column ascending">Billing Month</th>
            <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
            <th title="Voucher IDs" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Amount</th>
            <th title="Visa Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Visa Status: activate to sort column ascending" aria-sort="descending">Visa Status</th>
            <th title-"expiry date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="expiry date: activate to sort column ascending">Expiry Date</th>
            <th title="Payment Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Payment Status: activate to sort column ascending" aria-sort="descending">Payment Status</th>
            <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Action</th>
         </tr>
      </thead>
      <tbody>
         @foreach($data as $r)
         <tr class="text-center" data-row-id="{{ $r->id }}">
            <td>
               <span id="billing_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</span>
               @can('visaexpense_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-visa-field" data-id="{{ $r->id }}" data-field="billing">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="month"
                  id="billing_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->billing_month)->format('Y-m') }}"
                  class="form-control form-control-sm d-none">
            </td>
            <td>
               <span id="date_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</span>
               @can('visaexpense_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-visa-field" data-id="{{ $r->id }}" data-field="date">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="date"
                  id="date_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}"
                  class="form-control form-control-sm d-none">
            </td>
            <td>
               <span id="voucher_ids_display_{{ $r->id }}" class="d-inline-flex flex-wrap align-items-center justify-content-center gap-1">
                  @if($r->payment_status === 'paid')
                  @if($r->vouchers->isNotEmpty())
                  @foreach($r->vouchers as $voucher)
                  @php
                  $voucherNumber = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
                  @endphp
                  <a href="{{ route('vouchers.show', $voucher->id) }}" target="_blank">{{ $voucherNumber }}</a>@if(!$loop->last), @endif
                  @endforeach
                  @can('visaexpense_edit')
                  <a href="javascript:void(0);"
                     class="show-modal text-body-secondary"
                     data-size="xl"
                     data-title="Edit payment account — {{ $r->vouchers->first()->voucher_type ?? 'LV' }}-{{ str_pad((string) ($r->vouchers->first()->id ?? 0), 4, '0', STR_PAD_LEFT) }}"
                     data-action="{{ route('VisaExpense.editVoucherCreditForm', $r->id) }}"
                     title="Change credit / payment account only">
                     <i class="fa fa-edit text-primary"></i>
                  </a>
                  @endcan
                  @else
                  <span class="text-muted">No voucher</span>
                  @endif
                  @else
                  <span class="text-muted">-</span>
                  @endif
               </span>
            </td>
            <td>
               <span id="amount_display_{{ $r->id }}">{{ number_format((float) $r->amount, 2) }}</span>
               @can('visaexpense_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-visa-field" data-id="{{ $r->id }}" data-field="amount">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="number"
                  step="0.01"
                  min="0"
                  id="amount_input_{{ $r->id }}"
                  value="{{ number_format((float) $r->amount, 2, '.', '') }}"
                  class="form-control form-control-sm d-none">
            </td>
            <td>
               <span class="badge bg-primary">{{ $r->visa_status }}</span>
            </td>
            <td>
               <span id="expiry_date_display_{{ $r->id }}"></span>
            </td>
            <td>
               @if($r->payment_status == 'paid')
               <span class="badge bg-success">Paid</span>
               @else
               <span class="badge bg-danger">Unpaid</span>
               @endif
            </td>
            <td>
               <div class="dropdown">
                  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown">
                     <a href="{{ route('VisaExpense.viewvoucher', $r->id) }}" class='dropdown-item waves-effect'>
                        View Expense Detail
                     </a>
                     <a href="javascript:void(0);" data-action="{{ route('VisaExpense.edit' , $r->id) }}" data-size="lg" data-title="New Fine" class='dropdown-item waves-effect show-modal'>
                        Edit
                     </a>
                     <a href="javascript:void(0);" data-delete-url="{{ route('VisaExpense.delete', $r->id) }}" class='dropdown-item confirm-modal js-delete-visa-expense' data-size="lg" data-title="Delete Sim">
                        delete
                     </a>
                  </div>
               </div>
            </td>
         </tr>
         @endforeach
      </tbody>
   </table>
   @if($data->isEmpty())
   <div class="text-center mt-5">
      <h4 class="text-muted">No Visa Expenses found</h4>
   </div>
   @endif
   @if(method_exists($data, 'links'))
   {!! $data->links('components.global-pagination') !!}
   @endif
</div>
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
         if (billDisp) billDisp.textContent = bm.toLocaleDateString('en-US', {
            month: 'short',
            year: 'numeric'
         });
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
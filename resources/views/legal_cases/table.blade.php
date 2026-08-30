@push('third_party_stylesheets')
@endpush
<div id="legal-cases-inline-edit-scope">
   <table class="table table-striped dataTable no-footer" id="legalCasesDataTable">
      @php $vf = static fn (string $f): bool => field_visible('legalcase', $f); @endphp
      <thead class="text-center">
         <tr role="row">
            @if($vf('date'))<th title="Date">Date</th>@endif
            @if($vf('billing_month'))<th title="Billing Month">Billing Month</th>@endif
            @if($vf('case_status'))<th title="Case Status">Case Status</th>@endif
            @if($vf('reference_number'))<th title="Reference Number">Reference #</th>@endif
            @if($vf('expiry_date'))<th title="Expiry Date">Expiry Date</th>@endif
            @if($vf('step_status'))<th title="Step Status">Step Status</th>@endif
            <th title="Action">Action</th>
         </tr>
      </thead>
      <tbody>
         @foreach($data as $r)
         <tr class="text-center" data-row-id="{{ $r->id }}">
            @if($vf('date'))<td>
               <span id="date_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</span>
               @can('legal_case_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-legal-field" data-id="{{ $r->id }}" data-field="date">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="date"
                  id="date_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}"
                  class="form-control form-control-sm d-none">
            </td>@endif
            @if($vf('billing_month'))<td>
               <span id="billing_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</span>
               @can('legal_case_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-legal-field" data-id="{{ $r->id }}" data-field="billing">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="month"
                  id="billing_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->billing_month)->format('Y-m') }}"
                  class="form-control form-control-sm d-none">
            </td>@endif
            @if($vf('case_status'))<td>
               <span class="badge bg-primary">{{ $r->case_status }}</span>
            </td>@endif
            @if($vf('reference_number'))<td>{{ $r->reference_number }}</td>@endif
            @if($vf('expiry_date'))<td>
               @if($r->expiry_date)
               <span>{{ \Carbon\Carbon::parse($r->expiry_date)->format('d M Y') }}</span>
               @else
               <span class="text-muted">-</span>
               @endif
            </td>@endif
            @if($vf('step_status'))<td id="step_status_cell_{{ $r->id }}">
               @if($r->step_status === 'completed')
               <span class="badge bg-success">Completed</span>
               @else
               <span class="badge bg-warning text-dark">Pending</span>
               @endif
            </td>@endif
            <td id="step_action_cell_{{ $r->id }}">
               @include('layouts.partials.module_contract_action', [
                  'module' => 'legal_case',
                  'recordId' => $r->id,
                  'variant' => 'btn-group',
               ])
               @if($r->step_status === 'pending')
               @canany(['legal_case_create', 'legal_case_edit'])
               <button type="button"
                  class="btn btn-sm btn-primary js-complete-step"
                  data-id="{{ $r->id }}"
                  data-url="{{ route('LegalCase.completeStep') }}">
                  Complete the Step
               </button>
               @endcanany
               @else
               <span class="badge bg-success">Completed</span>
               @endif
            </td>
         </tr>
         @endforeach
      </tbody>
   </table>
   @if($data->isEmpty())
   <div class="text-center mt-5">
      <h4 class="text-muted">No Legal Cases found</h4>
   </div>
   @endif
   @if(method_exists($data, 'links'))
   {!! $data->links('components.global-pagination') !!}
   @endif
</div>
<script>
   function legalInlineScope() {
      return document.getElementById('legal-cases-inline-edit-scope');
   }

   function editLegalField(id, field) {
      var scope = legalInlineScope();
      if (!scope) return;
      var input = scope.querySelector('#' + field + '_input_' + id);
      var display = scope.querySelector('#' + field + '_display_' + id);
      if (!input || !display) return;
      display.classList.add('d-none');
      input.classList.remove('d-none');
      input.focus();
   }

   function saveLegalInline(id) {
      var scope = legalInlineScope();
      if (!scope) return;
      var dateInput = scope.querySelector('#date_input_' + id);
      var billingInput = scope.querySelector('#billing_input_' + id);
      if (!dateInput || !billingInput) return;

      var date = dateInput.value;
      var billingMonth = billingInput.value;
      if (!date || !billingMonth) return;

      var payload = new FormData();
      payload.append('_token', '{{ csrf_token() }}');
      payload.append('id', id);
      payload.append('date', date);
      payload.append('billing_month', billingMonth);

      fetch('{{ route("LegalCase.inlineUpdate") }}', {
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
         var scope = legalInlineScope();
         if (!scope) return;
         var dateDisp = scope.querySelector('#date_display_' + id);
         var billDisp = scope.querySelector('#billing_display_' + id);
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
         var scope = legalInlineScope();
         if (!scope) return;
         ['date', 'billing'].forEach(function(field) {
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
      var editLink = e.target.closest('.js-edit-legal-field');
      if (editLink) {
         editLegalField(editLink.getAttribute('data-id'), editLink.getAttribute('data-field'));
         return;
      }

      var completeBtn = e.target.closest('.js-complete-step');
      if (!completeBtn) return;

      var id = completeBtn.getAttribute('data-id');
      var url = completeBtn.getAttribute('data-url');
      if (!id || !url) return;

      completeBtn.disabled = true;

      var payload = new FormData();
      payload.append('_token', '{{ csrf_token() }}');
      payload.append('id', id);

      fetch(url, {
         method: 'POST',
         body: payload,
         headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
         }
      }).then(function(res) {
         return res.json().then(function(data) {
            if (!res.ok) throw new Error(data.message || 'Could not complete step');
            return data;
         });
      }).then(function() {
         var statusCell = document.getElementById('step_status_cell_' + id);
         var actionCell = document.getElementById('step_action_cell_' + id);
         if (statusCell) statusCell.innerHTML = '<span class="badge bg-success">Completed</span>';
         if (actionCell) actionCell.innerHTML = '<span class="badge bg-success">Completed</span>';
      }).catch(function(err) {
         completeBtn.disabled = false;
         if (typeof Swal !== 'undefined') {
            Swal.fire({
               icon: 'error',
               title: 'Error',
               text: err.message || 'Could not complete step.'
            });
         }
      });
   });

   document.addEventListener('blur', function(e) {
      var input = e.target.closest('[id^="date_input_"], [id^="billing_input_"]');
      if (!input || !input.closest('#legal-cases-inline-edit-scope')) return;
      var id = (input.id.split('_').pop() || '').trim();
      if (id) saveLegalInline(id);
   }, true);

   document.addEventListener('keydown', function(e) {
      var input = e.target.closest('[id^="date_input_"], [id^="billing_input_"]');
      if (!input || !input.closest('#legal-cases-inline-edit-scope') || e.key !== 'Enter') return;
      e.preventDefault();
      var id = (input.id.split('_').pop() || '').trim();
      if (id) saveLegalInline(id);
   });
</script>

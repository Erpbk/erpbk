@push('page-styles')
<style>
   @keyframes lc-next-pending-soft {
      0%, 100% {
         border-color: rgba(234, 88, 90, 0.35);
         box-shadow: 0 0 0 0 rgba(234, 88, 90, 0);
      }
      50% {
         border-color: rgba(234, 88, 90, 0.55);
         box-shadow: 0 1px 8px rgba(234, 88, 90, 0.12);
      }
   }

   .lc-next-pending-cell {
      background: linear-gradient(90deg, rgba(234, 88, 90, 0.04), rgba(253, 186, 116, 0.05));
   }

   .lc-next-pending-blink {
      padding: 0.25rem 0.5rem;
      border-radius: 8px;
      border: 1px solid rgba(234, 88, 90, 0.35);
      background: rgba(255, 253, 252, 0.95);
      animation: lc-next-pending-soft 2.8s ease-in-out infinite;
   }

   @keyframes lc-expiry-alert-soft {
      0%, 100% {
         border-color: rgba(217, 119, 6, 0.4);
         box-shadow: 0 0 0 0 rgba(217, 119, 6, 0);
      }
      50% {
         border-color: rgba(217, 119, 6, 0.65);
         box-shadow: 0 1px 8px rgba(217, 119, 6, 0.14);
      }
   }

   .lc-expiry-alert-cell {
      background: linear-gradient(90deg, rgba(254, 243, 199, 0.35), rgba(253, 230, 138, 0.2));
   }

   .lc-expiry-alert-blink {
      padding: 0.25rem 0.5rem;
      border-radius: 8px;
      border: 1px solid rgba(217, 119, 6, 0.4);
      background: rgba(255, 251, 235, 0.95);
      animation: lc-expiry-alert-soft 2.6s ease-in-out infinite;
   }
</style>
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th>Person ID</th>
         <th style="width: 220px;">Account Name</th>
         <th>Rider Status</th>
         <th>Next Pending Step</th>
         <th>Expiry Document</th>
         <th>Person Code</th>
         <th>Labour Card #</th>
         <th>Policy Number</th>
         <th>Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php
      $isEmployeeAccount = !empty($r->employee_id);
      $hasActiveBike = !$isEmployeeAccount && company_table('bikes')
      ->where('rider_id', $r->rider_id)
      ->where('warehouse', 'Active')
      ->exists();
      $badgeClass = $isEmployeeAccount ? 'bg-label-info' : ($hasActiveBike ? 'bg-label-success' : 'bg-label-danger');
      $badgeLabel = $isEmployeeAccount ? 'Employee' : ($hasActiveBike ? 'Active' : 'Inactive');
      $personRef = $isEmployeeAccount ? ($r->employee->employee_id ?? '-') : ($r->rider->rider_id ?? '-');
      $personCode = $isEmployeeAccount ? ($r->employee->person_code ?? '-') : ($r->rider->person_code ?? '-');
      $laborCard = $isEmployeeAccount ? ($r->employee->labor_card_number ?? '-') : ($r->rider->labor_card_number ?? '-');
      $policyNo = $isEmployeeAccount ? '-' : ($r->rider->policy_no ?? '-');
      $nextPending = ($nextPendingByAccountId ?? [])[$r->id] ?? null;
      $nextWhen = '';
      if ($nextPending) {
      try {
      if (!empty($nextPending->date)) {
      $nextWhen = \Carbon\Carbon::parse($nextPending->date)->format('d M Y');
      } elseif (!empty($nextPending->billing_month)) {
      $nextWhen = \Carbon\Carbon::parse($nextPending->billing_month)->format('M Y');
      }
      } catch (\Throwable $e) {
      $nextWhen = '';
      }
      }
      $urgentExpiry = ($urgentExpiryByAccountId ?? [])[$r->id] ?? null;
      $urgentExpiryWhen = '';
      if ($urgentExpiry && !empty($urgentExpiry->expiry_date)) {
      try {
      $urgentExpiryWhen = \Carbon\Carbon::parse($urgentExpiry->expiry_date)->format('d M Y');
      } catch (\Throwable $e) {
      $urgentExpiryWhen = '';
      }
      }
      @endphp
      <tr class="text-center">
         <td>{{ $personRef }}</td>
         <td class="text-start"><a href="{{ route('LegalCase.generatentries' , $r->id) }}">{{ $r->name }}</a></td>
         <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
         <td class="align-middle @if($nextPending) lc-next-pending-cell @endif">
            @if($nextPending)
            <a href="{{ route('LegalCase.generatentries', $r->id) }}" class="text-decoration-none text-body lc-next-pending-blink d-inline-block text-center">
               <span class="fw-semibold d-block text-body text-center">{{ $nextPending->case_status ?? '—' }}</span>
               @if($nextWhen !== '')
               <span class="text-muted small text-center">{{ $nextWhen }}</span>
               @endif
            </a>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td class="align-middle @if($urgentExpiry && $urgentExpiryWhen !== '') lc-expiry-alert-cell @endif">
            @if($urgentExpiry && $urgentExpiryWhen !== '')
            <a href="{{ route('LegalCase.generatentries', $r->id) }}" class="text-decoration-none text-body lc-expiry-alert-blink d-inline-block text-center" title="Document expiry within 10 days or overdue">
               <span class="fw-semibold d-block text-body text-center">{{ $urgentExpiry->case_status ?? '—' }}</span>
               <span class="text-muted small d-block text-center">{{ $urgentExpiryWhen }}</span>
            </a>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td>{{ $personCode }}</td>
         <td>{{ $laborCard }}</td>
         <td>{{ $policyNo }}</td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end">
                  @can('legalcase_view')
                  <a href="{{ route('LegalCase.generatentries' , $r->id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye"></i> View
                  </a>
                  @endcan
                  @can('legalcase_edit')
                  <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount{{ $r->id }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit"></i> Edit
                  </a>
                  @endcan
                  @can('legalcase_delete')
                  <a href="javascript:void(0);" data-delete-url="{{ route('LegalCase.deleteaccount', $r->id) }}" class='dropdown-item waves-effect js-delete-legal-case-account'>
                     <i class="fa fa-trash"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>

      <div class="modal fade" id="editaccount{{ $r->id }}" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
         <div class="modal-dialog modal-lg">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title">Update Account</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                  <form action="{{ route('LegalCase.editaccount') }}" method="POST">
                     @csrf
                     <input type="hidden" name="id" value="{{ $r->id }}">
                     <div class="row">
                        <div class="form-group col-md-12">
                           <label for="rider-{{ $r->id }}">Select Rider</label>
                           <select class="form-control rider-select" id="rider-{{ $r->id }}" name="rider_id">
                              <option value="" selected>Select</option>
                              @foreach(company_table('riders')->where('status' , 1)->get() as $ri)
                              <option value="{{ $ri->id }}" @if($ri->id == $r->rider_id) selected @endif>{{ $ri->rider_id }} - {{ $ri->name }}</option>
                              @endforeach
                           </select>
                        </div>
                        <div class="col-md-12 form-group text-center">
                           <button type="submit" class="btn btn-primary mt-3">Submit</button>
                        </div>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
      @endforeach
   </tbody>
</table>
{!! $data->links('pagination') !!}

@push('page-styles')
<style>
   /* Soft emphasis — gentle border/shadow breathing, no harsh opacity flicker */
   @keyframes visa-next-unpaid-soft {

      0%,
      100% {
         border-color: rgba(234, 88, 90, 0.35);
         box-shadow: 0 0 0 0 rgba(234, 88, 90, 0);
      }

      50% {
         border-color: rgba(234, 88, 90, 0.55);
         box-shadow: 0 1px 8px rgba(234, 88, 90, 0.12);
      }
   }

   .visa-next-unpaid-cell {
      background: linear-gradient(90deg, rgba(234, 88, 90, 0.04), rgba(253, 186, 116, 0.05));
   }

   .visa-next-unpaid-blink {
      padding: 0.25rem 0.5rem;
      border-radius: 8px;
      border: 1px solid rgba(234, 88, 90, 0.35);
      background: rgba(255, 253, 252, 0.95);
      animation: visa-next-unpaid-soft 2.8s ease-in-out infinite;
      transition: border-color 0.3s ease;
   }

   .visa-next-unpaid-blink:hover {
      border-color: rgba(234, 88, 90, 0.5);
      animation-play-state: paused;
   }

   @media (prefers-reduced-motion: reduce) {
      .visa-next-unpaid-blink {
         animation: none;
         border-color: rgba(234, 88, 90, 0.4);
      }
   }

   /* Paid-document expiry within 10 days (or overdue) — amber alert, soft pulse */
   @keyframes visa-expiry-alert-soft {

      0%,
      100% {
         border-color: rgba(217, 119, 6, 0.4);
         box-shadow: 0 0 0 0 rgba(217, 119, 6, 0);
      }

      50% {
         border-color: rgba(217, 119, 6, 0.65);
         box-shadow: 0 1px 8px rgba(217, 119, 6, 0.14);
      }
   }

   .visa-expiry-alert-cell {
      background: linear-gradient(90deg, rgba(254, 243, 199, 0.35), rgba(253, 230, 138, 0.2));
   }

   .visa-expiry-alert-blink {
      padding: 0.25rem 0.5rem;
      border-radius: 8px;
      border: 1px solid rgba(217, 119, 6, 0.4);
      background: rgba(255, 251, 235, 0.95);
      animation: visa-expiry-alert-soft 2.6s ease-in-out infinite;
      transition: border-color 0.3s ease;
   }

   .visa-expiry-alert-blink:hover {
      border-color: rgba(217, 119, 6, 0.55);
      animation-play-state: paused;
   }

   @media (prefers-reduced-motion: reduce) {
      .visa-expiry-alert-blink {
         animation: none;
         border-color: rgba(217, 119, 6, 0.5);
      }
   }
</style>
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th>Person ID</th>
         <th style="width: 220px;">Account Name</th>
         <th>Renewal Category</th>
         <th>Status</th>
         <th>Next Unpaid Document</th>
         <th>Expiry Document</th>
         <th>Person Code</th>
         <th>Labour Card #</th>
         <th>Policy Number</th>
         <th>Balance</th>
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
      $categoryId = (int) ($r->renewal_category_id ?? \App\Support\VisaRenewalCategoryService::defaultCategory()->id);
      $balance = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
         (int) $r->id,
         $r->rider_id ? (int) $r->rider_id : null,
         $categoryId
      )->sum('amount');
      $nextUnpaid = ($nextUnpaidVisaByAccountId ?? [])[$r->id] ?? null;
      $nextWhen = '';
      if ($nextUnpaid) {
      try {
      if (!empty($nextUnpaid->date)) {
      $nextWhen = \Carbon\Carbon::parse($nextUnpaid->date)->format('d M Y');
      } elseif (!empty($nextUnpaid->billing_month)) {
      $nextWhen = \Carbon\Carbon::parse($nextUnpaid->billing_month)->format('M Y');
      }
      } catch (\Throwable $e) {
      $nextWhen = '';
      }
      }
      $urgentExpiry = ($urgentVisaExpiryByAccountId ?? [])[$r->id] ?? null;
      $urgentExpiryWhen = '';
      if ($urgentExpiry && !empty($urgentExpiry->expiry_date)) {
      try {
      $urgentExpiryWhen = \Carbon\Carbon::parse($urgentExpiry->expiry_date)->format('d M Y');
      } catch (\Throwable $e) {
      $urgentExpiryWhen = '';
      }
      }
      $selectedPersonKey = $isEmployeeAccount ? ('employee:' . $r->employee_id) : ('rider:' . $r->rider_id);
      @endphp
      <tr class="text-center">
         <td>{{ $personRef }}</td>
         <td class="text-start"><a href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($r->id, $r->rider_id) }}">{{ $r->name }}</a></td>
         <td>{{ $r->renewalCategory->name ?? '—' }}</td>
         <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
         <td class="align-middle @if($nextUnpaid) visa-next-unpaid-cell @endif">
            @if($nextUnpaid)
            <a href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($r->id, $r->rider_id) }}" class="text-decoration-none text-body visa-next-unpaid-blink d-inline-block text-center">
               <span class="fw-semibold d-block text-body text-center">{{ $nextUnpaid->visa_status ?? '—' }}</span>
               @if($nextWhen !== '')
               <span class="text-muted small text-center">{{ \App\Helpers\Currency::symbol() }}{{ number_format((float) ($nextUnpaid->amount ?? 0), 2) }}</span>
               @endif
            </a>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td class="align-middle @if($urgentExpiry && $urgentExpiryWhen !== '') visa-expiry-alert-cell @endif">
            @if($urgentExpiry && $urgentExpiryWhen !== '')
            <a href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($r->id, $r->rider_id) }}" class="text-decoration-none text-body visa-expiry-alert-blink d-inline-block text-center" title="Visa document expiry within 10 days or overdue">
               <span class="fw-semibold d-block text-body text-center">{{ $urgentExpiry->visa_status ?? '—' }}</span>
               <span class="text-muted small d-block text-center">{{ $urgentExpiryWhen }}</span>
            </a>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td>{{ $personCode }}</td>
         <td>{{ $laborCard }}</td>
         <td>{{ $policyNo }}</td>
         <td>{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $balance, 2) }}</td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end">
                  @can('visa_expense_view')
                  <a href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($r->id, $r->rider_id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye"></i> View
                  </a>
                  @endcan
                  @can('visa_expense_edit')
                  <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount{{ $r->id }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit"></i> Edit
                  </a>
                  @endcan
                  @can('visa_expense_delete')
                  <a href="javascript:void(0);" data-delete-url="{{ route('VisaExpense.deleteaccount', $r->id) }}" class='dropdown-item waves-effect js-delete-expense-account'>
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
                  <form action="{{ route('VisaExpense.editaccount') }}" method="POST">
                     @csrf
                     <input type="hidden" name="id" value="{{ $r->id }}">
                     <div class="row">
                        <div class="form-group col-md-12">
                           <label for="person-{{ $r->id }}">Select Rider or Employee</label>
                           <select class="form-control rider-select" id="person-{{ $r->id }}" name="person_key">
                              <option value="">Select</option>
                              <optgroup label="Riders">
                                 @foreach(company_table('riders')->where('status' , 1)->get() as $ri)
                                 <option value="rider:{{ $ri->id }}" @if($selectedPersonKey === 'rider:'.$ri->id) selected @endif>{{ $ri->rider_id }} - {{ $ri->name }}</option>
                                 @endforeach
                              </optgroup>
                              <optgroup label="Employees">
                                 @foreach(\App\Models\Employee::query()->where(function ($q) { $q->where('status', 'active')->orWhere('status', 1)->orWhere('status', '1'); })->orderBy('name')->get() as $em)
                                 <option value="employee:{{ $em->id }}" @if($selectedPersonKey === 'employee:'.$em->id) selected @endif>{{ $em->employee_id }} - {{ $em->name }}</option>
                                 @endforeach
                              </optgroup>
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
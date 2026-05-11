@push('page-styles')
<style>
   /* Soft emphasis — gentle border/shadow breathing, no harsh opacity flicker */
   @keyframes visa-next-unpaid-soft {
      0%, 100% {
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
</style>
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th>Rider ID</th>
         <th style="width: 220px;">Account Name</th>
         <th>Rider Status</th>
         <th style="min-width: 160px;">Next Unpaid Expense</th>
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
      $hasActiveBike = DB::table('bikes')
      ->where('rider_id', $r->rider_id)
      ->where('warehouse', 'Active')
      ->exists();
      $badgeClass = $hasActiveBike ? 'bg-label-success' : 'bg-label-danger';
      $balance = \App\Models\visa_expenses::where('expense_account_id', $r->id)->sum('amount');
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
      @endphp
      <tr class="text-center">
         <td>{{ $r->rider->rider_id ?? '-' }}</td>
         <td class="text-start"><a href="{{ route('VisaExpense.generatentries' , $r->id) }}">{{ $r->name }}</a></td>
         <td><span class="badge {{ $badgeClass }}">{{ $hasActiveBike ? 'Active' : 'Inactive' }}</span></td>
         <td class="align-middle @if($nextUnpaid) visa-next-unpaid-cell @endif">
            @if($nextUnpaid)
            <a href="{{ route('VisaExpense.generatentries', $r->id) }}" class="text-decoration-none text-body visa-next-unpaid-blink d-inline-block text-start">
               <span class="fw-semibold d-block text-body">{{ $nextUnpaid->visa_status ?? '—' }}</span>
               @if($nextWhen !== '')
               <span class="text-muted small">{{ $nextWhen }} · {{ \App\Helpers\Currency::symbol() }}{{ number_format((float) ($nextUnpaid->amount ?? 0), 2) }}</span>
               @endif
            </a>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td>{{ $r->rider->person_code ?? '-' }}</td>
         <td>{{ $r->rider->labor_card_number ?? '-' }}</td>
         <td>{{ $r->rider->policy_no ?? '-' }}</td>
         <td>{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $balance, 2) }}</td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end">
                  <a href="{{ route('VisaExpense.generatentries' , $r->id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye"></i> View
                  </a>
                  <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount{{ $r->id }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit"></i> Edit
                  </a>
                  <a href="javascript:void(0);" data-delete-url="{{ route('VisaExpense.deleteaccount', $r->id) }}" class='dropdown-item waves-effect js-delete-expense-account'>
                     <i class="fa fa-trash"></i> Delete
                  </a>
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
                           <label for="rider-{{ $r->id }}">Select Rider</label>
                           <select class="form-control rider-select" id="rider-{{ $r->id }}" name="rider_id">
                              <option value="" selected>Select</option>
                              @foreach(DB::table('riders')->where('status' , 1)->get() as $ri)
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
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
@php
// Group individual ExpenseAccount rows by rider/employee so we show ONE row per person.
$groupedPersons = collect($data->items())->groupBy(
    fn ($r) => $r->employee_id ? 'emp:' . $r->employee_id : 'rider:' . $r->rider_id
);
$ajaxFragment = $ajaxFragment ?? false;
$pendingVisaExpenseIds = pending_deletion_ids_for(\App\Models\visa_expenses::class);
$pageAccounts = collect($data->items());
$pageAccountIds = $pageAccounts->pluck('id')->map(fn ($id) => (int) $id)->all();
$pendingExpenseAccountIds = [];
if ($pendingVisaExpenseIds !== [] && $pageAccountIds !== []) {
  $pendingExpenseAccountIds = array_flip(
    \App\Models\visa_expenses::query()
      ->whereIn('id', array_keys($pendingVisaExpenseIds))
      ->whereIn('expense_account_id', $pageAccountIds)
      ->pluck('expense_account_id')
      ->map(fn ($id) => (int) $id)
      ->unique()
      ->all()
  );
}
$accountsWithPaidTickets = [];
foreach ($pageAccounts as $pageAccount) {
  $acctId = (int) $pageAccount->id;
  if (isset($accountsWithPaidTickets[$acctId])) {
    continue;
  }
  $catId = (int) ($pageAccount->renewal_category_id ?? \App\Support\VisaRenewalCategoryService::defaultCategory()->id);
  $hasPaid = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
    $acctId,
    $pageAccount->rider_id ? (int) $pageAccount->rider_id : null,
    $catId
  )->whereRaw('LOWER(COALESCE(payment_status, "")) = ?', ['paid'])->exists();
  if ($hasPaid) {
    $accountsWithPaidTickets[$acctId] = true;
  }
}
@endphp
<table class="table" id="dataTableBuilder">
   <thead>
      <tr id="rlHead">
         <th class="sorting" data-k="person_id">Person ID<span class="ar">⇅</span></th>
         <th class="sorting" data-k="name">Name<span class="ar">⇅</span></th>
         <th class="sorting" data-k="categories">Visa Categories<span class="ar">⇅</span></th>
         <th class="sorting" data-k="status">Status<span class="ar">⇅</span></th>
         <th class="sorting" data-k="next_unpaid">Next Unpaid Document<span class="ar">⇅</span></th>
         <th class="sorting" data-k="expiry">Expiry Document<span class="ar">⇅</span></th>
         <th class="sorting" data-k="person_code">Person Code<span class="ar">⇅</span></th>
         <th class="sorting" data-k="labour">Labour Card #<span class="ar">⇅</span></th>
         <th class="sorting" data-k="policy">Policy Number<span class="ar">⇅</span></th>
         <th class="sorting" data-k="balance">Total Balance<span class="ar">⇅</span></th>
         <th class="nosort">Actions</th>
      </tr>
   </thead>
   <tbody id="rlBody">
      @forelse($groupedPersons as $personKey => $personAccounts)
      @php
      $r = $personAccounts->first(); // representative row
      $isEmployeeAccount = !empty($r->employee_id);

      $hasActiveBike = !$isEmployeeAccount && company_table('bikes')
         ->where('rider_id', $r->rider_id)
         ->where('warehouse', 'Active')
         ->exists();
      $badgeClass = $isEmployeeAccount ? 'bg-label-info' : ($hasActiveBike ? 'bg-label-success' : 'bg-label-danger');
      $badgeLabel = $isEmployeeAccount ? 'Employee' : ($hasActiveBike ? 'Active' : 'Inactive');

      $personRef  = $isEmployeeAccount ? ($r->employee->employee_id ?? '-') : ($r->rider->rider_id ?? '-');
      $personName = $isEmployeeAccount ? ($r->employee->name ?? $r->name) : ($r->rider->name ?? $r->name);
      $personCode = $isEmployeeAccount ? ($r->employee->person_code ?? '-') : ($r->rider->person_code ?? '-');
      $laborCard  = $isEmployeeAccount ? ($r->employee->labor_card_number ?? '-') : ($r->rider->labor_card_number ?? '-');
      $policyNo   = $isEmployeeAccount ? '-' : ($r->rider->policy_no ?? '-');

      // Total balance across ALL categories for this person
      $totalBalance = $personAccounts->sum(function ($acct) {
         $catId = (int) ($acct->renewal_category_id ?? \App\Support\VisaRenewalCategoryService::defaultCategory()->id);
         return \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
            (int) $acct->id,
            $acct->rider_id ? (int) $acct->rider_id : null,
            $catId
         )->sum('amount');
      });

      // Gather next-unpaid and urgent-expiry across ALL accounts for this person
      $nextUnpaid   = null;
      $urgentExpiry = null;
      foreach ($personAccounts as $acct) {
         if (!$nextUnpaid && isset($nextUnpaidVisaByAccountId[$acct->id])) {
            $nextUnpaid = $nextUnpaidVisaByAccountId[$acct->id];
         }
         if (!$urgentExpiry && isset($urgentVisaExpiryByAccountId[$acct->id])) {
            $urgentExpiry = $urgentVisaExpiryByAccountId[$acct->id];
         }
      }

      $nextWhen = '';
      if ($nextUnpaid) {
         try {
            $nextWhen = !empty($nextUnpaid->date)
               ? \Carbon\Carbon::parse($nextUnpaid->date)->format('d M Y')
               : (!empty($nextUnpaid->billing_month) ? \Carbon\Carbon::parse($nextUnpaid->billing_month)->format('M Y') : '');
         } catch (\Throwable $e) { $nextWhen = ''; }
      }

      $urgentExpiryWhen = '';
      if ($urgentExpiry && !empty($urgentExpiry->expiry_date)) {
         try { $urgentExpiryWhen = \Carbon\Carbon::parse($urgentExpiry->expiry_date)->format('d M Y'); }
         catch (\Throwable $e) { $urgentExpiryWhen = ''; }
      }

      // Rider-overview URL (new grouped detail page)
      $overviewUrl = $isEmployeeAccount
         ? route('VisaExpense.showForRider', ['riderId' => $r->employee_id, 'type' => 'employee'])
         : route('VisaExpense.showForRider', ['riderId' => $r->rider_id]);

      // Keep a reference to the first single-account URL (for backward compat modals)
      $firstAccountUrl = \App\Support\VisaRenewalCategoryService::generatentriesUrl($r->id, $r->rider_id);

      $selectedPersonKey = $isEmployeeAccount ? ('employee:' . $r->employee_id) : ('rider:' . $r->rider_id);
      @endphp
      <tr class="text-center">
         <td>{{ $personRef }}</td>
         <td class="text-start">
            <a href="{{ $overviewUrl }}" class="fw-semibold text-decoration-none">{{ $personName }}</a>
         </td>
         {{-- All visa categories for this rider as badges --}}
         <td class="text-start">
            @foreach($personAccounts as $acct)
            <a href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($acct->id, $acct->rider_id) }}"
               class="badge bg-label-primary me-1 mb-1 text-decoration-none"
               title="Open {{ $acct->renewalCategory->name ?? 'Visa' }} account">
               {{ $acct->renewalCategory->name ?? '—' }}
            </a>
            @endforeach
         </td>
         <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
         <td class="align-middle @if($nextUnpaid) visa-next-unpaid-cell @endif">
            @if($nextUnpaid)
            <a href="{{ $overviewUrl }}" class="text-decoration-none text-body visa-next-unpaid-blink d-inline-block text-center">
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
            <a href="{{ $overviewUrl }}" class="text-decoration-none text-body visa-expiry-alert-blink d-inline-block text-center" title="Visa document expiry within 10 days or overdue">
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
         <td>{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $totalBalance, 2) }}</td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end">
                  @canany(['visa_expense_entries_view', 'visa_expense_installments_view', 'visa_expense_account_creation_view'])
                  <a href="{{ $overviewUrl }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye me-1"></i> View All Visa History
                  </a>
                  @foreach($personAccounts as $acct)
                  <a href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($acct->id, $acct->rider_id) }}" class='dropdown-item waves-effect ps-4'>
                     <i class="fa fa-passport me-1 text-muted"></i> {{ $acct->renewalCategory->name ?? 'Account #'.$acct->id }}
                  </a>
                  @endforeach
                  @endcanany
                  @can('visa_expense_account_creation_delete')
                  <div class="dropdown-divider"></div>
                  @foreach($personAccounts as $acct)
                  @php
                     $acctPendingDeletion = isset($pendingExpenseAccountIds[(int) $acct->id]);
                     $acctHasPaidTickets = isset($accountsWithPaidTickets[(int) $acct->id]);
                  @endphp
                  @if($acctPendingDeletion)
                  <span class="dropdown-item-text text-muted small">
                     <i class="ti ti-lock me-1"></i>{{ $acct->renewalCategory->name ?? 'Account #'.$acct->id }} — Pending Deletion
                  </span>
                  @elseif($acctHasPaidTickets)
                  <span class="dropdown-item-text text-muted small" title="Paid tickets exist. Reverse those payments before deleting this account.">
                     <i class="ti ti-lock me-1"></i>Delete blocked: {{ $acct->renewalCategory->name ?? 'Account #'.$acct->id }} (paid tickets)
                  </span>
                  @else
                  <a href="javascript:void(0);" data-delete-url="{{ route('VisaExpense.deleteaccount', $acct->id) }}" class='dropdown-item waves-effect js-delete-expense-account text-danger'>
                     <i class="fa fa-trash me-1"></i> Delete: {{ $acct->renewalCategory->name ?? 'Account #'.$acct->id }}
                  </a>
                  @endif
                  @endforeach
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @empty
      <tr>
         <td colspan="11" class="text-center text-muted py-4">No visa expense accounts found.</td>
      </tr>
      @endforelse
   </tbody>
</table>
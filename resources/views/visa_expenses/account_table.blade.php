@push('page-styles')
<style>
   .visa-status-stack {
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      gap: .28rem;
      text-decoration: none;
   }
   .visa-status-stack .visa-status-amount {
      font-size: .82rem;
      font-weight: 700;
      color: #b91c1c;
      line-height: 1.2;
      font-variant-numeric: tabular-nums;
   }
   .visa-expiry-list {
      display: inline-flex;
      flex-direction: column;
      align-items: stretch;
      min-width: 0;
      max-width: 100%;
      text-decoration: none;
      color: inherit;
   }
   .visa-expiry-row {
      display: block;
      padding: .28rem .15rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      font-size: .8rem;
      line-height: 1.25;
      color: #1e293b;
   }
   .visa-expiry-row + .visa-expiry-row {
      border-top: 1px solid #e2e8f0;
   }
   .visa-expiry-row .visa-expiry-status {
      font-weight: 600;
   }
   .visa-expiry-row .visa-expiry-date {
      font-weight: 700;
      color: #b91c1c;
      font-variant-numeric: tabular-nums;
   }
   .riders-list-demo .rl-tbox tr.rl-fav-row > td {
      background: #fffbeb;
   }
</style>
@endpush
@php
// Group individual ExpenseAccount rows by rider/employee so we show ONE row per person.
$groupedPersons = collect($data->items())->groupBy(
    fn ($r) => $r->employee_id ? 'emp:' . $r->employee_id : 'rider:' . $r->rider_id
);
$ajaxFragment = $ajaxFragment ?? false;
$favoriteRiderIds = collect($favoriteRiderIds ?? (auth()->user()->favorite_rider_ids ?? []))->map(fn ($id) => (int) $id)->all();
$favoriteEmployeeIds = collect($favoriteEmployeeIds ?? (auth()->user()->favorite_employee_ids ?? []))->map(fn ($id) => (int) $id)->all();
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
  if (array_key_exists($acctId, $accountsWithPaidTickets)) {
    continue;
  }
  $catId = (int) ($pageAccount->renewal_category_id ?? \App\Support\VisaRenewalCategoryService::defaultCategory()->id);
  $accountsWithPaidTickets[$acctId] = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
    $acctId,
    $pageAccount->rider_id ? (int) $pageAccount->rider_id : null,
    $catId
  )->whereRaw('LOWER(COALESCE(payment_status, "")) = ?', ['paid'])->exists();
}
@endphp
<table class="table" id="dataTableBuilder">
   <thead>
      <tr id="rlHead">
         <th class="sorting" data-k="person_id">Person ID<span class="ar">⇅</span></th>
         <th class="sorting" data-k="name">Name<span class="ar">⇅</span></th>
         <th class="sorting" data-k="category">Visa Category<span class="ar">⇅</span></th>
         <th class="sorting" data-k="status">Status<span class="ar">⇅</span></th>
         <th class="sorting" data-k="wps">WPS<span class="ar">⇅</span></th>
         <th class="sorting" data-k="next_unpaid">Visa Status<span class="ar">⇅</span></th>
         <th class="sorting" data-k="expiry">Expiry Document<span class="ar">⇅</span></th>
         <th class="sorting" data-k="person_code">Person Code<span class="ar">⇅</span></th>
         <th class="sorting" data-k="labour">Labour Card #<span class="ar">⇅</span></th>
         <th class="sorting" data-k="policy">Policy Number<span class="ar">⇅</span></th>
         <th class="sorting" data-k="balance">Total<span class="ar">⇅</span></th>
         <th class="sorting" data-k="paid">Paid<span class="ar">⇅</span></th>
         <th class="sorting" data-k="unpaid">Unpaid<span class="ar">⇅</span></th>
         <th class="nosort">Actions</th>
      </tr>
   </thead>
   <tbody id="rlBody">
      @forelse($groupedPersons as $personKey => $personAccounts)
      @php
      $seedAccount = $personAccounts->first();
      $personAccountsAll = \App\Support\VisaRenewalCategoryService::siblingAccountsForPerson($seedAccount);
      if ($personAccountsAll->isEmpty()) {
         $personAccountsAll = $personAccounts;
      }
      $r = \App\Support\VisaRenewalCategoryService::latestAccountForPerson($seedAccount);
      $isEmployeeAccount = !empty($r->employee_id);

      $hasActiveBike = !$isEmployeeAccount && company_table('bikes')
         ->where('rider_id', $r->rider_id)
         ->where('warehouse', 'Active')
         ->exists();
      $badgeClass = $isEmployeeAccount ? 'bg-label-info' : ($hasActiveBike ? 'bg-label-success' : 'bg-label-danger');
      $badgeLabel = $isEmployeeAccount ? 'Employee' : ($hasActiveBike ? 'Active' : 'Inactive');

      // Prefer person details already eager-loaded on the page row when the latest account is a sibling
      $personSource = $personAccounts->first(function ($acct) use ($r) {
         return (int) $acct->id === (int) $r->id;
      }) ?? $seedAccount;
      if (!$r->relationLoaded('rider') && $personSource->relationLoaded('rider')) {
         $r->setRelation('rider', $personSource->rider);
      }
      if (!$r->relationLoaded('employee') && $personSource->relationLoaded('employee')) {
         $r->setRelation('employee', $personSource->employee);
      }

      $personRef  = $isEmployeeAccount ? ($r->employee->employee_id ?? $personSource->employee->employee_id ?? '-') : ($r->rider->rider_id ?? $personSource->rider->rider_id ?? '-');
      $personName = $isEmployeeAccount ? ($r->employee->name ?? $personSource->employee->name ?? $r->name) : ($r->rider->name ?? $personSource->rider->name ?? $r->name);
      $personCode = $isEmployeeAccount ? ($r->employee->person_code ?? $personSource->employee->person_code ?? '-') : ($r->rider->person_code ?? $personSource->rider->person_code ?? '-');
      $laborCard  = $isEmployeeAccount ? ($r->employee->labor_card_number ?? $personSource->employee->labor_card_number ?? '-') : ($r->rider->labor_card_number ?? $personSource->rider->labor_card_number ?? '-');
      $policyNo   = $isEmployeeAccount ? '-' : ($r->rider->policy_no ?? $personSource->rider->policy_no ?? '-');
      $wpsRaw = trim($isEmployeeAccount
         ? (string) ($r->employee->wps ?? $personSource->employee->wps ?? '')
         : (string) ($r->rider->wps ?? $personSource->rider->wps ?? ''));
      $wpsOptions = $wpsOptions ?? [];
      $wpsDisplay = $wpsRaw;
      $wpsKnown = false;
      if ($wpsRaw !== '') {
         foreach ($wpsOptions as $wpsOptValue => $wpsOptLabel) {
            if (strcasecmp((string) $wpsOptValue, $wpsRaw) === 0) {
               $wpsDisplay = (string) $wpsOptLabel;
               $wpsKnown = true;
               break;
            }
         }
      }

      $latestCategoryId = (int) ($r->renewal_category_id ?? \App\Support\VisaRenewalCategoryService::defaultCategory()->id);
      $latestCategoryName = $r->renewalCategory->name ?? '—';
      $latestAccountUrl = \App\Support\VisaRenewalCategoryService::generatentriesUrl($r->id, $r->rider_id);

      $categoryExpenseAmounts = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
         (int) $r->id,
         $r->rider_id ? (int) $r->rider_id : null,
         $latestCategoryId
      )->get(['amount', 'payment_status']);
      $totalBalance = (float) $categoryExpenseAmounts->sum('amount');
      $paidBalance = (float) $categoryExpenseAmounts
         ->filter(fn ($row) => strtolower((string) ($row->payment_status ?? '')) === 'paid')
         ->sum('amount');
      $unpaidBalance = (float) $categoryExpenseAmounts
         ->filter(fn ($row) => strtolower((string) ($row->payment_status ?? '')) === 'unpaid')
         ->sum('amount');

      $nextUnpaid = $nextUnpaidVisaByAccountId[$r->id] ?? null;
      if (!$nextUnpaid) {
         $nextUnpaid = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
            (int) $r->id,
            $r->rider_id ? (int) $r->rider_id : null,
            $latestCategoryId
         )
            ->where('payment_status', 'unpaid')
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('date', 'asc')
            ->orderBy('billing_month', 'asc')
            ->orderBy('id', 'asc')
            ->first();
      }
      $urgentExpiryItems = collect($urgentVisaExpiryByAccountId[$r->id] ?? [])
         ->filter(fn ($ve) => ! empty($ve->expiry_date))
         ->values();

      $nextWhen = '';
      if ($nextUnpaid) {
         try {
            $nextWhen = !empty($nextUnpaid->date)
               ? \Carbon\Carbon::parse($nextUnpaid->date)->format('d M Y')
               : (!empty($nextUnpaid->billing_month) ? \Carbon\Carbon::parse($nextUnpaid->billing_month)->format('M-Y') : '');
         } catch (\Throwable $e) { $nextWhen = ''; }
      }

      $overviewUrl = $isEmployeeAccount
         ? route('VisaExpense.showForRider', ['riderId' => $r->employee_id, 'type' => 'employee'])
         : route('VisaExpense.showForRider', ['riderId' => $r->rider_id]);
      $personModelId = $isEmployeeAccount ? (int) $r->employee_id : (int) $r->rider_id;
      $isFav = $isEmployeeAccount
         ? in_array($personModelId, $favoriteEmployeeIds, true)
         : in_array($personModelId, $favoriteRiderIds, true);
      @endphp
      <tr class="text-center{{ $isFav ? ' rl-fav-row' : '' }}">
         <td>{{ $personRef }}</td>
         <td class="name text-start">
            <button type="button"
               class="rl-star{{ $isFav ? ' on' : '' }}"
               data-fav="{{ $personModelId }}"
               data-fav-type="{{ $isEmployeeAccount ? 'employee' : 'rider' }}"
               aria-label="{{ $isFav ? 'Remove from' : 'Add to' }} favorites">★</button>
            <a href="{{ $overviewUrl }}" class="fw-semibold text-decoration-none">{{ $personName }}</a>
         </td>
         <td class="text-center">
            <a href="{{ $latestAccountUrl }}"
               class="badge bg-label-primary text-decoration-none"
               title="Open {{ $latestCategoryName }} account">
               {{ $latestCategoryName }}
            </a>
         </td>
         <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
         <td>
            @if($wpsDisplay !== '')
            <span class="badge {{ $wpsKnown ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $wpsDisplay }}</span>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td class="align-middle">
            @if($nextUnpaid)
            <a href="{{ $latestAccountUrl }}" class="visa-status-stack" title="@if($nextWhen !== ''){{ $nextWhen }}@endif">
               <span class="badge bg-label-primary">{{ $nextUnpaid->visa_status ?? '—' }}</span>
               <span class="visa-status-amount">{!! \App\Helpers\Currency::format((float) ($nextUnpaid->amount ?? 0), 2) !!}</span>
            </a>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td class="align-middle">
            @if($urgentExpiryItems->isNotEmpty())
            <a href="{{ $latestAccountUrl }}" class="visa-expiry-list" title="Visa documents expired or expiring within 10 days">
               @foreach($urgentExpiryItems as $urgentExpiry)
               @php
                  $urgentExpiryWhen = '';
                  try { $urgentExpiryWhen = \Carbon\Carbon::parse($urgentExpiry->expiry_date)->format('d M Y'); }
                  catch (\Throwable $e) { $urgentExpiryWhen = ''; }
               @endphp
               @if($urgentExpiryWhen !== '')
               <span class="visa-expiry-row">
                  <span class="visa-expiry-status">{{ $urgentExpiry->visa_status ?? '—' }}</span>
                  <span class="visa-expiry-date">{{ $urgentExpiryWhen }}</span>
               </span>
               @endif
               @endforeach
            </a>
            @else
            <span class="text-muted">—</span>
            @endif
         </td>
         <td>{{ $personCode }}</td>
         <td>{{ $laborCard }}</td>
         <td>{{ $policyNo }}</td>
         <td>{!! \App\Helpers\Currency::format($totalBalance, 2) !!}</td>
         <td class="text-success">{!! \App\Helpers\Currency::format($paidBalance, 2) !!}</td>
         <td class="{{ $unpaidBalance <= 0 ? 'text-success' : 'text-danger' }}">{!! \App\Helpers\Currency::format($unpaidBalance, 2) !!}</td>
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
                  @foreach($personAccountsAll as $acct)
                  <a href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($acct->id, $acct->rider_id) }}" class='dropdown-item waves-effect ps-4'>
                     <i class="fa fa-passport me-1 text-muted"></i> {{ $acct->renewalCategory->name ?? 'Account #'.$acct->id }}
                  </a>
                  @endforeach
                  @endcanany
                  @can('visa_expense_account_creation_delete')
                  <div class="dropdown-divider"></div>
                  @foreach($personAccountsAll as $acct)
                  @php
                     $acctIdKey = (int) $acct->id;
                     $acctPendingDeletion = isset($pendingExpenseAccountIds[$acctIdKey]);
                     if (!array_key_exists($acctIdKey, $accountsWithPaidTickets)) {
                        $paidCatId = (int) ($acct->renewal_category_id ?? \App\Support\VisaRenewalCategoryService::defaultCategory()->id);
                        $accountsWithPaidTickets[$acctIdKey] = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
                           $acctIdKey,
                           $acct->rider_id ? (int) $acct->rider_id : null,
                           $paidCatId
                        )->whereRaw('LOWER(COALESCE(payment_status, "")) = ?', ['paid'])->exists();
                     }
                     $acctHasPaidTickets = (bool) $accountsWithPaidTickets[$acctIdKey];
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
         <td colspan="14" class="text-center text-muted py-4 rl-empty">No visa expense accounts found.</td>
      </tr>
      @endforelse
   </tbody>
</table>
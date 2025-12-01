<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\CommonHelper;
use App\Helpers\General;
use App\Models\Rider;
use App\Models\Riders;
use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
  public function rider_invoice_index()
  {
    return view('reports.rider');
  }
  public function vendor_invoice_index()
  {
    return view('reports.vendor');
  }
  public function rider_list()
  {
    $riders = Riders::all()->sortBy('rider_id');
    return view('reports.rider_list', compact('riders'));
  }
  public function rider_report()
  {
    $riders = []; //Rider::all()->sortBy('rider_id');
    return view('reports.rider_report', compact('riders'));
  }
  public function rider_monthly_report()
  {
    return view('reports.rider_monthly_report');
  }
  public function rider_report_data(Request $request)
  {
    // Increase execution time for large datasets (e.g., "Show All")
    set_time_limit(300); // 5 minutes max execution time

    $data = '';
    $total = 0;
    $ob_total = 0;
    $opening_balance_total = 0;
    $b_total = 0;
    $total_debit_sum = 0;
    $total_credit_sum = 0;

    // Handle billing_month - make it optional
    $billing_month = null;
    if ($request->billing_month) {
      $billing_month = $request->billing_month;
      if (!str_contains($billing_month, '-01')) {
        $billing_month = $billing_month . "-01";
      }
    }

    // Optimize query with eager loading to reduce database queries
    $result = Riders::with(['vendor', 'bikes']);

    if ($request->status && $request->status !== '') {
      $result = $result->where('status', $request->status);
    }
    if ($request->VID && $request->VID !== '') {
      $result = $result->where('VID', $request->VID);
    }
    if ($request->designation && $request->designation !== '') {
      $result = $result->where('designation', $request->designation);
    }
    // Filter by bike assignment status (Active/Inactive based on bike assignment)
    if ($request->has('bike_assignment_status') && !empty($request->bike_assignment_status)) {
      if ($request->bike_assignment_status === 'Active') {
        // Riders who have an active bike assigned
        $result = $result->whereHas('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      } elseif ($request->bike_assignment_status === 'Inactive') {
        // Riders who don't have an active bike assigned
        $result = $result->whereDoesntHave('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      }
    }

    // Global pagination
    $perPage = $request->get('per_page', 25);

    // Handle 'all' and -1 options (show all records)
    if ($perPage === 'all' || $perPage === '-1' || $perPage == -1) {
      $perPage = $result->count(); // Get all records
    } else {
      $perPage = (int) $perPage;
      if ($perPage <= 0) $perPage = 25;
    }

    $page = (int) ($request->get('page') ?: 1);
    $totalCount = $result->count();
    $result = $result->orderBy('rider_id')->forPage($page, $perPage)->get();

    // Pre-load active bikes status for all riders in one query
    $riderIds = $result->pluck('id')->toArray();
    $activeBikeRiders = DB::table('bikes')
      ->whereIn('rider_id', $riderIds)
      ->where('warehouse', 'Active')
      ->pluck('rider_id')
      ->toArray();



    // Process each rider
    foreach ($result as $rider) {
      // Initialize default values
      $opening_balance = 0.00;
      $balance = 0.00;
      $rider_total_debit = 0.00;
      $rider_total_credit = 0.00;

      if (isset($rider->account_id) && $rider->account_id) {
        if ($billing_month) {
          // If billing month is provided, get monthly data
          $opening_balance = Account::Monthly_ob($billing_month, $rider->account_id);
          $balance = Account::BillingMonth_Balance($billing_month, $rider->account_id);

          // Calculate total debits and credits for the specific month
          $rider_total_debit = \App\Models\Transactions::where('account_id', $rider->account_id)
            ->whereDate('billing_month', $billing_month)->sum('debit');
          $rider_total_credit = \App\Models\Transactions::where('account_id', $rider->account_id)
            ->whereDate('billing_month', $billing_month)->sum('credit');

          $subtotal_debit = \App\Models\Transactions::where('account_id', $rider->account_id)
            ->sum('debit');
          $subtotal_credit = \App\Models\Transactions::where('account_id', $rider->account_id)
            ->sum('credit');
          $subtotal_balance = $subtotal_debit - $subtotal_credit;
        } else {
          // If no billing month, get overall account balance
          $opening_balance = 0.00; // No opening balance for overall report

          // Calculate total debits and credits for entire history
          $rider_total_debit = \App\Models\Transactions::where('account_id', $rider->account_id)->sum('debit');

          $rider_total_credit = \App\Models\Transactions::where('account_id', $rider->account_id)->sum('credit');

          // Calculate balance as debit minus credit
          $balance = $rider_total_debit - $rider_total_credit;
          $subtotal_debit = \App\Models\Transactions::where('account_id', $rider->account_id)
            ->sum('debit');
          $subtotal_credit = \App\Models\Transactions::where('account_id', $rider->account_id)
            ->sum('credit');
          $subtotal_balance = $subtotal_debit - $subtotal_credit;
        }

        // No additional calculations - just show raw debit and credit values
      }
      $data .= '<tr>';
      $data .= '<td  >' . @$rider->rider_id . '</td>';
      $data .= '<td  >' . @$rider->name . '</td>';
      $data .= '<td >' . @$rider->vendor->name . '</td>';
      $data .= '<td >' . @$rider->designation . '</td>';
      $data .= '<td >' . @$rider->person_code . '</td>';
      $data .= '<td >' . @$rider->labor_card_number . '</td>';
      $data .= '<td  >' . @$rider->bikes->plate . '</td>';
      $data .= '<td  >' . $rider->wps . '</td>';

      // Use pre-loaded active bike status (optimized - no database query per rider)
      $isActive = in_array($rider->id, $activeBikeRiders);
      $badgeClass = $isActive ? 'bg-label-success' : 'bg-label-danger';
      $statusText = $isActive ? 'Active' : 'Inactive';
      $data .= '<td>
            <span class="badge ' . $badgeClass . '">' . $statusText . '</span>
          </td>';


      $data .= '<td align="right" >' . number_format($opening_balance, 2) . '</td>';
      $data .= '<td align="right" >' . number_format($balance, 2) . '</td>';
      $data .= '<td align="right">' . Account::show_bal($opening_balance + $balance) . '</td>';
      $data .= '<td align="right">' . Account::show_bal($subtotal_balance - ($opening_balance + $balance)) . '</td>';
      $data .= '<td align="right">' . number_format($subtotal_balance, 2) . '</td>';
      $data .= '</tr>';

      $opening_balance_total += $opening_balance;
      $ob_total += $opening_balance;
      $total += $balance;
      $b_total += $opening_balance + $balance;
      $total_debit_sum += $rider_total_debit;
      $total_credit_sum += $rider_total_credit;
    }






    $data .= '<tr>';
    $data .= '<td colspan="9"></td>';
    $data .= '<th style="text-align: right">' . number_format($opening_balance_total, 2) . '</th>';
    $data .= '<th style="text-align: right">' . number_format($total, 2) . '</th>';
    $data .= '<th style="text-align: right">' . Account::show_bal($b_total) . '</th>';
    $data .= '<th style="text-align: right">' . number_format($total_debit_sum, 2) . '</th>';
    $data .= '<th style="text-align: right">' . number_format($total_credit_sum, 2) . '</th>';
    $data .= '</tr>';

    // Render pagination links (global component) for consistency with riders
    $paginationLinks = view('components.global-pagination', [
      'paginator' => new \Illuminate\Pagination\LengthAwarePaginator([], $totalCount, $perPage, $page, ['path' => url()->current()]),
      'perPageOptions' => [20, 50, 100, -1]
    ])->render();

    return [
      'data' => $data,
      'opening_balance_total' => $opening_balance_total,
      'total' => $total,
      'b_total' => $b_total,
      'total_debit_sum' => $total_debit_sum,
      'total_credit_sum' => $total_credit_sum,
      'paginationLinks' => $paginationLinks,
      'totalCount' => $totalCount,
      'perPage' => $perPage,
      'page' => $page,
    ];
  }

  public function rider_monthly_report_data(Request $request)
  {
    set_time_limit(300);

    $validated = $request->validate([
      'billing_month' => ['required', 'date_format:Y-m'],
    ]);

    $billingMonthInput = $validated['billing_month'];
    $billingMonth = str_ends_with($billingMonthInput, '-01') ? $billingMonthInput : $billingMonthInput . '-01';
    $billingMonthLabel = Carbon::parse($billingMonth)->format('F Y');

    $result = Riders::with(['vendor', 'bikes']);

    if ($request->status && $request->status !== '') {
      $result = $result->where('status', $request->status);
    }
    if ($request->VID && $request->VID !== '') {
      $result = $result->where('VID', $request->VID);
    }
    if ($request->designation && $request->designation !== '') {
      $result = $result->where('designation', $request->designation);
    }
    // Filter by bike assignment status (Active/Inactive based on bike assignment)
    if ($request->has('bike_assignment_status') && !empty($request->bike_assignment_status)) {
      if ($request->bike_assignment_status === 'Active') {
        // Riders who have an active bike assigned
        $result = $result->whereHas('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      } elseif ($request->bike_assignment_status === 'Inactive') {
        // Riders who don't have an active bike assigned
        $result = $result->whereDoesntHave('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      }
    }
    if ($quickSearch = trim((string) $request->quick_search)) {
      $result = $result->where(function ($query) use ($quickSearch) {
        $query->where('name', 'like', '%' . $quickSearch . '%')
          ->orWhere('rider_id', 'like', '%' . $quickSearch . '%')
          ->orWhere('person_code', 'like', '%' . $quickSearch . '%')
          ->orWhere('labor_card_number', 'like', '%' . $quickSearch . '%');
      });
    }

    $perPage = $request->get('per_page', 25);
    if ($perPage === 'all' || $perPage === '-1' || $perPage == -1) {
      $perPage = $result->count();
    } else {
      $perPage = (int) $perPage;
      if ($perPage <= 0) {
        $perPage = 25;
      }
    }

    $page = (int) ($request->get('page') ?: 1);
    $totalCount = $result->count();
    $result = $result->orderBy('rider_id')->forPage($page, $perPage)->get();

    $riderIds = $result->pluck('id')->filter()->all();
    $activeBikeRiders = [];
    if (!empty($riderIds)) {
      $activeBikeRiders = DB::table('bikes')
        ->whereIn('rider_id', $riderIds)
        ->where('warehouse', 'Active')
        ->pluck('rider_id')
        ->toArray();
    }

    $accountIds = $result->pluck('account_id')->filter()->unique()->values()->all();

    $monthlySums = collect();
    $openingSums = collect();

    if (!empty($accountIds)) {
      $monthlySums = Transactions::select(
        'account_id',
        DB::raw('SUM(debit) as debit_sum'),
        DB::raw('SUM(credit) as credit_sum')
      )
        ->whereIn('account_id', $accountIds)
        ->whereDate('billing_month', $billingMonth)
        ->groupBy('account_id')
        ->get()
        ->keyBy('account_id');

      $openingSums = Transactions::select(
        'account_id',
        DB::raw('SUM(debit) as debit_sum'),
        DB::raw('SUM(credit) as credit_sum')
      )
        ->whereIn('account_id', $accountIds)
        ->whereDate('billing_month', '<', $billingMonth)
        ->groupBy('account_id')
        ->get()
        ->keyBy('account_id');
    }

    $data = '';
    $openingTotal = 0;
    $monthlyDebitTotal = 0;
    $monthlyCreditTotal = 0;
    $netActivityTotal = 0;
    $closingTotal = 0;

    foreach ($result as $rider) {
      $accountId = $rider->account_id;
      $openingBalance = 0.00;
      $monthDebit = 0.00;
      $monthCredit = 0.00;

      if ($accountId) {
        $openingRecord = $openingSums->get($accountId);
        if ($openingRecord) {
          $openingBalance = (float) $openingRecord->debit_sum - (float) $openingRecord->credit_sum;
        }

        $monthlyRecord = $monthlySums->get($accountId);
        if ($monthlyRecord) {
          $monthDebit = (float) $monthlyRecord->debit_sum;
          $monthCredit = (float) $monthlyRecord->credit_sum;
        }
      }

      $netActivity = $monthDebit - $monthCredit;
      $closingBalance = $openingBalance + $netActivity;

      $isActive = in_array($rider->id, $activeBikeRiders);
      $badgeClass = $isActive ? 'bg-label-success' : 'bg-label-danger';
      $statusText = $isActive ? 'Active' : 'Inactive';

      $data .= '<tr>';
      $data .= '<td>' . e($rider->rider_id) . '</td>';
      $data .= '<td>' . e($rider->name) . '</td>';
      $data .= '<td>' . e(optional($rider->vendor)->name) . '</td>';
      $data .= '<td>' . e($rider->designation) . '</td>';
      $data .= '<td>' . e($rider->person_code) . '</td>';
      $data .= '<td>' . e($rider->labor_card_number) . '</td>';
      $data .= '<td>' . e(optional($rider->bikes)->plate) . '</td>';
      $data .= '<td>' . e($rider->wps) . '</td>';
      $data .= '<td><span class="badge ' . $badgeClass . '">' . $statusText . '</span></td>';
      $data .= '<td>' . e($billingMonthLabel) . '</td>';
      $data .= '<td align="right">' . number_format($openingBalance, 2) . '</td>';
      $data .= '<td align="right">' . number_format($monthDebit, 2) . '</td>';
      $data .= '<td align="right">' . number_format($monthCredit, 2) . '</td>';
      $data .= '<td align="right">' . number_format($netActivity, 2) . '</td>';
      $data .= '<td align="right">' . number_format($closingBalance, 2) . '</td>';
      $data .= '<td></td>';
      $data .= '<td></td>';
      $data .= '</tr>';

      $openingTotal += $openingBalance;
      $monthlyDebitTotal += $monthDebit;
      $monthlyCreditTotal += $monthCredit;
      $netActivityTotal += $netActivity;
      $closingTotal += $closingBalance;
    }

    if ($result->count() > 0) {
      $data .= '<tr class="font-weight-bold total-row">';
      $data .= '<td colspan="10" style="text-align:right">Totals</td>';
      $data .= '<th style="text-align:right">' . number_format($openingTotal, 2) . '</th>';
      $data .= '<th style="text-align:right">' . number_format($monthlyDebitTotal, 2) . '</th>';
      $data .= '<th style="text-align:right">' . number_format($monthlyCreditTotal, 2) . '</th>';
      $data .= '<th style="text-align:right">' . number_format($netActivityTotal, 2) . '</th>';
      $data .= '<th style="text-align:right">' . number_format($closingTotal, 2) . '</th>';
      $data .= '<td colspan="2"></td>';
      $data .= '</tr>';
    }

    $paginationLinks = view('components.global-pagination', [
      'paginator' => new \Illuminate\Pagination\LengthAwarePaginator([], $totalCount, $perPage, $page, ['path' => url()->current()]),
      'perPageOptions' => [20, 50, 100, -1]
    ])->render();

    return [
      'data' => $data,
      'opening_balance_total' => $openingTotal,
      'monthly_debit_total' => $monthlyDebitTotal,
      'monthly_credit_total' => $monthlyCreditTotal,
      'net_activity_total' => $netActivityTotal,
      'closing_balance_total' => $closingTotal,
      'paginationLinks' => $paginationLinks,
      'totalCount' => $totalCount,
      'perPage' => $perPage,
      'page' => $page,
    ];
  }
}

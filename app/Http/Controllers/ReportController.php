<?php

namespace App\Http\Controllers;

use App\Models\Riders;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
  public function __construct()
  {
    $this->middleware('permission:riders_report_view')->only(
      'rider_report',
      'rider_monthly_report',
      'rider_report_data',
      'rider_monthly_report_data',
      'rider_report_detail'
    );
  }

  public function rider_report()
  {
    $riders = [];
    return view('reports.rider_report', compact('riders'));
  }
  public function rider_monthly_report()
  {
    return view('reports.rider_monthly_report');
  }
  public function rider_report_data(Request $request)
  {
    set_time_limit(300);

    $normalizeMonth = static function ($value) {
      $value = trim((string) $value);
      if ($value === '') {
        return Carbon::now()->startOfMonth()->format('Y-m-01');
      }
      if (preg_match('/^\d{4}-\d{2}-01$/', $value)) {
        return $value;
      }
      if (preg_match('/^\d{4}-\d{2}$/', $value)) {
        return $value . '-01';
      }

      return Carbon::parse($value)->startOfMonth()->format('Y-m-01');
    };

    $fromMonth = $normalizeMonth($request->input('from_month', $request->input('billing_month')));
    $toMonth = $normalizeMonth($request->input('to_month', $request->input('billing_month', $fromMonth)));

    $fromCarbon = Carbon::parse($fromMonth)->startOfMonth();
    $toCarbon = Carbon::parse($toMonth)->startOfMonth();
    if ($fromCarbon->gt($toCarbon)) {
      [$fromCarbon, $toCarbon] = [$toCarbon, $fromCarbon];
      $fromMonth = $fromCarbon->format('Y-m-01');
      $toMonth = $toCarbon->format('Y-m-01');
    }

    $billingMonthLabel = $fromCarbon->equalTo($toCarbon)
      ? $fromCarbon->format('F Y')
      : $fromCarbon->format('M Y') . ' - ' . $toCarbon->format('M Y');

    $salikMonthValues = [];
    $cursor = $fromCarbon->copy();
    while ($cursor->lte($toCarbon)) {
      $salikMonthValues[] = $cursor->format('Y-m-01');
      $salikMonthValues[] = $cursor->format('Y-m');
      $salikMonthValues[] = $cursor->format('M') . '-' . $cursor->format('y');
      $cursor->addMonth();
    }
    $salikMonthValues = array_values(array_unique($salikMonthValues));

    $result = Riders::with(['customer', 'bikes']);

    if ($request->rider_id && $request->rider_id !== '') {
      $result = $result->where('id', $request->rider_id);
    }
    if ($request->status && $request->status !== '') {
      $result = $result->where('status', $request->status);
    }
    if ($request->customer_id && $request->customer_id !== '') {
      $result = $result->where('customer_id', $request->customer_id);
    }
    if ($request->designation && $request->designation !== '') {
      $result = $result->where('designation', $request->designation);
    }
    if ($request->has('bike_assignment_status') && !empty($request->bike_assignment_status)) {
      if ($request->bike_assignment_status === 'Active') {
        $result = $result->whereHas('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      } elseif ($request->bike_assignment_status === 'Inactive') {
        $result = $result->whereDoesntHave('bikes', function ($q) {
          $q->where('warehouse', 'Active');
        });
      }
    }
    if ($request->has('wps_status') && !empty($request->wps_status)) {
      $result = $result->where('wps', $request->wps_status);
    }
    if ($quickSearch = trim((string) $request->quick_search)) {
      $result = $result->where(function ($query) use ($quickSearch) {
        $query->where('name', 'like', '%' . $quickSearch . '%')
          ->orWhere('rider_id', 'like', '%' . $quickSearch . '%')
          ->orWhere('emirate_hub', 'like', '%' . $quickSearch . '%')
          ->orWhere('designation', 'like', '%' . $quickSearch . '%');
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

    // Overall stats across all filtered riders (independent of pagination)
    $allRiderAccountMap = (clone $result)->pluck('account_id', 'id');
    $allRiderIds = $allRiderAccountMap->keys()->filter()->values()->all();
    $allAccountIds = $allRiderAccountMap->filter()->unique()->values()->all();
    $overallStats = $this->aggregateRiderReportStats(
      $allRiderIds,
      $allAccountIds,
      $fromMonth,
      $toMonth,
      $salikMonthValues
    );

    $result = $result->orderBy('rider_id')->forPage($page, $perPage)->get();

    $riderIds = $result->pluck('id')->filter()->values()->all();
    $accountIds = $result->pluck('account_id')->filter()->unique()->values()->all();

    $activeBikeRiders = [];
    if (!empty($riderIds)) {
      $activeBikeRiders = \App\Support\CompanyQuery::table('bikes')
        ->whereIn('rider_id', $riderIds)
        ->where('warehouse', 'Active')
        ->pluck('rider_id')
        ->toArray();
    }

    $invoiceTotals = collect();
    $voucherByRider = [];
    $rtaTotals = collect();
    $salikTotals = collect();
    $fuelTotals = collect();
    $visaTotals = collect();
    $jvByAccount = collect();
    $previousBalances = collect();
    $paidByAccount = collect();

    if (!empty($riderIds)) {
      $invoiceTotals = company_table('rider_invoices')
        ->whereIn('rider_id', $riderIds)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->select('rider_id', DB::raw('SUM(total_amount) as total'))
        ->groupBy('rider_id')
        ->pluck('total', 'rider_id');

      $voucherRows = company_table('vouchers')
        ->whereIn('ref_id', $riderIds)
        ->whereIn('voucher_type', ['VC', 'COD', 'AL', 'PN', 'INC'])
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->select('ref_id', 'voucher_type', DB::raw('SUM(amount) as total'))
        ->groupBy('ref_id', 'voucher_type')
        ->get();

      foreach ($voucherRows as $row) {
        $voucherByRider[$row->ref_id][$row->voucher_type] = (float) $row->total;
      }

      $rtaTotals = company_table('rta_fines')
        ->whereIn('rider_id', $riderIds)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->select('rider_id', DB::raw('SUM(total_amount) as total'))
        ->groupBy('rider_id')
        ->pluck('total', 'rider_id');

      $salikTotals = company_table('saliks')
        ->whereIn('rider_id', $riderIds)
        ->where(function ($q) use ($fromMonth, $toMonth, $salikMonthValues) {
          $q->where(function ($dateQ) use ($fromMonth, $toMonth) {
            $dateQ->whereDate('billing_month', '>=', $fromMonth)
              ->whereDate('billing_month', '<=', $toMonth);
          })->orWhereIn('billing_month', $salikMonthValues);
        })
        ->select('rider_id', DB::raw('SUM(total_amount) as total'))
        ->groupBy('rider_id')
        ->pluck('total', 'rider_id');

      $fuelTotals = company_table('fuel_data')
        ->whereIn('rider_id', $riderIds)
        ->whereNull('deleted_at')
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->select('rider_id', DB::raw('SUM(total) as total'))
        ->groupBy('rider_id')
        ->pluck('total', 'rider_id');

      $visaTotals = $this->visaInstallmentTotalsByRider(
        $riderIds,
        $accountIds,
        $fromMonth,
        $toMonth,
        $salikMonthValues
      );
    }

    if (!empty($accountIds)) {
      $previousBalances = Transactions::whereIn('account_id', $accountIds)
        ->whereDate('billing_month', '<', $fromMonth)
        ->select('account_id', DB::raw('SUM(debit - credit) as balance'))
        ->groupBy('account_id')
        ->pluck('balance', 'account_id');

      // Paid Amount from Payments module (PV vouchers), keyed by rider account
      $paidByAccount = \App\Models\Payment::whereIn('payee_account_id', $accountIds)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->select('payee_account_id', DB::raw('SUM(amount) as total'))
        ->groupBy('payee_account_id')
        ->pluck('total', 'payee_account_id');

      // JV entries posted against rider ledger accounts (imported JV + journal vouchers)
      $jvByAccount = Transactions::whereIn('account_id', $accountIds)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->where(function ($q) {
          $q->where('reference_type', 'JV')
            ->orWhere(function ($inner) {
              $inner->where('reference_type', 'Voucher')
                ->whereIn('reference_id', company_table('vouchers')
                  ->where('voucher_type', 'JV')
                  ->whereNull('deleted_at')
                  ->select('id'));
            });
        })
        ->select('account_id', DB::raw('SUM(debit - credit) as total'))
        ->groupBy('account_id')
        ->pluck('total', 'account_id');
    }

    $data = '';
    $sumTotalAmount = 0;
    $sumVc = 0;
    $sumCod = 0;
    $sumRta = 0;
    $sumSalik = 0;
    $sumFuel = 0;
    $sumJv = 0;
    $sumAdvance = 0;
    $sumPenalty = 0;
    $sumVisa = 0;
    $sumIncentive = 0;
    $sumPrevious = 0;
    $sumPayable = 0;
    $sumPaid = 0;
    $sumBalance = 0;

    foreach ($result as $rider) {
      $riderId = $rider->id;
      $vouchers = $voucherByRider[$riderId] ?? [];

      $totalAmount = (float) ($invoiceTotals[$riderId] ?? 0);
      $vc = (float) ($vouchers['VC'] ?? 0);
      $cod = (float) ($vouchers['COD'] ?? 0);
      $rta = (float) ($rtaTotals[$riderId] ?? 0);
      $salik = (float) ($salikTotals[$riderId] ?? 0);
      $fuel = (float) ($fuelTotals[$riderId] ?? 0);
      $visa = (float) ($visaTotals[$riderId] ?? 0);
      $jv = $rider->account_id
        ? (float) ($jvByAccount[$rider->account_id] ?? 0)
        : 0.0;
      $advance = (float) ($vouchers['AL'] ?? 0);
      $penalty = (float) ($vouchers['PN'] ?? 0);
      $incentive = (float) ($vouchers['INC'] ?? 0);
      $paid = $rider->account_id
        ? (float) ($paidByAccount[$rider->account_id] ?? 0)
        : 0.0;
      $previousBalance = $rider->account_id
        ? (float) ($previousBalances[$rider->account_id] ?? 0)
        : 0.0;

      $components = $this->riderReportPayableComponents(
        $totalAmount,
        $vc,
        $cod,
        $rta,
        $salik,
        $fuel,
        $advance,
        $penalty,
        $visa,
        $jv,
        $incentive,
        $previousBalance
      );
      $payable = $components['payable'];
      $balance = $payable - $paid;
      $pendingPct = abs($payable) > 0.00001 ? ($paid / $payable) * 100 : 0;

      $statusOptionLabel = !empty($rider->rider_status_option) ? $rider->rider_status_option : null;
      if (!$statusOptionLabel) {
        if (($rider->absconder ?? 0) == 1) {
          $statusOptionLabel = 'Absconder';
        } elseif (($rider->flowup ?? 0) == 1) {
          $statusOptionLabel = 'Follow Up';
        } elseif (($rider->l_license ?? 0) == 1) {
          $statusOptionLabel = 'Learning License';
        } elseif ($rider->designation === 'Walker') {
          $statusOptionLabel = 'Walker';
        } elseif ($rider->designation === 'Vacation') {
          $statusOptionLabel = 'Vacation';
        } elseif ($rider->designation === 'Cancel') {
          $statusOptionLabel = 'Cancel';
        } elseif ($rider->designation === 'PRO' || ($rider->pro ?? 0) == 1) {
          $statusOptionLabel = 'PRO';
        }
      }
      if ($statusOptionLabel) {
        $statusText = $statusOptionLabel;
        $badgeClass = 'bg-label-primary';
      } else {
        $isActive = in_array($rider->id, $activeBikeRiders);
        $statusText = $isActive ? 'Active' : 'Inactive';
        $badgeClass = $isActive ? 'bg-label-success' : 'bg-label-danger';
      }

      $fmt = static fn ($n) => number_format((float) $n, 2);

      $detailUrl = route('reports.rider_report_detail', [
        'rider' => $rider->id,
        'from_month' => $fromCarbon->format('Y-m'),
        'to_month' => $toCarbon->format('Y-m'),
      ]);

      $data .= '<tr>';
      $data .= '<td>' . e($rider->rider_id) . '</td>';
      $data .= '<td><a target="_blank" href="' . route('riders.show', $rider->id) . '">' . e($rider->name) . '</a></td>';
      $data .= '<td><span class="badge ' . $badgeClass . '">' . e($statusText) . '</span></td>';
      $data .= '<td>' . e($rider->emirate_hub) . '</td>';
      $data .= '<td>' . e($rider->designation) . '</td>';
      $data .= '<td>' . e(optional($rider->customer)->name) . '</td>';
      $data .= '<td><a target="_blank" href="' . e($detailUrl) . '" title="View rider report details">' . e($billingMonthLabel) . '</a></td>';
      $data .= '<td align="center">' . $fmt($totalAmount) . '</td>';
      $data .= '<td align="center">' . $fmt($vc) . '</td>';
      $data .= '<td align="center">' . $fmt($cod) . '</td>';
      $data .= '<td align="center">' . $fmt($rta) . '</td>';
      $data .= '<td align="center">' . $fmt($salik) . '</td>';
      $data .= '<td align="center">' . $fmt($fuel) . '</td>';
      $data .= '<td align="center">' . $fmt($visa) . '</td>';
      $data .= '<td align="center">' . $fmt($jv) . '</td>';
      $data .= '<td align="center">' . $fmt($advance) . '</td>';
      $data .= '<td align="center">' . $fmt($penalty) . '</td>';
      $data .= '<td align="center">' . $fmt($incentive) . '</td>';
      $data .= '<td align="center">' . $fmt($previousBalance) . '</td>';
      $data .= '<td align="center">' . $fmt($payable) . '</td>';
      $data .= '<td align="center">' . $fmt($paid) . '</td>';
      $data .= '<td align="center">' . $fmt($balance) . '</td>';
      $data .= '<td align="center">' . number_format($pendingPct, 2) . '%</td>';
      $data .= '</tr>';

      $sumTotalAmount += $totalAmount;
      $sumVc += $vc;
      $sumCod += $cod;
      $sumRta += $rta;
      $sumSalik += $salik;
      $sumFuel += $fuel;
      $sumVisa += $visa;
      $sumJv += $jv;
      $sumAdvance += $advance;
      $sumPenalty += $penalty;
      $sumIncentive += $incentive;
      $sumPrevious += $previousBalance;
      $sumPayable += $payable;
      $sumPaid += $paid;
      $sumBalance += $balance;
    }

    $sumPendingPct = abs($sumPayable) > 0.00001 ? ($sumPaid / $sumPayable) * 100 : 0;

    if ($result->count() > 0) {
      $fmt = static fn ($n) => number_format((float) $n, 2);
      $data .= '<tr class="font-weight-bold total-row">';
      $data .= '<td colspan="7" style="text-align:center;font-weight:700;color:#000;">Totals</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumTotalAmount) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumVc) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumCod) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumRta) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumSalik) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumFuel) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumVisa) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumJv) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumAdvance) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumPenalty) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumIncentive) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumPrevious) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumPayable) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumPaid) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . $fmt($sumBalance) . '</td>';
      $data .= '<td style="text-align:center;font-weight:700;color:#000;">' . number_format($sumPendingPct, 2) . '%</td>';
      $data .= '</tr>';
    }

    $paginationLinks = view('components.global-pagination', [
      'paginator' => new \Illuminate\Pagination\LengthAwarePaginator([], $totalCount, $perPage, $page, ['path' => url()->current()]),
      'perPageOptions' => [20, 50, 100, -1]
    ])->render();

    return [
      'data' => $data,
      'riders_count' => $totalCount,
      'total_amount' => $overallStats['total_amount'],
      'total_additions' => $overallStats['total_additions'],
      'total_deductions' => $overallStats['total_deductions'],
      'total_payable' => $overallStats['total_payable'],
      'total_paid' => $overallStats['total_paid'],
      'total_balance' => $overallStats['total_balance'],
      'paginationLinks' => $paginationLinks,
      'totalCount' => $totalCount,
      'perPage' => $perPage,
      'page' => $page,
    ];
  }

  /**
   * Aggregate rider-report totals for the given rider/account set and billing period.
   *
   * @param  array<int, int|string>  $riderIds
   * @param  array<int, int|string>  $accountIds
   * @param  array<int, string>  $salikMonthValues
   * @return array{total_amount: float, total_additions: float, total_deductions: float, total_payable: float, total_paid: float, total_balance: float}
   */
  private function aggregateRiderReportStats(array $riderIds, array $accountIds, string $fromMonth, string $toMonth, array $salikMonthValues): array
  {
    $zeros = [
      'total_amount' => 0.0,
      'total_additions' => 0.0,
      'total_deductions' => 0.0,
      'total_payable' => 0.0,
      'total_paid' => 0.0,
      'total_balance' => 0.0,
    ];

    if (empty($riderIds)) {
      return $zeros;
    }

    $totalAmount = (float) company_table('rider_invoices')
      ->whereIn('rider_id', $riderIds)
      ->whereDate('billing_month', '>=', $fromMonth)
      ->whereDate('billing_month', '<=', $toMonth)
      ->sum('total_amount');

    $voucherSums = company_table('vouchers')
      ->whereIn('ref_id', $riderIds)
      ->whereIn('voucher_type', ['VC', 'COD', 'AL', 'PN', 'INC'])
      ->whereDate('billing_month', '>=', $fromMonth)
      ->whereDate('billing_month', '<=', $toMonth)
      ->select('voucher_type', DB::raw('SUM(amount) as total'))
      ->groupBy('voucher_type')
      ->pluck('total', 'voucher_type');

    $vc = (float) ($voucherSums['VC'] ?? 0);
    $cod = (float) ($voucherSums['COD'] ?? 0);
    $advance = (float) ($voucherSums['AL'] ?? 0);
    $penalty = (float) ($voucherSums['PN'] ?? 0);
    $incentive = (float) ($voucherSums['INC'] ?? 0);

    $rta = (float) company_table('rta_fines')
      ->whereIn('rider_id', $riderIds)
      ->whereDate('billing_month', '>=', $fromMonth)
      ->whereDate('billing_month', '<=', $toMonth)
      ->sum('total_amount');

    $salik = (float) company_table('saliks')
      ->whereIn('rider_id', $riderIds)
      ->where(function ($q) use ($fromMonth, $toMonth, $salikMonthValues) {
        $q->where(function ($dateQ) use ($fromMonth, $toMonth) {
          $dateQ->whereDate('billing_month', '>=', $fromMonth)
            ->whereDate('billing_month', '<=', $toMonth);
        })->orWhereIn('billing_month', $salikMonthValues);
      })
      ->sum('total_amount');

    $fuel = (float) company_table('fuel_data')
      ->whereIn('rider_id', $riderIds)
      ->whereNull('deleted_at')
      ->whereDate('billing_month', '>=', $fromMonth)
      ->whereDate('billing_month', '<=', $toMonth)
      ->sum('total');

    $visa = (float) $this->visaInstallmentTotalsByRider(
      $riderIds,
      $accountIds,
      $fromMonth,
      $toMonth,
      $salikMonthValues
    )->sum();

    $jv = 0.0;
    $previous = 0.0;
    $paid = 0.0;

    if (!empty($accountIds)) {
      $previous = (float) (Transactions::whereIn('account_id', $accountIds)
        ->whereDate('billing_month', '<', $fromMonth)
        ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
        ->value('balance') ?? 0);

      $paid = (float) (\App\Models\Payment::whereIn('payee_account_id', $accountIds)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->sum('amount'));

      $jv = (float) (Transactions::whereIn('account_id', $accountIds)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->where(function ($q) {
          $q->where('reference_type', 'JV')
            ->orWhere(function ($inner) {
              $inner->where('reference_type', 'Voucher')
                ->whereIn('reference_id', company_table('vouchers')
                  ->where('voucher_type', 'JV')
                  ->whereNull('deleted_at')
                  ->select('id'));
            });
        })
        ->selectRaw('COALESCE(SUM(debit - credit), 0) as total')
        ->value('total') ?? 0);
    }

    $components = $this->riderReportPayableComponents(
      $totalAmount,
      $vc,
      $cod,
      $rta,
      $salik,
      $fuel,
      $advance,
      $penalty,
      $visa,
      $jv,
      $incentive,
      $previous
    );

    return [
      'total_amount' => $totalAmount,
      'total_additions' => $components['additions'],
      'total_deductions' => $components['deductions'],
      'total_payable' => $components['payable'],
      'total_paid' => $paid,
      'total_balance' => $components['payable'] - $paid,
    ];
  }

  /**
   * Payable = invoice total + additions - deductions.
   * Additions: incentive + previous balance + JV credits (net credit).
   * Deductions: VC, COD, RTA, Salik, Fuel, Advance, Penalty, Visa installment + JV debits (net debit).
   *
   * @return array{additions: float, deductions: float, payable: float, jv_deduction: float, jv_addition: float}
   */
  private function riderReportPayableComponents(
    float $totalAmount,
    float $vc,
    float $cod,
    float $rta,
    float $salik,
    float $fuel,
    float $advance,
    float $penalty,
    float $visa,
    float $jvNet,
    float $incentive,
    float $previousBalance
  ): array {
    $jvDeduction = $jvNet > 0 ? $jvNet : 0.0;
    $jvAddition = $jvNet < 0 ? abs($jvNet) : 0.0;

    $deductions = $vc + $cod + $rta + $salik + $fuel + $advance + $penalty + $visa + $jvDeduction;
    $additions = $incentive + $previousBalance + $jvAddition;

    return [
      'additions' => $additions,
      'deductions' => $deductions,
      'payable' => $totalAmount + $additions - $deductions,
      'jv_deduction' => $jvDeduction,
      'jv_addition' => $jvAddition,
    ];
  }

  /**
   * visa_installment_plans.rider_id may be riders.id, accounts.id, or expense_accounts.id.
   *
   * @param  array<int, int|string>  $riderIds
   * @param  array<int, int|string>  $accountIds
   * @param  array<int, string>  $monthValues
   * @return \Illuminate\Support\Collection<int|string, float>
   */
  private function visaInstallmentTotalsByRider(
    array $riderIds,
    array $accountIds,
    string $fromMonth,
    string $toMonth,
    array $monthValues
  ) {
    if (empty($riderIds)) {
      return collect();
    }

    $riderIdInts = array_map('intval', $riderIds);
    $keyToRiderId = [];
    foreach ($riderIdInts as $riderId) {
      $keyToRiderId[$riderId] = $riderId;
    }

    if (!empty($accountIds)) {
      $accounts = \App\Models\Accounts::whereIn('id', array_filter($accountIds))
        ->get(['id', 'ref_id']);
      foreach ($accounts as $account) {
        $refId = (int) $account->ref_id;
        if ($refId && in_array($refId, $riderIdInts, true)) {
          $keyToRiderId[(int) $account->id] = $refId;
        }
      }
    }

    $expenseRows = company_table('expense_accounts')
      ->whereIn('rider_id', $riderIds)
      ->get(['id', 'rider_id', 'account_id']);

    foreach ($expenseRows as $row) {
      $mappedRider = (int) $row->rider_id;
      $keyToRiderId[(int) $row->id] = $mappedRider;
      if (!empty($row->account_id)) {
        $keyToRiderId[(int) $row->account_id] = $mappedRider;
      }
    }

    $keys = array_values(array_unique(array_filter(array_keys($keyToRiderId))));
    if ($keys === []) {
      return collect();
    }

    $rows = company_table('visa_installment_plans')
      ->whereIn('rider_id', $keys)
      ->whereNull('deleted_at')
      ->where(function ($q) use ($fromMonth, $toMonth, $monthValues) {
        $q->where(function ($dateQ) use ($fromMonth, $toMonth) {
          $dateQ->whereDate('billing_month', '>=', $fromMonth)
            ->whereDate('billing_month', '<=', $toMonth);
        })->orWhereIn('billing_month', $monthValues);
      })
      ->select('rider_id', DB::raw('SUM(amount) as total'))
      ->groupBy('rider_id')
      ->get();

    $totals = [];
    foreach ($rows as $row) {
      $mapped = $keyToRiderId[(int) $row->rider_id] ?? null;
      if (!$mapped) {
        continue;
      }
      $totals[$mapped] = ($totals[$mapped] ?? 0.0) + (float) $row->total;
    }

    return collect($totals);
  }

  public function rider_report_detail(Request $request, $company_slug, $rider)
  {
    $riderModel = Riders::with(['customer', 'bikes', 'vendor', 'sim'])->findOrFail($rider);

    $normalizeMonth = static function ($value) {
      $value = trim((string) $value);
      if ($value === '') {
        return Carbon::now()->startOfMonth()->format('Y-m-01');
      }
      if (preg_match('/^\d{4}-\d{2}-01$/', $value)) {
        return $value;
      }
      if (preg_match('/^\d{4}-\d{2}$/', $value)) {
        return $value . '-01';
      }

      return Carbon::parse($value)->startOfMonth()->format('Y-m-01');
    };

    $fromMonth = $normalizeMonth($request->input('from_month', $request->input('billing_month')));
    $toMonth = $normalizeMonth($request->input('to_month', $request->input('billing_month', $fromMonth)));

    $fromCarbon = Carbon::parse($fromMonth)->startOfMonth();
    $toCarbon = Carbon::parse($toMonth)->startOfMonth();
    if ($fromCarbon->gt($toCarbon)) {
      [$fromCarbon, $toCarbon] = [$toCarbon, $fromCarbon];
      $fromMonth = $fromCarbon->format('Y-m-01');
      $toMonth = $toCarbon->format('Y-m-01');
    }

    $periodLabel = $fromCarbon->equalTo($toCarbon)
      ? $fromCarbon->format('F Y')
      : $fromCarbon->format('M Y') . ' - ' . $toCarbon->format('M Y');

    $salikMonthValues = [];
    $cursor = $fromCarbon->copy();
    while ($cursor->lte($toCarbon)) {
      $salikMonthValues[] = $cursor->format('Y-m-01');
      $salikMonthValues[] = $cursor->format('Y-m');
      $salikMonthValues[] = $cursor->format('M') . '-' . $cursor->format('y');
      $cursor->addMonth();
    }
    $salikMonthValues = array_values(array_unique($salikMonthValues));

    $invoices = company_table('rider_invoices')
      ->where('rider_id', $riderModel->id)
      ->whereDate('billing_month', '>=', $fromMonth)
      ->whereDate('billing_month', '<=', $toMonth)
      ->orderBy('billing_month')
      ->orderBy('id')
      ->get();

    $voucherTypes = ['VC', 'COD', 'AL', 'PN', 'INC'];
    $vouchersByType = [];
    foreach ($voucherTypes as $type) {
      $vouchersByType[$type] = company_table('vouchers')
        ->where('ref_id', $riderModel->id)
        ->where('voucher_type', $type)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->orderBy('billing_month')
        ->orderBy('id')
        ->get();
    }

    $rtaFines = company_table('rta_fines')
      ->where('rider_id', $riderModel->id)
      ->whereDate('billing_month', '>=', $fromMonth)
      ->whereDate('billing_month', '<=', $toMonth)
      ->orderBy('billing_month')
      ->orderBy('id')
      ->get();

    $saliks = company_table('saliks')
      ->where('rider_id', $riderModel->id)
      ->where(function ($q) use ($fromMonth, $toMonth, $salikMonthValues) {
        $q->where(function ($dateQ) use ($fromMonth, $toMonth) {
          $dateQ->whereDate('billing_month', '>=', $fromMonth)
            ->whereDate('billing_month', '<=', $toMonth);
        })->orWhereIn('billing_month', $salikMonthValues);
      })
      ->orderBy('trip_date')
      ->orderBy('id')
      ->get();

    $fuelInvoices = company_table('fuel_data')
      ->where('rider_id', $riderModel->id)
      ->whereNull('deleted_at')
      ->whereDate('billing_month', '>=', $fromMonth)
      ->whereDate('billing_month', '<=', $toMonth)
      ->select(
        'inv_id',
        'billing_month',
        DB::raw('MIN(trans_date) as trans_date'),
        DB::raw('COUNT(*) as line_count'),
        DB::raw('SUM(qty) as total_qty'),
        DB::raw('SUM(total) as total_amount')
      )
      ->groupBy('inv_id', 'billing_month')
      ->orderBy('billing_month')
      ->orderBy('inv_id')
      ->get();

    $visaInstallmentKeys = [(int) $riderModel->id];
    if ($riderModel->account_id) {
      $visaInstallmentKeys[] = (int) $riderModel->account_id;
    }
    $expenseRows = company_table('expense_accounts')
      ->where('rider_id', $riderModel->id)
      ->get(['id', 'account_id']);
    foreach ($expenseRows as $row) {
      $visaInstallmentKeys[] = (int) $row->id;
      if (!empty($row->account_id)) {
        $visaInstallmentKeys[] = (int) $row->account_id;
      }
    }
    $visaInstallmentKeys = array_values(array_unique(array_filter($visaInstallmentKeys)));

    $visaInstallments = company_table('visa_installment_plans')
      ->whereIn('rider_id', $visaInstallmentKeys)
      ->whereNull('deleted_at')
      ->where(function ($q) use ($fromMonth, $toMonth, $salikMonthValues) {
        $q->where(function ($dateQ) use ($fromMonth, $toMonth) {
          $dateQ->whereDate('billing_month', '>=', $fromMonth)
            ->whereDate('billing_month', '<=', $toMonth);
        })->orWhereIn('billing_month', $salikMonthValues);
      })
      ->orderBy('billing_month')
      ->orderBy('id')
      ->get();

    $jvEntries = collect();
    $payments = collect();
    if ($riderModel->account_id) {
      $jvEntries = Transactions::where('account_id', $riderModel->account_id)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->where(function ($q) {
          $q->where('reference_type', 'JV')
            ->orWhere(function ($inner) {
              $inner->where('reference_type', 'Voucher')
                ->whereIn('reference_id', company_table('vouchers')
                  ->where('voucher_type', 'JV')
                  ->whereNull('deleted_at')
                  ->select('id'));
            });
        })
        ->orderBy('billing_month')
        ->orderBy('trans_date')
        ->orderBy('id')
        ->get();

      $payments = \App\Models\Payment::where('payee_account_id', $riderModel->account_id)
        ->whereDate('billing_month', '>=', $fromMonth)
        ->whereDate('billing_month', '<=', $toMonth)
        ->orderBy('billing_month')
        ->orderBy('id')
        ->get();
    }

    $previousBalance = 0.0;
    if ($riderModel->account_id) {
      $previousBalance = (float) Transactions::where('account_id', $riderModel->account_id)
        ->whereDate('billing_month', '<', $fromMonth)
        ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
        ->value('balance');
    }

    $jvNet = (float) $jvEntries->sum(fn ($row) => (float) $row->debit - (float) $row->credit);
    $components = $this->riderReportPayableComponents(
      (float) $invoices->sum('total_amount'),
      (float) $vouchersByType['VC']->sum('amount'),
      (float) $vouchersByType['COD']->sum('amount'),
      (float) $rtaFines->sum('total_amount'),
      (float) $saliks->sum('total_amount'),
      (float) $fuelInvoices->sum('total_amount'),
      (float) $vouchersByType['AL']->sum('amount'),
      (float) $vouchersByType['PN']->sum('amount'),
      (float) $visaInstallments->sum('amount'),
      $jvNet,
      (float) $vouchersByType['INC']->sum('amount'),
      $previousBalance
    );

    $totals = [
      'invoices' => (float) $invoices->sum('total_amount'),
      'vc' => (float) $vouchersByType['VC']->sum('amount'),
      'cod' => (float) $vouchersByType['COD']->sum('amount'),
      'rta' => (float) $rtaFines->sum('total_amount'),
      'salik' => (float) $saliks->sum('total_amount'),
      'fuel' => (float) $fuelInvoices->sum('total_amount'),
      'visa' => (float) $visaInstallments->sum('amount'),
      'jv' => $jvNet,
      'jv_deduction' => $components['jv_deduction'],
      'jv_addition' => $components['jv_addition'],
      'advance' => (float) $vouchersByType['AL']->sum('amount'),
      'penalty' => (float) $vouchersByType['PN']->sum('amount'),
      'incentive' => (float) $vouchersByType['INC']->sum('amount'),
      'paid' => (float) $payments->sum('amount'),
      'previous_balance' => $previousBalance,
      'deductions' => $components['deductions'],
      'additions' => $components['additions'],
      'payable' => $components['payable'],
    ];

    $totals['balance'] = $totals['payable'] - $totals['paid'];
    $totals['pending_pct'] = abs($totals['payable']) > 0.00001
      ? ($totals['paid'] / $totals['payable']) * 100
      : 0;

    return view('reports.rider_report_detail', [
      'rider' => $riderModel,
      'periodLabel' => $periodLabel,
      'fromMonth' => $fromCarbon->format('Y-m'),
      'toMonth' => $toCarbon->format('Y-m'),
      'invoices' => $invoices,
      'vouchersByType' => $vouchersByType,
      'rtaFines' => $rtaFines,
      'saliks' => $saliks,
      'fuelInvoices' => $fuelInvoices,
      'visaInstallments' => $visaInstallments,
      'jvEntries' => $jvEntries,
      'payments' => $payments,
      'totals' => $totals,
      'settings' => company_table('settings')->pluck('value', 'name')->toArray(),
      'brand' => app(\App\Services\Agreements\AgreementPdfBranding::class)
        ->forCompany(\App\Support\CompanyContext::id()),
    ]);
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
    // Filter by WPS status
    if ($request->has('wps_status') && !empty($request->wps_status)) {
      $result = $result->where('wps', $request->wps_status);
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
      $activeBikeRiders = \App\Support\CompanyQuery::table('bikes')
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
      $data .= '<td><a target="_blank" href="' . route('riders.show', $rider->id) . '">' . e($rider->name) . '</a></td>';
      $data .= '<td>' . e(optional($rider->vendor)->name) . '</td>';
      $data .= '<td>' . e($rider->designation) . '</td>';
      $data .= '<td style="mso-number-format:\'\@\';">' . e($rider->person_code) . '</td>';
      $data .= '<td style="mso-number-format:\'\@\';">' . e($rider->labor_card_number) . '</td>';
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

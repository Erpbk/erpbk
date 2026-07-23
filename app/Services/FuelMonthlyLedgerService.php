<?php

namespace App\Services;

use App\Helpers\Account;
use App\Models\FuelData;
use App\Models\Riders;
use App\Models\Transactions;
use App\Support\GlobalAccounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Posts one set of ledger entries per rider + billing month from fuel_data totals.
 * Individual fuel_data rows remain for invoice line items; ledger uses monthly aggregates.
 */
class FuelMonthlyLedgerService
{
    public const DEFAULT_SERVICE_CHARGE = 25.0;

    /**
     * Delete any existing fuel ledger for the rider/month, then post totals for current rows.
     */
    public function sync(int $riderId, $billingMonth, ?float $serviceChargeAmount = self::DEFAULT_SERVICE_CHARGE): void
    {
        $billingMonthDate = Carbon::parse($billingMonth)->startOfMonth()->toDateString();
        $rider = Riders::find($riderId);
        if (! $rider || ! $rider->account_id) {
            Log::warning("FuelMonthlyLedgerService: rider {$riderId} missing or has no account");

            return;
        }

        $activeRows = FuelData::with(['card.fuelCompany'])
            ->where('rider_id', $riderId)
            ->whereDate('billing_month', $billingMonthDate)
            ->orderBy('id')
            ->get();

        $trashedIds = FuelData::onlyTrashed()
            ->where('rider_id', $riderId)
            ->whereDate('billing_month', $billingMonthDate)
            ->pluck('id')
            ->all();

        $referenceIds = array_values(array_unique(array_merge(
            $activeRows->pluck('id')->all(),
            $trashedIds
        )));

        if ($referenceIds !== []) {
            Transactions::where('reference_type', 'fuel')
                ->whereIn('reference_id', $referenceIds)
                ->delete();
        }

        // Safety: clear any rider-side fuel ledger left for this billing month.
        Transactions::where('reference_type', 'fuel')
            ->where('account_id', $rider->account_id)
            ->whereDate('billing_month', $billingMonthDate)
            ->delete();

        if ($activeRows->isEmpty()) {
            return;
        }

        $this->postMonthlyTotals($rider, $activeRows, $billingMonthDate, $serviceChargeAmount);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FuelData>  $activeRows
     */
    private function postMonthlyTotals(Riders $rider, $activeRows, string $billingMonthDate, ?float $serviceChargeAmount): void
    {
        $anchor = $activeRows->first();
        $referenceId = (int) $anchor->id;
        $transCode = Account::trans_code();
        $transDate = Carbon::parse($activeRows->max('trans_date'))->format('Y-m-d');
        $branchId = $rider->branch_id;
        $riderName = $rider->name ?? ('Rider #' . $rider->id);
        $monthLabel = Carbon::parse($billingMonthDate)->format('M Y');

        $totalAmount = (float) $activeRows->sum('total');
        $totalVat = (float) $activeRows->sum('vat_amount');
        $txCount = $activeRows->count();
        $serviceCharge = (float) ($serviceChargeAmount ?? self::DEFAULT_SERVICE_CHARGE);
        $riderDebit = $totalAmount + max(0, $serviceCharge);

        // One combined debit on the rider ledger (fuel total + service charge).
        if ($riderDebit > 0) {
            $narration = "Fuel purchased ({$txCount} txn)";
            if ($serviceCharge > 0) {
                $narration .= ' + service charges';
            }
            $narration .= " — {$riderName} — {$monthLabel}";

            Transactions::create([
                'account_id' => $rider->account_id,
                'reference_id' => $referenceId,
                'reference_type' => 'fuel',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => $riderDebit,
                'credit' => 0,
                'billing_month' => $billingMonthDate,
                'branch_id' => $branchId,
            ]);
        }

        // Credit each fuel company for its share of the month.
        $byCompanyAccount = $activeRows->groupBy(function (FuelData $row) {
            return $row->card?->fuelCompany?->account_id;
        });

        foreach ($byCompanyAccount as $companyAccountId => $rows) {
            if (empty($companyAccountId)) {
                Log::warning("FuelMonthlyLedgerService: missing fuel company account for rider {$rider->id} month {$billingMonthDate}");

                continue;
            }
            $companyTotal = (float) $rows->sum('total');
            if ($companyTotal <= 0) {
                continue;
            }
            Transactions::create([
                'account_id' => (int) $companyAccountId,
                'reference_id' => $referenceId,
                'reference_type' => 'fuel',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => "Fuel purchased by {$riderName} — {$monthLabel}",
                'debit' => 0,
                'credit' => $companyTotal,
                'billing_month' => $billingMonthDate,
                'branch_id' => $branchId,
            ]);
        }

        // Credit service-charge income account separately.
        if ($serviceCharge > 0) {
            Transactions::create([
                'account_id' => GlobalAccounts::id('FUEL_ADMIN_CHARGES'),
                'reference_id' => $referenceId,
                'reference_type' => 'fuel',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => 'Monthly service charges for fuel transactions',
                'debit' => 0,
                'credit' => $serviceCharge,
                'billing_month' => $billingMonthDate,
                'branch_id' => $branchId,
            ]);
        }

        if ($totalVat > 0) {
            Transactions::create([
                'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                'reference_id' => $referenceId,
                'reference_type' => 'fuel',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => "VAT on Fuel purchased — {$riderName} — {$monthLabel}",
                'debit' => $totalVat,
                'credit' => 0,
                'billing_month' => $billingMonthDate,
                'branch_id' => $branchId,
            ]);
        }
    }
}

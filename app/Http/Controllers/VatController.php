<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Models\Accounts;
use App\Models\BikeMaintenance;
use App\Models\Settings;
use App\Models\Transactions;
use App\Models\VatReturn;
use App\Models\VatReturnEntry;
use App\Models\Vouchers;
use App\Models\VoucherType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VatController extends Controller
{
    /** Default debit account for VAT Payment voucher (VP). */
    public const VAT_PAYMENT_DEFAULT_DEBIT_ACCOUNT = 1027;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * VAT Ledger: combined entries of VAT accounts (1023, 1025) in a simple table. Sequence by billing_month.
     */
    public function index(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $accountIds = VatSettingsController::getVatAccountIds();
        $purchaseAccountId = (int) ($accountIds[0] ?? 0);
        $salesAccountId = (int) ($accountIds[1] ?? $accountIds[0] ?? 0);
        $quarters = VatSettingsController::getQuartersForDropdown();
        $years = $this->getYearsForDropdown();
        $rows = [];
        $filterMonths = [];
        $filterModules = [];
        $stats = [
            'output' => 0.0,
            'input' => 0.0,
            'net' => 0.0,
            'output_count' => 0,
            'input_count' => 0,
            'shown_count' => 0,
            'total_count' => 0,
            'total_entries' => 0,
            'filed_entries' => 0,
            'unfiled_entries' => 0,
            'total_debit' => 0.0,
            'total_credit' => 0.0,
        ];

        // Default load: current calendar year. Explicit vat_year in the query (even empty = All years)
        // must use exists(), because has() treats "" as missing and would re-apply the default.
        $yearProvided = $request->exists('vat_year');
        $year = $yearProvided ? (string) $request->input('vat_year', '') : (string) date('Y');
        $quarterSlot = $request->input('vat_quarter_slot');

        if (!empty($accountIds)) {
            // Year (+ optional quarter) universe for entry stats and the unfiled ledger base.
            $scopedAllQuery = Transactions::query()->whereIn('account_id', $accountIds);
            if ($year !== null && $year !== '') {
                $yearInt = (int) $year;
                $scopedAllQuery->where('billing_month', '>=', sprintf('%04d-01-01', $yearInt))
                    ->where('billing_month', '<', sprintf('%04d-01-01', $yearInt + 1));
                // Same quarter window as the ledger (selected quarter + earlier quarters in the year).
                if ($quarterSlot) {
                    $months = $this->getMonthsThroughQuarter((int) $quarterSlot);
                    if (!empty($months)) {
                        $this->applyBillingMonthMonthsFilter($scopedAllQuery, $yearInt, $months);
                    }
                }
            }

            $stats['total_entries'] = (clone $scopedAllQuery)->count();
            $stats['filed_entries'] = (clone $scopedAllQuery)
                ->whereIn('id', VatReturnEntry::query()->select('transaction_id'))
                ->count();
            $stats['unfiled_entries'] = max(0, $stats['total_entries'] - $stats['filed_entries']);

            // Constraints only (no eager load) so filter option queries stay lightweight.
            $baseQuery = (clone $scopedAllQuery)
                ->whereNotIn('id', VatReturnEntry::query()->select('transaction_id'));

            $stats['total_count'] = (clone $baseQuery)->count();
            $filterMonths = (clone $baseQuery)
                ->whereNotNull('billing_month')
                ->distinct()
                ->orderBy('billing_month')
                ->pluck('billing_month')
                ->filter()
                ->map(fn ($m) => date('Y-m', strtotime((string) $m)))
                ->unique()
                ->sort()
                ->values()
                ->all();
            $filterModules = (clone $baseQuery)
                ->whereNotNull('reference_type')
                ->distinct()
                ->pluck('reference_type')
                ->map(fn ($type) => $this->getModuleLabel((object) ['reference_type' => $type]))
                ->unique()
                ->sort()
                ->values()
                ->all();

            $query = (clone $baseQuery)->with(['account', 'voucher']);

            $vatType = $request->input('vat_type');
            if ($vatType === 's' && $salesAccountId) {
                $query->where('account_id', $salesAccountId);
            } elseif ($vatType === 'p' && $purchaseAccountId) {
                $query->where('account_id', $purchaseAccountId);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('trans_date', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('trans_date', '<=', $request->input('date_to'));
            }
            if ($request->filled('billing_month')) {
                $bm = (string) $request->input('billing_month');
                if (preg_match('/^\d{4}-\d{2}$/', $bm)) {
                    $start = $bm . '-01';
                    $end = date('Y-m-d', strtotime($start . ' +1 month'));
                    $query->where('billing_month', '>=', $start)->where('billing_month', '<', $end);
                }
            }
            if ($request->filled('module')) {
                $moduleTypes = $this->referenceTypesForModule((string) $request->input('module'));
                if (!empty($moduleTypes)) {
                    $query->whereIn('reference_type', $moduleTypes);
                }
            }
            if ($request->filled('quick_search')) {
                $q = '%' . trim((string) $request->input('quick_search')) . '%';
                $query->where(function ($w) use ($q) {
                    $w->where('narration', 'like', $q)
                        ->orWhereHas('voucher', function ($vq) use ($q) {
                            $vq->where('reference_number', 'like', $q)
                                ->orWhereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) like ?", [$q]);
                        })
                        ->orWhere('reference_type', 'like', $q);
                });
            }

            $query->orderBy('billing_month', 'ASC')->orderBy('trans_date', 'ASC');
            $transactions = $query->get();
            $enrichment = $this->buildLedgerEnrichmentMaps($transactions);
            $openingBalance = $this->getOpeningBalance($accountIds, $quarterSlot ?: null, $year ?: null);
            $runningBalance = $openingBalance;
            $totalDebit = 0;
            $totalCredit = 0;
            $output = 0.0;
            $input = 0.0;
            $outputCount = 0;
            $inputCount = 0;

            $rows[] = [
                'transaction_id' => null,
                'date' => '',
                'date_iso' => '',
                'account_name' => '',
                'account_code' => '',
                'is_output' => false,
                'reference_number' => '',
                'billing_month' => '',
                'billing_month_key' => '',
                'voucher' => '',
                'module' => '',
                'narration' => 'Balance Forward',
                'debit' => '',
                'credit' => '',
                'debit_raw' => 0,
                'credit_raw' => 0,
                'balance' => $this->formatBalance($openingBalance),
                'is_total' => false,
                'is_balance_forward' => true,
            ];

            foreach ($transactions as $row) {
                $debit = (float) $row->debit;
                $credit = (float) $row->credit;
                $runningBalance += $debit - $credit;
                $totalDebit += $debit;
                $totalCredit += $credit;

                $isOutput = (int) $row->account_id === $salesAccountId;
                if ($isOutput) {
                    $output += $credit - $debit;
                    $outputCount++;
                } else {
                    $input += $debit - $credit;
                    $inputCount++;
                }

                $viewFile = $this->getViewFile($row);
                $module = $this->getModuleLabel($row);
                $rows[] = [
                    'transaction_id' => $row->id,
                    'date' => Common::DateFormat($row->trans_date),
                    'date_iso' => $row->trans_date ? date('Y-m-d', strtotime($row->trans_date)) : '',
                    'account_name' => $row->account ? ($row->account->account_code . '-' . $row->account->name) : 'N/A',
                    'account_code' => $row->account->account_code ?? '',
                    'is_output' => $isOutput,
                    'reference_number' => $row->voucher ? ($row->voucher->reference_number ?? '-') : '-',
                    'billing_month' => $row->billing_month ? date('M Y', strtotime($row->billing_month)) : '',
                    'billing_month_key' => $row->billing_month ? date('Y-m', strtotime($row->billing_month)) : '',
                    'voucher' => $this->getVoucherText($row, $enrichment),
                    'module' => $module,
                    'narration' => $this->getNarration($row, $viewFile, $enrichment),
                    'debit' => number_format($debit, 2),
                    'credit' => number_format($credit, 2),
                    'debit_raw' => $debit,
                    'credit_raw' => $credit,
                    'balance' => $this->formatBalance($runningBalance),
                    'is_total' => false,
                    'is_balance_forward' => false,
                ];
            }

            $rows[] = [
                'transaction_id' => null,
                'date' => '',
                'date_iso' => '',
                'account_name' => '',
                'account_code' => '',
                'is_output' => false,
                'reference_number' => '',
                'billing_month' => '',
                'billing_month_key' => '',
                'voucher' => '',
                'module' => '',
                'narration' => 'Total',
                'debit' => number_format($totalDebit, 2),
                'credit' => number_format($totalCredit, 2),
                'debit_raw' => $totalDebit,
                'credit_raw' => $totalCredit,
                'balance' => $this->formatBalance($runningBalance),
                'is_total' => true,
                'is_balance_forward' => false,
            ];

            $stats = [
                'output' => $output,
                'input' => $input,
                'net' => $output - $input,
                'output_count' => $outputCount,
                'input_count' => $inputCount,
                'shown_count' => $transactions->count(),
                'total_count' => $stats['total_count'],
                'total_entries' => $stats['total_entries'],
                'filed_entries' => $stats['filed_entries'],
                'unfiled_entries' => $stats['unfiled_entries'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ];
        }

        $activeFilterCount = collect([
            $request->input('vat_type'),
            $request->input('date_from'),
            $request->input('date_to'),
            $request->input('billing_month'),
            $request->input('module'),
            // Count year only when the user explicitly set it (not the implicit current-year default).
            $yearProvided ? $request->input('vat_year') : null,
            $request->input('vat_quarter_slot'),
        ])->filter(fn ($v) => $v !== null && $v !== '')->count();

        return view('vat.index', compact(
            'rows',
            'quarters',
            'years',
            'year',
            'stats',
            'filterMonths',
            'filterModules',
            'activeFilterCount',
            'purchaseAccountId',
            'salesAccountId'
        ));
    }

    /**
     * Show list of VAT returns (Paid and Unpaid).
     */
    public function returnsIndex(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $pane = in_array($request->input('pane'), ['unpaid', 'paid', 'vp'], true)
            ? $request->input('pane')
            : 'unpaid';

        $entryTree = ['entries.transaction.account', 'entries.transaction.voucher'];

        $unpaidQuery = VatReturn::query()->where('status', 'unpaid')->orderByDesc('filed_at');
        $paidQuery = VatReturn::query()->where('status', 'paid')->orderByDesc('filed_at');

        if ($pane === 'unpaid') {
            $unpaidReturns = (clone $unpaidQuery)->with($entryTree)->get();
            $paidReturns = (clone $paidQuery)->withCount('entries')->get();
        } elseif ($pane === 'paid') {
            $paidReturns = (clone $paidQuery)->with($entryTree)->get();
            $unpaidReturns = (clone $unpaidQuery)->withCount('entries')->get();
        } else {
            // VP pane: headers only; payable totals via aggregate SQL.
            $unpaidReturns = (clone $unpaidQuery)->withCount('entries')->get();
            $paidReturns = (clone $paidQuery)->withCount('entries')->get();
        }

        $vpVouchers = Vouchers::where('voucher_type', 'VP')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $sumPayableFromEntries = static function ($returns): float {
            return (float) $returns->sum(function ($r) {
                if (!$r->relationLoaded('entries')) {
                    return 0.0;
                }
                $debit = 0.0;
                $credit = 0.0;
                foreach ($r->entries as $entry) {
                    if (!$entry->transaction) {
                        continue;
                    }
                    $debit += (float) $entry->transaction->debit;
                    $credit += (float) $entry->transaction->credit;
                }

                return $credit - $debit;
            });
        };

        $payableAggregate = static function (string $status): float {
            // Eloquent base query keeps company scope; join only for aggregate.
            return (float) VatReturn::query()
                ->where('vat_returns.status', $status)
                ->join('vat_return_entries', 'vat_return_entries.vat_return_id', '=', 'vat_returns.id')
                ->join('transactions', 'transactions.id', '=', 'vat_return_entries.transaction_id')
                ->whereNull('transactions.deleted_at')
                ->selectRaw('COALESCE(SUM(transactions.credit - transactions.debit), 0) as payable')
                ->value('payable');
        };

        $vpAgg = Vouchers::query()
            ->where('voucher_type', 'VP')
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(amount), 0) as t')
            ->first();

        $returnStats = [
            'unpaid_total' => $pane === 'unpaid' ? $sumPayableFromEntries($unpaidReturns) : $payableAggregate('unpaid'),
            'unpaid_count' => $unpaidReturns->count(),
            'paid_total' => $pane === 'paid' ? $sumPayableFromEntries($paidReturns) : $payableAggregate('paid'),
            'paid_count' => $paidReturns->count(),
            'vp_total' => (float) ($vpAgg->t ?? 0),
            'vp_count' => (int) ($vpAgg->c ?? 0),
        ];

        return view('vat.returns', compact('paidReturns', 'unpaidReturns', 'vpVouchers', 'returnStats', 'pane'));
    }

    /**
     * Show a single VAT return with its entries (both accounts) and option to make payment.
     */
    public function returnsShow($company_slug, VatReturn $vat_return)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $vat_return->load(['entries.transaction.account', 'entries.transaction.voucher']);
        // Order by transaction billing_month and trans_date so entries from both VAT accounts appear in sequence
        $entries = $vat_return->entries()
            ->with(['transaction.account', 'transaction.voucher'])
            ->join('transactions', 'vat_return_entries.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->orderBy('transactions.billing_month')
            ->orderBy('transactions.trans_date')
            ->select('vat_return_entries.*')
            ->get();

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($entries as $entry) {
            if ($entry->transaction) {
                $totalDebit += $entry->transaction->debit;
                $totalCredit += $entry->transaction->credit;
            }
        }
        $payableAmount = $totalCredit - $totalDebit;

        return view('vat.return_show', compact('vat_return', 'entries', 'totalDebit', 'totalCredit', 'payableAmount'));
    }

    /**
     * File a VAT return with only the selected ledger entries. Year and quarter are for reference.
     */
    public function fileReturn(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer', 'exists:transactions,id'],
            'vat_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'vat_quarter_slot' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
        ]);

        $year = (int) $request->input('vat_year');
        $quarterSlot = (int) $request->input('vat_quarter_slot');
        $accountIds = VatSettingsController::getVatAccountIds();
        if (empty($accountIds)) {
            return redirect()->route('vat.index')->with('error', 'VAT accounts not configured.');
        }

        $months = $this->getMonthsThroughQuarter($quarterSlot);
        if (empty($months)) {
            return redirect()->route('vat.index')->with('error', 'Invalid VAT quarter.');
        }

        $quarters = VatSettingsController::getQuartersForDropdown();
        $quarterLabel = $quarters[$quarterSlot] ?? 'Q' . $quarterSlot;

        $selected = array_values(array_unique(array_map('intval', $request->input('transaction_ids'))));
        // Only the selected entries are included; ensure they belong to VAT accounts and are not already filed
        $transactionIds = Transactions::whereIn('id', $selected)
            ->whereIn('account_id', $accountIds)
            ->whereNotIn('id', VatReturnEntry::query()->select('transaction_id'))
            ->pluck('id')
            ->toArray();

        if (empty($transactionIds)) {
            return redirect()->route('vat.index')->with('error', 'Select at least one VAT entry. Selected entries must be from VAT accounts (1023, 1025) and not already filed.');
        }

        if (!VoucherType::isCodeAllowedForModule('VTR', 'vat')) {
            return redirect()->route('vat.index')->with('error', 'VAT Return voucher type (VTR) is not available for this company. Please add it in Voucher Settings.');
        }

        $vatPayableAccountId = self::VAT_PAYMENT_DEFAULT_DEBIT_ACCOUNT;

        DB::beginTransaction();
        try {
            $vatReturn = VatReturn::create([
                'year' => $year,
                'quarter_slot' => $quarterSlot,
                'quarter_label' => $quarterLabel,
                'filed_at' => now(),
                'status' => 'unpaid',
                'filed_by' => auth()->id(),
            ]);

            foreach ($transactionIds as $tid) {
                VatReturnEntry::create([
                    'vat_return_id' => $vatReturn->id,
                    'transaction_id' => $tid,
                ]);
            }

            $this->createVatReturnVoucher($vatReturn, $transactionIds, $accountIds, $vatPayableAccountId, $quarterLabel, $year);

            DB::commit();

            return redirect()
                ->route('vat.returns.index')
                ->with('success', 'VAT return filed for ' . $quarterLabel . ' ' . $year . ' with ' . count($transactionIds) . ' entries. Voucher VTR created.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('vat.fileReturn.exception', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return redirect()->route('vat.index')->with('error', 'Failed to file VAT return: ' . $e->getMessage());
        }
    }

    /**
     * Create a VTR (VAT Return) voucher when filing a return. Exactly 3 entries:
     * 1. Debit: VAT on Sales (1025)
     * 2. Credit: VAT on Purchases (1023)
     * 3. Credit (or Debit if refund): VAT Payable (1027) = VAT on Sales - VAT on Purchases
     */
    private function createVatReturnVoucher(
        VatReturn $vatReturn,
        array $transactionIds,
        array $accountIds,
        int $vatPayableAccountId,
        string $quarterLabel,
        int $year
    ): void {
        $vatInAccountId = $accountIds[0];   // 1023 VAT on Purchases
        $vatOutAccountId = $accountIds[1] ?? $accountIds[0]; // 1025 VAT on Sales

        $totals = Transactions::whereIn('id', $transactionIds)
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $rowVatIn = $totals->get($vatInAccountId);
        $rowVatOut = $totals->get($vatOutAccountId);
        $vatOnPurchases = (float) ($rowVatIn ? $rowVatIn->total_debit : 0);  // VAT In = debits
        $vatOnSales = (float) ($rowVatOut ? $rowVatOut->total_credit : 0);   // VAT Out = credits

        $remaining = $vatOnSales - $vatOnPurchases;

        $billingMonth = $this->getBillingMonthForQuarter($year, $vatReturn->quarter_slot);
        $transDate = $vatReturn->filed_at ? $vatReturn->filed_at->format('Y-m-d') : now()->format('Y-m-d');
        $referenceNumber = 'VAT Return ' . $quarterLabel . ' ' . $year;
        $transCode = Account::trans_code();

        $voucher = Vouchers::create([
            'voucher_type' => 'VTR',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'billing_month' => $billingMonth,
            'reference_number' => $referenceNumber,
            'amount' => abs($remaining),
            'ref_id' => $vatReturn->id,
            'Created_By' => auth()->id(),
        ]);

        // 1. VAT on Sales: debit at the top
        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'account_id' => $vatOutAccountId,
            'debit' => $vatOnSales,
            'credit' => 0,
            'narration' => 'VAT on Sales',
            'reference_id' => $voucher->id,
            'reference_type' => 'Voucher',
            'billing_month' => $billingMonth,
        ]);

        // 2. VAT on Purchases: credit
        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'account_id' => $vatInAccountId,
            'debit' => 0,
            'credit' => $vatOnPurchases,
            'narration' => 'VAT on Purchases',
            'reference_id' => $voucher->id,
            'reference_type' => 'Voucher',
            'billing_month' => $billingMonth,
        ]);

        // 3. Remaining balance: credit to VAT Payable (or debit if refund)
        if ($remaining >= 0) {
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'account_id' => $vatPayableAccountId,
                'debit' => 0,
                'credit' => $remaining,
                'narration' => 'VAT Payable',
                'reference_id' => $voucher->id,
                'reference_type' => 'Voucher',
                'billing_month' => $billingMonth,
            ]);
        } else {
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'account_id' => $vatPayableAccountId,
                'debit' => abs($remaining),
                'credit' => 0,
                'narration' => 'VAT Payable (refund)',
                'reference_id' => $voucher->id,
                'reference_type' => 'Voucher',
                'billing_month' => $billingMonth,
            ]);
        }
    }

    /**
     * Billing month for the voucher: first day of the last month in the quarter.
     */
    private function getBillingMonthForQuarter(int $year, int $quarterSlot): string
    {
        $months = $this->getMonthsThroughQuarter($quarterSlot);
        $lastMonth = !empty($months) ? (int) end($months) : 1;
        return sprintf('%04d-%02d-01', $year, $lastMonth);
    }

    /**
     * Toggle VAT return status between paid and unpaid.
     */
    public function updateReturnStatus(Request $request, $company_slug, VatReturn $vat_return)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['status' => ['required', Rule::in(['paid', 'unpaid'])]]);
        $vat_return->update(['status' => $request->input('status')]);
        $message = $request->input('status') === 'paid' ? 'Payment recorded. Return marked as Paid.' : 'Return marked as Unpaid.';
        return redirect()->back()->with('success', $message);
    }

    /**
     * Remove selected entries from this return. Removed entries will show again in the VAT ledger.
     */
    public function deleteReturnEntries(Request $request, $company_slug, VatReturn $vat_return)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'entry_ids' => ['required', 'array'],
            'entry_ids.*' => ['integer', 'exists:vat_return_entries,id'],
        ]);

        $entryIds = array_map('intval', $request->input('entry_ids'));
        $deleted = VatReturnEntry::where('vat_return_id', $vat_return->id)
            ->whereIn('id', $entryIds)
            ->delete();

        return redirect()
            ->route('vat.returns.show', $vat_return)
            ->with('success', $deleted . ' entry(ies) removed. They will appear again in the VAT ledger.');
    }

    /**
     * Show form to create a VAT Payment voucher (VP). Default debit account 1027; Add Entry rows debit selected account, Bank credited.
     */
    public function createVoucher(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $bankCashAccounts = Accounts::bankAndCashDropdown();
        $allAccounts = Accounts::dropdown(null);

        $defaultAccount = Accounts::find(self::VAT_PAYMENT_DEFAULT_DEBIT_ACCOUNT);
        $prefillAmount = null;
        $prefillReference = null;
        $vatReturnId = null;
        if ($request->filled('vat_return_id')) {
            $return = VatReturn::find($request->input('vat_return_id'));
            if ($return !== null) {
                $vatReturnId = $return->id;
                $prefillAmount = $return->payable_amount;
                $prefillReference = 'VAT Return ' . ($return->quarter_label ?? 'Q' . $return->quarter_slot) . ' ' . $return->year;
            }
        }

        return view('vat.voucher_create', [
            'bankCashAccounts' => $bankCashAccounts,
            'allAccounts' => $allAccounts,
            'defaultDebitAccountId' => self::VAT_PAYMENT_DEFAULT_DEBIT_ACCOUNT,
            'defaultDebitAccountName' => $defaultAccount ? $defaultAccount->account_code . ' – ' . $defaultAccount->name : '1027',
            'prefillAmount' => $prefillAmount,
            'prefillReference' => $prefillReference,
            'vatReturnId' => $vatReturnId,
        ]);
    }

    /**
     * Store a VAT Payment voucher (VP). Each debit row: account debited; single credit: Bank.
     */
    public function storeVoucher(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'trans_date' => 'required|date',
            'billing_month' => 'required',
            'reference_number' => 'required|string|max:255',
            'credit_account_id' => 'required|exists:accounts,id',
            'debit_account_id' => 'required|array|min:1',
            'debit_account_id.*' => 'required|exists:accounts,id',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:0.01',
            'vat_return_id' => ['nullable', 'integer', 'exists:vat_returns,id'],
        ]);

        $debitAccounts = $request->input('debit_account_id');
        $creditAccountId = (int) $request->input('credit_account_id');
        $amounts = $request->input('amount');
        $narrations = $request->input('debit_narration', []);

        if (!VoucherType::isCodeAllowedForModule('VP', 'vat')) {
            return redirect()->back()->withInput()->with('error', 'VAT Payment voucher type (VP) is not assigned to the VAT module. Please assign it in Voucher Settings.');
        }

        DB::beginTransaction();
        try {
            $grandTotal = array_sum(array_map('floatval', $amounts));
            $transCode = Account::trans_code();

            $voucher = Vouchers::create([
                'voucher_type' => 'VP',
                'trans_code' => $transCode,
                'trans_date' => $request->input('trans_date'),
                'billing_month' => $request->input('billing_month') . '-01',
                'reference_number' => $request->input('reference_number'),
                'payment_type' => $request->input('payment_type'),
                'amount' => $grandTotal,
                'Created_By' => auth()->id(),
            ]);

            foreach ($debitAccounts as $index => $debitAccountId) {
                $amount = (float) ($amounts[$index] ?? 0);
                if ($amount <= 0) {
                    continue;
                }
                $narration = $narrations[$index] ?? 'VAT Payment';

                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $request->input('trans_date'),
                    'account_id' => $debitAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => $narration,
                    'reference_id' => $voucher->id,
                    'reference_type' => 'Voucher',
                    'billing_month' => $request->input('billing_month') . '-01',
                ]);
            }

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $request->input('trans_date'),
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $grandTotal,
                'narration' => 'VAT Payment',
                'reference_id' => $voucher->id,
                'reference_type' => 'Voucher',
                'billing_month' => $request->input('billing_month') . '-01',
            ]);

            if ($request->filled('vat_return_id')) {
                $vatReturn = VatReturn::find($request->input('vat_return_id'));
                if ($vatReturn !== null) {
                    $vatReturn->update(['status' => 'paid']);
                }
            }

            DB::commit();

            $vatReturnId = $request->input('vat_return_id');
            if ($vatReturnId) {
                return redirect()->route('vat.returns.show', $vatReturnId)
                    ->with('success', 'VAT Payment voucher created and return marked as paid.');
            }
            return redirect()->route('vat.index')->with('success', 'VAT Payment voucher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('vat.index')->with('error', 'Failed to create voucher: ' . $e->getMessage());
        }
    }

    /**
     * Delete a VAT return and its entries. Transactions will show again in the VAT ledger.
     * When the return is unpaid, the associated VTR voucher (ref_id + voucher_type) is also deleted.
     */
    public function destroyReturn($company_slug, VatReturn $vat_return)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        if ($vat_return->status === 'unpaid') {
            $returnVouchers = Vouchers::where('voucher_type', 'VTR')
                ->where('ref_id', $vat_return->id)
                ->get();
            foreach ($returnVouchers as $voucher) {
                Transactions::hardDeleteMatching(fn ($q) => $q->where('trans_code', $voucher->trans_code));
                $voucher->delete();
            }
        }

        $vat_return->entries()->delete();
        $vat_return->delete();

        return redirect()->route('vat.returns.index')->with('success', 'Return deleted. Entries are visible again in the VAT ledger.');
    }

    /**
     * Months from quarter 1 through the given quarter (1-4). Used so the ledger shows
     * unfiled entries from previous quarters when a later quarter is selected.
     */
    private function getMonthsThroughQuarter(int $quarterSlot): array
    {
        if ($quarterSlot < 1 || $quarterSlot > 4) {
            return [];
        }
        $keys = VatSettingsController::quarterKeys();
        $allMonths = [];
        for ($i = 0; $i < $quarterSlot && $i < 4; $i++) {
            $start = Settings::where('name', $keys[$i])->value('value');
            if ($start !== null && $start !== '' && (int) $start >= 1 && (int) $start <= 12) {
                $months = VatSettingsController::quarterMonthsForStart((int) $start);
                $allMonths = array_merge($allMonths, $months);
            }
        }
        return array_values(array_unique($allMonths));
    }

    /**
     * Opening balance: sum (debit - credit) before the selected period, excluding already-filed entries.
     * When year + quarter: cutoff is Jan 1 of that year (we show all quarters through selected one).
     */
    private function getOpeningBalance(array $accountIds, ?string $quarterSlot, ?string $year): float
    {
        if ($year === null || $year === '') {
            return 0;
        }
        $cutoff = $year . '-01-01';
        return (float) Transactions::whereIn('account_id', $accountIds)
            ->where('billing_month', '<', $cutoff)
            ->whereNotIn('id', VatReturnEntry::query()->select('transaction_id'))
            ->sum(DB::raw('debit - credit'));
    }

    private function getYearsForDropdown(): array
    {
        $current = (int) date('Y');
        $out = ['' => 'All years'];
        for ($y = $current; $y >= $current - 10; $y--) {
            $out[$y] = (string) $y;
        }
        return $out;
    }

    private function formatBalance(float $balance): string
    {
        $formatted = number_format($balance, 2);
        return $balance > 0 ? '+' . $formatted : $formatted;
    }

    /**
     * Human-readable module label for a VAT ledger transaction (demo redesign).
     */
    private function getModuleLabel($row): string
    {
        $type = (string) ($row->reference_type ?? '');

        return match ($type) {
            'Salik', 'Salik Voucher' => 'Salik',
            'RTA' => 'RTA Fine',
            'Invoice', 'RiderInvoice' => 'Customer Invoice',
            'LeasingCompanyInvoice' => 'Leasing',
            'Bike Maintenance' => 'Supplier / Garage',
            'LV' => 'Visa Expense',
            'SIM', 'SimInvoice' => 'SIM',
            'Fuel', 'FuelInvoice' => 'Fuel',
            'LeasingCompanyBillingInvoice', 'RentalBilling' => 'Rental Billing',
            'Expense', 'Voucher' => 'Expense',
            default => $type !== '' ? $type : 'Other',
        };
    }

    /**
     * Map filter module label back to transaction reference_type values.
     *
     * @return string[]
     */
    private function referenceTypesForModule(string $module): array
    {
        return match ($module) {
            'Salik' => ['Salik', 'Salik Voucher'],
            'RTA Fine' => ['RTA'],
            'Customer Invoice' => ['Invoice', 'RiderInvoice'],
            'Leasing' => ['LeasingCompanyInvoice'],
            'Supplier / Garage' => ['Bike Maintenance'],
            'Visa Expense' => ['LV'],
            'SIM' => ['SIM', 'SimInvoice'],
            'Fuel' => ['Fuel', 'FuelInvoice'],
            'Rental Billing' => ['LeasingCompanyBillingInvoice', 'RentalBilling'],
            'Expense' => ['Expense', 'Voucher'],
            'Other' => [''],
            default => [$module],
        };
    }

    /**
     * Restrict billing_month to specific calendar months within a year using range predicates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int[]  $months  1-12
     */
    private function applyBillingMonthMonthsFilter($query, int $year, array $months): void
    {
        $months = array_values(array_unique(array_map('intval', $months)));
        if (empty($months)) {
            return;
        }

        $query->where(function ($outer) use ($year, $months) {
            foreach ($months as $m) {
                if ($m < 1 || $m > 12) {
                    continue;
                }
                $start = sprintf('%04d-%02d-01', $year, $m);
                $endMonth = $m === 12 ? 1 : $m + 1;
                $endYear = $m === 12 ? $year + 1 : $year;
                $end = sprintf('%04d-%02d-01', $endYear, $endMonth);
                $outer->orWhere(function ($w) use ($start, $end) {
                    $w->where('billing_month', '>=', $start)->where('billing_month', '<', $end);
                });
            }
        });
    }

    /**
     * Batch-load related records used while rendering ledger voucher/narration cells.
     *
     * @param  \Illuminate\Support\Collection<int, Transactions>  $transactions
     * @return array{maintenances: \Illuminate\Support\Collection, rta_fines: \Illuminate\Support\Collection, visa_expenses: \Illuminate\Support\Collection, visa_names: array<int, string>}
     */
    private function buildLedgerEnrichmentMaps($transactions): array
    {
        $maintenanceIds = [];
        $rtaFineIds = [];
        $visaExpenseIds = [];

        foreach ($transactions as $row) {
            $type = (string) ($row->reference_type ?? '');
            if ($type === 'Bike Maintenance' && $row->reference_id) {
                $maintenanceIds[] = (int) $row->reference_id;
            }
            if ($type === 'RTA') {
                $voucher = $row->voucher;
                if ($voucher && !empty($voucher->ref_id)) {
                    $rtaFineIds[] = (int) $voucher->ref_id;
                }
            }
            if ($type === 'LV' && $row->reference_id) {
                $visaExpenseIds[] = (int) $row->reference_id;
            }
        }

        $maintenances = empty($maintenanceIds)
            ? collect()
            : BikeMaintenance::whereIn('id', array_values(array_unique($maintenanceIds)))->get()->keyBy('id');

        $rtaFines = empty($rtaFineIds)
            ? collect()
            : collect(\App\Support\CompanyQuery::table('rta_fines')->whereIn('id', array_values(array_unique($rtaFineIds)))->get())
                ->keyBy('id');

        $visaExpenses = empty($visaExpenseIds)
            ? collect()
            : collect(\App\Support\CompanyQuery::table('visa_expenses')->whereIn('id', array_values(array_unique($visaExpenseIds)))->get())
                ->keyBy('id');

        $visaNames = $this->resolveVisaExpensePersonNamesBulk($visaExpenses);

        return [
            'maintenances' => $maintenances,
            'rta_fines' => $rtaFines,
            'visa_expenses' => $visaExpenses,
            'visa_names' => $visaNames,
        ];
    }

    /**
     * Resolve display names for many visa expenses with bulk lookups (no N+1).
     *
     * @param  \Illuminate\Support\Collection<int, object>  $visaExpenses
     * @return array<int, string>
     */
    private function resolveVisaExpensePersonNamesBulk($visaExpenses): array
    {
        if ($visaExpenses->isEmpty()) {
            return [];
        }

        $employeeIds = [];
        $expenseAccountIds = [];
        $riderIds = [];
        $legacyAccountIds = [];

        foreach ($visaExpenses as $expense) {
            if (!empty($expense->employee_id)) {
                $employeeIds[] = (int) $expense->employee_id;
            }
            if (!empty($expense->expense_account_id)) {
                $expenseAccountIds[] = (int) $expense->expense_account_id;
            }
            if (!empty($expense->rider_id)) {
                $riderIds[] = (int) $expense->rider_id;
                $legacyAccountIds[] = (int) $expense->rider_id;
            }
        }

        $expenseAccounts = empty($expenseAccountIds)
            ? collect()
            : collect(\App\Support\CompanyQuery::table('expense_accounts')->whereIn('id', array_values(array_unique($expenseAccountIds)))->get())
                ->keyBy('id');

        foreach ($expenseAccounts as $ea) {
            if (!empty($ea->employee_id)) {
                $employeeIds[] = (int) $ea->employee_id;
            }
            if (!empty($ea->rider_id)) {
                $riderIds[] = (int) $ea->rider_id;
            }
        }

        $employees = empty($employeeIds)
            ? collect()
            : collect(\App\Support\CompanyQuery::table('employees')->whereIn('id', array_values(array_unique($employeeIds)))->get())
                ->keyBy('id');

        $riders = empty($riderIds)
            ? collect()
            : collect(\App\Support\CompanyQuery::table('riders')->whereIn('id', array_values(array_unique($riderIds)))->get())
                ->keyBy('id');

        $accounts = empty($legacyAccountIds)
            ? collect()
            : collect(\App\Support\CompanyQuery::table('accounts')->whereIn('id', array_values(array_unique($legacyAccountIds)))->get())
                ->keyBy('id');

        $names = [];
        foreach ($visaExpenses as $id => $expense) {
            $name = null;
            if (!empty($expense->employee_id) && $employees->has((int) $expense->employee_id)) {
                $employee = $employees->get((int) $expense->employee_id);
                $code = $employee->employee_id ?: ('EMP-' . $employee->id);
                $name = trim($code . ' - ' . $employee->name);
            } elseif (!empty($expense->expense_account_id) && $expenseAccounts->has((int) $expense->expense_account_id)) {
                $ea = $expenseAccounts->get((int) $expense->expense_account_id);
                if (!empty($ea->employee_id) && $employees->has((int) $ea->employee_id)) {
                    $employee = $employees->get((int) $ea->employee_id);
                    $code = $employee->employee_id ?: ('EMP-' . $employee->id);
                    $name = trim($code . ' - ' . $employee->name);
                } elseif (!empty($ea->rider_id) && $riders->has((int) $ea->rider_id)) {
                    $rider = $riders->get((int) $ea->rider_id);
                    $name = trim(($rider->rider_id ? $rider->rider_id . ' - ' : '') . $rider->name);
                } elseif (!empty($ea->name)) {
                    $name = (string) $ea->name;
                }
            } elseif (!empty($expense->rider_id)) {
                if ($riders->has((int) $expense->rider_id)) {
                    $rider = $riders->get((int) $expense->rider_id);
                    $name = trim(($rider->rider_id ? $rider->rider_id . ' - ' : '') . $rider->name);
                } elseif ($accounts->has((int) $expense->rider_id)) {
                    $name = (string) $accounts->get((int) $expense->rider_id)->name;
                }
            }
            if ($name !== null && $name !== '') {
                $names[(int) $id] = $name;
            }
        }

        return $names;
    }

    private function getViewFile($row): string
    {
        if (!isset($row->voucher->attach_file)) {
            return '';
        }
        if ($row->reference_type === 'RTA' || $row->reference_type === 'LV') {
            return '  <a href="' . url('storage/' . $row->voucher->attach_file) . '" class="no-print" target="_blank">View File</a>';
        }
        return '  <a href="' . url('storage/vouchers/' . $row->voucher->attach_file) . '" class="no-print" target="_blank">View File</a>';
    }

    private function getVoucherText($row, array $enrichment = []): string
    {
        $voucherTypes = ['Voucher', 'RTA', 'LV', 'VL', 'INC', 'PN', 'PAY', 'COD', 'Salik Voucher', 'VC', 'AL', 'RiderInvoice'];
        if (in_array($row->reference_type, $voucherTypes, true)) {
            $vouchers = $row->voucher;
            if ($vouchers) {
                $voucherId = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                return '<a href="javascript:void(0);" data-title="Voucher # ' . $voucherId . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="show-modal">' . $voucherId . '</a>';
            }
            return '<span class="text-danger">No Voucher Found</span>';
        }
        if ($row->reference_type === 'Invoice') {
            return '<a href="javascript:void(0);" data-title="Invoice # ' . $row->reference_id . '" data-size="xl" data-action="' . route('riderInvoices.show', $row->reference_id) . '" class="show-modal">RD-' . $row->reference_id . '</a>';
        }
        if ($row->reference_type === 'Bike Maintenance') {
            $maintenance = ($enrichment['maintenances'] ?? collect())->get((int) $row->reference_id);
            if ($maintenance) {
                return '<a href="' . route('bike-maintenance.invoice', $maintenance) . '" target="_blank" class="no-print">MA-' . $maintenance->id . '</a>';
            }
            return '<span class="text-danger">Maintenance record not found</span>';
        }
        if ($row->reference_type === 'LeasingCompanyInvoice') {
            return '<a href="javascript:void(0);" data-title="Leasing Company Invoice # ' . $row->reference_id . '" data-size="xl" data-action="' . route('leasingCompanyInvoices.show', $row->reference_id) . '" class="show-modal">LI-' . $row->reference_id . '</a>';
        }
        return '';
    }

    private function getNarration($row, string $viewFile, array $enrichment = []): string
    {
        if ($row->reference_type === 'RTA') {
            $voucher = $row->voucher;
            if ($voucher && !empty($voucher->ref_id)) {
                $fines = ($enrichment['rta_fines'] ?? collect())->get((int) $voucher->ref_id);
                if ($fines) {
                    return $row->narration . ', <b>Ticket Number: </b>' . $fines->ticket_no . ', <b>Bike No: </b>' . $fines->plate_no . ', ' . \Carbon\Carbon::parse($fines->trip_date)->format('d M Y') . ', ' . $viewFile;
                }
            }
            return $row->narration . ', ' . $viewFile;
        }
        if ($row->reference_type === 'LV') {
            $visaex = ($enrichment['visa_expenses'] ?? collect())->get((int) $row->reference_id);
            if ($visaex) {
                $personName = ($enrichment['visa_names'] ?? [])[(int) $row->reference_id] ?? null;
                if ($personName) {
                    return 'Paid to <b>' . $personName . ' </b>' . $visaex->visa_status . ' Charges ' . $visaex->date . $viewFile;
                }
                return $row->narration . ' (Rider not found) ' . $viewFile;
            }
            return $row->narration . ' (Visa expense not found) ' . $viewFile;
        }
        return $row->narration . ', ' . $viewFile;
    }
}

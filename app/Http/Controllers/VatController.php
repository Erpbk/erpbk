<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Models\Accounts;
use App\Models\BikeMaintenance;
use App\Models\CustomerInvoices;
use App\Models\EmployeeInvoices;
use App\Models\FuelData;
use App\Models\salik;
use App\Models\Settings;
use App\Models\SimInvoice;
use App\Models\SupplierInvoices;
use App\Models\Transactions;
use App\Models\Payment;
use App\Models\VatReturn;
use App\Models\VatReturnEntry;
use App\Support\CompanyQuery;
use App\Support\GlobalAccounts;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Resolve mapped VAT Payable global account or fail with a clear message.
     */
    private function requireVatPayableAccountId(): int
    {
        $id = GlobalAccounts::idOrNull('VAT_PAYABLE');
        if (! $id) {
            throw new \RuntimeException(
                'VAT Payable global account is not mapped. Configure VAT_PAYABLE in Admin → Global Accounts.'
            );
        }

        return (int) $id;
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
        $rawYear = $yearProvided ? (string) $request->input('vat_year', '') : (string) date('Y');
        if ($rawYear !== '' && preg_match('/^(\d{4})/', $rawYear, $yearMatch)) {
            $year = $yearMatch[1];
        } else {
            $year = $yearProvided ? '' : (string) date('Y');
        }
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

            // Source VAT lines only (exclude return-clearing offsets from filing stats / ledger).
            $sourceScopedQuery = (clone $scopedAllQuery)->where(function ($q) {
                $q->whereNull('reference_type')
                    ->orWhere('reference_type', '!=', VatReturn::LEDGER_REFERENCE_TYPE);
            });
            $stats['total_entries'] = (clone $sourceScopedQuery)->count();
            $stats['filed_entries'] = (clone $sourceScopedQuery)
                ->whereIn('id', VatReturnEntry::filedTransactionIdsQuery())
                ->count();
            $stats['unfiled_entries'] = max(0, $stats['total_entries'] - $stats['filed_entries']);

            // Unfiled, fileable source lines only — no filed entries, no VatReturn clearing offsets.
            $baseQuery = (clone $sourceScopedQuery)
                ->whereNotIn('id', VatReturnEntry::filedTransactionIdsQuery());

            $stats['total_count'] = (clone $baseQuery)->count();
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
                // type="month" sends YYYY-MM; tolerate YYYY-MM-DD from older bookmarks.
                if (preg_match('/^(\d{4}-\d{2})/', $bm, $m)) {
                    $start = $m[1] . '-01';
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
                'attachment' => '',
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

                $module = $this->getModuleLabel($row);
                $rows[] = [
                    'transaction_id' => $row->id,
                    'date' => Common::DateFormat($row->trans_date),
                    'date_iso' => $row->trans_date ? date('Y-m-d', strtotime($row->trans_date)) : '',
                    'account_name' => $row->account ? ($row->account->account_code . '-' . $row->account->name) : 'N/A',
                    'account_code' => $row->account?->account_code ?? '',
                    'is_output' => $isOutput,
                    'reference_number' => $row->voucher ? ($row->voucher->reference_number ?? '-') : '-',
                    'billing_month' => $row->billing_month ? date('M Y', strtotime($row->billing_month)) : '',
                    'billing_month_key' => $row->billing_month ? date('Y-m', strtotime($row->billing_month)) : '',
                    'voucher' => $this->getVoucherText($row, $enrichment),
                    'attachment' => $this->getAttachmentHtml($row, $enrichment),
                    'module' => $module,
                    'narration' => $this->getNarration($row, $enrichment),
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
                'attachment' => '',
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
            'filterModules',
            'activeFilterCount',
            'purchaseAccountId',
            'salesAccountId'
        ));
    }

    /**
     * Show list of VAT returns (headers only; entries load on demand).
     */
    public function returnsIndex(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $accountIds = VatSettingsController::getVatAccountIds();
        $purchaseAccountId = (int) ($accountIds[0] ?? 0);
        $salesAccountId = (int) ($accountIds[1] ?? $accountIds[0] ?? 0);
        $quarters = VatSettingsController::getQuartersForDropdown();

        $returnsQuery = VatReturn::query()->withCount('entries');

        $filterYear = (string) $request->input('year', '');
        if (preg_match('/^\d{4}$/', $filterYear)) {
            $returnsQuery->where('year', (int) $filterYear);
        }
        if ($request->filled('quarter_slot')) {
            $returnsQuery->where('quarter_slot', (int) $request->input('quarter_slot'));
        }
        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === 'unpaid') {
                $returnsQuery->where('status', VatReturn::STATUS_UNPAID);
            } elseif ($status === 'paid') {
                $returnsQuery->where('status', VatReturn::STATUS_PAID);
            } elseif ($status === 'partial') {
                $returnsQuery->where('status', VatReturn::STATUS_PARTIAL);
            } elseif (is_numeric($status)) {
                $returnsQuery->where('status', (int) $status);
            }
        }
        if ($request->filled('filed_from')) {
            $returnsQuery->whereDate('filed_at', '>=', $request->input('filed_from'));
        }
        if ($request->filled('filed_to')) {
            $returnsQuery->whereDate('filed_at', '<=', $request->input('filed_to'));
        }

        $returns = $returnsQuery
            ->orderByDesc('filed_at')
            ->orderByDesc('id')
            ->get();

        $activeFilterCount = collect([
            $request->input('year'),
            $request->input('quarter_slot'),
            $request->input('status'),
            $request->input('filed_from'),
            $request->input('filed_to'),
        ])->filter(fn ($v) => $v !== null && $v !== '')->count();

        $totalsByReturnId = $this->aggregateReturnEntryTotals(
            $returns->pluck('id')->map(fn ($id) => (int) $id)->all(),
            $purchaseAccountId,
            $salesAccountId
        );

        $paymentIds = $returns
            ->flatMap(fn (VatReturn $r) => $r->livePaymentIds())
            ->unique()
            ->values()
            ->all();

        $paymentsById = $paymentIds === []
            ? collect()
            : Payment::query()
                ->with('voucher')
                ->whereIn('id', $paymentIds)
                ->get()
                ->keyBy('id');

        $payableAggregate = static function ($statuses) use ($totalsByReturnId, $returns): float {
            $statuses = is_array($statuses) ? $statuses : [$statuses];
            $ids = $returns
                ->filter(fn (VatReturn $r) => in_array((int) $r->status, $statuses, true))
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            return round((float) $ids->sum(function ($id) use ($totalsByReturnId) {
                return (float) ($totalsByReturnId[$id]['net'] ?? 0);
            }), 2);
        };

        $vpAgg = Payment::query()
            ->whereNotNull('resource_links')
            ->where('resource_links', 'like', '%"invoice_type":"vat_return"%')
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(amount), 0) as t')
            ->first();

        $returnStats = [
            'unpaid_total' => $payableAggregate([VatReturn::STATUS_UNPAID, VatReturn::STATUS_PARTIAL]),
            'unpaid_count' => $returns->filter(
                fn (VatReturn $r) => in_array((int) $r->status, [VatReturn::STATUS_UNPAID, VatReturn::STATUS_PARTIAL], true)
            )->count(),
            'paid_total' => $payableAggregate(VatReturn::STATUS_PAID),
            'paid_count' => $returns->filter(
                fn (VatReturn $r) => (int) $r->status === VatReturn::STATUS_PAID
            )->count(),
            'vp_total' => (float) ($vpAgg->t ?? 0),
            'vp_count' => (int) ($vpAgg->c ?? 0),
        ];

        return view('vat.returns', compact(
            'returns',
            'paymentsById',
            'returnStats',
            'totalsByReturnId',
            'purchaseAccountId',
            'salesAccountId',
            'quarters',
            'activeFilterCount'
        ));
    }

    /**
     * Lazy-load entries HTML for a return expand row.
     */
    public function returnsEntries($company_slug, VatReturn $vat_return)
    {
        if (! user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $accountIds = VatSettingsController::getVatAccountIds();
        $purchaseAccountId = (int) ($accountIds[0] ?? 0);
        $salesAccountId = (int) ($accountIds[1] ?? $accountIds[0] ?? 0);

        $entries = $vat_return->entries()
            ->with(['transaction.account', 'transaction.voucher'])
            ->join('transactions', 'vat_return_entries.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.deleted_at')
            ->orderBy('transactions.billing_month')
            ->orderBy('transactions.trans_date')
            ->select('vat_return_entries.*')
            ->get();

        $transactions = $entries->map(fn ($entry) => $entry->transaction)->filter()->values();
        $enrichment = $this->buildLedgerEnrichmentMaps($transactions);

        $entryRows = [];
        $debit = 0.0;
        $credit = 0.0;
        foreach ($entries as $entry) {
            if (! $entry->transaction) {
                continue;
            }
            $t = $entry->transaction;
            $debit += (float) $t->debit;
            $credit += (float) $t->credit;
            $isOutput = $salesAccountId && (int) $t->account_id === $salesAccountId;
            $voucherHtml = $this->getVoucherText($t, $enrichment);
            if ($voucherHtml === '') {
                $fallback = $t->voucher
                    ? ($t->voucher->formatted_id
                        ?: ($t->voucher->voucher_type.'-'.str_pad((string) $t->voucher->id, 4, '0', STR_PAD_LEFT)))
                    : ($t->voucher_number ?? '—');
                $voucherHtml = e($fallback !== '' ? $fallback : '—');
            }
            $module = (string) ($t->reference_type ?? '—');
            $entryRows[] = [
                'date' => Common::DateFormat($t->trans_date),
                'account' => $isOutput
                    ? ('Output · Sales '.($t->account->account_code ?? ''))
                    : ('Input · Purchase '.($t->account->account_code ?? '')),
                'month' => $t->billing_month ? date('M Y', strtotime($t->billing_month)) : '—',
                'voucher' => $voucherHtml,
                'module' => $module !== '' ? $module : '—',
                'narration' => $t->narration ?: '—',
                'debit' => (float) $t->debit,
                'credit' => (float) $t->credit,
            ];
        }

        return view('vat.partials.return_entries_table', [
            'entryRows' => $entryRows,
            'debit' => $debit,
            'credit' => $credit,
            'purchaseAccountId' => $purchaseAccountId,
            'salesAccountId' => $salesAccountId,
        ]);
    }

    /**
     * Bulk output/input/net totals per return (no entry trees).
     *
     * @param  list<int>  $returnIds
     * @return array<int, array{output:float,input:float,net:float,debit:float,credit:float}>
     */
    private function aggregateReturnEntryTotals(array $returnIds, int $purchaseAccountId, int $salesAccountId): array
    {
        if ($returnIds === []) {
            return [];
        }

        $rows = DB::table('vat_return_entries')
            ->join('transactions', 'transactions.id', '=', 'vat_return_entries.transaction_id')
            ->whereIn('vat_return_entries.vat_return_id', $returnIds)
            ->whereNull('transactions.deleted_at')
            ->groupBy('vat_return_entries.vat_return_id')
            ->selectRaw(
                'vat_return_entries.vat_return_id as id,
                 COALESCE(SUM(transactions.debit), 0) as debit,
                 COALESCE(SUM(transactions.credit), 0) as credit,
                 COALESCE(SUM(CASE WHEN transactions.account_id = ? THEN transactions.credit - transactions.debit ELSE 0 END), 0) as output_vat,
                 COALESCE(SUM(CASE WHEN transactions.account_id = ? THEN transactions.debit - transactions.credit ELSE 0 END), 0) as input_vat',
                [$salesAccountId, $purchaseAccountId]
            )
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $out[(int) $row->id] = [
                'debit' => $debit,
                'credit' => $credit,
                'output' => (float) $row->output_vat,
                'input' => (float) $row->input_vat,
                'net' => $credit - $debit,
            ];
        }

        return $out;
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

        $linkedPayments = collect();
        $paymentIds = $vat_return->livePaymentIds();
        if ($paymentIds !== []) {
            $linkedPayments = Payment::query()
                ->whereIn('id', $paymentIds)
                ->orderByDesc('id')
                ->get();
        }

        return view('vat.return_show', compact(
            'vat_return',
            'entries',
            'totalDebit',
            'totalCredit',
            'payableAmount',
            'linkedPayments'
        ));
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
            ->whereNotIn('id', VatReturnEntry::filedTransactionIdsQuery())
            ->where(function ($q) {
                $q->whereNull('reference_type')
                    ->orWhere('reference_type', '!=', VatReturn::LEDGER_REFERENCE_TYPE);
            })
            ->pluck('id')
            ->toArray();

        if (empty($transactionIds)) {
            return redirect()->route('vat.index')->with('error', 'Select at least one VAT entry. Selected entries must be from VAT accounts (1023, 1025) and not already filed.');
        }

        try {
            $vatPayableAccountId = $this->requireVatPayableAccountId();
        } catch (\RuntimeException $e) {
            return redirect()->route('vat.index')->with('error', $e->getMessage());
        }

        DB::beginTransaction();
        try {
            $vatReturn = VatReturn::create([
                'year' => $year,
                'quarter_slot' => $quarterSlot,
                'quarter_label' => $quarterLabel,
                'filed_at' => now(),
                'status' => VatReturn::STATUS_UNPAID,
                'filed_by' => auth()->id(),
            ]);

            foreach ($transactionIds as $tid) {
                VatReturnEntry::create([
                    'vat_return_id' => $vatReturn->id,
                    'transaction_id' => $tid,
                ]);
            }

            $this->createVatReturnLedgerTransactions($vatReturn, $transactionIds, $accountIds, $vatPayableAccountId);

            DB::commit();

            return redirect()
                ->route('vat.returns.index')
                ->with('success', 'VAT return filed for ' . $quarterLabel . ' ' . $year . ' with ' . count($transactionIds) . ' entries.');
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
     * Post clearing ledger rows for a filed return (invoice-style: no voucher).
     * 1. Debit: VAT on Sales
     * 2. Credit: VAT on Purchases
     * 3. Credit (or Debit if refund): VAT Payable
     */
    private function createVatReturnLedgerTransactions(
        VatReturn $vatReturn,
        array $transactionIds,
        array $accountIds,
        int $vatPayableAccountId
    ): void {
        $lines = $this->buildVatReturnClearingLines($transactionIds, $accountIds, $vatPayableAccountId);
        $billingMonth = $this->getBillingMonthForQuarter((int) $vatReturn->year, (int) $vatReturn->quarter_slot);
        $transDate = $vatReturn->filed_at ? $vatReturn->filed_at->format('Y-m-d') : now()->format('Y-m-d');
        $narrationBase = 'VAT Return '.($vatReturn->quarter_label ?? ('Q'.$vatReturn->quarter_slot)).' '.$vatReturn->year;
        $transCode = Account::trans_code();

        foreach ($lines['rows'] as $row) {
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'account_id' => $row['account_id'],
                'debit' => $row['debit'],
                'credit' => $row['credit'],
                'narration' => $row['narration'].' — '.$narrationBase,
                'reference_id' => $vatReturn->id,
                'reference_type' => VatReturn::LEDGER_REFERENCE_TYPE,
                'billing_month' => $billingMonth,
            ]);
        }
    }

    /**
     * Rebuild clearing ledger for an unpaid return after entries are removed.
     */
    private function rebuildVatReturnLedger(VatReturn $vatReturn): void
    {
        $accountIds = VatSettingsController::getVatAccountIds();
        $vatPayableAccountId = $this->requireVatPayableAccountId();
        $transactionIds = $vatReturn->entries()->pluck('transaction_id')->map(fn ($id) => (int) $id)->all();

        Transactions::hardDeleteMatching(function ($q) use ($vatReturn) {
            $q->where('reference_type', VatReturn::LEDGER_REFERENCE_TYPE)
                ->where('reference_id', $vatReturn->id);
        });

        if ($transactionIds === []) {
            return;
        }

        $this->createVatReturnLedgerTransactions(
            $vatReturn,
            $transactionIds,
            $accountIds,
            $vatPayableAccountId
        );
    }

    /**
     * Shared clearing-line math for file + rebuild.
     *
     * @return array{remaining: float, rows: list<array{account_id:int,debit:float,credit:float,narration:string}>}
     */
    private function buildVatReturnClearingLines(array $transactionIds, array $accountIds, int $vatPayableAccountId): array
    {
        $vatInAccountId = (int) $accountIds[0];
        $vatOutAccountId = (int) ($accountIds[1] ?? $accountIds[0]);

        $totals = Transactions::whereIn('id', $transactionIds)
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $rowVatIn = $totals->get($vatInAccountId);
        $rowVatOut = $totals->get($vatOutAccountId);
        $vatOnPurchases = (float) ($rowVatIn ? $rowVatIn->total_debit : 0);
        $vatOnSales = (float) ($rowVatOut ? $rowVatOut->total_credit : 0);
        $remaining = $vatOnSales - $vatOnPurchases;

        $rows = [
            [
                'account_id' => $vatOutAccountId,
                'debit' => $vatOnSales,
                'credit' => 0.0,
                'narration' => 'VAT on Sales',
            ],
            [
                'account_id' => $vatInAccountId,
                'debit' => 0.0,
                'credit' => $vatOnPurchases,
                'narration' => 'VAT on Purchases',
            ],
        ];

        if ($remaining >= 0) {
            $rows[] = [
                'account_id' => $vatPayableAccountId,
                'debit' => 0.0,
                'credit' => $remaining,
                'narration' => 'VAT Payable',
            ];
        } else {
            $rows[] = [
                'account_id' => $vatPayableAccountId,
                'debit' => abs($remaining),
                'credit' => 0.0,
                'narration' => 'VAT Payable (refund)',
            ];
        }

        return ['remaining' => $remaining, 'rows' => $rows];
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
     * Status is driven only by Payment allocations — manual toggle disabled.
     */
    public function updateReturnStatus(Request $request, $company_slug, VatReturn $vat_return)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        return redirect()->back()->with(
            'error',
            'Return payment status is updated only when payments are created or deleted. Use Pay / Edit Payment / Delete Payment.'
        );
    }

    /**
     * Remove selected entries from this return. Removed entries will show again in the VAT ledger.
     * Allowed only while the return has no live payment allocations; rebuilds clearing ledger afterwards.
     */
    public function deleteReturnEntries(Request $request, $company_slug, VatReturn $vat_return)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        if ($vat_return->hasLivePayments() || (int) $vat_return->status !== VatReturn::STATUS_UNPAID) {
            return redirect()
                ->route('vat.returns.show', $vat_return)
                ->with('error', 'Cannot remove entries while the return has payments. Edit or delete linked payments first.');
        }

        $request->validate([
            'entry_ids' => ['required', 'array'],
            'entry_ids.*' => ['integer', 'exists:vat_return_entries,id'],
        ]);

        $entryIds = array_map('intval', $request->input('entry_ids'));
        $remainingCount = VatReturnEntry::where('vat_return_id', $vat_return->id)
            ->whereNotIn('id', $entryIds)
            ->count();

        if ($remainingCount < 1) {
            return redirect()
                ->route('vat.returns.show', $vat_return)
                ->with('error', 'Cannot remove all entries. Delete the return instead.');
        }

        DB::beginTransaction();
        try {
            $deleted = VatReturnEntry::where('vat_return_id', $vat_return->id)
                ->whereIn('id', $entryIds)
                ->delete();

            $this->rebuildVatReturnLedger($vat_return->fresh());

            DB::commit();

            return redirect()
                ->route('vat.returns.show', $vat_return)
                ->with('success', $deleted . ' entry(ies) removed. Return ledger recalculated. They will appear again in the VAT ledger.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('vat.returns.show', $vat_return)
                ->with('error', 'Failed to remove entries: '.$e->getMessage());
        }
    }

    /**
     * Redirect to Payment create for VAT returns (invoice-style payments).
     */
    public function createVoucher(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $this->requireVatPayableAccountId();
        } catch (\RuntimeException $e) {
            return redirect()->route('vat.returns.index')->with('error', $e->getMessage());
        }

        $params = [
            'invoice_type' => 'vat_return',
            'vat_payment' => 1,
        ];
        if ($request->filled('vat_return_id')) {
            $params['invoice_id'] = (int) $request->input('vat_return_id');
        }

        return redirect()->route('payments.create', $params);
    }

    /**
     * Legacy VP store disabled — payments must go through PaymentController.
     */
    public function storeVoucher(Request $request)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        return redirect()
            ->route('vat.returns.index')
            ->with('error', 'VAT payments must be created via the Payments module against a VAT return.');
    }

    /**
     * Soft-delete an unpaid VAT return (+ cascade clearing ledger). Blocked while payments exist.
     */
    public function destroyReturn(Request $request, $company_slug, VatReturn $vat_return)
    {
        if (!user_can('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        if ($vat_return->hasLivePayments() || in_array((int) $vat_return->status, [VatReturn::STATUS_PAID, VatReturn::STATUS_PARTIAL], true)) {
            $message = 'Cannot delete this return while it has linked payments. Edit or delete the payments first.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        DB::beginTransaction();
        try {
            if (Schema::hasColumn($vat_return->getTable(), 'deleted_by')) {
                $vat_return->deleted_by = auth()->id();
                $vat_return->save();
            }

            // Soft-delete return (Delete Request interceptor may queue approval).
            $vat_return->delete();

            $deleteRequest = request()->attributes->get('delete_approval_request')
                ?? \App\Services\DeleteRequestService::lastCreatedFor($vat_return);

            if ($deleteRequest) {
                $clearingTxns = Transactions::query()
                    ->where('reference_type', VatReturn::LEDGER_REFERENCE_TYPE)
                    ->where('reference_id', $vat_return->id)
                    ->get();
                foreach ($clearingTxns as $txn) {
                    $deleteRequest->appendCascadedRecord(
                        Transactions::class,
                        $txn->id,
                        "Transaction #{$txn->id}"
                    );
                }
            } else {
                // Approval off / bypass: soft-delete clearing ledger immediately.
                $vat_return->softDeleteLinkedClearing(auth()->id());
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $message = 'Failed to delete return: '.$e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return redirect()->back()->with('error', $message);
        }

        $deleteRequest = request()->attributes->get('delete_approval_request')
            ?? \App\Services\DeleteRequestService::lastCreatedFor($vat_return);
        if ($deleteRequest && ! $vat_return->trashed()) {
            $message = \App\Services\DeleteRequestService::pendingMessage($deleteRequest);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'delete_request_id' => $deleteRequest->id,
                    'pending_deletion' => true,
                    'reload' => true,
                ]);
            }

            return redirect()->route('vat.returns.index')->with('warning', $message);
        }

        $message = 'Return deleted. Entries are visible again in the VAT ledger.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message, 'reload' => true]);
        }

        return redirect()->route('vat.returns.index')->with('success', $message);
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
            ->whereNotIn('id', VatReturnEntry::filedTransactionIdsQuery())
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
            'Salik', 'salik', 'Salik Voucher', 'Salik Payment' => 'Salik',
            'RTA', 'RTA FINE', 'RTA_FINE' => 'RTA Fine',
            'Invoice', 'RiderInvoice', 'CI' => 'Customer Invoice',
            'LeasingCompanyInvoice' => 'Leasing',
            'Bike Maintenance', 'SUP' => 'Supplier / Garage',
            'LV' => 'Visa Expense',
            'SIM', 'SimInvoice' => 'SIM',
            'Fuel', 'fuel', 'FuelInvoice' => 'Fuel',
            'LeasingCompanyBillingInvoice', 'RentalBilling', 'Rental Invoice', 'bike Rental Invoice' => 'Rental Billing',
            'EmployeeInvoice' => 'Employee Invoice',
            VatReturn::LEDGER_REFERENCE_TYPE => 'VAT Return',
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
            'Salik' => ['Salik', 'salik', 'Salik Voucher', 'Salik Payment'],
            'RTA Fine' => ['RTA', 'RTA FINE', 'RTA_FINE'],
            'Customer Invoice' => ['Invoice', 'RiderInvoice', 'CI'],
            'Leasing' => ['LeasingCompanyInvoice'],
            'Supplier / Garage' => ['Bike Maintenance', 'SUP'],
            'Visa Expense' => ['LV'],
            'SIM' => ['SIM', 'SimInvoice'],
            'Fuel' => ['Fuel', 'fuel', 'FuelInvoice'],
            'Rental Billing' => ['LeasingCompanyBillingInvoice', 'RentalBilling', 'Rental Invoice', 'bike Rental Invoice'],
            'Employee Invoice' => ['EmployeeInvoice'],
            'VAT Return' => [VatReturn::LEDGER_REFERENCE_TYPE],
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
     * @return array<string, mixed>
     */
    private function buildLedgerEnrichmentMaps($transactions): array
    {
        $maintenanceIds = [];
        $rtaFineIds = [];
        $visaExpenseIds = [];
        $fuelIds = [];
        $salikIds = [];
        $customerInvoiceIds = [];
        $supplierInvoiceIds = [];
        $simInvoiceIds = [];
        $employeeInvoiceIds = [];
        $billingInvoiceIds = [];
        $vatReturnIds = [];

        foreach ($transactions as $row) {
            $type = (string) ($row->reference_type ?? '');
            $refId = $row->reference_id ? (int) $row->reference_id : 0;
            if ($type === 'Bike Maintenance' && $refId) {
                $maintenanceIds[] = $refId;
            }
            if (in_array($type, ['RTA', 'RTA FINE', 'RTA_FINE'], true)) {
                if ($refId) {
                    $rtaFineIds[] = $refId;
                }
                $voucher = $row->voucher;
                if ($voucher && !empty($voucher->ref_id)) {
                    $rtaFineIds[] = (int) $voucher->ref_id;
                }
            }
            if ($type === 'LV' && $refId) {
                $visaExpenseIds[] = $refId;
            }
            if (in_array($type, ['fuel', 'Fuel', 'FuelInvoice'], true) && $refId) {
                $fuelIds[] = $refId;
            }
            if (in_array($type, ['salik', 'Salik'], true) && $refId) {
                $salikIds[] = $refId;
            }
            if ($type === 'CI' && $refId) {
                $customerInvoiceIds[] = $refId;
            }
            if ($type === 'SUP' && $refId) {
                $supplierInvoiceIds[] = $refId;
            }
            if ($type === 'SimInvoice' && $refId) {
                $simInvoiceIds[] = $refId;
            }
            if ($type === 'EmployeeInvoice' && $refId) {
                $employeeInvoiceIds[] = $refId;
            }
            if ($type === VatReturn::LEDGER_REFERENCE_TYPE && $refId) {
                $vatReturnIds[] = $refId;
            }
            if (in_array($type, ['LeasingCompanyBillingInvoice', 'RentalBilling', 'Rental Invoice', 'bike Rental Invoice'], true) && $refId) {
                $billingInvoiceIds[] = $refId;
            }
        }

        $maintenances = empty($maintenanceIds)
            ? collect()
            : BikeMaintenance::whereIn('id', array_values(array_unique($maintenanceIds)))->get()->keyBy('id');

        $rtaFines = empty($rtaFineIds)
            ? collect()
            : collect(CompanyQuery::table('rta_fines')->whereIn('id', array_values(array_unique($rtaFineIds)))->get())
                ->keyBy('id');

        $visaExpenses = empty($visaExpenseIds)
            ? collect()
            : collect(CompanyQuery::table('visa_expenses')->whereIn('id', array_values(array_unique($visaExpenseIds)))->get())
                ->keyBy('id');

        $fuels = empty($fuelIds)
            ? collect()
            : FuelData::withTrashed()->whereIn('id', array_values(array_unique($fuelIds)))->get()->keyBy('id');

        $saliks = empty($salikIds)
            ? collect()
            : salik::withTrashed()->whereIn('id', array_values(array_unique($salikIds)))->get()->keyBy('id');

        $customerInvoices = empty($customerInvoiceIds)
            ? collect()
            : CustomerInvoices::whereIn('id', array_values(array_unique($customerInvoiceIds)))->get()->keyBy('id');

        $supplierInvoices = empty($supplierInvoiceIds)
            ? collect()
            : SupplierInvoices::whereIn('id', array_values(array_unique($supplierInvoiceIds)))->get()->keyBy('id');

        $simInvoices = empty($simInvoiceIds)
            ? collect()
            : SimInvoice::whereIn('id', array_values(array_unique($simInvoiceIds)))->get()->keyBy('id');

        $employeeInvoices = empty($employeeInvoiceIds)
            ? collect()
            : EmployeeInvoices::whereIn('id', array_values(array_unique($employeeInvoiceIds)))->get()->keyBy('id');

        $vatReturns = empty($vatReturnIds)
            ? collect()
            : VatReturn::whereIn('id', array_values(array_unique($vatReturnIds)))->get()->keyBy('id');

        $billingInvoices = empty($billingInvoiceIds)
            ? collect()
            : collect(CompanyQuery::table('leasing_company_billing_invoices')->whereIn('id', array_values(array_unique($billingInvoiceIds)))->get())
                ->keyBy('id');

        return [
            'maintenances' => $maintenances,
            'rta_fines' => $rtaFines,
            'visa_expenses' => $visaExpenses,
            'visa_names' => $this->resolveVisaExpensePersonNamesBulk($visaExpenses),
            'fuels' => $fuels,
            'saliks' => $saliks,
            'customer_invoices' => $customerInvoices,
            'supplier_invoices' => $supplierInvoices,
            'sim_invoices' => $simInvoices,
            'employee_invoices' => $employeeInvoices,
            'vat_returns' => $vatReturns,
            'billing_invoices' => $billingInvoices,
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

    /**
     * Build an attachment link for the ledger Attachment column.
     */
    private function getAttachmentHtml($row, array $enrichment = []): string
    {
        $type = (string) ($row->reference_type ?? '');
        $refId = (int) ($row->reference_id ?? 0);
        $path = null;

        if (in_array($type, ['RTA', 'RTA FINE', 'RTA_FINE'], true)) {
            if (!empty($row->voucher?->attach_file)) {
                $path = (string) $row->voucher->attach_file;
            } else {
                $fineId = $refId;
                if (!$fineId && !empty($row->voucher?->ref_id)) {
                    $fineId = (int) $row->voucher->ref_id;
                }
                $fine = $fineId ? ($enrichment['rta_fines'] ?? collect())->get($fineId) : null;
                if ($fine) {
                    $path = $fine->attachment ?: ($fine->attachment_path ?? null);
                }
            }
        } elseif ($type === 'Bike Maintenance') {
            $maintenance = ($enrichment['maintenances'] ?? collect())->get($refId);
            $path = $maintenance->attachment ?? null;
        } elseif ($type === 'CI') {
            $invoice = ($enrichment['customer_invoices'] ?? collect())->get($refId);
            $path = $invoice->attachment ?? null;
        } elseif ($type === 'SUP') {
            $invoice = ($enrichment['supplier_invoices'] ?? collect())->get($refId);
            $path = $invoice->attachment ?? null;
        } elseif ($type === 'SimInvoice') {
            $invoice = ($enrichment['sim_invoices'] ?? collect())->get($refId);
            $path = $invoice->attachment ?? null;
        } elseif ($type === 'EmployeeInvoice') {
            $invoice = ($enrichment['employee_invoices'] ?? collect())->get($refId);
            $path = $invoice->attachment ?? null;
        } elseif (in_array($type, ['fuel', 'Fuel', 'FuelInvoice'], true)) {
            $invoice = ($enrichment['fuels'] ?? collect())->get($refId);
            $path = $invoice->attachment ?? null;
        } elseif (in_array($type, ['LeasingCompanyBillingInvoice', 'RentalBilling', 'Rental Invoice', 'bike Rental Invoice'], true)) {
            $invoice = ($enrichment['billing_invoices'] ?? collect())->get($refId);
            $path = $invoice->attachment ?? null;
        } elseif (!empty($row->voucher?->attach_file)) {
            $path = (string) $row->voucher->attach_file;
            // Legacy voucher files were stored under storage/vouchers/
            if (!in_array($type, ['LV', 'LE', 'VR'], true)) {
                $url = storage_url($path) ?: storage_url('vouchers/' . ltrim($path, '/'));
                if ($url) {
                    return '<a href="' . e($url) . '" class="no-print" target="_blank">View File</a>';
                }
                return '<a href="' . e(url('storage/vouchers/' . ltrim($path, '/'))) . '" class="no-print" target="_blank">View File</a>';
            }
        }

        if ($path === null || $path === '') {
            return '';
        }

        $url = storage_url((string) $path);
        if (!$url) {
            $url = url('storage/' . ltrim((string) $path, '/'));
        }

        return '<a href="' . e($url) . '" class="no-print" target="_blank">View File</a>';
    }

    private function getVoucherText($row, array $enrichment = []): string
    {
        $type = (string) ($row->reference_type ?? '');
        $refId = (int) ($row->reference_id ?? 0);

        $voucherTypes = [
            'Voucher', 'RTA', 'LV', 'VL', 'LE', 'IL', 'FAV', 'FDV', 'BL', 'VR',
            'INC', 'PN', 'PAY', 'COD', 'Salik Voucher', 'Salik Payment', 'VC', 'AL',
            'RiderInvoice', 'PV', 'RV',
        ];
        if (in_array($type, $voucherTypes, true)) {
            $vouchers = $row->voucher;
            if ($vouchers) {
                $voucherId = $vouchers->voucher_type . '-' . str_pad((string) $vouchers->id, 4, '0', STR_PAD_LEFT);
                return '<a href="javascript:void(0);" data-title="Voucher # ' . e($voucherId) . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="show-modal">' . e($voucherId) . '</a>';
            }
            // RTA charge lines often have no voucher ? link the fine itself.
            if (in_array($type, ['RTA', 'RTA FINE', 'RTA_FINE'], true) && $refId) {
                return '<a href="javascript:void(0);" data-action="' . route('rtaFines.show', $refId) . '" data-size="xl" class="show-modal-right">RTA-' . $refId . '</a>';
            }
            return '<span class="text-danger">No Voucher Found</span>';
        }

        if (in_array($type, ['RTA FINE', 'RTA_FINE'], true) && $refId) {
            return '<a href="javascript:void(0);" data-action="' . route('rtaFines.show', $refId) . '" data-size="xl" class="show-modal-right">RTA FINE</a>';
        }

        if ($type === 'Invoice' && $refId) {
            $label = 'RINV-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT);
            return '<a href="javascript:void(0);" data-action="' . route('riderInvoices.show', $refId) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
        }

        if ($type === 'Bike Maintenance') {
            $maintenance = ($enrichment['maintenances'] ?? collect())->get($refId);
            if ($maintenance) {
                $label = 'MA-' . $maintenance->id;

                return '<a href="javascript:void(0);" data-action="' . route('bike-maintenance.invoice', $maintenance) . '" data-size="xl" data-title="Maintenance ' . e($label) . '" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">Maintenance record not found</span>';
        }

        if ($type === 'LeasingCompanyInvoice' && $refId) {
            $label = 'LI-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT);
            return '<a href="javascript:void(0);" data-action="' . route('leasingCompanyInvoices.show', $refId) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
        }

        if (in_array($type, ['LeasingCompanyBillingInvoice', 'RentalBilling', 'Rental Invoice', 'bike Rental Invoice'], true) && $refId) {
            $invoice = ($enrichment['billing_invoices'] ?? collect())->get($refId);
            $label = $invoice->invoice_number ?? ('RBI-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT));
            return '<a href="javascript:void(0);" data-action="' . route('leasingCompanyBillingInvoices.show', $refId) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
        }

        if ($type === 'CI' && $refId) {
            $invoice = ($enrichment['customer_invoices'] ?? collect())->get($refId);
            if ($invoice) {
                $label = $invoice->invoice_number ?: ('CI-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT));
                return '<a href="javascript:void(0);" data-action="' . route('customer_invoices.show', $invoice->id) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">Customer invoice not found</span>';
        }

        if ($type === 'SUP' && $refId) {
            $invoice = ($enrichment['supplier_invoices'] ?? collect())->get($refId);
            if ($invoice) {
                $label = $invoice->invoice_number ?: ('SUP-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT));
                return '<a href="javascript:void(0);" data-action="' . route('supplierInvoices.show', $invoice->id) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">Supplier invoice not found</span>';
        }

        if ($type === 'SimInvoice' && $refId) {
            $invoice = ($enrichment['sim_invoices'] ?? collect())->get($refId);
            if ($invoice) {
                $label = $invoice->invoice_number ?: ('SIM-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT));
                return '<a href="javascript:void(0);" data-action="' . route('simInvoices.show', $invoice->id) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">SIM invoice not found</span>';
        }

        if ($type === 'EmployeeInvoice' && $refId) {
            $invoice = ($enrichment['employee_invoices'] ?? collect())->get($refId);
            if ($invoice) {
                $label = $invoice->invoice_number ?: ('EMP-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT));
                return '<a href="javascript:void(0);" data-action="' . route('employeeInvoices.show', $invoice->id) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">Employee invoice not found</span>';
        }

        if ($type === VatReturn::LEDGER_REFERENCE_TYPE && $refId) {
            $vatReturn = ($enrichment['vat_returns'] ?? collect())->get($refId);
            if ($vatReturn) {
                $label = $vatReturn->invoice_number;
                return '<a href="javascript:void(0);" data-action="' . route('vat.returns.show', $vatReturn) . '" data-size="xl" data-title="VAT Return ' . e($label) . '" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">VAT return not found</span>';
        }

        if (in_array($type, ['fuel', 'Fuel', 'FuelInvoice'], true) && $refId) {
            $invoice = ($enrichment['fuels'] ?? collect())->get($refId);
            if ($invoice) {
                $label = $invoice->inv_id ?: ('FUEL-' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT));
                return '<a href="javascript:void(0);" data-action="' . route('fuel_data.show', $invoice->id) . '" data-size="xl" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">Fuel invoice not found</span>';
        }

        if (in_array($type, ['salik', 'Salik'], true) && $refId) {
            $salikRecord = ($enrichment['saliks'] ?? collect())->get($refId);
            if ($salikRecord && $salikRecord->billing_month && ($salikRecord->rider_id || $salikRecord->rental_company_id)) {
                $label = $salikRecord->inv_id ?: ('SLK-' . str_pad((string) $salikRecord->id, 4, '0', STR_PAD_LEFT));
                $billingMonth = Carbon::parse($salikRecord->billing_month)->format('Y-m');
                $invoiceUrl = $salikRecord->rider_id
                    ? route('salik.rider_monthly_summary', [$salikRecord->rider_id, $billingMonth])
                    : route('salik.company_monthly_summary', [$salikRecord->rental_company_id, $billingMonth]);
                return '<a href="javascript:void(0);" data-action="' . $invoiceUrl . '" data-size="xl" data-title="Salik Invoice ' . e($label) . '" class="show-modal-right">' . e($label) . '</a>';
            }
            return '<span class="text-danger">Salik invoice not found</span>';
        }

        return '';
    }

    private function getNarration($row, array $enrichment = []): string
    {
        if ($row->reference_type === 'RTA') {
            $voucher = $row->voucher;
            $fineId = !empty($voucher?->ref_id) ? (int) $voucher->ref_id : (int) ($row->reference_id ?? 0);
            if ($fineId) {
                $fines = ($enrichment['rta_fines'] ?? collect())->get($fineId);
                if ($fines) {
                    $trip = !empty($fines->trip_date) ? Carbon::parse($fines->trip_date)->format('d M Y') : '';
                    return $row->narration . ', <b>Ticket Number: </b>' . e($fines->ticket_no ?? '') . ', <b>Bike No: </b>' . e($fines->plate_no ?? '') . ($trip !== '' ? ', ' . $trip : '');
                }
            }
            return (string) $row->narration;
        }
        if ($row->reference_type === 'LV') {
            $visaex = ($enrichment['visa_expenses'] ?? collect())->get((int) $row->reference_id);
            if ($visaex) {
                $personName = ($enrichment['visa_names'] ?? [])[(int) $row->reference_id] ?? null;
                if ($personName) {
                    return 'Paid to <b>' . e($personName) . ' </b>' . e($visaex->visa_status ?? '') . ' Charges ' . e($visaex->date ?? '');
                }
                return $row->narration . ' (Rider not found)';
            }
            return $row->narration . ' (Visa expense not found)';
        }
        return (string) $row->narration;
    }
}

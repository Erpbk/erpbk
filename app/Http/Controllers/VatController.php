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
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $accountIds = VatSettingsController::getVatAccountIds();
        $quarters = VatSettingsController::getQuartersForDropdown();
        $years = $this->getYearsForDropdown();
        $rows = [];

        if (!empty($accountIds)) {
            $query = Transactions::with(['account', 'voucher'])
                ->whereIn('account_id', $accountIds)
                ->whereNotIn('id', VatReturnEntry::query()->select('transaction_id'));

            $quarterSlot = $request->input('vat_quarter_slot');
            $year = $request->input('vat_year');

            // Filter by year when selected (quarters are always in the context of the selected year)
            if ($year !== null && $year !== '') {
                $query->whereYear('billing_month', $year);
                // If a quarter is selected: include that quarter AND all previous quarters in the year,
                // so unfiled entries from earlier quarters appear and can be filed in the next return.
                if ($quarterSlot) {
                    $months = $this->getMonthsThroughQuarter((int) $quarterSlot);
                    if (!empty($months)) {
                        $query->whereRaw('MONTH(billing_month) IN (' . implode(',', array_map('intval', $months)) . ')');
                    }
                }
            }

            $query->orderBy('billing_month', 'ASC')->orderBy('trans_date', 'ASC');
            $transactions = $query->get();
            $openingBalance = $this->getOpeningBalance($accountIds, $quarterSlot ?: null, $year ?: null);
            $runningBalance = $openingBalance;
            $totalDebit = 0;
            $totalCredit = 0;

            $rows[] = [
                'transaction_id' => null,
                'date' => '',
                'account_name' => '',
                'reference_number' => '',
                'billing_month' => '',
                'voucher' => '',
                'narration' => 'Balance Forward',
                'debit' => '',
                'credit' => '',
                'balance' => $this->formatBalance($openingBalance),
                'is_total' => false,
                'is_balance_forward' => true,
            ];

            foreach ($transactions as $row) {
                $runningBalance += $row->debit - $row->credit;
                $totalDebit += $row->debit;
                $totalCredit += $row->credit;

                $viewFile = $this->getViewFile($row);
                $rows[] = [
                    'transaction_id' => $row->id,
                    'date' => Common::DateFormat($row->trans_date),
                    'account_name' => $row->account ? ($row->account->account_code . '-' . $row->account->name) : 'N/A',
                    'reference_number' => $row->voucher ? ($row->voucher->reference_number ?? '-') : '-',
                    'billing_month' => $row->billing_month ? date('M Y', strtotime($row->billing_month)) : '',
                    'voucher' => $this->getVoucherText($row),
                    'narration' => $this->getNarration($row, $viewFile),
                    'debit' => number_format($row->debit, 2),
                    'credit' => number_format($row->credit, 2),
                    'balance' => $this->formatBalance($runningBalance),
                    'is_total' => false,
                    'is_balance_forward' => false,
                ];
            }

            $rows[] = [
                'transaction_id' => null,
                'date' => '',
                'account_name' => '',
                'reference_number' => '',
                'billing_month' => '',
                'voucher' => '',
                'narration' => 'Total',
                'debit' => number_format($totalDebit, 2),
                'credit' => number_format($totalCredit, 2),
                'balance' => $this->formatBalance($runningBalance),
                'is_total' => true,
                'is_balance_forward' => false,
            ];
        }

        $vpVouchers = Vouchers::where('voucher_type', 'VP')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('vat.index', compact('rows', 'quarters', 'years', 'vpVouchers'));
    }

    /**
     * Show list of VAT returns (Paid and Unpaid).
     */
    public function returnsIndex()
    {
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $paidReturns = VatReturn::withCount('entries')
            ->where('status', 'paid')
            ->orderByDesc('filed_at')
            ->get();

        $unpaidReturns = VatReturn::withCount('entries')
            ->where('status', 'unpaid')
            ->orderByDesc('filed_at')
            ->get();

        $vpVouchers = Vouchers::where('voucher_type', 'VP')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('vat.returns', compact('paidReturns', 'unpaidReturns', 'vpVouchers'));
    }

    /**
     * Show a single VAT return with its entries (both accounts) and option to make payment.
     */
    public function returnsShow($company_slug, VatReturn $vat_return)
    {
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
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
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
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

        if (!VoucherType::isCodeAllowedForModule('VV', 'vat')) {
            return redirect()->route('vat.index')->with('error', 'VAT Return voucher type (VV) is not assigned to the VAT module. Please assign it in Voucher Settings.');
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
                ->with('success', 'VAT return filed for ' . $quarterLabel . ' ' . $year . ' with ' . count($transactionIds) . ' entries. Voucher VV created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('vat.index')->with('error', 'Failed to file VAT return: ' . $e->getMessage());
        }
    }

    /**
     * Create a VV (VAT Return) voucher when filing a return. Exactly 3 entries:
     * 1. Debit: VAT on Sales (1025)
     * 2. Credit: VAT on Purchases (1023)
     * 3. Credit (or Debit if refund): VAT Payable (1027) = VAT on Sales − VAT on Purchases
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
            'voucher_type' => 'VV',
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
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
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
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
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
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
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
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
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
     * When the return is unpaid, the associated VV voucher (ref_id + voucher_type) is also deleted.
     */
    public function destroyReturn($company_slug, VatReturn $vat_return)
    {
        if (!auth()->user()->hasPermissionTo('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        if ($vat_return->status === 'unpaid') {
            $vvVouchers = Vouchers::where('voucher_type', 'VV')
                ->where('ref_id', $vat_return->id)
                ->get();
            foreach ($vvVouchers as $voucher) {
                Transactions::where('trans_code', $voucher->trans_code)->delete();
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

    private function getVoucherText($row): string
    {
        $voucherTypes = ['Voucher', 'RTA', 'LV', 'VL', 'INC', 'PN', 'PAY', 'COD', 'Salik Voucher', 'VC', 'AL', 'RiderInvoice'];
        if (in_array($row->reference_type, $voucherTypes)) {
            $vouchers = \App\Support\CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
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
            $maintenance = BikeMaintenance::where('id', $row->reference_id)->first();
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

    private function getNarration($row, string $viewFile): string
    {
        if ($row->reference_type === 'RTA') {
            $vouchers = \App\Support\CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
            if ($vouchers) {
                $fines = \App\Support\CompanyQuery::table('rta_fines')->where('id', $vouchers->ref_id)->first();
                if ($fines) {
                    return $row->narration . ', <b>Ticket Number: </b>' . $fines->ticket_no . ', <b>Bike No: </b>' . $fines->plate_no . ', ' . \Carbon\Carbon::parse($fines->trip_date)->format('d M Y') . ', ' . $viewFile;
                }
            }
            return $row->narration . ', ' . $viewFile;
        }
        if ($row->reference_type === 'LV') {
            $visaex = \App\Support\CompanyQuery::table('visa_expenses')->where('id', $row->reference_id)->first();
            if ($visaex) {
                $rider = \App\Support\CompanyQuery::table('accounts')->where('id', $visaex->rider_id)->first();
                if ($rider) {
                    return 'Paid to <b>' . $rider->name . ' </b>' . $visaex->visa_status . ' Charges ' . $visaex->date . $viewFile;
                }
                return $row->narration . ' (Rider not found) ' . $viewFile;
            }
            return $row->narration . ' (Visa expense not found) ' . $viewFile;
        }
        return $row->narration . ', ' . $viewFile;
    }
}

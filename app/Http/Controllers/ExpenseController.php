<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\HeadAccount;
use App\Http\Requests\CreateAccountsRequest;
use App\Http\Requests\UpdateAccountsRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\AccountCustomField;
use App\Models\Accounts;
use App\Models\ExpenseAccount;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Repositories\AccountsRepository;
use App\Traits\GlobalPagination;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Flash;

class ExpenseController extends AppBaseController
{
    use GlobalPagination;

    public function __construct(AccountsRepository $accountsRepo)
    {
        $this->accountsRepository = $accountsRepo;
    }

    /** @var AccountsRepository */
    private $accountsRepository;

    /**
     * Get expense account IDs (from expense_accounts table).
     */
    private function getExpenseAccountIds(): array
    {
        return ExpenseAccount::pluck('account_id')->all();
    }

    /**
     * Build children collection for a parent from a keyed collection of all accounts.
     * Only includes accounts that are in the expense account IDs set.
     */
    private function buildExpenseChildren($parentId, $all, array $expenseIds): \Illuminate\Support\Collection
    {
        return $all->where('parent_id', $parentId)
            ->filter(fn($a) => in_array($a->id, $expenseIds, true))
            ->sortBy('account_code')
            ->values()
            ->map(function ($child) use ($all, $expenseIds) {
                $child->setRelation('children', $this->buildExpenseChildren($child->id, $all, $expenseIds));
                return $child;
            });
    }

    /**
     * Flatten account tree into a list with depth for hierarchical display.
     */
    private function flattenAccountTree($nodes, $search = null, $depth = 0): \Illuminate\Support\Collection
    {
        $result = collect();
        foreach ($nodes as $account) {
            $match = !$search
                || stripos($account->name, $search) !== false
                || stripos((string)($account->account_code ?? ''), $search) !== false
                || stripos((string)($account->account_type ?? ''), $search) !== false;
            if ($match) {
                $result->push((object)['account' => $account, 'depth' => $depth]);
            }
            $children = $account->relationLoaded('children') ? $account->children : collect();
            $result = $result->merge($this->flattenAccountTree($children, $search, $depth + 1));
        }
        return $result;
    }

    /**
     * Display Expense vouchers (voucher_type = 'EXP').
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = Vouchers::where('voucher_type', 'EXP')->orderBy('id', 'desc');

        if ($request->has('voucher_id') && !empty($request->voucher_id)) {
            $voucherId = $request->voucher_id;
            $query->where(function ($q) use ($voucherId) {
                $q->whereRaw("CONCAT('EXP-', LPAD(id, 4, '0')) LIKE ?", ["%{$voucherId}%"])
                    ->orWhere('id', 'like', "%{$voucherId}%");
            });
        }

        if ($request->has('trans_date') && !empty($request->trans_date)) {
            $query->whereDate('trans_date', $request->trans_date);
        }

        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $billingMonth = Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }

        if ($request->has('created_by') && !empty($request->created_by)) {
            $query->where('Created_By', $request->created_by);
        }

        if ($request->filled('quick_search')) {
            $search = $request->input('quick_search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT('EXP-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"])
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            $tableData = view('expenses.table', ['data' => $data])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('expenses.index', ['data' => $data]);
    }

    /**
     * Return expense voucher list fragment for the left sidebar.
     */
    public function listSidebar(Request $request)
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Vouchers::where('voucher_type', 'EXP')->orderBy('id', 'desc');

        if ($request->filled('quick_search')) {
            $search = $request->input('quick_search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT('EXP-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"])
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        $data = $query->paginate(20);

        return view('expenses.list_sidebar', ['data' => $data]);
    }

    /**
     * Display the specified expense voucher.
     */
    public function showVoucher($id)
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }

        $voucher = Vouchers::where('voucher_type', 'EXP')
            ->with(['transactions.account'])
            ->findOrFail($id);

        return view('expenses.show_modal', compact('voucher'));
    }

    /**
     * Show the form for creating a new expense account (same as Chart of Accounts).
     * Parent dropdown shows only expense-related accounts.
     */
    public function create()
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }
        $expenseIds = $this->getExpenseAccountIds();
        $parents = Accounts::whereIn('id', $expenseIds)->get(['id', 'name', 'parent_id'])->groupBy('parent_id');
        $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();
        $accounts = null;

        return view('expenses.create', compact('parents', 'customFields', 'accounts'));
    }

    /**
     * Store a newly created expense account (same as Chart of Accounts, then link to expense_accounts).
     */
    public function store(CreateAccountsRequest $request)
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }
        $input = $request->except(['custom_field_values']);
        $input['account_type'] = 'Expense';

        $account = $this->accountsRepository->create($input);
        $account->account_code = $account->account_code ?: str_pad($account->id, 4, '0', STR_PAD_LEFT);
        $account->is_locked = 0;
        $account->save();

        $this->saveCustomFieldValues($account, $request->input('custom_field_values', []));

        ExpenseAccount::firstOrCreate(
            ['account_id' => $account->id],
            ['account_id' => $account->id]
        );

        return response()->json(['message' => 'Expense account added successfully.']);
    }

    /**
     * Show the form for editing the specified expense account.
     */
    public function edit($id)
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }
        $expenseIds = $this->getExpenseAccountIds();
        if (!in_array((int) $id, $expenseIds, true)) {
            Flash::error('Expense account not found.');
            return redirect(route('expenses.index'));
        }
        $accounts = $this->accountsRepository->find($id);
        if (empty($accounts)) {
            Flash::error('Account not found.');
            return redirect(route('expenses.index'));
        }
        /** Only expense accounts for parent dropdown */
        $parents = Accounts::whereIn('id', $expenseIds)->get(['id', 'name', 'parent_id'])->groupBy('parent_id');
        $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();

        return view('expenses.edit', compact('accounts', 'parents', 'customFields'));
    }

    /**
     * Update the specified expense account in storage.
     */
    public function update($id, UpdateAccountsRequest $request)
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }
        $expenseIds = $this->getExpenseAccountIds();
        if (!in_array((int) $id, $expenseIds, true)) {
            return response()->json(['errors' => ['error' => 'Expense account not found.']], 422);
        }
        $accounts = $this->accountsRepository->find($id);
        if (empty($accounts)) {
            return response()->json(['errors' => ['error' => 'Account not found.']], 422);
        }

        $input = $request->except(['custom_field_values']);
        $accounts = $this->accountsRepository->update($input, $id);

        if ($accounts) {
            $this->saveCustomFieldValues($accounts, $request->input('custom_field_values', []));
            $row = \App\Helpers\Accounts::getRef(['ref_name' => $accounts->ref_name, 'ref_id' => $accounts->ref_id]);
            if (isset($row)) {
                $row->name = $accounts->name;
                $row->account_code = $accounts->account_code;
                $row->status = $accounts->status;
                $row->save();
            }
        }

        return response()->json(['message' => 'Expense account updated successfully.']);
    }

    /**
     * Remove the specified expense account (soft delete account; expense_accounts row removed by cascade or we remove it).
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('expenses_view')) {
            abort(403, 'Unauthorized action.');
        }
        $expenseIds = $this->getExpenseAccountIds();
        if (!in_array((int) $id, $expenseIds, true)) {
            return response()->json(['errors' => ['error' => 'Expense account not found.']], 422);
        }
        $account = $this->accountsRepository->find($id);
        if (empty($account)) {
            return response()->json(['errors' => ['error' => 'Account not found!']], 422);
        }

        $childCount = Accounts::where('parent_id', $account->id)->whereIn('id', $expenseIds)->count();
        if ($childCount > 0) {
            return response()->json(['errors' => ['error' => "Cannot delete account. This account has {$childCount} sub-account(s). Please delete or reassign child accounts first."]], 422);
        }

        $transactionsCount = Transactions::where('account_id', $account->id)->count();
        if ($transactionsCount > 0) {
            return response()->json(['errors' => ['error' => "Cannot delete account. This account has {$transactionsCount} transaction(s). Accounts with transactions cannot be deleted."]], 422);
        }

        $ledgerEntriesCount = DB::table('ledger_entries')
            ->where('account_id', $account->id)
            ->count();
        if ($ledgerEntriesCount > 0) {
            return response()->json(['errors' => ['error' => "Cannot delete account. This account has {$ledgerEntriesCount} ledger entry(ies). Please clear these first."]], 422);
        }

        ExpenseAccount::where('account_id', $account->id)->delete();
        $account->delete();

        return response()->json(['message' => 'Expense account deleted successfully.']);
    }

    /**
     * Account detail panel (AJAX) - reuse accounts detail.
     */
    public function accountDetail(Request $request, $id)
    {
        $expenseIds = $this->getExpenseAccountIds();
        if (!in_array((int) $id, $expenseIds, true)) {
            return response()->json(['error' => 'Expense account not found.'], 404);
        }
        return app(AccountsController::class)->accountDetail($request, $id);
    }

    /**
     * Ledger entries for an expense account (AJAX).
     */
    public function ledgerEntries(Request $request, $id)
    {
        $expenseIds = $this->getExpenseAccountIds();
        if (!in_array((int) $id, $expenseIds, true)) {
            return response()->json(['error' => 'Expense account not found.'], 404);
        }
        return app(AccountsController::class)->ledgerEntries($request, $id);
    }

    /**
     * Toggle lock status for an expense account (AJAX).
     */
    public function toggleLock(Request $request, $id)
    {
        $expenseIds = $this->getExpenseAccountIds();
        if (!in_array((int) $id, $expenseIds, true)) {
            return response()->json(['error' => 'Expense account not found.'], 404);
        }
        return app(AccountsController::class)->toggleLock($request, $id);
    }

    /**
     * Toggle active status for an expense account (AJAX).
     */
    public function toggleStatus(Request $request, $id)
    {
        $expenseIds = $this->getExpenseAccountIds();
        if (!in_array((int) $id, $expenseIds, true)) {
            return response()->json(['error' => 'Expense account not found.'], 404);
        }
        return app(AccountsController::class)->toggleStatus($request, $id);
    }

    private function saveCustomFieldValues(Accounts $account, array $values): void
    {
        $validIds = AccountCustomField::pluck('id')->flip()->all();
        $filtered = [];
        foreach ($values as $fieldId => $value) {
            if (isset($validIds[$fieldId])) {
                $filtered[(string) $fieldId] = $value;
            }
        }
        $account->custom_field_values = $filtered;
        $account->save();
    }

    /**
     * Show the form for creating a new expense voucher.
     */
    public function createVoucher()
    {
        if (!auth()->user()->can('expenses_create')) {
            abort(403, 'Unauthorized action.');
        }

        $expenseIds = $this->getExpenseAccountIds();
        $accounts = Accounts::whereIn('id', $expenseIds)
            ->get(['id', 'name', 'parent_id']);

        $expenseAccounts = [];
        foreach ($accounts as $account) {
            $parentKey = $account->parent_id;
            if ($parentKey === null || !in_array((int) $parentKey, $expenseIds, true)) {
                $parentKey = '_root_';
            }
            if (!isset($expenseAccounts[$parentKey])) {
                $expenseAccounts[$parentKey] = [];
            }
            $expenseAccounts[$parentKey][] = $account;
        }

        $bankCashAccounts = Accounts::bankAndCashDropdown();

        return view('expenses.voucher_create', compact('expenseAccounts', 'bankCashAccounts'));
    }

    /**
     * Store a newly created expense voucher.
     */
    public function storeVoucher(Request $request)
    {
        if (!auth()->user()->can('expenses_create')) {
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
        ]);

        $expenseIds = $this->getExpenseAccountIds();
        $debitAccounts = $request->input('debit_account_id');
        $creditAccountId = $request->input('credit_account_id');
        $amounts = $request->input('amount');
        $vatPercents = $request->input('vat_percent', []);
        $vatAmounts = $request->input('vat_amount', []);
        $debitNarrations = $request->input('debit_narration', []);

        foreach ($debitAccounts as $debitAccountId) {
            if (!in_array((int) $debitAccountId, $expenseIds, true)) {
                return response()->json(['errors' => ['error' => 'Invalid expense account selected.']], 422);
            }
        }

        DB::beginTransaction();
        try {
            $subtotal = array_sum(array_map('floatval', $amounts));
            $totalVat = array_sum(array_map('floatval', $vatAmounts));
            $grandTotal = $subtotal + $totalVat;
            $transCode = Account::trans_code();

            $voucher = \App\Models\Vouchers::create([
                'voucher_type' => 'EXP',
                'trans_code' => $transCode,
                'trans_date' => $request->input('trans_date'),
                'billing_month' => $request->input('billing_month') . '-01',
                'reference_number' => $request->input('reference_number'),
                'payment_type' => $request->input('payment_type'),
                'amount' => $grandTotal,
                'Created_By' => auth()->id(),
            ]);

            foreach ($debitAccounts as $index => $debitAccountId) {
                $amount = floatval($amounts[$index] ?? 0);
                $vatAmount = floatval($vatAmounts[$index] ?? 0);
                $debitNarration = $debitNarrations[$index] ?? '';

                if ($amount <= 0) {
                    continue;
                }

                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $request->input('trans_date'),
                    'account_id' => $debitAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => $debitNarration,
                    'reference_id' => $voucher->id,
                    'reference_type' => 'Voucher',
                    'billing_month' => $request->input('billing_month') . '-01',
                ]);

                if ($vatAmount > 0) {
                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $request->input('trans_date'),
                        'account_id' => HeadAccount::TAX_ACCOUNT,
                        'debit' => $vatAmount,
                        'credit' => 0,
                        'narration' => 'VAT: ' . $debitNarration,
                        'reference_id' => $voucher->id,
                        'reference_type' => 'Voucher',
                        'billing_month' => $request->input('billing_month') . '-01',
                    ]);
                }
            }

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $request->input('trans_date'),
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $grandTotal,
                'narration' => 'Expense Payment',
                'reference_id' => $voucher->id,
                'reference_type' => 'Voucher',
                'billing_month' => $request->input('billing_month') . '-01',
            ]);

            DB::commit();

            return response()->json(['message' => 'Expense voucher created successfully.', 'reload' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['error' => 'Failed to create voucher: ' . $e->getMessage()]], 422);
        }
    }

    /**
     * Show the form for editing an expense voucher.
     */
    public function editVoucher($id)
    {
        if (!auth()->user()->can('expenses_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $voucher = Vouchers::where('voucher_type', 'EXP')->findOrFail($id);
        $transactions = Transactions::where('trans_code', $voucher->trans_code)->get();

        $expenseIds = $this->getExpenseAccountIds();
        $accounts = Accounts::whereIn('id', $expenseIds)
            ->get(['id', 'name', 'parent_id']);

        $expenseAccounts = [];
        foreach ($accounts as $account) {
            $parentKey = $account->parent_id;
            if ($parentKey === null || !in_array((int) $parentKey, $expenseIds, true)) {
                $parentKey = '_root_';
            }
            if (!isset($expenseAccounts[$parentKey])) {
                $expenseAccounts[$parentKey] = [];
            }
            $expenseAccounts[$parentKey][] = $account;
        }

        $bankCashAccounts = Accounts::bankAndCashDropdown();

        $debitEntries = [];
        $creditEntry = null;

        foreach ($transactions as $trans) {
            if ($trans->debit > 0 && $trans->account_id != HeadAccount::TAX_ACCOUNT) {
                $vatTrans = $transactions->where('narration', 'VAT: ' . $trans->narration)->first();
                $debitEntries[] = [
                    'account_id' => $trans->account_id,
                    'narration' => $trans->narration,
                    'amount' => $trans->debit,
                    'vat_amount' => $vatTrans ? $vatTrans->debit : 0,
                    'vat_percent' => $vatTrans && $trans->debit > 0 ? round(($vatTrans->debit / $trans->debit) * 100, 2) : 0,
                ];
            } elseif ($trans->credit > 0) {
                $creditEntry = [
                    'account_id' => $trans->account_id,
                    'amount' => $trans->credit,
                ];
            }
        }

        return view('expenses.voucher_edit', compact('voucher', 'expenseAccounts', 'bankCashAccounts', 'debitEntries', 'creditEntry'));
    }

    /**
     * Update an expense voucher.
     */
    public function updateVoucher(Request $request, $id)
    {
        if (!auth()->user()->can('expenses_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $voucher = Vouchers::where('voucher_type', 'EXP')->findOrFail($id);

        $request->validate([
            'trans_date' => 'required|date',
            'billing_month' => 'required',
            'reference_number' => 'required|string|max:255',
            'credit_account_id' => 'required|exists:accounts,id',
            'debit_account_id' => 'required|array|min:1',
            'debit_account_id.*' => 'required|exists:accounts,id',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:0.01',
        ]);

        $expenseIds = $this->getExpenseAccountIds();
        $debitAccounts = $request->input('debit_account_id');
        $creditAccountId = $request->input('credit_account_id');
        $amounts = $request->input('amount');
        $vatPercents = $request->input('vat_percent', []);
        $vatAmounts = $request->input('vat_amount', []);
        $debitNarrations = $request->input('debit_narration', []);

        foreach ($debitAccounts as $debitAccountId) {
            if (!in_array((int) $debitAccountId, $expenseIds, true)) {
                return response()->json(['errors' => ['error' => 'Invalid expense account selected.']], 422);
            }
        }

        DB::beginTransaction();
        try {
            Transactions::where('trans_code', $voucher->trans_code)->delete();

            $subtotal = array_sum(array_map('floatval', $amounts));
            $totalVat = array_sum(array_map('floatval', $vatAmounts));
            $grandTotal = $subtotal + $totalVat;

            $voucher->update([
                'trans_date' => $request->input('trans_date'),
                'billing_month' => $request->input('billing_month') . '-01',
                'reference_number' => $request->input('reference_number'),
                'payment_type' => $request->input('payment_type'),
                'amount' => $grandTotal,
                'Updated_By' => auth()->id(),
            ]);

            foreach ($debitAccounts as $index => $debitAccountId) {
                $amount = floatval($amounts[$index] ?? 0);
                $vatAmount = floatval($vatAmounts[$index] ?? 0);
                $debitNarration = $debitNarrations[$index] ?? '';

                if ($amount <= 0) {
                    continue;
                }

                Transactions::create([
                    'trans_code' => $voucher->trans_code,
                    'trans_date' => $request->input('trans_date'),
                    'account_id' => $debitAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => $debitNarration,
                    'reference_id' => $voucher->id,
                    'reference_type' => 'Voucher',
                    'billing_month' => $request->input('billing_month') . '-01',
                ]);

                if ($vatAmount > 0) {
                    Transactions::create([
                        'trans_code' => $voucher->trans_code,
                        'trans_date' => $request->input('trans_date'),
                        'account_id' => HeadAccount::TAX_ACCOUNT,
                        'debit' => $vatAmount,
                        'credit' => 0,
                        'narration' => 'VAT: ' . $debitNarration,
                        'reference_id' => $voucher->id,
                        'reference_type' => 'Voucher',
                        'billing_month' => $request->input('billing_month') . '-01',
                    ]);
                }
            }

            Transactions::create([
                'trans_code' => $voucher->trans_code,
                'trans_date' => $request->input('trans_date'),
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $grandTotal,
                'narration' => 'Expense Payment',
                'reference_id' => $voucher->id,
                'reference_type' => 'Voucher',
                'billing_month' => $request->input('billing_month') . '-01',
            ]);

            DB::commit();

            return response()->json(['message' => 'Expense voucher updated successfully.', 'reload' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['error' => 'Failed to update voucher: ' . $e->getMessage()]], 422);
        }
    }

    /**
     * Delete an expense voucher.
     */
    public function destroyVoucher($id)
    {
        if (!auth()->user()->can('expenses_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $voucher = Vouchers::where('voucher_type', 'EXP')->findOrFail($id);

        DB::beginTransaction();
        try {
            Transactions::where('trans_code', $voucher->trans_code)->delete();
            $voucher->delete();

            DB::commit();

            return response()->json(['message' => 'Expense voucher deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['error' => 'Failed to delete voucher: ' . $e->getMessage()]], 422);
        }
    }
}

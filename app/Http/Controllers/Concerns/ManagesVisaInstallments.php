<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\Account;
use App\Support\GlobalAccounts;
use App\Models\Accounts;
use App\Models\Branch;
use App\Models\ExpenseAccount;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Models\visa_installment_plan;
use App\Services\TransactionService;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyQuery;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use DB;

trait ManagesVisaInstallments
{
    protected function installmentModelClass(): string
    {
        return visa_installment_plan::class;
    }

    protected function installmentQuery()
    {
        return $this->installmentModelClass()::query();
    }

    protected function installmentVoucherType(): string
    {
        return 'VL';
    }

    protected function installmentReferenceType(): string
    {
        return 'VL';
    }

    protected function installmentVoucherReason(): ?string
    {
        return null;
    }

    protected function installmentHeadAccountKey(): string
    {
        return 'VISA_EXPENSE_ACCOUNT';
    }

    protected function installmentNarrationLabel(): string
    {
        return 'visa loan installment';
    }

    protected function installmentVoucherRemarksPrefix(): string
    {
        return 'Loan Voucher';
    }

    protected function installmentAccountNotFoundMessage(): string
    {
        return 'Visa expense account not found.';
    }

    protected function installmentEntriesRouteName(): string
    {
        return 'VisaExpense.generatentries';
    }

    protected function installmentIndexRouteName(): ?string
    {
        return 'Installments.index';
    }

    protected function installmentViewPlanTable(): string
    {
        return 'installments.installmentPlanTable';
    }

    protected function installmentViewPlan(): string
    {
        return 'installments.installmentPlan';
    }

    protected function installmentViewCreate(): string
    {
        return 'installments.createInstallmentPlan';
    }

    protected function installmentViewInvoice(): string
    {
        return 'installments.installmentInvoice';
    }

    protected function installmentViewInvoiceAjax(): string
    {
        return 'installments.installmentInvoice_ajax';
    }

    protected function installmentRouteNames(): array
    {
        return [
            'create' => 'Installments.createInstallmentPlan',
            'create_form' => 'Installments.createInstallmentPlanForm',
            'pay' => 'Installments.payInstallment',
            'update_field' => 'Installments.updateInstallmentField',
            'delete' => 'Installments.deleteInstallment',
            'invoice' => 'Installments.generateInstallmentInvoice',
            'plan' => 'Installments.installmentPlan',
        ];
    }

    protected function installmentPermissionMap(): array
    {
        return [
            'view' => ['visa_expense_view', 'installment_view'],
            'create' => ['visa_expense_create', 'installment_create'],
            'edit' => ['visa_expense_edit', 'installment_edit'],
            'delete' => ['visa_expense_delete', 'installment_delete'],
        ];
    }

    protected function installmentAllows(string $action): bool
    {
        foreach ($this->installmentPermissionMap()[$action] ?? [] as $perm) {
            if (user_can($perm)) {
                return true;
            }
        }

        return false;
    }

    protected function expenseAccountBaseQuery()
    {
        return ExpenseAccount::query()->visa();
    }

    protected function installmentVouchersFor($installmentId)
    {
        $query = Vouchers::where('ref_id', $installmentId)
            ->where('voucher_type', $this->installmentVoucherType());
        $reason = $this->installmentVoucherReason();
        if ($reason) {
            $query->where('reason', $reason);
        }

        return $query;
    }

    protected function installmentTransactionsFor($installmentId)
    {
        return Transactions::where('reference_id', $installmentId)
            ->where('reference_type', $this->installmentReferenceType());
    }

    protected function installmentTable(): string
    {
        return (new ($this->installmentModelClass()))->getTable();
    }

    protected function withInstallmentVoucherReason(array $attributes): array
    {
        $reason = $this->installmentVoucherReason();
        if ($reason) {
            $attributes['reason'] = $reason;
        }

        return $attributes;
    }

    protected function createInstallmentVoucher(array $attributes): Vouchers
    {
        return Vouchers::create($this->withInstallmentVoucherReason($attributes));
    }

    protected function installmentTableViewData(array $extra = []): array
    {
        $routes = $this->installmentRouteNames();

        return array_merge([
            'installmentPayRoute' => $routes['pay'],
            'installmentUpdateFieldRoute' => $routes['update_field'],
            'installmentDeleteRoute' => $routes['delete'],
            'installmentStoreRoute' => $routes['create'],
            'installmentCreateFormRoute' => $routes['create_form'],
            'installmentInvoiceRoute' => $routes['invoice'],
            'installmentPlanRoute' => $routes['plan'] ?? 'Installments.installmentPlan',
            'installmentPlanTableView' => $this->installmentViewPlanTable(),
            'installmentIndexRoute' => $this->installmentIndexRouteName(),
            'installmentEntriesRoute' => $this->installmentEntriesRouteName(),
            'canEditInstallment' => $this->installmentAllows('edit'),
            'canDeleteInstallment' => $this->installmentAllows('delete'),
            'canCreateInstallment' => $this->installmentAllows('create'),
            'installmentPlanModel' => $this->installmentModelClass(),
        ], $extra);
    }

    public function installmentPlan(Request $request, $company_slug, $id)
    {
        // Debug session information
        \Log::info('InstallmentPlan Access Debug', [
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'session_data' => session()->all(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
        ]);

        // Check if user is authenticated first
        if (!auth()->check()) {
            \Log::warning('User not authenticated when accessing installment plan', [
                'session_id' => session()->getId(),
                'request_url' => $request->fullUrl(),
            ]);
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!$this->installmentAllows('view')) {
            abort(403, 'Unauthorized action.');
        }

        $account = $this->resolveExpenseAccountContext((int) $id);

        // Auto-mark installments as paid if their date has arrived
        $this->checkAndAutoMarkInstallments($account);

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = $this->installmentQuery()
            ->with(['vouchers', 'installmentTransactions'])
            ->orderBy('date', 'asc');
        $this->applyInstallmentRiderScope($query, $account);

        // Apply filters
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $query->where('billing_month', 'like', '%' . $request->billing_month . '%');
        }

        // Apply pagination using the trait

        $data = $this->applyPagination($query, $paginationParams);
        $installmentStats = $this->installmentPlanSummary($account);

        if ($request->ajax()) {
            $tableData = view($this->installmentViewPlanTable(), $this->installmentTableViewData([
                'data' => $data,
                'account' => $account,
            ]))->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        $riders = Riders::findOrFail($account->rider_id);

        return view($this->installmentViewPlan(), array_merge(
            compact('data', 'account', 'riders', 'installmentStats'),
            $this->installmentTableViewData()
        ));
    }

    public function createInstallmentPlanForm(Request $request, $company_slug, $riderId)
    {
        if (!$this->installmentAllows('create')) {
            abort(403, 'Unauthorized action.');
        }

        $account = $this->resolveExpenseAccountContext((int) $riderId);

        // Auto-mark installments silently in the background
        $this->checkAndAutoMarkInstallments($account);

        $account->loadMissing('rider.branch');
        $rider = $account->rider ?? Riders::find($account->rider_id);
        if (!$rider) {
            abort(404, 'Rider not found for this expense account.');
        }

        $branch = $rider->branch;
        $branchId = $branch?->id ?? $rider->branch_id;
        $branches = null;
        if (!$branchId) {
            $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        return view($this->installmentViewCreate(), array_merge(compact(
            'account',
            'rider',
            'branch',
            'branchId',
            'branches'
        ), $this->installmentTableViewData()));
    }

    public function createInstallmentPlan(Request $request, $company_slug)
    {
        if (!$this->installmentAllows('create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'rider_id' => 'required|integer',
            'total_amount' => 'required|numeric|min:1',
            'billing_month' => 'required|string',
            'number_of_installments' => 'required|integer|min:1|max:12',
            'installment_amounts' => 'required|array|min:1',
            'installment_amounts.*' => 'required|numeric|min:0',
            'reference_number' => 'required|string|max:255',
            'branch_id' => 'required|integer|exists:branches,id',
        ], [
            'branch_id.required' => 'Branch is required. Assign a branch to the rider or select one in the form.',
            'installment_amounts.required' => 'Installment amounts are missing. Please regenerate the installment preview and try again.',
        ]);

        try {
            DB::beginTransaction();

            // Resolve rider account context (expense_accounts.id, riders.id, or legacy accounts.id).
            $expenseAccount = $this->resolveExpenseAccountContext((int) $validated['rider_id']);
            $expenseAccount->loadMissing('renewalCategory');
            $riderAccount = (int) $expenseAccount->rider_id;

            // Validate that installment amounts sum to total amount.
            // Allow tiny rounding drift (e.g. 833.33 x 6 = 4999.98 for 5000.00 total),
            // then auto-adjust the last installment to keep exact total consistency.
            $installmentAmounts = array_map(static fn($amount) => round((float) $amount, 2), $validated['installment_amounts']);
            $sumOfInstallments = round(array_sum($installmentAmounts), 2);
            $totalAmount = round((float) $validated['total_amount'], 2);
            $difference = round($totalAmount - $sumOfInstallments, 2);

            if (abs($difference) <= 0.05 && count($installmentAmounts) > 0) {
                $lastIndex = count($installmentAmounts) - 1;
                $installmentAmounts[$lastIndex] = round($installmentAmounts[$lastIndex] + $difference, 2);
                $sumOfInstallments = round(array_sum($installmentAmounts), 2);
                $difference = round($totalAmount - $sumOfInstallments, 2);
            }

            if (abs($difference) > 0.01) {
                $message = 'The sum of individual installment amounts (' . number_format($sumOfInstallments, 2) . ') does not match the total amount (' . number_format($totalAmount, 2) . ').';
                if ($request->ajax()) {
                    return response()->json(['message' => $message], 422);
                }
                Flash::error($message);
                return redirect()->back()->withInput();
            }

            // Validate number of installments matches the array length
            if (count($installmentAmounts) != $validated['number_of_installments']) {
                $message = 'Number of installment amounts does not match the selected number of installments.';
                if ($request->ajax()) {
                    return response()->json(['message' => $message], 422);
                }
                Flash::error($message);
                return redirect()->back()->withInput();
            }

            // Find the liability account using ref_id from rider account
            $liabilityAccount = Accounts::where('ref_id', $riderAccount)
                ->where('account_type', 'Liability')
                ->where('parent_id', 1)
                ->first();

            if (!$liabilityAccount) {
                $liabilityAccount = Accounts::where('ref_id', $riderAccount)
                    ->where('account_type', 'Liability')
                    ->first();
            }

            if (!$liabilityAccount) {
                $message = 'Liability account not found for this rider. Please create the liability account first.';
                if ($request->ajax()) {
                    return response()->json(['message' => $message], 422);
                }
                Flash::error($message);
                return redirect()->back();
            }
            $rider = Riders::findOrFail($riderAccount);

            $existingInstallmentCount = $this->persistInstallmentPlanEntries(
                $expenseAccount,
                $rider,
                $liabilityAccount,
                (int) $validated['branch_id'],
                $validated['billing_month'],
                $validated['reference_number'],
                $totalAmount,
                $installmentAmounts
            );

            DB::commit();

            $installmentDetails = '';
            foreach ($installmentAmounts as $index => $amount) {
                $installmentDetails .= '<b>Installment ' . ($index + 1 + $existingInstallmentCount) . '</b>: <b>' . number_format($amount, 2) . '</b>, ';
            }
            $installmentDetails = rtrim($installmentDetails, ', ');

            $successMessage = $validated['number_of_installments'] . ' installment entries created successfully with individual amounts: ' . $installmentDetails . '. Total amount: ' . number_format($validated['total_amount'], 2);
            if ($request->ajax()) {
                return response()->json([
                    'message' => strip_tags($successMessage),
                    'reload' => true,
                ]);
            }

            Flash::success($successMessage);
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error creating installment plan: ' . $e->getMessage(),
                ], 500);
            }
            Flash::error('Error creating installment plan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function payInstallment(Request $request)
    {
        if (!$this->installmentAllows('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'installment_id' => 'required|exists:' . $this->installmentTable() . ',id',
            'status' => 'nullable|in:pending,paid'
        ]);

        try {
            DB::beginTransaction();

            $installment = $this->installmentModelClass()::findOrFail($validated['installment_id']);
            $currentStatus = $installment->status;
            $newStatus = $request->has('status') ? $validated['status'] : $this->installmentModelClass()::STATUS_PAID;

            // If status is already what we want to set it to
            if ($currentStatus === $newStatus) {
                $actionText = $newStatus === $this->installmentModelClass()::STATUS_PAID ? 'paid' : 'pending';
                Flash::info('This installment is already marked as ' . $actionText . '.');
                return redirect()->back();
            }

            // Update the status
            $installment->status = $newStatus;
            $installment->updated_by = auth()->user()->id;
            $installment->save();

            DB::commit();

            $actionText = $newStatus === $this->installmentModelClass()::STATUS_PAID ? 'paid' : 'pending';
            Flash::success('Installment marked as ' . $actionText . ' successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error processing status change: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function updateInstallmentField(Request $request)
    {
        if (!$this->installmentAllows('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'installment_id' => 'required|exists:' . $this->installmentTable() . ',id',
            'field' => 'required|in:date,billing_month,amount,narration',
            'value' => 'required',
            'update_subsequent' => 'nullable|in:true,false,1,0',
            'mark_as_paid' => 'nullable|boolean'
        ]);

        // Convert update_subsequent to boolean
        $validated['update_subsequent'] = in_array($request->input('update_subsequent'), ['true', '1', 1, true], true);
        $markAsPaid = in_array($request->input('mark_as_paid'), ['true', '1', 1, true], true);

        try {
            DB::beginTransaction();

            $installment = $this->installmentModelClass()::findOrFail($validated['installment_id']);
            $isPaid = $installment->status === $this->installmentModelClass()::STATUS_PAID;

            ['riderAccount' => $riderAccount, 'rider' => $rider] = $this->resolveInstallmentRiderContext($installment);
            $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                ->where('account_type', 'Liability')
                ->where('parent_id', 1)
                ->first();

            // Update the installment field
            $oldValue = $installment->{$validated['field']};

            if ($validated['field'] === 'billing_month') {
                $installment->billing_month = $validated['value'];
                // Check if billing month already contains day, if not add it
                $billingMonth = (strlen($validated['value']) <= 7) ? $validated['value'] . "-01" : $validated['value'];

                // Only update date for pending installments or if explicitly requested
                if (!$isPaid) {
                    // Update current installment date to 10th of next month from new billing month
                    $newBillingDate = Carbon::parse($validated['value'] . '-01');
                    $installment->date = $newBillingDate->addMonth()->day(10)->format('Y-m-d');
                }
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                // Update subsequent installments if requested
                if ($validated['update_subsequent']) {
                    $this->updateSubsequentInstallments($installment, 'billing_month', $validated['value'], $rider);
                }
            } elseif ($validated['field'] === 'date') {
                $installment->date = $validated['value'];
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                $billingMonth = $installment->billing_month;
                // Ensure billing month has proper format for voucher/transaction updates
                if (strlen($billingMonth) <= 7) {
                    $billingMonth = $billingMonth . "-01";
                }

                // Update subsequent installments if requested
                if ($validated['update_subsequent']) {
                    $this->updateSubsequentInstallments($installment, 'date', $validated['value'], $rider);
                }
            } elseif ($validated['field'] === 'amount') {
                $installment->amount = $validated['value'];
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                $billingMonth = $installment->billing_month;
                // Ensure billing month has proper format for voucher/transaction updates
                if (strlen($billingMonth) <= 7) {
                    $billingMonth = $billingMonth . "-01";
                }

                // Update subsequent installments if requested and this is not a paid installment
                if ($validated['update_subsequent'] && !$isPaid) {
                    $this->recalculateInstallmentAmounts($installment, $validated['value'], $rider);
                }
            } elseif ($validated['field'] === 'narration') {
                $installment->narration = trim((string) $validated['value']);
                $installment->updated_by = auth()->user()->id;
                $installment->save();
            }

            // Update voucher (no narration column on vouchers â€” narration lives on transactions)
            $voucher = $this->installmentVouchersFor($installment->id)
                ->first();

            if ($voucher && in_array($validated['field'], ['billing_month', 'amount', 'date'], true)) {
                if ($validated['field'] === 'billing_month') {
                    $voucher->billing_month = $billingMonth;
                } elseif ($validated['field'] === 'amount') {
                    $voucher->amount = $validated['value'];
                } elseif ($validated['field'] === 'date') {
                    $voucher->trans_date = $validated['value'];
                }
                $voucher->updated_by = auth()->user()->id;
                $voucher->save();
            }

            // Update transactions
            $transactions = $this->installmentTransactionsFor($installment->id)
                ->get();

            foreach ($transactions as $transaction) {
                if ($validated['field'] === 'billing_month') {
                    $transaction->billing_month = $billingMonth;
                    // Do not change narration here â€” only explicit "narration" edits update GL text.
                } elseif ($validated['field'] === 'amount') {
                    if ($transaction->credit > 0) {
                        $transaction->credit = $validated['value'];
                    } else {
                        $transaction->debit = $validated['value'];
                    }
                } elseif ($validated['field'] === 'date') {
                    $transaction->trans_date = $validated['value'];
                } elseif ($validated['field'] === 'narration') {
                    $transaction->narration = trim((string) $validated['value']);
                }
                $transaction->updated_at = now();
                $transaction->save();
            }

            // Update ledger entry if amount changed
            if ($validated['field'] === 'amount' && $liabilityAccount) {
                $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                    ->where('account_id', $liabilityAccount->id)
                    ->where('billing_month', $billingMonth)
                    ->first();

                if ($ledgerEntry) {
                    $difference = $validated['value'] - $oldValue;
                    \App\Support\CompanyQuery::table('ledger_entries')
                        ->where('account_id', $liabilityAccount->id)
                        ->where('billing_month', $billingMonth)
                        ->update([
                            'debit_balance' => $validated['value'],
                            'closing_balance' => $ledgerEntry->opening_balance + $validated['value'],
                            'updated_at' => now(),
                        ]);
                }
            }

            // If this is a paid installment and we want to keep it paid
            if ($markAsPaid && !$isPaid) {
                $installment->status = $this->installmentModelClass()::STATUS_PAID;
                $installment->save();
            }

            DB::commit();

            $message = ucfirst($validated['field']) . ' updated successfully with voucher and transactions.';
            if ($validated['update_subsequent']) {
                $message .= ' Subsequent installments were also updated accordingly.';
            }
            if ($markAsPaid && !$isPaid) {
                $message .= ' Installment marked as paid.';
            }

            Flash::success($message);
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error updating installment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function finalizePayment(Request $request)
    {
        if (!$this->installmentAllows('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'changes' => 'nullable|string',
            'deletions' => 'nullable|string',
            'additions' => 'nullable|string',
            'date_changes' => 'nullable|string',
            'billing_changes' => 'nullable|string',
        ]);

        $changes = $request->filled('changes') ? json_decode($validated['changes'], true) : [];
        $deletions = $request->filled('deletions') ? json_decode($validated['deletions'], true) : [];
        $additions = $request->filled('additions') ? json_decode($validated['additions'], true) : [];
        $dateChanges = $request->filled('date_changes') ? json_decode($validated['date_changes'], true) : [];
        $billingChanges = $request->filled('billing_changes') ? json_decode($validated['billing_changes'], true) : [];

        if ((!is_array($changes) || empty($changes)) &&
            (empty($deletions) || !is_array($deletions)) &&
            (empty($additions) || !is_array($additions)) &&
            (empty($dateChanges) || !is_array($dateChanges)) &&
            (empty($billingChanges) || !is_array($billingChanges))
        ) {
            \Flash::warning('Nothing to finalize.');
            return redirect()->back();
        }

        try {
            \DB::beginTransaction();

            $firstRiderId = null;
            $sumPendingVisibleDelta = 0.0;

            foreach ($changes as $installmentId => $newAmount) {
                if (!is_numeric($installmentId)) {
                    throw new \InvalidArgumentException('Invalid installment id provided.');
                }
                if (!is_numeric($newAmount) || $newAmount <= 0) {
                    throw new \InvalidArgumentException('Invalid amount provided for installment ' . $installmentId . '.');
                }

                /** @var \App\Models\visa_installment_plan $installment */
                $installment = $this->installmentModelClass()::findOrFail($installmentId);

                // Allow editing paid installments - this check is removed

                $firstRiderId = $firstRiderId ?: $installment->rider_id;

                ['riderAccount' => $riderAccount, 'rider' => $rider] = $this->resolveInstallmentRiderContext($installment);
                $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                    ->where('account_type', 'Liability')
                    ->where('parent_id', 1)
                    ->first();

                // Existing values
                $oldAmount = (float) $installment->amount;

                // Update installment amount
                $installment->amount = (float) $newAmount;
                $installment->updated_by = auth()->user()->id;
                $installment->save();
                $sumPendingVisibleDelta += ((float)$newAmount - (float)$oldAmount);

                // Ensure billing month format for downstream updates
                $billingMonth = $installment->billing_month;
                if (strlen($billingMonth) <= 7) {
                    $billingMonth = $billingMonth . '-01';
                }

                // Update or create voucher
                $voucher = $this->installmentVouchersFor($installment->id)
                    ->first();

                if ($voucher) {
                    $voucher->amount = (float) $newAmount;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                } else {
                    $trans_code = Account::trans_code();
                    $trans_date = Carbon::parse($installment->date ?? Carbon::today());

                    $voucher = $this->createInstallmentVoucher([
                        'rider_id' => $rider->id,
                        'trans_date' => $trans_date,
                        'trans_code' => $trans_code,
                        'billing_month' => $billingMonth,
                        'payment_type' => 1,
                        'voucher_type' => $this->installmentVoucherType(),
                        'remarks' => $rider->rider_id . ' - ' . $rider->name . ' - ' . $this->installmentNarrationLabel(),
                        'amount' => (float) $newAmount,
                        'reference_number' => $installment->reference_number ?? null,
                        'Created_By' => auth()->user()->id,
                        'ref_id' => $installment->id,
                        'custom_field_values' => [],
                        'branch_id' => $rider->branch_id,
                    ]);

                    // Create transactions for the voucher
                    $TransactionService = new TransactionService();
                    $TransactionService->recordTransaction([
                        'account_id' => $liabilityAccount?->id,
                        'reference_id' => $installment->id,
                        'reference_type' => $this->installmentReferenceType(),
                        'trans_code' => $trans_code,
                        'trans_date' => $trans_date,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting <b> installment </b> - ' . $installment->billing_month,
                        'debit' => (float) $newAmount,
                        'billing_month' => $billingMonth,
                        'created_by' => auth()->user()->id,
                        'branch_id' => $rider->branch_id,
                    ]);

                    $TransactionService->recordTransaction([
                        'account_id' => $riderAccount->id,
                        'reference_id' => $installment->id,
                        'reference_type' => $this->installmentReferenceType(),
                        'trans_code' => $trans_code,
                        'trans_date' => $trans_date,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting <b> installment </b> - ' . $installment->billing_month,
                        'credit' => (float) $newAmount,
                        'billing_month' => $billingMonth,
                        'created_by' => auth()->user()->id,
                        'branch_id' => $rider->branch_id,
                    ]);
                }

                // Update transactions (if existed prior)
                $transactions = $this->installmentTransactionsFor($installment->id)
                    ->get();

                foreach ($transactions as $transaction) {
                    if ($transaction->credit > 0) {
                        $transaction->credit = (float) $newAmount;
                    } else {
                        $transaction->debit = (float) $newAmount;
                    }
                    $transaction->updated_at = now();
                    $transaction->save();
                }

                // Update or insert ledger entry for liability account
                if ($liabilityAccount) {
                    $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                        ->where('account_id', $liabilityAccount->id)
                        ->where('billing_month', $billingMonth)
                        ->first();

                    if ($ledgerEntry) {
                        \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $billingMonth)
                            ->update([
                                'debit_balance' => (float) $newAmount,
                                'closing_balance' => (float) $ledgerEntry->opening_balance + (float) $newAmount,
                                'updated_at' => now(),
                            ]);
                    } else {
                        $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->orderBy('billing_month', 'desc')
                            ->first();
                        $opening_balance = $lastLedger ? (float) $lastLedger->closing_balance : 0.00;
                        \App\Support\CompanyQuery::insert('ledger_entries', [
                            'account_id' => $liabilityAccount->id,
                            'billing_month' => $billingMonth,
                            'opening_balance' => $opening_balance,
                            'debit_balance' => (float) $newAmount,
                            'credit_balance' => 0.00,
                            'closing_balance' => $opening_balance + (float) $newAmount,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Apply deletions if any
            if (is_array($deletions) && !empty($deletions)) {
                foreach ($deletions as $deleteId) {
                    /** @var \App\Models\visa_installment_plan $inst */
                    $inst = $this->installmentModelClass()::findOrFail($deleteId);
                    if ($inst->status === $this->installmentModelClass()::STATUS_PAID) {
                        throw new \RuntimeException('Cannot delete a paid installment (ID: ' . $deleteId . ').');
                    }

                    $firstRiderId = $firstRiderId ?: $inst->rider_id;

                    $installmentIdentifier = "Installment Plan #{$deleteId} - Billing Month: {$inst->billing_month} (Amount: " . number_format($inst->amount, 2) . ")";

                    // Get related vouchers before deletion (include soft deleted to be safe)
                    $relatedVouchers = $this->installmentVouchersFor($inst->id)
                        ->get();

                    \Log::info("Found " . $relatedVouchers->count() . " vouchers to track for installment {$deleteId} in finalizePayment", [
                        'voucher_ids' => $relatedVouchers->pluck('id')->toArray()
                    ]);

                    // Get related transactions before deletion
                    $relatedTransactions = $this->installmentTransactionsFor($inst->id)
                        ->get();

                    // Track cascade deletions for vouchers BEFORE deletion
                    foreach ($relatedVouchers as $voucher) {
                        try {
                            \Log::info("Attempting to track cascade deletion for voucher {$voucher->id}", [
                                'primary_model' => $this->installmentModelClass(),
                                'primary_id' => $inst->id,
                                'related_model' => Vouchers::class,
                                'related_id' => $voucher->id,
                            ]);

                            $cascadeRecord = $this->trackCascadeDeletion(
                                $this->installmentModelClass(),
                                $inst->id,
                                $installmentIdentifier,
                                Vouchers::class,
                                $voucher->id,
                                "Voucher #{$voucher->id} ({$voucher->voucher_type}-" . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) . ", Amount: " . number_format($voucher->amount, 2) . ")",
                                'hasMany',
                                'vouchers',
                                'soft',
                                'Cascade deletion from Installment Plan deletion via finalizePayment - voucher'
                            );

                            if ($cascadeRecord && $cascadeRecord->id) {
                                \Log::info("Cascade deletion tracked successfully for voucher {$voucher->id}, cascade record ID: {$cascadeRecord->id}");
                            } else {
                                \Log::warning("Cascade deletion tracking returned null for voucher {$voucher->id}");
                            }
                        } catch (\Exception $e) {
                            \Log::error("Failed to track cascade deletion for voucher {$voucher->id}: " . $e->getMessage(), [
                                'exception' => $e,
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                    // Track cascade deletions for transactions
                    foreach ($relatedTransactions as $transaction) {
                        try {
                            $this->trackCascadeDeletion(
                                $this->installmentModelClass(),
                                $inst->id,
                                $installmentIdentifier,
                                Transactions::class,
                                $transaction->id,
                                "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}, Amount: " . ($transaction->debit > 0 ? number_format($transaction->debit, 2) : number_format($transaction->credit, 2)) . ")",
                                'hasMany',
                                'transactions',
                                'soft',
                                'Cascade deletion from Installment Plan deletion via finalizePayment - transaction'
                            );
                        } catch (\Exception $e) {
                            \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
                        }
                    }

                    // Cascade delete vouchers related to this installment (soft delete with deleted_by)
                    // Use the same collection that was used for tracking
                    foreach ($relatedVouchers as $voucher) {
                        $voucher->deleted_by = auth()->id();
                        $voucher->save();
                        $voucher->delete();
                    }
                    foreach ($relatedTransactions as $transaction) {
                        if (\Illuminate\Support\Facades\Schema::hasColumn($transaction->getTable(), 'deleted_by')) {
                            $transaction->deleted_by = auth()->id();
                            $transaction->save();
                        }
                        $transaction->delete();
                    }

                    // Delete related ledger entries for the liability account for that billing month
                    ['riderAccount' => $riderAccount] = $this->resolveInstallmentRiderContext($inst);
                    $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                        ->where('account_type', 'Liability')
                        ->where('parent_id', 1)
                        ->first();
                    if ($liabilityAccount) {
                        // Get ledger entry before deletion
                        $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $inst->billing_month)
                            ->first();

                        if ($ledgerEntry) {
                            try {
                                $this->trackCascadeDeletion(
                                    $this->installmentModelClass(),
                                    $inst->id,
                                    $installmentIdentifier,
                                    \App\Models\LedgerEntry::class,
                                    $ledgerEntry->id,
                                    "Ledger Entry #{$ledgerEntry->id} (Account ID: {$liabilityAccount->id}, Billing Month: {$inst->billing_month})",
                                    'hasOne',
                                    'ledger_entry',
                                    'hard',
                                    'Cascade deletion from Installment Plan deletion via finalizePayment - ledger entry'
                                );
                            } catch (\Exception $e) {
                                \Log::error("Failed to track cascade deletion for ledger entry {$ledgerEntry->id}: " . $e->getMessage());
                            }
                        }

                        \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $inst->billing_month)
                            ->delete();
                    }

                    // Delete the installment (soft delete with deleted_by)
                    $inst->deleted_by = auth()->id();
                    $inst->save();
                    $inst->delete();
                }
            }

            // Create additions if any
            if (is_array($additions) && !empty($additions)) {
                // Determine context from any existing installment
                $contextInstallment = null;
                if (!empty($changes)) {
                    $firstChangeId = array_key_first($changes);
                    $contextInstallment = $this->installmentModelClass()::find($firstChangeId);
                }
                if (!$contextInstallment && !empty($deletions)) {
                    $contextInstallment = $this->installmentModelClass()::find($deletions[0]);
                }
                if (!$contextInstallment) {
                    $contextInstallment = $this->installmentModelClass()::first();
                }
                if (!$contextInstallment) {
                    throw new \RuntimeException('Unable to determine rider for new installment.');
                }

                $firstRiderId = $firstRiderId ?: $contextInstallment->rider_id;
                ['riderAccount' => $riderAccount, 'rider' => $rider] = $this->resolveInstallmentRiderContext($contextInstallment);
                $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                    ->where('account_type', 'Liability')
                    ->where('parent_id', 1)
                    ->first();
                $existingTotalAmount = (float) ($this->installmentModelClass()::where('rider_id', $contextInstallment->rider_id)->value('total_amount') ?? 0);

                foreach ($additions as $addition) {
                    $amount = isset($addition['amount']) ? (float) $addition['amount'] : null;
                    $bm = $addition['billing_month'] ?? null; // 'YYYY-MM'
                    $date = $addition['date'] ?? null; // 'YYYY-MM-DD'
                    if (!$amount || $amount <= 0 || !$bm) {
                        throw new \InvalidArgumentException('Invalid addition payload.');
                    }
                    if (!$date) {
                        $date = Carbon::parse($bm . '-01')->copy()->addMonth()->day(10)->format('Y-m-d');
                    }

                    $installment = $this->installmentModelClass()::create([
                        'rider_id' => $contextInstallment->rider_id,
                        'billing_month' => $bm,
                        'amount' => $amount,
                        'total_amount' => $existingTotalAmount,
                        'reference_number' => $contextInstallment->reference_number ?? null,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - installment - ' . $bm,
                        'status' => $this->installmentModelClass()::STATUS_PENDING,
                        'date' => $date,
                        'created_by' => auth()->user()->id,
                    ]);

                    // Create voucher and transactions for the new installment
                    $trans_code = Account::trans_code();
                    $trans_date = Carbon::parse($date);
                    $billingMonthFull = strlen($bm) <= 7 ? $bm . '-01' : $bm;

                    $this->createInstallmentVoucher([
                        'rider_id' => $rider->id,
                        'trans_date' => $trans_date,
                        'trans_code' => $trans_code,
                        'billing_month' => $billingMonthFull,
                        'payment_type' => 1,
                        'voucher_type' => $this->installmentVoucherType(),
                        'remarks' => $rider->rider_id . ' - ' . $rider->name . ' - ' . $this->installmentNarrationLabel() . ' (new)',
                        'amount' => $amount,
                        'reference_number' => $installment->reference_number ?? null,
                        'Created_By' => auth()->user()->id,
                        'ref_id' => $installment->id,
                        'custom_field_values' => [],
                        'branch_id' => $rider->branch_id,
                    ]);

                    $TransactionService = new TransactionService();
                    if ($liabilityAccount) {
                        $TransactionService->recordTransaction([
                            'account_id' => $liabilityAccount->id,
                            'reference_id' => $installment->id,
                            'reference_type' => $this->installmentReferenceType(),
                            'trans_code' => $trans_code,
                            'trans_date' => $trans_date,
                            'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting installment - ' . $bm,
                            'debit' => $amount,
                            'billing_month' => $billingMonthFull,
                            'created_by' => auth()->user()->id,
                            'branch_id' => $rider->branch_id,
                        ]);

                        // Update or insert ledger entry for liability
                        $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                            ->where('account_id', $liabilityAccount->id)
                            ->where('billing_month', $billingMonthFull)
                            ->first();
                        if ($ledgerEntry) {
                            \App\Support\CompanyQuery::table('ledger_entries')
                                ->where('account_id', $liabilityAccount->id)
                                ->where('billing_month', $billingMonthFull)
                                ->update([
                                    'debit_balance' => (float) $amount,
                                    'closing_balance' => (float) $ledgerEntry->opening_balance + (float) $amount,
                                    'updated_at' => now(),
                                ]);
                        } else {
                            $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
                                ->where('account_id', $liabilityAccount->id)
                                ->orderBy('billing_month', 'desc')
                                ->first();
                            $opening_balance = $lastLedger ? (float) $lastLedger->closing_balance : 0.00;
                            \App\Support\CompanyQuery::insert('ledger_entries', [
                                'account_id' => $liabilityAccount->id,
                                'billing_month' => $billingMonthFull,
                                'opening_balance' => $opening_balance,
                                'debit_balance' => (float) $amount,
                                'credit_balance' => 0.00,
                                'closing_balance' => $opening_balance + (float) $amount,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // Credit rider account
                    $TransactionService->recordTransaction([
                        'account_id' => $riderAccount->id,
                        'reference_id' => $installment->id,
                        'reference_type' => $this->installmentReferenceType(),
                        'trans_code' => $trans_code,
                        'trans_date' => $trans_date,
                        'narration' => $rider->rider_id . ' - ' . $rider->name . ' - deducting installment - ' . $bm,
                        'credit' => $amount,
                        'billing_month' => $billingMonthFull,
                        'created_by' => auth()->user()->id,
                        'branch_id' => $rider->branch_id,
                    ]);
                }
            }

            // Server-side validation: paid + pending must equal required total
            if ($firstRiderId) {
                $totalAmount = (float) $this->installmentModelClass()::where('rider_id', $firstRiderId)->value('total_amount') ?? 0.0;
                $paidTotal = (float) $this->installmentModelClass()::where('rider_id', $firstRiderId)->where('status', 'paid')->sum('amount');
                $pendingTotal = (float) $this->installmentModelClass()::where('rider_id', $firstRiderId)->where('status', 'pending')->sum('amount');
                $combined = $paidTotal + $pendingTotal;
                if (abs($totalAmount - $combined) > 0.009) {
                    throw new \RuntimeException('Totals mismatch after finalize. Please adjust amounts to match the required total.');
                }
            }

            // Process date changes if any
            if (is_array($dateChanges) && !empty($dateChanges)) {
                foreach ($dateChanges as $installmentId => $newDate) {
                    $installment = $this->installmentModelClass()::findOrFail($installmentId);
                    $installment->date = $newDate;
                    $installment->updated_by = auth()->user()->id;
                    $installment->save();

                    // Update related voucher
                    $voucher = $this->installmentVouchersFor($installment->id)
                        ->first();
                    if ($voucher) {
                        $voucher->trans_date = $newDate;
                        $voucher->updated_by = auth()->user()->id;
                        $voucher->save();
                    }

                    // Update related transactions
                    $transactions = $this->installmentTransactionsFor($installment->id)
                        ->get();
                    foreach ($transactions as $transaction) {
                        $transaction->trans_date = $newDate;
                        $transaction->updated_at = now();
                        $transaction->save();
                    }
                }
            }

            // Process billing month changes if any
            if (is_array($billingChanges) && !empty($billingChanges)) {
                foreach ($billingChanges as $installmentId => $newBillingMonth) {
                    $installment = $this->installmentModelClass()::findOrFail($installmentId);
                    $installment->billing_month = $newBillingMonth;
                    $installment->updated_by = auth()->user()->id;
                    $installment->save();

                    // Check if billing month already contains day, if not add it
                    $billingMonthWithDay = (strlen($newBillingMonth) <= 7) ? $newBillingMonth . "-01" : $newBillingMonth;

                    // Update related voucher
                    $voucher = $this->installmentVouchersFor($installment->id)
                        ->first();
                    if ($voucher) {
                        $voucher->billing_month = $billingMonthWithDay;
                        $voucher->updated_by = auth()->user()->id;
                        $voucher->save();
                    }

                    // Update related transactions
                    $transactions = $this->installmentTransactionsFor($installment->id)
                        ->get();
                    foreach ($transactions as $transaction) {
                        $transaction->billing_month = $billingMonthWithDay;
                        $transaction->updated_at = now();
                        $transaction->save();
                    }
                }
            }

            \DB::commit();

            \Flash::success('Payment finalized. All changes saved and vouchers updated successfully.');
            if ($firstRiderId) {
                return redirect()->route($this->installmentEntriesRouteName(), $firstRiderId);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            \DB::rollBack();
            \Flash::error('Error finalizing payment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function deleteInstallment($company_slug, $id)
    {
        if (!$this->installmentAllows('delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $installment = $this->installmentModelClass()::findOrFail($id);

            $relatedVouchers = $this->installmentVouchersFor($installment->id)
                ->get();

            $relatedTransactions = $this->installmentTransactionsFor($installment->id)
                ->get();

            $billingMonth = $installment->billing_month;
            $liabilityAccountId = null;
            try {
                ['riderAccount' => $riderAccount] = $this->resolveInstallmentRiderContext($installment);
                $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                    ->where('account_type', 'Liability')
                    ->where('parent_id', 1)
                    ->first();
                if (!$liabilityAccount) {
                    $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                        ->where('account_type', 'Liability')
                        ->first();
                }
                $liabilityAccountId = $liabilityAccount?->id;
            } catch (\Throwable $e) {
                \Log::warning('Could not resolve liability account before installment delete: ' . $e->getMessage());
            }

            // Queue the installment first so related deletes attach as cascades
            // and are NOT removed until an administrator approves.
            $installment->deleted_by = auth()->id();
            $installment->save();
            $installment->delete();

            $pendingQueued = (bool) request()->attributes->get('delete_approval_created');

            // Model deletes (not query-builder) so delete-approval can intercept/cascade.
            foreach ($relatedVouchers as $voucher) {
                $voucher->deleted_by = auth()->id();
                $voucher->save();
                $voucher->delete();
            }

            foreach ($relatedTransactions as $transaction) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($transaction->getTable(), 'deleted_by')) {
                    $transaction->deleted_by = auth()->id();
                    $transaction->save();
                }
                $transaction->delete();
            }

            // Ledger cleanup only when the delete actually happened (approval bypassed/disabled).
            if (!$pendingQueued && $liabilityAccountId) {
                $this->cleanupInstallmentLedgerAfterDelete($liabilityAccountId, $billingMonth);
            }

            DB::commit();

            if ($pendingQueued) {
                // Pending flash is applied by AdjustDeleteApprovalResponse — no success message.
                return redirect()->back();
            }

            Flash::success('Installment deleted successfully along with voucher and transactions.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error deleting installment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Recalculate/remove liability ledger rows after an installment is actually deleted.
     */
    private function cleanupInstallmentLedgerAfterDelete($liabilityAccountId, $billingMonth): void
    {
        $billingMonthForLedger = (strlen((string) $billingMonth) <= 7)
            ? $billingMonth . '-01'
            : $billingMonth;

        \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $liabilityAccountId)
            ->where('billing_month', $billingMonthForLedger)
            ->delete();

        $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $liabilityAccountId)
            ->where('billing_month', '<', $billingMonthForLedger)
            ->orderBy('billing_month', 'desc')
            ->first();

        $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;
        $monthTransactions = Transactions::where('account_id', $liabilityAccountId)
            ->where('billing_month', $billingMonthForLedger)
            ->get();

        if ($monthTransactions->count() === 0) {
            return;
        }

        $debitTotal = $monthTransactions->sum('debit');
        $creditTotal = $monthTransactions->sum('credit');

        \App\Support\CompanyQuery::insert('ledger_entries', [
            'account_id' => $liabilityAccountId,
            'billing_month' => $billingMonthForLedger,
            'opening_balance' => $openingBalance,
            'debit_balance' => $debitTotal,
            'credit_balance' => $creditTotal,
            'closing_balance' => $openingBalance + $debitTotal - $creditTotal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function generateInstallmentInvoice($company_slug, $riderId)
    {
        if (!$this->installmentAllows('view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $account = null;
            $rider = null;
            $installments = collect();

            $expenseAccount = ExpenseAccount::with('rider')->find($riderId);
            if ($expenseAccount) {
                $account = $expenseAccount;
                $rider = $expenseAccount->rider ?? Riders::findOrFail($expenseAccount->rider_id);
                $installments = $this->installmentQuery()
                    ->where(function ($q) use ($expenseAccount) {
                        $this->applyInstallmentRiderScope($q, $expenseAccount);
                    })
                    ->orderBy('billing_month', 'asc')
                    ->get();
            }

            if ($installments->isEmpty() && ($ledgerAccount = Accounts::find($riderId))) {
                $account = $ledgerAccount;
                $rider = Riders::findOrFail($ledgerAccount->ref_id);
                $installments = $this->installmentModelClass()::where('rider_id', $riderId)
                    ->orderBy('billing_month', 'asc')
                    ->get();
            }

            if ($installments->isEmpty()) {
                $rider = Riders::findOrFail($riderId);
                $installments = $this->installmentModelClass()::where('rider_id', $riderId)
                    ->orderBy('billing_month', 'asc')
                    ->get();
            }

            if ($installments->isEmpty()) {
                $msg = 'No installment plans found for this rider.';
                if (request()->ajax()) {
                    return response('<div class="alert alert-warning m-3 mb-0">' . e($msg) . '</div>', 200);
                }
                Flash::error($msg);
                return redirect()->back();
            }

            $rider->loadMissing(['vendor', 'sim']);

            if (request()->ajax()) {
                return view($this->installmentViewInvoiceAjax(), compact('rider', 'installments', 'account'));
            }

            return view($this->installmentViewInvoice(), compact('rider', 'installments', 'account'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response(
                    '<div class="alert alert-danger m-3 mb-0">' . e('Error generating invoice: ' . $e->getMessage()) . '</div>',
                    200
                );
            }
            Flash::error('Error generating invoice: ' . $e->getMessage());
            return redirect()->back();
        }
    }
    private function resolveExpenseAccountContext(int $id): ExpenseAccount
    {
        $expense = $this->expenseAccountBaseQuery()->with('rider')->find($id);
        if ($expense) {
            return $expense;
        }

        $expense = $this->expenseAccountBaseQuery()->with('rider')->where('rider_id', $id)->orderBy('id')->first();
        if ($expense) {
            return $expense;
        }

        $expense = $this->expenseAccountBaseQuery()->with('rider')->where('account_id', $id)->first();
        if ($expense) {
            return $expense;
        }

        $legacyAccount = Accounts::find($id);
        if ($legacyAccount && $legacyAccount->ref_id) {
            $expense = $this->expenseAccountBaseQuery()->with('rider')->where('rider_id', $legacyAccount->ref_id)->orderBy('id')->first();
            if ($expense) {
                return $expense;
            }
        }

        abort(404, $this->installmentAccountNotFoundMessage());
    }

    /**
     * @return array{unpaid_amount: float, paid_amount: float, paid_count: int, unpaid_count: int}
     */
    protected function installmentPlanSummary(ExpenseAccount $account): array
    {
        $base = $this->installmentQuery();
        $this->applyInstallmentRiderScope($base, $account);

        return [
            'unpaid_amount' => (float) (clone $base)->where('status', $this->installmentModelClass()::STATUS_PENDING)->sum('amount'),
            'paid_amount' => (float) (clone $base)->where('status', $this->installmentModelClass()::STATUS_PAID)->sum('amount'),
            'paid_count' => (int) (clone $base)->where('status', $this->installmentModelClass()::STATUS_PAID)->count(),
            'unpaid_count' => (int) (clone $base)->where('status', $this->installmentModelClass()::STATUS_PENDING)->count(),
        ];
    }

    /**
     * Keys used on visa_installment_plans.rider_id for this renewal account.
     * Plans are linked to expense_accounts.id so each renewal stays separate.
     * Legacy rider/ledger keys are only included for the rider's earliest account.
     *
     * @return list<int>
     */
    private function installmentPlanRiderIdKeys(ExpenseAccount $account): array
    {
        $keys = [(int) $account->id];

        $oldestAccountId = $this->expenseAccountBaseQuery()
            ->where('rider_id', $account->rider_id)
            ->orderBy('id')
            ->value('id');

        if ($oldestAccountId && (int) $oldestAccountId === (int) $account->id) {
            $keys[] = (int) $account->rider_id;
            if ($account->account_id) {
                $keys[] = (int) $account->account_id;
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<visa_installment_plan>  $query
     */
    protected function applyInstallmentRiderScope($query, ExpenseAccount $account)
    {
        $keys = $this->installmentPlanRiderIdKeys($account);
        if ($keys === []) {
            $query->whereRaw('0 = 1');

            return $query;
        }

        return $query->whereIn('rider_id', $keys);
    }

    /**
     * Persist installment rows + VL vouchers/transactions for one renewal expense account.
     * Always stores expense_accounts.id on visa_installment_plans.rider_id.
     *
     * @param  list<float|int|string>  $installmentAmounts
     * @return int Existing installment count before this insert (for numbering).
     */
    private function persistInstallmentPlanEntries(
        ExpenseAccount $expenseAccount,
        Riders $rider,
        Accounts $liabilityAccount,
        int $branchId,
        string $billingMonthYm,
        string $referenceNumber,
        float $totalAmount,
        array $installmentAmounts
    ): int {
        $existingInstallmentCount = (int) $this->installmentQuery()
            ->where(function ($q) use ($expenseAccount) {
                $this->applyInstallmentRiderScope($q, $expenseAccount);
            })
            ->count();

        $numberOfInstallments = count($installmentAmounts);
        $categoryLabel = null;
        if ($expenseAccount->renewal_category_id) {
            $categoryLabel = $expenseAccount->relationLoaded('renewalCategory')
                ? ($expenseAccount->renewalCategory->name ?? null)
                : ($expenseAccount->renewalCategory()->value('name'));
        }
        $categorySuffix = $categoryLabel ? (' - ' . $categoryLabel) : '';

        $TransactionService = new TransactionService();

        for ($i = 0; $i < $numberOfInstallments; $i++) {
            $billingDate = Carbon::parse($billingMonthYm . '-01')->addMonths($i);
            $billingMonth = $billingDate->format('Y-m-d');
            $billingMonthFormatted = $billingDate->format('Y-m');
            $installmentDate = $billingDate->copy()->addMonth()->day(10)->format('Y-m-d');
            $installmentAmount = round((float) $installmentAmounts[$i], 2);
            $installmentNumber = $i + 1 + $existingInstallmentCount;

            $installment = $this->installmentModelClass()::create([
                'rider_id' => $expenseAccount->id,
                'branch_id' => $branchId,
                'billing_month' => $billingMonthFormatted,
                'amount' => $installmentAmount,
                'total_amount' => $totalAmount,
                'reference_number' => $referenceNumber,
                'narration' => $rider->rider_id . ' - ' . $rider->name . $categorySuffix
                    . '<b> - installment ' . $installmentNumber . '</b>',
                'status' => $this->installmentModelClass()::STATUS_PENDING,
                'date' => $installmentDate,
                'created_by' => auth()->user()->id ?? null,
            ]);

            $trans_code = Account::trans_code();
            $trans_date = Carbon::today();

            $this->createInstallmentVoucher([
                'trans_date' => $trans_date,
                'trans_code' => $trans_code,
                'billing_month' => $billingMonth,
                'payment_type' => 1,
                'voucher_type' => $this->installmentVoucherType(),
                'remarks' => $this->installmentVoucherRemarksPrefix() . $categorySuffix . ' - <b>Installment ' . $installmentNumber . ' of '
                    . ($numberOfInstallments + $existingInstallmentCount) . '</b>'
                    . ' (Amount: <b>' . number_format($installmentAmount, 2) . '</b>)',
                'amount' => $installmentAmount,
                'reference_number' => $referenceNumber,
                'Created_By' => auth()->user()->id ?? null,
                'ref_id' => $installment->id,
                'custom_field_values' => [],
                'branch_id' => $branchId,
            ]);

            $TransactionService->recordTransaction([
                'account_id' => $liabilityAccount->id,
                'reference_id' => $installment->id,
                'reference_type' => $this->installmentReferenceType(),
                'trans_code' => $trans_code,
                'trans_date' => $trans_date,
                'narration' => $rider->rider_id . ' - ' . $rider->name . $categorySuffix
                    . '<b> - installment ' . $installmentNumber . '</b>',
                'debit' => $installmentAmount,
                'branch_id' => $branchId,
                'billing_month' => $billingMonth,
                'created_by' => auth()->user()->id ?? null,
            ]);

            $TransactionService->recordTransaction([
                'account_id' => GlobalAccounts::id($this->installmentHeadAccountKey()),
                'reference_id' => $installment->id,
                'reference_type' => $this->installmentReferenceType(),
                'trans_code' => $trans_code,
                'trans_date' => $trans_date,
                'narration' => $rider->rider_id . ' - ' . $rider->name . $categorySuffix
                    . '<b> - installment ' . $installmentNumber . '</b>',
                'credit' => $installmentAmount,
                'branch_id' => $branchId,
                'billing_month' => $billingMonth,
                'created_by' => auth()->user()->id ?? null,
            ]);

            $lastLedger = CompanyQuery::table('ledger_entries')
                ->where('account_id', $liabilityAccount->id)
                ->orderBy('billing_month', 'desc')
                ->first();

            $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;

            CompanyQuery::insert('ledger_entries', [
                'account_id' => $liabilityAccount->id,
                'billing_month' => $billingMonth,
                'opening_balance' => $opening_balance,
                'debit_balance' => $installmentAmount,
                'credit_balance' => 0.00,
                'closing_balance' => $opening_balance + $installmentAmount,
                'created_at' => now(),
                'updated_at' => now(),
                'branch_id' => $branchId,
            ]);
        }

        return $existingInstallmentCount;
    }

    /**
     * Create a fresh installment plan for a renewal expense account (never reuses a sibling plan).
     *
     * @return array{created: bool, message: string}
     */
    private function autoCreateInstallmentPlanForRenewal(ExpenseAccount $expenseAccount, float $totalAmount): array
    {
        $totalAmount = round($totalAmount, 2);
        if ($totalAmount <= 0) {
            return [
                'created' => false,
                'message' => 'Installment plan was not auto-created because the renewal total amount is zero. Create one manually when ready.',
            ];
        }

        $expenseAccount->loadMissing(['rider', 'renewalCategory']);
        $rider = $expenseAccount->rider ?? Riders::find($expenseAccount->rider_id);
        if (!$rider) {
            return [
                'created' => false,
                'message' => 'Installment plan was not auto-created because the rider could not be resolved.',
            ];
        }

        $branchId = (int) ($rider->branch_id ?? 0);
        if ($branchId <= 0) {
            return [
                'created' => false,
                'message' => 'Installment plan was not auto-created because the rider has no branch assigned.',
            ];
        }

        $liabilityAccount = Accounts::where('ref_id', $rider->id)
            ->where('account_type', 'Liability')
            ->where('parent_id', 1)
            ->first();

        if (!$liabilityAccount) {
            $liabilityAccount = Accounts::where('ref_id', $rider->id)
                ->where('account_type', 'Liability')
                ->first();
        }

        if (!$liabilityAccount) {
            return [
                'created' => false,
                'message' => 'Installment plan was not auto-created because the rider liability account was not found.',
            ];
        }

        $numberOfInstallments = $this->resolveAutoInstallmentCountForRenewal($expenseAccount);
        $installmentAmounts = $this->splitAmountAcrossInstallments($totalAmount, $numberOfInstallments);
        $billingMonthYm = Carbon::today()->format('Y-m');
        $categoryName = $expenseAccount->renewalCategory->name ?? ('Renewal-' . $expenseAccount->id);
        $referenceNumber = 'VI-' . $expenseAccount->id . '-' . preg_replace('/\s+/', '', $categoryName) . '-' . Carbon::today()->format('Ymd');

        $this->persistInstallmentPlanEntries(
            $expenseAccount,
            $rider,
            $liabilityAccount,
            $branchId,
            $billingMonthYm,
            $referenceNumber,
            $totalAmount,
            $installmentAmounts
        );

        return [
            'created' => true,
            'message' => 'A new installment plan (' . $numberOfInstallments . ' installment'
                . ($numberOfInstallments === 1 ? '' : 's') . ', total '
                . number_format($totalAmount, 2) . ') was created for ' . $categoryName . '.',
        ];
    }

    private function resolveAutoInstallmentCountForRenewal(ExpenseAccount $expenseAccount): int
    {
        $previousAccount = $this->expenseAccountBaseQuery()
            ->where('rider_id', $expenseAccount->rider_id)
            ->where('id', '<', $expenseAccount->id)
            ->orderByDesc('id')
            ->first();

        if (!$previousAccount) {
            return 1;
        }

        $previousCount = (int) $this->installmentQuery()
            ->where(function ($q) use ($previousAccount) {
                $this->applyInstallmentRiderScope($q, $previousAccount);
            })
            ->count();

        return max(1, min(12, $previousCount > 0 ? $previousCount : 1));
    }

    /**
     * @return list<float>
     */
    private function splitAmountAcrossInstallments(float $totalAmount, int $numberOfInstallments): array
    {
        $numberOfInstallments = max(1, min(12, $numberOfInstallments));
        $base = round($totalAmount / $numberOfInstallments, 2);
        $amounts = array_fill(0, $numberOfInstallments, $base);
        $amounts[$numberOfInstallments - 1] = round(
            $totalAmount - array_sum(array_slice($amounts, 0, $numberOfInstallments - 1)),
            2
        );

        return $amounts;
    }

    /**
     * visa_installment_plans.rider_id is used inconsistently: expense_accounts.id, riders.id, or accounts.id.
     *
     * @return array{riderAccount: Accounts, rider: Riders}
     */
    private function resolveInstallmentRiderContext($installment): array
    {
        $key = (int) $installment->rider_id;

        if ($expense = ExpenseAccount::find($key)) {
            $rider = $expense->rider ?? Riders::findOrFail($expense->rider_id);
            $riderAccount = Accounts::where('ref_id', $rider->id)->first();
            if (!$riderAccount && $expense->account_id) {
                $riderAccount = Accounts::find($expense->account_id);
            }
            if (!$riderAccount) {
                throw new \RuntimeException(
                    'Could not find a ledger account for this rider (expense account #' . $key . ').'
                );
            }

            return ['riderAccount' => $riderAccount, 'rider' => $rider];
        }

        if ($rider = Riders::find($key)) {
            $riderAccount = Accounts::where('ref_id', $rider->id)->first();
            if (!$riderAccount) {
                throw new \RuntimeException(
                    'Could not find accounts row with ref_id = rider #' . $key . ' for visa installment resolution.'
                );
            }

            return ['riderAccount' => $riderAccount, 'rider' => $rider];
        }

        $riderAccount = Accounts::find($key);
        if (!$riderAccount || !$riderAccount->ref_id) {
            throw new \RuntimeException(
                'Installment rider_id ' . $key . ' could not be resolved to a rider ledger account.'
            );
        }

        return [
            'riderAccount' => $riderAccount,
            'rider' => Riders::findOrFail($riderAccount->ref_id),
        ];
    }

    /**
     * Update subsequent installments based on the current installment change
     */
    private function updateSubsequentInstallments($currentInstallment, $field, $newValue, $rider)
    {
        // Get subsequent pending installments (id > current, same rider), ordered by ID
        // Works for both paid and pending current installment
        $subsequentInstallments = $this->installmentModelClass()::where('rider_id', $currentInstallment->rider_id)
            ->where('id', '>', $currentInstallment->id)
            ->where('status', $this->installmentModelClass()::STATUS_PENDING)
            ->orderBy('id', 'asc')
            ->get();

        if ($subsequentInstallments->isEmpty()) {
            return;
        }

        $monthIncrement = 1;
        foreach ($subsequentInstallments as $subsequentInstallment) {
            if ($field === 'billing_month') {
                // Calculate new billing month for subsequent installments
                $baseDate = Carbon::parse($newValue . '-01');
                $newBillingMonth = $baseDate->copy()->addMonths($monthIncrement)->format('Y-m');
                $billingMonthWithDay = $newBillingMonth . '-01';

                // Calculate new date - set to 10th of next month from billing month
                $newInstallmentDate = $baseDate->copy()->addMonths($monthIncrement)->addMonth()->day(10)->format('Y-m-d');

                $subsequentInstallment->billing_month = $newBillingMonth;
                $subsequentInstallment->date = $newInstallmentDate;
                $subsequentInstallment->updated_by = auth()->user()->id;
                $subsequentInstallment->save();

                // Update related voucher
                $voucher = $this->installmentVouchersFor($subsequentInstallment->id)
                    ->first();
                if ($voucher) {
                    $voucher->billing_month = $billingMonthWithDay;
                    $voucher->trans_date = $newInstallmentDate;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                }

                // Update related transactions
                $transactions = $this->installmentTransactionsFor($subsequentInstallment->id)
                    ->get();
                foreach ($transactions as $transaction) {
                    $transaction->billing_month = $billingMonthWithDay;
                    $transaction->trans_date = $newInstallmentDate;
                    $transaction->updated_at = now();
                    $transaction->save();
                }
            } elseif ($field === 'date') {
                // Calculate new date for subsequent installments
                $baseDate = Carbon::parse($newValue);
                $newDate = $baseDate->copy()->addMonths($monthIncrement)->format('Y-m-d');

                $subsequentInstallment->date = $newDate;
                $subsequentInstallment->updated_by = auth()->user()->id;
                $subsequentInstallment->save();

                // Update related voucher
                $voucher = $this->installmentVouchersFor($subsequentInstallment->id)
                    ->first();
                if ($voucher) {
                    $voucher->trans_date = $newDate;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                }

                // Update related transactions
                $transactions = $this->installmentTransactionsFor($subsequentInstallment->id)
                    ->get();
                foreach ($transactions as $transaction) {
                    $transaction->trans_date = $newDate;
                    $transaction->updated_at = now();
                    $transaction->save();
                }
            } elseif ($field === 'amount') {
                // For amount changes, we need to recalculate proportionally
                // This will be handled in the main method, not here
                // Skip subsequent updates for amount field
                break;
            }

            $monthIncrement++;
        }
    }

    /**
     * Recalculate installment amounts proportionally when one amount changes
     */
    private function recalculateInstallmentAmounts($currentInstallment, $newAmount, $rider)
    {
        // Get all installments for this rider (including current one)
        $allInstallments = $this->installmentModelClass()::where('rider_id', $currentInstallment->rider_id)
            ->where('status', $this->installmentModelClass()::STATUS_PENDING)
            ->orderBy('date', 'asc')
            ->get();

        if ($allInstallments->count() <= 1) {
            return; // No other installments to update
        }

        // Calculate current total
        $currentTotal = $allInstallments->sum('amount');

        // Calculate new total (current total - old amount + new amount)
        $oldAmount = $currentInstallment->amount;
        $newTotal = $currentTotal - $oldAmount + $newAmount;

        // Calculate new amount per installment (equal distribution)
        $newAmountPerInstallment = $newTotal / $allInstallments->count();

        // Update all installments with the new calculated amount
        foreach ($allInstallments as $installment) {
            $installment->amount = $newAmountPerInstallment;
            $installment->updated_by = auth()->user()->id;
            $installment->save();

            // Update related voucher
            $voucher = $this->installmentVouchersFor($installment->id)
                ->first();
            if ($voucher) {
                $voucher->amount = $newAmountPerInstallment;
                $voucher->updated_by = auth()->user()->id;
                $voucher->save();
            }

            // Update related transactions
            $transactions = $this->installmentTransactionsFor($installment->id)
                ->get();
            foreach ($transactions as $transaction) {
                if ($transaction->credit > 0) {
                    $transaction->credit = $newAmountPerInstallment;
                } else {
                    $transaction->debit = $newAmountPerInstallment;
                }
                $transaction->updated_at = now();
                $transaction->save();
            }

            // Update related ledger entry
            ['riderAccount' => $riderAccount] = $this->resolveInstallmentRiderContext($installment);
            $liabilityAccount = Accounts::where('ref_id', $riderAccount->ref_id)
                ->where('account_type', 'Liability')
                ->where('parent_id', 1)
                ->first();

            if ($liabilityAccount) {
                $billingMonthForLedger = (strlen($installment->billing_month) <= 7) ?
                    $installment->billing_month . '-01' : $installment->billing_month;

                $ledgerEntry = \App\Support\CompanyQuery::table('ledger_entries')
                    ->where('account_id', $liabilityAccount->id)
                    ->where('billing_month', $billingMonthForLedger)
                    ->first();

                if ($ledgerEntry) {
                    \App\Support\CompanyQuery::table('ledger_entries')
                        ->where('account_id', $liabilityAccount->id)
                        ->where('billing_month', $billingMonthForLedger)
                        ->update([
                            'debit_balance' => $newAmountPerInstallment,
                            'closing_balance' => $ledgerEntry->opening_balance + $newAmountPerInstallment,
                            'updated_at' => now(),
                        ]);
                }
            }
        }
    }

    /**
     * Automatically mark installments as paid when their date equals today
     *
     * @param  int|ExpenseAccount|null  $riderId  Rider id, expense account, or null for all
     */
    public function autoMarkInstallmentsAsPaid($company_slug, $riderId = null)
    {
        $today = Carbon::today()->format('Y-m-d');

        $query = $this->installmentModelClass()::where('status', $this->installmentModelClass()::STATUS_PENDING)
            ->where('date', '<=', $today);

        if ($riderId instanceof ExpenseAccount) {
            $this->applyInstallmentRiderScope($query, $riderId);
        } elseif ($riderId) {
            $expenseAccount = $this->expenseAccountBaseQuery()->find($riderId)
                ?? $this->expenseAccountBaseQuery()->where('rider_id', $riderId)->orderBy('id')->first();
            if ($expenseAccount) {
                $this->applyInstallmentRiderScope($query, $expenseAccount);
            } else {
                $query->where('rider_id', $riderId);
            }
        }

        $installmentsToUpdate = $query->get();
        if ($installmentsToUpdate->isEmpty()) {
            return 0;
        }

        $updatedCount = 0;
        foreach ($installmentsToUpdate as $installment) {
            try {
                DB::beginTransaction();

                // Extract billing month (assuming 'billing_month' is Y-m-d or Y-m format in DB)
                $billingMonth = Carbon::parse($installment->billing_month);

                // Set the 20th of that billing month
                $billingDueDate = $billingMonth->copy()->day(20);

                // Skip if today's date is before the 20th of that month
                if (Carbon::today()->lt($billingDueDate)) {
                    DB::rollBack();
                    continue;
                }

                // Mark installment as paid
                $installment->status = $this->installmentModelClass()::STATUS_PAID;
                $installment->updated_by = auth()->user()->id ?? 1;
                $installment->save();

                // Update related voucher
                $voucher = $this->installmentVouchersFor($installment->id)
                    ->first();

                if ($voucher) {
                    $voucher->remarks = ($voucher->remarks ?? '') . ' - Auto-paid on ' . $today;
                    $voucher->save();
                }

                DB::commit();
                $updatedCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error auto-marking installment as paid: ' . $e->getMessage(), [
                    'installment_id' => $installment->id,
                    'rider_id' => $installment->rider_id
                ]);
            }
        }
        return $updatedCount;
    }

    /**
     * Check and auto-mark installments for a specific renewal account (silent operation)
     *
     * @param  int|ExpenseAccount  $riderOrAccount
     */
    private function checkAndAutoMarkInstallments($riderOrAccount)
    {
        try {
            // Only run if user is authenticated
            if (!auth()->check()) {
                return 0;
            }

            $updatedCount = $this->autoMarkInstallmentsAsPaid(null, $riderOrAccount);

            // Silent operation - no flash messages to user
            // Only log for admin/debugging purposes
            if ($updatedCount > 0) {
                $label = $riderOrAccount instanceof ExpenseAccount
                    ? ('expense account ' . $riderOrAccount->id)
                    : ('rider ' . $riderOrAccount);
                \Log::info("Auto-marked {$updatedCount} installment(s) as paid for {$label}");
            }

            return $updatedCount;
        } catch (\Exception $e) {
            // Log error but don't break the main request
            \Log::error("Error in checkAndAutoMarkInstallments: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Recalculate installment amounts when one is edited
     * This method ensures the total amount is preserved and remaining installments are adjusted
     */
    public function recalculateInstallments(Request $request)
    {
        if (!$this->installmentAllows('edit')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'rider_id' => 'required|exists:accounts,id',
            'edited_installment_id' => 'required|exists:' . $this->installmentTable() . ',id',
            'new_amount' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Get all pending installments for this rider
            $installments = $this->installmentModelClass()::where('rider_id', $validated['rider_id'])
                ->where('status', $this->installmentModelClass()::STATUS_PENDING)
                ->orderBy('id', 'asc')
                ->get();

            if ($installments->isEmpty()) {
                Flash::error('No pending installments found for this rider.');
                return redirect()->back();
            }

            // Get the total amount from the first installment (all should have the same total_amount)
            $totalAmount = $installments->first()->total_amount;
            if (!$totalAmount) {
                Flash::error('Total amount not found. Please recreate the installment plan.');
                return redirect()->back();
            }

            // Find the edited installment
            $editedInstallment = $installments->where('id', $validated['edited_installment_id'])->first();
            if (!$editedInstallment) {
                Flash::error('Installment not found.');
                return redirect()->back();
            }

            // Step 1: Calculate remaining balance
            $remainingBalance = $totalAmount - $validated['new_amount'];

            // Step 2: Get remaining installments (excluding the edited one)
            $remainingInstallments = $installments->where('id', '!=', $validated['edited_installment_id']);

            if ($remainingInstallments->isEmpty()) {
                Flash::error('No remaining installments to adjust.');
                return redirect()->back();
            }

            // Step 3: Distribute balance equally among remaining installments
            $amountPerInstallment = $remainingBalance / $remainingInstallments->count();

            // Step 4: Apply rounding - round all but the last installment
            $roundedAmount = floor($amountPerInstallment * 100) / 100; // Round down to 2 decimal places
            $lastInstallment = $remainingInstallments->last();

            // Update all remaining installments
            foreach ($remainingInstallments as $index => $installment) {
                if ($installment->id === $lastInstallment->id) {
                    // Last installment gets the remaining balance to handle rounding
                    $usedAmount = $validated['new_amount'] + ($roundedAmount * ($remainingInstallments->count() - 1));
                    $lastAmount = $totalAmount - $usedAmount;
                    $installment->amount = $lastAmount;
                } else {
                    $installment->amount = $roundedAmount;
                }
                $installment->updated_by = auth()->user()->id;
                $installment->save();

                // Update related voucher
                $voucher = $this->installmentVouchersFor($installment->id)
                    ->first();
                if ($voucher) {
                    $voucher->amount = $installment->amount;
                    $voucher->updated_by = auth()->user()->id;
                    $voucher->save();
                }

                // Update related transactions
                $transactions = $this->installmentTransactionsFor($installment->id)
                    ->get();
                foreach ($transactions as $transaction) {
                    if ($transaction->credit > 0) {
                        $transaction->credit = $installment->amount;
                    } else {
                        $transaction->debit = $installment->amount;
                    }
                    $transaction->updated_at = now();
                    $transaction->save();
                }
            }

            // Update the edited installment
            $editedInstallment->amount = $validated['new_amount'];
            $editedInstallment->updated_by = auth()->user()->id;
            $editedInstallment->save();

            // Update related voucher for edited installment
            $voucher = $this->installmentVouchersFor($editedInstallment->id)
                ->first();
            if ($voucher) {
                $voucher->amount = $editedInstallment->amount;
                $voucher->updated_by = auth()->user()->id;
                $voucher->save();
            }

            // Update related transactions for edited installment
            $transactions = $this->installmentTransactionsFor($editedInstallment->id)
                ->get();
            foreach ($transactions as $transaction) {
                if ($transaction->credit > 0) {
                    $transaction->credit = $editedInstallment->amount;
                } else {
                    $transaction->debit = $editedInstallment->amount;
                }
                $transaction->updated_at = now();
                $transaction->save();
            }

            DB::commit();

            Flash::success('Installment amounts recalculated successfully. Total amount preserved: ' . number_format($totalAmount, 2));
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error recalculating installments: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}

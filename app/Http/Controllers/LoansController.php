<?php

namespace App\Http\Controllers;

use App\DataTables\LedgerDataTable;
use App\Http\Requests\CreateLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Transactions;
use App\Repositories\LoanRepository;
use App\Services\LoanAmortizationService;
use App\Services\LoanVoucherService;
use App\Support\GlobalAccounts;
use App\Support\CompanyQuery;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoansController extends AppBaseController
{
    use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;

    public function __construct(
        protected LoanRepository $loanRepository,
        protected LoanAmortizationService $amortizationService,
        protected LoanVoucherService $loanVoucherService
    ) {}

    public function index(Request $request)
    {
        if (! user_can('loan_view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->markOverdueInstallments();

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Loan::query()
            ->with(['receivingBank'])
            ->orderByDesc('id');

        if ($request->filled('loan_number')) {
            $query->where('loan_number', 'like', '%'.$request->loan_number.'%');
        }
        if ($request->filled('bank_name')) {
            $query->where('bank_name', 'like', '%'.$request->bank_name.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('maturity_month')) {
            $query->whereYear('maturity_date', substr($request->maturity_month, 0, 4))
                ->whereMonth('maturity_date', substr($request->maturity_month, 5, 2));
        }

        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('loans.table', ['data' => $data])->render(),
                'paginationLinks' => $data->links('components.global-pagination')->render(),
            ]);
        }

        $activeLoanIds = Loan::where('status', Loan::STATUS_ACTIVE)->pluck('id');

        $summary = [
            'total_outstanding' => Loan::where('status', Loan::STATUS_ACTIVE)->sum('outstanding_principal'),
            'active_count' => $activeLoanIds->count(),
            'overdue_count' => LoanInstallment::whereIn('loan_id', $activeLoanIds)
                ->where('status', LoanInstallment::STATUS_OVERDUE)
                ->count(),
            'paid_principal' => LoanInstallment::whereIn('loan_id', $activeLoanIds)
                ->where('status', LoanInstallment::STATUS_PAID)
                ->sum('principal_amount'),
            'paid_interest' => LoanInstallment::whereIn('loan_id', $activeLoanIds)
                ->where('status', LoanInstallment::STATUS_PAID)
                ->sum('interest_amount'),
            'draft_count' => Loan::where('status', Loan::STATUS_DRAFT)->count(),
        ];

        return view('loans.index', compact('data', 'summary'));
    }

    public function upcomingInstallments(Request $request)
    {
        if (! user_can('loan_installment_view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->markOverdueInstallments();

        $days = (int) $request->get('days', 30);
        $until = Carbon::today()->addDays($days);

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = LoanInstallment::query()
            ->with(['loan'])
            ->pending()
            ->whereBetween('due_date', [Carbon::today(), $until])
            ->orderBy('due_date');

        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('loans.upcoming_table', ['data' => $data])->render(),
                'paginationLinks' => $data->links('components.global-pagination')->render(),
            ]);
        }

        return view('loans.upcoming', compact('data', 'days'));
    }

    public function interestSummary(Request $request)
    {
        if (! user_can('loan_view')) {
            abort(403, 'Unauthorized action.');
        }

        $from = $request->get('from_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to_date', Carbon::now()->format('Y-m-d'));

        $rows = LoanInstallment::query()
            ->with('loan')
            ->where('status', LoanInstallment::STATUS_PAID)
            ->whereBetween('paid_date', [$from, $to])
            ->orderBy('paid_date')
            ->get()
            ->groupBy(fn ($row) => $row->loan?->bank_name ?: 'Unknown');

        return view('loans.interest_summary', compact('rows', 'from', 'to'));
    }

    public function create()
    {
        return view('loans.create');
    }

    public function store(CreateLoanRequest $request)
    {
        if (! user_can('loan_create')) {
            abort(403, 'Unauthorized action.');
        }

        $input = $request->all();

        try {
            DB::beginTransaction();

            unset($input['bank_id']);

            $loan = $this->loanRepository->create(array_merge($input, [
                'loan_number' => Loan::generateLoanNumber(),
                'bank_id' => null,
                'status' => Loan::STATUS_DRAFT,
                'outstanding_principal' => $input['principal_amount'],
                'created_by' => auth()->id(),
            ]));

            $this->regenerateSchedule($loan);

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Loan created successfully.', 'reload' => true]);
            }
            Flash::success('Loan created successfully.');

            return redirect()->route('loans.show', $loan->id);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating loan: '.$e->getMessage());

            return $this->loanErrorResponse($request, $e->getMessage());
        }
    }

    public function show($company_slug, $id)
    {
        if (! user_can('loan_view')) {
            abort(403, 'Unauthorized action.');
        }

        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        $loan->load(['receivingBank', 'payingBank', 'installments']);
        $this->markOverdueInstallmentsForLoan($loan->id);

        $nextInstallment = $loan->pendingInstallments()->first();
        $coaBalance = 0;
        if ($loan->account_id) {
            $credit = Transactions::where('account_id', $loan->account_id)->sum('credit');
            $debit = Transactions::where('account_id', $loan->account_id)->sum('debit');
            $coaBalance = $credit - $debit;
        }

        return view('loans.show', compact('loan', 'nextInstallment', 'coaBalance'));
    }

    public function edit($company_slug, $id)
    {
        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        if (! $loan->isEditable()) {
            Flash::error('This loan cannot be edited after disbursement or payments.');

            return redirect()->route('loans.show', $loan->id);
        }

        return view('loans.edit', compact('loan'));
    }

    public function update($company_slug, $id, UpdateLoanRequest $request)
    {
        if (! user_can('loan_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            return $this->loanErrorResponse($request, 'Loan not found.');
        }

        if (! $loan->isEditable()) {
            return $this->loanErrorResponse($request, 'This loan cannot be edited.');
        }

        try {
            DB::beginTransaction();
            $input = $request->all();
            unset($input['bank_id']);
            $loan = $this->loanRepository->update($input, $id);
            $loan->bank_id = null;
            $loan->outstanding_principal = $loan->principal_amount;
            $loan->save();
            $this->regenerateSchedule($loan);
            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Loan updated successfully.', 'reload' => true]);
            }
            Flash::success('Loan updated successfully.');

            return redirect()->route('loans.show', $loan->id);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->loanErrorResponse($request, $e->getMessage());
        }
    }

    public function destroy($company_slug, $id)
    {
        if (! user_can('loan_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            return response()->json(['message' => 'Loan not found.'], 404);
        }

        if ($loan->status === Loan::STATUS_ACTIVE) {
            return response()->json(['message' => 'Active loans cannot be deleted.'], 422);
        }

        DB::beginTransaction();
        try {
            $installments = $loan->installments()->get();
            $loanIdentifier = $loan->loan_number ?: 'Loan #'.$loan->id;

            // Queue loan first so related installment deletes attach as cascades
            // when delete-approval is enabled, and stay recoverable from Recycle Bin.
            $loan->deleted_by = auth()->id();
            $loan->save();
            $loan->delete();

            $pendingQueued = (bool) request()->attributes->get('delete_approval_created');

            foreach ($installments as $installment) {
                $installmentLabel = 'Installment #'.$installment->installment_no.' — '.$loanIdentifier;
                $installment->delete();

                $this->trackCascadeDeletion(
                    Loan::class,
                    $loan->id,
                    $loanIdentifier,
                    LoanInstallment::class,
                    $installment->id,
                    $installmentLabel,
                    'hasMany',
                    'installments',
                    'soft',
                    'Cascade deletion from loan delete'
                );
            }

            DB::commit();

            if ($pendingQueued) {
                return response()->json([
                    'message' => 'Delete request submitted for approval.',
                    'pending_approval' => true,
                ]);
            }

            return response()->json(['message' => 'Loan moved to recycle bin.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting loan: '.$e->getMessage());

            return response()->json(['message' => 'Failed to delete loan: '.$e->getMessage()], 500);
        }
    }

    public function disburse(Request $request, $company_slug, $id)
    {
        if (! user_can('loan_disburse')) {
            abort(403, 'Unauthorized action.');
        }

        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Loan not found.'], 404);
            }
            Flash::error('Loan not found');

            return redirect()->back();
        }

        if (! $loan->receiving_bank_id) {
            $message = 'Please set a receiving bank account before disbursement.';
            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->route('loans.edit', $loan->id);
        }

        if ($loan->installments()->count() === 0) {
            $message = 'Generate an installment schedule before disbursement.';
            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        try {
            DB::beginTransaction();
            $this->loanVoucherService->disburse($loan);
            DB::commit();
            if ($request->ajax()) {
                return response()->json(['message' => 'Loan disbursed and GL entries posted successfully.', 'reload' => true]);
            }
            Flash::success('Loan disbursed and GL entries posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            Flash::error('Disbursement failed: '.$e->getMessage());
        }

        return redirect()->route('loans.show', $loan->id);
    }

    public function installments(Request $request, $company_slug, $id)
    {
        if (! user_can('loan_installment_view')) {
            abort(403, 'Unauthorized action.');
        }

        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        // Schedule is shown on the Overview tab.
        return redirect()->route('loans.show', $loan->id);
    }

    public function payInstallmentForm($company_slug, $id)
    {
        if (! user_can('loan_repay')) {
            abort(403, 'Unauthorized action.');
        }

        $installment = LoanInstallment::with(['loan.account'])->findOrFail($id);

        if (! $installment->canBePaid()) {
            abort(422, 'This installment cannot be paid.');
        }

        $banks = Banks::with('account')->orderBy('name')->get();
        $defaultBankId = $installment->loan->paying_bank_id ?? $installment->loan->receiving_bank_id;
        $defaultNarration = 'Loan EMI #'.$installment->installment_no.' — '.$installment->loan->loan_number;
        $defaultDate = $installment->due_date->isPast()
            ? Carbon::today()->format('Y-m-d')
            : $installment->due_date->format('Y-m-d');

        $loanPayableAccount = $installment->loan->account
            ?? Accounts::find($installment->loan->account_id);
        $interestAccount = Accounts::find(GlobalAccounts::id('LOAN_INTEREST_EXPENSE'));
        $loanPayableLabel = $loanPayableAccount?->name ?? 'Loans Payable';
        $interestAccountLabel = $interestAccount?->name ?? 'Loan Interest Expense';

        $bankAccountLabels = $banks->mapWithKeys(function ($bank) {
            $label = $bank->name;
            if ($bank->account) {
                $label .= ' ('.$bank->account->account_code.' — '.$bank->account->name.')';
            }

            return [$bank->id => $label];
        })->prepend('Select Bank', '');

        return view('loans.pay_installment', compact(
            'installment',
            'banks',
            'bankAccountLabels',
            'defaultBankId',
            'defaultNarration',
            'defaultDate',
            'loanPayableLabel',
            'interestAccountLabel'
        ));
    }

    public function payInstallment(Request $request, $company_slug, $id)
    {
        if (! user_can('loan_repay')) {
            abort(403, 'Unauthorized action.');
        }

        $installment = LoanInstallment::with('loan')->findOrFail($id);

        if (! $installment->canBePaid()) {
            if ($request->ajax()) {
                return response()->json(['message' => 'This installment cannot be paid.'], 422);
            }
            Flash::error('This installment cannot be paid.');

            return redirect()->back();
        }

        $validated = $request->validate([
            'paying_bank_id' => 'required|exists:banks,id',
            'payment_date' => 'required|date',
            'narration' => 'nullable|string|max:65535',
            'principal_amount' => 'required|numeric|min:0',
            'interest_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0.01',
            'loan_payable_narration' => 'nullable|string|max:65535',
            'interest_narration' => 'nullable|string|max:65535',
            'bank_narration' => 'nullable|string|max:65535',
        ]);

        $principal = round((float) $validated['principal_amount'], 2);
        $interest = round((float) $validated['interest_amount'], 2);
        $total = round((float) $validated['total_amount'], 2);
        if (abs($total - ($principal + $interest)) > 0.01) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Total must equal principal plus interest.'], 422);
            }
            Flash::error('Total must equal principal plus interest.');

            return redirect()->back();
        }

        try {
            DB::beginTransaction();
            $paymentDate = Carbon::parse($validated['payment_date']);
            $this->loanVoucherService->repayInstallment(
                $installment,
                (int) $validated['paying_bank_id'],
                $paymentDate,
                $validated['narration'] ?? null,
                $principal,
                $interest,
                $total,
                $validated['loan_payable_narration'] ?? null,
                $validated['interest_narration'] ?? null,
                $validated['bank_narration'] ?? null
            );
            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Installment payment recorded successfully.',
                    'reload' => true,
                ]);
            }
            Flash::success('Installment payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            Flash::error('Payment failed: '.$e->getMessage());
        }

        return redirect()->back();
    }

    public function regenerateScheduleAction(Request $request, $company_slug, $id)
    {
        if (! user_can('loan_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $loan = $this->loanRepository->find($id);
        if (! $loan || ! $loan->isEditable()) {
            Flash::error('Schedule cannot be regenerated for this loan.');

            return redirect()->back();
        }

        try {
            DB::beginTransaction();
            $this->regenerateSchedule($loan);
            DB::commit();
            Flash::success('Installment schedule regenerated.');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error($e->getMessage());
        }

        return redirect()->back();
    }

    public function files($company_slug, $id)
    {
        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        $files = CompanyQuery::table('files')->where('type', 'loan')->where('type_id', $id)->latest('id')->get();

        return view('loans.document', compact('files', 'loan'));
    }

    public function ledger($company_slug, $id, LedgerDataTable $ledgerDataTable)
    {
        $loan = $this->loanRepository->find($id);
        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        if (! $loan->account_id) {
            Flash::error('Ledger is available after the loan is disbursed.');

            return redirect()->route('loans.show', $loan->id);
        }

        $account_id = $loan->account_id;

        return $ledgerDataTable->with(['account_id' => $account_id])
            ->render('loans.ledger', compact('loan'));
    }

    protected function regenerateSchedule(Loan $loan): void
    {
        $schedule = $this->amortizationService->buildSchedule(
            (float) $loan->principal_amount,
            (float) $loan->interest_rate,
            (int) $loan->term_months,
            Carbon::parse($loan->first_payment_date),
            $loan->interest_calculation_method ?: Loan::INTEREST_REDUCING
        );

        $this->amortizationService->persistSchedule($loan, $schedule);
    }

    protected function markOverdueInstallments(): void
    {
        LoanInstallment::query()
            ->whereIn('status', [LoanInstallment::STATUS_PENDING, LoanInstallment::STATUS_PARTIAL])
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => LoanInstallment::STATUS_OVERDUE]);
    }

    protected function markOverdueInstallmentsForLoan(int $loanId): void
    {
        LoanInstallment::where('loan_id', $loanId)
            ->whereIn('status', [LoanInstallment::STATUS_PENDING, LoanInstallment::STATUS_PARTIAL])
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => LoanInstallment::STATUS_OVERDUE]);
    }

    protected function getTrashModelClass()
    {
        return Loan::class;
    }

    protected function getTrashConfig()
    {
        return [
            'name' => 'Loan',
            'module_key' => 'loans',
            'display_columns' => ['loan_number', 'bank_name', 'agreement_ref', 'status'],
            'trash_view' => 'loans.trash',
            'index_route' => 'loans.index',
        ];
    }

    protected function loanErrorResponse(Request $request, string $message)
    {
        if ($request->ajax()) {
            return response()->json(['message' => $message], 500);
        }
        Flash::error($message);

        return redirect()->back();
    }
}

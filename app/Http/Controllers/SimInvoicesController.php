<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Http\Controllers\AppBaseController;
use App\Imports\SimInvoiceImport;
use App\Models\Accounts;
use App\Models\SimInvoice;
use App\Models\Sims;
use App\Models\Transactions;
use App\Models\VoucherType;
use App\Models\Vouchers;
use App\Models\Payment;
use App\Models\SimCompany;
use App\Repositories\SimInvoicesRepository;
use App\Traits\GlobalPagination;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SimInvoicesController extends AppBaseController
{
    use GlobalPagination;

    private $simInvoicesRepository;

    public function __construct(SimInvoicesRepository $simInvoicesRepo)
    {
        $this->simInvoicesRepository = $simInvoicesRepo;
    }

    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = SimInvoice::query()->orderBy('billing_month', 'desc')->orderBy('id', 'desc');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }
        if ($request->filled('billing_month')) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->filled('status')) {
            if ((string) $request->status === '0') {
                $query->where(function ($q) {
                    $q->whereNull('status')->orWhereNotIn('status', [1, 3]);
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        $statsQuery = clone $query;
        $totalAmount = (float) (clone $statsQuery)->sum('total_amount');
        $paidAmount = (float) (clone $statsQuery)->where('status', 1)->sum('total_amount');
        $partialPaid = (clone $statsQuery)->where('status', 3)->get(['total_amount', 'partial_paid_amount'])
            ->sum(fn (SimInvoice $invoice) => array_sum($invoice->partial_paid_amount ?? []));
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'unpaid' => (clone $statsQuery)->where(function ($q) {
                $q->whereNull('status')->orWhereNotIn('status', [1, 3]);
            })->count(),
            'partial' => (clone $statsQuery)->where('status', 3)->count(),
            'paid' => (clone $statsQuery)->where('status', 1)->count(),
            'total_amount' => $totalAmount,
            'outstanding' => $totalAmount - $paidAmount - (float) $partialPaid,
        ];

        $data = $this->applyPagination(
            (clone $query)->with('company')->withCount('items'),
            $paginationParams
        );
        $companies = SimCompany::where('status', 1)->orderBy('name')->get();

        if ($request->ajax()) {
            $tableData = view('sim_invoices.table', compact('data'))->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'stats' => $stats,
            ]);
        }

        return view('sim_invoices.index', compact('data', 'companies', 'stats'));
    }

    public function create($company_slug, $companyId = null)
    {
        $companies = SimCompany::where('status', 1)->orderBy('name')->pluck('name', 'id')->prepend('Select', '')->toArray();
        $sims = Sims::orderBy('number')->get()->mapWithKeys(function ($sim) {
            return [$sim->id => $sim->number . ' - ' . ($sim->company ?? '')];
        })->prepend('Select', '')->toArray();
        $company = $companyId ? SimCompany::find($companyId) : null;

        return view('sim_invoices.create', compact('companies', 'sims', 'company'));
    }

    public function createFromClone($company_slug, $id)
    {
        $sourceInvoice = $this->simInvoicesRepository->find($id);
        if (empty($sourceInvoice)) {
            $message = 'Source invoice not found.';
            if (request()->ajax()) {
                return response()->view('sim_invoices.modal_error', compact('message'), 200);
            }
            Flash::error($message);
            return redirect(route('simInvoices.index'));
        }

        $sourceInvoice->load('items');
        $nextMonth = \Carbon\Carbon::parse($sourceInvoice->billing_month)->addMonth();
        $nextMonthString = $nextMonth->format('Y-m');

        $existingInvoice = SimInvoice::where('vendor_id', $sourceInvoice->vendor_id)
            ->whereYear('billing_month', $nextMonth->year)
            ->whereMonth('billing_month', $nextMonth->month)
            ->first();

        if ($existingInvoice) {
            $message = 'An invoice for this company already exists for ' . $nextMonthString . '.';
            if (request()->ajax()) {
                return response()->view('sim_invoices.modal_error', compact('message'), 200);
            }
            Flash::error($message);
            return redirect(route('simInvoices.index'));
        }

        $companies = SimCompany::where('status', 1)->orderBy('name')->pluck('name', 'id')->prepend('Select', '')->toArray();
        $sims = Sims::where('vendor', $sourceInvoice->vendor_id)->orderBy('number')->get()->mapWithKeys(function ($sim) {
            return [$sim->id => $sim->number . ' - ' . ($sim->company ?? '')];
        })->prepend('Select', '')->toArray();

        $cloneItems = [];
        foreach ($sourceInvoice->items as $item) {
            $cloneItems[] = [
                'sim_id' => $item->sim_id,
                'rental_amount' => (float) $item->rental_amount,
                'additional_charges' => (float) ($item->additional_charges ?? 0),
                'international_usage_charges' => (float) ($item->international_usage_charges ?? 0),
                'tax_rate' => (float) ($item->tax_rate ?? 5),
            ];
        }

        $cloneFromInvoice = (object) [
            'inv_date' => now()->format('Y-m-d'),
            'billing_month' => $nextMonthString . '-01',
            'company_id' => $sourceInvoice->vendor_id,
            'descriptions' => $sourceInvoice->descriptions ?? '',
            'notes' => $sourceInvoice->notes ?? '',
        ];

        $nextBillingMonth = $nextMonthString;
        return view('sim_invoices.create', compact('companies', 'sims', 'cloneItems', 'cloneFromInvoice', 'nextBillingMonth'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'inv_date' => 'required|date',
                'billing_month' => 'required',
                'company_id' => 'required|exists:sim_companies,id',
                'reference_number' => 'required|string|max:255',
                'sim_id' => 'required|array|min:1',
                'sim_id.*' => 'required',
                'rental_amount' => 'required|array|min:1',
                'rental_amount.*' => 'numeric|min:0',
                'additional_charges' => 'nullable|array',
                'additional_charges.*' => 'nullable|numeric|min:0',
                'international_usage_charges' => 'nullable|array',
                'international_usage_charges.*' => 'nullable|numeric|min:0',
                'descriptions' => 'nullable|string',
                'notes' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            ]);

            $invoice = $this->simInvoicesRepository->record($request);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Invoice created successfully.',
                    'reload' => true,
                ], 200);
            }

            Flash::success('Invoice created successfully.');
            return redirect(route('simInvoices.show', $invoice->id));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
            }
            Flash::error($e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function importForm($company_slug)
    {
        if (!user_can('sims_invoices_create')) {
            abort(403, 'Unauthorized action.');
        }

        $companies = SimCompany::where('status', 1)->orderBy('name')->pluck('name', 'id')->prepend('Select', '')->toArray();
        $defaultVat = Common::getSetting('vat_percentage') ?? 0;

        return view('sim_invoices.import', compact('companies', 'defaultVat'));
    }

    public function import(Request $request, $company_slug)
    {
        if (!user_can('sims_invoices_create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
            'company_id' => 'required|exists:sim_companies,id',
            'billing_month' => 'required|date_format:Y-m',
            'inv_date' => 'required|date',
            'reference_number' => 'required|string|max:255',
            'vat_percent' => 'nullable|numeric|min:0',
            'descriptions' => 'nullable|string',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'col_sim_number' => 'required|integer|min:1',
            'col_monthly_charges' => 'required|integer|min:1',
            'col_intl_usage_charges' => 'nullable|integer|min:1',
            'col_additional_charges' => 'nullable|integer|min:1',
            'col_vat' => 'nullable|integer|min:1',
        ]);

        $columnMap = [
            'sim_number' => (int) $request->col_sim_number,
            'monthly_charges' => (int) $request->col_monthly_charges,
            'intl_usage_charges' => $request->filled('col_intl_usage_charges') ? (int) $request->col_intl_usage_charges : null,
            'additional_charges' => $request->filled('col_additional_charges') ? (int) $request->col_additional_charges : null,
            'vat' => $request->filled('col_vat') ? (int) $request->col_vat : null,
        ];

        $provided = array_filter($columnMap, fn ($v) => $v !== null);
        if (count($provided) !== count(array_unique($provided))) {
            $message = 'Column numbers must be unique.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        $billingMonth = $request->billing_month . '-01';
        $existingInvoice = SimInvoice::where('vendor_id', $request->company_id)
            ->where('billing_month', $billingMonth)
            ->first();
        if ($existingInvoice) {
            $message = 'An invoice for this company has already been generated for the selected billing month.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        try {
            $import = new SimInvoiceImport(
                (int) $request->company_id,
                $columnMap,
                $request->vat_percent ?? 0
            );
            Excel::import($import, $request->file('file'));

            $skippedLog = array_values($import->skippedLog);
            $skippedCount = count($skippedLog);

            if (empty($import->items)) {
                $message = 'No valid SIM rows were found in the file.';
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'imported_count' => 0,
                        'skipped_count' => $skippedCount,
                        'skipped_log' => $skippedLog,
                    ], 422);
                }
                Flash::error($message);
                if ($skippedCount > 0) {
                    session()->flash('import_skipped_log', $skippedLog);
                }
                return redirect()->back();
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('invoice', 'public');
            }

            $invoice = $this->simInvoicesRepository->createFromImport([
                'vendor_id' => (int) $request->company_id,
                'inv_date' => $request->inv_date,
                'billing_month' => $request->billing_month,
                'reference_number' => $request->reference_number,
                'descriptions' => $request->descriptions,
                'notes' => $request->notes,
                'attachment' => $attachmentPath,
            ], $import->items);

            $importedCount = $import->importedCount;
            $message = "Import finished. Imported: {$importedCount} SIM line(s) into invoice {$invoice->invoice_number}.";
            if ($skippedCount > 0) {
                $message .= " Skipped: {$skippedCount}.";
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'skipped_log' => $skippedLog,
                    'redirect' => route('simInvoices.show', $invoice->id),
                ]);
            }

            Flash::success($message);
            if ($skippedCount > 0) {
                session()->flash('import_skipped_log', $skippedLog);
            }
            return redirect(route('simInvoices.show', $invoice->id));
        } catch (\Exception $e) {
            \Log::error('SIM invoice import failed: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed: ' . $e->getMessage(),
                ], 422);
            }
            Flash::error('Import failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function show($company_slug, $id)
    {
        $invoice = $this->simInvoicesRepository->find($id);
        if (empty($invoice)) {
            Flash::error('Invoice not found');
            return redirect(route('simInvoices.index'));
        }

        return view('sim_invoices.show')->with('invoice', $invoice);
    }

    public function edit($company_slug, $id)
    {
        $invoice = $this->simInvoicesRepository->find($id);
        if (empty($invoice)) {
            Flash::error('Invoice not found');
            return redirect(route('simInvoices.index'));
        }

        $invoice->load('items');
        $companies = SimCompany::where('status', 1)->orderBy('name')->pluck('name', 'id')->prepend('Select', '')->toArray();
        $sims = Sims::orderBy('number')->get()->mapWithKeys(function ($sim) {
            return [$sim->id => $sim->number . ' - ' . ($sim->company ?? '')];
        })->prepend('Select', '')->toArray();

        return view('sim_invoices.edit', compact('invoice', 'companies', 'sims'));
    }

    public function update(Request $request, $company_slug, $id)
    {
        try {
            $invoice = $this->simInvoicesRepository->find($id);
            if (empty($invoice)) {
                Flash::error('Invoice not found');
                return redirect(route('simInvoices.index'));
            }

            $request->validate([
                'inv_date' => 'required|date',
                'billing_month' => 'required',
                'company_id' => 'required|exists:sim_companies,id',
                'reference_number' => 'required|string|max:255',
                'sim_id' => 'required|array|min:1',
                'sim_id.*' => 'required|exists:sims,id',
                'rental_amount' => 'required|array|min:1',
                'rental_amount.*' => 'numeric|min:0',
                'additional_charges' => 'nullable|array',
                'additional_charges.*' => 'nullable|numeric|min:0',
                'international_usage_charges' => 'nullable|array',
                'international_usage_charges.*' => 'nullable|numeric|min:0',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            ]);

            if ($request->hasFile('attachment')) {
                $fileName = time() . '_' . str_replace(' ', '_', $request->file('attachment')->getClientOriginalName());
                $attachmentPath = $request->file('attachment')->storeAs('sim_invoices', $fileName, 'public');
                $request->merge(['attachment' => $attachmentPath]);

                if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                    Storage::disk('public')->delete($invoice->attachment);
                }
            }

            $invoice = $this->simInvoicesRepository->record($request, $id);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Invoice updated successfully.',
                    'redirect' => route('simInvoices.show', $invoice->id),
                ]);
            }

            Flash::success('Invoice updated successfully.');
            return redirect(route('simInvoices.show', $invoice->id));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
            }
            Flash::error($e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($company_slug, $id)
    {
        if (!user_can('sim_invoice_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $invoice = $this->simInvoicesRepository->find($id);
        if (empty($invoice)) {
            Flash::error('Invoice not found');
            return redirect(route('simInvoices.index'));
        }
        if ($invoice->status == 1) {
            Flash::error('Cannot delete paid invoice. Only unpaid invoices can be deleted.');
            return redirect(route('simInvoices.index'));
        }

        try {
            // Delete first: when delete-approval is enabled this only queues a request
            // and the invoice stays intact, so related records must not be touched yet.
            $invoice->delete();

            if (request()->attributes->get('delete_approval_created')) {
                return redirect(route('simInvoices.index'));
            }

            \App\Support\CompanyQuery::table('transactions')->where('reference_type', 'SimInvoice')->where('reference_id', $id)->delete();
            \App\Support\CompanyQuery::table('sim_invoice_items')->where('inv_id', $id)->delete();

            if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                Storage::disk('public')->delete($invoice->attachment);
            }

            Flash::success('Invoice deleted successfully.');
        } catch (\Exception $e) {
            Flash::error('Error deleting invoice: ' . $e->getMessage());
        }

        return redirect(route('simInvoices.index'));
    }

    public function clone(Request $request, $company_slug, $id)
    {
        try {
            $sourceInvoice = $this->simInvoicesRepository->find($id);
            if (empty($sourceInvoice)) {
                return response()->json(['errors' => ['error' => 'Source invoice not found!']], 422);
            }

            $nextMonth = \Carbon\Carbon::parse($sourceInvoice->billing_month)->addMonth();
            $existingInvoice = SimInvoice::where('vendor_id', $sourceInvoice->vendor_id)
                ->whereYear('billing_month', $nextMonth->year)
                ->whereMonth('billing_month', $nextMonth->month)
                ->first();

            if ($existingInvoice) {
                return response()->json(['errors' => ['error' => 'An invoice for this vendor already exists for ' . $nextMonth->format('Y-m') . '.']], 422);
            }

            DB::beginTransaction();

            $newInvoiceData = $sourceInvoice->toArray();
            unset($newInvoiceData['id'], $newInvoiceData['invoice_number'], $newInvoiceData['sim_invoice_number'], $newInvoiceData['created_at'], $newInvoiceData['updated_at'], $newInvoiceData['deleted_at']);
            $newInvoiceData['billing_month'] = $nextMonth->format('Y-m') . '-01';
            $newInvoiceData['inv_date'] = now()->format('Y-m-d');
            $newInvoiceData['status'] = 0;

            $newInvoice = SimInvoice::create($newInvoiceData);

            foreach ($sourceInvoice->items as $item) {
                $newItemData = $item->toArray();
                unset($newItemData['id'], $newItemData['days'], $newItemData['created_at'], $newItemData['updated_at']);
                $newItemData['inv_id'] = $newInvoice->id;
                \App\Support\CompanyQuery::insert('sim_invoice_items', $newItemData);
            }

            $items = \App\Support\CompanyQuery::table('sim_invoice_items')->where('inv_id', $newInvoice->id)->get();
            $newInvoice->vat = $items->sum('tax_amount');
            $newInvoice->total_amount = $items->sum('total_amount');
            $newInvoice->subtotal = $newInvoice->total_amount - $newInvoice->vat;
            $newInvoice->save();

            $this->simInvoicesRepository->recordTransactionsForInvoice($newInvoice);
            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Invoice cloned successfully.',
                    'redirect' => route('simInvoices.show', $newInvoice->id),
                ]);
            }

            Flash::success('Invoice cloned successfully.');
            return redirect(route('simInvoices.show', $newInvoice->id));
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
            }
            Flash::error('Error cloning invoice: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function getSims($company_slug, $id)
    {
        $company = SimCompany::find($id);
        if (empty($company)) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $sims = Sims::where('vendor', $id)->orderBy('number')->get(['id', 'number', 'company']);
        return response()->json(['sims' => $sims]);
    }

    public function payments(Request $request)
    {
        $accountIds = SimCompany::whereNotNull('account_id')->pluck('account_id')->toArray();

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Payment::query()
            ->with(['voucher', 'payeeAccount'])
            ->latest('date_of_payment');

        if (empty($accountIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('payee_account_id', $accountIds);
        }

        if ($request->filled('quick_search')) {
            $search = $request->quick_search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('billing_month')) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date_of_payment', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_of_payment', '<=', $request->date_to);
        }

        $statsQuery = clone $query;
        $thisMonth = now();
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'total_amount' => (float) (clone $statsQuery)->sum('amount'),
            'this_month' => (clone $statsQuery)
                ->whereYear('date_of_payment', $thisMonth->year)
                ->whereMonth('date_of_payment', $thisMonth->month)
                ->count(),
            'this_month_amount' => (float) (clone $statsQuery)
                ->whereYear('date_of_payment', $thisMonth->year)
                ->whereMonth('date_of_payment', $thisMonth->month)
                ->sum('amount'),
        ];

        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            $tableData = view('payments.table', compact('data'))->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'stats' => $stats,
            ]);
        }

        return view('sims.payments', compact('data', 'stats'));
    }

    public function createPaymentVoucher($company_slug, $id)
    {
        if (!user_can('sim_invoice_payment_voucher')) {
            abort(403, 'Unauthorized action.');
        }

        $invoice = $this->simInvoicesRepository->find($id);
        if (empty($invoice)) {
            Flash::error('Invoice not found');
            return redirect(route('simInvoices.index'));
        }

        if ((int) $invoice->status === 1) {
            Flash::error('Invoice is already marked as paid.');
            return redirect(route('simInvoices.show', $invoice->id));
        }

        $invoice->load('company');
        $bankAccounts = Accounts::bankAccountsDropdown();

        return view('sim_invoices.payment_voucher', compact('invoice', 'bankAccounts'));
    }

    public function storePaymentVoucher(Request $request, $company_slug, $id)
    {
        if (!user_can('sim_invoice_payment_voucher')) {
            abort(403, 'Unauthorized action.');
        }

        $invoice = $this->simInvoicesRepository->find($id);
        if (empty($invoice)) {
            return response()->json(['errors' => ['error' => 'Invoice not found']], 422);
        }

        if ((int) $invoice->status === 1) {
            return response()->json(['errors' => ['error' => 'Invoice is already marked as paid.']], 422);
        }

        $invoice->load('company');
        if (!$invoice->company || !$invoice->company->account_id) {
            return response()->json(['errors' => ['error' => 'Vendor account is not configured.']], 422);
        }

        $request->validate([
            'trans_date' => 'required|date',
            'bank_account_id' => 'required|exists:accounts,id',
            'remarks' => 'nullable|string|max:255',
        ]);

        if (!VoucherType::isCodeAllowedForModule('PV', 'vouchers')) {
            return response()->json(['errors' => ['error' => 'Payment voucher type (PV) is not assigned to Vouchers module. Please assign it in Voucher Settings.']], 422);
        }

        DB::beginTransaction();
        try {
            $transCode = Account::trans_code();
            $amount = (float) $invoice->total_amount;
            $transDate = $request->input('trans_date');
            $billingMonth = \Carbon\Carbon::parse($invoice->billing_month)->format('Y-m-d');
            $remarks = $request->input('remarks') ?: ('Payment against SIM Invoice #' . ($invoice->invoice_number ?? $invoice->id));

            $voucher = Vouchers::create([
                'trans_date' => $transDate,
                'trans_code' => $transCode,
                'billing_month' => $billingMonth,
                'voucher_type' => 'PV',
                'payment_type' => 1,
                'payment_from' => $request->input('bank_account_id'),
                'payment_to' => $invoice->company->account_id,
                'reference_number' => $invoice->reference_number,
                'amount' => $amount,
                'remarks' => $remarks,
                'vendor_id' => $invoice->vendor_id,
                'ref_id' => $invoice->id,
                'Created_By' => auth()->id(),
            ]);

            // Debit vendor account (reduce payable)
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'account_id' => $invoice->company->account_id,
                'debit' => $amount,
                'credit' => 0,
                'narration' => $remarks,
                'reference_id' => $voucher->id,
                'reference_type' => 'PV',
                'billing_month' => $billingMonth,
            ]);

            // Credit bank account
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'account_id' => (int) $request->input('bank_account_id'),
                'debit' => 0,
                'credit' => $amount,
                'narration' => $remarks,
                'reference_id' => $voucher->id,
                'reference_type' => 'PV',
                'billing_month' => $billingMonth,
            ]);

            $invoice->status = 1;
            $invoice->save();

            DB::commit();

            return response()->json([
                'message' => 'Payment voucher created successfully.',
                'redirect' => route('simInvoices.show', $invoice->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
        }
    }
}

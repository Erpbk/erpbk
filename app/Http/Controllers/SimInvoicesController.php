<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Http\Controllers\AppBaseController;
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
        $query = SimInvoice::with('vendor')->orderBy('billing_month', 'desc')->orderBy('id', 'desc');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('billing_month')) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $this->applyPagination($query, $paginationParams);
        $companies = SimCompany::where('status', 1)->orderBy('name')->get();

        if ($request->ajax()) {
            $tableData = view('sim_invoices.table', compact('data'))->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json(['tableData' => $tableData, 'paginationLinks' => $paginationLinks]);
        }

        return view('sim_invoices.index', compact('data', 'companies'));
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
                'days' => min((int) ($item->days ?? 30), 30) ?: 30,
                'rental_amount' => (float) $item->rental_amount,
                'tax_rate' => (float) ($item->tax_rate ?? 5),
            ];
        }

        $cloneFromInvoice = (object) [
            'inv_date' => now()->format('Y-m-d'),
            'billing_month' => $nextMonthString . '-01',
            'vendor_id' => $sourceInvoice->vendor_id,
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
                'vendor_id' => 'required|exists:sim_companies,id',
                'reference_number' => 'required|string|max:255',
                'sim_id' => 'required|array|min:1',
                'sim_id.*' => 'required',
                'rental_amount' => 'required|array|min:1',
                'rental_amount.*' => 'numeric|min:0',
                'days' => 'nullable|array',
                'days.*' => 'nullable|integer|min:1',
                'descriptions' => 'nullable|string',
                'notes' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            ]);

            $invoice = $this->simInvoicesRepository->record($request);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Invoice created successfully.',
                    'redirect' => route('simInvoices.show', $invoice->id),
                ]);
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
                'vendor_id' => 'required|exists:sim_companies,id',
                'reference_number' => 'required|string|max:255',
                'sim_id' => 'required|array|min:1',
                'sim_id.*' => 'required|exists:sims,id',
                'rental_amount' => 'required|array|min:1',
                'rental_amount.*' => 'numeric|min:0',
                'days' => 'nullable|array',
                'days.*' => 'nullable|integer|min:1',
                'sim_invoice_number' => 'required|string|max:255',
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
            \App\Support\CompanyQuery::table('transactions')->where('reference_type', 'SimInvoice')->where('reference_id', $id)->delete();
            \App\Support\CompanyQuery::table('sim_invoice_items')->where('inv_id', $id)->delete();

            if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                Storage::disk('public')->delete($invoice->attachment);
            }

            $invoice->delete();
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
            unset($newInvoiceData['id'], $newInvoiceData['invoice_number'], $newInvoiceData['created_at'], $newInvoiceData['updated_at'], $newInvoiceData['deleted_at']);
            $newInvoiceData['billing_month'] = $nextMonth->format('Y-m') . '-01';
            $newInvoiceData['inv_date'] = now()->format('Y-m-d');
            $newInvoiceData['status'] = 0;

            $newInvoice = SimInvoice::create($newInvoiceData);
            if (empty($newInvoice->invoice_number)) {
                $newInvoice->invoice_number = 'SIMI' . str_pad($newInvoice->id, 8, '0', STR_PAD_LEFT);
                $newInvoice->save();
            }

            foreach ($sourceInvoice->items as $item) {
                $newItemData = $item->toArray();
                unset($newItemData['id'], $newItemData['created_at'], $newItemData['updated_at']);
                $newItemData['inv_id'] = $newInvoice->id;
                \App\Support\CompanyQuery::insert('sim_invoice_items', $newItemData);
            }

            $items = \App\Support\CompanyQuery::table('sim_invoice_items')->where('inv_id', $newInvoice->id)->get();
            $newInvoice->subtotal = $items->sum('rental_amount');
            $newInvoice->vat = $items->sum('tax_amount');
            $newInvoice->total_amount = $items->sum('total_amount');
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

        if (empty($accountIds)) {
            Flash::error('No SIM companies found with configured accounts.');

            return redirect()->back();
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Payment::query()->latest('date_of_payment');
        $query->whereIn('payee_account_id', $accountIds);

        $data = $this->applyPagination($query, $paginationParams);

        return view('sims.payments', compact('data'));
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

        $invoice->load('vendor');
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

        $invoice->load('vendor');
        if (!$invoice->vendor || !$invoice->vendor->account_id) {
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
                'payment_to' => $invoice->vendor->account_id,
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
                'account_id' => $invoice->vendor->account_id,
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

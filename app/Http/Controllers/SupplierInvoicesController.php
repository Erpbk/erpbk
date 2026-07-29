<?php

namespace App\Http\Controllers;

use App\DataTables\LedgerDataTable;
use App\Http\Requests\CreateSupplierInvoicesRequest;
use App\Http\Requests\UpdateSupplierInvoicesRequest;
use App\Imports\ImportSupplierInvoice;
use App\Models\InventoryPurchase;
use App\Models\Items;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\SupplierInvoices;
use App\Models\Transactions;
use App\Repositories\SupplierInvoicesRepository;
use App\Services\Email\CompanyEmailBrandingService;
use App\Support\CompanyQuery;
use App\Traits\GlobalPagination;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DB;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Storage;

class SupplierInvoicesController extends AppBaseController
{
    use GlobalPagination;

    /** @var SupplierInvoicesRepository */
    private $supplierInvoicesRepository;

    public function __construct(SupplierInvoicesRepository $supplierInvoicesRepo)
    {
        $this->supplierInvoicesRepository = $supplierInvoicesRepo;
        $this->middleware('permission:suppliers_invoices_view')->only('index', 'show');
        $this->middleware('permission:suppliers_invoices_create|suppliers_purchase_order_create')->only('create', 'store', 'import');
        $this->middleware('permission:suppliers_invoices_edit|suppliers_purchase_order_edit')->only('edit', 'update');
        $this->middleware('permission:suppliers_invoices_delete|suppliers_purchase_order_delete')->only('destroy');
        $this->middleware('permission:suppliers_payments_view')->only('payments');
        $this->middleware('permission:suppliers_ledger_view')->only('ledger');
        $this->middleware('permission:email_create')->only('sendEmail');
    }

    /**
     * Display a listing of the SupplierInvoices.
     */
    public function index(Request $request)
    {
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = SupplierInvoices::query()
            ->where('is_invoice', true)
            ->with(['supplier', 'garage'])
            ->orderBy('id', 'asc');

        $query->whereHas('supplier');
        if ($request->filled('garage_id')) {
            $query->where('garage_id', $request->garage_id);
        }
        if ($request->has('inv_id') && ! empty($request->inv_id)) {
            $query->where('inv_id', 'like', '%'.$request->inv_id.'%');
        }

        if ($request->has('supplier_id') && ! empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('inv_date_to')) {
            $fromDate = Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '>=', $fromDate);
        }

        if ($request->filled('inv_date_to')) {
            $toDate = Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '<=', $toDate);
        }
        if ($request->has('billing_month') && ! empty($request->billing_month)) {
            $billingMonth = Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('supplier_invoices.table', [
                'data' => $data,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('supplier_invoices.index', [
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new SupplierInvoices.
     */
    public function create()
    {
        $suppliers = Supplier::where('status', 1)->get();
        $type = request()->input('order') ? 'order' : 'invoice';

        return view('supplier_invoices.create', compact('suppliers', 'type'));
    }

    /**
     * Store a newly created SupplierInvoices in storage.
     */
    public function store(CreateSupplierInvoicesRequest $request)
    {
        if ($request->type == 'order') {
            $request['is_order'] = true;
        } else {
            $request['is_invoice'] = true;
        }
        $result = $this->supplierInvoicesRepository->record($request);
        if ($result['success']) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Supplier Invoice Created Successfully', 'reload' => true], 200);
            }
            Flash::success('Supplier Invoice Created Successfully');

            return redirect()->back();
        } else {
            if ($request->ajax()) {
                return response()->json(['message' => 'Error: '.$result['error']], 500);
            }
            Flash::error('Error: '.$result['error']);

            return redirect()->back();
        }

    }

    /**
     * Display the specified SupplierInvoices.
     */
    public function show($company_slug, $id)
    {
        $supplierInvoice = $this->supplierInvoicesRepository->find($id);

        if (empty($supplierInvoice)) {
            Flash::error('Supplier Invoice not found');

            return redirect(route('supplier_invoices.index'));
        }
        $supplierInvoice->load(['supplier', 'garage', 'createdBy', 'updatedBy']);

        if (request()->input('order')) {
            return view('supplier_invoices.showOrder')->with('supplierInvoice', $supplierInvoice);
        } else {
            return view('supplier_invoices.show')->with('supplierInvoice', $supplierInvoice);
        }
    }

    /**
     * Show the form for editing the specified SupplierInvoices.
     */
    public function edit($company_slug, $id)
    {
        $invoice = SupplierInvoices::with('items')->find($id);

        if (! $invoice) {
            Flash::error('Supplier Invoice not found');

            return redirect(route('supplierInvoices.index'));
        }
        $type = request()->input('order') ? 'order' : 'invoice';
        $suppliers = Supplier::where('status', 1)->get();

        //  $itemsWithPrices = Items::select('id', 'price')->get()->pluck('price', 'id');

        // if (!$invoice) {
        //     Flash::error('Supplier Invoice not found');
        //     return redirect(route('supplier_invoices.index'));
        // }

        return view('supplier_invoices.edit', compact('suppliers', 'type', 'invoice'));
    }

    /**
     * Update the specified SupplierInvoices in storage.
     */
    public function update($company_slug, $id, UpdateSupplierInvoicesRequest $request)
    {
        // Try to find the invoice first
        $supplierInvoice = $this->supplierInvoicesRepository->find($id);

        if (empty($supplierInvoice)) {
            Flash::error('Supplier Invoice not found');

            return redirect()->back();
        }
        if ($request->type == 'order') {
            $request['is_order'] = true;
        } else {
            $request['is_invoice'] = true;
        }
        // Call the repository method to update the invoice and related data
        $result = $this->supplierInvoicesRepository->record($request, $id);

        if ($result['success']) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Record Updated Successfully', 'reload' => true], 200);
            }
            Flash::success('Record Updated Successfully');

            return redirect()->back();
        } else {
            if ($request->ajax()) {
                return response()->json(['message' => 'Error: '.$result['error']], 500);
            }
            Flash::error('Error: '.$result['error']);

            return redirect()->back();
        }
    }

    /**
     * Remove the specified SupplierInvoices from storage.
     *
     * @throws \Exception
     */
    public function destroy(Request $request, $company_slug, $id)
    {
        $supplierInvoice = $this->supplierInvoicesRepository->find($id);

        if (empty($supplierInvoice)) {
            Flash::error('Supplier Invoice not found');

            return redirect(route('supplierInvoices.index'));
        }
        $payment = Payment::where('payee_account_id', $supplierInvoice->supplier->account_id)
            ->where('reference', 'like', '%'.$supplierInvoice->inv_id.'%')
            ->exists();
        if ($payment) {
            return response()->json([
                'message' => 'Cannot Delete Invoice. Payment has already been Made',
            ], 500);
        }
        $inventory = InventoryPurchase::where('inv_id', $supplierInvoice->id)->get();
        $items = $supplierInvoice->items;
        if ($inventory && ($inventory->sum('remaining_quantity') < $items->sum('qty'))) {
            return response()->json([
                'message' => 'Cannot delete Invoice. Inventory from this Purchase has already been used',
            ], 500);
        }
        DB::beginTransaction();
        try {
            $attachment = $supplierInvoice->attachment;
            InventoryPurchase::where('inv_id', $supplierInvoice->id)->delete();
            Transactions::where(['reference_id' => $supplierInvoice->id, 'reference_type' => 'SUP'])->delete();
            $supplierInvoice->items()->delete();
            $supplierInvoice->delete();

            if ($attachment && Storage::disk('public')->exists($attachment)) {
                Storage::disk('public')->delete($attachment);
            }
            DB::commit();
            if ($request->ajax()) {
                return response()->json(['message' => 'Record deleted successfully.'], 200);
            }
            Flash::success('Supplier Invoice deleted successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting Supplier Invoice ID: {$id} - ".$e->getMessage());
            if ($request->ajax()) {
                return response()->json(['message' => 'Error: '.$e->getMessage()], 500);
            }
            Flash::error('Error deleting Supplier Invoice: '.$e->getMessage());

            return redirect()->back();
        }

        return redirect(route('supplierInvoices.index'));
    }

    /**
     * Recalculate ledger entries after deletion
     * This ensures ledger integrity without deleting all entries
     */
    private function recalculateLedgerAfterDeletion($accountId, $billingMonth)
    {
        // Delete only the ledger entry for this specific billing month
        CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        // Get the last ledger entry before this billing month
        $lastLedger = CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', '<', $billingMonth)
            ->orderBy('billing_month', 'desc')
            ->first();

        $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

        // Recalculate totals for this month after deletion
        $monthTransactions = Transactions::where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->get();

        $debitTotal = $monthTransactions->sum('debit');
        $creditTotal = $monthTransactions->sum('credit');
        $closingBalance = $openingBalance + $debitTotal - $creditTotal;

        // Only insert a new ledger entry if there are still transactions for this month
        if ($monthTransactions->count() > 0) {
            CompanyQuery::insert('ledger_entries', [
                'account_id' => $accountId,
                'billing_month' => $billingMonth,
                'opening_balance' => $openingBalance,
                'debit_balance' => $debitTotal,
                'credit_balance' => $creditTotal,
                'closing_balance' => $closingBalance,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \Log::info("Recalculated ledger for account {$accountId} and billing month {$billingMonth}");
    }

    /**
     * Import supplier invoices from Excel file.
     */
    public function import(Request $request)
    {
        if ($request->isMethod('post')) {
            $rules = [
                'file' => 'required|max:50000|mimes:xlsx',
            ];
            $message = [
                'file.required' => 'Excel File Required',
            ];
            $this->validate($request, $rules, $message);
            Excel::import(new ImportSupplierInvoice, $request->file('file'));
        }

        return view('supplier_invoices.import');
    }

    /**
     * Send Supplier Invoice email with attached PDF.
     */
    public function sendEmail($company_slug, $id, Request $request)
    {
        if ($request->isMethod('post')) {
            $brandingService = app(CompanyEmailBrandingService::class);
            $data = $brandingService->mergeIntoMailData([
                'html' => $request->email_message,
            ]);

            $res = SupplierInvoices::with(['supplierInv_item'])->where('id', $id)->get();
            $pdf = Pdf::loadView('invoices.supplier_invoices.show', ['res' => $res])
                ->setPaper('a4', 'portrait');

            Mail::send('emails.general', $data, function ($message) use ($request, $pdf) {
                $message->to([$request->email_to]);
                $message->subject($request->email_subject);
                $message->attachData($pdf->output(), $request->email_subject.'.pdf');
                $message->priority(3);
            });
        }

        $invoice = SupplierInvoices::find($id);

        return view('supplier_invoices.send_email', compact('invoice'));
    }

    public function ledger()
    {
        return (new LedgerDataTable('supplier'))->render('supplier.ledger');
    }

    public function purchaseOrders(Request $request)
    {
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = SupplierInvoices::query()
            ->where('is_order', true)
            ->with(['supplier', 'garage'])
            ->orderBy('id', 'asc');

        $query->whereHas('supplier');
        if ($request->filled('garage_id')) {
            $query->where('garage_id', $request->garage_id);
        }
        if ($request->has('inv_id') && ! empty($request->inv_id)) {
            $query->where('inv_id', 'like', '%'.$request->inv_id.'%');
        }

        if ($request->has('supplier_id') && ! empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('inv_date_to')) {
            $fromDate = Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '>=', $fromDate);
        }

        if ($request->filled('inv_date_to')) {
            $toDate = Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '<=', $toDate);
        }
        if ($request->has('billing_month') && ! empty($request->billing_month)) {
            $billingMonth = Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('supplier_invoices.table', [
                'data' => $data,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('supplier_invoices.orderIndex', [
            'data' => $data,
        ]);
    }

    public function payments(Request $request)
    {
        $accountIds = Supplier::pluck('account_id')->toArray();

        if (empty($accountIds)) {
            Flash::error('No Suppliers found');

            return redirect()->back();
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Payment::query()->latest('date_of_payment');
        $query->whereIn('payee_account_id', $accountIds);

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);

        return view('supplier.payments', compact('data'));
    }
}

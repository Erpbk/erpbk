<?php

namespace App\Http\Controllers;

use App\DataTables\SupplierInvoicesDataTable;
use App\Helpers\HeadAccount;
use App\Http\Requests\CreateSupplierInvoicesRequest;
use App\Http\Requests\UpdateSupplierInvoicesRequest;
use App\Http\Controllers\AppBaseController;
use App\Imports\ImportSupplierInvoice;
use App\Models\SupplierInvoicesItem;
use App\Models\Accounts;
use App\Models\Items;
use App\Models\SupplierInvoices;
use App\Models\Supplier;
use App\Models\Payment;
use App\Models\Transactions;
use App\Repositories\SupplierInvoicesRepository;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\SupplierInvoiceItems;
use App\DataTables\LedgerDataTable;
use DB;
use Storage;



class SupplierInvoicesController extends AppBaseController
{
    use GlobalPagination;
    /** @var SupplierInvoicesRepository $supplierInvoicesRepository */
    private $supplierInvoicesRepository;

    public function __construct(SupplierInvoicesRepository $supplierInvoicesRepo)
    {
        $this->supplierInvoicesRepository = $supplierInvoicesRepo;
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
            ->orderBy('id', 'asc');

        $query->whereHas('supplier');
        if ($request->has('inv_id') && !empty($request->inv_id)) {
            $query->where('inv_id', 'like', '%' . $request->inv_id . '%');
        }

        if ($request->has('supplier_id') && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('inv_date_to')) {
            $fromDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '>=', $fromDate);
        }

        if ($request->filled('inv_date_to')) {
            $toDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '<=', $toDate);
        }
        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
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
        $suppliers = Supplier::where('status',1)->get();
        $type = request()->input('order') ? 'order' : 'invoice';

        return view('supplier_invoices.create', compact('suppliers','type'));
    }

    /**
     * Store a newly created SupplierInvoices in storage.
     */
    public function store(CreateSupplierInvoicesRequest $request)
    {
        if($request->type == 'order'){
            $request['is_order'] = true;
        }else {
            $request['is_invoice'] = true;
        }
        $result = $this->supplierInvoicesRepository->record($request);
        if($result['success']){
            if($request->ajax()){
                return response()->json(['message' => 'Supplier Invoice Created Successfully', 'reload' => true], 200);
            }
            Flash::success('Supplier Invoice Created Successfully');
            return redirect()->back();
        }else {
            if($request->ajax()){
                return response()->json(['message' => 'Error: ' .$result['error']], 500);
            }
            Flash::error('Error: '.$result['error']);
            return redirect()->back();
        }

    }

    /**
     * Display the specified SupplierInvoices.
     */
    public function show($id)
    {
        $supplierInvoice = $this->supplierInvoicesRepository->find($id);

        if (empty($supplierInvoice)) {
            Flash::error('Supplier Invoice not found');
            return redirect(route('supplier_invoices.index'));
        }
        $supplierInvoice->load(['supplier','createdBy','updatedBy']);

        if(request()->input('order'))
            return view('supplier_invoices.showOrder')->with('supplierInvoice', $supplierInvoice);
        else
            return view('supplier_invoices.show')->with('supplierInvoice', $supplierInvoice);
    }

    /**
     * Show the form for editing the specified SupplierInvoices.
     */
    public function edit($id)
    {
        $invoice = SupplierInvoices::with('items')->find($id);

        if (!$invoice) {
            Flash::error('Supplier Invoice not found');
            return redirect(route('supplierInvoices.index'));
        }
        $type = request()->input('order') ? 'order' : 'invoice';
        $suppliers = Supplier::where('status',1)->get();



        //  $itemsWithPrices = Items::select('id', 'price')->get()->pluck('price', 'id');

        // if (!$invoice) {
        //     Flash::error('Supplier Invoice not found');
        //     return redirect(route('supplier_invoices.index'));
        // }

        return view('supplier_invoices.edit', compact('suppliers', 'type','invoice'));
    }

    /**
     * Update the specified SupplierInvoices in storage.
     */
    public function update($id, UpdateSupplierInvoicesRequest $request)
    {
        // Try to find the invoice first
        $supplierInvoice = $this->supplierInvoicesRepository->find($id);

        if (empty($supplierInvoice)) {
            Flash::error('Supplier Invoice not found');
            return redirect()->back();
        }
        if($request->type == 'order'){
            $request['is_order'] = true;
        }else {
            $request['is_invoice'] = true;
        }
        // Call the repository method to update the invoice and related data
        $result = $this->supplierInvoicesRepository->record($request, $id);

        if($result['success']){
            if($request->ajax()){
                return response()->json(['message' => 'Record Updated Successfully', 'reload' => true], 200);
            }
            Flash::success('Record Updated Successfully');
            return redirect()->back();
        }else {
            if($request->ajax()){
                return response()->json(['message' => 'Error: ' .$result['error']], 500);
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
    public function destroy(Request $request, $id)
    {
        $supplierInvoice = $this->supplierInvoicesRepository->find($id);

        if (empty($supplierInvoices)) {
            Flash::error('Supplier Invoice not found');
            return redirect(route('supplierInvoices.index'));
        }

        DB::beginTransaction();
        try {
            if ($supplierInvoice->attachment && Storage::disk('public')->exists($supplierInvoice->attachment)) {
                Storage::disk('public')->delete($supplierInvoice->attachment);
            }
            Transactions::where(['reference_id' =>  $supplierInvoice->id, 'reference_type' => 'SUP'])->delete();
            $supplierInvoice->items()->delete();
            $supplierInvoice->delete();

            DB::commit();
            if($request->ajax()){
                return response()->json(['message' => 'Record deleted successfully.'],200);
            }
            Flash::success('Supplier Invoice deleted successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting Supplier Invoice ID: {$id} - " . $e->getMessage());
             if($request->ajax()){
                return response()->json(['message' => 'Error: '.$e->getMessage()],500);
            }
            Flash::error('Error deleting Supplier Invoice: ' . $e->getMessage());
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
        \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        // Get the last ledger entry before this billing month
        $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
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
            \App\Support\CompanyQuery::insert('ledger_entries', [
                'account_id'      => $accountId,
                'billing_month'   => $billingMonth,
                'opening_balance' => $openingBalance,
                'debit_balance'   => $debitTotal,
                'credit_balance'  => $creditTotal,
                'closing_balance' => $closingBalance,
                'created_at'      => now(),
                'updated_at'      => now(),
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
                'file' => 'required|max:50000|mimes:xlsx'
            ];
            $message = [
                'file.required' => 'Excel File Required'
            ];
            $this->validate($request, $rules, $message);
            Excel::import(new ImportSupplierInvoice(), $request->file('file'));
        }

        return view('supplier_invoices.import');
    }

    /**
     * Send Supplier Invoice email with attached PDF.
     */
    public function sendEmail($id, Request $request)
    {
        if ($request->isMethod('post')) {
            $data = [
                'html' => $request->email_message
            ];

            $res = SupplierInvoices::with(['supplierInv_item'])->where('id', $id)->get();
            $pdf = \PDF::loadView('invoices.supplier_invoices.show', ['res' => $res]);

            Mail::send('emails.general', $data, function ($message) use ($request, $pdf) {
                $message->to([$request->email_to]);
                $message->subject($request->email_subject);
                $message->attachData($pdf->output(), $request->email_subject . '.pdf');
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
            ->orderBy('id', 'asc');

        $query->whereHas('supplier');
        if ($request->has('inv_id') && !empty($request->inv_id)) {
            $query->where('inv_id', 'like', '%' . $request->inv_id . '%');
        }

        if ($request->has('supplier_id') && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('inv_date_to')) {
            $fromDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '>=', $fromDate);
        }

        if ($request->filled('inv_date_to')) {
            $toDate = \Carbon\Carbon::createFromFormat('Y-d-m', $request->inv_date_to);
            $query->where('inv_date', '<=', $toDate);
        }
        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
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

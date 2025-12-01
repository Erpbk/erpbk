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
            ->orderBy('id', 'asc');
        if ($request->has('inv_id') && !empty($request->inv_id)) {
            $query->where('inv_id', 'like', '%' . $request->inv_id . '%');
        }

        if ($request->has('supplier_id') && !empty($request->supplier_id)) {
            $query->where('supplier_id',$request->supplier_id);
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
        $supplier = Supplier::dropdown();
        $items = Items::dropdown();
        
        $itemsWithPrices = Items::select('id', 'price')->get()->pluck('price', 'id');
        
        return view('supplier_invoices.create', compact('supplier', 'itemsWithPrices', 'items'));
    }

    /**
     * Store a newly created SupplierInvoices in storage.
     */
    public function store(CreateSupplierInvoicesRequest $request)
    {
        $input = $request->all();

        $supplierInvoices = $this->supplierInvoicesRepository->record($request);

        Flash::success('Supplier Invoices saved successfully.');

        return redirect(route('supplier_invoices.index'));
        
    //     // Validate the request
    // $request->validate([
    //     'supplier_id' => 'required|exists:suppliers,id',
    //     'invoice_date' => 'required|date',
    //     'items' => 'required|array',
    //     'items.*.item_id' => 'required|exists:items,id',
    //     'items.*.qty' => 'required|numeric|min:1',
    //     'items.*.rate' => 'required|numeric|min:0',
    // ]);

    // $invoice = new SupplierInvoice();
    // $invoice->fill($request->only(['supplier_id', 'date', 'total']));
    // $invoice->save();

    // if ($request->has('items')) {
    //     foreach ($request->items as $item) {
    //         $invoice->items()->create([
    //             'item_id'   => $item['item_id'],
    //             'qty'       => $item['qty'],
    //             'rate'      => $item['rate'],
    //             'discount'  => $item['discount'],
    //             'tax'       => $item['tax'],
    //             'amount'    => $item['amount'],
    //         ]);
    //     }
    // }

    // return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    
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
        
         $supplier = Supplier::dropdown();
        $items = Items::dropdown();
           $itemsWithPrices = Items::select('id', 'price')->get()->pluck('price', 'id');

       

//  $itemsWithPrices = Items::select('id', 'price')->get()->pluck('price', 'id');

// if (!$invoice) {
//     Flash::error('Supplier Invoice not found');
//     return redirect(route('supplier_invoices.index'));
// }

        return view('supplier_invoices.edit', compact('supplier', 'items',  'invoice', 'itemsWithPrices'));
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
        return redirect(route('supplier_invoices.index'));
    }

    // Call the repository method to update the invoice and related data
    $updatedInvoice = $this->supplierInvoicesRepository->record($request, $id);

    if ($updatedInvoice) {
        Flash::success('Supplier Invoice updated successfully.');
    } else {
        Flash::error('Failed to update the Supplier Invoice.');
    }

    return redirect(route('supplier_invoices.index'));
}


    /**
     * Remove the specified SupplierInvoices from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $supplierInvoices = $this->supplierInvoicesRepository->find($id);

        if (empty($supplierInvoices)) {
            Flash::error('Supplier Invoice not found');
            return redirect(route('supplierInvoices.index'));
        }

        $trans_code = Transactions::where('reference_type', 'Invoice')->where('reference_id', $id)->value('trans_code');
        $transactions = new TransactionService();
        $transactions->deleteTransaction($trans_code);

        $this->supplierInvoicesRepository->delete($id);

        Flash::success('Supplier Invoice deleted successfully.');

        return redirect(route('supplierInvoices.index'));
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





}

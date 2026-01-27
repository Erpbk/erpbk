<?php

namespace App\Http\Controllers;

use App\DataTables\LeasingCompaniesDataTable;
use App\Helpers\Account;
use App\Http\Requests\CreateLeasingCompaniesRequest;
use App\Http\Requests\UpdateLeasingCompaniesRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\Bikes;
use App\Models\LeasingCompanies;
use App\Models\LeasingCompanyInvoice;
use App\Repositories\LeasingCompaniesRepository;
use App\Repositories\LeasingCompanyInvoicesRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Flash;

class LeasingCompaniesController extends AppBaseController
{
  use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;
  /** @var LeasingCompaniesRepository $leasingCompaniesRepository*/
  private $leasingCompaniesRepository;
  /** @var LeasingCompanyInvoicesRepository $leasingCompanyInvoicesRepository*/
  private $leasingCompanyInvoicesRepository;

  public function __construct(LeasingCompaniesRepository $leasingCompaniesRepo, LeasingCompanyInvoicesRepository $leasingCompanyInvoicesRepo)
  {
    $this->leasingCompaniesRepository = $leasingCompaniesRepo;
    $this->leasingCompanyInvoicesRepository = $leasingCompanyInvoicesRepo;
  }

  /**
   * Display a listing of the LeasingCompanies.
   */
  public function index(Request $request)
  {

    if (!auth()->user()->hasPermissionTo('leasing_view')) {
      abort(403, 'Unauthorized action.');
    }
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = LeasingCompanies::query()
      ->orderBy('id', 'desc');
    if ($request->has('name') && !empty($request->name)) {
      $query->where('name', 'like', '%' . $request->name . '%');
    }
    if ($request->has('contact_person') && !empty($request->contact_person)) {
      $query->where('contact_person', $request->contact_person);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }
    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    if ($request->ajax()) {
      $tableData = view('leasing_companies.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }
    return view('leasing_companies.index', [
      'data' => $data,
    ]);
  }


  /**
   * Show the form for creating a new LeasingCompanies.
   */
  public function create()
  {
    return view('leasing_companies.create');
  }

  /**
   * Store a newly created LeasingCompanies in storage.
   */
  public function store(CreateLeasingCompaniesRequest $request)
  {
    $input = $request->all();

    $leasingCompanies = $this->leasingCompaniesRepository->create($input);


    //Adding Account and setting reference

    $parentAccount = Accounts::where('name', 'Leasing Companies')->where('account_type', 'Liability')->where('parent_id', null)->first();
    if (!$parentAccount) {
      Flash::error('Parent account "Leasing Companies" not found.');
    }

    $account = new Accounts();
    $account->account_code = 'LC' . str_pad($leasingCompanies->id, 4, "0", STR_PAD_LEFT);
    $account->account_type = 'Liability';
    $account->name = $leasingCompanies->name;
    $account->parent_id = $parentAccount->id;
    $account->ref_name = 'LeasingCompany';
    $account->ref_id = $leasingCompanies->id;
    $account->status = $leasingCompanies->status;
    $account->save();

    $leasingCompanies->account_id = $account->id;
    $leasingCompanies->save();

    return response()->json(['message' => 'Company added successfully.']);
  }

  /**
   * Display the specified LeasingCompanies.
   */
  public function show($id)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      Flash::error('Leasing Companies not found');

      return redirect(route('leasingCompanies.index'));
    }

    return view('leasing_companies.show')->with('leasingCompanies', $leasingCompanies);
  }

  /**
   * Show the form for editing the specified LeasingCompanies.
   */
  public function edit($id)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      Flash::error('Leasing Companies not found');

      return redirect(route('leasingCompanies.index'));
    }

    return view('leasing_companies.edit')->with('leasingCompanies', $leasingCompanies);
  }

  /**
   * Update the specified LeasingCompanies in storage.
   */
  public function update($id, UpdateLeasingCompaniesRequest $request)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      return response()->json(['errors' => ['error' => 'Company not found!']], 422);
    }

    $leasingCompanies = $this->leasingCompaniesRepository->update($request->all(), $id);

    $leasingCompanies->account->name = $leasingCompanies->name;
    $leasingCompanies->account->status = $leasingCompanies->status;
    $leasingCompanies->account->save();


    return response()->json(['message' => 'Company updated successfully.']);
  }

  /**
   * Remove the specified LeasingCompanies from storage (soft delete with cascade tracking).
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $leasingCompanies = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompanies)) {
      return response()->json(['errors' => ['error' => 'Company not found!']], 422);
    }

    // Check if leasing company has transactions - protect from deletion
    $transactionCount = $leasingCompanies->transactions()->count();
    if ($transactionCount > 0) {
      return response()->json(['errors' => ['error' => 'Cannot delete leasing company. Company has ' . $transactionCount . ' transaction(s). Please deactivate instead.']], 422);
    }

    // Check if leasing company has assigned bikes - protect from deletion
    $bikeCount = $leasingCompanies->bikes()->count();
    if ($bikeCount > 0) {
      return response()->json(['errors' => ['error' => 'Cannot delete leasing company. Company has ' . $bikeCount . ' assigned bike(s). Please deactivate instead.']], 422);
    }

    // Check if leasing company has related vouchers - protect from deletion
    $voucherCount = $leasingCompanies->vouchers()->count();
    if ($voucherCount > 0) {
      return response()->json(['errors' => ['error' => 'Cannot delete leasing company. Company has ' . $voucherCount . ' voucher(s). Please deactivate instead.']], 422);
    }

    // Track cascaded deletions
    $cascadedItems = [];
    $leasingCompanyId = $leasingCompanies->id;
    $leasingCompanyName = $leasingCompanies->name;

    // Get related account BEFORE deleting (important!)
    $relatedAccount = $leasingCompanies->account;

    // Soft delete the leasing company
    $leasingCompanies->delete();

    // Track and soft delete the related account if exists
    if ($relatedAccount) {
      $cascadedItems[] = [
        'model' => 'Accounts',
        'id' => $relatedAccount->id,
        'name' => $relatedAccount->name,
      ];

      $relatedAccount->delete();

      // Log the cascade deletion
      $this->trackCascadeDeletion(
        'App\Models\LeasingCompanies',
        $leasingCompanyId,
        $leasingCompanyName,
        'App\Models\Accounts',
        $relatedAccount->id,
        $relatedAccount->name,
        'hasOne',
        'account',
        'soft',
        'Cascade deletion from Leasing Company deletion'
      );
    }

    // Build cascade message
    $cascadeMessage = '';
    if (!empty($cascadedItems)) {
      $cascadeMessage = ' (Also deleted: ';
      $parts = [];
      foreach ($cascadedItems as $item) {
        $parts[] = "{$item['model']}: {$item['name']}";
      }
      $cascadeMessage .= implode(', ', $parts) . ')';
    }

    return response()->json([
      'message' => 'Leasing company moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('trash.index') . '?module=leasing_companies" class="alert-link">View Recycle Bin</a> to restore if needed.'
    ]);
  }

  /**
   * Get the model class for trash functionality
   */
  protected function getTrashModelClass()
  {
    return LeasingCompanies::class;
  }

  /**
   * Get the trash configuration
   */
  protected function getTrashConfig()
  {
    return [
      'name' => 'Leasing Company',
      'display_columns' => ['name', 'contact_person', 'contact_number'],
      'trash_view' => 'leasing_companies.trash',
      'index_route' => 'leasingCompanies.index',
    ];
  }

  /**
   * Display a listing of Leasing Company Invoices.
   */
  public function indexInvoices(Request $request)
  {
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    
    $query = LeasingCompanyInvoice::with('leasingCompany')
      ->orderBy('billing_month', 'desc')
      ->orderBy('id', 'desc');

    // Filters
    if ($request->has('leasing_company_id') && !empty($request->leasing_company_id)) {
      $query->where('leasing_company_id', $request->leasing_company_id);
    }
    if ($request->has('billing_month') && !empty($request->billing_month)) {
      $billingMonth = \Carbon\Carbon::parse($request->billing_month);
      $query->whereYear('billing_month', $billingMonth->year)
        ->whereMonth('billing_month', $billingMonth->month);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }

    $data = $this->applyPagination($query, $paginationParams);
    $leasingCompanies = LeasingCompanies::where('status', 1)->orderBy('name')->get();

    if ($request->ajax()) {
      $tableData = view('leasing_company_invoices.table', [
        'data' => $data,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }

    return view('leasing_company_invoices.index', [
      'data' => $data,
      'leasingCompanies' => $leasingCompanies,
    ]);
  }

  /**
   * Show the form for creating a new invoice for a leasing company.
   */
  public function createInvoice($id = null)
  {
    // If ID is provided, use it; otherwise get from request
    $leasingCompanyId = $id ?? request('leasing_company_id');
    
    if ($leasingCompanyId) {
      $leasingCompany = $this->leasingCompaniesRepository->find($leasingCompanyId);
      if (empty($leasingCompany)) {
        Flash::error('Leasing Company not found');
        return redirect(route('leasingCompanyInvoices.index'));
      }
    } else {
      $leasingCompany = null;
    }

    // Get all active leasing companies for dropdown
    $leasingCompanies = LeasingCompanies::where('status', 1)->orderBy('name')->get();

    // Get bikes for selected leasing company (only active bikes)
    $bikes = collect();
    if ($leasingCompany) {
      $bikes = Bikes::where('company', $leasingCompany->id)
        ->where('status', 1)
        ->orderBy('plate')
        ->get();
    }

    return view('leasing_company_invoices.create', compact('leasingCompany', 'bikes', 'leasingCompanies'));
  }

  /**
   * Store a newly created invoice in storage.
   */
  public function storeInvoice(Request $request, $id = null)
  {
    try {
      $leasingCompanyId = $id ?? $request->leasing_company_id;
      
      $leasingCompany = $this->leasingCompaniesRepository->find($leasingCompanyId);

      if (empty($leasingCompany)) {
        return response()->json(['errors' => ['error' => 'Leasing Company not found!']], 422);
      }

      // Validate request
      $request->validate([
        'inv_date' => 'required|date',
        'billing_month' => 'required',
        'bike_id' => 'required|array|min:1',
        'bike_id.*' => 'exists:bikes,id',
        'rental_amount' => 'required|array|min:1',
        'rental_amount.*' => 'numeric|min:0',
        'descriptions' => 'nullable|string',
        'notes' => 'nullable|string',
      ]);

      // Add leasing_company_id to request
      $request->merge(['leasing_company_id' => $leasingCompanyId]);

      $invoice = $this->leasingCompanyInvoicesRepository->record($request);

      Flash::success('Invoice created successfully.');

      if ($request->ajax()) {
        return response()->json([
          'message' => 'Invoice created successfully.',
          'redirect' => route('leasingCompanyInvoices.show', $invoice->id)
        ]);
      }

      return redirect(route('leasingCompanyInvoices.show', $invoice->id));
    } catch (\Exception $e) {
      if ($request->ajax()) {
        return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
      }
      Flash::error($e->getMessage());
      return redirect()->back()->withInput();
    }
  }

  /**
   * Display the specified invoice.
   */
  public function showInvoice($id)
  {
    $invoice = $this->leasingCompanyInvoicesRepository->find($id);

    if (empty($invoice)) {
      Flash::error('Invoice not found');
      return redirect(route('leasingCompanyInvoices.index'));
    }

    return view('leasing_company_invoices.show')->with('invoice', $invoice);
  }

  /**
   * Show the form for editing the specified invoice.
   */
  public function editInvoice($id)
  {
    $invoice = $this->leasingCompanyInvoicesRepository->find($id);

    if (empty($invoice)) {
      Flash::error('Invoice not found');
      return redirect(route('leasingCompanyInvoices.index'));
    }

    $leasingCompany = $invoice->leasingCompany;
    
    // Get active bikes for this leasing company
    $bikes = Bikes::where('company', $leasingCompany->id)
      ->where('status', 1)
      ->orderBy('plate')
      ->get();

    return view('leasing_company_invoices.edit', compact('invoice', 'leasingCompany', 'bikes'));
  }

  /**
   * Update the specified invoice in storage.
   */
  public function updateInvoice(Request $request, $id)
  {
    try {
      $invoice = $this->leasingCompanyInvoicesRepository->find($id);

      if (empty($invoice)) {
        Flash::error('Invoice not found');
        return redirect(route('leasingCompanyInvoices.index'));
      }

      // Validate request
      $request->validate([
        'inv_date' => 'required|date',
        'billing_month' => 'required',
        'bike_id' => 'required|array|min:1',
        'bike_id.*' => 'exists:bikes,id',
        'rental_amount' => 'required|array|min:1',
        'rental_amount.*' => 'numeric|min:0',
        'descriptions' => 'nullable|string',
        'notes' => 'nullable|string',
      ]);

      $invoice = $this->leasingCompanyInvoicesRepository->record($request, $id);

      Flash::success('Invoice updated successfully.');

      if ($request->ajax()) {
        return response()->json([
          'message' => 'Invoice updated successfully.',
          'redirect' => route('leasingCompanyInvoices.show', $invoice->id)
        ]);
      }

      return redirect(route('leasingCompanyInvoices.show', $invoice->id));
    } catch (\Exception $e) {
      if ($request->ajax()) {
        return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
      }
      Flash::error($e->getMessage());
      return redirect()->back()->withInput();
    }
  }

  /**
   * Remove the specified invoice from storage.
   */
  public function destroyInvoice($id)
  {
    $invoice = $this->leasingCompanyInvoicesRepository->find($id);

    if (empty($invoice)) {
      Flash::error('Invoice not found');
      return redirect(route('leasingCompanyInvoices.index'));
    }

    // Check if invoice is paid - prevent deletion of paid invoices
    if ($invoice->status == 1) {
      Flash::error('Cannot delete paid invoice. Only unpaid invoices can be deleted.');
      return redirect(route('leasingCompanyInvoices.index'));
    }

    try {
      // Delete related transactions
      \DB::table('transactions')
        ->where('reference_type', 'LeasingCompanyInvoice')
        ->where('reference_id', $id)
        ->delete();

      // Delete invoice items
      \DB::table('leasing_company_invoice_items')
        ->where('inv_id', $id)
        ->delete();

      // Soft delete the invoice
      $invoice->delete();

      Flash::success('Invoice deleted successfully.');
    } catch (\Exception $e) {
      Flash::error('Error deleting invoice: ' . $e->getMessage());
    }

    return redirect(route('leasingCompanyInvoices.index'));
  }

  /**
   * Clone invoice to next month.
   */
  public function cloneInvoice(Request $request, $id)
  {
    try {
      $sourceInvoice = $this->leasingCompanyInvoicesRepository->find($id);

      if (empty($sourceInvoice)) {
        return response()->json(['errors' => ['error' => 'Source invoice not found!']], 422);
      }

      // Calculate next month
      $nextMonth = \Carbon\Carbon::parse($sourceInvoice->billing_month)->addMonth();
      $nextMonthString = $nextMonth->format('Y-m');

      // Check if invoice already exists for next month
      $existingInvoice = LeasingCompanyInvoice::where('leasing_company_id', $sourceInvoice->leasing_company_id)
        ->whereYear('billing_month', $nextMonth->year)
        ->whereMonth('billing_month', $nextMonth->month)
        ->first();

      if ($existingInvoice) {
        return response()->json(['errors' => ['error' => 'An invoice for this leasing company already exists for ' . $nextMonthString . '.']], 422);
      }

      // Create new invoice data
      $newInvoiceData = $sourceInvoice->toArray();
      unset($newInvoiceData['id']);
      unset($newInvoiceData['invoice_number']);
      unset($newInvoiceData['created_at']);
      unset($newInvoiceData['updated_at']);
      unset($newInvoiceData['deleted_at']);
      
      $newInvoiceData['billing_month'] = $nextMonthString . '-01';
      $newInvoiceData['inv_date'] = now()->format('Y-m-d');
      $newInvoiceData['status'] = 0; // Unpaid

      // Create new invoice
      $newInvoice = LeasingCompanyInvoice::create($newInvoiceData);

      // Generate invoice number
      if (empty($newInvoice->invoice_number)) {
        $newInvoice->invoice_number = 'LCI' . str_pad($newInvoice->id, 8, '0', STR_PAD_LEFT);
        $newInvoice->save();
      }

      // Clone invoice items (only for active bikes)
      foreach ($sourceInvoice->items as $item) {
        // Check if bike is still active
        $bike = Bikes::find($item->bike_id);
        if ($bike && $bike->status == 1) {
          $newItemData = $item->toArray();
          unset($newItemData['id']);
          unset($newItemData['created_at']);
          unset($newItemData['updated_at']);
          $newItemData['inv_id'] = $newInvoice->id;
          
          \DB::table('leasing_company_invoice_items')->insert($newItemData);
        }
      }

      // Recalculate totals
      $items = \DB::table('leasing_company_invoice_items')
        ->where('inv_id', $newInvoice->id)
        ->get();

      $subtotal = $items->sum('rental_amount');
      $vat = $items->sum('tax_amount');
      $totalAmount = $items->sum('total_amount');

      $newInvoice->subtotal = $subtotal;
      $newInvoice->vat = $vat;
      $newInvoice->total_amount = $totalAmount;
      $newInvoice->save();

      Flash::success('Invoice cloned successfully for ' . $nextMonthString . '.');

      if ($request->ajax()) {
        return response()->json([
          'message' => 'Invoice cloned successfully for ' . $nextMonthString . '.',
          'redirect' => route('leasingCompanyInvoices.show', $newInvoice->id)
        ]);
      }

      return redirect(route('leasingCompanyInvoices.show', $newInvoice->id));
    } catch (\Exception $e) {
      if ($request->ajax()) {
        return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
      }
      Flash::error('Error cloning invoice: ' . $e->getMessage());
      return redirect()->back();
    }
  }

  /**
   * Get active bikes for a leasing company (AJAX endpoint).
   */
  public function getBikes($id)
  {
    $leasingCompany = $this->leasingCompaniesRepository->find($id);

    if (empty($leasingCompany)) {
      return response()->json(['error' => 'Leasing Company not found'], 404);
    }

    // Get only active bikes for this leasing company
    $bikes = Bikes::where('company', $id)
      ->where('status', 1)
      ->orderBy('plate')
      ->get(['id', 'plate', 'model']);

    return response()->json([
      'bikes' => $bikes,
      'rental_amount' => $leasingCompany->rental_amount ?? 0
    ]);
  }
}

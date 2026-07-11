<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBikeRentCompaniesRequest;
use App\Http\Requests\UpdateBikeRentCompaniesRequest;
use App\Models\Accounts;
use App\Models\Receipt;
use App\Models\Files;
use App\Models\Bikes;
use App\Models\Transactions;
use App\DataTables\FilesDataTable;
use App\DataTables\LedgerDataTable;
use App\Models\BikeMaintenance;
use App\Models\BikeRentCompany;
use App\Models\LeasingCompanyBillingInvoice;
use App\Repositories\BikeRentCompaniesRepository;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BikeRentCompaniesController extends AppBaseController
{
    use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;

    private BikeRentCompaniesRepository $bikeRentCompaniesRepository;

    public function __construct(BikeRentCompaniesRepository $bikeRentCompaniesRepository)
    {
        $this->bikeRentCompaniesRepository = $bikeRentCompaniesRepository;
        $this->middleware('permission:bike_on_rent_customers_view')->only('index', 'show', 'bikes');
        $this->middleware('permission:bike_on_rent_customers_create')->only('create', 'store');
        $this->middleware('permission:bike_on_rent_customers_edit')->only('edit', 'update');
        $this->middleware('permission:bike_on_rent_customers_delete')->only('destroy');
        $this->middleware('permission:garages_customers_view')->only('garageIndex', 'show');
        $this->middleware('permission:garages_customers_create')->only('create', 'store');
        $this->middleware('permission:garages_customers_edit')->only('edit', 'update');
        $this->middleware('permission:garages_customers_delete')->only('destroy');
        $this->middleware('permission:bike_on_rent_documents_view|garages_documents_view')->only('files');
        $this->middleware('permission:bike_on_rent_ledger_view|garages_ledger_view')->only('ledger');
        $this->middleware('permission:bike_on_rent_payments_view|garages_payments_view')->only('receipts', 'allReceipts');
        $this->middleware('permission:bike_on_rent_invoices_view')->only('invoices');
        $this->middleware('permission:bike_on_rent_maintenance_view|garages_maintenance_view')->only('maintenances');
    }

    public function index(Request $request)
    {

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = BikeRentCompany::query()
            ->with('account')
            ->orderBy('id', 'desc')
            ->where('customer_type', 'bike_rental');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('bike_rent_companies.table', ['data' => $data])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('bike_rent_companies.index', ['data' => $data, 'type' => 'bike_rental']);
    }

    public function garageIndex(Request $request)
    {

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = BikeRentCompany::query()
            ->with('account')
            ->orderBy('id', 'desc')
            ->where('customer_type' , 'garage');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('bike_rent_companies.table', ['data' => $data])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('bike_rent_companies.index', ['data' => $data, 'type' => 'garage']);
    }

    public function create()
    {
        $type = request()->input('type');
        return view('bike_rent_companies.create', compact('type'));
    }

    public function store(CreateBikeRentCompaniesRequest $request)
    {

        $input = $request->all();
        if($input['customer_type'] == 'bike_rental') {
            $customersAsset = \App\Support\GlobalAccounts::id('VEHICLE_RENTAL_CUSTOMERS');
        }else {
            $customersAsset = \App\Support\GlobalAccounts::id('GARAGE_CUSTOMERS');
        }
        try {
            DB::beginTransaction();
            $input['created_by'] = auth()->id();
            $bikeRentCompany = $this->bikeRentCompaniesRepository->create($input);

            $account = new Accounts();
            if($bikeRentCompany->customer_type == 'bike_rental') {
                $account->account_code = 'BR' . str_pad((string) $bikeRentCompany->id, 4, '0', STR_PAD_LEFT);
                $account->ref_name = 'BikeRentCompany';
            }else {
                $account->account_code = 'GC' . str_pad((string) $bikeRentCompany->id, 4, '0', STR_PAD_LEFT);
                $account->ref_name = 'GarageCustomer';
            }
            $account->account_type = 'Asset';
            $account->name = $bikeRentCompany->name;
            $account->parent_id = $customersAsset;
            $account->ref_id = $bikeRentCompany->id;
            $account->status = (int) $bikeRentCompany->status;
            $account->branch_id = $bikeRentCompany->branch_id;
            $account->created_by = auth()->id();
            $account->save();

            $bikeRentCompany->account_id = $account->id;
            $bikeRentCompany->save();
            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Bike on rent customer added successfully.', 'reload' => true], 200);
            }
            Flash::success('Bike on rent customer created successfully.');
            return redirect(route('bikeRentCompanies.index'));
        } catch (\Exception $e) {
            \Log::error('Bike rent company store failed: ' . $e->getMessage());
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
            }
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function show($company_slug, $id)
    {
        $bikeRentCompany = $this->bikeRentCompaniesRepository->find((int) $id);
        if (empty($bikeRentCompany)) {
            Flash::error('Record not found');
            return redirect(route('bikeRentCompanies.index'));
        }

        $bikeRentCompany->load('account');

        return view('bike_rent_companies.show', ['bikeRentCompany' => $bikeRentCompany]);
    }

    public function edit($company_slug, $id)
    {

        $bikeRentCompany = $this->bikeRentCompaniesRepository->find((int) $id);
        if (empty($bikeRentCompany)) {
            Flash::error('Record not found');
            return redirect(route('bikeRentCompanies.index'));
        }

        return view('bike_rent_companies.edit', ['bikeRentCompany' => $bikeRentCompany]);
    }

    public function update($company_slug, $id, UpdateBikeRentCompaniesRequest $request)
    {
        $bikeRentCompany = $this->bikeRentCompaniesRepository->find((int) $id);
        if (empty($bikeRentCompany)) {
            return response()->json(['errors' => ['error' => 'Record not found!']], 422);
        }

        $input = $request->all();
        $input['updated_by'] = auth()->id();

        $bikeRentCompany = $this->bikeRentCompaniesRepository->update($input, (int) $id);

        if ($bikeRentCompany->account) {
            $bikeRentCompany->account->name = $bikeRentCompany->name;
            $bikeRentCompany->account->status = (int) $bikeRentCompany->status;
            $bikeRentCompany->account->branch_id = $bikeRentCompany->branch_id;
            $bikeRentCompany->account->save();
        }

        return response()->json(['message' => 'Customer Info Updated Successfully.', 'reload' => true]);
    }

    public function destroy($company_slug, $id)
    {

        $bikeRentCompany = $this->bikeRentCompaniesRepository->find((int) $id);
        if (empty($bikeRentCompany)) {
            return response()->json(['errors' => ['error' => 'Record not found!']], 422);
        }

        if ($bikeRentCompany->transactions()->count() > 0) {
            return response()->json([
                'errors' => [
                    'error' => 'Cannot delete this Customer. It has ' . $bikeRentCompany->transactions()->count() . ' transaction(s). Please deactivate instead.',
                ],
            ], 422);
        }

        if ($bikeRentCompany->account) {
            $ledgerEntriesCount = \App\Support\CompanyQuery::table('ledger_entries')
                ->where('account_id', $bikeRentCompany->account->id)
                ->count();
            if ($ledgerEntriesCount > 0) {
                return response()->json([
                    'errors' => [
                        'error' => "Cannot delete this record. The linked account has {$ledgerEntriesCount} ledger entry(ies).",
                    ],
                ], 422);
            }
        }

        $cascadedItems = [];
        $relatedAccount = $bikeRentCompany->account;

        $bikeRentCompany->delete();

        if ($relatedAccount) {
            $cascadedItems[] = [
                'model' => 'Accounts',
                'id' => $relatedAccount->id,
                'name' => $relatedAccount->name,
            ];
            $relatedAccount->delete();

            $this->trackCascadeDeletion(
                BikeRentCompany::class,
                $bikeRentCompany->id,
                $bikeRentCompany->name,
                Accounts::class,
                $relatedAccount->id,
                $relatedAccount->name,
                'hasOne',
                'account',
                'soft'
            );
        }

        $cascadeMessage = '';
        if (!empty($cascadedItems)) {
            $parts = array_map(fn($item) => "{$item['model']}: {$item['name']}", $cascadedItems);
            $cascadeMessage = ' (Also deleted: ' . implode(', ', $parts) . ')';
        }

        return response()->json([
            'message' => 'Moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=bike_rent_companies" class="alert-link">View Recycle Bin</a> to restore if needed.',
        ]);
    }

    protected function getTrashModelClass()
    {
        return BikeRentCompany::class;
    }

    protected function getTrashConfig()
    {
        return [
            'name' => 'Bike on rent customer',
            'display_columns' => ['name', 'email', 'company_contact'],
            'trash_view' => 'bike_rent_companies.trash',
            'index_route' => 'bikeRentCompanies.index',
        ];
    }

    public function ledger($company_slug, $id, LedgerDataTable $ledgerDataTable)
  {
    $customer = BikeRentCompany::find($id);
    if (!$customer) {
      Flash::error('Customer not found');
      return redirect()->back();
    }

    if (empty($customer->account_id)) {
      Flash::error('Customer account not linked. Please assign an account first.');
      return redirect()->back();
    }

    $details = $this->getDetails($customer->account_id);
    $account_id = $customer->account_id;

    return $ledgerDataTable->with(['account_id' => $account_id])->render('bike_rent_companies.ledger', compact('customer', 'details'));
  }

  public function files($company_slug, $id, FilesDataTable $filesDataTable)
  {
    $customer = BikeRentCompany::find($id);
    if (!$customer) {
      Flash::error('Bike on rent customer not found');
      return redirect()->back();
    }
    $files = Files::where(['type' => 'rentCompany', 'type_id' => $id])->latest('id')->get();
    $details = $this->getDetails($customer->account_id);
    return view('bike_rent_companies.files', compact('files', 'details', 'customer'));
  }

  public function receipts(Request $request, $company_slug, $id)
  {
    $customer = BikeRentCompany::find($id);

    if (empty($customer)) {
      Flash::error('Bike on rent customer not found');
      return redirect()->back();
    }

    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Receipt::query()->latest('date_of_receipt')->with('payerAccount', 'payeeAccount');
    $query->where('payer_account_id', $customer->account_id);

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    $details = $this->getDetails($customer->account_id);
    return view('bike_rent_companies.receipts', compact('data', 'customer', 'details'));
  }

  public function allReceipts(Request $request)
  {
    $type = str_contains($request->path(), 'bikeRentCompany') ? 'bike_rental' : 'garage' ;
    $account_ids = BikeRentCompany::where('customer_type', $type)->pluck('account_id')->toArray();
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Receipt::query()->latest('date_of_receipt');
    $query->whereIn('payer_account_id', $account_ids);
    $data = $this->applyPagination($query, $paginationParams);
    return view('bike_rent_companies.all_receipts', compact('data'));
  }

  public function invoices(Request $request, $company_slug, $id)
  {
    $customer = BikeRentCompany::find($id);
    if (!$customer) {
      Flash::error('Bike on rent customer not found');
      return redirect()->back();
    }
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = LeasingCompanyBillingInvoice::query()->latest('billing_month')->where('customer_id', $id);
    $invoices = $this->applyPagination($query, $paginationParams);
    $details = $this->getDetails($customer->account_id);
    return view('bike_rent_companies.invoices', compact('invoices', 'customer', 'details'));
  }

  public function bikes(Request $request, $company_slug, $id)
  {
    $customer = BikeRentCompany::find($id);
    if (!$customer) {
      Flash::error('Bike on rent customer not found');
      return redirect()->back();
    }
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = Bikes::query()
    ->with(['history' => function($q) use ($customer) {
        $q->where('rental_company_id', $customer->id)
          ->orderBy('note_date', 'desc');
    }])
    ->where(function($query) use ($customer) {
        $query->where('rental_company_id', $customer->id)
            ->orWhereHas('history', function($subQuery) use ($customer) {
                $subQuery->where('rental_company_id', $customer->id);
            });
    });
    $bikes = $this->applyPagination($query, $paginationParams);
    $details = $this->getDetails($customer->account_id);
    return view('bike_rent_companies.bikes', compact('bikes', 'customer', 'details'));
  }

  public function maintenances(Request $request, $company_slug, $id)
  {
    $customer = BikeRentCompany::find($id);
    if (!$customer) {
      Flash::error('Bike on rent customer not found');
      return redirect()->back();
    }
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
    $query = BikeMaintenance::where('rental_company_id', $customer->id);
    $maintenances = $this->applyPagination($query, $paginationParams);
    $details = $this->getDetails($customer->account_id);
    return view('bike_rent_companies.maintenances', compact('maintenances','customer','details'));
  }

  private function getDetails($accountId)
  {
    $currentMonthStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
    $currentMonthEnd = \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d');
    $transactions = Transactions::where('account_id', $accountId)->get();
    $currentMonthCredit = $transactions->whereBetween('trans_date', [$currentMonthStart, $currentMonthEnd])->sum('credit');
    $currentMonthDebit = $transactions->whereBetween('trans_date', [$currentMonthStart, $currentMonthEnd])->sum('debit');
    $netFlow = $currentMonthDebit - $currentMonthCredit;

    // Calculate balance
    $credit = $transactions->sum('credit');
    $debit = $transactions->sum('debit');
    $balance = $debit - $credit;
    return [
      'currentMonthCredit' => $currentMonthCredit,
      'currentMonthDebit' => $currentMonthDebit,
      'netFlow' => $netFlow,
      'credit' => $credit,
      'debit' => $debit,
      'balance' => $balance
    ];
  }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBikeRentCompaniesRequest;
use App\Http\Requests\UpdateBikeRentCompaniesRequest;
use App\Models\Accounts;
use App\Models\BikeRentCompany;
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
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('bike_rent_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = BikeRentCompany::query()
            ->with('account')
            ->orderBy('id', 'desc');

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

        return view('bike_rent_companies.index', ['data' => $data]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('bike_rent_create')) {
            abort(403, 'Unauthorized action.');
        }
        return view('bike_rent_companies.create');
    }

    public function store(CreateBikeRentCompaniesRequest $request)
    {
        if (!auth()->user()->hasPermissionTo('bike_rent_create')) {
            abort(403, 'Unauthorized action.');
        }

        $input = $request->all();
        $customersAsset = Accounts::where('name', 'Customers (Vehicle Rental)')->where('account_type', 'Asset')->first();
        if (!$customersAsset) {
            $message = 'Chart of accounts is missing a "Customers (Vehicle Rental)" (Asset) head under Assets. Add it in Chart of Accounts first or run migrations.';
            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        try {
            DB::beginTransaction();
            $input['created_by'] = auth()->id();
            $bikeRentCompany = $this->bikeRentCompaniesRepository->create($input);

            $account = new Accounts();
            $account->account_code = 'BR' . str_pad((string) $bikeRentCompany->id, 4, '0', STR_PAD_LEFT);
            $account->account_type = 'Asset';
            $account->name = $bikeRentCompany->name;
            $account->parent_id = $customersAsset->id;
            $account->ref_name = 'BikeRentCompany';
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
        if (!auth()->user()->hasPermissionTo('bike_rent_view')) {
            abort(403, 'Unauthorized action.');
        }

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
        if (!auth()->user()->hasPermissionTo('bike_rent_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $bikeRentCompany = $this->bikeRentCompaniesRepository->find((int) $id);
        if (empty($bikeRentCompany)) {
            Flash::error('Record not found');
            return redirect(route('bikeRentCompanies.index'));
        }

        return view('bike_rent_companies.edit', ['bikeRentCompany' => $bikeRentCompany]);
    }

    public function update($company_slug, $id, UpdateBikeRentCompaniesRequest $request)
    {
        if (!auth()->user()->hasPermissionTo('bike_rent_edit')) {
            abort(403, 'Unauthorized action.');
        }

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

        return response()->json(['message' => 'Bike on rent customer updated successfully.']);
    }

    public function destroy($company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('bike_rent_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $bikeRentCompany = $this->bikeRentCompaniesRepository->find((int) $id);
        if (empty($bikeRentCompany)) {
            return response()->json(['errors' => ['error' => 'Record not found!']], 422);
        }

        if ($bikeRentCompany->transactions()->count() > 0) {
            return response()->json([
                'errors' => [
                    'error' => 'Cannot delete this record. It has ' . $bikeRentCompany->transactions()->count() . ' transaction(s). Please deactivate instead.',
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
}

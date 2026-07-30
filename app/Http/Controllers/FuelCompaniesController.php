<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFuelCompaniesRequest;
use App\Http\Requests\UpdateFuelCompaniesRequest;
use App\Models\Accounts;
use App\Models\FuelCompany;
use App\Repositories\FuelCompaniesRepository;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelCompaniesController extends AppBaseController
{
    use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;

    private FuelCompaniesRepository $fuelCompaniesRepository;

    public function __construct(FuelCompaniesRepository $fuelCompaniesRepository)
    {
        $this->fuelCompaniesRepository = $fuelCompaniesRepository;
    }

    public function index(Request $request)
    {
        if (!user_can('fuel_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = FuelCompany::query()
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
            $tableData = view('fuel_companies.table', ['data' => $data])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('fuel_companies.index', ['data' => $data]);
    }

    public function create()
    {
        if (!user_can('fuel_create')) {
            abort(403, 'Unauthorized action.');
        }
        return view('fuel_companies.create');
    }

    public function store(CreateFuelCompaniesRequest $request)
    {
        if (!user_can('fuel_create')) {
            abort(403, 'Unauthorized action.');
        }

        $input = $request->all();
        $parentAccount = \App\Support\GlobalAccounts::id('FUEL_COMPANIES_PARENT');

        try {
            DB::beginTransaction();
            $input['created_by'] = auth()->id();
            $fuelCompany = $this->fuelCompaniesRepository->create($input);

            $account = new Accounts();
            $account->account_code = 'FC' . str_pad((string) $fuelCompany->id, 4, '0', STR_PAD_LEFT);
            $account->account_type = 'Asset';
            $account->name = $fuelCompany->name;
            $account->parent_id = $parentAccount;
            $account->ref_name = 'FuelCompany';
            $account->ref_id = $fuelCompany->id;
            $account->status = (int) $fuelCompany->status;
            $account->branch_id = $fuelCompany->branch_id;
            $account->created_by = auth()->id();
            $account->save();

            $fuelCompany->account_id = $account->id;
            $fuelCompany->save();
            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Fuel company added successfully.', 'reload' => true], 200);
            }
            Flash::success('Fuel company created successfully.');
            return redirect(route('fuelCompanies.index'));
        } catch (\Exception $e) {
            \Log::error('Fuel company store failed: ' . $e->getMessage());
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
        if (!user_can('fuel_view')) {
            abort(403, 'Unauthorized action.');
        }

        $fuelCompany = $this->fuelCompaniesRepository->find((int) $id);
        if (empty($fuelCompany)) {
            Flash::error('Fuel company not found');
            return redirect(route('fuelCompanies.index'));
        }

        $fuelCompany->load('account');

        return view('fuel_companies.show', ['fuelCompany' => $fuelCompany]);
    }

    public function edit($company_slug, $id)
    {
        if (!user_can('fuel_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $fuelCompany = $this->fuelCompaniesRepository->find((int) $id);
        if (empty($fuelCompany)) {
            Flash::error('Fuel company not found');
            return redirect(route('fuelCompanies.index'));
        }

        return view('fuel_companies.edit', ['fuelCompany' => $fuelCompany]);
    }

    public function update($company_slug, $id, UpdateFuelCompaniesRequest $request)
    {
        if (!user_can('fuel_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $fuelCompany = $this->fuelCompaniesRepository->find((int) $id);
        if (empty($fuelCompany)) {
            return response()->json(['errors' => ['error' => 'Fuel company not found!']], 422);
        }

        $input = $request->all();
        $input['updated_by'] = auth()->id();

        $fuelCompany = $this->fuelCompaniesRepository->update($input, (int) $id);

        if ($fuelCompany->account) {
            $fuelCompany->account->name = $fuelCompany->name;
            $fuelCompany->account->status = (int) $fuelCompany->status;
            $fuelCompany->account->branch_id = $fuelCompany->branch_id;
            $fuelCompany->account->save();
        }

        return response()->json(['message' => 'Fuel company updated successfully.']);
    }

    public function destroy($company_slug, $id)
    {
        if (!user_can('fuel_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $fuelCompany = $this->fuelCompaniesRepository->find((int) $id);
        if (empty($fuelCompany)) {
            return response()->json(['errors' => ['error' => 'Fuel company not found!']], 422);
        }

        if ($fuelCompany->transactions()->count() > 0) {
            return response()->json([
                'errors' => [
                    'error' => 'Cannot delete fuel company. It has ' . $fuelCompany->transactions()->count() . ' transaction(s). Please deactivate instead.',
                ],
            ], 422);
        }

        if ($fuelCompany->account) {
            $ledgerEntriesCount = \App\Support\CompanyQuery::table('ledger_entries')
                ->where('account_id', $fuelCompany->account->id)
                ->count();
            if ($ledgerEntriesCount > 0) {
                return response()->json([
                    'errors' => [
                        'error' => "Cannot delete fuel company. The linked account has {$ledgerEntriesCount} ledger entry(ies).",
                    ],
                ], 422);
            }
        }

        $cascadedItems = [];
        $relatedAccount = $fuelCompany->account;

        $fuelCompany->delete();

        if ($relatedAccount) {
            $cascadedItems[] = [
                'model' => 'Accounts',
                'id' => $relatedAccount->id,
                'name' => $relatedAccount->name,
            ];
            $relatedAccount->delete();

            $this->trackCascadeDeletion(
                FuelCompany::class,
                $fuelCompany->id,
                $fuelCompany->name,
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
            'message' => 'Fuel company moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=fuel_companies" class="alert-link">View Recycle Bin</a> to restore if needed.',
        ]);
    }

    protected function getTrashModelClass()
    {
        return FuelCompany::class;
    }

    protected function getTrashConfig()
    {
        return [
            'name' => 'Fuel company',
            'display_columns' => ['name', 'email', 'company_contact'],
            'trash_view' => 'fuel_companies.trash',
            'index_route' => 'fuelCompanies.index',
        ];
    }
}

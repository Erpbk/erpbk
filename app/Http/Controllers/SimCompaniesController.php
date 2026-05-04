<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSimCompaniesRequest;
use App\Http\Requests\UpdateSimCompaniesRequest;
use App\Models\Accounts;
use App\Models\SimCompany;
use App\Repositories\SimCompaniesRepository;
use App\Traits\GlobalPagination;
use App\Traits\HasTrashFunctionality;
use App\Traits\TracksCascadingDeletions;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimCompaniesController extends AppBaseController
{
    use GlobalPagination, HasTrashFunctionality, TracksCascadingDeletions;

    private SimCompaniesRepository $simCompaniesRepository;

    public function __construct(SimCompaniesRepository $simCompaniesRepository)
    {
        $this->simCompaniesRepository = $simCompaniesRepository;
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('sim_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = SimCompany::query()
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
            $tableData = view('sim_companies.table', ['data' => $data])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('sim_companies.index', ['data' => $data]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('sim_create')) {
            abort(403, 'Unauthorized action.');
        }
        return view('sim_companies.create');
    }

    public function store(CreateSimCompaniesRequest $request)
    {
        if (!auth()->user()->hasPermissionTo('sim_create')) {
            abort(403, 'Unauthorized action.');
        }

        $input = $request->all();
        $currentLiabilities = Accounts::where('name', 'Sims (Company)')->where('account_type', 'Liability')->first();
        if (!$currentLiabilities) {
            return response()->json([
                'success' => false,
                'message' => 'Parent account "Sims (Company)" not found.',
            ], 422);
            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        try {
            DB::beginTransaction();
            $input['created_by'] = auth()->id();
            $simCompany = $this->simCompaniesRepository->create($input);

            $account = new Accounts();
            $account->account_code = 'SC' . str_pad((string) $simCompany->id, 4, '0', STR_PAD_LEFT);
            $account->account_type = 'Liability';
            $account->name = $simCompany->name;
            $account->parent_id = $currentLiabilities->id;
            $account->ref_name = 'SimCompany';
            $account->ref_id = $simCompany->id;
            $account->status = (int) $simCompany->status;
            $account->branch_id = $simCompany->branch_id;
            $account->created_by = auth()->id();
            $account->save();

            $simCompany->account_id = $account->id;
            $simCompany->save();
            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'SIM company added successfully.', 'reload' => true], 200);
            }
            Flash::success('SIM company created successfully.');
            return redirect(route('simCompanies.index'));
        } catch (\Exception $e) {
            \Log::error('Sim company store failed: ' . $e->getMessage());
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
        if (!auth()->user()->hasPermissionTo('sim_view')) {
            abort(403, 'Unauthorized action.');
        }

        $simCompany = $this->simCompaniesRepository->find((int) $id);
        if (empty($simCompany)) {
            Flash::error('SIM company not found');
            return redirect(route('simCompanies.index'));
        }

        $simCompany->load('account');

        return view('sim_companies.show', ['simCompany' => $simCompany]);
    }

    public function edit($company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('sim_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $simCompany = $this->simCompaniesRepository->find((int) $id);
        if (empty($simCompany)) {
            Flash::error('SIM company not found');
            return redirect(route('simCompanies.index'));
        }

        return view('sim_companies.edit', ['simCompany' => $simCompany]);
    }

    public function update($company_slug, $id, UpdateSimCompaniesRequest $request)
    {
        if (!auth()->user()->hasPermissionTo('sim_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $simCompany = $this->simCompaniesRepository->find((int) $id);
        if (empty($simCompany)) {
            return response()->json(['errors' => ['error' => 'SIM company not found!']], 422);
        }

        $input = $request->all();
        $input['updated_by'] = auth()->id();

        $simCompany = $this->simCompaniesRepository->update($input, (int) $id);

        if ($simCompany->account) {
            $simCompany->account->name = $simCompany->name;
            $simCompany->account->status = (int) $simCompany->status;
            $simCompany->account->branch_id = $simCompany->branch_id;
            $simCompany->account->save();
        }

        return response()->json(['message' => 'SIM company updated successfully.']);
    }

    public function destroy($company_slug, $id)
    {
        if (!auth()->user()->hasPermissionTo('sim_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $simCompany = $this->simCompaniesRepository->find((int) $id);
        if (empty($simCompany)) {
            return response()->json(['errors' => ['error' => 'SIM company not found!']], 422);
        }

        if ($simCompany->transactions()->count() > 0) {
            return response()->json([
                'errors' => [
                    'error' => 'Cannot delete SIM company. It has ' . $simCompany->transactions()->count() . ' transaction(s). Please deactivate instead.',
                ],
            ], 422);
        }

        if ($simCompany->account) {
            $ledgerEntriesCount = \App\Support\CompanyQuery::table('ledger_entries')
                ->where('account_id', $simCompany->account->id)
                ->count();
            if ($ledgerEntriesCount > 0) {
                return response()->json([
                    'errors' => [
                        'error' => "Cannot delete SIM company. The linked account has {$ledgerEntriesCount} ledger entry(ies).",
                    ],
                ], 422);
            }
        }

        $cascadedItems = [];
        $relatedAccount = $simCompany->account;

        $simCompany->delete();

        if ($relatedAccount) {
            $cascadedItems[] = [
                'model' => 'Accounts',
                'id' => $relatedAccount->id,
                'name' => $relatedAccount->name,
            ];
            $relatedAccount->delete();

            $this->trackCascadeDeletion(
                SimCompany::class,
                $simCompany->id,
                $simCompany->name,
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
            'message' => 'SIM company moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=sim_companies" class="alert-link">View Recycle Bin</a> to restore if needed.',
        ]);
    }

    protected function getTrashModelClass()
    {
        return SimCompany::class;
    }

    protected function getTrashConfig()
    {
        return [
            'name' => 'SIM company',
            'display_columns' => ['name', 'email', 'company_contact'],
            'trash_view' => 'sim_companies.trash',
            'index_route' => 'simCompanies.index',
        ];
    }
}

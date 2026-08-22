<?php

namespace App\Http\Controllers;

use App\DataTables\LedgerDataTable;
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
use Illuminate\Support\Facades\Schema;

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
        if (!user_can('sim_view')) {
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
        if (!user_can('sim_create')) {
            abort(403, 'Unauthorized action.');
        }
        return view('sim_companies.create');
    }

    public function store(CreateSimCompaniesRequest $request)
    {
        if (!user_can('sim_create')) {
            abort(403, 'Unauthorized action.');
        }

        $input = $request->all();

        try {
            $currentLiabilities = \App\Support\GlobalAccounts::account('SIM_COMPANIES');
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
        if (!user_can('sim_view')) {
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

    public function ledger($company_slug, $id, LedgerDataTable $ledgerDataTable)
    {
        if (!user_can('sim_view')) {
            abort(403, 'Unauthorized action.');
        }

        $simCompany = $this->simCompaniesRepository->find((int) $id);
        if (empty($simCompany)) {
            Flash::error('SIM company not found');
            return redirect(route('simCompanies.index'));
        }

        if (empty($simCompany->account_id)) {
            Flash::error('SIM company has no linked ledger account.');
            return redirect(route('simCompanies.show', $simCompany->id));
        }

        return $ledgerDataTable->with(['account_id' => $simCompany->account_id])
            ->render('sim_companies.ledger', [
                'simCompany' => $simCompany,
            ]);
    }

    public function edit($company_slug, $id)
    {
        if (!user_can('sim_edit')) {
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
        if (!user_can('sim_edit')) {
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
        if (!user_can('sim_delete')) {
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

        $willQueue = \App\Services\DeleteRequestService::enabled()
            && ! \App\Services\DeleteRequestService::shouldBypassApproval();

        if (! $willQueue && Schema::hasColumn($simCompany->getTable(), 'deleted_by')) {
            $simCompany->deleted_by = auth()->id();
            $simCompany->save();
        }

        $cascadedItems = [];
        $relatedAccount = $simCompany->account;

        $simCompany->delete();
        $queued = (bool) request()->attributes->get('delete_approval_created');

        if ($relatedAccount) {
            if (! $queued && Schema::hasColumn($relatedAccount->getTable(), 'deleted_by')) {
                $relatedAccount->deleted_by = auth()->id();
                $relatedAccount->save();
            }

            // Queued: intercepts into cascaded_records (account stays live until approve).
            $relatedAccount->delete();

            if (! $queued) {
                $cascadedItems[] = [
                    'model' => 'Accounts',
                    'id' => $relatedAccount->id,
                    'name' => $relatedAccount->name,
                ];
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
        }

        $cascadeMessage = '';
        if (!empty($cascadedItems)) {
            $parts = array_map(fn($item) => "{$item['model']}: {$item['name']}", $cascadedItems);
            $cascadeMessage = ' (Also deleted: ' . implode(', ', $parts) . ')';
        }

        $message = $queued
            ? delete_outcome_message('SIM company')
            : ('SIM company moved to Recycle Bin' . $cascadeMessage . '. <a href="' . route('settings-panel.trash.index') . '?module=sim_companies" class="alert-link">View Recycle Bin</a> to restore if needed.');

        return response()->json([
            'queued' => $queued,
            'message' => $message,
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

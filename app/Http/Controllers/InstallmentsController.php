<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesVisaInstallments;
use App\Models\ExpenseAccount;
use App\Models\Riders;
use App\Models\visa_installment_plan;
use App\Support\CompanyAuthRedirect;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Illuminate\Http\Request;

class InstallmentsController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions, ManagesVisaInstallments;

    public function __construct()
    {
        $this->middleware('permission:visa_expense_view')->only('index');
    }
    /**
     * List expense accounts with installment activity (main module index).
     */
    public function index(Request $request)
    {

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $userBranches = app('user_branches');

        $query = ExpenseAccount::query()
            ->with('rider')
            ->whereHas('rider')
            ->orderByDesc('id');

        if (!auth()->user()->isAdmin()) {
            if (!empty($userBranches)) {
                $query->whereHas('rider', function ($q) use ($userBranches) {
                    $q->whereIn('branch_id', $userBranches)->orWhereNull('branch_id');
                });
            } else {
                $query->whereHas('rider', function ($q) {
                    $q->whereNull('branch_id');
                });
            }
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('quick_search')) {
            $term = trim((string) $request->quick_search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhereHas('rider', function ($qr) use ($term) {
                        $qr->where('name', 'like', '%' . $term . '%')
                            ->orWhere('rider_id', 'like', '%' . $term . '%')
                            ->orWhere('person_code', 'like', '%' . $term . '%');
                    });
            });
        }

        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('installments.account_table', compact('data'))->render(),
                'paginationLinks' => $data->links('components.global-pagination')->render(),
            ]);
        }

        return view('installments.account_index', compact('data'));
    }
}

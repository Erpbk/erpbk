<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Http\Requests\CreateRecruitersRequest;
use App\Http\Requests\UpdateRecruitersRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Accounts;
use App\Models\Recruiters;
use App\Repositories\RecruitersRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;
use App\Models\Riders;

class RecruitersController extends AppBaseController
{
    use GlobalPagination;
    /** @var RecruitersRepository $recruitersRepository*/
    private $recruitersRepository;

    public function __construct(RecruitersRepository $recruitersRepo)
    {
        $this->recruitersRepository = $recruitersRepo;
    }

    /**
     * Display a listing of the Recruiters.
     */
    public function index(Request $request)
    {
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Recruiters::query()
            ->orderBy('id', 'desc');
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('account_id') && !empty($request->account_id)) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('recruiters.table', [
                'data' => $data,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }
        return view('recruiters.index', [
            'data' => $data,
        ]);
    }


    /**
     * Show the form for creating a new Recruiters.
     */
    public function create()
    {
        return view('recruiters.create');
    }

    /**
     * Store a newly created Recruiters in storage.
     */
    public function store(CreateRecruitersRequest $request)
    {
        $input = $request->all();

        $recruiter = $this->recruitersRepository->create($input);

        //Adding Account and setting reference

        $parentAccount = Accounts::firstOrCreate(
            ['name' => 'Recruiter', 'account_type' => 'Liability', 'parent_id' => null],
            ['name' => 'Recruiter', 'account_type' => 'Liability', 'account_code' => Account::code()]
        );

        $account = new Accounts();
        $account->account_code = 'RC' . str_pad($recruiter->id, 4, "0", STR_PAD_LEFT);
        $account->account_type = 'Liability';
        $account->name = $recruiter->name;
        $account->parent_id = $parentAccount->id;
        $account->ref_name = 'Recruiter';
        $account->ref_id = $recruiter->id;
        $account->status = $recruiter->status;
        $account->created_by = auth()->user()->id;
        $account->save();

        $recruiter->account_id = $account->id;
        $recruiter->created_by = auth()->user()->id;
        $recruiter->save();

        return response()->json(['message' => 'Recruiter added successfully.']);
    }

    /**
     * Display the specified Recruiters.
     */
    public function show($id)
    {
        $recruiters = $this->recruitersRepository->find((int)$id);

        if (empty($recruiters)) {
            Flash::error('Recruiters not found');

            return redirect(route('recruiters.index'));
        }

        return view('recruiters.show')->with('recruiters', $recruiters);
    }

    /**
     * Show the form for editing the specified Recruiters.
     */
    public function edit($id)
    {
        $recruiters = $this->recruitersRepository->find((int)$id);

        if (empty($recruiters)) {
            Flash::error('Recruiters not found');

            return redirect(route('recruiters.index'));
        }

        return view('recruiters.edit')->with('recruiters', $recruiters);
    }

    /**
     * Update the specified Recruiters in storage.
     */
    public function update($id, UpdateRecruitersRequest $request)
    {
        $recruiter = $this->recruitersRepository->find((int)$id);

        if (empty($recruiter)) {

            return response()->json(['errors' => ['error' => 'Recruiter not found!']], 422);
        }

        $recruiter = $this->recruitersRepository->update($request->all(), $id);
        $recruiter->account->status = $recruiter->status;
        $recruiter->account->created_by = auth()->user()->id;
        $recruiter->save();

        return response()->json(['message' => 'Recruiter updated successfully.']);
    }

    /**
     * Remove the specified Recruiters from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $recruiter = $this->recruitersRepository->find((int)$id);

        if (empty($recruiter)) {
            return response()->json(['errors' => ['error' => 'Recruiter not found!']], 422);
        }


        if ($recruiter->transactions->count() > 0) {
            return response()->json(['errors' => ['error' => 'Recruiter have transactions!']], 422);
        } else {
            // Remove the account deletion
            $this->recruitersRepository->delete($id);
        }


        return response()->json(['message' => 'Recruiter deleted successfully.']);
    }

    /**
     * Show riders for a specific recruiter
     */
    public function showRiders($id)
    {
        $recruiter = $this->recruitersRepository->find((int)$id);

        if (empty($recruiter)) {
            Flash::error('Recruiters not found');
            return redirect(route('recruiters.index'));
        }

        // Eager load the recruiter relationship for all riders
        $riders = $recruiter->riders()->with('recruiter')->paginate(10);

        return view('recruiters.riders', [
            'recruiter' => $recruiter,
            'riders' => $riders
        ]);
    }

    /**
     * Assign riders to a recruiter
     */
    public function assignRiders(Request $request, $recruiterId)
    {
        $recruiter = $this->recruitersRepository->find((int)$recruiterId);

        if (empty($recruiter)) {
            return response()->json(['errors' => ['error' => 'Recruiter not found!']], 422);
        }

        // Validate the request
        $request->validate([
            'rider_ids' => 'required|array',
            'rider_ids.*' => 'exists:riders,id'
        ]);

        // Get the rider IDs from the request
        $riderIds = $request->input('rider_ids', []);

        // Update riders to assign to this recruiter
        Riders::whereIn('id', $riderIds)->update(['recruiter_id' => $recruiterId]);

        return response()->json([
            'message' => count($riderIds) . ' riders assigned to recruiter successfully.',
            'recruiter' => $recruiter
        ]);
    }

    /**
     * Remove riders from a recruiter
     */
    public function removeRiders(Request $request, $recruiterId)
    {
        $recruiter = $this->recruitersRepository->find((int)$recruiterId);

        if (empty($recruiter)) {
            return response()->json(['errors' => ['error' => 'Recruiter not found!']], 422);
        }

        // Validate the request
        $request->validate([
            'rider_ids' => 'required|array',
            'rider_ids.*' => 'exists:riders,id'
        ]);

        // Get the rider IDs from the request
        $riderIds = $request->input('rider_ids', []);

        // Update riders to remove from this recruiter
        $updatedCount = Riders::whereIn('id', $riderIds)
            ->where('recruiter_id', $recruiterId)
            ->update(['recruiter_id' => null]);

        return response()->json([
            'message' => $updatedCount . ' riders removed from recruiter successfully.',
            'recruiter' => $recruiter
        ]);
    }

    /**
     * Get unassigned riders for a recruiter
     */
    public function getUnassignedRiders(Request $request)
    {
        // Get riders without a recruiter
        $unassignedRiders = Riders::whereNull('recruiter_id')
            ->select('id', 'name', 'rider_id')
            ->get();

        return response()->json($unassignedRiders);
    }

    /**
     * Show the view for assigning riders to a recruiter
     */
    public function showAssignRidersView($recruiterId)
    {
        $recruiter = $this->recruitersRepository->find((int)$recruiterId);

        if (empty($recruiter)) {
            Flash::error('Recruiter not found');
            return redirect(route('recruiters.index'));
        }

        return view('recruiters.assign-riders', [
            'recruiter' => $recruiter
        ]);
    }
}

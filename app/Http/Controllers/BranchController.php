<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laracasts\Flash\Flash;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{

    /**
     * Display a listing of branches with DataTables.
     */
    public function index(Request $request)
    {
        $branches = Branch::with(['parent', 'createdBy'])->get();
        return view('branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create()
    {
        $parents = Branch::active()->get();
        $branchTypes = [
            'headquarters' => 'Headquarters',
            'branch' => 'Branch',
            'warehouse' => 'Warehouse',
            'grage' => 'Garage',
        ];
        
        return view('branches.create', compact('parents', 'branchTypes'));
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {

        $data = $this->validate($request, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:branches,code',
            'contact' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'parent_branch_id' => 'nullable|exists:branches,id',
            'branch_type' => 'required|in:headquarters,branch,warehouse,grage',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $data['is_active'] = $request->has('is_active') ? true : false;
            $data['created_by'] = auth()->id();
            
            // Ensure only one headquarters
            if ($request->branch_type === 'headquarters') {
                Branch::where('branch_type', 'headquarters')->update(['branch_type' => 'branch']);
            }

            $branch = Branch::create($data);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Branch created successfully.',
                    'reload' => true,
                ]);
            }
            Flash::success('Branch created successfully.');
            return redirect()->route('settings-panel.branches.index');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating branch: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error creating branch: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified branch with statistics.
     */
    public function show(Branch $branch)
    {
        $branch->load([
            'parent',
            'children',
            'createdBy',
            'updatedBy'
        ]);

        $statistics = $branch->getStatistics();
        
        // Get recent related records
        $recentUsers = $branch->users()->latest()->take(5)->get();
        $recentEmployees = $branch->employees()->latest()->take(5)->get();
        
        return view('branches.show', compact(
            'branch', 
            'statistics', 
            'recentUsers', 
            'recentEmployees', 
            'recentCustomers'
        ));
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch)
    {
        // Prevent setting self as parent
        $parents = Branch::active()
            ->where('id', '!=', $branch->id)
            ->where(function($query) use ($branch) {
                $query->whereNull('parent_branch_id')
                    ->orWhere('parent_branch_id', '!=', $branch->id);
            })
            ->get();
            
        $branchTypes = [
            'headquarters' => 'Headquarters',
            'branch' => 'Branch',
            'warehouse' => 'Warehouse',
            'grage' => 'Garage',
        ];
        
        return view('branches.edit', compact('branch', 'parents', 'branchTypes'));
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, Branch $branch)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:branches,code,' . $branch->id,
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'parent_branch_id' => 'nullable|exists:branches,id|not_in:' . $branch->id,
            'branch_type' => 'required|in:headquarters,branch,warehouse,grage',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['is_active'] = $request->has('is_active') ? true : false;

            // Handle headquarters change
            if ($request->branch_type === 'headquarters' && !$branch->isHeadquarters()) {
                Branch::where('branch_type', 'headquarters')->update(['branch_type' => 'branch']);
            }

            $branch->update($data);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Branch updated successfully.',
                    'reload' => true,
                ]);
            }
            Flash::success('Branch updated successfully.');
            return redirect()->route('settings-panel.branches.index');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating branch: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error updating branch: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch)
    {
        // Check if branch has children
        if ($branch->children()->exists()) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete branch with child branches.'
                ], 422);
            }
            return back()->with('error', 'Cannot delete branch with child branches.');
        }

        // Check for related records
        $statistics = $branch->getStatistics();
        $hasRecords = array_filter($statistics, function($count) {
            return $count > 0;
        });

        try {
            DB::beginTransaction();

            if (!empty($hasRecords)) {
                // Soft delete if has related records
                $branch->delete();
                $message = 'Branch soft deleted successfully.';
            } else {
                // Hard delete if no related records
                $branch->forceDelete();
                $message = 'Branch permanently deleted successfully.';
            }

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('settings-panel.branches.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting branch: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error deleting branch: ' . $e->getMessage());
        }
    }

    /**
     * Get hierarchical tree of branches.
     */
    public function getTree()
    {
        $rootBranch = Branch::with('children')
            ->whereNull('parent_branch_id')
            ->orderBy('name')
            ->get()
            ->map(function ($branch) {
                return $this->formatBranchForTree($branch);
            });
        
        return response()->json([
            'success' => true,
            'data' => $rootBranch
        ]);
    }

    /**
     * Format branch for tree display recursively.
     */
    private function formatBranchForTree($branch)
    {
        $formatted = [
            'id' => $branch->id,
            'name' => $branch->name,
            'type' => $branch->type,
            'type_badge' => $branch->type_badge_class,
            'status' => $branch->status,
            'status_badge' => $branch->status_badge_class,
            'contact' => $branch->contact,
            'address' => $branch->address,
        ];

        if ($branch->children->count() > 0) {
            $formatted['children'] = $branch->children->map(function ($child) {
                return $this->formatBranchForTree($child);
            });
        }

        return $formatted;
    }

    /**
     * Get branch statistics.
     */
    public function getStatistics(Branch $branch)
    {
        $statistics = $branch->getStatistics();
        
        // Add additional stats
        $statistics['total_children_recursive'] = $branch->descendants()->count();
        $statistics['is_headquarters'] = $branch->isHeadquarters();
        $statistics['is_garage'] = $branch->isGarage();
        $statistics['is_warehouse'] = $branch->isWarehouse();
        $statistics['is_regular_branch'] = $branch->isBranch();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laracasts\Flash\Flash;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{

    /**
     * Display a listing of branches with DataTables.
     */
    public function index(string $company_slug, Request $request)
    {
        $branches = Branch::with(['parent', 'createdBy'])->get();
        return view('branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(string $company_slug)
    {
        $parents = Branch::active()->get();
        $currentCompany = request()->attributes->get('company');
        $companyCountry = (string) ($currentCompany->country ?? '');
        $branchTypes = [
            'headquarters' => 'Headquarters',
            'branch' => 'Branch',
            'warehouse' => 'Warehouse',
            'grage' => 'Garage',
        ];

        return view('branches.create', compact('parents', 'branchTypes', 'companyCountry'));
    }

    /**
     * Store a newly created branch.
     */
    public function store(string $company_slug, Request $request)
    {
        $validator = Validator::make($request->all(), $this->branchValidationRules());

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

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
    public function show(string $company_slug, Branch $branch)
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
    public function edit(string $company_slug, Branch $branch)
    {
        // Prevent setting self as parent
        $currentCompany = request()->attributes->get('company');
        $companyCountry = (string) ($currentCompany->country ?? '');
        $parents = Branch::active()
            ->where('id', '!=', $branch->id)
            ->where(function ($query) use ($branch) {
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

        return view('branches.edit', compact('branch', 'parents', 'branchTypes', 'companyCountry'));
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, string $company_slug, Branch $branch)
    {
        $validator = Validator::make($request->all(), $this->branchValidationRules($branch));

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
    public function destroy(string $company_slug, Branch $branch)
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
        $hasRecords = array_filter($statistics, function ($count) {
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
    public function getTree(string $company_slug)
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
    /**
     * @return array<string, mixed>
     */
    private function branchValidationRules(?Branch $branch = null): array
    {
        $companyId = CompanyContext::id();

        $codeRule = Rule::unique('branches', 'code')
            ->where(function ($query) use ($companyId) {
                if ($companyId === null) {
                    $query->whereNull('company_id');
                } else {
                    $query->where('company_id', $companyId);
                }
            });

        if ($branch) {
            $codeRule->ignore($branch->id);
        }

        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:100', $codeRule],
            'contact' => 'nullable|string|max:255',
            'address' => ($branch ? 'nullable' : 'required') . '|string|max:255',
            'city' => 'nullable|string|max:255',
            'parent_branch_id' => 'nullable|exists:branches,id' . ($branch ? '|not_in:' . $branch->id : ''),
            'branch_type' => 'required|in:headquarters,branch,warehouse,grage',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string|max:255',
        ];
    }

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
    public function getStatistics(string $company_slug, Branch $branch)
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

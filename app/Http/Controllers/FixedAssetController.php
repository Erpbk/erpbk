<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\Accounts;
use App\Models\Bikes;
use App\Models\FixedAsset;
use App\Services\FixedAssets\DepreciationScheduleService;
use App\Services\FixedAssets\FixedAssetDepreciationPostingService;
use App\Services\FixedAssets\FixedAssetVoucherService;
use App\Support\CompanyAuthRedirect;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;
use Flash;
use DB;
use Illuminate\Validation\Rule;

class FixedAssetController extends AppBaseController
{
    use GlobalPagination;

    public function __construct(
        private readonly DepreciationScheduleService $scheduleService,
        private readonly FixedAssetVoucherService $voucherService,
        private readonly FixedAssetDepreciationPostingService $postingService
    ) {
    }

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->to(CompanyAuthRedirect::url($request))->with('error', 'Please log in to access this page.');
        }

        if (!user_can('asset_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = FixedAsset::query()
            ->with(['category', 'branch', 'bike', 'depreciationSchedules'])
            ->orderByDesc('acquisition_date')
            ->orderByDesc('id');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('asset_category_id')) {
            $query->where('asset_category_id', $request->asset_category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('quick_search')) {
            $term = trim((string) $request->quick_search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('asset_code', 'like', '%' . $term . '%')
                    ->orWhere('serial_number', 'like', '%' . $term . '%');
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', FixedAsset::STATUS_DRAFT)->count(),
            'active' => (clone $query)->where('status', FixedAsset::STATUS_ACTIVE)->count(),
            'fully_depreciated' => (clone $query)->where('status', FixedAsset::STATUS_FULLY_DEPRECIATED)->count(),
        ];

        $data = $this->applyPagination($query, $paginationParams);
        $categories = AssetCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        if ($request->ajax()) {
            return response()->json([
                'tableData' => view('fixed_assets.table', ['data' => $data])->render(),
                'paginationLinks' => method_exists($data, 'links')
                    ? $data->links('components.global-pagination')->render()
                    : '',
                'stats' => $stats,
            ]);
        }

        return view('fixed_assets.index', [
            'data' => $data,
            'stats' => $stats,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        if (!user_can('asset_create')) {
            abort(403, 'Unauthorized action.');
        }

        $categories = AssetCategory::query()->where('is_active', true)->with('assetAccount')->orderBy('name')->get();
        $creditAccounts = Accounts::dropdown(null);

        return view('fixed_assets.create', [
            'categories' => $categories,
            'creditAccounts' => $creditAccounts,
            'availableBikes' => Bikes::availableForFixedAssetSelect(),
            'depreciationMethods' => AssetCategory::depreciationMethods(),
            'depreciationFrequencies' => AssetCategory::depreciationFrequencies(),
        ]);
    }

    public function store(Request $request)
    {
        if (!user_can('asset_create')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $this->validateAssetRequest($request, forUpdate: false);

        $category = AssetCategory::findOrFail($validated['asset_category_id']);
        $this->resolveAssetIdentity($validated, $category);
        $acquisitionCost = (float) $validated['acquisition_cost'];
        $salvageValue = (float) $validated['salvage_value'];

        if ($error = $this->validateDepreciationAmounts($request, $acquisitionCost, $salvageValue, $validated)) {
            return $error;
        }

        if ($validated['acquisition_type'] !== FixedAsset::ACQUISITION_OPENING_BALANCE
            && $validated['status'] === FixedAsset::STATUS_ACTIVE
            && empty($validated['acquisition_posting'])) {
            return $this->validationErrorResponse($request, 'Please indicate whether the acquisition is already posted or should be posted now.');
        }

        try {
            DB::beginTransaction();

            $asset = new FixedAsset();
            $asset->asset_category_id = $category->id;
            $asset->bike_id = $validated['bike_id'] ?? null;
            $asset->name = $validated['name'];
            $asset->asset_code = $validated['asset_code'] ?? null;
            $asset->description = $validated['description'] ?? null;
            $asset->serial_number = $validated['serial_number'] ?? null;
            $asset->branch_id = $validated['branch_id'];
            $asset->acquisition_date = $validated['acquisition_date'];
            $asset->in_service_date = $validated['in_service_date'];
            $asset->acquisition_type = $validated['acquisition_type'];
            $asset->opening_accumulated_depreciation = $validated['opening_accumulated_depreciation'];
            $asset->depreciation_as_of_date = $validated['depreciation_as_of_date'];
            $asset->past_depreciation_handling = $validated['past_depreciation_handling'] ?? null;
            $asset->acquisition_cost = $acquisitionCost;
            $asset->salvage_value = $salvageValue;
            $asset->depreciation_method = $validated['depreciation_method'];
            $asset->depreciation_frequency = $validated['depreciation_frequency'];
            $asset->useful_life_months = (int) $validated['useful_life_months'];
            $asset->notes = $validated['notes'] ?? null;
            $asset->status = $validated['status'];
            $asset->created_by = auth()->id();

            $this->scheduleService->applyCategoryDefaults($asset, $category, $acquisitionCost);
            $asset->salvage_value = $salvageValue;
            $asset->depreciation_method = $validated['depreciation_method'];
            $asset->depreciation_frequency = $validated['depreciation_frequency'];
            $asset->useful_life_months = (int) $validated['useful_life_months'];

            $asset->save();

            if (!$asset->asset_code) {
                $asset->asset_code = 'FA' . str_pad((string) $asset->id, 5, '0', STR_PAD_LEFT);
                $asset->save();
            }

            $this->applyAcquisitionPosting($asset, $validated);
            $asset->save();

            $this->scheduleService->regenerate($asset);

            if ($asset->canPostDepreciation()) {
                $this->postingService->postDueForAsset($asset);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Fixed asset created successfully.', 'reload' => true], 200);
            }

            Flash::success('Fixed asset created successfully.');
            return redirect()->route('fixed-assets.index');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
            }

            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show(string $company_slug, int $id)
    {
        if (!user_can('asset_view')) {
            abort(403, 'Unauthorized action.');
        }

        $asset = FixedAsset::with(['category', 'branch', 'depreciationSchedules', 'bike'])->findOrFail($id);

        return view('fixed_assets.show', compact('asset'));
    }

    public function edit(string $company_slug, int $id)
    {
        if (!user_can('asset_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $asset = FixedAsset::with(['category', 'assetAccount', 'bike'])->findOrFail($id);
        $categories = AssetCategory::query()->where('is_active', true)->with('assetAccount')->orderBy('name')->get();
        $creditAccounts = Accounts::dropdown(null);

        return view('fixed_assets.edit', [
            'asset' => $asset,
            'categories' => $categories,
            'creditAccounts' => $creditAccounts,
            'availableBikes' => Bikes::availableForFixedAssetSelect($asset->id),
            'depreciationMethods' => AssetCategory::depreciationMethods(),
            'depreciationFrequencies' => AssetCategory::depreciationFrequencies(),
        ]);
    }

    public function update(Request $request, string $company_slug, int $id)
    {
        if (!user_can('asset_edit')) {
            abort(403, 'Unauthorized action.');
        }

        $asset = FixedAsset::findOrFail($id);

        $validated = $this->validateAssetRequest($request, forUpdate: true);

        $category = AssetCategory::findOrFail($validated['asset_category_id']);
        $this->resolveAssetIdentity($validated, $category, $asset->id);

        $salvageValue = (float) $validated['salvage_value'];

        if ($error = $this->validateDepreciationAmounts($request, (float) $validated['acquisition_cost'], $salvageValue, $validated)) {
            return $error;
        }

        $wasDraft = $asset->isDraft();
        $activating = $wasDraft && ($validated['status'] ?? '') === FixedAsset::STATUS_ACTIVE;

        if ($activating
            && !$asset->isAcquisitionPosted()
            && empty($validated['acquisition_posting'])
            && ($validated['acquisition_type'] ?? '') !== FixedAsset::ACQUISITION_OPENING_BALANCE) {
            return $this->validationErrorResponse($request, 'Please indicate whether the acquisition is already posted or should be posted now.');
        }

        $depreciationChanged = $asset->acquisition_date->toDateString() !== $validated['acquisition_date']
            || ($asset->in_service_date?->toDateString() ?? '') !== $validated['in_service_date']
            || ($asset->acquisition_type ?? FixedAsset::ACQUISITION_NEW_PURCHASE) !== $validated['acquisition_type']
            || (int) ($asset->bike_id ?? 0) !== (int) ($validated['bike_id'] ?? 0)
            || (float) $asset->opening_accumulated_depreciation !== (float) $validated['opening_accumulated_depreciation']
            || ($asset->depreciation_as_of_date?->toDateString() ?? '') !== ($validated['depreciation_as_of_date'] ?? '')
            || ($asset->past_depreciation_handling ?? null) !== ($validated['past_depreciation_handling'] ?? null)
            || (float) $asset->acquisition_cost !== (float) $validated['acquisition_cost']
            || (float) $asset->salvage_value !== $salvageValue
            || $asset->depreciation_method !== $validated['depreciation_method']
            || ($asset->depreciation_frequency ?? 'monthly') !== $validated['depreciation_frequency']
            || (int) $asset->useful_life_months !== (int) $validated['useful_life_months'];

        try {
            DB::beginTransaction();

            $category = AssetCategory::findOrFail($validated['asset_category_id']);
            $previousCategoryId = $asset->asset_category_id;

            $asset->asset_category_id = $category->id;
            $asset->bike_id = $validated['bike_id'] ?? null;
            $asset->name = $validated['name'];
            $asset->asset_code = $validated['asset_code'] ?? $asset->asset_code;
            $asset->description = $validated['description'] ?? null;
            $asset->serial_number = $validated['serial_number'] ?? null;
            $asset->branch_id = $validated['branch_id'];
            $asset->acquisition_date = $validated['acquisition_date'];
            $asset->in_service_date = $validated['in_service_date'];
            $asset->acquisition_type = $validated['acquisition_type'];
            $asset->opening_accumulated_depreciation = $validated['opening_accumulated_depreciation'];
            $asset->depreciation_as_of_date = $validated['depreciation_as_of_date'];
            $asset->past_depreciation_handling = $validated['past_depreciation_handling'] ?? null;
            $asset->acquisition_cost = $validated['acquisition_cost'];
            $asset->salvage_value = $salvageValue;
            $asset->depreciation_method = $validated['depreciation_method'];
            $asset->depreciation_frequency = $validated['depreciation_frequency'];
            $asset->useful_life_months = (int) $validated['useful_life_months'];
            $asset->status = $validated['status'];
            $asset->notes = $validated['notes'] ?? null;
            $asset->updated_by = auth()->id();

            if ($previousCategoryId !== $category->id || !$asset->asset_account_id) {
                $this->scheduleService->applyCategoryDefaults($asset, $category, (float) $validated['acquisition_cost']);
                $asset->salvage_value = $salvageValue;
                $asset->depreciation_method = $validated['depreciation_method'];
                $asset->depreciation_frequency = $validated['depreciation_frequency'];
                $asset->useful_life_months = (int) $validated['useful_life_months'];
            }

            if (!$asset->isAcquisitionPosted()) {
                $shouldPostAcquisition = $activating
                    || $asset->isOpeningBalance()
                    || ($validated['acquisition_type'] ?? '') === FixedAsset::ACQUISITION_OPENING_BALANCE;

                if ($shouldPostAcquisition) {
                    $this->applyAcquisitionPosting($asset, $validated);
                }
            }

            $asset->save();

            if ($depreciationChanged && $asset->status !== FixedAsset::STATUS_DISPOSED) {
                $this->scheduleService->regenerate($asset);
            }

            if ($asset->canPostDepreciation()) {
                $this->postingService->postDueForAsset($asset);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Fixed asset updated successfully.', 'reload' => true], 200);
            }

            Flash::success('Fixed asset updated successfully.');
            return redirect()->route('fixed-assets.index');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
            }

            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, string $company_slug, int $id)
    {
        if (!user_can('asset_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $asset = FixedAsset::findOrFail($id);
        $asset->deleted_by = auth()->id();
        $asset->save();
        $asset->delete();

        if ($request->ajax()) {
            return response()->json(['message' => 'Fixed asset deleted successfully.', 'reload' => true]);
        }

        Flash::success('Fixed asset deleted successfully.');
        return redirect()->route('fixed-assets.index');
    }

    public function categoryDefaults(Request $request, string $company_slug, int $categoryId)
    {
        if (!user_can('asset_view')) {
            abort(403, 'Unauthorized action.');
        }

        $category = AssetCategory::with('assetAccount')->findOrFail($categoryId);
        $cost = (float) $request->query('acquisition_cost', 0);
        $assetAccount = $category->assetAccount;

        return response()->json([
            'depreciation_method' => $category->depreciation_method,
            'depreciation_frequency' => $category->depreciation_frequency,
            'useful_life_months' => $category->useful_life_months,
            'salvage_value' => $category->salvageValueForCost($cost),
            'salvage_value_percent' => $category->salvage_value_percent,
            'asset_account_id' => $category->asset_account_id,
            'asset_account_name' => $assetAccount?->name ?? $category->name,
        ]);
    }

    private function validationErrorResponse(Request $request, string $message)
    {
        if ($request->ajax()) {
            return response()->json(['message' => $message], 422);
        }

        Flash::error($message);
        return redirect()->back()->withInput();
    }

    private function validateAssetRequest(Request $request, bool $forUpdate): array
    {
        $methodKeys = implode(',', array_keys(AssetCategory::depreciationMethods()));

        $rules = [
            'asset_category_id' => 'required|exists:asset_categories,id',
            'bike_id' => 'nullable|exists:bikes,id',
            'name' => 'nullable|string|max:255',
            'asset_code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'serial_number' => 'nullable|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'acquisition_date' => 'required|date',
            'in_service_date' => 'required|date',
            'acquisition_type' => 'required|string|in:new_purchase,opening_balance,donation',
            'opening_accumulated_depreciation' => 'nullable|numeric|min:0',
            'depreciation_as_of_date' => 'nullable|date',
            'past_depreciation_handling' => 'nullable|string|in:backdated_entries,catch_up_entry,current_period',
            'acquisition_cost' => 'required|numeric|min:0.01',
            'salvage_value' => 'nullable|numeric|min:0',
            'depreciation_method' => 'required|string|in:' . $methodKeys,
            'depreciation_frequency' => 'required|string|in:monthly,yearly',
            'useful_life_months' => 'required|integer|min:1|max:600',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($forUpdate) {
            $rules['status'] = 'required|string|in:draft,active,fully_depreciated,disposed';
        } else {
            $rules['status'] = 'required|string|in:draft,active';
        }

        $needsAcquisitionVoucher = fn () => $request->input('status') === FixedAsset::STATUS_ACTIVE
            && $request->input('acquisition_posting') === 'post_now';

        $rules['acquisition_posting'] = ['nullable', Rule::in(['already_posted', 'post_now'])];
        $rules['voucher_trans_date'] = ['nullable', Rule::requiredIf($needsAcquisitionVoucher), 'date'];
        $rules['voucher_billing_month'] = ['nullable', Rule::requiredIf($needsAcquisitionVoucher), 'date_format:Y-m'];
        $rules['voucher_reference_number'] = ['nullable', Rule::requiredIf($needsAcquisitionVoucher), 'string', 'max:255'];
        $rules['acquisition_credit_account_id'] = ['nullable', Rule::requiredIf($needsAcquisitionVoucher), 'exists:accounts,id'];

        $validated = $request->validate($rules);

        $category = AssetCategory::findOrFail($validated['asset_category_id']);
        $validated['salvage_value'] = array_key_exists('salvage_value', $validated) && $validated['salvage_value'] !== null
            ? (float) $validated['salvage_value']
            : $category->salvageValueForCost((float) $validated['acquisition_cost']);

        if ($validated['acquisition_type'] === FixedAsset::ACQUISITION_NEW_PURCHASE
            || $validated['acquisition_type'] === FixedAsset::ACQUISITION_DONATION) {
            $validated['opening_accumulated_depreciation'] = 0;
            $validated['depreciation_as_of_date'] = null;

            $lastMonthStart = FixedAsset::lastMonthStartDate()->toDateString();
            $needsPastHandling = $validated['in_service_date'] < $lastMonthStart;

            if ($needsPastHandling) {
                if (empty($validated['past_depreciation_handling'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'past_depreciation_handling' => 'Please select how to handle past depreciation entries.',
                    ]);
                }
            } else {
                $validated['past_depreciation_handling'] = null;
            }

            if ($validated['in_service_date'] < $validated['acquisition_date']) {
                $message = $validated['acquisition_type'] === FixedAsset::ACQUISITION_DONATION
                    ? 'In-service date must be on or after the donation receipt date.'
                    : 'In-service date must be on or after acquisition date for new purchases.';

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'in_service_date' => $message,
                ]);
            }
        } else {
            $validated['opening_accumulated_depreciation'] = (float) ($validated['opening_accumulated_depreciation'] ?? 0);
            $validated['depreciation_as_of_date'] = $validated['depreciation_as_of_date'] ?? $validated['acquisition_date'];
            $validated['past_depreciation_handling'] = null;

            if (!$forUpdate) {
                $validated['status'] = FixedAsset::STATUS_ACTIVE;
            } elseif (($validated['status'] ?? '') === FixedAsset::STATUS_DRAFT) {
                $validated['status'] = FixedAsset::STATUS_ACTIVE;
            }

            if ($validated['depreciation_as_of_date'] < $validated['in_service_date']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'depreciation_as_of_date' => 'Depreciation as-of date must be on or after in-service date.',
                ]);
            }
        }

        return $validated;
    }

    private function resolveAssetIdentity(array &$validated, AssetCategory $category, ?int $existingAssetId = null): void
    {
        if ($category->isVehicles()) {
            if (empty($validated['bike_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bike_id' => 'Please select a vehicle for the Vehicles category.',
                ]);
            }

            $bike = Bikes::findOrFail($validated['bike_id']);

            $alreadyLinked = FixedAsset::query()
                ->where('bike_id', $bike->id)
                ->when($existingAssetId, fn ($query) => $query->where('id', '!=', $existingAssetId))
                ->exists();

            if ($alreadyLinked) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bike_id' => 'This bike is already linked to another fixed asset.',
                ]);
            }

            $validated['name'] = $bike->emiratesPlateLabel();
            $validated['bike_id'] = $bike->id;
            $validated['serial_number'] = $bike->chassis_number;

            if (!empty($bike->branch_id)) {
                $validated['branch_id'] = $bike->branch_id;
            }

            return;
        }

        $validated['bike_id'] = null;

        if (empty(trim((string) ($validated['name'] ?? '')))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'Asset name is required.',
            ]);
        }
    }

    private function validateDepreciationAmounts(Request $request, float $cost, float $salvageValue, array $validated)
    {
        if ($salvageValue >= $cost) {
            return $this->validationErrorResponse($request, 'Salvage value must be less than acquisition cost.');
        }

        $maxOpening = $cost - $salvageValue;
        if ((float) $validated['opening_accumulated_depreciation'] > $maxOpening) {
            return $this->validationErrorResponse(
                $request,
                'Opening accumulated depreciation cannot exceed acquisition cost minus salvage value.'
            );
        }

        return null;
    }

    private function applyAcquisitionPosting(FixedAsset $asset, array $validated): void
    {
        if ($asset->isOpeningBalance()) {
            if (!$asset->isAcquisitionPosted()) {
                $voucher = $this->voucherService->createOpeningBalanceAcquisitionVoucher($asset);
                $asset->acquisition_posting = FixedAsset::ACQUISITION_POSTING_POSTED;
                $asset->acquisition_voucher_id = $voucher->id;
            }

            return;
        }

        if (($validated['status'] ?? '') !== FixedAsset::STATUS_ACTIVE) {
            $asset->acquisition_posting = null;
            $asset->acquisition_voucher_id = null;

            return;
        }

        if (($validated['acquisition_posting'] ?? '') === 'post_now') {
            $voucher = $this->voucherService->createAcquisitionVoucher($asset, [
                'trans_date' => $validated['voucher_trans_date'],
                'billing_month' => $validated['voucher_billing_month'],
                'reference_number' => $validated['voucher_reference_number'],
                'credit_account_id' => (int) $validated['acquisition_credit_account_id'],
            ]);

            $asset->acquisition_posting = FixedAsset::ACQUISITION_POSTING_POSTED;
            $asset->acquisition_voucher_id = $voucher->id;

            return;
        }

        $asset->acquisition_posting = FixedAsset::ACQUISITION_POSTING_ALREADY_POSTED;
    }
}

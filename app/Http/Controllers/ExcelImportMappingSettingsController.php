<?php

namespace App\Http\Controllers;

use App\Services\ExcelImport\ExcelImportMappingService;
use App\Support\ExcelImport\ExcelImportMappingRegistry;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExcelImportMappingSettingsController extends Controller
{
    public function __construct(
        private readonly ExcelImportMappingService $mappingService
    ) {
        $this->middleware('auth');
    }

    protected function authorizeModule(string $moduleKey): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized action.');
        }

        $profile = $this->mappingService->profile($moduleKey);
        if ($user->can('gn_settings')) {
            return;
        }

        if ($profile->permission && $user->can($profile->permission)) {
            return;
        }

        abort(403, 'Unauthorized action.');
    }

    public function index(Request $request, $company_slug, string $module)
    {
        if (! ExcelImportMappingRegistry::has($module)) {
            abort(404);
        }

        $this->authorizeModule($module);
        $profile = $this->mappingService->profile($module);
        $scopes = $this->mappingService->scopeOptions($profile);

        $selectedScopeId = (int) ($request->get('scope_id') ?: 0);
        if ($profile->hasScope()) {
            if ($scopes->isEmpty()) {
                $selectedScopeId = 0;
            } elseif (! $scopes->contains('id', $selectedScopeId)) {
                $selectedScopeId = (int) $scopes->first()->id;
            }
        } else {
            $selectedScopeId = 0;
        }

        $resolved = $selectedScopeId > 0 || ! $profile->hasScope()
            ? $this->mappingService->resolve($module, $selectedScopeId ?: null)
            : [
                'configured' => false,
                'header_rows_to_skip' => $profile->defaultHeaderRowsToSkip,
                'column_mappings' => [],
                'dynamic_mappings' => [],
                'options' => [],
            ];

        $configuredScopeIds = [];
        if ($profile->hasScope()) {
            foreach ($scopes as $scope) {
                $payload = $this->mappingService->resolve($module, (int) $scope->id);
                if ($payload['configured']) {
                    $configuredScopeIds[] = (int) $scope->id;
                }
            }
        }

        return view('settings.excel_import_mappings.index', [
            'moduleKey' => $module,
            'profile' => $profile,
            'scopes' => $scopes,
            'selectedScopeId' => $selectedScopeId,
            'resolved' => $resolved,
            'configuredScopeIds' => $configuredScopeIds,
            'dynamicItems' => $this->mappingService->dynamicItemOptions($profile),
            'excelColumnChoices' => $this->mappingService->excelColumnChoices($profile->maxColumnIndex),
            'companySlug' => $company_slug,
        ]);
    }

    public function store(Request $request, $company_slug, string $module)
    {
        if (! ExcelImportMappingRegistry::has($module)) {
            abort(404);
        }

        $this->authorizeModule($module);
        $profile = $this->mappingService->profile($module);

        $rules = [
            'scope_id' => $profile->hasScope() ? 'required|integer' : 'nullable',
            'header_rows_to_skip' => 'nullable|integer|min:0|max:20',
            'column_mappings' => 'required|array',
            'dynamic_mappings' => 'sometimes|array',
            'dynamic_mappings.*.item_id' => 'nullable|integer',
            'dynamic_mappings.*.col' => 'nullable|integer|min:1',
            'dynamic_mappings.*.rate' => 'nullable|numeric',
            'is_active' => 'sometimes|boolean',
        ];

        foreach ($profile->fields as $field) {
            $key = $field['key'];
            $rules["column_mappings.{$key}"] = ! empty($field['required'])
                ? 'required|integer|min:1'
                : 'nullable|integer|min:1';
        }

        $validated = $request->validate($rules);
        $scopeId = $profile->hasScope() ? (int) $validated['scope_id'] : null;

        try {
            $this->mappingService->save($module, $validated, $scopeId);
        } catch (ValidationException $e) {
            throw $e;
        }

        Flash::success($profile->label . ' mappings saved.');

        if ($request->input('return_to') === 'module_settings') {
            return redirect()->route('settings-panel.module-settings.index', [
                'company_slug' => $company_slug,
                'module' => $module,
                'tab' => 'import-mappings',
                'scope_id' => $scopeId,
            ]);
        }

        return redirect()->route('settings-panel.excel-import-mappings.index', [
            'company_slug' => $company_slug,
            'module' => $module,
            'scope_id' => $scopeId,
        ]);
    }
}

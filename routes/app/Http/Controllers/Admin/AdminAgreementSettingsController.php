<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAgreementAssignableModule;
use App\Models\AdminAgreementPlaceholder;
use App\Models\Settings;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Agreements\AgreementPlaceholderCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAgreementSettingsController extends Controller
{
    public function index(Request $request, AgreementPlaceholderCatalog $catalog, AgreementModuleService $modules): View
    {
        $this->authorizeView();

        $erpLabels = config('erp_modules.modules', []);
        $candidateKeys = $modules->configAssignableModuleKeys();
        $saved = AdminAgreementAssignableModule::query()->get()->keyBy('module_key');

        $assignableModules = collect($candidateKeys)->map(function (string $key) use ($saved, $erpLabels) {
            $row = $saved->get($key);

            return [
                'module_key' => $key,
                'label' => Settings::getMenuLabel($key) ?: ($erpLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))),
                'enabled' => $row ? (bool) $row->enabled : true,
                'sort_order' => $row ? (int) $row->sort_order : 0,
            ];
        })->values();

        $selectedModule = (string) $request->get('module', $candidateKeys[0] ?? 'riders');
        if (! in_array($selectedModule, $candidateKeys, true) && $selectedModule !== 'system') {
            $selectedModule = $candidateKeys[0] ?? 'system';
        }

        $placeholders = AdminAgreementPlaceholder::query()
            ->where('module_key', $selectedModule)
            ->orderBy('sort_order')
            ->get();

        $sourceOptionGroups = $catalog->sourceFieldOptionGroups($selectedModule);
        $groupLabels = $catalog->groupLabels();
        $moduleOptions = collect($candidateKeys)
            ->mapWithKeys(fn (string $key) => [
                $key => Settings::getMenuLabel($key) ?: ($erpLabels[$key] ?? $key),
            ])
            ->put('system', 'System (shared)')
            ->all();

        return view('admin.agreement_settings.index', compact(
            'assignableModules',
            'placeholders',
            'selectedModule',
            'sourceOptionGroups',
            'groupLabels',
            'moduleOptions'
        ));
    }

    public function updateModules(Request $request): RedirectResponse
    {
        $this->authorizeEdit();

        $candidateKeys = app(AgreementModuleService::class)->configAssignableModuleKeys();
        $data = $request->validate([
            'modules' => 'nullable|array',
            'modules.*' => ['string', Rule::in($candidateKeys)],
        ]);

        $enabled = array_values(array_unique($data['modules'] ?? []));
        $now = now();

        DB::connection('mysql_admin')->transaction(function () use ($candidateKeys, $enabled, $now) {
            foreach ($candidateKeys as $i => $key) {
                AdminAgreementAssignableModule::query()->updateOrCreate(
                    ['module_key' => $key],
                    [
                        'enabled' => in_array($key, $enabled, true),
                        'sort_order' => $i + 1,
                        'updated_at' => $now,
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.agreement-settings.index')
            ->with('success', __('Assignable modules saved.'));
    }

    public function storePlaceholder(Request $request): RedirectResponse
    {
        $this->authorizeEdit();

        $request->merge([
            'placeholder' => $this->normalizePlaceholderToken($request->input('placeholder')),
        ]);

        $catalog = app(AgreementPlaceholderCatalog::class);
        $candidateKeys = array_merge(app(AgreementModuleService::class)->configAssignableModuleKeys(), ['system']);
        $groupLabels = $catalog->groupLabels();
        $allowedSources = array_keys($catalog->sourceFieldOptions((string) $request->input('module_key', 'system')));

        $data = $request->validate([
            'module_key' => ['required', 'string', Rule::in($candidateKeys)],
            'placeholder' => [
                'required',
                'string',
                'max:80',
                'regex:/^\{[a-z0-9_]+\}$/i',
                Rule::unique(AdminAgreementPlaceholder::class, 'placeholder')
                    ->where(fn ($q) => $q->where('module_key', $request->input('module_key'))),
            ],
            'description' => 'nullable|string|max:255',
            'group_label' => ['required', 'string', Rule::in($groupLabels)],
            'source_key' => ['required', 'string', 'max:120', Rule::in($allowedSources)],
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        AdminAgreementPlaceholder::query()->create([
            'module_key' => $data['module_key'],
            'placeholder' => $data['placeholder'],
            'description' => $data['description'] ?? null,
            'group_label' => $data['group_label'],
            'source_key' => $data['source_key'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.agreement-settings.index', ['module' => $data['module_key']])
            ->with('success', __('Placeholder added.'));
    }

    public function updatePlaceholder(Request $request, AdminAgreementPlaceholder $placeholder): RedirectResponse
    {
        $this->authorizeEdit();

        $request->merge([
            'placeholder' => $this->normalizePlaceholderToken($request->input('placeholder')),
        ]);

        $catalog = app(AgreementPlaceholderCatalog::class);
        $candidateKeys = array_merge(app(AgreementModuleService::class)->configAssignableModuleKeys(), ['system']);
        $groupLabels = $catalog->groupLabels();
        $allowedSources = array_keys($catalog->sourceFieldOptions((string) $request->input('module_key', $placeholder->module_key)));
        if ($placeholder->source_key) {
            $allowedSources[] = (string) $placeholder->source_key;
            $allowedSources = array_values(array_unique($allowedSources));
        }

        $data = $request->validate([
            'module_key' => ['required', 'string', Rule::in($candidateKeys)],
            'placeholder' => [
                'required',
                'string',
                'max:80',
                'regex:/^\{[a-z0-9_]+\}$/i',
                Rule::unique(AdminAgreementPlaceholder::class, 'placeholder')
                    ->where(fn ($q) => $q->where('module_key', $request->input('module_key')))
                    ->ignore($placeholder->id),
            ],
            'description' => 'nullable|string|max:255',
            'group_label' => ['required', 'string', Rule::in($groupLabels)],
            'source_key' => ['required', 'string', 'max:120', Rule::in($allowedSources)],
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $placeholder->update([
            'module_key' => $data['module_key'],
            'placeholder' => $data['placeholder'],
            'description' => $data['description'] ?? null,
            'group_label' => $data['group_label'],
            'source_key' => $data['source_key'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.agreement-settings.index', ['module' => $data['module_key']])
            ->with('success', __('Placeholder updated.'));
    }

    /**
     * Ensure placeholder tokens are stored as {token}; wrap braces when omitted.
     */
    private function normalizePlaceholderToken(mixed $token): string
    {
        $inner = trim((string) $token);
        $inner = trim($inner, "{} \t\n\r\0\x0B");

        return $inner === '' ? '' : '{'.$inner.'}';
    }

    public function destroyPlaceholder(AdminAgreementPlaceholder $placeholder): RedirectResponse
    {
        $this->authorizeEdit();

        $module = $placeholder->module_key;
        $placeholder->delete();

        return redirect()
            ->route('admin.agreement-settings.index', ['module' => $module])
            ->with('success', __('Placeholder deleted.'));
    }

    private function authorizeView(): void
    {
        $admin = auth('admin')->user();
        abort_unless(
            $admin && ($admin->hasPermission('agreement_settings_view') || $admin->hasPermission('agreement_settings_edit') || $admin->hasRole('Super Admin')),
            403
        );
    }

    private function authorizeEdit(): void
    {
        $admin = auth('admin')->user();
        abort_unless(
            $admin && ($admin->hasPermission('agreement_settings_edit') || $admin->hasRole('Super Admin')),
            403
        );
    }
}

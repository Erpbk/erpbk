<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Models\AgreementPlaceholder;
use App\Models\AgreementTemplate;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Agreements\AgreementLetterheadLayout;
use App\Services\Agreements\AgreementPdfBranding;
use App\Services\Agreements\AgreementPdfService;
use App\Support\CompanyContext;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laracasts\Flash\Flash;
use Illuminate\Validation\Rule;

class AgreementSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, $company_slug)
    {
        $this->authorizeAgreement('agreement_view');

        AgreementCategory::ensureDefaultsForCompany();

        $groupKey = $request->get('group', 'rider_agreements');
        $groups = config('agreement_categories.groups', []);

        $categories = AgreementCategory::query()
            ->where('group_key', $groupKey)
            ->orderBy('sort_order')
            ->with('defaultTemplate')
            ->get();

        $modules = $this->moduleOptions();

        return view('settings.agreements.index', compact('categories', 'groupKey', 'groups', 'modules'));
    }

    public function createAgreement(Request $request, $company_slug)
    {
        $this->authorizeAgreement('agreement_create');

        $groupKey = (string) $request->get('group', 'rider_agreements');
        $groups = config('agreement_categories.groups', []);
        $modules = $this->moduleOptions();

        return view('settings.agreements.create', compact('groupKey', 'groups', 'modules'));
    }

    public function storeAgreement(Request $request, $company_slug)
    {
        $this->authorizeAgreement('agreement_create');

        $companyId = CompanyContext::id();
        $assignableModuleKeys = $this->assignableModuleKeys();

        $data = $request->validate([
            'agreement_name' => 'required|string|max:191',
            'agreement_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('agreement_categories', 'agreement_code')->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                }),
            ],
            'description' => 'nullable|string',
            'assigned_modules' => 'required|array|min:1',
            'assigned_modules.*' => ['string', Rule::in($assignableModuleKeys)],
            'status' => 'sometimes|boolean',
            'group_key' => 'nullable|string|max:80',
        ], [
            'assigned_modules.required' => 'Select at least one module for this agreement.',
            'assigned_modules.min' => 'Select at least one module for this agreement.',
            'assigned_modules.*.in' => 'One or more selected modules are not valid for agreements.',
        ]);

        $slug = Str::slug((string) $data['agreement_code'], '_');
        if ($slug === '') {
            $slug = Str::slug((string) $data['agreement_name'], '_');
        }

        $sortOrder = (int) AgreementCategory::query()->where('group_key', (string) ($data['group_key'] ?? 'rider_agreements'))->max('sort_order');
        $sortOrder = $sortOrder + 1;

        $category = AgreementCategory::create([
            'group_key' => (string) ($data['group_key'] ?? 'rider_agreements'),
            'slug' => $slug,
            'agreement_code' => (string) $data['agreement_code'],
            'name' => (string) $data['agreement_name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $sortOrder,
            'status' => $request->boolean('status', true),
            'assigned_modules' => $this->normalizeAssignedModules($data['assigned_modules']),
        ]);

        $this->seedAgreementTemplates($category, $category->status);

        Flash::success('Agreement created and assigned to: ' . $this->moduleAssignmentLabel($category->assigned_modules) . '. Corporate template is the default contract template — change it under Edit if needed.');

        return redirect()->route('agreements.edit-agreement', [
            'company_slug' => $company_slug,
            'category' => $category->id,
        ]);
    }

    public function editAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreement_edit');

        $category = AgreementCategory::with(['templates' => fn($q) => $q->sampleStyles()->where('status', true)->orderBy('template_name')])->findOrFail($category);
        $modules = $this->moduleOptions();
        $groups = config('agreement_categories.groups', []);
        $placeholders = AgreementPlaceholder::grouped();
        $pdfBranding = app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());

        $contractTemplateId = optional($category->contractTemplate())->id;
        $letterheadMargins = $category->resolvedLetterheadMarginsMm();

        return view('settings.agreements.edit', compact(
            'category',
            'modules',
            'groups',
            'contractTemplateId',
            'placeholders',
            'pdfBranding',
            'letterheadMargins'
        ));
    }

    public function showAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreement_view');

        $category = AgreementCategory::with(['defaultTemplate', 'templates' => fn($q) => $q->sampleStyles()])->findOrFail($category);
        $modules = $this->moduleOptions();
        $groups = config('agreement_categories.groups', []);

        return view('settings.agreements.show', compact('category', 'modules', 'groups'));
    }

    public function updateAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreement_edit');

        $category = AgreementCategory::with('templates')->findOrFail($category);
        $companyId = CompanyContext::id();
        $assignableModuleKeys = $this->assignableModuleKeys();

        $data = $request->validate([
            'agreement_name' => 'required|string|max:191',
            'agreement_code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('agreement_categories', 'agreement_code')
                    ->where(function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    })
                    ->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'assigned_modules' => 'required|array|min:1',
            'assigned_modules.*' => ['string', Rule::in($assignableModuleKeys)],
            'status' => 'sometimes|boolean',
            'contract_template_id' => [
                'required',
                'integer',
                Rule::exists('agreement_templates', 'id')->where(fn($q) => $q->where('category_id', $category->id)),
            ],
            'template_contents' => 'nullable|array',
            'template_contents.*' => 'nullable|string',
            'letterhead' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'remove_letterhead' => 'sometimes|boolean',
            'letterhead_margins' => 'nullable|array',
            'letterhead_margins.top' => 'nullable|numeric|min:10|max:100',
            'letterhead_margins.bottom' => 'nullable|numeric|min:10|max:100',
            'letterhead_margins.left' => 'nullable|numeric|min:8|max:50',
            'letterhead_margins.right' => 'nullable|numeric|min:8|max:50',
        ], [
            'assigned_modules.required' => 'Select at least one module for this agreement.',
            'assigned_modules.min' => 'Select at least one module for this agreement.',
            'assigned_modules.*.in' => 'One or more selected modules are not valid for agreements.',
        ]);

        $slug = Str::slug((string) $data['agreement_code'], '_');
        if ($slug === '') {
            $slug = Str::slug((string) $data['agreement_name'], '_');
        }

        $newStatus = $request->boolean('status', true);

        $category->fill([
            'agreement_code' => (string) $data['agreement_code'],
            'slug' => $slug,
            'name' => (string) $data['agreement_name'],
            'description' => $data['description'] ?? null,
            'status' => $newStatus,
            'assigned_modules' => $this->normalizeAssignedModules($data['assigned_modules']),
        ]);
        $category->save();

        // Keep templates status aligned with agreement status.
        AgreementTemplate::query()
            ->where('category_id', $category->id)
            ->update(['status' => $newStatus]);

        $this->assignContractTemplate($category, (int) $data['contract_template_id']);

        $this->saveTemplateContents($category, $data['template_contents'] ?? []);

        $this->saveLetterheadMargins($request, $category);
        $this->handleLetterheadUpload($request, $category);

        Flash::success('Agreement saved. Assigned to: ' . $this->moduleAssignmentLabel($category->assigned_modules) . '.');

        return redirect()->route('agreements.edit-agreement', [
            'company_slug' => $company_slug,
            'category' => $category->id,
        ]);
    }

    public function destroyAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreement_delete');

        $category = AgreementCategory::findOrFail($category);
        $groupKey = (string) $category->group_key;
        $this->deleteLetterheadFile($category);
        $category->delete();

        Flash::success('Agreement deleted.');

        return redirect()->route('agreements.index', [
            'company_slug' => $company_slug,
            'group' => $groupKey,
        ]);
    }

    public function toggleAgreementStatus(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreement_edit');

        $category = AgreementCategory::findOrFail($category);
        $category->status = ! $category->status;
        $category->save();

        AgreementTemplate::query()
            ->where('category_id', $category->id)
            ->update(['status' => $category->status]);

        Flash::success('Agreement status updated.');

        return redirect()->route('agreements.index', [
            'company_slug' => $company_slug,
            'group' => $category->group_key,
        ]);
    }

    public function templates(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreement_view');

        $category = AgreementCategory::findOrFail($category);
        $templates = AgreementTemplate::where('category_id', $category->id)
            ->orderByDesc('is_default')
            ->orderBy('template_name')
            ->get();

        $pdfBranding = app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());

        return view('settings.agreements.templates', compact('category', 'templates', 'pdfBranding'));
    }

    public function create($company_slug, $category)
    {
        $this->authorizeAgreement('agreement_create');

        $category = AgreementCategory::findOrFail($category);
        $placeholders = AgreementPlaceholder::grouped();

        return view('settings.agreements.editor', array_merge(
            $this->editorViewData($category, new AgreementTemplate([
                'template_type' => AgreementTemplate::TYPE_CORPORATE,
                'status' => true,
            ])),
            ['placeholders' => $placeholders]
        ));
    }

    public function edit($company_slug, $id)
    {
        $this->authorizeAgreement('agreement_edit');

        $template = AgreementTemplate::with('category')->findOrFail($id);
        $placeholders = AgreementPlaceholder::grouped();

        return view('settings.agreements.editor', array_merge(
            $this->editorViewData($template->category, $template),
            ['placeholders' => $placeholders]
        ));
    }

    public function store(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreement_create');

        $category = AgreementCategory::findOrFail($category);
        $data = $this->validatedTemplate($request);

        $template = AgreementTemplate::create(array_merge($data, [
            'category_id' => $category->id,
        ]));

        if ($request->boolean('is_default')) {
            $template->setAsDefault();
        }

        Flash::success('Agreement template created.');

        return redirect()->route('agreements.templates', [
            'company_slug' => $company_slug,
            'category' => $category->id,
        ]);
    }

    public function update(Request $request, $company_slug, $id)
    {
        $this->authorizeAgreement('agreement_edit');

        $template = AgreementTemplate::findOrFail($id);
        $data = $this->validatedTemplate($request);
        $template->fill($data);
        $template->save();

        if ($request->boolean('is_default')) {
            $template->setAsDefault();
        } elseif ($template->is_default && !$request->boolean('is_default')) {
            $template->is_default = false;
            $template->save();
        }

        Flash::success('Agreement template updated.');

        return redirect()->route('agreements.templates', [
            'company_slug' => $company_slug,
            'category' => $template->category_id,
        ]);
    }

    public function destroy(Request $request, $company_slug, $id)
    {
        $this->authorizeAgreement('agreement_delete');

        $template = AgreementTemplate::findOrFail($id);
        $categoryId = $template->category_id;
        $wasDefault = $template->is_default;
        $template->delete();

        if ($wasDefault) {
            $next = AgreementTemplate::where('category_id', $categoryId)->first();
            if ($next) {
                $next->setAsDefault();
            }
        }

        Flash::success('Template deleted.');

        return redirect()->route('agreements.templates', [
            'company_slug' => $company_slug,
            'category' => $categoryId,
        ]);
    }

    public function duplicate(Request $request, $company_slug, $id)
    {
        $this->authorizeAgreement('agreement_create');

        $template = AgreementTemplate::findOrFail($id);
        $name = $request->input('template_name', $template->template_name . ' (Copy)');
        $template->duplicate($name);

        Flash::success('Template duplicated.');

        return redirect()->route('agreements.templates', [
            'company_slug' => $company_slug,
            'category' => $template->category_id,
        ]);
    }

    public function setDefault(Request $request, $company_slug, $id)
    {
        $this->authorizeAgreement('agreement_manage_templates');

        $template = AgreementTemplate::findOrFail($id);
        $template->setAsDefault();

        Flash::success('Default template updated.');

        return redirect()->route('agreements.templates', [
            'company_slug' => $company_slug,
            'category' => $template->category_id,
        ]);
    }

    public function toggleStatus(Request $request, $company_slug, $id)
    {
        $this->authorizeAgreement('agreement_edit');

        $template = AgreementTemplate::findOrFail($id);
        $template->status = !$template->status;
        $template->save();

        return back()->with('success', 'Template status updated.');
    }

    public function preview(Request $request, $company_slug, $id, AgreementPdfService $pdfService)
    {
        $this->authorizeAgreement('agreement_view');

        $template = AgreementTemplate::findOrFail($id);
        $html = $pdfService->renderHtml($template, new \App\Models\Riders(), null, true);

        return view('agreements.preview', compact('html', 'template'));
    }

    public function previewPdf(Request $request, $company_slug, $id, AgreementPdfService $pdfService)
    {
        $this->authorizeAgreement('agreement_view');

        $template = AgreementTemplate::findOrFail($id);
        $pdf = $pdfService->previewPdf($template);
        $filename = Str::slug($template->template_name) . '-preview.pdf';

        return $pdf->download($filename);
    }

    private function validatedTemplate(Request $request): array
    {
        return $request->validate([
            'template_name' => 'required|string|max:191',
            'template_type' => 'required|in:corporate,premium',
            'description' => 'nullable|string',
            'status' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]) + [
            'status' => $request->boolean('status', true),
        ];
    }

    private function editorViewData(AgreementCategory $category, AgreementTemplate $template): array
    {
        $pdfBranding = app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());

        return [
            'category' => $category,
            'template' => $template,
            'pdfBranding' => $pdfBranding,
        ];
    }

    private function authorizeAgreement(string $permission): void
    {
        if (!Gate::allows($permission) && !Gate::allows('gn_settings')) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * @return array<string, string>
     */
    /**
     * Module keys that may be assigned to agreements (all ERP modules except system entries).
     *
     * @return list<string>
     */
    private function assignableModuleKeys(): array
    {
        return app(AgreementModuleService::class)->assignableModuleKeys();
    }

    /**
     * @return array<string, string>
     */
    private function moduleOptions(): array
    {
        $erpLabels = config('erp_modules.modules', []);

        return collect($this->assignableModuleKeys())
            ->mapWithKeys(fn(string $moduleKey) => [
                $moduleKey => Settings::getMenuLabel($moduleKey) ?: ($erpLabels[$moduleKey] ?? ucfirst(str_replace('_', ' ', $moduleKey))),
            ])
            ->all();
    }

    /**
     * @param  array<int, string>|null  $moduleKeys
     */
    private function moduleAssignmentLabel(?array $moduleKeys): string
    {
        $keys = $this->normalizeAssignedModules($moduleKeys ?? []);
        if ($keys === []) {
            return 'none';
        }

        $labels = $this->moduleOptions();

        return collect($keys)
            ->map(fn(string $key) => $labels[$key] ?? $key)
            ->implode(', ');
    }

    /**
     * @param  array<int, string>  $modules
     * @return list<string>
     */
    private function normalizeAssignedModules(array $modules): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn($key) => is_string($key) ? trim($key) : '',
            $modules
        ))));
    }

    private function assignContractTemplate(AgreementCategory $category, int $templateId): void
    {
        $template = AgreementTemplate::query()
            ->where('category_id', $category->id)
            ->sampleStyles()
            ->where('status', true)
            ->findOrFail($templateId);

        $template->setAsDefault();
    }

    /**
     * @param  array<int|string, string|null>  $contents
     */
    private function saveTemplateContents(AgreementCategory $category, array $contents): void
    {
        if ($contents === []) {
            return;
        }

        $validIds = AgreementTemplate::query()
            ->where('category_id', $category->id)
            ->sampleStyles()
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        foreach ($contents as $templateId => $content) {
            $templateId = (int) $templateId;
            if (! in_array($templateId, $validIds, true)) {
                continue;
            }

            AgreementTemplate::query()
                ->where('category_id', $category->id)
                ->whereKey($templateId)
                ->update(['description' => $content ?? '']);
        }
    }

    private function handleLetterheadUpload(Request $request, AgreementCategory $category): void
    {
        if ($request->boolean('remove_letterhead')) {
            $this->deleteLetterheadFile($category);
            $category->letterhead_path = null;
            $category->letterhead_margins = null;
            $category->save();

            return;
        }

        if (! $request->hasFile('letterhead')) {
            return;
        }

        $this->deleteLetterheadFile($category);

        $path = $request->file('letterhead')->store(
            'agreement-letterheads/' . (int) $category->company_id,
            'public'
        );

        $fullPath = storage_path('app/public/' . ltrim($path, '/'));
        $layout = app(AgreementLetterheadLayout::class);

        $category->letterhead_path = $path;
        $category->letterhead_margins = $layout->suggestMarginsFromFilesystem(
            is_readable($fullPath) ? $fullPath : null
        );
        $category->save();
    }

    private function deleteLetterheadFile(AgreementCategory $category): void
    {
        if (! $category->hasLetterhead()) {
            return;
        }

        Storage::disk('public')->delete((string) $category->letterhead_path);
    }

    private function saveLetterheadMargins(Request $request, AgreementCategory $category): void
    {
        if (! $request->has('letterhead_margins')) {
            return;
        }

        $input = $request->input('letterhead_margins', []);
        if (! is_array($input)) {
            return;
        }

        $margins = [];
        foreach (['top', 'bottom', 'left', 'right'] as $side) {
            if (isset($input[$side]) && $input[$side] !== '') {
                $margins[$side] = (float) $input[$side];
            }
        }

        $category->letterhead_margins = $margins !== [] ? $margins : null;
        $category->save();
    }

    private function seedAgreementTemplates(AgreementCategory $category, bool $status): void
    {
        $companyId = CompanyContext::id();

        // Generic default content: editable via “Manage Templates”.
        // Uses the same placeholder tokens the system already supports.
        $seedContent = <<<HTML
<p><strong>Date:</strong> {agreement_date}</p>
<p>This agreement is made between <strong>{company_name}</strong> and <strong>{rider_name}</strong> (Rider ID: <strong>{rider_code}</strong>).</p>

<h3>Terms &amp; Conditions</h3>
<ol>
  <li>Both parties agree to comply with company policies and all applicable regulations.</li>
  <li>The agreement becomes effective from the signature date shown on this document.</li>
  <li>Any amendments must be documented in writing.</li>
</ol>

<p style="margin-top:14pt;"><strong>Signature Section</strong></p>
<p class="text-muted" style="margin-top:6pt;">(Company and recipient signatures appear automatically in the PDF layout.)</p>
HTML;

        AgreementTemplate::create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'template_name' => $category->name . ' (Corporate Professional)',
            'template_type' => AgreementTemplate::TYPE_CORPORATE,
            'description' => $seedContent,
            'is_default' => true,
            'status' => $status,
        ]);

        AgreementTemplate::create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'template_name' => $category->name . ' (Modern Premium)',
            'template_type' => AgreementTemplate::TYPE_PREMIUM,
            'description' => $seedContent,
            'is_default' => false,
            'status' => $status,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Models\AgreementLetterhead;
use App\Models\AgreementTemplate;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Agreements\AgreementFontSettings;
use App\Services\Agreements\AgreementLetterheadLayout;
use App\Services\Agreements\AgreementLetterheadPaginator;
use App\Services\Agreements\AgreementPdfBranding;
use App\Services\Agreements\AgreementPdfService;
use App\Services\Agreements\AgreementPlaceholderCatalog;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $this->authorizeAgreement('agreements_view');

        AgreementCategory::ensureDefaultsForCompany();

        $filters = [
            'module' => trim((string) $request->get('module', '')),
            'name' => trim((string) $request->get('name', '')),
            'status' => $request->get('status'),
        ];

        $query = AgreementCategory::query()->orderBy('sort_order')->orderBy('name');

        if ($filters['module'] !== '' && in_array($filters['module'], $this->assignableModuleKeys(), true)) {
            $query->assignedToModule($filters['module']);
        }

        if ($filters['name'] !== '') {
            $term = '%'.$filters['name'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('agreement_code', 'like', $term);
            });
        }

        if ($filters['status'] === '1' || $filters['status'] === '0') {
            $query->where('status', (bool) (int) $filters['status']);
        }

        $categories = $query->with('defaultTemplate')->get();
        $modules = $this->moduleOptions();

        return view('settings.agreements.index', compact('categories', 'modules', 'filters'));
    }

    public function createAgreement(Request $request, $company_slug)
    {
        $this->authorizeAgreement('agreements_create');

        $groupKey = (string) $request->get('group', 'rider_agreements');
        $groups = config('agreement_categories.groups', []);
        $modules = $this->moduleOptions();
        $assignModule = (string) $request->query('assign_module', '');
        if ($assignModule !== '' && ! in_array($assignModule, $this->assignableModuleKeys(), true)) {
            $assignModule = '';
        }

        return view('settings.agreements.create', compact('groupKey', 'groups', 'modules', 'assignModule'));
    }

    public function storeAgreement(Request $request, $company_slug)
    {
        $this->authorizeAgreement('agreements_create');

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
            'assigned_modules' => ['required', 'string', Rule::in($assignableModuleKeys)],
            'status' => 'sometimes|boolean',
            'group_key' => 'nullable|string|max:80',
        ], [
            'assigned_modules.required' => 'Select a module for this agreement.',
            'assigned_modules.in' => 'The selected module is not valid for agreements.',
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
            'assigned_modules' => $this->normalizeAssignedModules([$data['assigned_modules']]),
        ]);

        $this->seedAgreementTemplates($category, $category->status);

        Flash::success('Agreement created and assigned to: ' . $this->moduleAssignmentLabel($category->assigned_modules) . '.');

        $returnModule = (string) $request->input('return_module', '');
        if ($returnModule !== '' && in_array($returnModule, $this->assignableModuleKeys(), true)) {
            return redirect()->route('module-agreements.index', [
                'company_slug' => $company_slug,
                'module' => $returnModule,
            ]);
        }

        return redirect()->route('agreements.edit-agreement', [
            'company_slug' => $company_slug,
            'category' => $category->id,
        ]);
    }

    public function editAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_edit');

        $category = AgreementCategory::with([
            'letterhead',
            'watermark',
            'templates' => fn($q) => $q->where('status', true)->orderBy('template_name'),
        ])->findOrFail($category);
        $modules = $this->moduleOptions();
        $groups = config('agreement_categories.groups', []);
        $moduleKey = $category->normalizedAssignedModules()[0] ?? null;
        $placeholders = app(AgreementPlaceholderCatalog::class)->groupedForModule($moduleKey);
        $pdfBranding = app(AgreementPdfBranding::class)->forCompany(CompanyContext::id());
        $letterheads = AgreementLetterhead::query()->ofKind(AgreementLetterhead::KIND_LETTERHEAD)->orderBy('name')->get();
        $watermarks = AgreementLetterhead::query()->ofKind(AgreementLetterhead::KIND_WATERMARK)->orderBy('name')->get();

        $contractTemplateId = optional($category->contractTemplate())->id;
        $letterheadMargins = app(AgreementLetterheadLayout::class)->resolvedMarginsMm($category);

        return view('settings.agreements.edit', compact(
            'category',
            'modules',
            'groups',
            'contractTemplateId',
            'placeholders',
            'pdfBranding',
            'letterheadMargins',
            'letterheads',
            'watermarks'
        ));
    }

    public function showAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_view');

        $category = AgreementCategory::with(['defaultTemplate', 'templates', 'letterhead'])->findOrFail($category);
        $modules = $this->moduleOptions();
        $groups = config('agreement_categories.groups', []);

        return view('settings.agreements.show', compact('category', 'modules', 'groups'));
    }

    public function updateAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_edit');

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
            'assigned_modules' => ['required', 'string', Rule::in($assignableModuleKeys)],
            'status' => 'sometimes|boolean',
            'contract_template_id' => [
                'required',
                'integer',
                Rule::exists('agreement_templates', 'id')->where(fn($q) => $q->where('category_id', $category->id)),
            ],
            'template_contents' => 'nullable|array',
            'template_contents.*' => 'nullable|string',
            'letterhead_id' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($companyId) {
                if (in_array($value, ['none', 'default'], true)) {
                    return;
                }
                $exists = AgreementLetterhead::query()
                    ->ofKind(AgreementLetterhead::KIND_LETTERHEAD)
                    ->where('company_id', $companyId)
                    ->whereKey($value)
                    ->exists();
                if (! $exists) {
                    $fail('Select a valid letterhead.');
                }
            }],
            'watermark_id' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($companyId) {
                if (in_array($value, ['none', 'default'], true)) {
                    return;
                }
                $exists = AgreementLetterhead::query()
                    ->ofKind(AgreementLetterhead::KIND_WATERMARK)
                    ->where('company_id', $companyId)
                    ->whereKey($value)
                    ->exists();
                if (! $exists) {
                    $fail('Select a valid watermark.');
                }
            }],
            'letterhead_margins' => 'nullable|array',
            'letterhead_margins.top' => 'nullable|numeric|min:30|max:100',
            'letterhead_margins.bottom' => 'nullable|numeric|min:0|max:50',
            'letterhead_margins.left' => 'nullable|numeric|min:5|max:50',
            'letterhead_margins.right' => 'nullable|numeric|min:5|max:50',
            'letterhead_margins.page_size' => ['nullable', 'string', Rule::in(app(AgreementLetterheadLayout::class)->allowedPageSizeKeys())],
        ], [
            'assigned_modules.required' => 'Select a module for this agreement.',
            'assigned_modules.in' => 'The selected module is not valid for agreements.',
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
            'assigned_modules' => $this->normalizeAssignedModules([$data['assigned_modules']]),
        ]);
        $category->save();

        // Keep templates status aligned with agreement status.
        AgreementTemplate::query()
            ->where('category_id', $category->id)
            ->update(['status' => $newStatus]);

        $this->assignContractTemplate($category, (int) $data['contract_template_id']);

        $this->saveTemplateContents($category, $data['template_contents'] ?? []);

        $layout = app(AgreementLetterheadLayout::class);
        $marginBaseline = $layout->resolvedMarginsMm($category);

        $letterheadId = (string) $request->input('letterhead_id', 'default');
        if ($letterheadId === 'none') {
            $category->letterhead_mode = 'none';
            $category->letterhead_id = null;
        } elseif ($letterheadId === 'default' || $letterheadId === '') {
            $category->letterhead_mode = 'default';
            $category->letterhead_id = null;
        } else {
            $category->letterhead_mode = 'library';
            $category->letterhead_id = (int) $letterheadId;
        }

        $watermarkId = (string) $request->input('watermark_id', 'none');
        if ($watermarkId === 'default') {
            $category->watermark_mode = 'default';
            $category->watermark_id = null;
        } elseif ($watermarkId === 'none' || $watermarkId === '') {
            $category->watermark_mode = 'none';
            $category->watermark_id = null;
        } else {
            $category->watermark_mode = 'library';
            $category->watermark_id = (int) $watermarkId;
        }
        $category->save();

        $this->saveLetterheadMargins($request, $category, $marginBaseline, false);

        Flash::success('Agreement saved. Assigned to: ' . $this->moduleAssignmentLabel($category->assigned_modules) . '.');

        return redirect()->route('agreements.edit-agreement', [
            'company_slug' => $company_slug,
            'category' => $category->id,
        ]);
    }

    public function updateLetterheadLayout(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_edit');

        $category = AgreementCategory::findOrFail($category);
        $layout = app(AgreementLetterheadLayout::class);

        $request->validate([
            'letterhead_margins' => 'required|array',
            'letterhead_margins.top' => 'nullable|numeric|min:30|max:100',
            'letterhead_margins.bottom' => 'nullable|numeric|min:0|max:50',
            'letterhead_margins.left' => 'nullable|numeric|min:5|max:50',
            'letterhead_margins.right' => 'nullable|numeric|min:5|max:50',
            'letterhead_margins.page_size' => ['nullable', 'string', Rule::in($layout->allowedPageSizeKeys())],
        ]);

        $this->saveLetterheadMargins($request, $category, $layout->resolvedMarginsMm($category), false);
        $category->refresh();
        $size = $layout->resolvedPageSize($category);

        return response()->json([
            'ok' => true,
            'page_size' => $size['key'],
            'width_mm' => $size['width_mm'],
            'height_mm' => $size['height_mm'],
        ]);
    }

    public function destroyAgreement(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_delete');

        $category = AgreementCategory::findOrFail($category);
        $category->delete();

        Flash::success('Agreement deleted.');

        return redirect()->route('agreements.index', [
            'company_slug' => $company_slug,
        ]);
    }

    public function toggleAgreementStatus(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_edit');

        $category = AgreementCategory::findOrFail($category);
        $category->status = ! $category->status;
        $category->save();

        AgreementTemplate::query()
            ->where('category_id', $category->id)
            ->update(['status' => $category->status]);

        Flash::success('Agreement status updated.');

        return redirect()->route('agreements.index', array_filter([
            'company_slug' => $company_slug,
            'module' => $request->get('module'),
            'name' => $request->get('name'),
            'status' => $request->get('status'),
        ], static fn ($v) => $v !== null && $v !== ''));
    }

    public function templates(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_view');

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
        $this->authorizeAgreement('agreements_create');

        $category = AgreementCategory::findOrFail($category);
        $moduleKey = $category->normalizedAssignedModules()[0] ?? null;
        $placeholders = app(AgreementPlaceholderCatalog::class)->groupedForModule($moduleKey);

        return view('settings.agreements.editor', array_merge(
            $this->editorViewData($category, new AgreementTemplate([
                'template_type' => AgreementTemplate::TYPE_STANDARD,
                'status' => true,
            ])),
            ['placeholders' => $placeholders]
        ));
    }

    public function edit($company_slug, $id)
    {
        $this->authorizeAgreement('agreements_edit');

        $template = AgreementTemplate::with('category')->findOrFail($id);
        $moduleKey = $template->category?->normalizedAssignedModules()[0] ?? null;
        $placeholders = app(AgreementPlaceholderCatalog::class)->groupedForModule($moduleKey);

        return view('settings.agreements.editor', array_merge(
            $this->editorViewData($template->category, $template),
            ['placeholders' => $placeholders]
        ));
    }

    public function store(Request $request, $company_slug, $category)
    {
        $this->authorizeAgreement('agreements_create');

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
        $this->authorizeAgreement('agreements_edit');

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
        $this->authorizeAgreement('agreements_delete');

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
        $this->authorizeAgreement('agreements_create');

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
        $this->authorizeAgreement('agreements_manage_templates');

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
        $this->authorizeAgreement('agreements_edit');

        $template = AgreementTemplate::findOrFail($id);
        $template->status = !$template->status;
        $template->save();

        return back()->with('success', 'Template status updated.');
    }

    public function preview(Request $request, $company_slug, $id, AgreementPdfService $pdfService)
    {
        $this->authorizeAgreement('agreements_view');

        $template = AgreementTemplate::findOrFail($id);
        $withLetterhead = $request->boolean('letterhead', true);
        $params = [
            'company_slug' => $company_slug,
            'id' => $template->id,
            'letterhead' => $withLetterhead ? 1 : 0,
        ];
        $pdfDownloadUrl = route('agreements.preview-pdf', $params);
        $pdfStreamUrl = route('agreements.preview-pdf', $params + ['inline' => 1]);

        return view('agreements.preview', compact('template', 'withLetterhead', 'pdfDownloadUrl', 'pdfStreamUrl'));
    }

    public function previewPdf(Request $request, $company_slug, $id, AgreementPdfService $pdfService)
    {
        $this->authorizeAgreement('agreements_view');

        $template = AgreementTemplate::findOrFail($id);
        $withLetterhead = $request->boolean('letterhead', true);
        $pdf = $pdfService->previewPdf($template, null, null, $withLetterhead);
        $filename = Str::slug($template->template_name) . '-preview.pdf';

        return $pdfService->httpResponse($pdf, $filename, $request);
    }

    public function paginateHtml(
        Request $request,
        $company_slug,
        AgreementLetterheadPaginator $paginator,
        AgreementLetterheadLayout $layout,
        AgreementFontSettings $fonts,
        AgreementPdfBranding $branding
    ) {
        $this->authorizeAgreement('agreements_view');

        $data = $request->validate([
            'html' => 'nullable|string',
            'category_id' => 'nullable|integer',
        ]);

        $category = isset($data['category_id'])
            ? AgreementCategory::query()->find($data['category_id'])
            : null;

        $html = $branding->inlineHtmlImages($fonts->normalizeHtml((string) ($data['html'] ?? '')));
        $pages = $paginator->paginate($html, $layout->contentZoneHeightMm($category, true));

        return response()->json([
            'pages' => $pages !== [] ? $pages : ['<p></p>'],
        ]);
    }

    private function validatedTemplate(Request $request): array
    {
        return $request->validate([
            'template_name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'status' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]) + [
            'status' => $request->boolean('status', true),
            'template_type' => AgreementTemplate::TYPE_STANDARD,
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
        $modules = app(AgreementModuleService::class);

        return collect($this->assignableModuleKeys())
            ->mapWithKeys(fn (string $moduleKey) => [
                $moduleKey => $modules->moduleLabel($moduleKey),
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
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn($key) => is_string($key) ? trim($key) : '',
            $modules
        ))));

        // Agreements are limited to exactly one assigned module.
        return $normalized === [] ? [] : [array_values($normalized)[0]];
    }

    private function assignContractTemplate(AgreementCategory $category, int $templateId): void
    {
        $template = AgreementTemplate::query()
            ->where('category_id', $category->id)
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

    private function saveLetterheadMargins(
        Request $request,
        AgreementCategory $category,
        array $baseline,
        bool $newLetterheadUpload
    ): void {
        $input = $request->input('letterhead_margins');
        if (! is_array($input)) {
            return;
        }

        $margins = [];

        foreach (['top', 'bottom', 'left', 'right'] as $side) {
            if (! array_key_exists($side, $input) || $input[$side] === '' || $input[$side] === null) {
                continue;
            }

            $margins[$side] = (float) $input[$side];
        }

        $layout = app(AgreementLetterheadLayout::class);
        $pageSizeKey = strtolower(trim((string) ($input['page_size'] ?? '')));
        $hasPageSize = $pageSizeKey !== '' && in_array($pageSizeKey, $layout->allowedPageSizeKeys(), true);

        if ($margins === [] && ! $hasPageSize) {
            return;
        }

        if ($newLetterheadUpload && $margins !== [] && ! $this->letterheadMarginsChanged($margins, $baseline) && ! $hasPageSize) {
            return;
        }

        $category->refresh();
        $stored = is_array($category->letterhead_margins) ? $category->letterhead_margins : [];

        foreach (['top', 'bottom', 'left', 'right'] as $side) {
            if (! array_key_exists($side, $margins)) {
                continue;
            }

            $min = $side === 'top' ? 30 : ($side === 'bottom' ? 0 : 8);
            $max = in_array($side, ['top', 'bottom'], true)
                ? ($side === 'top' ? 100 : 50)
                : 55;

            $stored[$side] = max($min, min($max, round($margins[$side], 1)));
        }

        if ($hasPageSize) {
            $stored['page_size'] = $pageSizeKey;
        }

        $category->letterhead_margins = $stored;
        $category->save();
    }

    /**
     * @param  array<string, float>  $submitted
     * @param  array<string, float>  $baseline
     */
    private function letterheadMarginsChanged(array $submitted, array $baseline): bool
    {
        foreach (['top', 'bottom', 'left', 'right'] as $side) {
            if (! array_key_exists($side, $submitted)) {
                continue;
            }

            $submittedValue = round((float) $submitted[$side], 1);
            $baselineValue = round((float) ($baseline[$side] ?? 0), 1);

            if (abs($submittedValue - $baselineValue) > 0.05) {
                return true;
            }
        }

        return false;
    }

    private function seedAgreementTemplates(AgreementCategory $category, bool $status): void
    {
        $companyId = CompanyContext::id();

        // Generic default content: editable via “Manage Templates”.
        // Uses the same placeholder tokens the system already supports.
        $seedContent = <<<HTML
<p><strong>Date:</strong> {current_date}</p>
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
            'template_name' => $category->name,
            'template_type' => AgreementTemplate::TYPE_STANDARD,
            'description' => $seedContent,
            'is_default' => true,
            'status' => $status,
        ]);
    }
}

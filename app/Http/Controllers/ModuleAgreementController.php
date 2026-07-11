<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Models\AgreementPlaceholder;
use App\Models\AgreementTemplate;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Agreements\AgreementPdfBranding;
use App\Services\Agreements\AgreementPdfService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ModuleAgreementController extends Controller
{
    public function __construct(
        protected AgreementModuleService $moduleService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:agreements_view')->only('index', 'show', 'previewTemplate', 'previewTemplatePdf');
        $this->middleware('permission:agreements_edit')->only('editTemplate', 'updateTemplate', 'assignContractTemplate');
        $this->middleware('permission:agreements_delete')->only('destroy');
    }

    public function index(Request $request, $company_slug, string $module)
    {
        $this->authorizeModule($module);

        AgreementCategory::ensureDefaultsForCompany();

        $categories = AgreementCategory::query()
            ->assignedToModule($module)
            ->where('status', true)
            ->withCount(['templates' => fn($q) => $q->where('status', true)->sampleStyles()])
            ->with('defaultTemplate')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $moduleLabel = $this->moduleLabel($module);

        return view('agreements.module.index', compact('categories', 'module', 'moduleLabel'));
    }

    public function show(Request $request, $company_slug, string $module, AgreementCategory $category)
    {
        $this->authorizeModule($module);
        $this->assertCategoryAssigned($category, $module);

        $category->load([
            'templates' => fn($q) => $q->sampleStyles()->where('status', true)->orderByDesc('is_default')->orderBy('template_name'),
            'defaultTemplate',
        ]);

        $placeholders = AgreementPlaceholder::grouped();
        $pdfBranding = app(AgreementPdfBranding::class)->forCompany($category->company_id);
        $moduleLabel = $this->moduleLabel($module);

        $activeTemplateId = (int) $request->query('template', 0);
        $activeTemplate = $activeTemplateId
            ? $category->templates->firstWhere('id', $activeTemplateId)
            : null;
        $activeTemplate = $activeTemplate ?? $category->templates->first();

        return view('agreements.module.show', compact(
            'category',
            'module',
            'moduleLabel',
            'placeholders',
            'pdfBranding',
            'activeTemplate'
        ));
    }

    public function editTemplate(Request $request, $company_slug, string $module, int $template)
    {
        $this->authorizeModule($module);

        $template = $this->resolveModuleTemplate($template, $module);

        return redirect()->route('module-agreements.show', [
            'company_slug' => $company_slug,
            'module' => $module,
            'category' => $template->category_id,
            'template' => $template->id,
        ]);
    }

    public function updateTemplate(Request $request, $company_slug, string $module, int $template)
    {
        $this->authorizeModule($module);

        $template = $this->resolveModuleTemplate($template, $module);
        $validated = $request->validate([
            'description' => 'nullable|string',
        ]);

        $template->description = $validated['description'] ?? '';
        $template->save();

        return redirect()->route('module-agreements.show', [
            'company_slug' => $company_slug,
            'module' => $module,
            'category' => $template->category_id,
            'template' => $template->id,
        ])->with('success', 'Template updated successfully.');
    }

    public function assignContractTemplate(Request $request, $company_slug, string $module, AgreementCategory $category, int $template)
    {
        $this->authorizeModule($module);
        $this->assertCategoryAssigned($category, $module);

        $template = $this->resolveModuleTemplate($template, $module);
        if ((int) $template->category_id !== (int) $category->id) {
            abort(403, 'Template does not belong to this category.');
        }

        $template->setAsDefault();

        return redirect()->route('module-agreements.show', [
            'company_slug' => $company_slug,
            'module' => $module,
            'category' => $category->id,
            'template' => $template->id,
        ])->with('success', 'Contract template assigned for ' . $category->name . '.');
    }

    public function previewTemplate(Request $request, $company_slug, string $module, int $template, AgreementPdfService $pdfService)
    {
        $this->authorizeModule($module);

        $template = $this->resolveModuleTemplate($template, $module);
        $sampleEntity = $this->sampleEntityForModule($module);
        $agreementDate = $request->input('agreement_date', now()->format('Y-m-d'));
        $withLetterhead = $request->boolean('letterhead', true);
        $html = $pdfService->renderHtml($template, $sampleEntity, $agreementDate, true, $withLetterhead);

        return view('agreements.preview', compact('html', 'template', 'withLetterhead'));
    }

    public function previewTemplatePdf(Request $request, $company_slug, string $module, int $template, AgreementPdfService $pdfService)
    {
        $this->authorizeModule($module);

        $template = $this->resolveModuleTemplate($template, $module);
        $sampleEntity = $this->sampleEntityForModule($module);
        $withLetterhead = $request->boolean('letterhead', true);
        $pdf = $pdfService->previewPdf($template, $sampleEntity, null, $withLetterhead);
        $filename = Str::slug($template->template_name) . '-preview.pdf';

        return $pdf->download($filename);
    }

    private function resolveModuleTemplate(int $templateId, string $module): AgreementTemplate
    {
        $template = AgreementTemplate::query()
            ->sampleStyles()
            ->with('category')
            ->findOrFail($templateId);
        $this->assertCategoryAssigned($template->category, $module);

        return $template;
    }

    private function assertCategoryAssigned(?AgreementCategory $category, string $module): void
    {
        if (! $category || ! $category->status || ! $category->assignedToModule($module)) {
            abort(403, 'Agreement is not assigned to this module.');
        }
    }

    private function sampleEntityForModule(string $module): Model
    {
        $modelClass = config("agreement_modules.modules.{$module}.model");
        if (! $modelClass || ! class_exists($modelClass)) {
            return new \App\Models\Riders();
        }

        return $modelClass::query()->first() ?? new $modelClass();
    }

    private function moduleLabel(string $module): string
    {
        return $this->moduleService->moduleLabel($module);
    }

    private function authorizeModule(string $module): void
    {
        $this->moduleService->authorize($module);
    }
}

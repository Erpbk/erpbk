<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Models\AgreementTemplate;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Agreements\AgreementPdfService;
use App\Traits\GlobalPagination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModuleAgreementController extends Controller
{
    use GlobalPagination;

    public function __construct(
        protected AgreementModuleService $moduleService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request, $company_slug, string $module)
    {
        $this->authorizeModule($module);

        AgreementCategory::ensureDefaultsForCompany();

        $baseQuery = AgreementCategory::query()->assignedToModule($module);
        $assignedCount = (clone $baseQuery)->count();

        $query = (clone $baseQuery)
            ->with('defaultTemplate')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('agreement_code', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $status = (string) $request->input('status', '');
        if ($status === 'active') {
            $query->where('status', true);
        } elseif ($status === 'inactive') {
            $query->where('status', false);
        }

        $paginationParams = $this->getPaginationParams($request);
        $categories = $this->applyPagination($query, $paginationParams);
        if (method_exists($categories, 'appends')) {
            $categories->appends($request->except('page'));
        }

        $moduleLabel = $this->moduleLabel($module);
        $hasFilters = $request->filled('search') || $request->filled('status');

        return view('agreements.module.index', compact(
            'categories',
            'module',
            'moduleLabel',
            'assignedCount',
            'hasFilters'
        ));
    }

    public function forRecord(Request $request, $company_slug, string $module, int $record)
    {
        $this->authorizeModule($module);

        $recordModel = $this->moduleService->resolveRecord($module, $record);
        AgreementCategory::ensureDefaultsForCompany();

        $baseQuery = AgreementCategory::query()->assignedToModule($module);
        $assignedCount = (clone $baseQuery)->count();

        $query = (clone $baseQuery)
            ->with('defaultTemplate')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('agreement_code', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $status = (string) $request->input('status', '');
        if ($status === 'active') {
            $query->where('status', true);
        } elseif ($status === 'inactive') {
            $query->where('status', false);
        }

        $paginationParams = $this->getPaginationParams($request);
        $categories = $this->applyPagination($query, $paginationParams);
        if (method_exists($categories, 'appends')) {
            $categories->appends($request->except('page'));
        }

        $pageTitle = $this->moduleService->recordAgreementsTitle($module, $recordModel);
        $hasFilters = $request->filled('search') || $request->filled('status');

        return view('agreements.module.record-index', compact(
            'categories',
            'module',
            'record',
            'recordModel',
            'pageTitle',
            'assignedCount',
            'hasFilters'
        ));
    }

    public function viewForRecord(Request $request, $company_slug, string $module, int $record, int $category, AgreementPdfService $pdfService)
    {
        $this->authorizeModule($module);

        [$recordModel, $agreement, $template] = $this->moduleService->resolveAssignedAgreementForRecord(
            $module,
            $record,
            $category
        );

        $agreementDate = $request->input('agreement_date', now()->format('Y-m-d'));
        $withLetterhead = $request->boolean('letterhead', true);
        $html = $pdfService->renderHtmlForModule(
            $template,
            $module,
            $recordModel,
            $agreementDate,
            false,
            false,
            $withLetterhead
        );

        $pdfDownloadUrl = route('module-record-agreements.download', [
            'company_slug' => $company_slug,
            'module' => $module,
            'record' => $record,
            'category' => $agreement->id,
            'agreement_date' => $agreementDate,
            'letterhead' => $withLetterhead ? 1 : 0,
        ]);

        return view('agreements.preview', compact('html', 'template', 'pdfDownloadUrl', 'withLetterhead'));
    }

    public function downloadForRecord(Request $request, $company_slug, string $module, int $record, int $category, AgreementPdfService $pdfService)
    {
        $this->authorizeModule($module);

        [$recordModel, $agreement, $template] = $this->moduleService->resolveAssignedAgreementForRecord(
            $module,
            $record,
            $category
        );

        $agreementDate = $request->input('agreement_date', now()->format('Y-m-d'));
        $withLetterhead = $request->boolean('letterhead', true);
        $meta = $this->moduleService->recordMeta($module, $recordModel);
        $pdf = $pdfService->generatePdfForModule($template, $module, $recordModel, $agreementDate, $withLetterhead);
        $filename = Str::slug($meta['code'] . '-' . $agreement->name) . '.pdf';

        return $pdf->download($filename);
    }

    public function show(Request $request, $company_slug, string $module, AgreementCategory $category)
    {
        $this->authorizeModule($module);
        $this->assertCategoryAssigned($category, $module);

        $category->load([
            'templates' => fn($q) => $q->sampleStyles()->where('status', true)->orderByDesc('is_default')->orderBy('template_name'),
            'defaultTemplate',
        ]);

        $moduleLabel = $this->moduleLabel($module);

        return view('agreements.module.show', compact(
            'category',
            'module',
            'moduleLabel'
        ));
    }

    public function editTemplate(Request $request, $company_slug, string $module, int $template)
    {
        $this->abortModuleManagement();
    }

    public function updateTemplate(Request $request, $company_slug, string $module, int $template)
    {
        $this->abortModuleManagement();
    }

    public function assignContractTemplate(Request $request, $company_slug, string $module, AgreementCategory $category, int $template)
    {
        $this->abortModuleManagement();
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

    public function destroy(Request $request, $company_slug, string $module, AgreementCategory $category)
    {
        $this->abortModuleManagement();
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
        if (! $category || ! $category->assignedToModule($module)) {
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

    private function abortModuleManagement(): void
    {
        abort(403, 'Agreement management must be performed from the Agreements module.');
    }
}

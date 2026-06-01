<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Models\AgreementPlaceholder;
use App\Models\AgreementTemplate;
use App\Services\Agreements\AgreementPdfBranding;
use App\Services\Agreements\AgreementPdfService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laracasts\Flash\Flash;

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
            ->where('status', true)
            ->orderBy('sort_order')
            ->get();

        return view('settings.agreements.index', compact('categories', 'groupKey', 'groups'));
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

        return redirect()->route('settings-panel.agreements.templates', [
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

        return redirect()->route('settings-panel.agreements.templates', [
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

        return redirect()->route('settings-panel.agreements.templates', [
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

        return redirect()->route('settings-panel.agreements.templates', [
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

        return redirect()->route('settings-panel.agreements.templates', [
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
        $filename = \Str::slug($template->template_name) . '-preview.pdf';

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
}

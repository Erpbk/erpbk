<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Models\AgreementPlaceholder;
use App\Models\AgreementTemplate;
use App\Models\Riders;
use App\Services\Agreements\AgreementPdfService;
use App\Services\Email\CompanyEmailBrandingService;
use App\Services\Email\UserEmailService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AgreementGenerationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function modal(Request $request, $company_slug, int $riderId)
    {
        $this->authorizeGenerate();

        AgreementCategory::ensureDefaultsForCompany();

        $rider = Riders::findOrFail($riderId);
        $categorySlug = (string) $request->get('category', 'rider_contract');
        $category = AgreementCategory::query()
            ->where('slug', $categorySlug)
            ->where('status', true)
            ->firstOrFail();

        // Tenant-safe gating: riders module can only access agreements assigned to `riders`.
        if (! $category->assignedToModule('riders')) {
            abort(403, 'Agreement is not assigned to this module.');
        }

        $category->load('defaultTemplate');
        $defaultTemplate = $category->contractTemplate();

        if (! $defaultTemplate) {
            abort(422, 'No contract template assigned for this agreement. Configure it in Documents → Agreements.');
        }

        return view('agreements.generate-modal', compact(
            'rider',
            'category',
            'defaultTemplate'
        ));
    }

    public function preview(Request $request, $company_slug, int $riderId, AgreementPdfService $pdfService)
    {
        $this->authorizeGenerate();

        $rider = Riders::findOrFail($riderId);
        $template = $this->resolveRiderTemplate((int) $request->input('template_id'));
        $agreementDate = $request->input('agreement_date', now()->format('Y-m-d'));

        $html = $pdfService->renderHtml($template, $rider, $agreementDate);

        return view('agreements.preview', compact('html', 'template', 'rider'));
    }

    public function pdf(Request $request, $company_slug, int $riderId, AgreementPdfService $pdfService)
    {
        $this->authorizeGenerate();

        $rider = Riders::findOrFail($riderId);
        $template = $this->resolveRiderTemplate((int) $request->input('template_id'));
        $agreementDate = $request->input('agreement_date', now()->format('Y-m-d'));

        $pdf = $pdfService->generatePdf($template, $rider, $agreementDate);
        $filename = Str::slug($rider->rider_id . '-' . $template->template_name) . '.pdf';

        if ($request->boolean('download', true)) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function email(Request $request, $company_slug, int $riderId, AgreementPdfService $pdfService)
    {
        $this->authorizeGenerate();

        $wantsJson = $request->ajax() || $request->wantsJson();

        $validated = $request->validate([
            'template_id' => 'required|integer|exists:agreement_templates,id',
            'email_to' => 'required|email',
            'email_subject' => 'nullable|string|max:255',
            'email_message' => 'nullable|string',
            'agreement_date' => 'nullable|date',
        ]);

        $rider = Riders::findOrFail($riderId);
        $template = $this->resolveRiderTemplate((int) $validated['template_id']);
        $agreementDate = $validated['agreement_date'] ?? now()->format('Y-m-d');

        $user = auth()->user();
        $emailService = app(UserEmailService::class);
        $smtpPrep = $emailService->prepareCompanySmtp($user);
        if (!$smtpPrep['ready']) {
            $message = $smtpPrep['message'] ?? 'Email is not configured. Configure Gmail SMTP under Settings → Email Settings.';

            return $wantsJson
                ? response()->json(['success' => false, 'message' => $message], $smtpPrep['status'] ?? 422)
                : back()->with('error', $message);
        }

        $toEmail = trim($validated['email_to']);
        $subject = !empty($validated['email_subject'])
            ? trim($validated['email_subject'])
            : $template->template_name . ' — ' . $rider->name;

        $companyId = $template->company_id ?? CompanyContext::id();
        $brandingService = app(CompanyEmailBrandingService::class);
        $data = $brandingService->mergeIntoMailData([
            'html' => $validated['email_message'] ?? '<p>Please find attached your agreement document.</p>',
            'rider_name' => $rider->name,
            'rider_id' => $rider->rider_id,
        ], $companyId);

        $filename = Str::slug($rider->rider_id . '-' . $template->template_name) . '.pdf';
        $fromEmail = $smtpPrep['from_email'];
        $fromName = $smtpPrep['from_name'];

        try {
            $pdf = $pdfService->generatePdf($template, $rider, $agreementDate);
            $pdfBytes = $pdf->output();

            $brandingService->sendBrandedEmail('emails.general', $data, function ($message) use ($toEmail, $pdfBytes, $subject, $filename, $fromEmail, $fromName) {
                $message->to([$toEmail]);
                $message->from($fromEmail, $fromName);
                $message->replyTo($fromEmail, $fromName);
                $message->subject($subject);
                $message->attachData($pdfBytes, $filename, [
                    'mime' => 'application/pdf',
                ]);
                $message->priority(3);
            }, $companyId);
        } catch (\Throwable $e) {
            report($e);

            $message = $emailService->formatMailFailureMessage($e);

            return $wantsJson
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message)->withInput();
        }

        $successMessage = 'Agreement emailed successfully to ' . $toEmail . '.';

        return $wantsJson
            ? response()->json(['success' => true, 'message' => $successMessage])
            : back()->with('success', $successMessage);
    }

    public function editTemplate(Request $request, $company_slug, int $riderId, int $template)
    {
        $this->authorizeGenerate();

        $rider = Riders::findOrFail($riderId);
        $template = $this->resolveRiderTemplate($template);
        $category = $template->category()->first();
        $placeholders = AgreementPlaceholder::grouped();

        return view('riders.agreements.template-editor', compact(
            'rider',
            'template',
            'category',
            'placeholders'
        ));
    }

    public function updateTemplate(Request $request, $company_slug, int $riderId, int $template)
    {
        $this->authorizeGenerate();

        $template = $this->resolveRiderTemplate($template);
        $validated = $request->validate([
            'description' => 'nullable|string',
        ]);

        $template->description = $validated['description'] ?? '';
        $template->save();

        return redirect()->route('rider-agreements.templates.edit', [
            'company_slug' => $company_slug,
            'riderId' => $riderId,
            'template' => $template->id,
        ])->with('success', 'Agreement template updated from module.');
    }

    private function authorizeGenerate(): void
    {
        if (!Gate::allows('agreement_generate') && !Gate::allows('agreement_view') && !Gate::allows('rider_view')) {
            abort(403, 'Unauthorized');
        }
    }

    private function resolveRiderTemplate(int $templateId): AgreementTemplate
    {
        $template = AgreementTemplate::query()->with('category')->findOrFail($templateId);
        $category = $template->category;

        if (! $category || ! $category->status || ! $category->assignedToModule('riders')) {
            abort(403, 'Template is not assigned to Riders module.');
        }

        $assigned = $category->contractTemplate();
        if (! $assigned || (int) $assigned->id !== (int) $template->id) {
            abort(403, 'Only the contract template assigned in Agreement settings can be used.');
        }

        return $template;
    }
}

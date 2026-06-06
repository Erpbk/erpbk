<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Agreements\AgreementPdfService;
use App\Services\Email\CompanyEmailBrandingService;
use App\Services\Email\UserEmailService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModuleContractController extends Controller
{
    public function __construct(
        protected AgreementModuleService $modules,
        protected AgreementPdfService $pdfService
    ) {
        $this->middleware('auth');
    }

    public function modal(Request $request, $company_slug, string $module, int $record)
    {
        $this->modules->authorize($module);

        AgreementCategory::ensureDefaultsForCompany();

        $recordModel = $this->modules->resolveRecord($module, $record);
        $agreements = $this->modules->agreementsForModule($module);
        $meta = $this->modules->recordMeta($module, $recordModel);
        $moduleLabel = $this->modules->moduleLabel($module);

        return view('agreements.module-contract-modal', compact(
            'module',
            'record',
            'recordModel',
            'agreements',
            'meta',
            'moduleLabel'
        ));
    }

    public function preview(Request $request, $company_slug, string $module, int $record)
    {
        $this->modules->authorize($module);

        $recordModel = $this->modules->resolveRecord($module, $record);
        $template = $this->modules->resolveContractTemplate((int) $request->input('template_id'), $module);
        $agreementDate = $request->input('agreement_date', now()->format('Y-m-d'));
        $subject = $this->modules->pdfSubject($module, $recordModel);

        $html = $this->pdfService->renderHtmlForModule($template, $module, $recordModel, $agreementDate);

        return view('agreements.preview', compact('html', 'template') + ['rider' => $subject]);
    }

    public function pdf(Request $request, $company_slug, string $module, int $record)
    {
        $this->modules->authorize($module);

        $recordModel = $this->modules->resolveRecord($module, $record);
        $template = $this->modules->resolveContractTemplate((int) $request->input('template_id'), $module);
        $agreementDate = $request->input('agreement_date', now()->format('Y-m-d'));
        $meta = $this->modules->recordMeta($module, $recordModel);

        $pdf = $this->pdfService->generatePdfForModule($template, $module, $recordModel, $agreementDate);
        $filename = Str::slug($meta['code'] . '-' . $template->template_name) . '.pdf';

        if ($request->boolean('download', true)) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function email(Request $request, $company_slug, string $module, int $record)
    {
        $this->modules->authorize($module);

        $wantsJson = $request->ajax() || $request->wantsJson();

        $validated = $request->validate([
            'template_id' => 'required|integer|exists:agreement_templates,id',
            'email_to' => 'required|email',
            'email_subject' => 'nullable|string|max:255',
            'email_message' => 'nullable|string',
            'agreement_date' => 'nullable|date',
        ]);

        $recordModel = $this->modules->resolveRecord($module, $record);
        $template = $this->modules->resolveContractTemplate((int) $validated['template_id'], $module);
        $agreementDate = $validated['agreement_date'] ?? now()->format('Y-m-d');
        $meta = $this->modules->recordMeta($module, $recordModel);

        $user = auth()->user();
        $emailService = app(UserEmailService::class);
        $smtpPrep = $emailService->prepareCompanySmtp($user);
        if (! $smtpPrep['ready']) {
            $message = $smtpPrep['message'] ?? 'Email is not configured. Configure Gmail SMTP under Settings → Email Settings.';

            return $wantsJson
                ? response()->json(['success' => false, 'message' => $message], $smtpPrep['status'] ?? 422)
                : back()->with('error', $message);
        }

        $toEmail = trim($validated['email_to']);
        $subject = ! empty($validated['email_subject'])
            ? trim($validated['email_subject'])
            : $template->template_name . ' — ' . $meta['name'];

        $companyId = $template->company_id ?? CompanyContext::id();
        $brandingService = app(CompanyEmailBrandingService::class);
        $data = $brandingService->mergeIntoMailData([
            'html' => $validated['email_message'] ?? '<p>Please find attached your contract document.</p>',
            'rider_name' => $meta['name'],
            'rider_id' => $meta['code'],
        ], $companyId);

        $filename = Str::slug($meta['code'] . '-' . $template->template_name) . '.pdf';
        $fromEmail = $smtpPrep['from_email'];
        $fromName = $smtpPrep['from_name'];

        try {
            $pdf = $this->pdfService->generatePdfForModule($template, $module, $recordModel, $agreementDate);
            $pdfBytes = $pdf->output();

            $brandingService->sendBrandedEmail('emails.general', $data, function ($message) use ($toEmail, $pdfBytes, $subject, $filename, $fromEmail, $fromName) {
                $message->to([$toEmail]);
                $message->from($fromEmail, $fromName);
                $message->replyTo($fromEmail, $fromName);
                $message->subject($subject);
                $message->attachData($pdfBytes, $filename, ['mime' => 'application/pdf']);
                $message->priority(3);
            }, $companyId);
        } catch (\Throwable $e) {
            report($e);
            $message = $emailService->formatMailFailureMessage($e);

            return $wantsJson
                ? response()->json(['success' => false, 'message' => $message], 500)
                : back()->with('error', $message)->withInput();
        }

        $successMessage = 'Contract emailed successfully to ' . $toEmail . '.';

        return $wantsJson
            ? response()->json(['success' => true, 'message' => $successMessage])
            : back()->with('success', $successMessage);
    }
}

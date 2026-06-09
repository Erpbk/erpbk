<?php

namespace App\Services\Agreements;

use App\Models\AgreementTemplate;
use App\Models\Riders;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Email\CompanyEmailBrandingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;

class AgreementPdfService
{
    public function __construct(
        protected AgreementPlaceholderResolver $resolver,
        protected CompanyEmailBrandingService $branding,
        protected AgreementPdfBranding $pdfBranding
    ) {}

    public function renderHtml(
        AgreementTemplate $template,
        Riders $rider,
        ?string $agreementDate = null,
        bool $useSampleData = false
    ): string {
        return $this->renderHtmlForModule($template, 'riders', $rider, $agreementDate, $useSampleData);
    }

    public function renderHtmlForModule(
        AgreementTemplate $template,
        string $module,
        Model $record,
        ?string $agreementDate = null,
        bool $useSampleData = false
    ): string {
        $content = (string) ($template->description ?? '');
        $map = $useSampleData
            ? $this->sampleMap()
            : $this->resolver->resolveForModule($module, $record, $agreementDate);

        $body = $this->resolver->replace($content, $map);
        $branding = $this->pdfBranding->forCompany($template->company_id);
        $template->loadMissing('category');
        $view = $template->template_type === AgreementTemplate::TYPE_PREMIUM
            ? 'agreements.pdf.premium'
            : 'agreements.pdf.corporate';

        $subject = app(AgreementModuleService::class)->pdfSubject($module, $record);

        return view($view, [
            'body' => $body,
            'branding' => $branding,
            'rider' => $subject,
            'template' => $template,
            'category' => $template->category,
            'agreementDate' => $agreementDate ?? now()->format('Y-m-d'),
        ])->render();
    }

    public function generatePdf(
        AgreementTemplate $template,
        Riders $rider,
        ?string $agreementDate = null
    ) {
        return $this->generatePdfForModule($template, 'riders', $rider, $agreementDate);
    }

    public function generatePdfForModule(
        AgreementTemplate $template,
        string $module,
        Model $record,
        ?string $agreementDate = null
    ) {
        $html = $this->renderHtmlForModule($template, $module, $record, $agreementDate);

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    public function previewPdf(
        AgreementTemplate $template,
        ?Riders $rider = null,
        ?string $agreementDate = null
    ) {
        $rider = $rider ?? new Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']);
        $html = $this->renderHtml($template, $rider, $agreementDate, $rider->exists === false);

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    private function sampleMap(): array
    {
        return [
            '{rider_name}' => 'Sample Rider Name',
            '{rider_code}' => 'R-0001',
            '{rider_email}' => 'rider@example.com',
            '{rider_phone}' => '0500000000',
            '{rider_cnic}' => '784-0000-0000000-0',
            '{rider_passport_number}' => 'AB1234567',
            '{rider_nationality}' => 'Pakistan',
            '{rider_date_of_birth}' => '01-Jan-1990',
            '{rider_gender}' => 'Male',
            '{rider_address}' => 'Sample Address Line',
            '{rider_city}' => 'Dubai',
            '{rider_country}' => 'UAE',
            '{joining_date}' => '01-Jan-2024',
            '{designation}' => 'Delivery Rider',
            '{salary}' => '—',
            '{branch_name}' => 'Main Branch',
            '{company_name}' => 'Sample Company',
            '{bike_number}' => 'DXB-12345',
            '{bike_model}' => 'Honda Click',
            '{current_date}' => now()->format('d-M-Y'),
            '{agreement_date}' => now()->format('d-M-Y'),
        ];
    }
}

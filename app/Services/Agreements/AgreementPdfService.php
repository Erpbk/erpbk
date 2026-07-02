<?php

namespace App\Services\Agreements;

use App\Models\AgreementTemplate;
use App\Models\Riders;
use App\Services\Agreements\AgreementLetterheadLayout;
use App\Services\Agreements\AgreementModuleService;
use App\Services\Email\CompanyEmailBrandingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;

class AgreementPdfService
{
    public function __construct(
        protected AgreementPlaceholderResolver $resolver,
        protected CompanyEmailBrandingService $branding,
        protected AgreementPdfBranding $pdfBranding,
        protected AgreementLetterheadLayout $letterheadLayout,
        protected AgreementLetterheadPaginator $letterheadPaginator
    ) {}

    public function renderHtml(
        AgreementTemplate $template,
        Riders $rider,
        ?string $agreementDate = null,
        bool $useSampleData = false,
        bool $withLetterhead = true
    ): string {
        return $this->renderHtmlForModule($template, 'riders', $rider, $agreementDate, $useSampleData, false, $withLetterhead);
    }

    public function renderHtmlForModule(
        AgreementTemplate $template,
        string $module,
        Model $record,
        ?string $agreementDate = null,
        bool $useSampleData = false,
        bool $forPdf = false,
        bool $withLetterhead = true
    ): string {
        $content = (string) ($template->description ?? '');
        $map = $useSampleData
            ? $this->sampleMap()
            : $this->resolver->resolveForModule($module, $record, $agreementDate);

        $body = $this->resolver->replace($content, $map);
        $branding = $this->pdfBranding->forCompany($template->company_id);
        $template->loadMissing('category');
        $category = $template->category;

        $subject = app(AgreementModuleService::class)->pdfSubject($module, $record);

        $contentZoneMm = $this->letterheadLayout->contentZoneHeightMm($category, $withLetterhead);
        $pages = $this->letterheadPaginator->paginate($body, $contentZoneMm);
        $margins = $this->letterheadLayout->resolvedMarginsMm($category);
        $contentPadding = $this->letterheadLayout->contentPaddingMm($category, $withLetterhead);

        return view('agreements.pdf.letterhead', [
            'body' => $body,
            'pages' => $pages,
            'contentZoneHeightMm' => $contentZoneMm,
            'branding' => $branding,
            'letterheadMargins' => $margins,
            'contentPadding' => $contentPadding,
            'pageMarginCss' => $this->letterheadLayout->pageMarginCss($category),
            'pageWidthMm' => $this->letterheadLayout->pageWidthMm(),
            'pageHeightMm' => $this->letterheadLayout->pageHeightMm(),
            'forPdf' => $forPdf,
            'withLetterhead' => $withLetterhead,
            'rider' => $subject,
            'template' => $template,
            'category' => $category,
            'agreementDate' => $agreementDate ?? now()->format('Y-m-d'),
        ])->render();
    }

    public function generatePdf(
        AgreementTemplate $template,
        Riders $rider,
        ?string $agreementDate = null,
        bool $withLetterhead = true
    ) {
        return $this->generatePdfForModule($template, 'riders', $rider, $agreementDate, $withLetterhead);
    }

    public function generatePdfForModule(
        AgreementTemplate $template,
        string $module,
        Model $record,
        ?string $agreementDate = null,
        bool $withLetterhead = true
    ) {
        $template->loadMissing('category');
        $html = $this->renderHtmlForModule($template, $module, $record, $agreementDate, false, true, $withLetterhead);

        return $this->buildPdf($html, $template->category);
    }

    public function previewPdf(
        AgreementTemplate $template,
        ?Riders $rider = null,
        ?string $agreementDate = null,
        bool $withLetterhead = true
    ) {
        $rider = $rider ?? new Riders(['name' => 'Sample Rider', 'rider_id' => 'R-0001']);
        $template->loadMissing('category');
        $html = $this->renderHtmlForModule(
            $template,
            'riders',
            $rider,
            $agreementDate,
            $rider->exists === false,
            true,
            $withLetterhead
        );

        return $this->buildPdf($html, $template->category);
    }

    private function buildPdf(string $html, ?\App\Models\AgreementCategory $category = null)
    {
        $pageW = $this->letterheadLayout->pageWidthMm();
        $pageH = $this->letterheadLayout->pageHeightMm();
        $paperSize = [0, 0, $pageW * 2.83465, $pageH * 2.83465];

        $pdf = Pdf::loadHTML($html)->setPaper($paperSize, 'portrait');

        $dompdf = $pdf->getDomPDF();
        $options = $dompdf->getOptions();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(true);
        $options->setDefaultFont('DejaVu Sans');
        $options->setDpi(96);
        $options->setDefaultPaperSize($paperSize);
        $options->setChroot([
            storage_path('app/public'),
            public_path(),
        ]);

        return $pdf;
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

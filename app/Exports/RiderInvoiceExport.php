<?php

namespace App\Exports;

use App\Exports\Concerns\BindsTextColumns;
use App\Services\RiderInvoice\RiderInvoiceViewDataBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RiderInvoiceExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder
{
    use BindsTextColumns;

    public function __construct(
        protected Collection $invoices
    ) {
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function map($invoice): array
    {
        $rider = $invoice->rider;
        $riderLabel = $rider
            ? trim(($rider->rider_id ?? '').'-'.($rider->name ?? ''), '-')
            : '';

        $billingMonth = $invoice->billing_month
            ? Carbon::parse($invoice->billing_month)->format('M Y')
            : '';

        $invDate = $invoice->inv_date
            ? Carbon::parse($invoice->inv_date)->format('d M Y')
            : '';

        return [
            $invoice->invoice_number,
            $invDate,
            $billingMonth,
            $rider?->person_code ?? '',
            $riderLabel,
            RiderInvoiceViewDataBuilder::riderStatusLabel($rider?->status),
            (string) ($invoice->descriptions ?? ''),
            $rider?->customer?->name ?? '',
            number_format((float) $invoice->total_amount, 2, '.', ''),
            number_format((float) $invoice->final_amount, 2, '.', ''),
            match ($invoice->paymentStatusKey()) {
                'paid' => 'Paid',
                'partial' => 'Partially Paid',
                default => 'Unpaid',
            },
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice',
            'Inv Date',
            'Billing Month',
            'Person Code',
            'Rider',
            'Rider Status',
            'Descriptions',
            'Project',
            'Invoice Amount',
            'Payable Salary',
            'Status',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    protected function textColumns(): array
    {
        return ['A', 'D', 'E'];
    }
}

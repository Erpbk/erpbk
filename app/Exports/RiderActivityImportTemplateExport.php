<?php

namespace App\Exports;

use App\Services\RiderActivities\RiderActivityImportMappingService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RiderActivityImportTemplateExport implements FromArray, ShouldAutoSize, WithStyles
{
    /**
     * @param  array<string, int>  $columnMappings
     * @param  array<string, string>  $fieldLabels
     */
    public function __construct(
        private readonly int $headerRowsToSkip,
        private readonly array $columnMappings,
        private readonly array $fieldLabels,
        private readonly string $importType = RiderActivityImportMappingService::TYPE_RIDER
    ) {
    }

    public function array(): array
    {
        $maxIndex = 0;
        foreach ($this->columnMappings as $index) {
            $maxIndex = max($maxIndex, (int) $index);
        }

        $width = max(1, $maxIndex + 1);
        $rows = [];
        $skip = max(0, $this->headerRowsToSkip);

        for ($i = 0; $i < $skip; $i++) {
            $row = array_fill(0, $width, '');
            if ($i === 0 && $skip > 1) {
                $typeLabel = RiderActivityImportMappingService::importTypeLabels()[$this->importType] ?? 'Activity';
                $row[0] = $typeLabel . ' import template';
            }
            if ($i === $skip - 1) {
                foreach ($this->columnMappings as $field => $index) {
                    $row[(int) $index] = $this->fieldLabels[$field] ?? $field;
                }
            }
            $rows[] = $row;
        }

        $sample = array_fill(0, $width, '');
        $samples = [
            'date' => date('Y-m-d'),
            'rider_id' => 'RIDER001',
            'payout_type' => 'Daily',
            'delivery_rating' => '5',
            'login_hr' => '8',
            'delivered_orders' => '20',
            'cancelled_orders' => '1',
            'rejected_orders' => '0',
            'ontime_orders_percentage' => '95',
        ];
        foreach ($this->columnMappings as $field => $index) {
            $sample[(int) $index] = $samples[$field] ?? '';
        }
        $rows[] = $sample;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];

        if ($this->headerRowsToSkip > 0) {
            $styles[$this->headerRowsToSkip] = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ];
        }

        if ($this->headerRowsToSkip > 1) {
            $styles[1] = [
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ];
        }

        return $styles;
    }
}

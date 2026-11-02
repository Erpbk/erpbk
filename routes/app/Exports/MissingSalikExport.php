<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MissingSalikExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        // Remove the first row (headings) from data since we're using WithHeadings
        return array_slice($this->data, 1);
    }

    public function headings(): array
    {
        return $this->data[0] ?? [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Transaction ID
            'B' => 20, // Transaction Post Date
            'C' => 15, // Trip Date
            'D' => 15, // Trip Time
            'E' => 15, // Billing Month
            'F' => 15, // Plate Number
            'G' => 12, // Amount
            'H' => 20, // Salik Account ID
            'I' => 15, // Admin Charge
            'J' => 30, // Details
            'K' => 25, // Reason
            'L' => 12, // Row Number
            'M' => 20, // Import Date
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (headings)
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '366092']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Auto-fit columns
                $event->sheet->getDelegate()->getStyle('A:M')->getAlignment()->setWrapText(true);

                // Add borders to all cells
                $event->sheet->getDelegate()->getStyle('A1:M' . ($event->sheet->getDelegate()->getHighestRow()))
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }
}

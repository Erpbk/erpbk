<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    /**
     * @param  array{label?: string, mode?: string, from_date?: ?string, to_date?: ?string, month?: ?string}  $period
     * @param  list<array<string, mixed>>  $revenue
     * @param  list<array<string, mixed>>  $expense
     */
    public function __construct(
        protected array $period,
        protected array $revenue,
        protected array $expense,
        protected float $totalRevenue,
        protected float $totalExpense,
        protected float $netProfit,
    ) {
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['Period', (string) ($this->period['label'] ?? ''), ''];
        $rows[] = ['', '', ''];

        $rows[] = ['Revenue', '', ''];
        $this->appendTreeRows($rows, $this->revenue);
        $rows[] = ['Total Revenue', '', $this->formatAmount($this->totalRevenue)];
        $rows[] = ['', '', ''];

        $rows[] = ['Expenses', '', ''];
        $this->appendTreeRows($rows, $this->expense);
        $rows[] = ['Total Expenses', '', $this->formatAmount($this->totalExpense)];
        $rows[] = ['', '', ''];

        $rows[] = [
            $this->netProfit >= 0 ? 'Net Profit' : 'Net Loss',
            '',
            $this->formatAmount(abs($this->netProfit)),
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $highestRow = (int) $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $code = trim((string) $sheet->getCell('A'.$row)->getValue());
            $name = trim((string) $sheet->getCell('B'.$row)->getValue());
            if (in_array($code, ['Period', 'Revenue', 'Expenses', 'Total Revenue', 'Total Expenses', 'Net Profit', 'Net Loss'], true)
                || ($code === '' && in_array($name, ['Revenue', 'Expenses'], true))) {
                $sheet->getStyle('A'.$row.':C'.$row)->getFont()->setBold(true);
            }
        }

        $sheet->getStyle('C2:C'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 48,
            'C' => 16,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<array{0: string, 1: string, 2: string}>  $rows
     */
    private function appendTreeRows(array &$rows, array $nodes): void
    {
        foreach ($nodes as $node) {
            $level = (int) ($node['level'] ?? 0);
            $indent = str_repeat('  ', max(0, $level));
            $hasChildren = ! empty($node['children']);
            $amount = $hasChildren
                ? (float) ($node['subtotal_amount'] ?? 0)
                : (float) ($node['own_amount'] ?? 0);

            $rows[] = [
                (string) ($node['code'] ?? ''),
                $indent.(string) ($node['name'] ?? ''),
                $this->formatAmount($amount),
            ];

            if ($hasChildren) {
                $this->appendTreeRows($rows, $node['children']);
            }
        }
    }

    private function formatAmount(float $amount): string
    {
        if (abs($amount) < 0.005) {
            return '-';
        }

        return number_format($amount, 2, '.', '');
    }
}

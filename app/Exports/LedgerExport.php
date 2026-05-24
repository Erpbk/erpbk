<?php

namespace App\Exports;

use App\Helpers\Common;
use App\Models\Accounts;
use App\Models\Transactions;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    protected $account_id;

    protected $month;

    protected $from_date;

    protected $to_date;

    public function __construct($account_id = null, $month = null, $from_date = null, $to_date = null)
    {
        $this->account_id = $account_id;
        $this->month = $month;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    public function array(): array
    {
        // Build query
        $query = Transactions::with(['voucher']);

        if ($this->account_id) {
            $query->where('account_id', $this->account_id);
        }

        if ($this->month && ! $this->from_date && ! $this->to_date) {
            $query->where('billing_month', $this->month.'-01');
        }

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('trans_date', [$this->from_date, $this->to_date]);
        }

        $query = $query->orderBy('billing_month', 'ASC')->orderBy('trans_date', 'ASC');
        $transactions = $query->get();
        $account = Accounts::find($this->account_id);
        $account = $account ? $account->account_code.' - '.$account->name : 'N/A';

        // Calculate opening balance
        $openingBalance = $this->getOpeningBalance();

        $data = [];
        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        // Add Balance Forward row
        $data[] = [
            '',
            '',
            '',
            '',
            '',
            'Balance Forward',
            '',
            '',
            number_format($openingBalance, 2),
        ];

        // Process transactions
        foreach ($transactions as $row) {
            $runningBalance += $row->debit - $row->credit;
            $totalDebit += $row->debit;
            $totalCredit += $row->credit;

            $month = date('M Y', strtotime($row->billing_month));

            $data[] = [
                Common::DateFormat($row->trans_date),
                $account,
                $month,
                $row->voucher?->reference_number ?? '',
                $row->voucher_number, // Remove HTML tags for Excel
                strip_tags($row->narration), // Remove HTML tags for Excel
                number_format($row->debit, 2),
                number_format($row->credit, 2),
                number_format($runningBalance, 2),
            ];
        }

        // Add totals row
        $data[] = [
            '',
            '',
            '',
            '',
            '',
            'Total',
            number_format($totalDebit, 2),
            number_format($totalCredit, 2),
            number_format($runningBalance, 2),
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Account',
            'Month',
            'Reference',
            'Voucher',
            'Narration',
            'Debit',
            'Credit',
            'Balance',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Make heading bold
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 15,
            'D' => 20,
            'E' => 15,
            'F' => 50,
            'G' => 15,
            'H' => 15,
            'I' => 15,
        ];
    }

    private function getOpeningBalance()
    {
        if (! $this->month && ! $this->from_date && ! $this->to_date) {
            return 0;
        }

        if ($this->from_date && $this->to_date) {
            return Transactions::where('account_id', $this->account_id)
                ->where('trans_date', '<', $this->from_date)
                ->sum(DB::raw('debit - credit'));
        }

        return Transactions::where('account_id', $this->account_id)
            ->whereDate('billing_month', '<', $this->month.'-01')
            ->sum(DB::raw('debit - credit'));
    }
}

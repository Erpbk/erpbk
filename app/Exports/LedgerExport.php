<?php

namespace App\Exports;

use App\Helpers\Common;
use App\Models\Transactions;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $account_id;
    protected $month;

    public function __construct($account_id = null, $month = null)
    {
        $this->account_id = $account_id;
        $this->month = $month;
    }

    public function array(): array
    {
        // Build query
        $query = Transactions::with(['account']);

        if ($this->account_id) {
            $query->where('account_id', $this->account_id);
        }

        if ($this->month) {
            $query->where('billing_month', $this->month . '-01');
        }

        $query = $query->orderBy('trans_date', 'ASC');
        $transactions = $query->get();

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

            // Get voucher information
            $voucher_text = $this->getVoucherText($row);

            // Get narration
            $narration = $this->getNarration($row);

            $month = date('M Y', strtotime($row->billing_month));

            $data[] = [
                Common::DateFormat($row->trans_date),
                ($row->account->account_code ?? 'N/A') . '-' . ($row->account->name ?? 'N/A'),
                $month,
                strip_tags($voucher_text), // Remove HTML tags for Excel
                strip_tags($narration), // Remove HTML tags for Excel
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
            'Voucher',
            'Narration',
            'Debit',
            'Credit',
            'Balance'
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
            'E' => 50,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];
    }

    private function getOpeningBalance()
    {
        if (!$this->month) {
            return 0;
        }

        return Transactions::where('account_id', $this->account_id)
            ->whereDate('billing_month', '<', $this->month . '-01')
            ->sum(DB::raw("debit - credit"));
    }

    private function getVoucherText($row)
    {
        $voucher_text = '';
        $voucher_ID = '';

        if (in_array($row->reference_type, ['Voucher', 'RTA', 'LV', 'VL', 'INC', 'PN', 'PAY', 'COD', 'Salik Voucher', 'VC', 'AL', 'RiderInvoice', 'LeasingCompanyInvoice'])) {
            $vouchers = DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
            if ($vouchers) {
                $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                $voucher_text = $voucher_ID;
            } else {
                $voucher_text = 'No Voucher Found';
            }
        }

        if ($row->reference_type == 'Invoice') {
            $invoice_ID = $row->reference_id;
            $voucher_text = 'RD-' . $invoice_ID;
        }

        if ($row->reference_type == 'LeasingCompanyInvoice') {
            $invoice_ID = $row->reference_id;
            $voucher_text = 'LI-' . $invoice_ID;
        }

        return $voucher_text;
    }

    private function getNarration($row)
    {
        $narration = $row->narration;

        if ($row->reference_type == 'RTA') {
            $vouchers = DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
            if ($vouchers) {
                $fines = DB::table('rta_fines')->where('id', $vouchers->ref_id)->first();
                if ($fines) {
                    $narration = $row->narration . ', Ticket Number: ' . $fines->ticket_no . ', Bike No: ' . $fines->plate_no . ', ' . \Carbon\Carbon::parse($fines->trip_date)->format('d M Y');
                }
            }
        } elseif ($row->reference_type == 'LV') {
            $visaex = DB::table('visa_expenses')->where('id', $row->reference_id)->first();
            if ($visaex) {
                $rider = DB::Table('accounts')->where('id', $visaex->rider_id)->first();
                if ($rider) {
                    $narration = 'Paid to ' . $rider->name . ' ' . $visaex->visa_status . ' Charges ' . $visaex->date;
                }
            }
        }

        return $narration;
    }
}

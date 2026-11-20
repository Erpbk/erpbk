<?php

namespace App\Exports;

use App\Exports\Concerns\BindsTextColumns;
use App\Helpers\Common;
use App\Helpers\General;
use App\Support\VoucherAccountKind;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;

class CustomizableVoucherExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    use BindsTextColumns;

    protected $visibleColumns;

    protected $columnOrder;

    /** @var Collection<int, \App\Models\Vouchers> */
    protected Collection $vouchers;

    protected array $availableColumns = [
        'trans_date' => ['title' => 'Date'],
        'billing_month' => ['title' => 'Billing Month'],
        'voucher_id' => ['title' => 'Voucher ID'],
        'reference_number' => ['title' => 'Reference'],
        'module' => ['title' => 'Module'],
        'account_kind' => ['title' => 'Type'],
        'paid_from' => ['title' => 'Paid From'],
        'amount' => ['title' => 'Amount'],
        'created_by' => ['title' => 'Created By'],
        'updated_by' => ['title' => 'Updated By'],
        'attach_file' => ['title' => 'File'],
        'approval_status' => ['title' => 'Status'],
    ];

    protected array $moduleByType = [
        'PV' => 'Cash & Banks',
        'RV' => 'Cash & Banks',
        'TRF' => 'Cash & Banks',
        'JV' => 'Vouchers',
        'VL' => 'Visa Expense',
        'LV' => 'Visa Expense',
        'VR' => 'Visa Expense',
        'RFV' => 'RTA Fines',
        'EXP' => 'Expenses',
        'AL' => 'Riders',
        'PN' => 'Riders',
        'COD' => 'Riders',
        'INC' => 'Riders',
        'VC' => 'Riders',
    ];

    /**
     * @param  array<int, string>|null  $visibleColumns
     * @param  array<int, string>|null  $columnOrder
     * @param  Collection<int, \App\Models\Vouchers>  $vouchers
     */
    public function __construct($visibleColumns, $columnOrder, Collection $vouchers)
    {
        $defaults = array_keys($this->availableColumns);
        $this->visibleColumns = array_values(array_filter(
            is_array($visibleColumns) ? $visibleColumns : $defaults,
            fn ($k) => isset($this->availableColumns[$k])
        ));
        if ($this->visibleColumns === []) {
            $this->visibleColumns = $defaults;
        }

        $order = array_values(array_filter(
            is_array($columnOrder) ? $columnOrder : $defaults,
            fn ($k) => isset($this->availableColumns[$k])
        ));
        foreach ($this->visibleColumns as $key) {
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }
        $this->columnOrder = $order !== [] ? $order : $defaults;
        $this->vouchers = $vouchers;
    }

    public function collection()
    {
        return $this->vouchers;
    }

    public function map($voucher): array
    {
        $row = $this->buildRowData($voucher);
        $data = [];
        foreach ($this->columnOrder as $columnKey) {
            if (! in_array($columnKey, $this->visibleColumns, true)) {
                continue;
            }
            $data[] = $row[$columnKey] ?? '';
        }

        return $data;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->columnOrder as $columnKey) {
            if (! in_array($columnKey, $this->visibleColumns, true)) {
                continue;
            }
            $headings[] = $this->availableColumns[$columnKey]['title']
                ?? ucfirst(str_replace('_', ' ', (string) $columnKey));
        }

        return $headings;
    }

    protected function textColumns(): array
    {
        return $this->columnLettersForKeys(
            $this->columnOrder,
            $this->visibleColumns,
            ['voucher_id', 'reference_number', 'paid_from', 'module', 'account_kind', 'created_by', 'updated_by', 'attach_file', 'approval_status']
        );
    }

    /**
     * @param  \App\Models\Vouchers  $voucher
     * @return array<string, string|float|int>
     */
    protected function buildRowData($voucher): array
    {
        $voucherId = $voucher->voucher_type.'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);
        $kind = (string) ($voucher->list_account_kind ?? VoucherAccountKind::KIND_OTHER);
        $paidFrom = collect($voucher->list_paid_from ?? [])
            ->pluck('label')
            ->filter()
            ->implode('; ');

        $statusRaw = $voucher->approval_status ?? 'pending_approval';
        $status = $statusRaw === 'pending_approval' ? 'pending' : $statusRaw;

        $types = General::VoucherType();

        return [
            'trans_date' => $voucher->trans_date ? Common::DateFormat($voucher->trans_date) : '',
            'billing_month' => $voucher->billing_month ? Common::MonthFormat($voucher->billing_month) : '',
            'voucher_id' => $voucherId,
            'reference_number' => (string) ($voucher->reference_number ?? ''),
            'module' => $this->moduleByType[$voucher->voucher_type] ?? ($types[$voucher->voucher_type] ?? 'Vouchers'),
            'account_kind' => VoucherAccountKind::labelForKind($kind),
            'paid_from' => $paidFrom,
            'amount' => round((float) ($voucher->amount ?? 0), 2),
            'created_by' => Common::UserName($voucher->Created_By),
            'updated_by' => Common::UserName($voucher->Updated_By),
            'attach_file' => $voucher->attach_file ? basename((string) $voucher->attach_file) : '',
            'approval_status' => ucfirst(str_replace('_', ' ', (string) $status)),
        ];
    }
}
